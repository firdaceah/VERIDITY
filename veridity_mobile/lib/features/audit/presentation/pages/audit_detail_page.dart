import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../domain/entities/audit_entity.dart';

class AuditDetail extends StatefulWidget {
  const AuditDetail({super.key, required this.audit});

  final AuditEntity audit;

  @override
  State<AuditDetail> createState() => _AuditDetailState();
}

class _AuditDetailState extends State<AuditDetail> {
  late Future<AuditEntity> _futureAudit;

  @override
  void initState() {
    super.initState();
    _futureAudit = AppDependencies.auditRepository.detail(widget.audit.id);
  }

  Color _statusColor(String color) {
    return switch (color) {
      'success' => Colors.green,
      'danger' => Colors.red,
      'warning' => Colors.orange,
      _ => Colors.blueGrey,
    };
  }

  Future<void> _deleteAudit(AuditEntity audit) async {
    await AppDependencies.auditRepository.delete(audit.id);
    if (!mounted) {
      return;
    }
    Navigator.pushNamedAndRemoveUntil(
      context,
      '/History',
      (route) => false,
      arguments: AppDependencies.sessionStore.session?.asRouteArguments(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        title: const Text(
          "Detail Analisis",
          style: TextStyle(color: Colors.white),
        ),
      ),
      body: FutureBuilder<AuditEntity>(
        future: _futureAudit,
        initialData: widget.audit,
        builder: (context, snapshot) {
          final audit = snapshot.data ?? widget.audit;
          final statusColor = _statusColor(audit.summaryColor);

          return ListView(
            padding: const EdgeInsets.all(24),
            children: [
              Text(
                audit.fileName,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: const Color(0xFF1D143E),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.white10),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _InfoRow(label: "Status", value: audit.summaryLabel),
                    _InfoRow(
                      label: "Skor akhir",
                      value: audit.finalScore.toStringAsFixed(1),
                    ),
                    _InfoRow(label: "Metadata", value: audit.metadataSummary),
                    _InfoRow(
                      label: "Kalimat terklasifikasi",
                      value: audit.classificationCount.toString(),
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 8,
                      ),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.16),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        audit.summaryColor.toUpperCase(),
                        style: TextStyle(
                          color: statusColor,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 18),
              Text(
                "Report PDF: ${AppDependencies.auditRepository.reportUri(audit.id)}",
                style: const TextStyle(color: Colors.white54, fontSize: 12),
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => _deleteAudit(audit),
                icon: const Icon(Icons.delete_outline),
                label: const Text("Hapus Riwayat"),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  foregroundColor: Colors.white,
                  minimumSize: const Size(double.infinity, 52),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(color: Colors.white54, fontSize: 12),
          ),
          Text(
            value.isEmpty ? "-" : value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 15,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
