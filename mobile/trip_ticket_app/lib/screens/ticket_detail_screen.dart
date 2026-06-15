import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/trip_ticket.dart';
import '../services/api_service.dart';
import '../theme/portal_theme.dart';
import '../widgets/status_chip.dart';

class TicketDetailScreen extends StatefulWidget {
  const TicketDetailScreen({
    super.key,
    required this.api,
    required this.ticketId,
  });

  final ApiService api;
  final int ticketId;

  @override
  State<TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends State<TicketDetailScreen> {
  final _date = DateFormat('MMM d, yyyy h:mm a');
  TripTicket? _ticket;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadTicket();
  }

  Future<void> _loadTicket() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final ticket = await widget.api.ticket(widget.ticketId);
      if (!mounted) {
        return;
      }

      setState(() {
        _ticket = ticket;
        _loading = false;
      });
    } on ApiException catch (error) {
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
        _error = 'Unable to load trip ticket.';
        _loading = false;
      });
    }
  }

  Future<void> _confirmAction(String action) async {
    final remarks = await showDialog<String>(
      context: context,
      builder: (context) => _RemarksDialog(action: action),
    );

    if (remarks == null) {
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      switch (action) {
        case 'approve':
          await widget.api.approve(widget.ticketId, remarks);
          break;
        case 'reject':
          await widget.api.reject(widget.ticketId, remarks);
          break;
        case 'return':
          await widget.api.returnForCorrection(widget.ticketId, remarks);
          break;
      }

      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = error.message;
        _saving = false;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = 'Unable to update trip ticket.';
        _saving = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final ticket = _ticket;

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Trip Ticket',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null && ticket == null
              ? _errorBody()
              : ticket == null
                  ? const Center(child: Text('Ticket not found.'))
                  : _ticketBody(ticket),
      bottomNavigationBar: ticket == null || ticket.status != 'for_approval'
          ? null
          : _actionBar(),
    );
  }

  Widget _errorBody() {
    return ListView(
      padding: const EdgeInsets.all(24),
      children: [
        const SizedBox(height: 80),
        const Icon(
          Icons.cloud_off_outlined,
          size: 44,
          color: PortalColors.muted,
        ),
        const SizedBox(height: 16),
        Text(
          _error!,
          textAlign: TextAlign.center,
          style: const TextStyle(color: PortalColors.muted),
        ),
        const SizedBox(height: 18),
        FilledButton.icon(
          onPressed: _loadTicket,
          icon: const Icon(Icons.refresh),
          label: const Text('Try again'),
        ),
      ],
    );
  }

  Widget _ticketBody(TripTicket ticket) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
      children: [
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: PortalColors.brandDark,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      ticket.displayNumber,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  StatusChip(status: ticket.status),
                ],
              ),
              const SizedBox(height: 14),
              Text(
                ticket.destination ?? 'No destination',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
              if (ticket.requestedStart != null) ...[
                const SizedBox(height: 6),
                Text(
                  _date.format(ticket.requestedStart!),
                  style: const TextStyle(color: Color(0xffcbd5e1)),
                ),
              ],
            ],
          ),
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xfffff1f3),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: const Color(0xffffcdd6)),
            ),
            child: Text(
              _error!,
              style: const TextStyle(
                color: Color(0xff9f1239),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
        const SizedBox(height: 14),
        _section(
          icon: Icons.person_outline,
          title: 'Requester',
          children: [
            _row('Name', ticket.requesterName),
            _row(
              'Department / Section',
              [ticket.departmentName, ticket.sectionName]
                  .whereType<String>()
                  .where((value) => value.isNotEmpty)
                  .join(' / '),
            ),
            _row('Contact Number', ticket.contactNumber),
          ],
        ),
        const SizedBox(height: 12),
        _section(
          icon: Icons.route_outlined,
          title: 'Trip Details',
          children: [
            _row('Destination', ticket.destination),
            _row('Purpose', ticket.purpose),
            _row('Passengers / Personnel', ticket.passengers),
            _row('Requested Departure', _format(ticket.requestedStart)),
            _row('Requested Return', _format(ticket.requestedEnd)),
          ],
        ),
        const SizedBox(height: 12),
        _section(
          icon: Icons.local_shipping_outlined,
          title: 'Operational Details',
          children: [
            _row('Vehicle', ticket.vehicleDetails, pending: true),
            _row('Driver', ticket.driverName, pending: true),
            _row(
              'Actual Departure',
              _format(ticket.actualDeparture),
              pending: true,
            ),
            _row(
              'Actual Return',
              _format(ticket.actualReturn),
              pending: true,
            ),
            _row('Encoded By', ticket.encoderName, pending: true),
            _row('Encoder Remarks', ticket.remarks),
            _row('Approval Remarks', ticket.approvalRemarks),
          ],
        ),
      ],
    );
  }

  Widget _section({
    required IconData icon,
    required String title,
    required List<Widget> children,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: PortalColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
            child: Row(
              children: [
                Icon(icon, size: 20, color: PortalColors.primary),
                const SizedBox(width: 9),
                Text(title, style: Theme.of(context).textTheme.titleMedium),
              ],
            ),
          ),
          const Divider(height: 1),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 6),
            child: Column(children: children),
          ),
        ],
      ),
    );
  }

  Widget _row(String label, String? value, {bool pending = false}) {
    final hasValue = value != null && value.trim().isNotEmpty;
    final display = hasValue ? value! : (pending ? 'Pending' : 'N/A');

    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 118,
            child: Text(
              label,
              style: const TextStyle(
                color: PortalColors.muted,
                fontSize: 12,
                fontWeight: FontWeight.w700,
                height: 1.35,
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              display,
              style: TextStyle(
                color: hasValue
                    ? const Color(0xff334155)
                    : PortalColors.muted,
                fontWeight: hasValue ? FontWeight.w600 : FontWeight.w500,
                height: 1.35,
                fontStyle: hasValue ? FontStyle.normal : FontStyle.italic,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _actionBar() {
    return SafeArea(
      top: false,
      child: Container(
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: PortalColors.border)),
        ),
        child: Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _saving ? null : () => _confirmAction('return'),
                icon: const Icon(Icons.undo, size: 18),
                label: const Text('Return'),
              ),
            ),
            const SizedBox(width: 8),
            IconButton.outlined(
              tooltip: 'Reject',
              onPressed: _saving ? null : () => _confirmAction('reject'),
              icon: const Icon(Icons.close, color: Color(0xffc01048)),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: FilledButton.icon(
                onPressed: _saving ? null : () => _confirmAction('approve'),
                icon: _saving
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.check, size: 18),
                label: Text(_saving ? 'Saving...' : 'Approve'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String? _format(DateTime? value) {
    return value == null ? null : _date.format(value);
  }
}

class _RemarksDialog extends StatefulWidget {
  const _RemarksDialog({required this.action});

  final String action;

  @override
  State<_RemarksDialog> createState() => _RemarksDialogState();
}

class _RemarksDialogState extends State<_RemarksDialog> {
  final _remarks = TextEditingController();

  @override
  void dispose() {
    _remarks.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final config = switch (widget.action) {
      'return' => (
          title: 'Return for correction',
          description: 'Tell the requester what needs to be corrected.',
          icon: Icons.undo,
          color: const Color(0xffb54708),
        ),
      'reject' => (
          title: 'Reject request',
          description: 'Add a reason for rejecting this trip ticket.',
          icon: Icons.close,
          color: const Color(0xffc01048),
        ),
      _ => (
          title: 'Approve request',
          description: 'Add optional approval remarks before continuing.',
          icon: Icons.check,
          color: const Color(0xff027a48),
        ),
    };

    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      title: Row(
        children: [
          Icon(config.icon, color: config.color),
          const SizedBox(width: 10),
          Expanded(child: Text(config.title)),
        ],
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            config.description,
            style: const TextStyle(color: PortalColors.muted),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _remarks,
            minLines: 3,
            maxLines: 5,
            autofocus: widget.action != 'approve',
            decoration: const InputDecoration(
              labelText: 'Remarks',
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
          style: FilledButton.styleFrom(backgroundColor: config.color),
          onPressed: () => Navigator.of(context).pop(_remarks.text.trim()),
          child: const Text('Confirm'),
        ),
      ],
    );
  }
}
