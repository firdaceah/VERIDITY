import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/widgets/app_bottom_nav.dart';

class Help extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const Help({super.key, this.userData});

  @override
  HelpState createState() => HelpState();
}

class HelpState extends State<Help> {
  final int _selectedIndex = 2;

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: Stack(
        children: [
          Positioned.fill(
            child: SingleChildScrollView(
              padding: EdgeInsets.fromLTRB(
                25,
                40,
                25,
                AppBottomNav.contentBottomPadding(context),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    lang.text("Help", "Bantuan"),
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    lang.text(
                      "Common questions about using VERIDITY.",
                      "Pertanyaan umum tentang penggunaan VERIDITY.",
                    ),
                    style: const TextStyle(color: Colors.white60),
                  ),
                  const SizedBox(height: 25),
                  _faq(
                    lang.text(
                      "How do I analyze a file?",
                      "Bagaimana cara analisis file?",
                    ),
                    lang.text(
                      "Tap the add button in the bottom navigation, choose a photo/PDF, check the preview, then tap Upload & Analyze.",
                      "Ketuk tombol tambah di navigasi bawah, pilih foto/PDF, cek preview, lalu tekan Unggah & Analisis.",
                    ),
                  ),
                  _faq(
                    lang.text(
                      "What formats are supported?",
                      "Format apa yang didukung?",
                    ),
                    lang.text(
                      "VERIDITY supports JPG, JPEG, PNG for photos and text-based PDF documents. PDFs exported from PPT/slides or scanned images are not supported for text analysis yet.",
                      "VERIDITY mendukung JPG, JPEG, PNG untuk foto serta PDF dokumen teks. PDF hasil ekspor PPT/slide atau scan gambar belum didukung untuk analisis teks.",
                    ),
                  ),
                  _faq(
                    lang.text(
                      "What do Safe, Suspicious, and Dangerous mean?",
                      "Apa arti hasil Aman, Mencurigakan, dan Berbahaya?",
                    ),
                    lang.text(
                      "Safe means the file pattern tends to be natural. Suspicious means there are mixed/manipulation indications. Dangerous means AI, deepfake, or strong editing indications are found.",
                      "Aman berarti pola file cenderung natural. Mencurigakan berarti ada indikasi campuran/manipulasi. Berbahaya berarti indikasi AI, deepfake, atau rekayasa cukup kuat.",
                    ),
                  ),
                  _faq(
                    lang.text(
                      "What is the difference between photo and document analysis?",
                      "Apa perbedaan analisis foto dan dokumen?",
                    ),
                    lang.text(
                      "Photos are analyzed using ELA, noise, metadata, and AI/deepfake detection. Documents are analyzed using human, AI, and hybrid text mapping.",
                      "Foto dianalisis memakai ELA, noise, metadata, dan AI/deepfake detection. Dokumen dianalisis memakai pemetaan teks manusia, AI, dan hybrid.",
                    ),
                  ),
                  _faq(
                    lang.text(
                      "Where can I see previous results?",
                      "Di mana melihat hasil sebelumnya?",
                    ),
                    lang.text(
                      "Open the History page. You can search by file name and filter All, Photos, or Documents.",
                      "Buka halaman Riwayat. Kamu bisa mencari nama file dan memfilter Semua, Foto, atau Dokumen.",
                    ),
                  ),
                  _faq(
                    lang.text(
                      "What if I forgot my password?",
                      "Bagaimana jika lupa password?",
                    ),
                    lang.text(
                      "Use Forgot password on the login page to create a reset token by email. If you are still logged in but forgot your old password, log out first and use Forgot password. Edit Profile is only for changing the password when you still remember the old password.",
                      "Gunakan fitur Lupa password di halaman login agar aplikasi membuat token reset melalui email. Jika masih login tetapi benar-benar lupa password lama, keluar dari akun lalu gunakan Lupa password. Menu Edit Profile hanya dipakai jika masih ingat password lama dan ingin menggantinya.",
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

  Widget _faq(String title, String answer) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF1D143E),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          iconColor: const Color(0xFF39D2DD),
          collapsedIconColor: Colors.white54,
          title: Text(
            title,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
          childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          children: [
            Text(
              answer,
              style: const TextStyle(
                color: Colors.white70,
                height: 1.5,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
