import 'package:dio/dio.dart';

/// Thin Dio wrapper bound to a tenant base URL + bearer token.
///
/// Base URL is the tenant's web address (e.g. https://abcgroup.noovapos.com);
/// all calls hit `<baseUrl>/api/...`.
class ApiClient {
  final Dio dio;

  ApiClient({required String baseUrl, String? token})
      : dio = Dio(
          BaseOptions(
            baseUrl: '${baseUrl.replaceAll(RegExp(r'/$'), '')}/api',
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 20),
            headers: {
              'Accept': 'application/json',
              if (token != null && token.isNotEmpty)
                'Authorization': 'Bearer $token',
            },
          ),
        );

  /// Unwraps the standard `{ success, data, message }` envelope.
  static dynamic data(Response res) {
    final body = res.data;
    if (body is Map && body.containsKey('data')) return body['data'];
    return body;
  }

  static String errorMessage(Object e) {
    if (e is DioException) {
      final d = e.response?.data;
      if (d is Map && d['message'] != null) return d['message'].toString();
      return e.message ?? 'Network error';
    }
    return e.toString();
  }
}
