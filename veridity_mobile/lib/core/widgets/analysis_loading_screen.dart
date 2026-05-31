import 'package:flutter/material.dart';

class AnalysisLoadingScreen extends StatelessWidget {
  const AnalysisLoadingScreen({
    super.key,
    required this.isDocument,
    required this.fileName,
  });

  final bool isDocument;
  final String fileName;

  @override
  Widget build(BuildContext context) {
    final title = isDocument ? 'Menganalisis Dokumen' : 'Menganalisis Foto';
    final subtitle = isDocument
        ? 'Membaca teks, segmentasi kalimat, dan probabilitas AI.'
        : 'Menjalankan ELA, noise, metadata, dan deteksi deepfake.';
    final steps = isDocument
        ? const [
            'Ekstraksi teks dokumen',
            'Pemetaan pola linguistik',
            'Kalkulasi human, AI, hybrid',
          ]
        : const [
            'Pemeriksaan ELA',
            'Ekstraksi metadata dan noise',
            'Deteksi AI/deepfake',
          ];

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: SafeArea(
        child: Padding(
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
                  isDocument ? Icons.description_outlined : Icons.image_search,
                  color: const Color(0xFF39D2DD),
                  size: 58,
                ),
              ),
              const SizedBox(height: 28),
              Text(
                title,
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
                subtitle,
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
      ),
    );
  }
}
