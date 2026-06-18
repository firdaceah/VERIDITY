import 'package:flutter/material.dart';

import '../../../../core/config/api_config.dart';
import '../../../../core/utils/legal_link_launcher.dart';
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
                  const Text(
                    "Bantuan",
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    "Pertanyaan umum tentang penggunaan VERIDITY.",
                    style: TextStyle(color: Colors.white60),
                  ),
                  const SizedBox(height: 25),
                  _legalLinkCard(
                    icon: Icons.privacy_tip_outlined,
                    title: "Kebijakan Privasi",
                    subtitle: "Lihat cara VERIDITY mengelola data pengguna.",
                    uri: ApiConfig.privacyPolicyUri,
                  ),
                  _legalLinkCard(
                    icon: Icons.delete_outline,
                    title: "Penghapusan Akun & Data",
                    subtitle: "Panduan meminta penghapusan akun atau data analisis.",
                    uri: ApiConfig.accountDeletionUri,
                  ),
                  const SizedBox(height: 8),
                  _faq(
                    "Bagaimana cara analisis file?",
                    "Ketuk tombol tambah di navigasi bawah, pilih foto/PDF, cek preview, lalu tekan Unggah & Analisis.",
                  ),
                  _faq(
                    "Format apa yang didukung?",
                    "VERIDITY mendukung JPG, JPEG, PNG untuk foto serta PDF dokumen teks. PDF hasil ekspor PPT/slide atau scan gambar belum didukung untuk analisis teks.",
                  ),
                  _faq(
                    "Apa arti hasil Aman, Mencurigakan, dan Berbahaya?",
                    "Aman berarti pola file cenderung natural. Mencurigakan berarti ada indikasi campuran/manipulasi. Berbahaya berarti indikasi AI, deepfake, atau rekayasa cukup kuat.",
                  ),
                  _faq(
                    "Apa perbedaan analisis foto dan dokumen?",
                    "Foto dianalisis memakai ELA, noise, metadata, dan AI/deepfake detection. Dokumen dianalisis memakai pemetaan teks manusia, AI, dan hybrid.",
                  ),
                  _faq(
                    "Di mana melihat hasil sebelumnya?",
                    "Buka halaman Riwayat. Kamu bisa mencari nama file dan memfilter Semua, Foto, atau Dokumen.",
                  ),
                  _faq(
                    "Bagaimana jika lupa password?",
                    "Gunakan fitur Lupa password di halaman login agar aplikasi membuat token reset melalui email. Jika masih login tetapi benar-benar lupa password lama, keluar dari akun lalu gunakan Lupa password. Menu Edit Data hanya dipakai jika masih ingat password lama dan ingin menggantinya.",
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

  Widget _legalLinkCard({
    required IconData icon,
    required String title,
    required String subtitle,
    required Uri uri,
  }) {
    return InkWell(
      onTap: () => LegalLinkLauncher.open(context, uri),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF1D143E),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white10),
        ),
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: const Color(0xFF39D2DD).withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: const Color(0xFF39D2DD)),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: const TextStyle(color: Colors.white60, fontSize: 12),
                  ),
                ],
              ),
            ),
            const Icon(Icons.open_in_new, color: Colors.white54, size: 18),
          ],
        ),
      ),
    );
  }
}
