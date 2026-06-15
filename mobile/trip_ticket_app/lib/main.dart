import 'package:flutter/material.dart';

import 'models/user_session.dart';
import 'screens/login_screen.dart';
import 'screens/portal_shell_screen.dart';
import 'services/api_service.dart';
import 'theme/portal_theme.dart';

void main() {
  runApp(const TripTicketApp());
}

class TripTicketApp extends StatefulWidget {
  const TripTicketApp({super.key});

  @override
  State<TripTicketApp> createState() => _TripTicketAppState();
}

class _TripTicketAppState extends State<TripTicketApp> {
  final ApiService _api = ApiService();
  UserSession? _session;
  bool _checkingSession = true;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    try {
      final session = await _api.restoreSession();
      setState(() {
        _session = session;
        _checkingSession = false;
      });
    } catch (_) {
      await _api.clearToken();
      setState(() {
        _session = null;
        _checkingSession = false;
      });
    }
  }

  Future<void> _handleLogout() async {
    try {
      await _api.logout();
    } catch (_) {
      await _api.clearToken();
    }

    if (mounted) {
      setState(() => _session = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Support Portal',
      theme: PortalTheme.light(),
      home: _checkingSession
          ? const _StartupScreen()
          : _session == null
              ? LoginScreen(
                  api: _api,
                  onLoggedIn: (session) => setState(() => _session = session),
                )
              : PortalShellScreen(
                  api: _api,
                  session: _session!,
                  onLogout: _handleLogout,
                ),
    );
  }
}

class _StartupScreen extends StatelessWidget {
  const _StartupScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Image.asset(
              'assets/images/portal_mark.png',
              width: 64,
              height: 64,
            ),
            const SizedBox(height: 20),
            const SizedBox(
              width: 24,
              height: 24,
              child: CircularProgressIndicator(strokeWidth: 2.5),
            ),
          ],
        ),
      ),
    );
  }
}
