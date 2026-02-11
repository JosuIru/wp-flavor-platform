import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../module_definition.dart';

/// Gestor avanzado de caché de módulos
///
/// Soporta múltiples estrategias de invalidación:
/// - Por tiempo (TTL)
/// - Por evento
/// - Manual
/// - Por versión de la API
class ModuleCacheManager {
  static const String _cacheKey = 'modules_cache_v2';
  static const String _cacheTimeKey = 'modules_cache_time_v2';
  static const String _cacheVersionKey = 'modules_cache_version';
  static const String _cacheHashKey = 'modules_cache_hash';

  static const Duration _defaultTTL = Duration(hours: 1);
  static const Duration _shortTTL = Duration(minutes: 15);
  static const Duration _longTTL = Duration(hours: 24);

  final Duration _ttl;

  ModuleCacheManager({Duration? ttl}) : _ttl = ttl ?? _defaultTTL;

  /// Guarda módulos en caché con metadatos
  Future<void> save(
    List<Map<String, dynamic>> modules, {
    String? version,
    Map<String, dynamic>? metadata,
  }) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final now = DateTime.now().millisecondsSinceEpoch;
      final hash = _generateHash(modules);

      await Future.wait([
        prefs.setString(_cacheKey, json.encode(modules)),
        prefs.setInt(_cacheTimeKey, now),
        if (version != null) prefs.setString(_cacheVersionKey, version),
        prefs.setString(_cacheHashKey, hash),
      ]);

      debugPrint('✅ Módulos guardados en caché: ${modules.length} (hash: $hash)');
    } catch (e) {
      debugPrint('❌ Error al guardar caché de módulos: $e');
    }
  }

  /// Carga módulos desde caché
  Future<CachedModules?> load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedData = prefs.getString(_cacheKey);

      if (cachedData == null) {
        debugPrint('⚠️ No hay caché disponible');
        return null;
      }

      final cacheTime = prefs.getInt(_cacheTimeKey) ?? 0;
      final version = prefs.getString(_cacheVersionKey);
      final hash = prefs.getString(_cacheHashKey);

      final modulesList = (json.decode(cachedData) as List<dynamic>)
          .whereType<Map<String, dynamic>>()
          .toList();

      final cachedDate = DateTime.fromMillisecondsSinceEpoch(cacheTime);
      final age = DateTime.now().difference(cachedDate);

      debugPrint('📦 Caché cargado: ${modulesList.length} módulos (edad: ${age.inMinutes}min)');

      return CachedModules(
        modules: modulesList,
        cachedAt: cachedDate,
        version: version,
        hash: hash,
        age: age,
      );
    } catch (e) {
      debugPrint('❌ Error al cargar caché: $e');
      return null;
    }
  }

  /// Verifica si el caché es válido
  Future<bool> isValid() async {
    final cached = await load();
    if (cached == null) return false;

    // Verificar TTL
    if (cached.age > _ttl) {
      debugPrint('⏱️ Caché expirado (${cached.age.inMinutes}min > ${_ttl.inMinutes}min)');
      return false;
    }

    return true;
  }

  /// Invalida el caché
  Future<void> invalidate({InvalidationReason? reason}) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await Future.wait([
        prefs.remove(_cacheKey),
        prefs.remove(_cacheTimeKey),
        prefs.remove(_cacheVersionKey),
        prefs.remove(_cacheHashKey),
      ]);

      debugPrint('🗑️ Caché invalidado: ${reason?.toString() ?? "manual"}');
    } catch (e) {
      debugPrint('❌ Error al invalidar caché: $e');
    }
  }

  /// Actualiza parcialmente el caché (solo ciertos módulos)
  Future<void> updatePartial(List<Map<String, dynamic>> updatedModules) async {
    final cached = await load();
    if (cached == null) {
      await save(updatedModules);
      return;
    }

    // Merge de módulos
    final modulesMap = {
      for (var m in cached.modules) m['id'].toString(): m,
    };

    for (var updated in updatedModules) {
      modulesMap[updated['id'].toString()] = updated;
    }

    await save(modulesMap.values.toList());
    debugPrint('🔄 Caché actualizado parcialmente: ${updatedModules.length} módulos');
  }

  /// Genera hash de los módulos para detectar cambios
  String _generateHash(List<Map<String, dynamic>> modules) {
    final ids = modules.map((m) => m['id']).join(',');
    final activeStates = modules.map((m) => m['active']).join(',');
    return '${ids.hashCode}_${activeStates.hashCode}';
  }

  /// Verifica si los módulos han cambiado comparando hash
  Future<bool> hasChanged(List<Map<String, dynamic>> newModules) async {
    final cached = await load();
    if (cached == null) return true;

    final newHash = _generateHash(newModules);
    return cached.hash != newHash;
  }

  /// Obtiene el tiempo restante antes de expiración
  Future<Duration?> getTimeUntilExpiration() async {
    final cached = await load();
    if (cached == null) return null;

    final remaining = _ttl - cached.age;
    return remaining.isNegative ? Duration.zero : remaining;
  }

  /// Estadísticas del caché
  Future<CacheStats> getStats() async {
    final cached = await load();
    if (cached == null) {
      return CacheStats(
        exists: false,
        moduleCount: 0,
        age: Duration.zero,
        isValid: false,
      );
    }

    return CacheStats(
      exists: true,
      moduleCount: cached.modules.length,
      age: cached.age,
      isValid: cached.age <= _ttl,
      version: cached.version,
      expiresIn: await getTimeUntilExpiration(),
    );
  }
}

/// Módulos cacheados con metadatos
class CachedModules {
  final List<Map<String, dynamic>> modules;
  final DateTime cachedAt;
  final String? version;
  final String? hash;
  final Duration age;

  const CachedModules({
    required this.modules,
    required this.cachedAt,
    this.version,
    this.hash,
    required this.age,
  });

  List<ModuleDefinition> toModuleDefinitions() {
    return modules.map((data) => ModuleDefinition.fromApi(data)).toList();
  }
}

/// Estadísticas del caché
class CacheStats {
  final bool exists;
  final int moduleCount;
  final Duration age;
  final bool isValid;
  final String? version;
  final Duration? expiresIn;

  const CacheStats({
    required this.exists,
    required this.moduleCount,
    required this.age,
    required this.isValid,
    this.version,
    this.expiresIn,
  });

  @override
  String toString() {
    if (!exists) return 'CacheStats(no existe)';
    return 'CacheStats(módulos: $moduleCount, edad: ${age.inMinutes}min, válido: $isValid, expira en: ${expiresIn?.inMinutes ?? 0}min)';
  }
}

/// Razones de invalidación del caché
enum InvalidationReason {
  manual,
  expired,
  apiUpdate,
  userLogout,
  forcedRefresh,
  versionChange,
}

/// Estrategia de caché
enum CacheStrategy {
  /// Usar caché si está disponible, actualizar en segundo plano
  cacheFirst,

  /// Consultar API primero, usar caché como fallback
  networkFirst,

  /// Usar caché hasta que expire, luego consultar API
  cacheUntilExpired,

  /// Siempre consultar API, actualizar caché
  networkOnly,
}
