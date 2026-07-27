import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_client.dart';
import 'models.dart';

const _tokenKey = 'cms.token';
const _userKey = 'cms.user';

/// Holds the Sanctum token and the signed in user, and keeps both on the device
/// so the app opens straight into the content next time.
class Session extends ChangeNotifier {
  Session({required this.api});

  final ApiClient api;

  String? _token;
  ApiUser? _user;
  bool _restoring = true;

  ApiUser? get user => _user;
  bool get signedIn => _token != null && _user != null;
  bool get restoring => _restoring;

  Future<void> restore() async {
    final store = await SharedPreferences.getInstance();
    final token = store.getString(_tokenKey);
    final user = store.getString(_userKey);

    if (token != null && user != null) {
      _token = token;
      _user = ApiUser.fromJson(jsonDecode(user) as Map<String, dynamic>);
      api.token = token;

      // The stored token may have been revoked since. Checking now avoids
      // dropping the user into a screen that cannot load anything.
      try {
        _user = await api.me();
        await _persist();
      } on ApiException catch (error) {
        if (error.status == 401) {
          await _forget();
        }
      } catch (_) {
        // Offline or the API is down: keep what was cached and let the screens
        // surface the failure themselves.
      }
    }

    _restoring = false;
    notifyListeners();
  }

  Future<void> signIn(String email, String password) async {
    final result = await api.login(email, password);

    _token = result.token;
    _user = result.user;
    api.token = result.token;

    await _persist();
    notifyListeners();
  }

  Future<void> signOut() async {
    try {
      await api.logout();
    } catch (_) {
      // Revoking server side is best effort; the local token goes either way.
    }

    await _forget();
    notifyListeners();
  }

  Future<void> _persist() async {
    final store = await SharedPreferences.getInstance();
    await store.setString(_tokenKey, _token!);
    await store.setString(_userKey, jsonEncode(_user!.toJson()));
  }

  Future<void> _forget() async {
    _token = null;
    _user = null;
    api.token = null;

    final store = await SharedPreferences.getInstance();
    await store.remove(_tokenKey);
    await store.remove(_userKey);
  }
}
