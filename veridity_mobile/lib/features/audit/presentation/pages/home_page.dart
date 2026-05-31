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
              padding: const EdgeInsets.only(
                left: 25,
                right: 25,
                top: 50,
                bottom: 120,
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
                          const Text(
                            "Halo,",
                            style: TextStyle(color: Colors.white, fontSize: 18),
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
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                "Analisis Foto & Dokumen",
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              Text(
                                "Verifikasi keaslian citra, PDF, dan DOCX",
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
                          child: const Text(
                            "Scan",
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 40),
                  const Text(
                    "Our Method",
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 20),
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
                        "Deteksi wajah dan citra sintetis",
                      ),
                      _buildMethodItem(
                        Icons.layers,
                        "ELA Visual",
                        "Error Level Analysis",
                      ),
                      _buildMethodItem(
                        Icons.description_outlined,
                        "NLP Dokumen",
                        "Pola kalimat manusia vs AI",
                      ),
                      _buildMethodItem(
                        Icons.info_outline,
                        "Metadata",
                        "Metadata Forensik",
                      ),
                    ],
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

  Widget _buildMethodItem(IconData icon, String title, String desc) {
    return Container(
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
    );
  }
}
