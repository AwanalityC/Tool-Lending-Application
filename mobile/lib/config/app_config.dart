import 'package:flutter/foundation.dart';

class AppConfig {
  AppConfig._();

  /// Override at launch with --dart-define=API_BASE_URL=http://host:8000/api
  static const String _providedBaseUrl = String.fromEnvironment('API_BASE_URL');

  static String get apiBaseUrl {
    if (_providedBaseUrl.trim().isNotEmpty) {
      return '${_providedBaseUrl.trim().replaceAll(RegExp(r'/+$'), '')}/';
    }
    // Chrome runs on the same computer as `php artisan serve` by default.
    if (kIsWeb) return 'http://127.0.0.1:8000/api/';
    // Android emulator uses 10.0.2.2 to reach the host computer.
    return 'http://10.0.2.2:8000/api/';
  }

  /// The Tahap 5 return route was not present in the shared Tahap 4 ZIP.
  /// Set this to the exact route in your backend, e.g. with
  /// --dart-define=RETURN_ENDPOINT_TEMPLATE=/peminjaman/{id}/pengembalian
  /// Keep empty to disable submission instead of guessing a route.
  static const String returnEndpointTemplate =
      String.fromEnvironment('RETURN_ENDPOINT_TEMPLATE');
}
