import 'package:flutter/material.dart';

import 'models/user_session.dart';
import 'screens/approval_list_screen.dart';
import 'screens/login_screen.dart';
import 'services/api_service.dart';

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
    await _api.logout();
    setState(() => _session = null);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Trip Ticket Approval',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xff2563eb)),
        useMaterial3: true,
      ),
      home: _checkingSession
          ? const _StartupScreen()
          : _session == null
              ? LoginScreen(
                  api: _api,
                  onLoggedIn: (session) => setState(() => _session = session),
                )
              : ApprovalListScreen(
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
    return const Scaffold(
      body: Center(child: CircularProgressIndicator()),
    );
  }
}
