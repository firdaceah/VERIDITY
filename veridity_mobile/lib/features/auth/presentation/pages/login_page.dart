import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/localization/app_language.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/widgets/app_top_snackbar.dart';
import '../../../audit/presentation/pages/home_page.dart';

class Login extends StatefulWidget {
  const Login({super.key});
  @override
  LoginState createState() => LoginState();
}

class LoginState extends State<Login> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passController = TextEditingController();
  bool _isObscure = true;
  bool _isLoading = false;

  String get _languageCode =>
      AppDependencies.language.value == AppLocale.id ? 'id' : 'en';

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
        languageCode: _languageCode,
      );

      if (!mounted) {
        return;
      }

      AppTopSnackBar.success(
        context,
        lang.text('Login successful.', 'Login berhasil.'),
      );
      await Future<void>.delayed(const Duration(milliseconds: 450));
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
      AppTopSnackBar.error(context, _localizedAuthError(e.message));
    } catch (e) {
      if (!mounted) {
        return;
      }
      AppTopSnackBar.error(
        context,
        lang.text("Server is not responding", "Server tidak merespon"),
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
    super.dispose();
  }

  Future<void> _openForgotPassword() async {
    await Navigator.push<void>(
      context,
      MaterialPageRoute(
        builder: (context) => ForgotPasswordFlowPage(
          initialEmail: _emailController.text.trim(),
          localizedAuthError: _localizedAuthError,
        ),
      ),
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
                onPressed: _openForgotPassword,
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

}

class ForgotPasswordFlowPage extends StatefulWidget {
  const ForgotPasswordFlowPage({
    super.key,
    required this.initialEmail,
    required this.localizedAuthError,
  });

  final String initialEmail;
  final String Function(String message) localizedAuthError;

  @override
  State<ForgotPasswordFlowPage> createState() => _ForgotPasswordFlowPageState();
}

class _ForgotPasswordFlowPageState extends State<ForgotPasswordFlowPage> {
  late final TextEditingController _emailController;
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmController = TextEditingController();

  bool _requesting = false;
  bool _resetting = false;
  bool _passwordHidden = true;
  bool _confirmHidden = true;
  int _step = 0;
  String? _resetToken;

  String get _languageCode =>
      AppDependencies.language.value == AppLocale.id ? 'id' : 'en';

  @override
  void initState() {
    super.initState();
    _emailController = TextEditingController(text: widget.initialEmail);
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _requestReset() async {
    final lang = AppDependencies.language;
    setState(() => _requesting = true);

    try {
      final token = await AppDependencies.authRepository.forgotPassword(
        _emailController.text.trim(),
        languageCode: _languageCode,
      );

      if (!mounted) {
        return;
      }

      if (token == null || token.isEmpty) {
        AppTopSnackBar.error(
          context,
          lang.text(
            'Unable to prepare password reset. Please try again.',
            'Reset password belum dapat disiapkan. Silakan coba lagi.',
          ),
        );
        return;
      }

      setState(() {
        _resetToken = token;
        _step = 1;
      });
      AppTopSnackBar.success(
        context,
        lang.text(
          'Email verified. Create your new password.',
          'Email terverifikasi. Buat password baru.',
        ),
      );
    } on ApiException catch (e) {
      if (mounted) {
        AppTopSnackBar.error(context, widget.localizedAuthError(e.message));
      }
    } catch (e) {
      if (mounted) {
        AppTopSnackBar.error(
          context,
          lang.text('Server is not responding', 'Server tidak merespon'),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _requesting = false);
      }
    }
  }

  Future<void> _resetPassword() async {
    final lang = AppDependencies.language;
    final token = _resetToken;

    if (token == null || token.isEmpty) {
      AppTopSnackBar.error(
        context,
        lang.text(
          'Please verify your email first.',
          'Verifikasi email terlebih dahulu.',
        ),
      );
      return;
    }

    setState(() => _resetting = true);

    try {
      await AppDependencies.authRepository.resetPassword(
        email: _emailController.text.trim(),
        token: token,
        password: _passwordController.text,
        passwordConfirmation: _confirmController.text,
        languageCode: _languageCode,
      );

      if (!mounted) {
        return;
      }

      AppTopSnackBar.success(
        context,
        lang.text(
          'Password reset successfully. Please log in.',
          'Password berhasil direset. Silakan login.',
        ),
      );
      await Future<void>.delayed(const Duration(milliseconds: 650));
      if (mounted) {
        Navigator.pop(context);
      }
    } on ApiException catch (e) {
      if (mounted) {
        AppTopSnackBar.error(context, widget.localizedAuthError(e.message));
      }
    } catch (e) {
      if (mounted) {
        AppTopSnackBar.error(
          context,
          lang.text('Failed to reset password.', 'Gagal reset password.'),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _resetting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        title: Text(
          lang.text('Reset Password', 'Reset Password'),
          style: const TextStyle(color: Colors.white),
        ),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(30, 28, 30, 36),
          children: [
            Text(
              _step == 0
                  ? lang.text('Enter your email', 'Masukkan email')
                  : lang.text('Create new password', 'Buat password baru'),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 28,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              _step == 0
                  ? lang.text(
                      'We will verify your account before you create a new password.',
                      'Kami akan memverifikasi akun sebelum kamu membuat password baru.',
                    )
                  : lang.text(
                      'Use at least 8 characters for your new password.',
                      'Gunakan minimal 8 karakter untuk password baru.',
                    ),
              style: const TextStyle(color: Colors.white60, height: 1.45),
            ),
            const SizedBox(height: 28),
            if (_step == 0) ...[
              _resetField(
                controller: _emailController,
                hint: 'Email',
                keyboardType: TextInputType.emailAddress,
              ),
              const SizedBox(height: 18),
              _primaryButton(
                label: _requesting
                    ? lang.text('Checking...', 'Memeriksa...')
                    : lang.text('Continue', 'Lanjut'),
                loading: _requesting,
                onPressed: _requesting ? null : _requestReset,
              ),
            ] else ...[
              _resetField(
                controller: _passwordController,
                hint: lang.text('New password', 'Password baru'),
                obscureText: _passwordHidden,
                onToggle: () =>
                    setState(() => _passwordHidden = !_passwordHidden),
              ),
              const SizedBox(height: 14),
              _resetField(
                controller: _confirmController,
                hint: lang.text('Confirm password', 'Konfirmasi password'),
                obscureText: _confirmHidden,
                onToggle: () =>
                    setState(() => _confirmHidden = !_confirmHidden),
              ),
              const SizedBox(height: 18),
              _primaryButton(
                label: _resetting
                    ? lang.text('Saving...', 'Menyimpan...')
                    : lang.text('Save Password', 'Simpan Password'),
                loading: _resetting,
                onPressed: _resetting ? null : _resetPassword,
              ),
              TextButton(
                onPressed: _resetting ? null : () => setState(() => _step = 0),
                child: Text(lang.text('Change email', 'Ganti email')),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _primaryButton({
    required String label,
    required bool loading,
    required VoidCallback? onPressed,
  }) {
    return ElevatedButton(
      onPressed: onPressed,
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF4338CA),
        disabledBackgroundColor: const Color(0xFF1D1B3D),
        minimumSize: const Size(double.infinity, 54),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
      child: loading
          ? const SizedBox(
              width: 22,
              height: 22,
              child: CircularProgressIndicator(
                strokeWidth: 2.4,
                color: Colors.white,
              ),
            )
          : Text(
              label,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
              ),
            ),
    );
  }

  Widget _resetField({
    required TextEditingController controller,
    required String hint,
    TextInputType? keyboardType,
    bool obscureText = false,
    VoidCallback? onToggle,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: TextField(
        controller: controller,
        keyboardType: keyboardType,
        obscureText: obscureText,
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
          suffixIcon: onToggle == null
              ? null
              : IconButton(
                  onPressed: onToggle,
                  icon: Icon(
                    obscureText ? Icons.visibility : Icons.visibility_off,
                    color: Colors.grey,
                  ),
                ),
        ),
      ),
    );
  }
}
