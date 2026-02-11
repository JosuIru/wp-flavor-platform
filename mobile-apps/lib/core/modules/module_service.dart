import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../api/api_client.dart';
import '../providers/providers.dart';
import 'module_definition.dart';
import 'module_registry.dart';

/// Servicio para gestionar la sincronización de módulos
///
/// Carga módulos desde la API, los cachea localmente y
/// sincroniza el registro de módulos.
class ModuleService {
  final ApiClient _apiClient;
  final ModuleRegistry _registry;

  static const String _cacheKey = 'modules_cache';
  static const String _cacheTimeKey = 'modules_cache_time';
  static const Duration _cacheDuration = Duration(hours: 1);

  ModuleService(this._apiClient, this._registry);

  /// Inicializa los módulos
  ///
  /// 1. Intenta cargar desde caché
  /// 2. Si el caché está vencido o no existe, consulta la API
  /// 3. Actualiza el registro
  Future<void> initialize() async {
    debugPrint('Inicializando módulos...');

    // Intentar cargar desde caché
    final cachedModules = await _loadFromCache();
    if (cachedModules != null) {
      _registry.registerAll(cachedModules);
      debugPrint('Módulos cargados desde caché: ${cachedModules.length}');

      // Si el caché no está vencido, terminar
      if (!await _isCacheExpired()) {
        return;
      }
    }

    // Sincronizar desde la API
    await sync();
  }

  /// Sincroniza módulos desde la API
  Future<void> sync() async {
    try {
      debugPrint('Sincronizando módulos desde API...');
      final response = await _apiClient.getAvailableModules();

      if (!response.success || response.data == null) {
        debugPrint('Error al sincronizar módulos: ${response.error}');
        return;
      }

      final modulesList = (response.data!['modules'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .toList();

      // Actualizar registro
      await _registry.syncFromApi(modulesList);

      // Guardar en caché
      await _saveToCache(modulesList);

      debugPrint('Módulos sincronizados: ${modulesList.length}');
    } catch (e) {
      debugPrint('Error al sincronizar módulos: $e');
    }
  }

  /// Carga módulos desde el caché local
  Future<List<ModuleDefinition>?> _loadFromCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedData = prefs.getString(_cacheKey);

      if (cachedData == null) {
        return null;
      }

      final modulesList = (json.decode(cachedData) as List<dynamic>)
          .whereType<Map<String, dynamic>>()
          .toList();

      return modulesList.map((data) => ModuleDefinition.fromApi(data)).toList();
    } catch (e) {
      debugPrint('Error al cargar módulos desde caché: $e');
      return null;
    }
  }

  /// Guarda módulos en el caché local
  Future<void> _saveToCache(List<Map<String, dynamic>> modules) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_cacheKey, json.encode(modules));
      await prefs.setInt(_cacheTimeKey, DateTime.now().millisecondsSinceEpoch);
    } catch (e) {
      debugPrint('Error al guardar módulos en caché: $e');
    }
  }

  /// Verifica si el caché está vencido
  Future<bool> _isCacheExpired() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cacheTime = prefs.getInt(_cacheTimeKey);

      if (cacheTime == null) {
        return true;
      }

      final cachedDate = DateTime.fromMillisecondsSinceEpoch(cacheTime);
      final now = DateTime.now();

      return now.difference(cachedDate) > _cacheDuration;
    } catch (e) {
      return true;
    }
  }

  /// Limpia el caché de módulos
  Future<void> clearCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_cacheKey);
      await prefs.remove(_cacheTimeKey);
      debugPrint('Caché de módulos limpiado');
    } catch (e) {
      debugPrint('Error al limpiar caché de módulos: $e');
    }
  }

  /// Obtiene información detallada de un módulo desde la API
  Future<Map<String, dynamic>?> getModuleDetails(String moduleId) async {
    try {
      final response = await _apiClient.getModuleInfo(moduleId);

      if (response.success && response.data != null) {
        return response.data;
      }

      return null;
    } catch (e) {
      debugPrint('Error al obtener detalles del módulo $moduleId: $e');
      return null;
    }
  }

  /// Ejecuta una acción de un módulo
  Future<Map<String, dynamic>?> executeAction({
    required String moduleId,
    required String actionName,
    Map<String, dynamic>? params,
  }) async {
    try {
      final response = await _apiClient.executeModuleAction(
        moduleId: moduleId,
        actionName: actionName,
        params: params,
      );

      if (response.success && response.data != null) {
        return response.data;
      }

      return null;
    } catch (e) {
      debugPrint('Error al ejecutar acción $actionName en módulo $moduleId: $e');
      return null;
    }
  }
}

/// Provider de Riverpod para el servicio de módulos
final moduleServiceProvider = Provider<ModuleService>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  final registry = ref.watch(moduleRegistryProvider);
  return ModuleService(apiClient, registry);
});

/// Provider para la inicialización de módulos
final moduleInitializationProvider = FutureProvider<void>((ref) async {
  final moduleService = ref.watch(moduleServiceProvider);
  await moduleService.initialize();
});
