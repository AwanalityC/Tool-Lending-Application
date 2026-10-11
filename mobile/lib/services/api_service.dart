import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../config/app_config.dart';

class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode, this.errors});
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  @override
  String toString() => message;
}

class ApiService {
  ApiService() {
    _dio = Dio(BaseOptions(
      baseUrl: AppConfig.apiBaseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 20),
      sendTimeout: const Duration(seconds: 20),
      headers: const {'Accept': 'application/json', 'Content-Type': 'application/json'},
    ));
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: _tokenKey);
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
    ));
  }

  static const String _tokenKey = 'pinjamalat_sanctum_token';
  static const FlutterSecureStorage _storage = FlutterSecureStorage();
  late final Dio _dio;

  Future<String?> readToken() => _storage.read(key: _tokenKey);
  Future<void> saveToken(String token) => _storage.write(key: _tokenKey, value: token);
  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  Future<dynamic> get(String path, {Map<String, dynamic>? query}) async {
    try {
      final response = await _dio.get(_relativePath(path), queryParameters: query);
      return response.data;
    } on DioException catch (error) {
      throw _toApiException(error);
    }
  }

  Future<dynamic> post(String path, {Object? data}) async {
    try {
      final response = await _dio.post(_relativePath(path), data: data);
      return response.data;
    } on DioException catch (error) {
      throw _toApiException(error);
    }
  }

  Future<dynamic> put(String path, {Object? data}) async {
    try {
      final response = await _dio.put(_relativePath(path), data: data);
      return response.data;
    } on DioException catch (error) {
      throw _toApiException(error);
    }
  }

  Future<dynamic> delete(String path) async {
    try {
      final response = await _dio.delete(_relativePath(path));
      return response.data;
    } on DioException catch (error) {
      throw _toApiException(error);
    }
  }

  String _relativePath(String path) => path.replaceFirst(RegExp(r'^/+'), '');

  ApiException _toApiException(DioException error) {
    final status = error.response?.statusCode;
    final body = error.response?.data;
    String message;
    Map<String, dynamic>? errors;
    if (body is Map) {
      message = (body['pesan'] ?? body['message'] ?? body['error'] ?? '').toString();
      if (body['errors'] is Map) errors = Map<String, dynamic>.from(body['errors'] as Map);
      if (message.isEmpty && errors != null) {
        message = errors.values
            .expand((value) => value is List ? value : [value])
            .map((value) => value.toString())
            .join('\n');
      }
    } else {
      message = '';
    }
    // Laravel may return a generic `pesan` alongside the useful per-field
    // validation details in `errors`. Always surface those details for 422s,
    // otherwise users only see "Data yang dikirim tidak valid".
    if (status == 422 && errors != null && errors.isNotEmpty) {
      final details = errors.entries.map((entry) {
        final raw = entry.value;
        final detail = raw is List ? raw.join(', ') : raw.toString();
        return '${entry.key}: $detail';
      }).join('\n');
      final normalizedMessage = message.trim().toLowerCase();
      if (normalizedMessage.isEmpty ||
          normalizedMessage == 'data yang dikirim tidak valid' ||
          normalizedMessage == 'the given data was invalid.') {
        message = 'Validasi gagal:\n$details';
      } else {
        message = '$message\n$details';
      }
    }

    if (message.isEmpty) {
      if (error.type == DioExceptionType.connectionTimeout ||
          error.type == DioExceptionType.receiveTimeout ||
          error.type == DioExceptionType.connectionError) {
        message = 'Tidak dapat terhubung ke server. Periksa alamat API dan pastikan Laravel sedang berjalan.';
      } else if (status == 401) {
        message = 'Sesi tidak valid atau sudah berakhir. Silakan login kembali.';
      } else if (status == 403) {
        message = 'Akun ini tidak memiliki izin untuk melakukan tindakan tersebut.';
      } else if (status == 404) {
        message = 'Data atau alamat API tidak ditemukan (404).';
      } else if (status == 409) {
        message = 'Terjadi konflik data atau stok. Muat ulang lalu coba lagi.';
      } else if (status == 422) {
        message = 'Data belum valid. Periksa kembali isian formulir.';
      } else {
        message = error.message ?? 'Permintaan ke server gagal.';
      }
    }
    return ApiException(message, statusCode: status, errors: errors);
  }

  static Map<String, dynamic> unwrapMap(dynamic response) {
    if (response is Map) {
      final map = Map<String, dynamic>.from(response);
      final data = map['data'];
      if (data is Map) return Map<String, dynamic>.from(data);
      return map;
    }
    return <String, dynamic>{};
  }

  static List<Map<String, dynamic>> unwrapList(dynamic response) {
    dynamic data = response;
    if (response is Map && response['data'] is List) data = response['data'];
    if (data is! List) return <Map<String, dynamic>>[];
    return data.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
  }

  static String messageFrom(dynamic response, {String fallback = 'Berhasil.'}) {
    if (response is Map) {
      return (response['pesan'] ?? response['message'] ?? fallback).toString();
    }
    return fallback;
  }
}
