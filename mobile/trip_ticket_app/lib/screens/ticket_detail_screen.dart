import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/trip_ticket.dart';
import '../services/api_service.dart';
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
      setState(() {
        _ticket = ticket;
        _loading = false;
      });
    } on ApiException catch (error) {
      setState(() {
        _error = error.message;
        _loading = false;
      });
    } catch (_) {
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

    setState(() => _saving = true);

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
      setState(() {
        _error = error.message;
        _saving = false;
      });
    } catch (_) {
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
      appBar: AppBar(title: const Text('Trip Ticket')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null && ticket == null
              ? _errorBody()
              : ticket == null
                  ? const Center(child: Text('Ticket not found.'))
                  : _ticketBody(ticket),
      bottomNavigationBar: ticket == null || ticket.status != 'for_approval'
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _saving ? null : () => _confirmAction('return'),
                        child: const Text('Return'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _saving ? null : () => _confirmAction('reject'),
                        child: const Text('Reject'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: FilledButton(
                        onPressed: _saving ? null : () => _confirmAction('approve'),
                        child: _saving
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Text('Approve'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _errorBody() {
    return ListView(
      padding: const EdgeInsets.all(24),
      children: [
        Text(
          _error!,
          style: TextStyle(color: Theme.of(context).colorScheme.error),
        ),
        const SizedBox(height: 12),
        FilledButton(onPressed: _loadTicket, child: const Text('Retry')),
      ],
    );
  }

  Widget _ticketBody(TripTicket ticket) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Text(
                ticket.displayNumber,
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
            ),
            StatusChip(status: ticket.status),
          ],
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          Text(
            _error!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ],
        const SizedBox(height: 16),
        _section('Requester', [
          _row('Name', ticket.requesterName),
          _row('Department', ticket.departmentName),
          _row('Section', ticket.sectionName),
          _row('Contact', ticket.contactNumber),
        ]),
        _section('Trip', [
          _row('Destination', ticket.destination),
          _row('Purpose', ticket.purpose),
          _row('Passengers', ticket.passengers),
          _row('Requested Departure', _format(ticket.requestedStart)),
          _row('Requested Return', _format(ticket.requestedEnd)),
        ]),
        _section('Dispatch', [
          _row('Vehicle', ticket.vehicleDetails),
          _row('Driver', ticket.driverName),
          _row('Actual Departure', _format(ticket.actualDeparture)),
          _row('Actual Return', _format(ticket.actualReturn)),
          _row('Encoded By', ticket.encoderName),
          _row('Remarks', ticket.remarks),
        ]),
      ],
    );
  }

  Widget _section(String title, List<Widget> children) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 10),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String? value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                  fontWeight: FontWeight.w700,
                ),
          ),
          const SizedBox(height: 2),
          Text(value == null || value.isEmpty ? 'N/A' : value),
        ],
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
    final label = widget.action == 'return'
        ? 'Return for Correction'
        : widget.action[0].toUpperCase() + widget.action.substring(1);

    return AlertDialog(
      title: Text(label),
      content: TextField(
        controller: _remarks,
        minLines: 3,
        maxLines: 5,
        decoration: const InputDecoration(
          border: OutlineInputBorder(),
          labelText: 'Remarks',
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () => Navigator.of(context).pop(_remarks.text.trim()),
          child: const Text('Submit'),
        ),
      ],
    );
  }
}
