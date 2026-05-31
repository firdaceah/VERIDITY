import '../../../../core/network/api_client.dart';
import '../../../../core/storage/session_store.dart';
import '../../../auth/domain/entities/user_entity.dart';
import '../../domain/entities/profile_entity.dart';

class ProfileRepository {
  const ProfileRepository({
    required ApiClient apiClient,
    required SessionStore sessionStore,
  }) : _apiClient = apiClient,
       _sessionStore = sessionStore;

  final ApiClient _apiClient;
  final SessionStore _sessionStore;

  ProfileEntity? currentProfile() {
    final session = _sessionStore.session;
    if (session == null) {
      return null;
    }
    return ProfileEntity(user: session.user);
  }

  Future<UserEntity> updateProfile({
    required String name,
    required String email,
  }) async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      throw StateError('User belum login');
    }

    final response = await _apiClient.postForm(
      '/profile',
      body: {'name': name, 'email': email},
      token: token,
    );
    final user = UserEntity.fromJson(response['data'] as Map<String, dynamic>);
    _sessionStore.updateUser(user);
    return user;
  }

  Future<UserEntity> updateProfilePhoto(String filePath) async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      throw StateError('User belum login');
    }

    final response = await _apiClient.multipartFields(
      '/profile/photo',
      fields: const {},
      fieldName: 'photo',
      filePath: filePath,
      token: token,
    );
    final user = UserEntity.fromJson(response['data'] as Map<String, dynamic>);
    _sessionStore.updateUser(user);
    return user;
  }

  Future<void> updatePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
  }) async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      throw StateError('User belum login');
    }

    await _apiClient.postForm(
      '/profile/password',
      body: {
        'current_password': currentPassword,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
      token: token,
    );
  }
}
