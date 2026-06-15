import 'package:flutter/material.dart';

class StatusChip extends StatelessWidget {
  const StatusChip({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final appearance = _appearance(status);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(999),
        color: appearance.background,
        border: Border.all(color: appearance.border),
      ),
      child: Text(
        status.replaceAll('_', ' ').toUpperCase(),
        style: TextStyle(
          color: appearance.foreground,
          fontSize: 10,
          fontWeight: FontWeight.w800,
          letterSpacing: 0,
        ),
      ),
    );
  }

  _StatusAppearance _appearance(String value) {
    switch (value) {
      case 'approved':
        return const _StatusAppearance(
          background: Color(0xffecfdf3),
          foreground: Color(0xff027a48),
          border: Color(0xffabefc6),
        );
      case 'rejected':
        return const _StatusAppearance(
          background: Color(0xfffff1f3),
          foreground: Color(0xffc01048),
          border: Color(0xffffcdd6),
        );
      case 'returned':
      case 'for_correction':
        return const _StatusAppearance(
          background: Color(0xfffffaeb),
          foreground: Color(0xffb54708),
          border: Color(0xfffedf89),
        );
      case 'for_approval':
        return const _StatusAppearance(
          background: Color(0xffeff6ff),
          foreground: Color(0xff1d4ed8),
          border: Color(0xffbfdbfe),
        );
      default:
        return const _StatusAppearance(
          background: Color(0xfff1f5f9),
          foreground: Color(0xff475569),
          border: Color(0xffcbd5e1),
        );
    }
  }
}

class _StatusAppearance {
  const _StatusAppearance({
    required this.background,
    required this.foreground,
    required this.border,
  });

  final Color background;
  final Color foreground;
  final Color border;
}
