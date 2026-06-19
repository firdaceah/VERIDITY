import 'package:file_picker/file_picker.dart';

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
    required String languageCode,
  }) async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      throw StateError('User belum login');
    }

    final response = await _apiClient.postForm(
      '/profile',
      body: {'name': name, 'email': email, 'language': languageCode},
      token: token,
    );
    final user = UserEntity.fromJson(response['data'] as Map<String, dynamic>);
    await _sessionStore.updateUser(user);
    return user;
  }

  Future<UserEntity> updateProfilePhoto(
    PlatformFile file, {
    required String languageCode,
  }) async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      throw StateError('User belum login');
    }

    final bytes = file.bytes;
    if (bytes == null || bytes.isEmpty) {
      throw StateError('Foto tidak dapat dibaca dari perangkat');
    }

    final response = await _apiClient.multipartBytes(
      '/profile/photo',
      fieldName: 'photo',
      fileName: file.name,
      bytes: bytes,
      fields: {'language': languageCode},
      token: token,
    );
    final user = UserEntity.fromJson(response['data'] as Map<String, dynamic>);
    await _sessionStore.updateUser(user);
    return user;
  }

  Future<void> updatePassword({
    required String currentPassword,
    required String password,
    required String passwordConfirmation,
    required String languageCode,
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
        'language': languageCode,
      },
      token: token,
    );
  }
}
