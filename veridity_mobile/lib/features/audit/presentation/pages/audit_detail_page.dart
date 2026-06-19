import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../app/app_dependencies.dart';
import '../../domain/entities/audit_entity.dart';

class AuditDetail extends StatefulWidget {
  const AuditDetail({
    super.key,
    required this.audit,
    this.returnToHistory = false,
  });

  final AuditEntity audit;
  final bool returnToHistory;

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

  void _handleBack() {
    if (widget.returnToHistory) {
      Navigator.pushReplacementNamed(
        context,
        '/History',
        arguments: AppDependencies.sessionStore.session?.asRouteArguments(),
      );
      return;
    }

    Navigator.pop(context, true);
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop) {
          _handleBack();
        }
      },
      child: Scaffold(
        backgroundColor: const Color(0xFF111028),
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new),
            onPressed: _handleBack,
          ),
          iconTheme: const IconThemeData(color: Colors.white),
          title: Text(
            lang.text("Analysis Detail", "Detail Analisis"),
            style: const TextStyle(color: Colors.white),
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
                if (audit.isImage &&
                    ((audit.elaImageUrl?.isNotEmpty ?? false) ||
                        (audit.noiseImageUrl?.isNotEmpty ?? false))) ...[
                  const SizedBox(height: 18),
                  _ForensicMapsCard(audit: audit),
                ],
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  onPressed: () => _downloadReport(context, audit),
                  icon: const Icon(Icons.picture_as_pdf_outlined),
                  label: Text(lang.text("Download PDF", "Unduh PDF")),
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
      ),
    );
  }

  Future<void> _downloadReport(BuildContext context, AuditEntity audit) async {
    final lang = AppDependencies.language;
    final uri = AppDependencies.auditRepository.mobileReportUri(
      audit.id,
      languageCode: lang.isEnglish ? 'en' : 'id',
    );
    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);

    if (!context.mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          opened
              ? lang.text("Opening PDF report...", "Membuka laporan PDF...")
              : lang.text(
                  "Unable to open PDF report.",
                  "Tidak dapat membuka laporan PDF.",
                ),
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
    final lang = AppDependencies.language;

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
                  lang.auditLabel(audit.summaryLabel),
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
              lang.fileTypeLabel(isDocument: audit.isDocument),
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
    final imageUrls = [
      audit.fileUrl,
      audit.elaImageUrl,
      audit.noiseImageUrl,
    ].whereType<String>().where((url) => url.isNotEmpty).toList();

    return Container(
      height: audit.isImage ? 260 : 180,
      decoration: BoxDecoration(
        color: const Color(0xFF0E0E20),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Colors.white10),
      ),
      clipBehavior: Clip.antiAlias,
      child: audit.isImage && imageUrls.isNotEmpty
          ? _NetworkImageFallback(urls: imageUrls)
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

class _NetworkImageFallback extends StatefulWidget {
  const _NetworkImageFallback({required this.urls});

  final List<String> urls;

  @override
  State<_NetworkImageFallback> createState() => _NetworkImageFallbackState();
}

class _NetworkImageFallbackState extends State<_NetworkImageFallback> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final url = widget.urls[_index];

    return Image.network(
      url,
      width: double.infinity,
      fit: BoxFit.contain,
      loadingBuilder: (context, child, progress) {
        if (progress == null) {
          return child;
        }
        return const Center(
          child: CircularProgressIndicator(color: Color(0xFF39D2DD)),
        );
      },
      errorBuilder: (context, error, stackTrace) {
        if (_index < widget.urls.length - 1) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted) {
              setState(() => _index += 1);
            }
          });
          return const Center(
            child: CircularProgressIndicator(color: Color(0xFF39D2DD)),
          );
        }

        final lang = AppDependencies.language;

        return Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.broken_image, color: Colors.white54, size: 54),
            const SizedBox(height: 10),
            Text(
              lang.text('Image unavailable', 'Gambar tidak tersedia'),
              style: const TextStyle(color: Colors.white54),
            ),
          ],
        );
      },
    );
  }
}

class _ImageDetailCard extends StatelessWidget {
  const _ImageDetailCard({required this.audit});

  final AuditEntity audit;

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    return _MetricCard(
      title: lang.text('Photo Forensic Details', 'Detail Forensik Foto'),
      icon: Icons.image_search,
      children: [
        _MetricTile(
          label: 'Error Level Analysis',
          value: '${audit.elaScore.toStringAsFixed(2)}%',
          helper: lang.analysisMessage(
            audit.elaInterpretationKey,
            fallback: lang.text(
              'Pixel anomalies and local editing traces.',
              'Anomali piksel dan jejak editing lokal.',
            ),
          ),
        ),
        _MetricTile(
          label: 'AI / Deepfake Score',
          value: audit.ganScore.toStringAsFixed(4),
          helper: lang.text(
            'Probability of AI-generated spectral patterns.',
            'Probabilitas pola spektral buatan AI.',
          ),
        ),
        _MetricTile(
          label: 'Noise Authenticity',
          value: '${audit.noiseAuthenticityScore.toStringAsFixed(2)}%',
          helper: lang.analysisMessage(
            audit.noiseInterpretationKey,
            fallback: audit.noiseInterpretation,
          ),
        ),
        _MetricTile(
          label: 'Metadata',
          value: lang.analysisMessage(
            audit.metadataVerdictKey,
            fallback: audit.metadataSummary,
          ),
          helper: lang.text(
            'EXIF history and editor-application traces.',
            'Riwayat EXIF dan jejak aplikasi editor.',
          ),
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
    final lang = AppDependencies.language;

    return _MetricCard(
      title: lang.text('Document Forensic Details', 'Detail Forensik Dokumen'),
      icon: Icons.manage_search,
      children: [
        _MetricTile(
          label: 'Human Written',
          value: '${audit.humanPercentage.toStringAsFixed(1)}%',
          helper: lang.text(
            'Share of sentences that appear naturally human-written.',
            'Porsi kalimat yang tampak natural ditulis manusia.',
          ),
        ),
        _MetricTile(
          label: 'AI Generated',
          value: '${audit.aiPercentage.toStringAsFixed(1)}%',
          helper: lang.text(
            'Share of sentences with AI-generative patterns.',
            'Porsi kalimat dengan pola generatif AI.',
          ),
        ),
        _MetricTile(
          label: 'Hybrid Refined',
          value: '${audit.hybridPercentage.toStringAsFixed(1)}%',
          helper: lang.text(
            'Share of mixed human writing and AI assistance.',
            'Porsi teks campuran manusia dan bantuan AI.',
          ),
        ),
        _MetricTile(
          label: lang.text('Detected Sentences', 'Kalimat Terdeteksi'),
          value: audit.classificationCount.toString(),
          helper: lang.analysisMessage(
            audit.documentInterpretationKey,
            fallback: audit.documentInterpretation,
          ),
        ),
      ],
    );
  }
}

class _ForensicMapsCard extends StatelessWidget {
  const _ForensicMapsCard({required this.audit});

  final AuditEntity audit;

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;
    final maps = [
      if (audit.elaImageUrl?.isNotEmpty == true)
        _ForensicMapItem(
          title: 'ELA Map',
          helper: lang.text(
            'Compression-error visualization for spotting local edits.',
            'Visualisasi eror kompresi untuk melihat edit lokal.',
          ),
          url: audit.elaImageUrl!,
        ),
      if (audit.noiseImageUrl?.isNotEmpty == true)
        _ForensicMapItem(
          title: lang.text('Noise Map', 'Peta Noise'),
          helper: lang.text(
            'Noise distribution used as supporting forensic evidence.',
            'Sebaran noise sebagai bukti forensik pendukung.',
          ),
          url: audit.noiseImageUrl!,
        ),
    ];

    return _MetricCard(
      title: lang.text('Visual Evidence Maps', 'Peta Bukti Visual'),
      icon: Icons.layers_outlined,
      children: maps,
    );
  }
}

class _ForensicMapItem extends StatelessWidget {
  const _ForensicMapItem({
    required this.title,
    required this.helper,
    required this.url,
  });

  final String title;
  final String helper;
  final String url;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF0E0E20),
        borderRadius: BorderRadius.circular(14),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AspectRatio(
            aspectRatio: 16 / 10,
            child: Image.network(
              url,
              fit: BoxFit.contain,
              loadingBuilder: (context, child, progress) {
                if (progress == null) {
                  return child;
                }
                return const Center(
                  child: CircularProgressIndicator(color: Color(0xFF39D2DD)),
                );
              },
              errorBuilder: (context, error, stackTrace) {
                final lang = AppDependencies.language;
                return Center(
                  child: Text(
                    lang.text('Map unavailable', 'Peta tidak tersedia'),
                    style: const TextStyle(color: Colors.white54),
                  ),
                );
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: Color(0xFF39D2DD),
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  helper,
                  style: const TextStyle(color: Colors.white54, fontSize: 11),
                ),
              ],
            ),
          ),
        ],
      ),
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
