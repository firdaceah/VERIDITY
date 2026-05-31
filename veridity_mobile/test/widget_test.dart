import 'package:flutter_test/flutter_test.dart';

import 'package:veridity_mobile/app/app.dart';

void main() {
  testWidgets('Veridity app starts on the splash screen', (tester) async {
    await tester.pumpWidget(const VeridityApp());

    expect(find.text('VERIDITY'), findsOneWidget);
    expect(find.text('A I  P h o t o  F o r e n s i c s'), findsOneWidget);
  });
}
