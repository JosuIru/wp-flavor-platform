import 'package:shared_preferences/shared_preferences.dart';

class FlavorDraftStore {
  static Future<String> load(String key) async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(key)?.trim() ?? '';
  }

  static Future<void> save(String key, String value) async {
    final prefs = await SharedPreferences.getInstance();
    final text = value.trim();
    if (text.isEmpty) {
      await prefs.remove(key);
      return;
    }
    await prefs.setString(key, text);
  }

  static Future<void> clear(String key) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(key);
  }
}
