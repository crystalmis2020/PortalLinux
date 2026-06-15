import 'package:flutter/material.dart';

import '../theme/portal_theme.dart';

class MessHallScreen extends StatelessWidget {
  const MessHallScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 28),
      children: [
        Text(
          'Mess Hall',
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        const SizedBox(height: 4),
        const Text(
          'Meal scheduling and attendance will live here.',
          style: TextStyle(color: PortalColors.muted),
        ),
        const SizedBox(height: 28),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 36),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: PortalColors.border),
          ),
          child: Column(
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: const Color(0xffecfdf3),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(
                  Icons.restaurant_menu,
                  size: 34,
                  color: PortalColors.green,
                ),
              ),
              const SizedBox(height: 20),
              Text(
                'Mess Hall is next',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 8),
              const Text(
                'The module is already part of the mobile navigation. '
                'Its workflows will be connected after Trip Ticket is polished.',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: PortalColors.muted,
                  height: 1.5,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
