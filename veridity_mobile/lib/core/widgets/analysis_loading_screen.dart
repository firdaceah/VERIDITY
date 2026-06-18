import 'package:flutter/material.dart';

import '../../app/app_dependencies.dart';

class AnalysisLoadingScreen extends StatelessWidget {
  const AnalysisLoadingScreen({
    super.key,
    required this.isDocument,
    required this.fileName,
    this.onCancel,
    this.isCancelling = false,
  });

  final bool isDocument;
  final String fileName;
  final Future<void> Function()? onCancel;
  final bool isCancelling;

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;
    final title = isDocument
        ? lang.text('Analyzing Document', 'Menganalisis Dokumen')
        : lang.text('Analyzing Photo', 'Menganalisis Foto');
    final subtitle = isDocument
        ? lang.text(
            'Reading text, segmenting sentences, and calculating AI probability.',
            'Membaca teks, segmentasi kalimat, dan probabilitas AI.',
          )
        : lang.text(
            'Running ELA, noise, metadata, and deepfake detection.',
            'Menjalankan ELA, noise, metadata, dan deteksi deepfake.',
          );
    final steps = isDocument
        ? [
            lang.text('Extracting document text', 'Ekstraksi teks dokumen'),
            lang.text(
              'Mapping linguistic patterns',
              'Pemetaan pola linguistik',
            ),
            lang.text(
              'Calculating human, AI, and hybrid scores',
              'Kalkulasi human, AI, hybrid',
            ),
          ]
        : [
            lang.text('Checking ELA traces', 'Pemeriksaan ELA'),
            lang.text(
              'Extracting metadata and noise',
              'Ekstraksi metadata dan noise',
            ),
            lang.text('Detecting AI/deepfake traces', 'Deteksi AI/deepfake'),
          ];

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: SafeArea(
        child: Stack(
          children: [
            if (onCancel != null)
              Positioned(
                left: 12,
                top: 8,
                child: IconButton(
                  tooltip: lang.text('Cancel analysis', 'Batalkan analisis'),
                  onPressed: isCancelling ? null : () => _confirmCancel(context),
                  icon: isCancelling
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white70,
                          ),
                        )
                      : const Icon(Icons.close, color: Colors.white),
                ),
              ),
            Padding(
              padding: const EdgeInsets.all(28),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 120,
                    height: 120,
                    decoration: BoxDecoration(
                      color: const Color(0xFF1D143E),
                      borderRadius: BorderRadius.circular(28),
                      border: Border.all(color: const Color(0xFF39D2DD)),
                    ),
                    child: Icon(
                      isDocument
                          ? Icons.description_outlined
                          : Icons.image_search,
                      color: const Color(0xFF39D2DD),
                      size: 58,
                    ),
                  ),
                  const SizedBox(height: 28),
                  Text(
                    isCancelling
                        ? lang.text('Cancelling Analysis', 'Membatalkan Analisis')
                        : title,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 26,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    fileName,
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isCancelling
                        ? lang.text(
                            'Sending cancellation request to the analysis server.',
                            'Mengirim permintaan pembatalan ke server analisis.',
                          )
                        : subtitle,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.white54, height: 1.5),
                  ),
                  const SizedBox(height: 28),
                  const CircularProgressIndicator(color: Color(0xFF39D2DD)),
                  const SizedBox(height: 28),
                  ...steps.map(
                    (step) => Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.check_circle_outline,
                            color: Color(0xFF39D2DD),
                            size: 18,
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              step,
                              style: const TextStyle(color: Colors.white70),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _confirmCancel(BuildContext context) async {
    final lang = AppDependencies.language;
    final shouldCancel = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        backgroundColor: const Color(0xFF1D143E),
        title: Text(
          lang.text('Cancel analysis?', 'Batalkan analisis?'),
          style: const TextStyle(color: Colors.white),
        ),
        content: Text(
          lang.text(
            'Are you sure you want to cancel this analysis?',
            'Yakin ingin membatalkan analisis?',
          ),
          style: const TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(lang.text('No', 'Tidak')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(lang.text('Yes, cancel', 'Ya, batalkan')),
          ),
        ],
      ),
    );

    if (shouldCancel == true) {
      await onCancel?.call();
    }
  }
}
