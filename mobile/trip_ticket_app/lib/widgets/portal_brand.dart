import 'package:flutter/material.dart';

import '../theme/portal_theme.dart';

class PortalBrand extends StatelessWidget {
  const PortalBrand({
    super.key,
    this.compact = false,
    this.light = false,
  });

  final bool compact;
  final bool light;

  @override
  Widget build(BuildContext context) {
    final foreground = light ? Colors.white : PortalColors.brandDark;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: compact ? 38 : 52,
          height: compact ? 38 : 52,
          padding: const EdgeInsets.all(4),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: light
                  ? Colors.white.withValues(alpha: 0.35)
                  : PortalColors.border,
            ),
          ),
          child: Image.asset('assets/images/portal_mark.png'),
        ),
        const SizedBox(width: 10),
        Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'SUPPORT PORTAL',
              style: TextStyle(
                color: foreground,
                fontSize: compact ? 15 : 20,
                fontWeight: FontWeight.w900,
                letterSpacing: 0,
              ),
            ),
            Text(
              'CSCI MIS',
              style: TextStyle(
                color: light
                    ? Colors.white.withValues(alpha: 0.72)
                    : PortalColors.muted,
                fontSize: compact ? 10 : 12,
                fontWeight: FontWeight.w700,
                letterSpacing: 0,
              ),
            ),
          ],
        ),
      ],
    );
  }
}
