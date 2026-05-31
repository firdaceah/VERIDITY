import '../../features/auth/domain/entities/auth_session.dart';
import '../../features/auth/domain/entities/user_entity.dart';

class SessionStore {
  SessionStore._();

  static final SessionStore instance = SessionStore._();

  AuthSession? _session;

  AuthSession? get session => _session;
  String? get token => _session?.token;

  void save(AuthSession session) {
    _session = session;
  }

  void updateUser(UserEntity user) {
    final current = _session;
    if (current == null) {
      return;
    }
    _session = current.copyWith(user: user);
  }

  void clear() {
    _session = null;
  }
}
