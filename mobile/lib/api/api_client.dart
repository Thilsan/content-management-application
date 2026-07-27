import 'dart:convert';

import 'package:http/http.dart' as http;

import 'models.dart';

/// Raised for any non 2xx response. [errors] carries the field messages Laravel
/// returns on a 422 so the login form can put them next to the right input.
class ApiException implements Exception {
  ApiException(this.status, this.message, [this.errors = const {}]);

  final int status;
  final String message;
  final Map<String, List<String>> errors;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({required this.baseUrl, http.Client? client})
    : _client = client ?? http.Client();

  /// Where the Laravel API lives. Overridden at build time with
  /// --dart-define=API_URL=..., which is how the Android emulator reaches the
  /// host machine (10.0.2.2) rather than itself.
  static const defaultBaseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://localhost:8000',
  );

  final String baseUrl;
  final http.Client _client;

  String? _token;

  set token(String? value) => _token = value;

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Map<String, dynamic>? body,
    Map<String, String>? query,
  }) async {
    final uri = Uri.parse('$baseUrl/api$path').replace(queryParameters: query);

    final request = http.Request(method, uri)
      ..headers['Accept'] = 'application/json';

    if (_token != null) {
      request.headers['Authorization'] = 'Bearer $_token';
    }

    if (body != null) {
      request.headers['Content-Type'] = 'application/json';
      request.body = jsonEncode(body);
    }

    final response = await http.Response.fromStream(await _client.send(request));

    final decoded = response.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode >= 400) {
      throw ApiException(
        response.statusCode,
        (decoded['message'] as String?) ?? 'The request failed.',
        _fieldErrors(decoded['errors']),
      );
    }

    return decoded;
  }

  Map<String, List<String>> _fieldErrors(Object? raw) {
    if (raw is! Map) return const {};

    return raw.map(
      (field, messages) =>
          MapEntry(field as String, (messages as List).cast<String>()),
    );
  }

  /// Returns the signed in user and the token to send on later requests.
  Future<({String token, ApiUser user})> login(
    String email,
    String password,
  ) async {
    final response = await _send(
      'POST',
      '/auth/login',
      body: {
        'email': email,
        'password': password,
        'device_name': 'mobile',
      },
    );

    return (
      token: response['token'] as String,
      user: ApiUser.fromJson(response['data'] as Map<String, dynamic>),
    );
  }

  Future<ApiUser> me() async {
    final response = await _send('GET', '/auth/me');

    return ApiUser.fromJson(response['data'] as Map<String, dynamic>);
  }

  Future<void> logout() => _send('POST', '/auth/logout');

  Future<List<MenuNode>> menus() async {
    final response = await _send('GET', '/menus');

    return (response['data'] as List)
        .map((node) => MenuNode.fromJson(node as Map<String, dynamic>))
        .toList();
  }

  Future<List<CmsPage>> pages({String? search, int perPage = 100}) async {
    final response = await _send(
      'GET',
      '/pages',
      query: {
        'per_page': '$perPage',
        if (search != null && search.isNotEmpty) 'search': search,
      },
    );

    return (response['data'] as List)
        .map((page) => CmsPage.fromJson(page as Map<String, dynamic>))
        .toList();
  }

  Future<CmsPage> page(int id) async {
    final response = await _send('GET', '/pages/$id');

    return CmsPage.fromJson(response['data'] as Map<String, dynamic>);
  }
}
