import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/config/api_config.dart';
import '../../../../core/localization/app_language.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/utils/legal_link_launcher.dart';
import '../../../../core/widgets/app_bottom_nav.dart';
import '../../../../core/widgets/profile_avatar.dart';

class Profil extends StatefulWidget {
  final Map<String, dynamic>? userData;
  const Profil({super.key, this.userData});

  @override
  ProfilState createState() => ProfilState();
}

class ProfilState extends State<Profil> {
  final int _selectedIndex = 3;
  late TextEditingController _nameController;
  late TextEditingController _emailController;
  late TextEditingController _currentPasswordController;
  late TextEditingController _newPasswordController;
  late TextEditingController _confirmPasswordController;

  bool _isEditing = false;
  bool _isSaving = false;
  bool _hideCurrentPassword = true;
  bool _hideNewPassword = true;
  bool _hideConfirmPassword = true;
  String? _photoUrl;
  PlatformFile? _pendingPhoto;

  String get _languageCode =>
      AppDependencies.language.value == AppLocale.id ? 'id' : 'en';

  @override
  void initState() {
    super.initState();
    final profile = AppDependencies.profileRepository.currentProfile();
    _nameController = TextEditingController(
      text: profile?.name ?? widget.userData?['name'] ?? "User",
    );
    _emailController = TextEditingController(
      text: profile?.email ?? widget.userData?['email'] ?? "email@example.com",
    );
    _photoUrl =
        profile?.profilePhotoUrl ??
        widget.userData?['profile_photo_url']?.toString();
    _currentPasswordController = TextEditingController();
    _newPasswordController = TextEditingController();
    _confirmPasswordController = TextEditingController();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    if (!_isEditing) {
      return;
    }

    final result = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png'],
      withData: true,
    );

    if (result == null || result.files.single.bytes == null) {
      return;
    }

    setState(() => _pendingPhoto = result.files.single);
  }

  Future<void> _saveProfile() async {
    final lang = AppDependencies.language;
    setState(() => _isSaving = true);

    try {
      final user = await AppDependencies.profileRepository.updateProfile(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
        languageCode: _languageCode,
      );

      var latestUser = user;
      if (_pendingPhoto != null) {
        latestUser = await AppDependencies.profileRepository.updateProfilePhoto(
          _pendingPhoto!,
          languageCode: _languageCode,
        );
      }

      if (_newPasswordController.text.isNotEmpty ||
          _currentPasswordController.text.isNotEmpty ||
          _confirmPasswordController.text.isNotEmpty) {
        await AppDependencies.profileRepository.updatePassword(
          currentPassword: _currentPasswordController.text,
          password: _newPasswordController.text,
          passwordConfirmation: _confirmPasswordController.text,
          languageCode: _languageCode,
        );
      }

      if (!mounted) {
        return;
      }

      setState(() {
        _nameController.text = latestUser.name;
        _emailController.text = latestUser.email;
        _photoUrl = latestUser.profilePhotoUrl;
        _pendingPhoto = null;
        _isEditing = false;
        _currentPasswordController.clear();
        _newPasswordController.clear();
        _confirmPasswordController.clear();
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            lang.text(
              "Profile updated successfully",
              "Profil berhasil diperbarui",
            ),
          ),
        ),
      );
    } on ApiException catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            lang.text("Failed to update profile", "Gagal memperbarui profil"),
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isSaving = false);
      }
    }
  }

  Future<void> _logout() async {
    await AppDependencies.authRepository.logout();
    if (!mounted) {
      return;
    }
    Navigator.pushNamedAndRemoveUntil(context, '/Login', (r) => false);
  }

  void _resetProfileForm() {
    final profile = AppDependencies.profileRepository.currentProfile();
    _nameController.text = profile?.name ?? widget.userData?['name'] ?? "User";
    _emailController.text =
        profile?.email ?? widget.userData?['email'] ?? "email@example.com";
    _photoUrl =
        profile?.profilePhotoUrl ??
        widget.userData?['profile_photo_url']?.toString();
    _currentPasswordController.clear();
    _newPasswordController.clear();
    _confirmPasswordController.clear();
    _pendingPhoto = null;
  }

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
                30,
                0,
                30,
                AppBottomNav.contentBottomPadding(context),
              ),
              child: Column(
                children: [
                  const SizedBox(height: 50),
                  Stack(
                    alignment: Alignment.bottomRight,
                    children: [
                      _buildAvatarPreview(),
                      Container(
                        decoration: const BoxDecoration(
                          color: Color(0xFF39D2DD),
                          shape: BoxShape.circle,
                        ),
                        child: IconButton(
                          onPressed: _isEditing
                              ? _pickPhoto
                              : () => setState(() => _isEditing = true),
                          icon: Icon(
                            _isEditing ? Icons.camera_alt : Icons.edit,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                  if (_isEditing)
                    Padding(
                      padding: const EdgeInsets.only(top: 10),
                      child: Text(
                        lang.text(
                          "Profile photo max 4 MB, JPG, JPEG, or PNG.",
                          "Foto profil maksimal 4 MB, format JPG, JPEG, atau PNG.",
                        ),
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white54,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  if (_pendingPhoto != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 10),
                      child: Text(
                        lang.text(
                          "New photo selected. Save to upload it.",
                          "Foto baru dipilih, simpan untuk mengunggah.",
                        ),
                        style: const TextStyle(
                          color: Colors.white54,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  const SizedBox(height: 30),
                  _buildProfileField(
                    lang.text("Full Name", "Nama Lengkap"),
                    _nameController,
                  ),
                  _buildProfileField("Email", _emailController),
                  if (_isEditing) ...[
                    const SizedBox(height: 12),
                    _buildProfileField(
                      lang.text("Current Password", "Password Lama"),
                      _currentPasswordController,
                      obscure: _hideCurrentPassword,
                      hint: lang.text(
                        "Fill only if you want to change password",
                        "Isi jika ingin ganti password",
                      ),
                      onToggleObscure: () => setState(
                        () => _hideCurrentPassword = !_hideCurrentPassword,
                      ),
                    ),
                    _buildProfileField(
                      lang.text("New Password", "Password Baru"),
                      _newPasswordController,
                      obscure: _hideNewPassword,
                      hint: lang.text(
                        "Minimum 8 characters",
                        "Minimal 8 karakter",
                      ),
                      onToggleObscure: () => setState(
                        () => _hideNewPassword = !_hideNewPassword,
                      ),
                    ),
                    _buildProfileField(
                      lang.text("Confirm Password", "Konfirmasi Password"),
                      _confirmPasswordController,
                      obscure: _hideConfirmPassword,
                      hint: lang.text(
                        "Repeat new password",
                        "Ulangi password baru",
                      ),
                      onToggleObscure: () => setState(
                        () => _hideConfirmPassword = !_hideConfirmPassword,
                      ),
                    ),
                  ],
                  if (_isEditing) ...[
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton(
                            onPressed: _isSaving ? null : _saveProfile,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF4338CA),
                              padding: const EdgeInsets.symmetric(vertical: 15),
                            ),
                            child: Text(
                              _isSaving
                                  ? lang.text("Saving...", "Menyimpan...")
                                  : lang.text("Save", "Simpan"),
                              style: const TextStyle(color: Colors.white),
                            ),
                          ),
                        ),
                        const SizedBox(width: 15),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: _isSaving
                                ? null
                                : () => setState(() {
                                    _resetProfileForm();
                                    _isEditing = false;
                                  }),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.white12,
                              padding: const EdgeInsets.symmetric(vertical: 15),
                            ),
                            child: Text(
                              lang.text("Cancel", "Batal"),
                              style: const TextStyle(color: Colors.white),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                  if (!_isEditing) ...[
                    const SizedBox(height: 18),
                    _buildMenuTile(
                      icon: Icons.settings_outlined,
                      title: lang.text("Settings", "Pengaturan"),
                      subtitle: lang.text(
                        "Language, privacy, and data deletion",
                        "Bahasa, privasi, dan penghapusan data",
                      ),
                      onTap: _showSettingsSheet,
                    ),
                    const SizedBox(height: 12),
                    _buildMenuTile(
                      icon: Icons.logout,
                      title: "Logout",
                      subtitle: lang.text(
                        "Sign out from this device",
                        "Keluar dari perangkat ini",
                      ),
                      danger: true,
                      onTap: _logout,
                    ),
                  ],
                ],
              ),
            ),
          ),
          AppBottomNav(activeIndex: _selectedIndex, userData: widget.userData),
        ],
      ),
    );
  }

  Widget _buildAvatarPreview() {
    final pendingPhoto = _pendingPhoto;
    if (pendingPhoto?.bytes != null) {
      return InkWell(
        onTap: _pickPhoto,
        customBorder: const CircleBorder(),
        child: Container(
          width: 120,
          height: 120,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: const Color(0xFF39D2DD), width: 2),
          ),
          clipBehavior: Clip.antiAlias,
          child: Image.memory(
            pendingPhoto!.bytes!,
            fit: BoxFit.cover,
            errorBuilder: (context, error, stackTrace) =>
                const Icon(Icons.person, color: Colors.white70, size: 60),
          ),
        ),
      );
    }

    return ProfileAvatar(photoUrl: _photoUrl, radius: 60, onTap: _pickPhoto);
  }

  Future<void> _showSettingsSheet() async {
    final lang = AppDependencies.language;

    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: const Color(0xFF1D143E),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            Future<void> setLanguage(AppLocale locale) async {
              await AppDependencies.language.setLocale(locale);
              if (mounted) {
                setState(() {});
              }
              setSheetState(() {});
            }

            return SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(22, 22, 22, 28),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      lang.text("Settings", "Pengaturan"),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      lang.text("Language", "Bahasa"),
                      style: const TextStyle(
                        color: Colors.white70,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    _languageOption(
                      title: "English",
                      selected: AppDependencies.language.value == AppLocale.en,
                      onTap: () => setLanguage(AppLocale.en),
                    ),
                    _languageOption(
                      title: "Indonesia",
                      selected: AppDependencies.language.value == AppLocale.id,
                      onTap: () => setLanguage(AppLocale.id),
                    ),
                    const Divider(color: Colors.white12),
                    _settingsLink(
                      Icons.privacy_tip_outlined,
                      lang.text("Privacy Policy", "Kebijakan Privasi"),
                      ApiConfig.privacyPolicyUri,
                    ),
                    _settingsLink(
                      Icons.delete_outline,
                      lang.text(
                        "Delete Account & Data",
                        "Penghapusan Akun & Data",
                      ),
                      ApiConfig.accountDeletionUri,
                    ),
                    _settingsLink(
                      Icons.cleaning_services_outlined,
                      lang.text("Delete Data Only", "Hapus Data Saja"),
                      ApiConfig.dataDeletionUri,
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _settingsLink(IconData icon, String title, Uri uri) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon, color: const Color(0xFF39D2DD)),
      title: Text(
        title,
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w600,
        ),
      ),
      trailing: const Icon(Icons.open_in_new, color: Colors.white54, size: 18),
      onTap: () => LegalLinkLauncher.open(context, uri),
    );
  }

  Widget _languageOption({
    required String title,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          children: [
            Icon(
              selected
                  ? Icons.radio_button_checked
                  : Icons.radio_button_unchecked,
              color: selected ? const Color(0xFF39D2DD) : Colors.white54,
            ),
            const SizedBox(width: 12),
            Text(
              title,
              style: TextStyle(
                color: selected ? Colors.white : Colors.white70,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMenuTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    bool danger = false,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Colors.white10,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white10),
        ),
        child: Row(
          children: [
            Icon(
              icon,
              color: danger ? const Color(0xFFEF4444) : const Color(0xFF39D2DD),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: const TextStyle(color: Colors.white54, fontSize: 12),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: Colors.white54, size: 22),
          ],
        ),
      ),
    );
  }

  Widget _buildProfileField(
    String label,
    TextEditingController controller, {
    bool obscure = false,
    String? hint,
    VoidCallback? onToggleObscure,
  }) {
    return _FieldShell(
      label: label,
      child: TextField(
        controller: controller,
        enabled: _isEditing,
        obscureText: obscure,
        style: const TextStyle(color: Colors.white),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(color: Colors.white30),
          border: InputBorder.none,
          isDense: true,
          suffixIcon: onToggleObscure == null
              ? null
              : IconButton(
                  onPressed: onToggleObscure,
                  icon: Icon(
                    obscure ? Icons.visibility : Icons.visibility_off,
                    color: Colors.white54,
                  ),
                ),
        ),
      ),
    );
  }
}

class _FieldShell extends StatelessWidget {
  const _FieldShell({required this.label, required this.child});

  final String label;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 10, bottom: 8),
          child: Text(
            label,
            style: const TextStyle(color: Colors.white, fontSize: 14),
          ),
        ),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(15),
          decoration: BoxDecoration(
            color: Colors.white10,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.white10),
          ),
          child: child,
        ),
        const SizedBox(height: 20),
      ],
    );
  }
}
