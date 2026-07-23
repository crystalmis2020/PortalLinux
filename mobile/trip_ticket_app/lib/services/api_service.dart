import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import '../models/trip_ticket.dart';
import '../models/user_session.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiService {
  ApiService({
    http.Client? client,
    FlutterSecureStorage? storage,
  })  : _client = client ?? http.Client(),
        _storage = storage ?? const FlutterSecureStorage();

  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://128.0.254.20/support',
  );

  static const String _tokenKey = 'trip_ticket_token';
  static const Duration _requestTimeout = Duration(seconds: 15);

  final http.Client _client;
  final FlutterSecureStorage _storage;

  Future<UserSession> login({
    required String username,
    required String password,
  }) async {
    final response = await _request(
      _client.post(
        _uri('/api/login'),
        headers: _headers(),
        body: jsonEncode({
          'username': username,
          'password': password,
          'device_name': 'flutter-mobile',
        }),
      ),
    );

    final data = _decode(response);
    final token = data['access_token'] as String?;
    if (token == null || token.isEmpty) {
      throw ApiException('Login response did not include an access token.');
    }

    await _storage.write(key: _tokenKey, value: token);

    return UserSession.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<UserSession?> restoreSession() async {
    final token = await _storage.read(key: _tokenKey);
    if (token == null || token.isEmpty) {
      return null;
    }

    final response = await _request(
      _client.get(
        _uri('/api/me'),
        headers: _headers(token: token),
      ),
    );

    final data = _decode(response);
    return UserSession.fromJson(data['user'] as Map<String, dynamic>);
  }

  Future<void> logout() async {
    final token = await _storage.read(key: _tokenKey);
    try {
      if (token != null && token.isNotEmpty) {
        await _request(
          _client.post(
            _uri('/api/logout'),
            headers: _headers(token: token),
          ),
        );
      }
    } finally {
      await clearToken();
    }
  }

  Future<void> clearToken() async {
    await _storage.delete(key: _tokenKey);
  }

  Future<List<TripTicket>> ticketsForApproval() async {
    final response = await _request(
      _client.get(
        _uri('/api/trip-tickets/for-approval'),
        headers: await _authHeaders(),
      ),
    );

    final data = _decode(response);
    final rows = data['data'] as List<dynamic>;
    return rows
        .map((item) => TripTicket.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<TripTicket> ticket(int id) async {
    final response = await _request(
      _client.get(
        _uri('/api/trip-tickets/$id'),
        headers: await _authHeaders(),
      ),
    );

    final data = _decode(response);
    return TripTicket.fromJson(data['ticket'] as Map<String, dynamic>);
  }

  Future<List<TripTicket>> gatekeeperReadyForDeparture() {
    return _gatekeeperTickets(
        '/api/trip-tickets/gatekeeper/ready-for-departure');
  }

  Future<List<TripTicket>> gatekeeperAwaitingReturn() {
    return _gatekeeperTickets('/api/trip-tickets/gatekeeper/awaiting-return');
  }

  Future<List<TripTicket>> gatekeeperSearch(String query) {
    return _gatekeeperTickets(
      '/api/trip-tickets/gatekeeper/search',
      query: query,
    );
  }

  Future<TripTicket> gatekeeperQrLookup(String token) async {
    final response = await _request(
      _client.get(
        _uri('/api/trip-tickets/gatekeeper/qr/${Uri.encodeComponent(token)}'),
        headers: await _authHeaders(),
      ),
    );

    final data = _decode(response);
    return TripTicket.fromJson(data['ticket'] as Map<String, dynamic>);
  }

  Future<TripTicket> gatekeeperRecordDeparture(
    int id,
    String remarks,
    DateTime actualDeparture,
    double odometer,
  ) {
    return _gatekeeperAction(
      id,
      'departure',
      remarks,
      actualDeparture,
      odometer,
    );
  }

  Future<TripTicket> gatekeeperRecordReturn(
    int id,
    String remarks,
    DateTime actualReturn,
    double odometer,
  ) {
    return _gatekeeperAction(
      id,
      'return',
      remarks,
      actualReturn,
      odometer,
    );
  }

  Future<List<TripTicket>> _gatekeeperTickets(
    String path, {
    String? query,
  }) async {
    final response = await _request(
      _client.get(
        _uri(path, queryParameters: {
          if (query != null && query.trim().isNotEmpty) 'q': query.trim(),
        }),
        headers: await _authHeaders(),
      ),
    );

    final data = _decode(response);
    final rows = data['data'] as List<dynamic>;
    return rows
        .map((item) => TripTicket.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<TripTicket> _gatekeeperAction(
    int id,
    String action,
    String remarks,
    DateTime actualDateTime,
    double odometer,
  ) async {
    final dateTimeField = action == 'departure'
        ? 'actual_departure_datetime'
        : 'actual_return_datetime';

    final response = await _request(
      _client.post(
        _uri('/api/trip-tickets/gatekeeper/$id/$action'),
        headers: await _authHeaders(),
        body: jsonEncode({
          'remarks': remarks,
          dateTimeField: actualDateTime.toIso8601String(),
          '${action}_odometer': odometer,
        }),
      ),
    );

    final data = _decode(response);
    return TripTicket.fromJson(data['ticket'] as Map<String, dynamic>);
  }

  Future<TripTicket> approve(int id, String remarks) {
    return _approvalAction(id, 'approve', remarks);
  }

  Future<TripTicket> reject(int id, String remarks) {
    return _approvalAction(id, 'reject', remarks);
  }

  Future<TripTicket> returnForCorrection(int id, String remarks) {
    return _approvalAction(id, 'return', remarks);
  }

  Future<TripTicket> _approvalAction(
    int id,
    String action,
    String remarks,
  ) async {
    final response = await _request(
      _client.post(
        _uri('/api/trip-tickets/$id/$action'),
        headers: await _authHeaders(),
        body: jsonEncode({'approval_remarks': remarks}),
      ),
    );

    final data = _decode(response);
    return TripTicket.fromJson(data['ticket'] as Map<String, dynamic>);
  }

  Future<Map<String, String>> _authHeaders() async {
    final token = await _storage.read(key: _tokenKey);
    if (token == null || token.isEmpty) {
      throw ApiException('Missing login token.', statusCode: 401);
    }

    return _headers(token: token);
  }

  Map<String, String> _headers({String? token}) {
    return {
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Uri _uri(String path, {Map<String, String>? queryParameters}) {
    final uri = Uri.parse('$baseUrl$path');
    if (queryParameters == null || queryParameters.isEmpty) {
      return uri;
    }

    return uri.replace(queryParameters: queryParameters);
  }

  Future<http.Response> _request(Future<http.Response> request) async {
    try {
      return await request.timeout(_requestTimeout);
    } on HandshakeException {
      throw ApiException(
        'The server certificate is not trusted by this device.',
      );
    } on SocketException {
      throw ApiException(
        'Cannot reach the server. Check Wi-Fi and the API address.',
      );
    } on TimeoutException {
      throw ApiException('The server did not respond in time.');
    } on http.ClientException {
      throw ApiException('Cannot connect to the server.');
    }
  }

  Map<String, dynamic> _decode(http.Response response) {
    dynamic decoded;

    try {
      decoded = response.body.isEmpty
          ? <String, dynamic>{}
          : jsonDecode(response.body);
    } on FormatException {
      throw ApiException(
        'The server returned an invalid response.',
        statusCode: response.statusCode,
      );
    }

    final data =
        decoded is Map<String, dynamic> ? decoded : <String, dynamic>{};

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw ApiException(
        (data['message'] ?? 'Request failed.').toString(),
        statusCode: response.statusCode,
      );
    }

    return data;
  }
}
