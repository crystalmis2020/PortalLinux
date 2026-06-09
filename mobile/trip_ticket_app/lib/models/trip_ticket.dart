class TripTicket {
  TripTicket({
    required this.id,
    required this.status,
    this.ticketNumber,
    this.destination,
    this.purpose,
    this.passengers,
    this.contactNumber,
    this.remarks,
    this.vehicleDetails,
    this.driverName,
    this.requestedStart,
    this.requestedEnd,
    this.actualDeparture,
    this.actualReturn,
    this.approvalRemarks,
    this.requesterName,
    this.departmentName,
    this.sectionName,
    this.encoderName,
  });

  final int id;
  final String status;
  final String? ticketNumber;
  final String? destination;
  final String? purpose;
  final String? passengers;
  final String? contactNumber;
  final String? remarks;
  final String? vehicleDetails;
  final String? driverName;
  final DateTime? requestedStart;
  final DateTime? requestedEnd;
  final DateTime? actualDeparture;
  final DateTime? actualReturn;
  final String? approvalRemarks;
  final String? requesterName;
  final String? departmentName;
  final String? sectionName;
  final String? encoderName;

  String get displayNumber => ticketNumber == null || ticketNumber!.isEmpty
      ? 'Request #$id'
      : ticketNumber!;

  factory TripTicket.fromJson(Map<String, dynamic> json) {
    return TripTicket(
      id: json['id'] as int,
      status: (json['status'] ?? '') as String,
      ticketNumber: json['ticket_number'] as String?,
      destination: json['destination'] as String?,
      purpose: json['purpose'] as String?,
      passengers: json['passengers'] as String?,
      contactNumber: json['contact_number'] as String?,
      remarks: json['remarks'] as String?,
      vehicleDetails: json['vehicle_details'] as String?,
      driverName: json['driver_name'] as String?,
      requestedStart: _date(json['requested_start_datetime']),
      requestedEnd: _date(json['requested_end_datetime']),
      actualDeparture: _date(json['actual_departure_datetime']),
      actualReturn: _date(json['actual_return_datetime']),
      approvalRemarks: json['approval_remarks'] as String?,
      requesterName: _nestedName(json['requester']),
      departmentName: _nestedName(json['department']),
      sectionName: _nestedName(json['section']),
      encoderName: _nestedName(json['encoder']),
    );
  }

  static DateTime? _date(dynamic value) {
    if (value == null || value.toString().isEmpty) {
      return null;
    }

    return DateTime.tryParse(value.toString())?.toLocal();
  }

  static String? _nestedName(dynamic value) {
    if (value is Map) {
      return value['full_name'] as String? ?? value['name'] as String?;
    }

    return null;
  }
}
