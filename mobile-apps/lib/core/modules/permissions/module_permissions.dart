import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../module_definition.dart';

/// Sistema de permisos para módulos
///
/// Controla el acceso a módulos y sus acciones basado en roles y permisos
class ModulePermissions {
  static final ModulePermissions _instance = ModulePermissions._internal();
  factory ModulePermissions() => _instance;
  ModulePermissions._internal();

  // Permisos actuales del usuario
  Map<String, List<String>> _userPermissions = {};
  String _userRole = 'guest';

  /// Inicializa permisos desde configuración
  void initialize({
    required Map<String, List<String>> permissions,
    required String userRole,
  }) {
    _userPermissions = permissions;
    _userRole = userRole;
    debugPrint('Permisos inicializados para rol: $userRole');
  }

  /// Verifica si el usuario puede acceder a un módulo
  bool canAccessModule(String moduleId) {
    // Admin tiene acceso a todo
    if (_userRole == 'admin' || _userRole == 'administrator') {
      return true;
    }

    // Verificar permisos específicos
    final modulePerms = _userPermissions[moduleId];
    if (modulePerms == null) {
      return _hasDefaultAccess(moduleId);
    }

    return modulePerms.contains('access') || modulePerms.contains('*');
  }

  /// Verifica si el usuario puede ejecutar una acción en un módulo
  bool canExecuteAction(String moduleId, String action) {
    // Admin puede todo
    if (_userRole == 'admin' || _userRole == 'administrator') {
      return true;
    }

    final modulePerms = _userPermissions[moduleId];
    if (modulePerms == null) return false;

    return modulePerms.contains(action) || modulePerms.contains('*');
  }

  /// Verifica permisos múltiples
  bool hasAllPermissions(String moduleId, List<String> actions) {
    return actions.every((action) => canExecuteAction(moduleId, action));
  }

  /// Verifica si tiene al menos uno de los permisos
  bool hasAnyPermission(String moduleId, List<String> actions) {
    return actions.any((action) => canExecuteAction(moduleId, action));
  }

  /// Obtiene módulos accesibles para el usuario
  List<ModuleDefinition> getAccessibleModules(List<ModuleDefinition> allModules) {
    return allModules.where((m) => canAccessModule(m.id)).toList();
  }

  /// Filtra acciones permitidas para un módulo
  List<String> getAllowedActions(String moduleId, List<String> allActions) {
    return allActions.where((action) => canExecuteAction(moduleId, action)).toList();
  }

  /// Determina si tiene acceso por defecto según rol
  bool _hasDefaultAccess(String moduleId) {
    // Módulos públicos
    const publicModules = [
      'eventos',
      'marketplace',
      'foros',
    ];

    if (publicModules.contains(moduleId)) {
      return true;
    }

    // Roles con acceso limitado
    if (_userRole == 'subscriber' || _userRole == 'guest') {
      return publicModules.contains(moduleId);
    }

    // Otros roles tienen acceso por defecto
    return true;
  }

  /// Permisos comunes predefinidos
  static const viewPermission = 'view';
  static const createPermission = 'create';
  static const editPermission = 'edit';
  static const deletePermission = 'delete';
  static const managePermission = 'manage';
  static const allPermissions = '*';

  /// Niveles de acceso por rol
  static Map<String, List<String>> getDefaultPermissionsForRole(String role) {
    switch (role.toLowerCase()) {
      case 'admin':
      case 'administrator':
        return {'*': [allPermissions]};

      case 'editor':
        return {
          '*': [viewPermission, createPermission, editPermission],
        };

      case 'author':
        return {
          '*': [viewPermission, createPermission],
        };

      case 'contributor':
        return {
          '*': [viewPermission],
        };

      default:
        return {};
    }
  }
}

/// Provider de Riverpod para permisos
final modulePermissionsProvider = Provider<ModulePermissions>((ref) {
  return ModulePermissions();
});

/// Extensión para facilitar verificación de permisos en widgets
extension ModulePermissionsX on WidgetRef {
  bool canAccessModule(String moduleId) {
    return read(modulePermissionsProvider).canAccessModule(moduleId);
  }

  bool canExecuteAction(String moduleId, String action) {
    return read(modulePermissionsProvider).canExecuteAction(moduleId, action);
  }
}
