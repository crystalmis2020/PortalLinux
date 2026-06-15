import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:trip_ticket_app/main.dart';

void main() {
  testWidgets('Support Portal app shows branded startup state',
      (WidgetTester tester) async {
    await tester.pumpWidget(const TripTicketApp());

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    expect(find.byType(Image), findsOneWidget);
  });
}
