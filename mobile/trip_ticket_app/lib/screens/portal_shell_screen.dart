import 'package:flutter/material.dart';

import '../models/user_session.dart';
import '../services/api_service.dart';
import '../theme/portal_theme.dart';
import '../widgets/portal_brand.dart';
import 'approval_list_screen.dart';
import 'gatekeeper_screen.dart';
import 'messhall_screen.dart';

class PortalShellScreen extends StatefulWidget {
  const PortalShellScreen({
    super.key,
    required this.api,
    required this.session,
    required this.onLogout,
  });

  final ApiService api;
  final UserSession session;
  final Future<void> Function() onLogout;

  @override
  State<PortalShellScreen> createState() => _PortalShellScreenState();
}

class _PortalShellScreenState extends State<PortalShellScreen> {
  int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    final pages = <Widget>[
      if (widget.session.canApproveTripTickets)
        ApprovalListScreen(
          api: widget.api,
          session: widget.session,
          onUnauthorized: widget.onLogout,
        ),
      if (widget.session.canGatekeepTripTickets)
        GatekeeperScreen(
          api: widget.api,
          session: widget.session,
          onUnauthorized: widget.onLogout,
        ),
      if (!widget.session.canGatekeepTripTickets) const MessHallScreen(),
    ];

    final destinations = <NavigationDestination>[
      if (widget.session.canApproveTripTickets)
        const NavigationDestination(
          icon: Icon(Icons.fact_check_outlined),
          selectedIcon: Icon(Icons.fact_check),
          label: 'Approval',
        ),
      if (widget.session.canGatekeepTripTickets)
        const NavigationDestination(
          icon: Icon(Icons.local_shipping_outlined),
          selectedIcon: Icon(Icons.local_shipping),
          label: 'Gatekeeper',
        ),
      if (!widget.session.canGatekeepTripTickets)
        const NavigationDestination(
          icon: Icon(Icons.restaurant_outlined),
          selectedIcon: Icon(Icons.restaurant),
          label: 'Mess Hall',
        ),
    ];

    if (_selectedIndex >= pages.length) {
      _selectedIndex = 0;
    }
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 68,
        titleSpacing: 16,
        title: const PortalBrand(compact: true),
        actions: [
          PopupMenuButton<String>(
            tooltip: 'Account',
            position: PopupMenuPosition.under,
            onSelected: (value) {
              if (value == 'logout') {
                widget.onLogout();
              }
            },
            itemBuilder: (context) => [
              PopupMenuItem(
                enabled: false,
                child: SizedBox(
                  width: 220,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        widget.session.fullName,
                        style: const TextStyle(
                          color: PortalColors.brandDark,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        widget.session.department ?? widget.session.username,
                        style: const TextStyle(color: PortalColors.muted),
                      ),
                    ],
                  ),
                ),
              ),
              const PopupMenuDivider(),
              const PopupMenuItem(
                value: 'logout',
                child: Row(
                  children: [
                    Icon(Icons.logout, size: 20),
                    SizedBox(width: 12),
                    Text('Logout'),
                  ],
                ),
              ),
            ],
            child: Padding(
              padding: const EdgeInsets.only(right: 14),
              child: CircleAvatar(
                radius: 19,
                backgroundColor: const Color(0xffe8eef5),
                foregroundColor: PortalColors.brandDark,
                child: Text(
                  _initials(widget.session.fullName),
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
      body: IndexedStack(
        index: _selectedIndex,
        children: pages,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (index) {
          setState(() => _selectedIndex = index);
        },
        destinations: destinations,
      ),
    );
  }

  String _initials(String name) {
    final words = name
        .trim()
        .split(RegExp(r'\s+'))
        .where((word) => word.isNotEmpty)
        .toList();

    if (words.isEmpty) {
      return 'U';
    }

    return words.take(2).map((word) => word[0].toUpperCase()).join();
  }
}
