import '../../../../core/network/api_client.dart';
import '../../../../core/storage/session_store.dart';
import '../../domain/entities/audit_entity.dart';

class AuditRepository {
  AuditRepository({
    required ApiClient apiClient,
    required SessionStore sessionStore,
  }) : _apiClient = apiClient,
       _sessionStore = sessionStore;

  final ApiClient _apiClient;
  final SessionStore _sessionStore;

  Future<List<AuditEntity>> history() async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      return [];
    }

    final response = await _apiClient.getJson('/audits', token: token);
    final items = response['data'] is List
        ? response['data'] as List
        : const [];
    return items
        .whereType<Map<String, dynamic>>()
        .map(AuditEntity.fromJson)
        .toList();
  }

  Future<AuditEntity> uploadFile(String filePath) async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      throw StateError('User belum login');
    }

    final response = await _apiClient.multipartFile(
      '/audits',
      fieldName: 'image',
      filePath: filePath,
      token: token,
    );
    return AuditEntity.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<AuditEntity> detail(int id) async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      throw StateError('User belum login');
    }

    final response = await _apiClient.getJson('/audits/$id', token: token);
    return AuditEntity.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<void> delete(int id) async {
    final token = _sessionStore.token;
    if (token == null || token.isEmpty) {
      throw StateError('User belum login');
    }

    await _apiClient.deleteJson('/audits/$id', token: token);
  }

  Uri reportUri(int id) => _apiClient.authenticatedUri('/audits/$id/report');

  Uri mobileReportUri(int id) {
    final token = _sessionStore.token ?? '';
    final uri = _apiClient.authenticatedUri('/audits/$id/report-mobile');
    return uri.replace(queryParameters: {'token': token});
  }
}
