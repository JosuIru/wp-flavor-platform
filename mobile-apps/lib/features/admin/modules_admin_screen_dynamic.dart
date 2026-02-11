import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/admin_modules_provider.dart';
import 'admin_reservations_screen.dart';
import 'camps/camps_management_screen.dart';
import 'module_placeholder_screen.dart';
import '../modules/facturas/facturas_screen.dart';
import '../modules/fichaje_empleados/fichaje_empleados_screen.dart';
import '../modules/bares/bares_screen.dart';
import '../modules/woocommerce/woocommerce_admin_screen.dart';

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
            subtitle: 'Pedidos abiertos',
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
            subtitle: 'Servicios activos',
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
            subtitle: 'Anuncios activos',
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
            subtitle: 'Eventos programados',
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
          subtitle: 'Módulo activo',
          value: '✓',
          icon: Icons.school,
          color: Colors.indigo,
        );

      case 'biblioteca':
        return _ModuleMetrics(
          id: moduleId,
          name: moduleName,
          subtitle: 'Módulo activo',
          value: '✓',
          icon: Icons.library_books,
          color: Colors.teal,
        );

      default:
        // Módulos genéricos
        return _ModuleMetrics(
          id: moduleId,
          name: moduleName,
          subtitle: 'Módulo activo',
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
    final i18n = AppLocalizations.of(context)!;
    final modulesAsync = ref.watch(adminModulesProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.adminModulesDashboardTitle),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refresh,
            tooltip: 'Actualizar',
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: modulesAsync.when(
          data: (modules) => _buildContent(i18n, modules),
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, stack) => Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
                const SizedBox(height: 16),
                Text(
                  'Error al cargar módulos',
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
                  label: const Text('Reintentar'),
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
              'No hay módulos activos',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 8),
            Text(
              'Activa módulos desde el panel de WordPress',
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
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
                const SizedBox(height: 16),
                Text(
                  'Error al cargar métricas',
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
                  'Sin métricas disponibles',
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
            return Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: InkWell(
                onTap: () => _navigateToModule(context, metric.id, metric.name),
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Container(
                        width: 56,
                        height: 56,
                        decoration: BoxDecoration(
                          color: metric.color.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(
                          metric.icon,
                          color: metric.color,
                          size: 28,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              metric.name,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              metric.subtitle,
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.grey.shade600,
                              ),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        metric.value,
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: metric.color,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Icon(Icons.chevron_right, color: Colors.grey.shade400),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  void _navigateToModule(BuildContext context, String moduleId, String moduleName) {
    Widget? screen;

    // Mapear módulos activos a sus pantallas
    // Los IDs vienen del endpoint /app-discovery/v1/modules
    switch (moduleId) {
      case 'reservas':
        screen = const AdminReservationsScreen();
        break;

      case 'facturas':
        screen = const FacturasScreen();
        break;

      case 'fichaje_empleados':
        screen = const FichajeEmpleadosScreen();
        break;

      case 'bares':
        screen = const BaresScreen();
        break;

      case 'woocommerce':
        screen = const WooCommerceAdminScreen();
        break;

      case 'campamentos':
      case 'basabere-campamentos':
        screen = const CampsManagementScreen();
        break;

      // Otros módulos sin pantalla específica
      default:
        screen = ModulePlaceholderScreen(
          moduleId: moduleId,
          moduleName: moduleName,
        );
    }

    // Navegar a la pantalla
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => screen!,
      ),
    );
  }
}

/// Métricas de un módulo
class _ModuleMetrics {
  final String id;
  final String name;
  final String subtitle;
  final String value;
  final IconData icon;
  final Color color;

  const _ModuleMetrics({
    required this.id,
    required this.name,
    required this.subtitle,
    required this.value,
    required this.icon,
    required this.color,
  });
}
