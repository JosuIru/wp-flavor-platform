import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/admin_modules_provider.dart';
import '../../core/widgets/flavor_state_widgets.dart';
import 'admin_reservations_screen.dart';
import 'camps/camps_management_screen.dart';
import 'module_placeholder_screen.dart';
import '../modules/facturas/facturas_screen.dart';
import '../modules/fichaje_empleados/fichaje_empleados_screen.dart';
import '../modules/bares/bares_screen.dart';
import '../modules/woocommerce/woocommerce_admin_screen.dart';
import '../modules/avisos_municipales/avisos_municipales_screen.dart';
import '../modules/ayuda_vecinal/ayuda_vecinal_screen.dart';
import '../modules/banco_tiempo/banco_tiempo_screen.dart';
import '../modules/biblioteca/biblioteca_screen.dart';
import '../modules/bicicletas_compartidas/bicicletas_compartidas_screen.dart';
import '../modules/chat_grupos/chat_grupos_screen.dart';
import '../modules/chat_interno/chat_interno_screen.dart';
import '../modules/cursos/cursos_screen.dart';
import '../modules/espacios_comunes/espacios_comunes_screen.dart';
import '../modules/eventos/eventos_screen.dart';
import '../modules/grupos_consumo/grupos_consumo_screen.dart';
import '../modules/huertos_urbanos/huertos_urbanos_screen.dart';
import '../modules/incidencias/incidencias_screen.dart';
import '../modules/marketplace/marketplace_screen.dart';
import '../modules/parkings/parkings_screen.dart';
import '../modules/reciclaje/reciclaje_screen.dart';
import '../modules/socios/socios_screen.dart';
import '../modules/talleres/talleres_screen.dart';
import '../modules/tramites/tramites_screen.dart';

part 'modules_admin_screen_dynamic_parts.dart';

/// Dashboard dinámico de módulos administrativos
///
/// Lee los módulos activos desde la API y muestra métricas
/// para cada módulo de forma dinámica.
class ModulesAdminScreenDynamic extends ConsumerStatefulWidget {
  const ModulesAdminScreenDynamic({super.key});

  @override
  ConsumerState<ModulesAdminScreenDynamic> createState() => _ModulesAdminScreenDynamicState();
}

class _ModulesAdminScreenDynamicState extends ConsumerState<ModulesAdminScreenDynamic> {
  String _localizedModuleSubtitle(AppLocalizations i18n, _ModuleMetrics metric) {
    switch (metric.id) {
      case 'grupos-consumo':
        return i18n.adminMetricOpenOrders;
      case 'banco-tiempo':
        return i18n.adminMetricActiveServices;
      case 'marketplace':
        return i18n.adminMetricActiveListings;
      case 'eventos':
        return i18n.adminModuleScheduledEvents;
      case 'cursos':
      case 'biblioteca':
        return i18n.adminModuleActive;
      default:
        switch (metric.subtitle) {
          case '__OPEN_ORDERS__':
            return i18n.adminMetricOpenOrders;
          case '__ACTIVE_SERVICES__':
            return i18n.adminMetricActiveServices;
          case '__ACTIVE_LISTINGS__':
            return i18n.adminMetricActiveListings;
          case '__SCHEDULED_EVENTS__':
            return i18n.adminModuleScheduledEvents;
          case '__ACTIVE_MODULE__':
            return i18n.adminModuleActive;
          default:
            return metric.subtitle;
        }
    }
  }

  late Future<List<_ModuleMetrics>> _futureMetrics;

  @override
  void initState() {
    super.initState();
    _futureMetrics = _loadAllModuleMetrics();
  }

  /// Carga métricas para todos los módulos activos
  Future<List<_ModuleMetrics>> _loadAllModuleMetrics() async {
    final api = ref.read(apiClientProvider);
    final metrics = <_ModuleMetrics>[];

    // Obtener módulos activos
    final modulesResponse = await api.getAvailableModules();
    if (!modulesResponse.success || modulesResponse.data == null) {
      return metrics;
    }

    final modulesList = (modulesResponse.data!['modules'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .toList();

    // Cargar métricas para cada módulo
    for (final moduleData in modulesList) {
      final moduleId = moduleData['id']?.toString() ?? '';
      if (moduleId.isEmpty) continue;

      try {
        final moduleMetrics = await _loadMetricsForModule(api, moduleId, moduleData);
        if (moduleMetrics != null) {
          metrics.add(moduleMetrics);
        }
      } catch (e) {
        debugPrint('Error loading metrics for $moduleId: $e');
      }
    }

    return metrics;
  }

  /// Carga métricas para un módulo específico
  Future<_ModuleMetrics?> _loadMetricsForModule(
    ApiClient api,
    String moduleId,
    Map<String, dynamic> moduleData,
  ) async {
    final moduleName = moduleData['name']?.toString() ?? moduleId;

    // Mapeo de módulos a sus endpoints y datos
    switch (moduleId) {
      case 'grupos-consumo':
        final response = await api.getGruposConsumoPedidos(estado: 'abierto', perPage: 50, page: 1);
        if (response.success && response.data != null) {
          final count = (response.data!['data'] as List<dynamic>? ?? []).length;
          return _ModuleMetrics(
            id: moduleId,
            name: moduleName,
            subtitle: '__OPEN_ORDERS__',
            value: count.toString(),
            icon: Icons.shopping_basket_outlined,
            color: Colors.green,
          );
        }
        break;

      case 'banco-tiempo':
        final response = await api.getBancoTiempoServicios(limite: 50, pagina: 1);
        if (response.success && response.data != null) {
          final count = (response.data!['servicios'] as List<dynamic>? ?? []).length;
          return _ModuleMetrics(
            id: moduleId,
            name: moduleName,
            subtitle: '__ACTIVE_SERVICES__',
            value: count.toString(),
            icon: Icons.volunteer_activism,
            color: Colors.blue,
          );
        }
        break;

      case 'marketplace':
        final response = await api.getMarketplaceAnuncios(limite: 50, pagina: 1);
        if (response.success && response.data != null) {
          final count = (response.data!['anuncios'] as List<dynamic>? ?? []).length;
          return _ModuleMetrics(
            id: moduleId,
            name: moduleName,
            subtitle: '__ACTIVE_LISTINGS__',
            value: count.toString(),
            icon: Icons.storefront_outlined,
            color: Colors.orange,
          );
        }
        break;

      case 'eventos':
        final response = await api.getEventos(limite: 50);
        if (response.success && response.data != null) {
          final count = (response.data!['data'] as List<dynamic>? ?? []).length;
          return _ModuleMetrics(
            id: moduleId,
            name: moduleName,
            subtitle: '__SCHEDULED_EVENTS__',
            value: count.toString(),
            icon: Icons.event,
            color: Colors.purple,
          );
        }
        break;

      case 'cursos':
        // Para módulos sin endpoint específico, mostrar como disponible
        return _ModuleMetrics(
          id: moduleId,
          name: moduleName,
          subtitle: '__ACTIVE_MODULE__',
          value: '✓',
          icon: Icons.school,
          color: Colors.indigo,
        );

      case 'biblioteca':
        return _ModuleMetrics(
          id: moduleId,
          name: moduleName,
          subtitle: '__ACTIVE_MODULE__',
          value: '✓',
          icon: Icons.library_books,
          color: Colors.teal,
        );

      default:
        // Módulos genéricos
        return _ModuleMetrics(
          id: moduleId,
          name: moduleName,
          subtitle: '__ACTIVE_MODULE__',
          value: '✓',
          icon: Icons.extension,
          color: Colors.grey,
        );
    }

    return null;
  }

  Future<void> _refresh() async {
    setState(() {
      _futureMetrics = _loadAllModuleMetrics();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    final modulesAsync = ref.watch(adminModulesProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.adminModulesDashboardTitle),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refresh,
            tooltip: i18n.actualizar2e7be1,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: modulesAsync.when(
          data: (modules) => _buildContent(i18n, modules),
          loading: () => const FlavorLoadingState(),
          error: (error, stack) => Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
                const SizedBox(height: 16),
                Text(
                  i18n.adminModulesLoadError,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 8),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 32),
                  child: Text(
                    error.toString(),
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  onPressed: () {
                    ref.invalidate(adminModulesProvider);
                    _refresh();
                  },
                  icon: const Icon(Icons.refresh),
                  label: Text(i18n.commonRetry),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildContent(AppLocalizations i18n, List<String> modules) {
    if (modules.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.extension_off, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              i18n.adminModulesEmpty,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 8),
            Text(
              i18n.adminModulesEnableInWordPress,
              style: TextStyle(color: Colors.grey.shade600),
            ),
          ],
        ),
      );
    }

    return FutureBuilder<List<_ModuleMetrics>>(
      future: _futureMetrics,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const FlavorLoadingState();
        }

        if (snapshot.hasError) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
                const SizedBox(height: 16),
                Text(
                  i18n.adminModulesMetricsLoadError,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 8),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 32),
                  child: Text(
                    snapshot.error.toString(),
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                ),
              ],
            ),
          );
        }

        final metrics = snapshot.data ?? [];
        if (metrics.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.query_stats, size: 64, color: Colors.grey.shade400),
                const SizedBox(height: 16),
                Text(
                  i18n.adminModulesDashboardEmpty,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
              ],
            ),
          );
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: metrics.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final metric = metrics[index];
            return _ModuleMetricsCard(
              metric: metric,
              subtitle: _localizedModuleSubtitle(i18n, metric),
              onTap: () => _navigateToModule(context, metric.id, metric.name),
            );
          },
        );
      },
    );
  }

  void _navigateToModule(BuildContext context, String moduleId, String moduleName) {
    final screen = _buildAdminModuleScreen(moduleId, moduleName);
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => screen,
      ),
    );
  }
}
