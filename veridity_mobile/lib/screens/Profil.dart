import 'package:flutter/material.dart';

class Profil extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const Profil({super.key, this.userData});
  @override
  ProfilState createState() => ProfilState();
}

class ProfilState extends State<Profil> {
  int _selectedIndex = 3;
  late TextEditingController _nameController;
  late TextEditingController _emailController;
  late TextEditingController _passController;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.userData?['name'] ?? "User");
    _emailController = TextEditingController(text: widget.userData?['email'] ?? "email@example.com");
    _passController = TextEditingController(text: "********");
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: Stack(
        children: [
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 30),
              child: Column(
                children: [
                  const SizedBox(height: 50),
                  const Center(
                    child: CircleAvatar(
                      radius: 70,
                      backgroundImage: NetworkImage("https://storage.googleapis.com/tagjs-prod.appspot.com/v1/SmbShTn1dS/1ky4xzpo_expires_30_days.png"),
                    ),
                  ),
                  const SizedBox(height: 30),
                  _buildProfileField("Nama Lengkap", _nameController, false),
                  _buildProfileField("Email", _emailController, false),
                  _buildProfileField("Password", _passController, true),
                  const SizedBox(height: 30),
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () {},
                          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF4338CA), padding: const EdgeInsets.symmetric(vertical: 15)),
                          child: const Text("Edit Data", style: TextStyle(color: Colors.white)),
                        ),
                      ),
                      const SizedBox(width: 15),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () => Navigator.pushNamedAndRemoveUntil(context, '/Login', (r) => false),
                          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFEF4444), padding: const EdgeInsets.symmetric(vertical: 15)),
                          child: const Text("Logout", style: TextStyle(color: Colors.white)),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 120),
                ],
              ),
            ),
          ),
          _buildBottomNav(),
        ],
      ),
    );
  }

  Widget _buildProfileField(String label, TextEditingController controller, bool isPass) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 10, bottom: 8),
          child: Text(label, style: const TextStyle(color: Colors.white, fontSize: 14)),
        ),
        Container(
          decoration: BoxDecoration(color: Colors.white10, borderRadius: BorderRadius.circular(12)),
          child: TextField(
            controller: controller,
            obscureText: isPass,
            style: const TextStyle(color: Colors.white),
            decoration: const InputDecoration(contentPadding: EdgeInsets.all(15), border: InputBorder.none),
          ),
        ),
        const SizedBox(height: 20),
      ],
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
            decoration: const BoxDecoration(color: Color(0xFF0E0E20), borderRadius: BorderRadius.only(topLeft: Radius.circular(20), topRight: Radius.circular(20))),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _navItem(Icons.home, "Home", 0, '/Home'),
                _navItem(Icons.history, "History", 1, '/History'),
                const SizedBox(width: 50),
                _navItem(Icons.help_outline, "Help", 2, '/Help'),
                _navItem(Icons.person_outline, "Profile", 3, '/Profil'),
              ],
            ),
          ),
          Positioned(
            top: -25,
            child: FloatingActionButton(
              onPressed: () => Navigator.pushNamed(context, '/UploadFoto'),
              backgroundColor: const Color(0xFF39D2DD),
              shape: const CircleBorder(),
              child: const Icon(Icons.qr_code_scanner, color: Colors.white, size: 28),
            ),
          ),
        ],
      ),
    );
  }

  Widget _navItem(IconData icon, String label, int index, String route) {
    bool isActive = _selectedIndex == index;
    return GestureDetector(
      onTap: () { if (!isActive) Navigator.pushReplacementNamed(context, route, arguments: widget.userData); },
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