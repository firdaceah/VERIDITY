import '../../features/auth/domain/entities/auth_session.dart';

class SessionStore {
  SessionStore._();

  static final SessionStore instance = SessionStore._();

  AuthSession? _session;

  AuthSession? get session => _session;
  String? get token => _session?.token;

  void save(AuthSession session) {
    _session = session;
  }

  void clear() {
    _session = null;
  }
}
