import 'package:flutter/material.dart';

class Help extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const Help({super.key, this.userData});
  @override
  HelpState createState() => HelpState();
}

class HelpState extends State<Help> {
  int _selectedIndex = 2; // Index 2 untuk Help

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: Stack(
        children: [
          Positioned.fill(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 25, vertical: 40),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text("Bantuan",
                      style: TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 25),
                  
                  // Card Panduan Lengkap
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1D143E),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.white10),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text("Panduan Cepat Penggunaan VeriDity",
                            style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                        SizedBox(height: 15),
                        Text(
                          "Selamat datang di VeriDity! Gunakan panduan ini untuk memahami cara menganalisis keaslian foto Anda.\n\n"
                          "1. Cara Melakukan Analisis Foto\n"
                          "• Klik ikon Scan (+) yang menonjol di tengah bottom navigation.\n"
                          "• Unggah Gambar: Pilih foto dari galeri (Format: PNG/JPG, Maks. 5MB).\n"
                          "• Tunggu sistem menjalankan algoritma ELA & Noise Analysis.\n"
                          "• Hasil akan muncul otomatis di Riwayat.",
                          style: TextStyle(color: Colors.white70, fontSize: 14, height: 1.5),
                        ),
                      ],
                    ),
                  ),
                  // const SizedBox(height: 120),
                ],
              ),
            ),
          ),
          _buildBottomNav(),
        ],
      ),
    );
  }

  Widget _buildBottomNav() {
    return Positioned(
      bottom: 0, left: 0, right: 0,
      child: Stack(
        alignment: Alignment.topCenter,
        clipBehavior: Clip.none,
        children: [
          Container(
            height: 80,
            decoration: const BoxDecoration(
              color: Color(0xFF0E0E20),
              borderRadius: BorderRadius.only(topLeft: Radius.circular(20), topRight: Radius.circular(20))
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _navItem(Icons.home, "Home", 0, '/Home'),
                _navItem(Icons.history, "History", 1, '/History'),
                const SizedBox(width: 60),
                _navItem(Icons.help_outline, "Help", 2, '/Help'),
                _navItem(Icons.person_outline, "Profile", 3, '/Profil'),
              ],
            ),
          ),
          Positioned(
            top: -18,
            child: SizedBox(
              width: 75,
              height: 75,
              child: FloatingActionButton(
                heroTag: null,
                onPressed: () => Navigator.pushNamed(context, '/UploadFoto'),
                backgroundColor: const Color(0xFF39D2DD),
                shape: const CircleBorder(),
                child: const Icon(Icons.qr_code_scanner, color: Colors.white, size: 45),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _navItem(IconData icon, String label, int index, String route) {
    bool isActive = _selectedIndex == index;
    return GestureDetector(
      onTap: () { if (!isActive) {
        Future.delayed(Duration.zero, () {
            if (mounted) Navigator.pushNamedAndRemoveUntil(context, route, (route)=>false, arguments: widget.userData);
          });
      }
      },
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: isActive ? const Color(0xFF7C3AED) : Colors.white54),
          Text(label, style: TextStyle(color: isActive ? const Color(0xFF7C3AED) : Colors.white54, fontSize: 11)),
        ],
      ),
    );
  }
}