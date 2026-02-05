import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/system_info.dart';
import '../config/api_config.dart';

/// Servicio para detectar qué plugins están activos en un sitio WordPress
class PluginDetectorService {
  final String baseUrl;

  PluginDetectorService({String? baseUrl})
      : baseUrl = baseUrl ?? ApiConfig.baseUrl;

  /// Detecta los plugins activos en el sitio
  Future<SystemInfo?> detectPlugins() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/wp-json/app-discovery/v1/info'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return SystemInfo.fromJson(data);
      }

      return null;
    } catch (e) {
      print('Error en detectPlugins: $e');
      return null;
    }
  }

  /// Verifica si el sitio tiene Flavor Chat IA activo
  Future<bool> hasFlavorChatIA() async {
    final info = await detectPlugins();
    if (info == null) return false;

    return info.activeSystems.any((s) => s['id'] == 'flavor-chat-ia');
  }

  /// Verifica si el sitio tiene wp-calendario-experiencias activo
  Future<bool> hasCalendarioExperiencias() async {
    final info = await detectPlugins();
    if (info == null) return false;

    return info.activeSystems.any((s) => s['id'] == 'calendario-experiencias');
  }

  /// Obtiene la lista de módulos disponibles
  Future<List<Map<String, dynamic>>> getAvailableModules() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/wp-json/app-discovery/v1/modules'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['success'] == true && data['modules'] != null) {
          return List<Map<String, dynamic>>.from(data['modules']);
        }
      }

      return [];
    } catch (e) {
      print('Error en getAvailableModules: $e');
      return [];
    }
  }

  /// Verifica si un módulo específico está disponible
  Future<bool> hasModule(String moduleId) async {
    final modules = await getAvailableModules();
    return modules.any((m) => m['id'] == moduleId);
  }
}
