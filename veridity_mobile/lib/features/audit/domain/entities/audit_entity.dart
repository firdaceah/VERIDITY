class AuditEntity {
  const AuditEntity({
    required this.id,
    required this.fileName,
    required this.summaryLabel,
    required this.summaryColor,
    required this.finalScore,
    required this.createdAt,
    required this.metadataDetails,
    required this.finalResult,
  });

  final int id;
  final String fileName;
  final String summaryLabel;
  final String summaryColor;
  final double finalScore;
  final String createdAt;
  final Map<String, dynamic> metadataDetails;
  final Map<String, dynamic> finalResult;

  bool get isSafe => summaryColor == 'success';
  bool get isDanger => summaryColor == 'danger';
  bool get isWarning => summaryColor == 'warning';

  String get metadataSummary {
    final summary = metadataDetails['summary'];
    if (summary is Map<String, dynamic>) {
      return summary['verdict']?.toString() ??
          summary['status']?.toString() ??
          'Tidak tersedia';
    }
    return 'Tidak tersedia';
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

    return AuditEntity(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      fileName:
          json['file_name']?.toString() ??
          json['image_name']?.toString() ??
          'Unknown File',
      summaryLabel:
          json['summary_label']?.toString() ??
          json['status']?.toString() ??
          (color == 'success' ? 'Aman' : 'Beresiko'),
      summaryColor: color,
      finalScore:
          double.tryParse(
            (json['final_score'] ?? json['score'] ?? 0).toString(),
          ) ??
          0,
      createdAt: json['created_at']?.toString() ?? '',
      metadataDetails: json['metadata_details'] is Map<String, dynamic>
          ? json['metadata_details'] as Map<String, dynamic>
          : <String, dynamic>{},
      finalResult: json['final_result'] is Map<String, dynamic>
          ? json['final_result'] as Map<String, dynamic>
          : <String, dynamic>{},
    );
  }
}
