import 'package:flutter/material.dart';

class Home extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const Home({super.key, this.userData});

  @override
  State<Home> createState() => _HomeState();
}

class _HomeState extends State<Home> {
  int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    String userName = widget.userData?['name'] ?? "User";

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: Stack(
        children: [
          Positioned.fill(
            child: SingleChildScrollView(
              padding: const EdgeInsets.only(left: 25, right: 25, top: 50, bottom: 120),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text("Halo,", style: TextStyle(color: Colors.white, fontSize: 18)),
                          Text(userName, style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold)),
                        ],
                      ),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(30),
                        child: Image.asset(
                          "assets/images/user.png",
                          width: 60,
                          height: 60, 
                          fit: BoxFit.cover
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 35),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(25),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(20),
                      gradient: const LinearGradient(colors: [Color(0xFF371F73), Color(0xFF251549)]),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text("Analisis Foto", style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                              Text("Deteksi manipulasi & deepfake citra digital", style: TextStyle(color: Colors.white60, fontSize: 13)),
                            ],
                          ),
                        ),
                        ElevatedButton(
                          onPressed: () => Navigator.pushNamed(context, '/UploadFoto'),
                          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF39D2DD)),
                          child: const Text("Scan", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                        )
                      ],
                    ),
                  ),
                  const SizedBox(height: 40),
                  const Text("Our Method", style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 20),
                  GridView.count(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisCount: 2, mainAxisSpacing: 15, crossAxisSpacing: 15, childAspectRatio: 1.1,
                    children: [
                      _buildMethodItem(Icons.face, "AI Source", "Deteksi AI vs Asli"),
                      _buildMethodItem(Icons.layers, "ELA Visual", "Error Level Analysis"),
                      _buildMethodItem(Icons.lens_blur, "Ghost Map", "Anomali Kompresi"),
                      _buildMethodItem(Icons.info_outline, "Meta Deep", "Metadata Forensik"),
                    ],
                  ),
                ],
              ),
            ),
          ),
          _buildBottomNav(),
        ],
      ),
    );
  }

  Widget _buildMethodItem(IconData icon, String title, String desc) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: const Color(0xFF0E0E20), borderRadius: BorderRadius.circular(15)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: const Color(0xFF7C3AED), size: 28),
          const SizedBox(height: 8),
          Text(title, style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold)),
          Text(desc, style: const TextStyle(color: Colors.white54, fontSize: 10)),
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
              width: 75, height: 75,
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
      onTap: () {
        if (!isActive) {
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