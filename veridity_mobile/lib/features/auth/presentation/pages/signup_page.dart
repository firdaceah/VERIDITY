import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/config/api_config.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/utils/legal_link_launcher.dart';
import '../../../audit/presentation/pages/home_page.dart';

class SignUp extends StatefulWidget {
  const SignUp({super.key});
  @override
  SignUpState createState() => SignUpState();
}

class SignUpState extends State<SignUp> {
  //controller
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passController = TextEditingController();
  final TextEditingController _confirmPassController = TextEditingController();

  bool _isObscure = true; // State untuk sembunyikan password
  bool _isObscureConfirm = true;
  bool _isLoading = false;
  bool _acceptedPrivacyPolicy = false;

  Future<void> registerUser() async {
    final lang = AppDependencies.language;
    if (!_acceptedPrivacyPolicy) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            lang.text(
              "Please accept the Privacy Policy first.",
              "Setujui Kebijakan Privasi terlebih dahulu.",
            ),
          ),
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final session = await AppDependencies.authRepository.register(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        password: _passController.text,
        passwordConfirmation: _confirmPassController.text,
      );

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            lang.text("Registration successful!", "Registrasi berhasil!"),
          ),
        ),
      );
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(
          builder: (context) => Home(userData: session.asRouteArguments()),
        ),
        (route) => false,
      );
    } on ApiException catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(lang.text("Failed: ", "Gagal: ") + e.message)),
      );
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            lang.text(
              "Unable to connect to server.",
              "Koneksi ke server gagal!",
            ),
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passController.dispose();
    _confirmPassController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 50),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 20),
            Image.asset("assets/images/logo.png", width: 60),
            const SizedBox(height: 15),
            Text(
              lang.text("Sign up", "Daftar"),
              style: TextStyle(
                color: Colors.white,
                fontSize: 32,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              lang.text(
                "Create your VERIDITY account",
                "Silahkan daftarkan akun anda",
              ),
              style: const TextStyle(color: Colors.white70, fontSize: 14),
            ),
            const SizedBox(height: 35),

            _buildInputLabel(lang.text("Full Name", "Nama Lengkap")),
            _buildTextField(
              _nameController,
              lang.text("Enter your name", "Masukkan nama anda"),
            ),

            _buildInputLabel("Email"),
            _buildTextField(
              _emailController,
              lang.text("Enter your email", "Masukkan email anda"),
            ),

            _buildInputLabel("Password"),
            _buildPasswordField(
              _passController,
              lang.text("Enter your password", "Masukkan password"),
              _isObscure,
              () {
                setState(() {
                  _isObscure = !_isObscure;
                });
              },
            ),

            _buildInputLabel(
              lang.text("Confirm Password", "Konfirmasi Password"),
            ),
            _buildPasswordField(
              _confirmPassController,
              lang.text("Repeat your password", "Ulangi password"),
              _isObscureConfirm,
              () {
                setState(() {
                  _isObscureConfirm = !_isObscureConfirm;
                });
              },
            ),

            const SizedBox(height: 22),
            _buildPrivacyConsent(),
            const SizedBox(height: 22),
            ElevatedButton(
              onPressed: _isLoading || !_acceptedPrivacyPolicy
                  ? null
                  : registerUser,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF4338CA),
                disabledBackgroundColor: const Color(0xFF1D1B3D),
                minimumSize: const Size(double.infinity, 55),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: _isLoading
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.4,
                        color: Colors.white,
                      ),
                    )
                  : Text(
                      lang.text("Sign up", "Daftar"),
                      style: TextStyle(
                        color: _acceptedPrivacyPolicy
                            ? Colors.white
                            : Colors.white38,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
            Center(
              child: TextButton(
                onPressed: () => Navigator.pushNamed(context, '/Login'),
                child: Text.rich(
                  TextSpan(
                    style: const TextStyle(color: Colors.white70),
                    children: [
                      TextSpan(
                        text: lang.text(
                          "Already have an account? ",
                          "Sudah punya akun? ",
                        ),
                      ),
                      TextSpan(
                        text: lang.text("Login", "Masuk"),
                        style: const TextStyle(
                          color: Color(0xFF39D2DD),
                          fontWeight: FontWeight.bold,
                          decoration: TextDecoration.underline,
                        ),
                      ),
                    ],
                  ),
                ),
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
      child: Text(
        label,
        style: const TextStyle(color: Colors.white, fontSize: 15),
      ),
    );
  }

  Widget _buildPrivacyConsent() {
    final lang = AppDependencies.language;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 28,
            height: 28,
            child: Checkbox(
              value: _acceptedPrivacyPolicy,
              activeColor: const Color(0xFF39D2DD),
              checkColor: const Color(0xFF111028),
              side: const BorderSide(color: Colors.white54),
              onChanged: (value) {
                setState(() => _acceptedPrivacyPolicy = value ?? false);
              },
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: RichText(
              text: TextSpan(
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 12,
                  height: 1.5,
                ),
                children: [
                  TextSpan(
                    text: lang.text(
                      'I have read and agree to the ',
                      'Saya telah membaca dan menyetujui ',
                    ),
                  ),
                  WidgetSpan(
                    alignment: PlaceholderAlignment.middle,
                    child: GestureDetector(
                      onTap: () => LegalLinkLauncher.open(
                        context,
                        ApiConfig.privacyPolicyUri,
                      ),
                      child: Text(
                        lang.text('Privacy Policy', 'Kebijakan Privasi'),
                        style: const TextStyle(
                          color: Color(0xFF39D2DD),
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          decoration: TextDecoration.underline,
                        ),
                      ),
                    ),
                  ),
                  const TextSpan(text: ' VERIDITY.'),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTextField(TextEditingController controller, String hint) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
      ),
      child: TextField(
        controller: controller,
        style: const TextStyle(
          color: Color(0xFF111827),
          fontWeight: FontWeight.w600,
        ),
        cursorColor: const Color(0xFF4338CA),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(color: Color(0xFF6B7280)),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 15,
            vertical: 15,
          ),
          border: InputBorder.none,
        ),
      ),
    );
  }

  Widget _buildPasswordField(
    TextEditingController controller,
    String hint,
    bool obscure,
    VoidCallback toggle,
  ) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
      ),
      child: TextField(
        controller: controller,
        obscureText: obscure,
        style: const TextStyle(
          color: Color(0xFF111827),
          fontWeight: FontWeight.w600,
        ),
        cursorColor: const Color(0xFF4338CA),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(color: Color(0xFF6B7280)),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 15,
            vertical: 15,
          ),
          border: InputBorder.none,
          suffixIcon: IconButton(
            icon: Icon(
              obscure ? Icons.visibility : Icons.visibility_off,
              color: Colors.grey,
            ),
            onPressed: toggle,
          ),
        ),
      ),
    );
  }
}
