import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/trip_ticket.dart';
import '../models/user_session.dart';
import '../services/api_service.dart';
import '../widgets/status_chip.dart';
import 'ticket_detail_screen.dart';

class ApprovalListScreen extends StatefulWidget {
  const ApprovalListScreen({
    super.key,
    required this.api,
    required this.session,
    required this.onLogout,
  });

  final ApiService api;
  final UserSession session;
  final VoidCallback onLogout;

  @override
  State<ApprovalListScreen> createState() => _ApprovalListScreenState();
}

class _ApprovalListScreenState extends State<ApprovalListScreen> {
  final _date = DateFormat('MMM d, yyyy h:mm a');
  List<TripTicket> _tickets = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadTickets();
  }

  Future<void> _loadTickets() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final tickets = await widget.api.ticketsForApproval();
      setState(() {
        _tickets = tickets;
        _loading = false;
      });
    } on ApiException catch (error) {
      if (error.statusCode == 401) {
        widget.onLogout();
        return;
      }

      setState(() {
        _error = error.message;
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _error = 'Unable to load trip tickets.';
        _loading = false;
      });
    }
  }

  Future<void> _openTicket(TripTicket ticket) async {
    final changed = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => TicketDetailScreen(api: widget.api, ticketId: ticket.id),
      ),
    );

    if (changed == true) {
      await _loadTickets();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('For Approval'),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _loadTickets,
            icon: const Icon(Icons.refresh),
          ),
          IconButton(
            tooltip: 'Logout',
            onPressed: widget.onLogout,
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadTickets,
        child: _body(),
      ),
    );
  }

  Widget _body() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Text(
            _error!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
          const SizedBox(height: 12),
          FilledButton(onPressed: _loadTickets, child: const Text('Retry')),
        ],
      );
    }

    if (_tickets.isEmpty) {
      return ListView(
        padding: const EdgeInsets.all(24),
        children: const [
          SizedBox(height: 120),
          Center(child: Text('No trip tickets for approval.')),
        ],
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(12),
      itemCount: _tickets.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final ticket = _tickets[index];
        return Card(
          margin: EdgeInsets.zero,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          child: ListTile(
            onTap: () => _openTicket(ticket),
            title: Text(
              ticket.displayNumber,
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
            subtitle: Padding(
              padding: const EdgeInsets.only(top: 6),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(ticket.destination ?? 'No destination'),
                  if (ticket.requesterName != null) Text(ticket.requesterName!),
                  if (ticket.requestedStart != null)
                    Text(_date.format(ticket.requestedStart!)),
                ],
              ),
            ),
            trailing: const StatusChip(status: 'for_approval'),
          ),
        );
      },
    );
  }
}
