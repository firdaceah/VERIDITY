import 'package:flutter_test/flutter_test.dart';
import 'package:veridity_mobile/features/audit/domain/entities/audit_entity.dart';

void main() {
  test('AuditEntity parses canonical ForensicResource response', () {
    final audit = AuditEntity.fromJson({
      'id': 12,
      'file_name': 'document.pdf',
      'summary_label': 'MIXED TEXT',
      'summary_color': 'warning',
      'final_score': 62.5,
      'metadata_details': {
        'summary': {'verdict': 'MIXED TEXT'},
      },
      'final_result': {
        'full_report': {
          'classification_map': {'Kalimat contoh': 'AI-generated'},
        },
      },
      'created_at': '2026-05-31 14:20:00',
    });

    expect(audit.id, 12);
    expect(audit.fileName, 'document.pdf');
    expect(audit.summaryLabel, 'MIXED TEXT');
    expect(audit.isSafe, isFalse);
    expect(audit.metadataSummary, 'MIXED TEXT');
    expect(audit.classificationCount, 1);
  });

  test('AuditEntity falls back to legacy mobile keys', () {
    final audit = AuditEntity.fromJson({
      'id': 3,
      'image_name': 'photo.jpg',
      'is_deepfake': false,
      'created_at': '2026-05-31 14:20:00',
    });

    expect(audit.fileName, 'photo.jpg');
    expect(audit.summaryColor, 'success');
    expect(audit.isSafe, isTrue);
  });
}
