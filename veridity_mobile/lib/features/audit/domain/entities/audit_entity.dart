import '../../../../core/config/api_config.dart';

class AuditEntity {
  const AuditEntity({
    required this.id,
    required this.fileName,
    required this.summaryLabel,
    required this.summaryColor,
    required this.finalScore,
    required this.createdAt,
    this.fileUrl,
    this.storagePath,
    this.elaImageUrl,
    this.noiseImageUrl,
    required this.metadataDetails,
    required this.finalResult,
    this.analysisLanguage = 'en',
  });

  final int id;
  final String fileName;
  final String summaryLabel;
  final String summaryColor;
  final double finalScore;
  final String createdAt;
  final String? fileUrl;
  final String? storagePath;
  final String? elaImageUrl;
  final String? noiseImageUrl;
  final Map<String, dynamic> metadataDetails;
  final Map<String, dynamic> finalResult;
  final String analysisLanguage;

  String get extension => fileName.split('.').last.toLowerCase();
  bool get isDocument => ['pdf', 'docx'].contains(extension);
  bool get isImage => !isDocument;
  bool get isSafe => summaryColor == 'success';
  bool get isDanger => summaryColor == 'danger';
  bool get isWarning => summaryColor == 'warning';
  IconType get iconType {
    if (extension == 'pdf') {
      return IconType.pdf;
    }
    if (extension == 'docx') {
      return IconType.docx;
    }
    return IconType.image;
  }

  Map<String, dynamic> get fullReport {
    final report = finalResult['full_report'];
    return report is Map<String, dynamic> ? report : <String, dynamic>{};
  }

  Map<String, dynamic> get results {
    final map = fullReport['results'];
    return map is Map<String, dynamic> ? map : <String, dynamic>{};
  }

  double get elaScore => _metric(['ela', 'metrics', 'anomaly_score']);
  double get ganScore => _metric(['ai_detection', 'metrics', 'gan_score']);
  double get noiseAuthenticityScore =>
      _metric(['noise', 'metrics', 'noise_authenticity_score']);
  double get metadataAuthenticityScore =>
      _metric(['metadata', 'summary', 'authenticity_score']);
  double get humanPercentage => _metric(['document', 'metrics', 'human_p']);
  double get aiPercentage => _metric(['document', 'metrics', 'ai_p']);
  double get hybridPercentage => _metric(['document', 'metrics', 'hybrid_p']);

  String get documentInterpretation => _stringMetric([
    'document',
    'interpretation',
  ], fallback: 'Language analysis completed.');
  String get documentInterpretationKey => _stringMetric([
    'document',
    'interpretation_key',
  ], fallback: '');
  String get noiseInterpretation => _stringMetric([
    'noise',
    'interpretation',
  ], fallback: 'Noise analysis completed.');
  String get noiseInterpretationKey => _stringMetric([
    'noise',
    'interpretation_key',
  ], fallback: '');
  String get elaInterpretationKey => _stringMetric([
    'ela',
    'interpretation_key',
  ], fallback: '');
  String get metadataVerdictKey {
    final summary = metadataDetails['summary'];
    if (summary is Map<String, dynamic>) {
      return summary['verdict_key']?.toString() ?? '';
    }
    return '';
  }

  double _metric(List<String> path) {
    dynamic current = results;
    for (final key in path) {
      if (current is! Map<String, dynamic>) {
        return 0;
      }
      current = current[key];
    }
    return double.tryParse(current?.toString() ?? '') ?? 0;
  }

  String _stringMetric(List<String> path, {required String fallback}) {
    dynamic current = results;
    for (final key in path) {
      if (current is! Map<String, dynamic>) {
        return fallback;
      }
      current = current[key];
    }
    return current?.toString().isNotEmpty == true
        ? current.toString()
        : fallback;
  }

  String get metadataSummary {
    final summary = metadataDetails['summary'];
    if (summary is Map<String, dynamic>) {
      return summary['verdict']?.toString() ??
          summary['status']?.toString() ??
          'Unavailable';
    }
    return 'Unavailable';
  }

  int get classificationCount {
    final fullReport = finalResult['full_report'];
    if (fullReport is Map<String, dynamic>) {
      final map = fullReport['classification_map'];
      if (map is Map) {
        return map.length;
      }
    }
    return 0;
  }

  factory AuditEntity.fromJson(Map<String, dynamic> json) {
    final isDeepfake =
        json['is_deepfake'] == true ||
        json['is_deepfake']?.toString() == '1' ||
        json['is_ai'] == true;

    final color =
        json['summary_color']?.toString() ??
        json['color']?.toString() ??
        (isDeepfake ? 'danger' : 'success');

    final storagePath = json['storage_path']?.toString();
    final fileUrl =
        _nonEmpty(json['file_url']) ??
        _nonEmpty(json['image_url']) ??
        _publicFileUrl(storagePath);

    return AuditEntity(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      fileName:
          json['file_name']?.toString() ??
          json['image_name']?.toString() ??
          'Unknown File',
      summaryLabel:
          json['summary_label']?.toString() ??
          json['status']?.toString() ??
          (color == 'success' ? 'Safe' : 'High risk'),
      summaryColor: color,
      finalScore:
          double.tryParse(
            (json['final_score'] ?? json['score'] ?? 0).toString(),
          ) ??
          0,
      createdAt: json['created_at']?.toString() ?? '',
      fileUrl: fileUrl,
      storagePath: storagePath,
      elaImageUrl: json['ela_image_url']?.toString(),
      noiseImageUrl: json['noise_image_url']?.toString(),
      metadataDetails: json['metadata_details'] is Map<String, dynamic>
          ? json['metadata_details'] as Map<String, dynamic>
          : <String, dynamic>{},
      finalResult: json['final_result'] is Map<String, dynamic>
          ? json['final_result'] as Map<String, dynamic>
          : <String, dynamic>{},
      analysisLanguage: json['analysis_language']?.toString() ?? 'en',
    );
  }

  static String? _publicFileUrl(String? path) {
    if (path == null || path.isEmpty) {
      return null;
    }

    final apiBase = ApiConfig.baseUrl.endsWith('/api')
        ? ApiConfig.baseUrl.substring(0, ApiConfig.baseUrl.length - 4)
        : ApiConfig.baseUrl;
    final normalizedBase = apiBase.endsWith('/')
        ? apiBase.substring(0, apiBase.length - 1)
        : apiBase;
    final normalizedPath = path.startsWith('/') ? path.substring(1) : path;

    return Uri.encodeFull('$normalizedBase/files/$normalizedPath');
  }

  static String? _nonEmpty(Object? value) {
    final text = value?.toString().trim();
    return text == null || text.isEmpty ? null : text;
  }
}

enum IconType { image, pdf, docx }
