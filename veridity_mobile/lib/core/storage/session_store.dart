import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import '../../features/auth/domain/entities/auth_session.dart';
import '../../features/auth/domain/entities/user_entity.dart';

class SessionStore {
  SessionStore._();

  static final SessionStore instance = SessionStore._();

  AuthSession? _session;
  bool _onboardingCompleted = false;
  static const _sessionKey = 'veridity.auth.session';
  static const _onboardingKey = 'veridity.onboarding.completed';

  AuthSession? get session => _session;
  String? get token => _session?.token;
  bool get onboardingCompleted => _onboardingCompleted;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    _onboardingCompleted = prefs.getBool(_onboardingKey) ?? false;

    final rawSession = prefs.getString(_sessionKey);
    if (rawSession == null || rawSession.isEmpty) {
      return;
    }

    try {
      final json = jsonDecode(rawSession) as Map<String, dynamic>;
      _session = AuthSession.fromStoredJson(json);
    } catch (_) {
      await prefs.remove(_sessionKey);
      _session = null;
    }
  }

  Future<void> completeOnboarding() async {
    _onboardingCompleted = true;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_onboardingKey, true);
  }

  Future<void> save(AuthSession session) async {
    _session = session;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_sessionKey, jsonEncode(session.toStoredJson()));
  }

  Future<void> updateUser(UserEntity user) async {
    final current = _session;
    if (current == null) {
      return;
    }
    _session = current.copyWith(user: user);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_sessionKey, jsonEncode(_session!.toStoredJson()));
  }

  Future<void> clear() async {
    _session = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_sessionKey);
  }
}
