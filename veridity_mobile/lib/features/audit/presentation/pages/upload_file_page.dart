import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/widgets/analysis_loading_screen.dart';

class UploadFoto extends StatefulWidget {
  const UploadFoto({super.key});

  @override
  State<UploadFoto> createState() => _UploadFotoState();
}

class _UploadFotoState extends State<UploadFoto> {
  PlatformFile? _selectedFile;
  bool _isLoading = false;
  String? _errorMessage;

  bool get _hasSelectedFile =>
      _selectedFile?.bytes != null && _selectedFile!.bytes!.isNotEmpty;

  bool get _isDocument {
    final extension = _selectedFile?.extension?.toLowerCase();
    return extension == 'pdf';
  }

  bool get _isImage {
    final extension = _selectedFile?.extension?.toLowerCase();
    return ['jpg', 'jpeg', 'png'].contains(extension);
  }

  String _friendlyUploadError(String message) {
    final normalized = message.toLowerCase();

    if (normalized.contains('image failed to upload') ||
        normalized.contains('file failed to upload') ||
        normalized.contains('failed to upload')) {
      return _isDocument
          ? 'Dokumen gagal diunggah. Pastikan file PDF tidak lebih dari 15MB dan berisi teks dokumen, bukan slide/presentasi yang diekspor menjadi PDF.'
          : 'Foto gagal diunggah. Pastikan file JPG/JPEG/PNG tidak lebih dari 15MB. Jika ukuran sudah benar, coba kompres foto atau ulangi setelah server aktif.';
    }

    if (normalized.contains(
      'server mengirim respons yang tidak dapat dibaca',
    )) {
      return _isDocument
          ? 'Analisis dokumen belum dapat diproses. Gunakan PDF dokumen teks, bukan file presentasi/slide yang dijadikan PDF.'
          : message;
    }

    return message;
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
      withData: true,
    );

    if (result == null || result.files.isEmpty) {
      return;
    }

    setState(() {
      _selectedFile = result.files.single;
      _errorMessage = null;
    });
  }

  Future<void> _uploadAndAnalyze() async {
    final file = _selectedFile;
    if (file == null || file.bytes == null || file.bytes!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Pilih file terlebih dahulu")),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final audit = await AppDependencies.auditRepository.uploadFile(file);
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text("Analisis selesai: ${audit.summaryLabel}")),
      );
      Navigator.pushReplacementNamed(
        context,
        '/AuditDetail',
        arguments: {'audit': audit, 'returnToHistory': true},
      );
    } on ApiException catch (e) {
      if (!mounted) {
        return;
      }
      final message = _friendlyUploadError(e.message);
      setState(() => _errorMessage = message);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    } catch (e) {
      if (!mounted) {
        return;
      }
      final message = _friendlyUploadError("Gagal mengunggah file: $e");
      setState(() => _errorMessage = message);
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return AnalysisLoadingScreen(
        isDocument: _isDocument,
        fileName: _selectedFile?.name ?? 'File analisis',
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFF111028),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(30, 0, 30, 28),
          children: [
            const Text(
              "Unggah File",
              style: TextStyle(
                color: Colors.white,
                fontSize: 32,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 40),
            const Text(
              "Format: JPG, JPEG, PNG, atau PDF dokumen teks. PDF dari PPT/slide atau hasil scan gambar belum didukung untuk analisis teks.",
              style: TextStyle(
                color: Colors.white60,
                fontSize: 12,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 14),
            GestureDetector(
              onTap: _isLoading ? null : _pickFile,
              child: Container(
                width: double.infinity,
                height: 310,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                    color: _hasSelectedFile
                        ? const Color(0xFF39D2DD)
                        : const Color(0xFF472C86),
                    width: 2,
                    style: BorderStyle.solid,
                  ),
                  color: const Color(0xFF0E0E20),
                ),
                child: _hasSelectedFile ? _buildPreview() : _buildEmptyPicker(),
              ),
            ),
            if (_errorMessage != null) ...[
              const SizedBox(height: 14),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.red.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.red.withValues(alpha: 0.28)),
                ),
                child: Text(
                  _errorMessage!,
                  style: const TextStyle(color: Colors.redAccent, height: 1.4),
                ),
              ),
            ],
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: !_hasSelectedFile || _isLoading
                  ? null
                  : _uploadAndAnalyze,
              style: ElevatedButton.styleFrom(
                backgroundColor: _hasSelectedFile
                    ? const Color(0xFF4338CA)
                    : Colors.white12,
                disabledBackgroundColor: Colors.white12,
                minimumSize: const Size(double.infinity, 60),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(15),
                ),
              ),
              child: Text(
                _isLoading ? "Menganalisis..." : "Unggah & Analisis",
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyPicker() {
    return const Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.cloud_upload_outlined, color: Color(0xFF39D2DD), size: 64),
        SizedBox(height: 15),
        Text(
          "Ketuk untuk memilih foto atau dokumen",
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        SizedBox(height: 8),
        Text(
          "PNG, JPG, JPEG, PDF dokumen teks (max. 15MB)",
          textAlign: TextAlign.center,
          style: TextStyle(color: Colors.white54, fontSize: 12),
        ),
      ],
    );
  }

  Widget _buildPreview() {
    final file = _selectedFile!;
    final extension = file.extension?.toUpperCase() ?? 'FILE';
    final isImage = _isImage;
    final bytes = file.bytes;

    return Column(
      children: [
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: isImage && bytes != null
                ? Image.memory(bytes, width: double.infinity, fit: BoxFit.cover)
                : Container(
                    width: double.infinity,
                    color: const Color(0xFF1D143E),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.picture_as_pdf_outlined,
                          color: Colors.redAccent,
                          size: 72,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          extension,
                          style: const TextStyle(
                            color: Colors.white70,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
          ),
        ),
        const SizedBox(height: 14),
        Row(
          children: [
            Icon(
              isImage ? Icons.image_outlined : Icons.insert_drive_file_outlined,
              color: const Color(0xFF39D2DD),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                file.name,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            IconButton(
              onPressed: () => setState(() {
                _selectedFile = null;
                _errorMessage = null;
              }),
              icon: const Icon(Icons.close, color: Colors.white54),
            ),
          ],
        ),
      ],
    );
  }
}
