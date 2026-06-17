import '../../../../core/network/api_client.dart';
import '../../../../core/storage/session_store.dart';
import '../../domain/entities/auth_session.dart';

class AuthRepository {
  AuthRepository({
    required ApiClient apiClient,
    required SessionStore sessionStore,
  }) : _apiClient = apiClient,
       _sessionStore = sessionStore;

  final ApiClient _apiClient;
  final SessionStore _sessionStore;

  Future<AuthSession> login({
    required String email,
    required String password,
  }) async {
    final response = await _apiClient.postForm(
      '/login',
      body: {'email': email, 'password': password},
    );
    final session = AuthSession.fromJson(response);
    await _sessionStore.save(session);
    return session;
  }

  Future<AuthSession> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _apiClient.postForm(
      '/register',
      body: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
    final session = AuthSession.fromJson(response);
    await _sessionStore.save(session);
    return session;
  }

  Future<void> logout() async {
    final token = _sessionStore.token;
    if (token != null && token.isNotEmpty) {
      await _apiClient.postJson('/logout', token: token);
    }
    await _sessionStore.clear();
  }

  Future<String?> forgotPassword(String email) async {
    final response = await _apiClient.postForm(
      '/forgot-password',
      body: {'email': email},
    );
    return response['dev_reset_token']?.toString();
  }

  Future<void> resetPassword({
    required String email,
    required String token,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _apiClient.postForm(
      '/reset-password',
      body: {
        'email': email,
        'token': token,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
  }
}
