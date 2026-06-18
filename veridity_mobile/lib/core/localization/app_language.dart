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

  String fileTypeLabel({required bool isDocument}) =>
      isDocument ? text('Document', 'Dokumen') : text('Photo', 'Foto');

  String auditLabel(String value) {
    final normalized = value.toLowerCase().trim();

    if (normalized.contains('authentic') ||
        normalized.contains('original') ||
        normalized.contains('human written') ||
        normalized.contains('otentik')) {
      return text('Authentic / likely original', 'Otentik / cenderung asli');
    }

    if (normalized.contains('aman') ||
        normalized.contains('safe') ||
        normalized.contains('asli')) {
      return text('Safe / likely authentic', 'Aman / cenderung asli');
    }

    if (normalized.contains('high risk') ||
        normalized.contains('mostly ai') ||
        normalized.contains('mayoritas ai')) {
      return text('High risk', 'Berisiko tinggi');
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

  String analysisText(String value) {
    final normalized = value.toLowerCase().trim();

    if (normalized.isEmpty || normalized == '-') {
      return value;
    }

    if (normalized.contains('gambar tidak tersedia')) {
      return text('Image unavailable', 'Gambar tidak tersedia');
    }
    if (normalized.contains('analisis bahasa selesai')) {
      return text('Language analysis completed.', 'Analisis bahasa selesai.');
    }
    if (normalized.contains('analisis noise selesai')) {
      return text('Noise analysis completed.', 'Analisis noise selesai.');
    }
    if (normalized.contains('kamera fisik real') ||
        normalized.contains('otentik')) {
      return text(
        'Physical camera capture (authentic)',
        'Kamera fisik real (otentik)',
      );
    }
    if (normalized.contains('rekayasa digital') ||
        normalized.contains('editing')) {
      return text(
        'Digital manipulation / editing indicated',
        'Rekayasa digital / editing',
      );
    }
    if (normalized.contains('pola noise') || normalized.contains('noise')) {
      if (normalized.contains('tidak') || normalized.contains('anomali')) {
        return text(
          'Noise pattern needs review as a supporting forensic signal.',
          'Pola noise perlu ditinjau sebagai sinyal forensik pendukung.',
        );
      }
      return text(
        'Noise pattern remains within the final tolerance range.',
        'Pola noise masih berada dalam toleransi hasil akhir.',
      );
    }

    return auditLabel(value);
  }
}
