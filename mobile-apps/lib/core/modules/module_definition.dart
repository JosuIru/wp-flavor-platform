import 'package:flutter/material.dart';

/// Definición de un módulo de la aplicación
///
/// Los módulos son features dinámicas que se activan/desactivan
/// desde WordPress y se registran automáticamente en la app.
class ModuleDefinition {
  /// ID único del módulo (debe coincidir con el ID en WordPress)
  final String id;

  /// Nombre mostrado del módulo
  final String name;

  /// Descripción breve
  final String description;

  /// Icono del módulo
  final IconData icon;

  /// Color principal del módulo
  final Color color;

  /// Categoría del módulo
  final ModuleCategory category;

  /// Si el módulo está activo
  final bool isActive;

  /// Constructor de la pantalla principal del módulo
  final Widget Function(BuildContext context)? screenBuilder;

  /// Constructor de la pantalla de admin del módulo
  final Widget Function(BuildContext context)? adminScreenBuilder;

  /// Endpoints específicos del módulo
  final List<String> endpoints;

  /// Permisos requeridos
  final List<String> requiredPermissions;

  const ModuleDefinition({
    required this.id,
    required this.name,
    required this.description,
    required this.icon,
    required this.color,
    this.category = ModuleCategory.other,
    this.isActive = true,
    this.screenBuilder,
    this.adminScreenBuilder,
    this.endpoints = const [],
    this.requiredPermissions = const [],
  });

  /// Crea un módulo desde los datos de la API
  factory ModuleDefinition.fromApi(Map<String, dynamic> data) {
    final moduleId = data['id']?.toString() ?? '';
    final moduleName = data['name']?.toString() ?? moduleId;
    final moduleDescription = data['description']?.toString() ?? '';
    final isActive = data['active'] == true;

    return ModuleDefinition(
      id: moduleId,
      name: moduleName,
      description: moduleDescription,
      icon: _getIconForModule(moduleId),
      color: _getColorForModule(moduleId),
      category: _getCategoryForModule(moduleId),
      isActive: isActive,
    );
  }

  /// Determina el icono según el ID del módulo
  static IconData _getIconForModule(String moduleId) {
    final iconMap = {
      'grupos-consumo': Icons.shopping_basket_outlined,
      'banco-tiempo': Icons.volunteer_activism,
      'marketplace': Icons.storefront_outlined,
      'eventos': Icons.event,
      'cursos': Icons.school,
      'biblioteca': Icons.library_books,
      'talleres': Icons.construction,
      'bicicletas': Icons.pedal_bike,
      'carpooling': Icons.directions_car,
      'espacios-comunes': Icons.meeting_room,
      'huertos-urbanos': Icons.grass,
      'incidencias': Icons.report_problem,
      'reciclaje': Icons.recycling,
      'podcast': Icons.podcasts,
      'radio': Icons.radio,
      'multimedia': Icons.perm_media,
      'parkings': Icons.local_parking,
      'foros': Icons.forum,
      'chat-grupos': Icons.groups,
      'comunidades': Icons.groups,
      'ayuda-vecinal': Icons.handshake,
      'red-social': Icons.people,
      'woocommerce': Icons.shopping_cart,
    };

    return iconMap[moduleId] ?? Icons.extension;
  }

  /// Determina el color según el ID del módulo
  static Color _getColorForModule(String moduleId) {
    final colorMap = {
      'grupos-consumo': Colors.green,
      'banco-tiempo': Colors.blue,
      'marketplace': Colors.orange,
      'eventos': Colors.purple,
      'cursos': Colors.indigo,
      'biblioteca': Colors.teal,
      'talleres': Colors.brown,
      'bicicletas': Colors.cyan,
      'carpooling': Colors.lightBlue,
      'espacios-comunes': Colors.amber,
      'huertos-urbanos': Colors.lightGreen,
      'incidencias': Colors.red,
      'reciclaje': Colors.green.shade700,
      'podcast': Colors.deepPurple,
      'radio': Colors.pink,
      'multimedia': Colors.deepOrange,
      'parkings': Colors.blueGrey,
      'foros': Colors.indigo.shade300,
      'chat-grupos': Colors.teal.shade400,
      'comunidades': Colors.blue.shade600,
      'ayuda-vecinal': Colors.orange.shade700,
      'red-social': Colors.purple.shade400,
      'woocommerce': Colors.purple.shade600,
    };

    return colorMap[moduleId] ?? Colors.grey;
  }

  /// Determina la categoría según el ID del módulo
  static ModuleCategory _getCategoryForModule(String moduleId) {
    if (['grupos-consumo', 'marketplace', 'woocommerce'].contains(moduleId)) {
      return ModuleCategory.commerce;
    }
    if (['eventos', 'cursos', 'talleres', 'biblioteca'].contains(moduleId)) {
      return ModuleCategory.education;
    }
    if (['chat-grupos', 'foros', 'red-social', 'comunidades'].contains(moduleId)) {
      return ModuleCategory.social;
    }
    if (['banco-tiempo', 'ayuda-vecinal', 'espacios-comunes'].contains(moduleId)) {
      return ModuleCategory.community;
    }
    if (['bicicletas', 'carpooling', 'parkings'].contains(moduleId)) {
      return ModuleCategory.mobility;
    }
    if (['podcast', 'radio', 'multimedia'].contains(moduleId)) {
      return ModuleCategory.media;
    }
    if (['incidencias', 'reciclaje', 'huertos-urbanos'].contains(moduleId)) {
      return ModuleCategory.environment;
    }
    return ModuleCategory.other;
  }

  @override
  String toString() => 'ModuleDefinition(id: $id, name: $name, active: $isActive)';

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ModuleDefinition &&
          runtimeType == other.runtimeType &&
          id == other.id;

  @override
  int get hashCode => id.hashCode;
}

/// Categorías de módulos
enum ModuleCategory {
  commerce('Comercio'),
  education('Educación'),
  social('Social'),
  community('Comunidad'),
  mobility('Movilidad'),
  media('Medios'),
  environment('Medio Ambiente'),
  other('Otros');

  final String label;
  const ModuleCategory(this.label);
}
