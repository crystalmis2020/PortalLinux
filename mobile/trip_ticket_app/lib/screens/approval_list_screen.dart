import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/trip_ticket.dart';
import '../models/user_session.dart';
import '../services/api_service.dart';
import '../theme/portal_theme.dart';
import '../widgets/status_chip.dart';
import 'ticket_detail_screen.dart';

class ApprovalListScreen extends StatefulWidget {
  const ApprovalListScreen({
    super.key,
    required this.api,
    required this.session,
    required this.onUnauthorized,
  });

  final ApiService api;
  final UserSession session;
  final Future<void> Function() onUnauthorized;

  @override
  State<ApprovalListScreen> createState() => _ApprovalListScreenState();
}

class _ApprovalListScreenState extends State<ApprovalListScreen> {
  final _date = DateFormat('MMM d, yyyy');
  final _search = TextEditingController();
  List<TripTicket> _tickets = [];
  bool _loading = true;
  String? _error;
  String _query = '';

  List<TripTicket> get _visibleTickets {
    final query = _query.trim().toLowerCase();
    if (query.isEmpty) {
      return _tickets;
    }

    return _tickets.where((ticket) {
      return [
        ticket.displayNumber,
        ticket.destination,
        ticket.requesterName,
        ticket.departmentName,
      ].whereType<String>().any(
            (value) => value.toLowerCase().contains(query),
          );
    }).toList();
  }

  @override
  void initState() {
    super.initState();
    _loadTickets();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _loadTickets() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final tickets = await widget.api.ticketsForApproval();
      if (!mounted) {
        return;
      }

      setState(() {
        _tickets = tickets;
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
        _error = 'Unable to load trip tickets.';
        _loading = false;
      });
    }
  }

  Future<void> _openTicket(TripTicket ticket) async {
    final changed = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => TicketDetailScreen(
          api: widget.api,
          ticketId: ticket.id,
        ),
      ),
    );

    if (changed == true) {
      await _loadTickets();
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _loadTickets,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 12),
            sliver: SliverToBoxAdapter(child: _header()),
          ),
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            sliver: SliverToBoxAdapter(child: _summary()),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
            sliver: SliverToBoxAdapter(child: _searchField()),
          ),
          ..._content(),
          const SliverToBoxAdapter(child: SizedBox(height: 28)),
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
                'Trip Tickets',
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
          onPressed: _loading ? null : _loadTickets,
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
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(
              Icons.pending_actions,
              color: Colors.white,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'FOR APPROVAL',
                  style: TextStyle(
                    color: Color(0xffd8eadc),
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  _loading
                      ? 'Checking requests...'
                      : '${_tickets.length} pending',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right, color: Colors.white70),
        ],
      ),
    );
  }

  Widget _searchField() {
    return TextField(
      controller: _search,
      onChanged: (value) => setState(() => _query = value),
      decoration: InputDecoration(
        hintText: 'Search request, destination, requester',
        prefixIcon: const Icon(Icons.search),
        suffixIcon: _query.isEmpty
            ? null
            : IconButton(
                tooltip: 'Clear search',
                onPressed: () {
                  _search.clear();
                  setState(() => _query = '');
                },
                icon: const Icon(Icons.close),
              ),
      ),
    );
  }

  List<Widget> _content() {
    if (_loading) {
      return const [
        SliverFillRemaining(
          hasScrollBody: false,
          child: Center(child: CircularProgressIndicator()),
        ),
      ];
    }

    if (_error != null) {
      return [
        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          sliver: SliverToBoxAdapter(
            child: _MessagePanel(
              icon: Icons.cloud_off_outlined,
              title: 'Could not load requests',
              message: _error!,
              actionLabel: 'Try again',
              onAction: _loadTickets,
            ),
          ),
        ),
      ];
    }

    final tickets = _visibleTickets;
    if (tickets.isEmpty) {
      return [
        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          sliver: SliverToBoxAdapter(
            child: _MessagePanel(
              icon: _query.isEmpty ? Icons.task_alt : Icons.search_off_outlined,
              title: _query.isEmpty ? 'Queue is clear' : 'No matching requests',
              message: _query.isEmpty
                  ? 'There are no trip tickets waiting for approval.'
                  : 'Try a different request number, destination, or requester.',
            ),
          ),
        ),
      ];
    }

    return [
      SliverPadding(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        sliver: SliverList.separated(
          itemCount: tickets.length,
          separatorBuilder: (_, __) => const SizedBox(height: 10),
          itemBuilder: (context, index) => _ticketCard(tickets[index]),
        ),
      ),
    ];
  }

  Widget _ticketCard(TripTicket ticket) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: () => _openTicket(ticket),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          ticket.displayNumber,
                          style: const TextStyle(
                            color: PortalColors.muted,
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 5),
                        Text(
                          ticket.destination ?? 'No destination',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  StatusChip(status: ticket.status),
                ],
              ),
              const SizedBox(height: 14),
              _infoBlock(
                icon: Icons.person_outline,
                label: 'Requester',
                value: ticket.requesterName ?? 'Requester unavailable',
              ),
              const SizedBox(height: 10),
              _infoBlock(
                icon: Icons.event_outlined,
                label: 'Requested Schedule',
                value: _schedule(ticket),
              ),
              const SizedBox(height: 10),
              _infoBlock(
                icon: Icons.business_outlined,
                label: 'Department',
                value: ticket.departmentName ?? 'Department unavailable',
              ),
              if (ticket.purpose != null && ticket.purpose!.trim().isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  ticket.purpose!,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Color(0xff475569),
                    height: 1.4,
                  ),
                ),
              ],
              const Divider(height: 26),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      ticket.sectionName?.isNotEmpty == true
                          ? ticket.sectionName!
                          : 'Tap to review request details',
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: PortalColors.muted,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  const Text(
                    'Review',
                    style: TextStyle(
                      color: PortalColors.primary,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(width: 2),
                  const Icon(
                    Icons.chevron_right,
                    size: 20,
                    color: PortalColors.primary,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _infoBlock({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xfff8fafc),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: PortalColors.border),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: PortalColors.primary),
          const SizedBox(width: 9),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    color: PortalColors.muted,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    color: Color(0xff334155),
                    fontWeight: FontWeight.w700,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _schedule(TripTicket ticket) {
    final start = ticket.requestedStart;
    final end = ticket.requestedEnd;

    if (start == null && end == null) {
      return 'Schedule unavailable';
    }

    if (start != null && end != null) {
      final sameDay = start.year == end.year &&
          start.month == end.month &&
          start.day == end.day;

      if (sameDay) {
        return _date.format(start);
      }

      return '${_date.format(start)} - ${_date.format(end)}';
    }

    if (start != null) {
      return _date.format(start);
    }

    return _date.format(end!);
  }
}

class _MessagePanel extends StatelessWidget {
  const _MessagePanel({
    required this.icon,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

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
          if (actionLabel != null && onAction != null) ...[
            const SizedBox(height: 18),
            FilledButton.icon(
              onPressed: onAction,
              icon: const Icon(Icons.refresh),
              label: Text(actionLabel!),
            ),
          ],
        ],
      ),
    );
  }
}
