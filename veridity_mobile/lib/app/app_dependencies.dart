import '../core/config/api_config.dart';
import '../core/network/api_client.dart';
import '../core/storage/session_store.dart';
import '../features/audit/data/repositories/audit_repository.dart';
import '../features/auth/data/repositories/auth_repository.dart';
import '../features/profile/data/repositories/profile_repository.dart';

class AppDependencies {
  AppDependencies._();

  static final SessionStore sessionStore = SessionStore.instance;
  static final ApiClient apiClient = ApiClient(baseUrl: ApiConfig.baseUrl);
  static final AuthRepository authRepository = AuthRepository(
    apiClient: apiClient,
    sessionStore: sessionStore,
  );
  static final AuditRepository auditRepository = AuditRepository(
    apiClient: apiClient,
    sessionStore: sessionStore,
  );
  static final ProfileRepository profileRepository = ProfileRepository(
    apiClient: apiClient,
    sessionStore: sessionStore,
  );
}
