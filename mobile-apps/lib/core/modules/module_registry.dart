import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'module_definition.dart';

/// Registro centralizado de módulos
///
/// Gestiona todos los módulos disponibles en la aplicación,
/// permitiendo registro dinámico y acceso desde cualquier parte.
class ModuleRegistry {
  static final ModuleRegistry _instance = ModuleRegistry._internal();
  factory ModuleRegistry() => _instance;
  ModuleRegistry._internal();

  final Map<String, ModuleDefinition> _modules = {};
  final List<void Function()> _listeners = [];

  /// Registra un módulo
  void register(ModuleDefinition module) {
    _modules[module.id] = module;
    _notifyListeners();
    debugPrint('Módulo registrado: ${module.id}');
  }

  /// Registra múltiples módulos
  void registerAll(List<ModuleDefinition> modules) {
    for (final module in modules) {
      _modules[module.id] = module;
    }
    _notifyListeners();
    debugPrint('${modules.length} módulos registrados');
  }

  /// Desregistra un módulo
  void unregister(String moduleId) {
    _modules.remove(moduleId);
    _notifyListeners();
    debugPrint('Módulo desregistrado: $moduleId');
  }

  /// Limpia todos los módulos
  void clear() {
    _modules.clear();
    _notifyListeners();
    debugPrint('Todos los módulos limpiados');
  }

  /// Obtiene un módulo por ID
  ModuleDefinition? getModule(String moduleId) {
    return _modules[moduleId];
  }

  /// Obtiene todos los módulos registrados
  List<ModuleDefinition> getAllModules() {
    return _modules.values.toList();
  }

  /// Obtiene módulos activos
  List<ModuleDefinition> getActiveModules() {
    return _modules.values.where((m) => m.isActive).toList();
  }

  /// Obtiene módulos por categoría
  List<ModuleDefinition> getModulesByCategory(ModuleCategory category) {
    return _modules.values.where((m) => m.category == category).toList();
  }

  /// Verifica si un módulo está registrado
  bool isRegistered(String moduleId) {
    return _modules.containsKey(moduleId);
  }

  /// Verifica si un módulo está activo
  bool isActive(String moduleId) {
    final module = _modules[moduleId];
    return module?.isActive ?? false;
  }

  /// Añade un listener para cambios en el registro
  void addListener(void Function() listener) {
    _listeners.add(listener);
  }

  /// Elimina un listener
  void removeListener(void Function() listener) {
    _listeners.remove(listener);
  }

  /// Notifica a todos los listeners
  void _notifyListeners() {
    for (final listener in _listeners) {
      listener();
    }
  }

  /// Obtiene el conteo de módulos por categoría
  Map<ModuleCategory, int> getModuleCountByCategory() {
    final counts = <ModuleCategory, int>{};
    for (final module in _modules.values) {
      counts[module.category] = (counts[module.category] ?? 0) + 1;
    }
    return counts;
  }

  /// Actualiza módulos desde la API
  Future<void> syncFromApi(List<Map<String, dynamic>> apiModules) async {
    final newModules = apiModules.map((data) => ModuleDefinition.fromApi(data)).toList();

    // Limpiar módulos que ya no están en la API
    final apiModuleIds = newModules.map((m) => m.id).toSet();
    final currentModuleIds = _modules.keys.toSet();
    final toRemove = currentModuleIds.difference(apiModuleIds);

    for (final moduleId in toRemove) {
      _modules.remove(moduleId);
    }

    // Añadir o actualizar módulos
    for (final module in newModules) {
      _modules[module.id] = module;
    }

    _notifyListeners();
    debugPrint('Módulos sincronizados desde API: ${newModules.length}');
  }

  @override
  String toString() {
    return 'ModuleRegistry(total: ${_modules.length}, active: ${getActiveModules().length})';
  }
}

/// Provider de Riverpod para el registro de módulos
///
/// Permite acceder al registro desde cualquier widget con:
/// ```dart
/// final registry = ref.watch(moduleRegistryProvider);
/// ```
final moduleRegistryProvider = Provider<ModuleRegistry>((ref) {
  return ModuleRegistry();
});
