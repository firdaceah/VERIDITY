import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

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
            padding: const EdgeInsets.fromLTRB(24, 24, 24, 36),
            children: [
              _HeaderCard(audit: audit, statusColor: statusColor),
              const SizedBox(height: 18),
              _PreviewCard(audit: audit),
              const SizedBox(height: 18),
              if (audit.isDocument)
                _DocumentDetailCard(audit: audit)
              else
                _ImageDetailCard(audit: audit),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => _downloadReport(context, audit),
                icon: const Icon(Icons.picture_as_pdf_outlined),
                label: const Text("Download PDF"),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF4338CA),
                  foregroundColor: Colors.white,
                  minimumSize: const Size(double.infinity, 52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _downloadReport(BuildContext context, AuditEntity audit) async {
    final uri = AppDependencies.auditRepository.mobileReportUri(audit.id);
    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);

    if (!context.mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          opened
              ? "Membuka laporan PDF..."
              : "Tidak dapat membuka laporan PDF.",
        ),
      ),
    );
  }
}

class _HeaderCard extends StatelessWidget {
  const _HeaderCard({required this.audit, required this.statusColor});

  final AuditEntity audit;
  final Color statusColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: statusColor.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: statusColor.withValues(alpha: 0.25)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: statusColor,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(
                  audit.isDocument
                      ? Icons.description_outlined
                      : Icons.image_search,
                  color: Colors.white,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  audit.summaryLabel,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    height: 1.25,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Expanded(
                child: Text(
                  audit.fileName,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: Colors.white70, fontSize: 12),
                ),
              ),
              const SizedBox(width: 16),
              Text(
                '${audit.finalScore.toStringAsFixed(1)}%',
                style: TextStyle(
                  color: statusColor,
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              audit.isDocument ? 'Dokumen' : 'Foto',
              style: TextStyle(
                color: statusColor,
                fontSize: 11,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PreviewCard extends StatelessWidget {
  const _PreviewCard({required this.audit});

  final AuditEntity audit;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: audit.isImage ? 260 : 180,
      decoration: BoxDecoration(
        color: const Color(0xFF0E0E20),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white10),
      ),
      clipBehavior: Clip.antiAlias,
      child: audit.isImage && audit.fileUrl != null
          ? Image.network(
              audit.fileUrl!,
              fit: BoxFit.contain,
              errorBuilder: (context, error, stackTrace) => const Icon(
                Icons.broken_image,
                color: Colors.white54,
                size: 54,
              ),
            )
          : Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  audit.extension == 'pdf'
                      ? Icons.picture_as_pdf_outlined
                      : Icons.description_outlined,
                  color: audit.extension == 'pdf'
                      ? Colors.redAccent
                      : const Color(0xFF39D2DD),
                  size: 68,
                ),
                const SizedBox(height: 10),
                Text(
                  audit.extension.toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white70,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
    );
  }
}

class _ImageDetailCard extends StatelessWidget {
  const _ImageDetailCard({required this.audit});

  final AuditEntity audit;

  @override
  Widget build(BuildContext context) {
    return _MetricCard(
      title: 'Detail Forensik Foto',
      icon: Icons.image_search,
      children: [
        _MetricTile(
          label: 'Error Level Analysis',
          value: '${audit.elaScore.toStringAsFixed(2)}%',
          helper: 'Anomali piksel dan jejak editing lokal.',
        ),
        _MetricTile(
          label: 'AI / Deepfake Score',
          value: audit.ganScore.toStringAsFixed(4),
          helper: 'Probabilitas pola spektral buatan AI.',
        ),
        _MetricTile(
          label: 'Noise Authenticity',
          value: '${audit.noiseAuthenticityScore.toStringAsFixed(2)}%',
          helper: audit.noiseInterpretation,
        ),
        _MetricTile(
          label: 'Metadata',
          value: audit.metadataSummary,
          helper: 'Riwayat EXIF dan jejak aplikasi editor.',
        ),
      ],
    );
  }
}

class _DocumentDetailCard extends StatelessWidget {
  const _DocumentDetailCard({required this.audit});

  final AuditEntity audit;

  @override
  Widget build(BuildContext context) {
    return _MetricCard(
      title: 'Detail Forensik Dokumen',
      icon: Icons.manage_search,
      children: [
        _MetricTile(
          label: 'Human Written',
          value: '${audit.humanPercentage.toStringAsFixed(1)}%',
          helper: 'Porsi kalimat yang tampak natural ditulis manusia.',
        ),
        _MetricTile(
          label: 'AI Generated',
          value: '${audit.aiPercentage.toStringAsFixed(1)}%',
          helper: 'Porsi kalimat dengan pola generatif AI.',
        ),
        _MetricTile(
          label: 'Hybrid Refined',
          value: '${audit.hybridPercentage.toStringAsFixed(1)}%',
          helper: 'Porsi teks campuran manusia dan bantuan AI.',
        ),
        _MetricTile(
          label: 'Kalimat Terdeteksi',
          value: audit.classificationCount.toString(),
          helper: audit.documentInterpretation,
        ),
      ],
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
    required this.title,
    required this.icon,
    required this.children,
  });

  final String title;
  final IconData icon;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF1D143E),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: const Color(0xFF39D2DD)),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ...children,
        ],
      ),
    );
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({
    required this.label,
    required this.value,
    required this.helper,
  });

  final String label;
  final String value;
  final String helper;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF0E0E20),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(color: Colors.white70, fontSize: 12),
          ),
          const SizedBox(height: 5),
          Text(
            value.isEmpty ? '-' : value,
            softWrap: true,
            style: const TextStyle(
              color: Color(0xFF39D2DD),
              fontWeight: FontWeight.bold,
              height: 1.35,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            helper,
            style: const TextStyle(color: Colors.white54, fontSize: 11),
          ),
        ],
      ),
    );
  }
}
