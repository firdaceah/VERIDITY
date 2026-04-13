import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class SignUp extends StatefulWidget {
  const SignUp({super.key});
  @override
  SignUpState createState() => SignUpState();
}

class SignUpState extends State<SignUp> {
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passController = TextEditingController();
  final TextEditingController _confirmPassController = TextEditingController();

  bool _isObscure = true; // State untuk sembunyikan password
  bool _isObscureConfirm = true;

  Future<void> registerUser() async {
    // Gunakan 10.0.2.2 jika pakai Emulator Android, localhost jika pakai HP Asli (pastikan satu Wi-Fi)
    final url = Uri.parse('http://192.168.0.100:8000/api/register');
    
    try {
      final response = await http.post(url, body: {
        'name': _nameController.text,
        'email': _emailController.text,
        'password': _passController.text,
        'password_confirmation': _confirmPassController.text,
      }, headers: {'Accept': 'application/json'});

      if (response.statusCode == 201) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Registrasi Berhasil! Silahkan Login.")));
        Navigator.pushNamed(context, '/Login');
      } else {
        final error = jsonDecode(response.body);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Gagal: ${error['message']}")));
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Koneksi ke Server Gagal!")));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 50),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 20),
            Image.network("https://storage.googleapis.com/tagjs-prod.appspot.com/v1/SmbShTn1dS/jwq8fpsn_expires_30_days.png", width: 60),
            const SizedBox(height: 15),
            const Text("Register", style: TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold)),
            const Text("Silahkan daftarkan akun anda", style: TextStyle(color: Colors.white70, fontSize: 14)),
            const SizedBox(height: 35),
            
            _buildInputLabel("Nama Lengkap"),
            _buildTextField(_nameController, "Masukkan nama anda"),
            
            _buildInputLabel("Email"),
            _buildTextField(_emailController, "Masukkan email anda"),
            
            _buildInputLabel("Password"),
            _buildPasswordField(_passController, "Masukkan password", _isObscure, () {
              setState(() { _isObscure = !_isObscure; });
            }),
            
            _buildInputLabel("Konfirmasi Password"),
            _buildPasswordField(_confirmPassController, "Ulangi password", _isObscureConfirm, () {
              setState(() { _isObscureConfirm = !_isObscureConfirm; });
            }),

            const SizedBox(height: 30),
            ElevatedButton(
              onPressed: registerUser,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF4338CA),
                minimumSize: const Size(double.infinity, 55),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text("Daftar", style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
            ),
            Center(
              child: TextButton(
                onPressed: () => Navigator.pushNamed(context, '/Login'),
                child: const Text("Sudah punya akun? Masuk", style: TextStyle(color: Colors.white70)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInputLabel(String label) {
    return Padding(
      padding: const EdgeInsets.only(left: 5, bottom: 8, top: 15),
      child: Text(label, style: const TextStyle(color: Colors.white, fontSize: 15)),
    );
  }

  Widget _buildTextField(TextEditingController controller, String hint) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10)),
      child: TextField(
        controller: controller,
        decoration: InputDecoration(
          hintText: hint,
          contentPadding: const EdgeInsets.symmetric(horizontal: 15, vertical: 15),
          border: InputBorder.none,
        ),
      ),
    );
  }

  Widget _buildPasswordField(TextEditingController controller, String hint, bool obscure, VoidCallback toggle) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(10)),
      child: TextField(
        controller: controller,
        obscureText: obscure,
        decoration: InputDecoration(
          hintText: hint,
          contentPadding: const EdgeInsets.symmetric(horizontal: 15, vertical: 15),
          border: InputBorder.none,
          suffixIcon: IconButton(
            icon: Icon(obscure ? Icons.visibility_off : Icons.visibility, color: Colors.grey),
            onPressed: toggle,
          ),
        ),
      ),
    );
  }
}