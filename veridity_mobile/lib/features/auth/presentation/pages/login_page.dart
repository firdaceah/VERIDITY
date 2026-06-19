import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/network/api_exception.dart';
import '../../../audit/presentation/pages/home_page.dart';

class Login extends StatefulWidget {
  const Login({super.key});
  @override
  LoginState createState() => LoginState();
}

class LoginState extends State<Login> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passController = TextEditingController();
  final TextEditingController _forgotEmailController = TextEditingController();
  final TextEditingController _resetTokenController = TextEditingController();
  final TextEditingController _resetPasswordController =
      TextEditingController();
  final TextEditingController _resetConfirmController = TextEditingController();
  bool _isObscure = true;
  bool _isLoading = false;

  String _localizedAuthError(String message) {
    final lang = AppDependencies.language;
    final normalized = message.toLowerCase();

    if (normalized.contains('login gagal') ||
        normalized.contains('invalid') ||
        normalized.contains('password salah') ||
        normalized.contains('email atau password')) {
      return lang.text(
        'Login failed. Check your email and password.',
        'Login gagal. Periksa email dan password.',
      );
    }

    if (normalized.contains('token reset password tidak valid') ||
        normalized.contains('invalid token')) {
      return lang.text(
        'The reset token is invalid.',
        'Token reset password tidak valid.',
      );
    }

    if (normalized.contains('kedaluwarsa') ||
        normalized.contains('expired')) {
      return lang.text(
        'The reset token has expired. Request a new one.',
        'Token reset password sudah kedaluwarsa. Minta token baru.',
      );
    }

    return message;
  }

  Future<void> loginUser() async {
    final lang = AppDependencies.language;
    setState(() => _isLoading = true);

    try {
      final session = await AppDependencies.authRepository.login(
        email: _emailController.text.trim(),
        password: _passController.text,
      );

      if (!mounted) {
        return;
      }

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
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_localizedAuthError(e.message))));
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            lang.text("Server is not responding", "Server tidak merespon"),
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
    _emailController.dispose();
    _passController.dispose();
    _forgotEmailController.dispose();
    _resetTokenController.dispose();
    _resetPasswordController.dispose();
    _resetConfirmController.dispose();
    super.dispose();
  }

  Future<void> _showForgotPasswordDialog() async {
    final lang = AppDependencies.language;
    _forgotEmailController.text = _emailController.text.trim();
    _resetTokenController.clear();
    _resetPasswordController.clear();
    _resetConfirmController.clear();

    await showDialog<void>(
      context: context,
      builder: (context) {
        bool requesting = false;
        bool resetting = false;
        bool resetPasswordHidden = true;
        bool resetConfirmHidden = true;

        return StatefulBuilder(
          builder: (context, setDialogState) {
            Future<void> requestReset() async {
              setDialogState(() => requesting = true);
              try {
                final token = await AppDependencies.authRepository
                    .forgotPassword(_forgotEmailController.text.trim());
                if (token != null && token.isNotEmpty) {
                  _resetTokenController.text = token;
                }
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        token != null && token.isNotEmpty
                            ? lang.text(
                                "Reset token created and filled automatically.",
                                "Token reset dibuat dan terisi otomatis.",
                              )
                            : lang.text(
                                "Reset instructions were sent to your email. Open the link from your email to continue.",
                                "Instruksi reset dikirim ke email. Buka tautan dari email untuk melanjutkan.",
                              ),
                      ),
                    ),
                  );
                }
              } on ApiException catch (e) {
                if (context.mounted) {
                  ScaffoldMessenger.of(
                    context,
                  ).showSnackBar(
                    SnackBar(content: Text(_localizedAuthError(e.message))),
                  );
                }
              } finally {
                setDialogState(() => requesting = false);
              }
            }

            Future<void> resetPassword() async {
              setDialogState(() => resetting = true);
              try {
                await AppDependencies.authRepository.resetPassword(
                  email: _forgotEmailController.text.trim(),
                  token: _resetTokenController.text.trim(),
                  password: _resetPasswordController.text,
                  passwordConfirmation: _resetConfirmController.text,
                );
                if (context.mounted) {
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        lang.text(
                          "Password reset successfully. Please log in.",
                          "Password berhasil direset. Silakan login.",
                        ),
                      ),
                    ),
                  );
                }
              } on ApiException catch (e) {
                if (context.mounted) {
                  ScaffoldMessenger.of(
                    context,
                  ).showSnackBar(
                    SnackBar(content: Text(_localizedAuthError(e.message))),
                  );
                }
              } finally {
                setDialogState(() => resetting = false);
              }
            }

            return AlertDialog(
              backgroundColor: const Color(0xFF1D143E),
              title: Text(
                lang.text("Forgot Password", "Lupa Password"),
                style: const TextStyle(color: Colors.white),
              ),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _dialogField(_forgotEmailController, "Email"),
                    const SizedBox(height: 10),
                    _dialogField(
                      _resetTokenController,
                      lang.text("Reset token", "Token reset"),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      lang.text(
                        "If the token is not filled automatically, use the reset link sent to your email.",
                        "Jika token tidak terisi otomatis, gunakan tautan reset yang dikirim ke email.",
                      ),
                      style: const TextStyle(
                        color: Colors.white54,
                        fontSize: 11,
                        height: 1.35,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _dialogPasswordField(
                      _resetPasswordController,
                      lang.text("New password", "Password baru"),
                      hidden: resetPasswordHidden,
                      onToggle: () => setDialogState(
                        () => resetPasswordHidden = !resetPasswordHidden,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _dialogPasswordField(
                      _resetConfirmController,
                      lang.text("Confirm password", "Konfirmasi password"),
                      hidden: resetConfirmHidden,
                      onToggle: () => setDialogState(
                        () => resetConfirmHidden = !resetConfirmHidden,
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: requesting || resetting
                      ? null
                      : () => Navigator.pop(context),
                  child: Text(lang.text("Cancel", "Batal")),
                ),
                TextButton(
                  onPressed: requesting ? null : requestReset,
                  child: Text(
                    requesting
                        ? lang.text("Requesting...", "Meminta...")
                        : lang.text("Request Token", "Minta Token"),
                  ),
                ),
                ElevatedButton(
                  onPressed: resetting ? null : resetPassword,
                  child: Text(resetting ? "Reset..." : "Reset"),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 38, vertical: 60),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 30),
            Image.asset("assets/images/logo.png", width: 57),
            Text(
              lang.text("Login", "Masuk"),
              style: TextStyle(
                color: Colors.white,
                fontSize: 36,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              lang.text(
                "Sign in to continue to your account",
                "Silakan masuk ke akun anda",
              ),
              style: const TextStyle(color: Colors.white70, fontSize: 16),
            ),
            const SizedBox(height: 40),

            const Text(
              "Email",
              style: TextStyle(color: Colors.white, fontSize: 16),
            ),
            const SizedBox(height: 10),
            _buildTextField(
              _emailController,
              lang.text("Enter your email", "Masukkan email anda"),
            ),

            const SizedBox(height: 20),
            const Text(
              "Password",
              style: TextStyle(color: Colors.white, fontSize: 16),
            ),
            const SizedBox(height: 10),
            _buildPasswordField(
              _passController,
              lang.text("Enter your password", "Masukkan password"),
            ),

            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: _showForgotPasswordDialog,
                child: Text(
                  lang.text("Forgot password?", "Lupa password?"),
                  style: const TextStyle(color: Colors.white70, fontSize: 14),
                ),
              ),
            ),

            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isLoading ? null : loginUser,
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
                      lang.text("Login", "Masuk"),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
            Center(
              child: TextButton(
                onPressed: () => Navigator.pushNamed(context, '/SignUp'),
                child: Text.rich(
                  TextSpan(
                    style: const TextStyle(color: Colors.white70),
                    children: [
                      TextSpan(
                        text: lang.text(
                          "Don't have an account? ",
                          "Belum punya akun? ",
                        ),
                      ),
                      TextSpan(
                        text: lang.text("Sign up", "Daftar"),
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

  Widget _buildPasswordField(TextEditingController controller, String hint) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
      ),
      child: TextField(
        controller: controller,
        obscureText: _isObscure,
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
              _isObscure ? Icons.visibility : Icons.visibility_off,
              color: Colors.grey,
            ),
            onPressed: () {
              setState(() {
                _isObscure = !_isObscure;
              });
            },
          ),
        ),
      ),
    );
  }

  Widget _dialogField(
    TextEditingController controller,
    String hint, {
    bool obscure = false,
  }) {
    return TextField(
      controller: controller,
      obscureText: obscure,
      style: const TextStyle(color: Colors.white),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Colors.white38),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Colors.white24),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFF39D2DD)),
        ),
      ),
    );
  }

  Widget _dialogPasswordField(
    TextEditingController controller,
    String hint, {
    required bool hidden,
    required VoidCallback onToggle,
  }) {
    return TextField(
      controller: controller,
      obscureText: hidden,
      style: const TextStyle(color: Colors.white),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Colors.white38),
        suffixIcon: IconButton(
          onPressed: onToggle,
          icon: Icon(
            hidden ? Icons.visibility : Icons.visibility_off,
            color: Colors.white54,
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Colors.white24),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFF39D2DD)),
        ),
      ),
    );
  }
}
