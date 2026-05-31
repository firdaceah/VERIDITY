import 'package:flutter_test/flutter_test.dart';
import 'package:veridity_mobile/features/auth/domain/entities/auth_session.dart';

void main() {
  test('AuthSession parses the phase 1 Laravel auth response', () {
    final session = AuthSession.fromJson({
      'access_token': 'abc-token',
      'token': 'legacy-token',
      'data': {'id': 7, 'name': 'Veridity User', 'email': 'user@example.test'},
      'user': {'id': 8, 'name': 'Legacy User', 'email': 'legacy@example.test'},
    });

    expect(session.token, 'abc-token');
    expect(session.user.id, 7);
    expect(session.user.name, 'Veridity User');
    expect(session.asRouteArguments()['token'], 'abc-token');
    expect(session.asRouteArguments()['name'], 'Veridity User');
  });

  test('AuthSession falls back to legacy token and user keys', () {
    final session = AuthSession.fromJson({
      'token': 'legacy-token',
      'user': {'id': 9, 'name': 'Legacy User', 'email': 'legacy@example.test'},
    });

    expect(session.token, 'legacy-token');
    expect(session.user.id, 9);
    expect(session.user.email, 'legacy@example.test');
  });
}
