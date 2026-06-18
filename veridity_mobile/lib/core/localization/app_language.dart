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

  String analysisMessage(String key, {String fallback = ''}) {
    return switch (key) {
      'document_authentic_human' => text(
          'Authentic / human written',
          'Otentik / ditulis manusia',
        ),
      'document_mixed_ai_assisted' => text(
          'Mixed text / AI assisted',
          'Teks campuran / dibantu AI',
        ),
      'document_mostly_ai' => text(
          'Mostly AI generated',
          'Mayoritas AI generated',
        ),
      'document_human_style' => text(
          'The language style has dynamic sentence-length variation and natural word choice typical of human writing.',
          'Gaya bahasa memiliki variasi panjang kalimat yang sangat dinamis dengan kekayaan diksi yang alami khas tulisan manusia murni.',
        ),
      'document_mixed_style' => text(
          'Mixed language patterns were detected. Some paragraphs appear manually written while others contain AI-assisted sentences.',
          'Terdeteksi kombinasi gaya bahasa campuran. Sebagian paragraf terindikasi disusun manual dan sebagian lainnya disisipi kalimat bentukan AI.',
        ),
      'document_mostly_ai_style' => text(
          'Most sentences are strongly indicated as AI-generated. Any detected human-written portions are still counted in the NLP metrics.',
          'Mayoritas kalimat terindikasi kuat dibuat AI. Bagian yang terdeteksi human-written tetap dihitung dalam metrik NLP.',
        ),
      'noise_very_low' => text(
          'Noise level is very low, indicating possible local retouching or smoothing.',
          'Kadar noise sangat rendah, mengindikasikan kemungkinan retouching atau smoothing lokal.',
        ),
      'noise_local_variation' => text(
          'Local noise variation was found. This is a supporting signal and is not enough alone to conclude splicing.',
          'Ditemukan variasi noise lokal. Ini sinyal pendukung dan belum cukup sendiri untuk menyimpulkan splicing.',
        ),
      'noise_uniform' => text(
          'Noise distribution and compression-error traces are even and homogeneous across the image.',
          'Sebaran noise dan jejak eror kompresi tersebar merata dan homogen pada gambar.',
        ),
      'metadata_authentic_camera' => text(
          'Physical camera capture (authentic)',
          'Kamera fisik real (otentik)',
        ),
      'metadata_suspicious_editing' => text(
          'Editing indication detected (suspicious)',
          'Terindikasi editing (mencurigakan)',
        ),
      'metadata_digital_editing' => text(
          'Digital manipulation / editing indicated',
          'Rekayasa digital / editing',
        ),
      'ela_compression_trace' => text(
          'ELA detects compression-level residue. Isolated sharp bright areas may indicate digital pasted regions.',
          'ELA mendeteksi sisa tingkat kompresi. Area cerah tajam yang terisolasi dapat mengindikasikan tempelan digital.',
        ),
      'ai_deepfake_likelihood' => text(
          'AI/deepfake likelihood is calculated from spectral image patterns.',
          'Indikasi AI/deepfake dihitung dari pola spektral gambar.',
        ),
      'ai_likelihood_very_high' => text('Very high', 'Sangat tinggi'),
      'ai_likelihood_suspicious' => text('Suspicious', 'Mencurigakan'),
      'ai_likelihood_low' => text('Low / negative', 'Rendah / negatif'),
      _ => fallback.isEmpty ? key : analysisText(fallback),
    };
  }
}
