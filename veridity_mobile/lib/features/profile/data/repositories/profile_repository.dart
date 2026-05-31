import '../../../../core/storage/session_store.dart';
import '../../domain/entities/profile_entity.dart';

class ProfileRepository {
  const ProfileRepository({required SessionStore sessionStore})
    : _sessionStore = sessionStore;

  final SessionStore _sessionStore;

  ProfileEntity? currentProfile() {
    final session = _sessionStore.session;
    if (session == null) {
      return null;
    }
    return ProfileEntity(user: session.user);
  }
}
