import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';

import 'api_exception.dart';

class ApiClient {
  ApiClient({required this.baseUrl, http.Client? httpClient})
    : _httpClient = httpClient ?? http.Client();

  final String baseUrl;
  final http.Client _httpClient;

  Uri _uri(String path) {
    final normalizedBase = baseUrl.endsWith('/')
        ? baseUrl.substring(0, baseUrl.length - 1)
        : baseUrl;
    final normalizedPath = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$normalizedBase$normalizedPath');
  }

  Map<String, String> _headers({String? token}) {
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> getJson(String path, {String? token}) async {
    final response = await _httpClient.get(
      _uri(path),
      headers: _headers(token: token),
    );
    return _decode(response);
  }

  Uri authenticatedUri(String path) => _uri(path);

  Future<Map<String, dynamic>> postJson(
    String path, {
    Map<String, dynamic>? body,
    String? token,
  }) async {
    final response = await _httpClient.post(
      _uri(path),
      headers: _headers(token: token),
      body: jsonEncode(body ?? <String, dynamic>{}),
    );
    return _decode(response);
  }

  Future<void> deleteJson(String path, {String? token}) async {
    final response = await _httpClient.delete(
      _uri(path),
      headers: _headers(token: token),
    );
    _decode(response);
  }

  Future<Map<String, dynamic>> postForm(
    String path, {
    required Map<String, String> body,
    String? token,
  }) async {
    final headers = {
      'Accept': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
    final response = await _httpClient.post(
      _uri(path),
      headers: headers,
      body: body,
    );
    return _decode(response);
  }

  Future<Map<String, dynamic>> multipartFile(
    String path, {
    required String fieldName,
    required String filePath,
    String? token,
  }) async {
    final request = http.MultipartRequest('POST', _uri(path));
    request.headers.addAll({
      'Accept': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    });
    request.files.add(await http.MultipartFile.fromPath(fieldName, filePath));
    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);
    return _decode(response);
  }

  Future<Map<String, dynamic>> multipartFields(
    String path, {
    required Map<String, String> fields,
    String? fieldName,
    String? filePath,
    String? token,
  }) async {
    final request = http.MultipartRequest('POST', _uri(path));
    request.headers.addAll({
      'Accept': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    });
    request.fields.addAll(fields);
    if (fieldName != null && filePath != null && filePath.isNotEmpty) {
      request.files.add(await http.MultipartFile.fromPath(fieldName, filePath));
    }
    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);
    return _decode(response);
  }

  Future<Map<String, dynamic>> multipartBytes(
    String path, {
    required String fieldName,
    required String fileName,
    required Uint8List bytes,
    Map<String, String> fields = const {},
    String? token,
  }) async {
    var response = await _sendMultipartBytes(
      path,
      fieldName: fieldName,
      fileName: fileName,
      bytes: bytes,
      fields: fields,
      token: token,
    );

    if (_isTransientGatewayError(response.statusCode)) {
      await Future<void>.delayed(const Duration(seconds: 8));
      response = await _sendMultipartBytes(
        path,
        fieldName: fieldName,
        fileName: fileName,
        bytes: bytes,
        fields: fields,
        token: token,
      );
    }

    return _decode(response);
  }

  Future<http.Response> _sendMultipartBytes(
    String path, {
    required String fieldName,
    required String fileName,
    required Uint8List bytes,
    Map<String, String> fields = const {},
    String? token,
  }) async {
    final request = http.MultipartRequest('POST', _uri(path));
    request.headers.addAll({
      'Accept': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    });
    request.fields.addAll(fields);
    request.files.add(
      http.MultipartFile.fromBytes(
        fieldName,
        bytes,
        filename: fileName,
        contentType: _mediaTypeFor(fileName),
      ),
    );
    final streamedResponse = await request.send();
    return http.Response.fromStream(streamedResponse);
  }

  bool _isTransientGatewayError(int statusCode) =>
      statusCode == 502 || statusCode == 503 || statusCode == 504;

  Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic> decoded;

    try {
      decoded = response.body.isEmpty
          ? <String, dynamic>{}
          : jsonDecode(response.body) as Map<String, dynamic>;
    } on FormatException {
      final message = switch (response.statusCode) {
        413 =>
          'Ukuran file melebihi batas server. Coba kompres file atau pilih file yang lebih kecil.',
        502 || 503 || 504 =>
          'Layanan analisis sedang bangun atau sibuk. Buka ulang beberapa saat lagi lalu coba analisis kembali.',
        _ => 'Server mengirim respons yang tidak dapat dibaca aplikasi.',
      };
      throw ApiException(message, statusCode: response.statusCode);
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return decoded;
    }

    final validationMessage = _firstValidationMessage(decoded['errors']);
    throw ApiException(
      validationMessage ??
          decoded['message']?.toString() ??
          'Terjadi kesalahan pada server',
      statusCode: response.statusCode,
      errors: decoded['errors'] is Map<String, dynamic>
          ? decoded['errors'] as Map<String, dynamic>
          : null,
    );
  }

  String? _firstValidationMessage(Object? errors) {
    if (errors is! Map<String, dynamic> || errors.isEmpty) {
      return null;
    }

    final first = errors.values.first;
    if (first is List && first.isNotEmpty) {
      return first.first.toString();
    }

    return first?.toString();
  }

  MediaType? _mediaTypeFor(String fileName) {
    final extension = fileName.split('.').last.toLowerCase();

    return switch (extension) {
      'jpg' || 'jpeg' => MediaType('image', 'jpeg'),
      'png' => MediaType('image', 'png'),
      'pdf' => MediaType('application', 'pdf'),
      _ => null,
    };
  }
}
