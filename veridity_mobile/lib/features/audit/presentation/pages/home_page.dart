import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/widgets/app_bottom_nav.dart';
import '../../../../core/widgets/profile_avatar.dart';

class Home extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const Home({super.key, this.userData});

  @override
  State<Home> createState() => _HomeState();
}

class _HomeState extends State<Home> {
  final int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;
    String userName =
        AppDependencies.sessionStore.session?.user.name ??
        widget.userData?['name'] ??
        "User";
    final photoUrl =
        AppDependencies.sessionStore.session?.user.profilePhotoUrl ??
        widget.userData?['profile_photo_url']?.toString();

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: Stack(
        children: [
          Positioned.fill(
            child: SingleChildScrollView(
              padding: EdgeInsets.only(
                left: 25,
                right: 25,
                top: 50,
                bottom: AppBottomNav.contentBottomPadding(context),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            lang.text("Hello,", "Halo,"),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 18,
                            ),
                          ),
                          Text(
                            userName,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 26,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                      ProfileAvatar(photoUrl: photoUrl, radius: 30),
                    ],
                  ),
                  const SizedBox(height: 35),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(25),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(20),
                      gradient: const LinearGradient(
                        colors: [Color(0xFF371F73), Color(0xFF251549)],
                      ),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                lang.text(
                                  "Photo & Document Analysis",
                                  "Analisis Foto & Dokumen",
                                ),
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              Text(
                                lang.text(
                                  "Verify image and PDF authenticity",
                                  "Verifikasi keaslian citra dan PDF",
                                ),
                                style: TextStyle(
                                  color: Colors.white60,
                                  fontSize: 13,
                                ),
                              ),
                            ],
                          ),
                        ),
                        ElevatedButton(
                          onPressed: () =>
                              Navigator.pushNamed(context, '/UploadFoto'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF39D2DD),
                          ),
                          child: Text(
                            lang.text("Scan", "Scan"),
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 40),
                  Text(
                    lang.text(
                      "Our Detection Methods",
                      "Metode Deteksi VERIDITY",
                    ),
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    lang.text(
                      "VERIDITY combines several forensic methods to help explain why a file is considered safe, suspicious, or risky.",
                      "VERIDITY menggabungkan beberapa metode forensik untuk menjelaskan mengapa file dinilai aman, mencurigakan, atau berisiko.",
                    ),
                    style: const TextStyle(
                      color: Colors.white60,
                      height: 1.5,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 18),
                  GridView.count(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisCount: 2,
                    mainAxisSpacing: 15,
                    crossAxisSpacing: 15,
                    childAspectRatio: 1.1,
                    children: [
                      _buildMethodItem(
                        Icons.face,
                        "Deepfake AI",
                        lang.text(
                          "Synthetic image screening",
                          "Deteksi wajah dan citra sintetis",
                        ),
                        lang.text(
                          "Analyzes visual patterns that often appear in synthetic images or AI-generated outputs.",
                          "Menganalisis pola visual yang sering muncul pada gambar sintetis atau hasil generator AI.",
                        ),
                      ),
                      _buildMethodItem(
                        Icons.layers,
                        "ELA Visual",
                        "Error Level Analysis",
                        lang.text(
                          "Compares pixel compression levels to find areas with editing traces that differ from the rest of the image.",
                          "Membandingkan level kompresi piksel untuk menemukan area yang punya jejak penyuntingan berbeda dari bagian lain.",
                        ),
                      ),
                      _buildMethodItem(
                        Icons.description_outlined,
                        lang.text(
                          "Document Text Analysis",
                          "Analisis Teks Dokumen",
                        ),
                        lang.text(
                          "Lightweight linguistic indicators",
                          "Indikator linguistik ringan",
                        ),
                        lang.text(
                          "Analyzes sentence variation, repetition, vocabulary diversity, and structured AI-like writing patterns in text-based PDF documents.",
                          "Menganalisis variasi kalimat, repetisi, keragaman kosakata, dan pola struktur penulisan yang menyerupai AI pada dokumen PDF berbasis teks.",
                        ),
                      ),
                      _buildMethodItem(
                        Icons.info_outline,
                        "Metadata",
                        lang.text("Forensic metadata", "Metadata Forensik"),
                        lang.text(
                          "Reads EXIF traces, device/software information, timestamps, and file processing indications.",
                          "Membaca jejak EXIF, perangkat, software, waktu, dan indikasi pengolahan file.",
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 22),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0E0E20),
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: Colors.white10),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.route_outlined,
                          color: Color(0xFF39D2DD),
                          size: 28,
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Text(
                            lang.text(
                              "Workflow: upload a file, wait for forensic analysis, then review the result and download the PDF report.",
                              "Alur kerja: unggah file, tunggu analisis forensik, lalu lihat detail hasil dan unduh laporan PDF.",
                            ),
                            style: const TextStyle(
                              color: Colors.white70,
                              height: 1.45,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          AppBottomNav(activeIndex: _selectedIndex, userData: widget.userData),
        ],
      ),
    );
  }

  Widget _buildMethodItem(
    IconData icon,
    String title,
    String desc,
    String detail,
  ) {
    return InkWell(
      borderRadius: BorderRadius.circular(15),
      onTap: () => _showMethodDetail(icon, title, detail),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: const Color(0xFF0E0E20),
          borderRadius: BorderRadius.circular(15),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: const Color(0xFF7C3AED), size: 28),
            const SizedBox(height: 8),
            Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 14,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              desc,
              style: const TextStyle(color: Colors.white54, fontSize: 10),
            ),
          ],
        ),
      ),
    );
  }

  void _showMethodDetail(IconData icon, String title, String detail) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: const Color(0xFF1D143E),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: const Color(0xFF39D2DD), size: 34),
            const SizedBox(height: 14),
            Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              detail,
              style: const TextStyle(
                color: Colors.white70,
                height: 1.5,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 18),
          ],
        ),
      ),
    );
  }
}
