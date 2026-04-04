import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/admin_modules_provider.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/widgets/chart_widgets.dart';
import '../../core/widgets/flavor_state_widgets.dart';
import '../../core/services/client_dashboard_service.dart';
import '../../core/utils/haptics.dart';
import 'widgets/client_stats_card.dart';
import 'widgets/quick_actions_grid.dart';
import 'widgets/activity_timeline.dart';
import 'widgets/dashboard_map_widget.dart';
import 'widgets/network_status_widget.dart';
import 'widgets/stats_charts_widget.dart';

part 'client_dashboard_screen_parts.dart';

/// Provider para metricas del usuario desde el servicio
final enhancedUserMetricsProvider =
    FutureProvider<List<UserStatistic>>((ref) async {
  final api = ref.read(apiClientProvider);
  final statistics = <UserStatistic>[];

  try {
    // Intentar obtener estadisticas del endpoint dedicado
    final response = await api.get('/client/statistics');
    if (response.success && response.data != null) {
      final statsJson = response.data!['statistics'] as List? ?? [];
      return statsJson
          .map((statistic) =>
              UserStatistic.fromJson(statistic as Map<String, dynamic>))
          .toList();
    }
  } catch (error) {
    debugPrint('[UserMetrics] Error getting statistics from endpoint: $error');
  }

  // Fallback: construir estadisticas manualmente
  try {
    // Obtener modulos activos para cargar metricas relevantes
    final modulesAsync = await ref.watch(adminModulesProvider.future);

    // Mis Pedidos (WooCommerce) - solo si hay WooCommerce activo
    if (modulesAsync.contains('woocommerce') ||
        modulesAsync.contains('pedidos') ||
        modulesAsync.contains('tienda')) {
      final pedidos = await api.getWooPedidos(limite: 100);
      if (pedidos.success && pedidos.data != null) {
        final list = pedidos.data!['pedidos'] as List? ?? [];
        statistics.add(UserStatistic(
          id: 'my_orders',
          title: 'Mis Pedidos',
          value: list.length.toString(),
          numericValue: list.length.toDouble(),
          iconName: 'shopping_cart',
          colorHex: '#2196F3',
        ));
      }
    }

    // Grupos de Consumo
    if (modulesAsync.contains('grupos_consumo') ||
        modulesAsync.contains('grupos-consumo')) {
      final pedidosGruposConsumo = await api.getGruposConsumoPedidos(
          estado: 'abierto', perPage: 10, page: 1);
      if (pedidosGruposConsumo.success && pedidosGruposConsumo.data != null) {
        final list = pedidosGruposConsumo.data!['data'] as List? ?? [];
        statistics.add(UserStatistic(
          id: 'gc_pedidos',
          title: 'Pedidos Activos',
          value: list.length.toString(),
          numericValue: list.length.toDouble(),
          iconName: 'shopping_basket',
          colorHex: '#4CAF50',
        ));
      }
    }

    // Banco de Tiempo
    if (modulesAsync.contains('banco_tiempo') ||
        modulesAsync.contains('banco-tiempo')) {
      final servicios =
          await api.getBancoTiempoServicios(limite: 50, pagina: 1);
      if (servicios.success && servicios.data != null) {
        final list = servicios.data!['servicios'] as List? ?? [];
        statistics.add(UserStatistic(
          id: 'bt_servicios',
          title: 'Servicios Disponibles',
          value: list.length.toString(),
          numericValue: list.length.toDouble(),
          iconName: 'volunteer_activism',
          colorHex: '#009688',
        ));
      }
    }

    // Marketplace
    if (modulesAsync.contains('marketplace')) {
      final anuncios = await api.getMarketplaceAnuncios(limite: 50, pagina: 1);
      if (anuncios.success && anuncios.data != null) {
        final list = anuncios.data!['anuncios'] as List? ?? [];
        statistics.add(UserStatistic(
          id: 'marketplace_anuncios',
          title: 'Anuncios Activos',
          value: list.length.toString(),
          numericValue: list.length.toDouble(),
          iconName: 'storefront',
          colorHex: '#FF9800',
        ));
      }
    }

    // Eventos proximos
    if (modulesAsync.contains('eventos')) {
      statistics.add(const UserStatistic(
        id: 'eventos_proximos',
        title: 'Eventos Proximos',
        value: '3',
        numericValue: 3,
        iconName: 'event',
        colorHex: '#E91E63',
        trendPercentage: 15.0,
      ));
    }
  } catch (error) {
    debugPrint('[UserMetrics] Error building fallback stats: $error');
  }

  return statistics;
});

/// Provider para actividad reciente del usuario
/// Devuelve lista vacía si el endpoint no existe - no usar datos de ejemplo
final userActivityProvider = FutureProvider<List<UserActivity>>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.get('/client/activity?limit=10');
    if (response.success && response.data != null) {
      final activityJson = response.data!['activity'] as List? ?? [];
      return activityJson
          .map((activity) =>
              UserActivity.fromJson(activity as Map<String, dynamic>))
          .toList();
    }
  } catch (error) {
    debugPrint('[UserActivity] Endpoint no disponible: $error');
  }

  // No devolver datos de ejemplo - solo datos reales del servidor
  return [];
});

/// Provider para notificaciones del usuario
final userNotificationsProvider =
    FutureProvider<List<UserNotification>>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.get('/client/notifications?limit=5');
    if (response.success && response.data != null) {
      final notificationsJson = response.data!['notifications'] as List? ?? [];
      return notificationsJson
          .map((notification) =>
              UserNotification.fromJson(notification as Map<String, dynamic>))
          .toList();
    }
  } catch (error) {
    debugPrint('[UserNotifications] Error: $error');
  }

  return [];
});

/// Provider para perfil del usuario
final userProfileProvider = FutureProvider<UserProfile?>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.get('/client/profile');
    if (response.success && response.data != null) {
      return UserProfile.fromJson(
          response.data!['profile'] as Map<String, dynamic>? ?? {});
    }
  } catch (error) {
    debugPrint('[UserProfile] Error: $error');
  }

  // Perfil por defecto
  return const UserProfile(
    id: '0',
    displayName: 'Usuario',
    email: '',
    level: 1,
    points: 0,
  );
});

/// Provider para datos de graficos del usuario
final userChartDataProvider = FutureProvider.family<List<ChartData>, String>(
  (ref, type) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.getDashboardCharts(type: type, days: 7);
      if (response.success && response.data != null) {
        final List<dynamic> chartData = response.data!['data'] as List? ?? [];
        return chartData
            .map((item) => ChartData.fromJson(item as Map<String, dynamic>))
            .toList();
      }
    } catch (error) {
      debugPrint('[UserChartData] Error getting $type chart: $error');
    }

    return [];
  },
);

/// Provider de configuración de inicio (quick actions + home widgets) desde servidor
final clientHomeConfigProvider =
    FutureProvider<Map<String, dynamic>>((ref) async {
  final api = ref.read(apiClientProvider);
  try {
    final response = await api.getClientAppConfig();
    if (response.success && response.data != null) {
      final config = response.data!['config'];
      if (config is Map<String, dynamic>) {
        return config;
      }
    }
  } catch (error) {
    debugPrint('[ClientHomeConfig] Error: $error');
  }
  return const <String, dynamic>{};
});

/// Dashboard mejorado para clientes - Home Screen moderno
class ClientDashboardScreen extends ConsumerStatefulWidget {
  final Function(String)? onNavigateToModule;

  const ClientDashboardScreen({
    super.key,
    this.onNavigateToModule,
  });

  @override
  ConsumerState<ClientDashboardScreen> createState() =>
      _ClientDashboardScreenState();
}

class _ClientDashboardScreenState extends ConsumerState<ClientDashboardScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _handleRefresh() async {
    Haptics.medium();

    // Invalidar todos los providers relacionados
    ref.invalidate(enhancedUserMetricsProvider);
    ref.invalidate(userActivityProvider);
    ref.invalidate(userNotificationsProvider);
    ref.invalidate(userProfileProvider);

    // Invalidar nuevos providers de widgets mejorados
    ref.invalidate(mapPointsOfInterestProvider);
    ref.invalidate(networkStatusProvider);
    ref.invalidate(quickStatsProvider);
    ref.invalidate(weeklyActivityProvider);
    ref.invalidate(distributionChartProvider);

    // Esperar un poco para feedback visual
    await Future.delayed(const Duration(milliseconds: 500));

    if (mounted) {
      Haptics.success();
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      backgroundColor: colorScheme.surface,
      body: RefreshableList(
        onRefresh: _handleRefresh,
        child: CustomScrollView(
          controller: _scrollController,
          slivers: [
            // Header con perfil del usuario
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                child: _UserProfileHeader(
                  onNotificationsTap: () => _showNotifications(context),
                ),
              ),
            ),

            // Saludo personalizado
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                child: _GreetingSection(),
              ),
            ),

            // Quick Stats Panel horizontal
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: QuickStatsPanel(
                  onStatTap: (quickStat) {
                    Haptics.light();
                    // Navegar al módulo correspondiente
                    if (widget.onNavigateToModule != null && quickStat.moduleId != null) {
                      widget.onNavigateToModule!(quickStat.moduleId!);
                    }
                  },
                ),
              ),
            ),

            // Metricas del usuario
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: _EnhancedMetricsSection(
                  onNavigateToModule: widget.onNavigateToModule,
                ),
              ),
            ),

            // Accesos rapidos
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: _QuickActionsSection(
                  onNavigateToModule: widget.onNavigateToModule,
                ),
              ),
            ),

            // Mapa interactivo de puntos de interes
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: DashboardMapWidget(
                  height: 220,
                  showControls: true,
                  enableClustering: true,
                  onPointTap: (point) {
                    Haptics.medium();
                    // Navegar al modulo correspondiente
                    if (widget.onNavigateToModule != null) {
                      widget.onNavigateToModule!(point.moduleType);
                    }
                  },
                ),
              ),
            ),

            // Estado de la red y comunidades
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: NetworkStatusWidget(
                  showDetails: true,
                  onTap: () {
                    Haptics.light();
                    widget.onNavigateToModule?.call('network');
                  },
                  onViewCommunities: () {
                    Haptics.light();
                    widget.onNavigateToModule?.call('communities');
                  },
                  onViewResources: () {
                    Haptics.light();
                    widget.onNavigateToModule?.call('shared_resources');
                  },
                ),
              ),
            ),

            // Graficos de actividad mejorados
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: _EnhancedChartsSection(
                  onNavigateToModule: widget.onNavigateToModule,
                ),
              ),
            ),

            // Actividad reciente
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: _EnhancedActivitySection(
                  onActivityTap: (activity) => _handleActivityTap(activity),
                ),
              ),
            ),

            // Widgets de modulos (proximas reservas, etc.)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 24),
                child: _ModuleWidgetsSection(
                  onNavigateToModule: widget.onNavigateToModule,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showNotifications(BuildContext context) {
    Haptics.light();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) => const _NotificationsSheet(),
    );
  }

  void _handleActivityTap(UserActivity activity) {
    Haptics.light();
    if (activity.actionRoute != null && widget.onNavigateToModule != null) {
      widget.onNavigateToModule!(activity.actionRoute!);
    }
  }
}
