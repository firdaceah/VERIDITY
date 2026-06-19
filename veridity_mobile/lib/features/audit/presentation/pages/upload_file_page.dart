import 'dart:math';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../../../app/app_dependencies.dart';
import '../../../../core/localization/app_language.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/widgets/analysis_loading_screen.dart';
import '../../../../core/widgets/app_top_snackbar.dart';

class UploadFoto extends StatefulWidget {
  const UploadFoto({super.key});

  @override
  State<UploadFoto> createState() => _UploadFotoState();
}

class _UploadFotoState extends State<UploadFoto> {
  PlatformFile? _selectedFile;
  bool _isLoading = false;
  bool _isCancelling = false;
  bool _cancelRequested = false;
  String? _errorMessage;
  String? _analysisToken;

  bool get _hasSelectedFile =>
      _selectedFile?.bytes != null && _selectedFile!.bytes!.isNotEmpty;

  bool get _isDocument {
    final extension = _selectedFile?.extension?.toLowerCase();
    return extension == 'pdf';
  }

  String get _languageCode =>
      AppDependencies.language.value == AppLocale.id ? 'id' : 'en';

  String _newAnalysisToken() {
    final randomPart = Random.secure().nextInt(1 << 32);
    return '${DateTime.now().microsecondsSinceEpoch}-$randomPart';
  }

  bool get _isImage {
    final extension = _selectedFile?.extension?.toLowerCase();
    return ['jpg', 'jpeg', 'png'].contains(extension);
  }

  String _friendlyUploadError(String message) {
    final lang = AppDependencies.language;
    final normalized = message.toLowerCase();

    if (normalized.contains('image failed to upload') ||
        normalized.contains('file failed to upload') ||
        normalized.contains('failed to upload')) {
      return _isDocument
          ? lang.text(
              'Document upload failed. Make sure the PDF is under 15MB and contains text content, not slides/presentations exported as PDF.',
              'Dokumen gagal diunggah. Pastikan file PDF tidak lebih dari 15MB dan berisi teks dokumen, bukan slide/presentasi yang diekspor menjadi PDF.',
            )
          : lang.text(
              'Photo upload failed. Make sure the JPG/JPEG/PNG file is under 15MB. If the size is valid, try compressing the photo or retry after the server is active.',
              'Foto gagal diunggah. Pastikan file JPG/JPEG/PNG tidak lebih dari 15MB. Jika ukuran sudah benar, coba kompres foto atau ulangi setelah server aktif.',
            );
    }

    if (normalized.contains('server mengirim respons yang tidak dapat dibaca') ||
        normalized.contains('server sent a response')) {
      return _isDocument
          ? lang.text(
              'Document analysis cannot be processed yet. Use a text-based PDF document, not a presentation/slide file exported as PDF.',
              'Analisis dokumen belum dapat diproses. Gunakan PDF dokumen teks, bukan file presentasi/slide yang dijadikan PDF.',
            )
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
    final lang = AppDependencies.language;
    final file = _selectedFile;
    if (file == null || file.bytes == null || file.bytes!.isEmpty) {
      AppTopSnackBar.error(
        context,
        lang.text("Please choose a file first", "Pilih file terlebih dahulu"),
      );
      return;
    }

    final analysisToken = _newAnalysisToken();
    setState(() {
      _analysisToken = analysisToken;
      _cancelRequested = false;
      _isCancelling = false;
      _isLoading = true;
    });

    try {
      final audit = await AppDependencies.auditRepository.uploadFile(
        file,
        languageCode: _languageCode,
        analysisToken: analysisToken,
      );
      if (!mounted) {
        return;
      }
      if (_cancelRequested || _analysisToken != analysisToken) {
        return;
      }
      AppTopSnackBar.success(
        context,
        lang.text("Analysis complete: ", "Analisis selesai: ") +
            lang.auditLabel(audit.summaryLabel),
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
      if (_cancelRequested || _analysisToken != analysisToken) {
        return;
      }
      final message = _friendlyUploadError(e.message);
      setState(() => _errorMessage = message);
      AppTopSnackBar.error(context, message);
    } catch (e) {
      if (!mounted) {
        return;
      }
      if (_cancelRequested || _analysisToken != analysisToken) {
        return;
      }
      final message = _friendlyUploadError(
        lang.text("Failed to upload file: $e", "Gagal mengunggah file: $e"),
      );
      setState(() => _errorMessage = message);
      AppTopSnackBar.error(context, message);
    } finally {
      if (mounted && _analysisToken == analysisToken && !_cancelRequested) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _cancelAnalysis() async {
    final lang = AppDependencies.language;
    final token = _analysisToken;
    if (token == null || token.isEmpty || _isCancelling) {
      return;
    }

    setState(() {
      _isCancelling = true;
      _cancelRequested = true;
    });

    try {
      await AppDependencies.auditRepository.cancelAnalysis(
        analysisToken: token,
        languageCode: _languageCode,
      );
      if (!mounted) {
        return;
      }
      setState(() {
        _isLoading = false;
        _isCancelling = false;
        _analysisToken = null;
      });
      AppTopSnackBar.info(
        context,
        lang.text('Analysis cancelled.', 'Analisis dibatalkan.'),
      );
    } catch (e) {
      if (!mounted) {
        return;
      }
      setState(() {
        _isLoading = false;
        _isCancelling = false;
        _analysisToken = null;
      });
      AppTopSnackBar.info(
        context,
        lang.text(
          'Cancellation requested, but the server response could not be confirmed.',
          'Pembatalan diminta, tetapi respons server belum dapat dipastikan.',
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = AppDependencies.language;

    if (_isLoading) {
      return AnalysisLoadingScreen(
        isDocument: _isDocument,
        fileName: _selectedFile?.name ?? lang.text('Analysis file', 'File analisis'),
        isCancelling: _isCancelling,
        onCancel: _cancelAnalysis,
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
            Text(
              lang.text("Upload File", "Unggah File"),
              style: const TextStyle(
                color: Colors.white,
                fontSize: 32,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 40),
            Text(
              lang.text(
                "Format: JPG, JPEG, PNG, or text-based PDF documents. PDFs exported from PPT/slides or scanned images are not supported for text analysis yet.",
                "Format: JPG, JPEG, PNG, atau PDF dokumen teks. PDF dari PPT/slide atau hasil scan gambar belum didukung untuk analisis teks.",
              ),
              style: const TextStyle(
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
                _isLoading
                    ? lang.text("Analyzing...", "Menganalisis...")
                    : lang.text("Upload & Analyze", "Unggah & Analisis"),
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
    final lang = AppDependencies.language;

    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.cloud_upload_outlined, color: Color(0xFF39D2DD), size: 64),
        const SizedBox(height: 15),
        Text(
          lang.text(
            "Tap to choose a photo or document",
            "Ketuk untuk memilih foto atau dokumen",
          ),
          textAlign: TextAlign.center,
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 8),
        Text(
          lang.text(
            "PNG, JPG, JPEG, text-based PDF (max. 15MB)",
            "PNG, JPG, JPEG, PDF dokumen teks (max. 15MB)",
          ),
          textAlign: TextAlign.center,
          style: const TextStyle(color: Colors.white54, fontSize: 12),
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
