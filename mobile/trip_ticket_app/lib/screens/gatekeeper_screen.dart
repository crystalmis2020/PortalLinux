import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/trip_ticket.dart';
import '../models/user_session.dart';
import '../services/api_service.dart';
import '../theme/portal_theme.dart';
import '../widgets/status_chip.dart';
import 'qr_scan_screen.dart';

class GatekeeperScreen extends StatefulWidget {
  const GatekeeperScreen({
    super.key,
    required this.api,
    required this.session,
    required this.onUnauthorized,
  });

  final ApiService api;
  final UserSession session;
  final Future<void> Function() onUnauthorized;

  @override
  State<GatekeeperScreen> createState() => _GatekeeperScreenState();
}

class _GatekeeperScreenState extends State<GatekeeperScreen> {
  final _date = DateFormat('MMM d, yyyy h:mm a');
  final _search = TextEditingController();
  final _qr = TextEditingController();
  List<TripTicket> _ready = [];
  List<TripTicket> _returning = [];
  List<TripTicket> _searchResults = [];
  bool _loading = true;
  bool _searching = false;
  String? _error;
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    _loadLists();
  }

  @override
  void dispose() {
    _search.dispose();
    _qr.dispose();
    super.dispose();
  }

  Future<void> _loadLists() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final results = await Future.wait([
        widget.api.gatekeeperReadyForDeparture(),
        widget.api.gatekeeperAwaitingReturn(),
      ]);

      if (!mounted) {
        return;
      }

      setState(() {
        _ready = results[0];
        _returning = results[1];
        _loading = false;
      });
    } on ApiException catch (error) {
      if (error.statusCode == 401) {
        await widget.onUnauthorized();
        return;
      }
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error.message;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = 'Unable to load gatekeeper trips.';
        _loading = false;
      });
    }
  }

  Future<void> _runSearch() async {
    final query = _search.text.trim();
    if (query.isEmpty) {
      setState(() => _searchResults = []);
      return;
    }

    setState(() {
      _searching = true;
      _error = null;
    });

    try {
      final tickets = await widget.api.gatekeeperSearch(query);
      if (!mounted) {
        return;
      }
      setState(() {
        _searchResults = tickets;
        _searching = false;
      });
    } on ApiException catch (error) {
      if (error.statusCode == 401) {
        await widget.onUnauthorized();
        return;
      }
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error.message;
        _searching = false;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = 'Unable to search trip tickets.';
        _searching = false;
      });
    }
  }

  Future<void> _scanQr() async {
    final token = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => const QrScanScreen()),
    );

    if (token == null || token.trim().isEmpty) {
      return;
    }

    _qr.text = token.trim();
    await _lookupQr();
  }

  Future<void> _lookupQr() async {
    final token = _qr.text.trim();
    if (token.isEmpty) {
      return;
    }

    setState(() {
      _searching = true;
      _error = null;
    });

    try {
      final ticket = await widget.api.gatekeeperQrLookup(token);
      if (!mounted) {
        return;
      }
      setState(() {
        _searchResults = [ticket];
        _tab = 2;
        _searching = false;
      });
    } on ApiException catch (error) {
      if (error.statusCode == 401) {
        await widget.onUnauthorized();
        return;
      }
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error.message;
        _searching = false;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = 'Unable to find QR ticket.';
        _searching = false;
      });
    }
  }

  Future<void> _record(TripTicket ticket, String action) async {
    final result = await showDialog<_GatekeeperRecordResult>(
      context: context,
      builder: (_) => _GatekeeperRemarksDialog(action: action),
    );

    if (result == null) {
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final updatedTicket = action == 'departure'
          ? await widget.api.gatekeeperRecordDeparture(
              ticket.id,
              result.remarks,
              result.actualDateTime,
            )
          : await widget.api.gatekeeperRecordReturn(
              ticket.id,
              result.remarks,
              result.actualDateTime,
            );

      if (_tab == 2) {
        _replaceSearchResult(updatedTicket);
      }

      await _loadLists();
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            action == 'departure'
                ? 'Actual departure recorded.'
                : 'Actual return recorded.',
          ),
        ),
      );
    } on ApiException catch (error) {
      if (error.statusCode == 401) {
        await widget.onUnauthorized();
        return;
      }
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error.message;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = 'Unable to update trip ticket.';
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _loadLists,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
        children: [
          _header(),
          const SizedBox(height: 14),
          _summary(),
          const SizedBox(height: 14),
          _tabs(),
          const SizedBox(height: 14),
          if (_error != null) ...[
            _ErrorPanel(message: _error!, onRetry: _loadLists),
            const SizedBox(height: 14),
          ],
          if (_loading)
            const Padding(
              padding: EdgeInsets.only(top: 80),
              child: Center(child: CircularProgressIndicator()),
            )
          else
            _tabContent(),
        ],
      ),
    );
  }

  Widget _header() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Gatekeeper',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 4),
              Text(
                'Welcome, ${widget.session.fullName.split(' ').first}.',
                style: const TextStyle(color: PortalColors.muted),
              ),
            ],
          ),
        ),
        IconButton.filledTonal(
          tooltip: 'Refresh',
          onPressed: _loading ? null : _loadLists,
          icon: const Icon(Icons.refresh),
        ),
      ],
    );
  }

  Widget _summary() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: PortalColors.brandDark,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          _summaryCount('Ready', _ready.length),
          Container(width: 1, height: 44, color: Colors.white24),
          _summaryCount('Returning', _returning.length),
        ],
      ),
    );
  }

  Widget _summaryCount(String label, int count) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Text(
            label.toUpperCase(),
            style: const TextStyle(
              color: Color(0xffd8eadc),
              fontSize: 11,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            '$count',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }

  Widget _tabs() {
    return SegmentedButton<int>(
      segments: const [
        ButtonSegment(
          value: 0,
          icon: Icon(Icons.logout),
          label: Text('Ready'),
        ),
        ButtonSegment(
          value: 1,
          icon: Icon(Icons.login),
          label: Text('Return'),
        ),
        ButtonSegment(
          value: 2,
          icon: Icon(Icons.search),
          label: Text('Search'),
        ),
      ],
      selected: {_tab},
      onSelectionChanged: (value) => setState(() => _tab = value.first),
    );
  }

  void _replaceSearchResult(TripTicket updatedTicket) {
    final index = _searchResults.indexWhere((ticket) => ticket.id == updatedTicket.id);
    if (index == -1) {
      return;
    }

    _searchResults[index] = updatedTicket;
  }

  Widget _tabContent() {
    switch (_tab) {
      case 1:
        return _ticketList(_returning, 'return');
      case 2:
        return _searchContent();
      default:
        return _ticketList(_ready, 'departure');
    }
  }

  Widget _searchContent() {
    return Column(
      children: [
        TextField(
          controller: _search,
          textInputAction: TextInputAction.search,
          onSubmitted: (_) => _runSearch(),
          decoration: InputDecoration(
            hintText: 'Search plate, ticket, driver, requester',
            prefixIcon: const Icon(Icons.search),
            suffixIcon: IconButton(
              tooltip: 'Search',
              onPressed: _searching ? null : _runSearch,
              icon: _searching
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.arrow_forward),
            ),
          ),
        ),
        const SizedBox(height: 10),
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: TextField(
                controller: _qr,
                textInputAction: TextInputAction.search,
                onSubmitted: (_) => _lookupQr(),
                decoration: InputDecoration(
                  hintText: 'Enter QR token, example TT:abc123',
                  prefixIcon: const Icon(Icons.qr_code_scanner),
                  suffixIcon: IconButton(
                    tooltip: 'Lookup QR token',
                    onPressed: _searching ? null : _lookupQr,
                    icon: const Icon(Icons.open_in_new),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            SizedBox(
              height: 56,
              child: FilledButton.icon(
                onPressed: _searching ? null : _scanQr,
                icon: const Icon(Icons.camera_alt_outlined),
                label: const Text('Scan'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 14),
        _ticketList(_searchResults, null, emptySearch: true),
      ],
    );
  }

  Widget _ticketList(
    List<TripTicket> tickets,
    String? action, {
    bool emptySearch = false,
  }) {
    if (tickets.isEmpty) {
      return _EmptyPanel(
        icon: emptySearch ? Icons.search_off_outlined : Icons.task_alt,
        title: emptySearch ? 'No search results' : 'No trips here',
        message: emptySearch
            ? 'Search a plate number, ticket number, or QR token.'
            : 'Pull down to refresh the daily list.',
      );
    }

    return Column(
      children: tickets
          .map(
            (ticket) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _TicketCard(
                ticket: ticket,
                date: _date,
                onRecord: _recordActionFor(ticket, action) == null
                    ? null
                    : () => _record(ticket, _recordActionFor(ticket, action)!),
              ),
            ),
          )
          .toList(),
    );
  }
}

String? _recordActionFor(TripTicket ticket, String? listAction) {
  if (listAction != null) {
    return listAction;
  }

  if (ticket.canRecordDeparture) {
    return 'departure';
  }

  if (ticket.canRecordReturn) {
    return 'return';
  }

  return null;
}

class _TicketCard extends StatelessWidget {
  const _TicketCard({
    required this.ticket,
    required this.date,
    this.onRecord,
  });

  final TripTicket ticket;
  final DateFormat date;
  final VoidCallback? onRecord;

  @override
  Widget build(BuildContext context) {
    final canRecord = ticket.canRecordDeparture || ticket.canRecordReturn;
    final actionLabel = ticket.canRecordDeparture ? 'Departure' : 'Return';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    ticket.displayNumber,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                StatusChip(status: ticket.status),
              ],
            ),
            const SizedBox(height: 12),
            _line(Icons.directions_car_outlined, _vehicleText(), strong: true),
            const SizedBox(height: 8),
            _line(Icons.person_outline, ticket.driverName ?? 'Driver pending'),
            const SizedBox(height: 8),
            _line(Icons.location_on_outlined,
                ticket.destination ?? 'No destination'),
            const SizedBox(height: 8),
            _line(
              Icons.schedule,
              _timeText(),
            ),
            const Divider(height: 24),
            Row(
              children: [
                Expanded(
                  child: Text(
                    ticket.requesterName ?? 'Requester unavailable',
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: PortalColors.muted,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                if (onRecord != null && canRecord) ...[
                  const SizedBox(width: 10),
                  FilledButton.icon(
                    onPressed: onRecord,
                    icon: Icon(
                      ticket.canRecordDeparture ? Icons.logout : Icons.login,
                      size: 18,
                    ),
                    label: Text(actionLabel),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _line(IconData icon, String text, {bool strong = false}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: PortalColors.muted),
        const SizedBox(width: 9),
        Expanded(
          child: Text(
            text,
            style: TextStyle(
              color: strong ? PortalColors.brandDark : const Color(0xff475569),
              fontWeight: strong ? FontWeight.w700 : FontWeight.w500,
              height: 1.35,
            ),
          ),
        ),
      ],
    );
  }

  String _vehicleText() {
    return ticket.vehiclePlateNumber ??
        ticket.vehicleDetails ??
        'Vehicle pending';
  }

  String _timeText() {
    if (ticket.canRecordDeparture && ticket.requestedStart != null) {
      return 'Scheduled ${date.format(ticket.requestedStart!)}';
    }
    if (ticket.actualDeparture != null && ticket.actualReturn == null) {
      return 'Departed ${date.format(ticket.actualDeparture!)}';
    }
    if (ticket.actualReturn != null) {
      return 'Returned ${date.format(ticket.actualReturn!)}';
    }
    return 'Schedule pending';
  }
}

class _GatekeeperRecordResult {
  const _GatekeeperRecordResult({
    required this.actualDateTime,
    required this.remarks,
  });

  final DateTime actualDateTime;
  final String remarks;
}

class _GatekeeperRemarksDialog extends StatefulWidget {
  const _GatekeeperRemarksDialog({required this.action});

  final String action;

  @override
  State<_GatekeeperRemarksDialog> createState() =>
      _GatekeeperRemarksDialogState();
}

class _GatekeeperRemarksDialogState extends State<_GatekeeperRemarksDialog> {
  final _remarks = TextEditingController();
  final _dateTimeFormat = DateFormat('MMM d, yyyy h:mm a');
  late DateTime _actualDateTime;

  @override
  void initState() {
    super.initState();
    _actualDateTime = DateTime.now();
  }

  @override
  void dispose() {
    _remarks.dispose();
    super.dispose();
  }

  Future<void> _changeDateTime() async {
    final date = await showDatePicker(
      context: context,
      initialDate: _actualDateTime,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 30)),
    );

    if (date == null || !mounted) {
      return;
    }

    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(_actualDateTime),
    );

    if (time == null) {
      return;
    }

    setState(() {
      _actualDateTime = DateTime(
        date.year,
        date.month,
        date.day,
        time.hour,
        time.minute,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDeparture = widget.action == 'departure';
    final actionLabel = isDeparture ? 'departure' : 'return';

    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      title: Row(
        children: [
          Icon(isDeparture ? Icons.logout : Icons.login),
          const SizedBox(width: 10),
          Expanded(
            child: Text(isDeparture ? 'Record departure' : 'Record return'),
          ),
        ],
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Actual $actionLabel time',
            style: const TextStyle(
              color: PortalColors.muted,
              fontSize: 12,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 8),
          InkWell(
            borderRadius: BorderRadius.circular(8),
            onTap: _changeDateTime,
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                border: Border.all(color: PortalColors.border),
                borderRadius: BorderRadius.circular(8),
                color: const Color(0xfff8fafc),
              ),
              child: Row(
                children: [
                  const Icon(Icons.event_outlined, color: PortalColors.primary),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      _dateTimeFormat.format(_actualDateTime),
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                  const Text(
                    'Change',
                    style: TextStyle(
                      color: PortalColors.primary,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _remarks,
            minLines: 3,
            maxLines: 5,
            decoration: const InputDecoration(
              labelText: 'Remarks optional',
              alignLabelWithHint: true,
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () => Navigator.of(context).pop(
            _GatekeeperRecordResult(
              actualDateTime: _actualDateTime,
              remarks: _remarks.text.trim(),
            ),
          ),
          child: const Text('Confirm'),
        ),
      ],
    );
  }
}

class _EmptyPanel extends StatelessWidget {
  const _EmptyPanel({
    required this.icon,
    required this.title,
    required this.message,
  });

  final IconData icon;
  final String title;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 34),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: PortalColors.border),
      ),
      child: Column(
        children: [
          Icon(icon, size: 40, color: PortalColors.muted),
          const SizedBox(height: 14),
          Text(title, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 6),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: PortalColors.muted, height: 1.45),
          ),
        ],
      ),
    );
  }
}

class _ErrorPanel extends StatelessWidget {
  const _ErrorPanel({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xfffff1f3),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xffffcdd6)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.error_outline, color: Color(0xffc01048)),
          const SizedBox(width: 9),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: Color(0xff9f1239),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          IconButton(
            tooltip: 'Try again',
            onPressed: onRetry,
            icon: const Icon(Icons.refresh, color: Color(0xff9f1239)),
          ),
        ],
      ),
    );
  }
}
