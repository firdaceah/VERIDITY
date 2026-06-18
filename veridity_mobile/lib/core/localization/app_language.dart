import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

enum AppLocale { en, id }

class AppLanguage extends ValueNotifier<AppLocale> {
  AppLanguage._() : super(AppLocale.en);

  static final AppLanguage instance = AppLanguage._();
  static const _languageKey = 'veridity.language';

  bool get isEnglish => value == AppLocale.en;

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_languageKey);
    value = raw == 'id' ? AppLocale.id : AppLocale.en;
  }

  Future<void> setLocale(AppLocale locale) async {
    value = locale;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_languageKey, locale == AppLocale.id ? 'id' : 'en');
  }

  String text(String en, String id) => isEnglish ? en : id;

  String auditLabel(String value) {
    final normalized = value.toLowerCase().trim();

    if (normalized.contains('aman') ||
        normalized.contains('safe') ||
        normalized.contains('asli')) {
      return text('Safe / likely authentic', 'Aman / cenderung asli');
    }

    if (normalized.contains('bahaya') ||
        normalized.contains('danger') ||
        normalized.contains('beresiko') ||
        normalized.contains('berisiko')) {
      return text('High risk', 'Berisiko tinggi');
    }

    if (normalized.contains('ai') ||
        normalized.contains('mixed') ||
        normalized.contains('hybrid')) {
      return text('Mixed / AI assisted', 'Campuran / dibantu AI');
    }

    if (normalized.contains('mencurigakan') ||
        normalized.contains('suspicious') ||
        normalized.contains('rekayasa')) {
      return text('Suspicious', 'Mencurigakan');
    }

    return value;
  }
}
