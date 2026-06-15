import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/network/api_exception.dart';
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
  String? _photoUrl;
  PlatformFile? _pendingPhoto;

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
    setState(() => _isSaving = true);

    try {
      final user = await AppDependencies.profileRepository.updateProfile(
        name: _nameController.text.trim(),
        email: _emailController.text.trim(),
      );

      var latestUser = user;
      if (_pendingPhoto != null) {
        latestUser = await AppDependencies.profileRepository.updateProfilePhoto(
          _pendingPhoto!,
        );
      }

      if (_newPasswordController.text.isNotEmpty ||
          _currentPasswordController.text.isNotEmpty ||
          _confirmPasswordController.text.isNotEmpty) {
        await AppDependencies.profileRepository.updatePassword(
          currentPassword: _currentPasswordController.text,
          password: _newPasswordController.text,
          passwordConfirmation: _confirmPasswordController.text,
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
        const SnackBar(content: Text("Profil berhasil diperbarui")),
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
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text("Gagal memperbarui profil")));
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

  @override
  Widget build(BuildContext context) {
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
                      if (_isEditing)
                        Container(
                          decoration: const BoxDecoration(
                            color: Color(0xFF39D2DD),
                            shape: BoxShape.circle,
                          ),
                          child: IconButton(
                            onPressed: _pickPhoto,
                            icon: const Icon(
                              Icons.camera_alt,
                              color: Colors.white,
                            ),
                          ),
                        ),
                    ],
                  ),
                  if (_pendingPhoto != null)
                    const Padding(
                      padding: EdgeInsets.only(top: 10),
                      child: Text(
                        "Foto baru dipilih, simpan untuk mengunggah.",
                        style: TextStyle(color: Colors.white54, fontSize: 12),
                      ),
                    ),
                  const SizedBox(height: 30),
                  _buildProfileField("Nama Lengkap", _nameController),
                  _buildProfileField("Email", _emailController),
                  _buildDisplayField("Password", "********"),
                  if (_isEditing) ...[
                    const SizedBox(height: 12),
                    _buildProfileField(
                      "Password Lama",
                      _currentPasswordController,
                      obscure: true,
                      hint: "Isi jika ingin ganti password",
                    ),
                    _buildProfileField(
                      "Password Baru",
                      _newPasswordController,
                      obscure: true,
                      hint: "Minimal 8 karakter",
                    ),
                    _buildProfileField(
                      "Konfirmasi Password",
                      _confirmPasswordController,
                      obscure: true,
                      hint: "Ulangi password baru",
                    ),
                  ],
                  const SizedBox(height: 30),
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton(
                          onPressed: _isSaving
                              ? null
                              : (_isEditing
                                    ? _saveProfile
                                    : () => setState(() => _isEditing = true)),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF4338CA),
                            padding: const EdgeInsets.symmetric(vertical: 15),
                          ),
                          child: Text(
                            _isSaving
                                ? "Menyimpan..."
                                : (_isEditing ? "Simpan" : "Edit Data"),
                            style: const TextStyle(color: Colors.white),
                          ),
                        ),
                      ),
                      const SizedBox(width: 15),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: _isSaving
                              ? null
                              : (_isEditing
                                    ? () => setState(() => _isEditing = false)
                                    : _logout),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: _isEditing
                                ? Colors.white12
                                : const Color(0xFFEF4444),
                            padding: const EdgeInsets.symmetric(vertical: 15),
                          ),
                          child: Text(
                            _isEditing ? "Batal" : "Logout",
                            style: const TextStyle(color: Colors.white),
                          ),
                        ),
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

  Widget _buildDisplayField(String label, String value) {
    return _FieldShell(
      label: label,
      child: Text(
        value,
        style: const TextStyle(color: Colors.white, fontSize: 15),
      ),
    );
  }

  Widget _buildProfileField(
    String label,
    TextEditingController controller, {
    bool obscure = false,
    String? hint,
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
