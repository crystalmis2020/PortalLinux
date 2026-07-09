class UserSession {
  UserSession({
    required this.id,
    required this.fullName,
    required this.username,
    required this.canApproveTripTickets,
    required this.canGatekeepTripTickets,
    this.department,
    this.section,
  });

  final int id;
  final String fullName;
  final String username;
  final bool canApproveTripTickets;
  final bool canGatekeepTripTickets;
  bool get canUseTripTickets => canApproveTripTickets || canGatekeepTripTickets;
  final String? department;
  final String? section;

  factory UserSession.fromJson(Map<String, dynamic> json) {
    final permissions = (json['permissions'] as Map?) ?? {};

    return UserSession(
      id: json['id'] as int,
      fullName: (json['full_name'] ?? '') as String,
      username: (json['username'] ?? '') as String,
      canApproveTripTickets: permissions['can_approve_trip_tickets'] == true,
      canGatekeepTripTickets: permissions['can_gatekeep_trip_tickets'] == true,
      department: json['department'] as String?,
      section: json['section'] as String?,
    );
  }
}
