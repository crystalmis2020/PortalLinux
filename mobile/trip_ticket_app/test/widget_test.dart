import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:trip_ticket_app/main.dart';

void main() {
  testWidgets('Trip ticket app shows startup state', (WidgetTester tester) async {
    await tester.pumpWidget(const TripTicketApp());

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });
}
