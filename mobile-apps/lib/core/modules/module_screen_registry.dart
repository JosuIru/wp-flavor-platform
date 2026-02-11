import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'lazy/module_lazy_loader.dart';
import 'builders/module_screen_builder.dart';
import 'module_definition.dart';

// Importar TODAS las pantallas implementadas
import '../../features/modules/grupos_consumo/grupos_consumo_screen.dart';
import '../../features/modules/banco_tiempo/banco_tiempo_screen.dart';
import '../../features/modules/marketplace/marketplace_screen.dart';
import '../../features/modules/eventos/eventos_screen.dart';
import '../../features/modules/socios/socios_screen.dart';
import '../../features/modules/facturas/facturas_screen.dart';
import '../../features/modules/chat_grupos/chat_grupos_screen.dart';
import '../../features/modules/chat_interno/chat_interno_screen.dart';
import '../../features/modules/incidencias/incidencias_screen.dart';
import '../../features/modules/cursos/cursos_screen.dart';
import '../../features/modules/biblioteca/biblioteca_screen.dart';
import '../../features/modules/espacios_comunes/espacios_comunes_screen.dart';
import '../../features/modules/talleres/talleres_screen.dart';
import '../../features/modules/tramites/tramites_screen.dart';
import '../../features/modules/huertos_urbanos/huertos_urbanos_screen.dart';
import '../../features/modules/reciclaje/reciclaje_screen.dart';
import '../../features/modules/bicicletas_compartidas/bicicletas_compartidas_screen.dart';
import '../../features/modules/parkings/parkings_screen.dart';
import '../../features/modules/avisos_municipales/avisos_municipales_screen.dart';
import '../../features/modules/ayuda_vecinal/ayuda_vecinal_screen.dart';
import '../../features/modules/module_placeholder_screen.dart';

/// Registro centralizado de pantallas de módulos
class ModuleScreenRegistry {
  static final ModuleScreenRegistry _instance = ModuleScreenRegistry._internal();
  factory ModuleScreenRegistry() => _instance;
  ModuleScreenRegistry._internal();

  /// Registra todas las pantallas de módulos en el lazy loader
  void registerAllScreens() {
    final loader = ModuleLazyLoader();

    // TODOS los módulos con pantallas implementadas
    loader.registerScreenBuilder('grupos-consumo', (_) => const GruposConsumoScreen());
    loader.registerScreenBuilder('banco-tiempo', (_) => const BancoTiempoScreen());
    loader.registerScreenBuilder('marketplace', (_) => const MarketplaceScreen());
    loader.registerScreenBuilder('eventos', (_) => const EventosScreen());
    loader.registerScreenBuilder('socios', (_) => const SociosScreen());
    loader.registerScreenBuilder('facturas', (_) => const FacturasScreen());
    loader.registerScreenBuilder('chat-grupos', (_) => const ChatGruposScreen());
    loader.registerScreenBuilder('chat-interno', (_) => const ChatInternoScreen());
    loader.registerScreenBuilder('incidencias', (_) => const IncidenciasScreen());
    loader.registerScreenBuilder('cursos', (_) => const CursosScreen());
    loader.registerScreenBuilder('biblioteca', (_) => const BibliotecaScreen());
    loader.registerScreenBuilder('espacios-comunes', (_) => const EspaciosComunesScreen());
    loader.registerScreenBuilder('talleres', (_) => const TalleresScreen());
    loader.registerScreenBuilder('tramites', (_) => const TramitesScreen());
    loader.registerScreenBuilder('huertos-urbanos', (_) => const HuertosUrbanosScreen());
    loader.registerScreenBuilder('reciclaje', (_) => const ReciclajeScreen());
    loader.registerScreenBuilder('bicicletas-compartidas', (_) => const BicicletasCompartidasScreen());
    loader.registerScreenBuilder('parkings', (_) => const ParkingsScreen());
    loader.registerScreenBuilder('avisos-municipales', (_) => const AvisosMunicipalesScreen());
    loader.registerScreenBuilder('ayuda-vecinal', (_) => const AyudaVecinalScreen());

    // Variantes con guion bajo (compatibilidad)
    loader.registerScreenBuilder('grupos_consumo', (_) => const GruposConsumoScreen());
    loader.registerScreenBuilder('banco_tiempo', (_) => const BancoTiempoScreen());
    loader.registerScreenBuilder('chat_grupos', (_) => const ChatGruposScreen());
    loader.registerScreenBuilder('chat_interno', (_) => const ChatInternoScreen());
    loader.registerScreenBuilder('espacios_comunes', (_) => const EspaciosComunesScreen());
    loader.registerScreenBuilder('huertos_urbanos', (_) => const HuertosUrbanosScreen());
    loader.registerScreenBuilder('bicicletas_compartidas', (_) => const BicicletasCompartidasScreen());
    loader.registerScreenBuilder('avisos_municipales', (_) => const AvisosMunicipalesScreen());
    loader.registerScreenBuilder('ayuda_vecinal', (_) => const AyudaVecinalScreen());

    debugPrint('✅ Registradas ${loader.loadedCount} pantallas de módulos');
  }

  /// Obtiene la pantalla para un módulo (implementada o genérica)
  Widget getScreenForModule(
    BuildContext context,
    ModuleDefinition module, {
    String? fallbackTitle,
    String? fallbackDescription,
  }) {
    final loader = ModuleLazyLoader();

    // Intentar obtener pantalla registrada
    final builder = loader.getScreenBuilder(module.id);
    if (builder != null) {
      debugPrint('📱 Usando pantalla implementada para: ${module.id}');
      return builder(context);
    }

    // Si no hay pantalla implementada, verificar si el módulo está activo
    if (!module.isActive) {
      return ModulePlaceholderScreen(
        title: module.name,
        description: 'Este módulo está desactivado',
      );
    }

    // Para módulos activos sin pantalla, usar el builder genérico
    debugPrint('🔨 Generando pantalla genérica para: ${module.id}');
    return ModuleScreenBuilder.buildGenericScreen(
      context,
      module,
      type: _getScreenTypeForModule(module.id),
    );
  }

  /// Determina el tipo de pantalla genérica según el módulo
  ModuleScreenType _getScreenTypeForModule(String moduleId) {
    // Módulos que funcionan mejor como lista
    if (_isListModule(moduleId)) {
      return ModuleScreenType.list;
    }

    // Módulos que funcionan mejor como grid
    if (_isGridModule(moduleId)) {
      return ModuleScreenType.grid;
    }

    // Módulos que funcionan mejor como dashboard
    if (_isDashboardModule(moduleId)) {
      return ModuleScreenType.dashboard;
    }

    // Por defecto, lista
    return ModuleScreenType.list;
  }

  bool _isListModule(String moduleId) {
    return [
      'incidencias',
      'tramites',
      'participacion',
      'presupuestos-participativos',
      'avisos-municipales',
      'ayuda-vecinal',
      'talleres',
      'cursos',
      'biblioteca',
      'podcast',
      'radio',
    ].contains(moduleId);
  }

  bool _isGridModule(String moduleId) {
    return [
      'multimedia',
      'tienda-local',
      'red-social',
      'bares',
      'colectivos',
    ].contains(moduleId);
  }

  bool _isDashboardModule(String moduleId) {
    return [
      'transparencia',
      'reciclaje',
      'compostaje',
      'huertos-urbanos',
      'espacios-comunes',
      'bicicletas-compartidas',
      'parkings',
      'carpooling',
      'empresarial',
      'woocommerce',
      'email-marketing',
    ].contains(moduleId);
  }

  /// Obtiene la pantalla directamente por ID de módulo
  Widget? getScreenById(BuildContext context, String moduleId) {
    final loader = ModuleLazyLoader();
    final builder = loader.getScreenBuilder(moduleId);
    return builder?.call(context);
  }

  /// Verifica si un módulo tiene pantalla implementada
  bool hasImplementedScreen(String moduleId) {
    final loader = ModuleLazyLoader();
    return loader.getScreenBuilder(moduleId) != null;
  }
}

/// Provider para el registro de pantallas
final moduleScreenRegistryProvider = Provider<ModuleScreenRegistry>((ref) {
  return ModuleScreenRegistry();
});
