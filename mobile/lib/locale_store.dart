import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _localeKey = 'cms.locale';

/// Which language the reader picked, remembered between launches.
class LocaleStore extends ChangeNotifier {
  String _locale = 'en';

  String get locale => _locale;
  bool get isRtl => _locale == 'ar';

  Future<void> restore() async {
    final store = await SharedPreferences.getInstance();
    final saved = store.getString(_localeKey);

    if (saved == 'ar' || saved == 'en') {
      _locale = saved!;
      notifyListeners();
    }
  }

  Future<void> use(String next) async {
    if (next == _locale) {
      return;
    }

    _locale = next;
    notifyListeners();

    final store = await SharedPreferences.getInstance();
    await store.setString(_localeKey, next);
  }
}
