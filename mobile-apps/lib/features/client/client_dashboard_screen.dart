import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/admin_modules_provider.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/widgets/chart_widgets.dart';
import '../../core/services/client_dashboard_service.dart';
import '../../core/utils/haptics.dart';
import 'widgets/client_stats_card.dart';
import 'widgets/quick_actions_grid.dart';
import 'widgets/activity_timeline.dart';

/// Provider para metricas del usuario desde el servicio
final enhancedUserMetricsProvider = FutureProvider<List<UserStatistic>>((ref) async {
  final api = ref.read(apiClientProvider);
  final statistics = <UserStatistic>[];

  try {
    // Intentar obtener estadisticas del endpoint dedicado
    final response = await api.get('/client/statistics');
    if (response.success && response.data != null) {
      final statsJson = response.data!['statistics'] as List? ?? [];
      return statsJson.map((statistic) => UserStatistic.fromJson(statistic as Map<String, dynamic>)).toList();
    }
  } catch (error) {
    debugPrint('[UserMetrics] Error getting statistics from endpoint: $error');
  }

  // Fallback: construir estadisticas manualmente
  try {
    // Obtener modulos activos para cargar metricas relevantes
    final modulesAsync = await ref.watch(adminModulesProvider.future);

    // Mis Pedidos (WooCommerce)
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

    // Grupos de Consumo
    if (modulesAsync.contains('grupos_consumo') || modulesAsync.contains('grupos-consumo')) {
      final pedidosGruposConsumo = await api.getGruposConsumoPedidos(estado: 'abierto', perPage: 10, page: 1);
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
    if (modulesAsync.contains('banco_tiempo') || modulesAsync.contains('banco-tiempo')) {
      final servicios = await api.getBancoTiempoServicios(limite: 50, pagina: 1);
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
      statistics.add(UserStatistic(
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
final userActivityProvider = FutureProvider<List<UserActivity>>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.get('/client/activity?limit=10');
    if (response.success && response.data != null) {
      final activityJson = response.data!['activity'] as List? ?? [];
      return activityJson.map((activity) => UserActivity.fromJson(activity as Map<String, dynamic>)).toList();
    }
  } catch (error) {
    debugPrint('[UserActivity] Error: $error');
  }

  // Devolver actividad de ejemplo si no hay endpoint
  return _getExampleActivities();
});

/// Provider para notificaciones del usuario
final userNotificationsProvider = FutureProvider<List<UserNotification>>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.get('/client/notifications?limit=5');
    if (response.success && response.data != null) {
      final notificationsJson = response.data!['notifications'] as List? ?? [];
      return notificationsJson.map((notification) => UserNotification.fromJson(notification as Map<String, dynamic>)).toList();
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
      return UserProfile.fromJson(response.data!['profile'] as Map<String, dynamic>? ?? {});
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
        return chartData.map((item) => ChartData.fromJson(item as Map<String, dynamic>)).toList();
      }
    } catch (error) {
      debugPrint('[UserChartData] Error getting $type chart: $error');
    }

    return [];
  },
);

/// Dashboard mejorado para clientes - Home Screen moderno
class ClientDashboardScreen extends ConsumerStatefulWidget {
  final Function(String)? onNavigateToModule;

  const ClientDashboardScreen({
    super.key,
    this.onNavigateToModule,
  });

  @override
  ConsumerState<ClientDashboardScreen> createState() => _ClientDashboardScreenState();
}

class _ClientDashboardScreenState extends ConsumerState<ClientDashboardScreen> {
  final ScrollController _scrollController = ScrollController();
  bool _isRefreshing = false;

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _handleRefresh() async {
    Haptics.medium();
    setState(() => _isRefreshing = true);

    // Invalidar todos los providers relacionados
    ref.invalidate(enhancedUserMetricsProvider);
    ref.invalidate(userActivityProvider);
    ref.invalidate(userNotificationsProvider);
    ref.invalidate(userProfileProvider);

    // Esperar un poco para feedback visual
    await Future.delayed(const Duration(milliseconds: 500));

    if (mounted) {
      setState(() => _isRefreshing = false);
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

            // Metricas del usuario
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: _EnhancedMetricsSection(),
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

            // Graficos de actividad
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
                child: _ChartsSection(),
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

/// Header con perfil del usuario, avatar, nivel y notificaciones
class _UserProfileHeader extends ConsumerWidget {
  final VoidCallback? onNotificationsTap;

  const _UserProfileHeader({this.onNotificationsTap});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profileAsync = ref.watch(userProfileProvider);
    final notificationsAsync = ref.watch(userNotificationsProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return profileAsync.when(
      data: (profile) {
        if (profile == null) return const SizedBox.shrink();

        final unreadCount = notificationsAsync.whenOrNull(
          data: (notifications) => notifications.where((notification) => !notification.isRead).length,
        ) ?? 0;

        return Semantics(
          label: 'Perfil de ${profile.displayName}, nivel ${profile.level}, ${profile.points} puntos',
          child: Row(
            children: [
              // Avatar con indicador de nivel
              _buildAvatar(context, profile, colorScheme),

              const SizedBox(width: 12),

              // Nombre y nivel
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ExcludeSemantics(
                      child: Text(
                        profile.displayName,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(height: 4),
                    _buildLevelBadge(context, profile, colorScheme),
                  ],
                ),
              ),

              // Boton de notificaciones
              Semantics(
                button: true,
                label: 'Notificaciones${unreadCount > 0 ? ', $unreadCount sin leer' : ''}',
                child: Badge(
                  isLabelVisible: unreadCount > 0,
                  label: Text(
                    unreadCount > 99 ? '99+' : unreadCount.toString(),
                    style: const TextStyle(fontSize: 10),
                  ),
                  child: IconButton(
                    onPressed: onNotificationsTap,
                    icon: const Icon(Icons.notifications_outlined),
                    style: IconButton.styleFrom(
                      backgroundColor: colorScheme.surfaceContainerHighest,
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
      loading: () => _buildSkeletonHeader(colorScheme),
      error: (_, __) => const SizedBox.shrink(),
    );
  }

  Widget _buildAvatar(BuildContext context, UserProfile profile, ColorScheme colorScheme) {
    return Stack(
      children: [
        ExcludeSemantics(
          child: CircleAvatar(
            radius: 28,
            backgroundColor: colorScheme.primaryContainer,
            backgroundImage: profile.avatarUrl != null ? NetworkImage(profile.avatarUrl!) : null,
            child: profile.avatarUrl == null
                ? Text(
                    profile.displayName.isNotEmpty ? profile.displayName[0].toUpperCase() : 'U',
                    style: TextStyle(
                      color: colorScheme.onPrimaryContainer,
                      fontWeight: FontWeight.bold,
                      fontSize: 24,
                    ),
                  )
                : null,
          ),
        ),
        // Indicador de nivel
        Positioned(
          right: 0,
          bottom: 0,
          child: ExcludeSemantics(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: colorScheme.primary,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: colorScheme.surface,
                  width: 2,
                ),
              ),
              child: Text(
                '${profile.level}',
                style: TextStyle(
                  color: colorScheme.onPrimary,
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildLevelBadge(BuildContext context, UserProfile profile, ColorScheme colorScheme) {
    return ExcludeSemantics(
      child: Row(
        children: [
          Icon(
            Icons.star,
            size: 14,
            color: Colors.amber[700],
          ),
          const SizedBox(width: 4),
          Text(
            '${profile.points} pts',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: colorScheme.onSurfaceVariant,
                  fontWeight: FontWeight.w500,
                ),
          ),
          const SizedBox(width: 8),
          // Barra de progreso mini
          SizedBox(
            width: 60,
            height: 4,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(2),
              child: LinearProgressIndicator(
                value: profile.levelProgress,
                backgroundColor: colorScheme.surfaceContainerHighest,
                color: Colors.amber[700],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSkeletonHeader(ColorScheme colorScheme) {
    return Row(
      children: [
        Container(
          width: 56,
          height: 56,
          decoration: BoxDecoration(
            color: colorScheme.surfaceContainerHighest,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 120,
                height: 18,
                decoration: BoxDecoration(
                  color: colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const SizedBox(height: 8),
              Container(
                width: 80,
                height: 14,
                decoration: BoxDecoration(
                  color: colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

/// Seccion de saludo personalizado
class _GreetingSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colorScheme = Theme.of(context).colorScheme;
    final hour = DateTime.now().hour;

    String greeting;
    IconData greetingIcon;

    if (hour < 12) {
      greeting = 'Buenos dias';
      greetingIcon = Icons.wb_sunny_outlined;
    } else if (hour < 19) {
      greeting = 'Buenas tardes';
      greetingIcon = Icons.wb_twilight_outlined;
    } else {
      greeting = 'Buenas noches';
      greetingIcon = Icons.nightlight_outlined;
    }

    return Semantics(
      label: '$greeting, ${_formatDate(DateTime.now(), context)}',
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [
              colorScheme.primaryContainer,
              colorScheme.secondaryContainer,
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                ExcludeSemantics(
                  child: Icon(
                    greetingIcon,
                    color: colorScheme.onPrimaryContainer,
                    size: 28,
                  ),
                ),
                const SizedBox(width: 12),
                ExcludeSemantics(
                  child: Text(
                    greeting,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: colorScheme.onPrimaryContainer,
                        ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            ExcludeSemantics(
              child: Text(
                _formatDate(DateTime.now(), context),
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: colorScheme.onPrimaryContainer.withOpacity(0.8),
                    ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime date, BuildContext context) {
    final locale = Localizations.localeOf(context).toLanguageTag();
    return DateFormat('EEEE, d \'de\' MMMM', locale).format(date);
  }
}

/// Seccion de metricas mejorada
class _EnhancedMetricsSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final metricsAsync = ref.watch(enhancedUserMetricsProvider);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Semantics(
          header: true,
          child: Text(
            'Mi Resumen',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 12),
        metricsAsync.when(
          data: (statistics) => ClientStatsGrid(
            statistics: statistics,
            onStatisticTap: (statistic) {
              Haptics.light();
              debugPrint('Tap on statistic: ${statistic.id}');
            },
          ),
          loading: () => const ClientStatsGrid(statistics: [], isLoading: true),
          error: (error, stack) => Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Text('Error al cargar metricas'),
            ),
          ),
        ),
      ],
    );
  }
}

/// Seccion de accesos rapidos
class _QuickActionsSection extends ConsumerWidget {
  final Function(String)? onNavigateToModule;

  const _QuickActionsSection({this.onNavigateToModule});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final modulesAsync = ref.watch(adminModulesProvider);

    return modulesAsync.when(
      data: (modules) {
        final actions = _buildQuickActions(modules);

        return QuickActionsSection(
          title: 'Accesos rapidos',
          actions: actions,
          onActionTap: (action) {
            Haptics.selection();
            if (action.route != null) {
              onNavigateToModule?.call(action.route!);
            }
          },
          gridColumns: 4,
        );
      },
      loading: () => const SizedBox(
        height: 100,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => const SizedBox.shrink(),
    );
  }

  List<QuickAction> _buildQuickActions(List<String> modules) {
    final actions = <QuickAction>[];

    // Reservas (siempre disponible)
    actions.add(const QuickAction(
      id: 'reservations',
      label: 'Reservar',
      iconName: 'calendar_today',
      colorHex: '#2196F3',
      route: 'reservations',
    ));

    // Chat IA
    actions.add(const QuickAction(
      id: 'chat',
      label: 'Chat IA',
      iconName: 'smart_toy',
      colorHex: '#9C27B0',
      route: 'chat',
    ));

    // Grupos de Consumo
    if (modules.contains('grupos_consumo') || modules.contains('grupos-consumo')) {
      actions.add(const QuickAction(
        id: 'grupos_consumo',
        label: 'Grupos',
        iconName: 'shopping_basket',
        colorHex: '#4CAF50',
        route: 'grupos_consumo',
        badgeCount: 2,
      ));
    }

    // Banco de Tiempo
    if (modules.contains('banco_tiempo') || modules.contains('banco-tiempo')) {
      actions.add(const QuickAction(
        id: 'banco_tiempo',
        label: 'Tiempo',
        iconName: 'volunteer_activism',
        colorHex: '#009688',
        route: 'banco_tiempo',
      ));
    }

    // Marketplace
    if (modules.contains('marketplace')) {
      actions.add(const QuickAction(
        id: 'marketplace',
        label: 'Mercado',
        iconName: 'storefront',
        colorHex: '#FF9800',
        route: 'marketplace',
      ));
    }

    // Eventos
    if (modules.contains('eventos')) {
      actions.add(const QuickAction(
        id: 'eventos',
        label: 'Eventos',
        iconName: 'event',
        colorHex: '#E91E63',
        route: 'eventos',
        badgeCount: 3,
      ));
    }

    // Biblioteca
    if (modules.contains('biblioteca')) {
      actions.add(const QuickAction(
        id: 'biblioteca',
        label: 'Biblioteca',
        iconName: 'article',
        colorHex: '#795548',
        route: 'biblioteca',
      ));
    }

    // Incidencias
    if (modules.contains('incidencias')) {
      actions.add(const QuickAction(
        id: 'incidencias',
        label: 'Incidencias',
        iconName: 'notifications',
        colorHex: '#F44336',
        route: 'incidencias',
      ));
    }

    return actions;
  }
}

/// Seccion de actividad reciente mejorada
class _EnhancedActivitySection extends ConsumerWidget {
  final Function(UserActivity)? onActivityTap;

  const _EnhancedActivitySection({this.onActivityTap});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final activityAsync = ref.watch(userActivityProvider);

    return activityAsync.when(
      data: (activities) => ActivitySection(
        title: 'Actividad reciente',
        activities: activities,
        onActivityTap: onActivityTap,
        maxItems: 5,
        onViewAll: () {
          Haptics.light();
          // Navegar a pantalla de actividad completa
        },
      ),
      loading: () => const ActivitySection(
        activities: [],
        isLoading: true,
      ),
      error: (_, __) => const SizedBox.shrink(),
    );
  }
}

/// Seccion de graficos de actividad
class _ChartsSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colorScheme = Theme.of(context).colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Semantics(
          header: true,
          child: Text(
            'Mi Actividad',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 12),

        // Grafico de actividad semanal
        _ActivityBarChartWidget(),

        const SizedBox(height: 16),

        // Grafico de distribucion por modulo
        _DistributionPieChartWidget(),
      ],
    );
  }
}

/// Widget de grafico de barras de actividad
class _ActivityBarChartWidget extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final chartDataAsync = ref.watch(userChartDataProvider('activity'));

    return chartDataAsync.when(
      data: (data) {
        if (data.isEmpty) {
          return const SizedBox.shrink();
        }
        return ActivityBarChart(
          data: data,
          title: 'Actividad de los ultimos 7 dias',
          barColor: Theme.of(context).colorScheme.primary,
        );
      },
      loading: () => SizedBox(
        height: 200,
        child: Card(
          child: Center(child: CircularProgressIndicator()),
        ),
      ),
      error: (error, stack) {
        debugPrint('[ActivityBarChart] Error: $error');
        return const SizedBox.shrink();
      },
    );
  }
}

/// Widget de grafico circular de distribucion
class _DistributionPieChartWidget extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final chartDataAsync = ref.watch(userChartDataProvider('distribution'));

    return chartDataAsync.when(
      data: (data) {
        if (data.isEmpty) {
          return const SizedBox.shrink();
        }
        return DistributionPieChart(
          data: data,
          title: 'Distribucion por modulo',
        );
      },
      loading: () => SizedBox(
        height: 200,
        child: Card(
          child: Center(child: CircularProgressIndicator()),
        ),
      ),
      error: (error, stack) {
        debugPrint('[DistributionPieChart] Error: $error');
        return const SizedBox.shrink();
      },
    );
  }
}

/// Seccion de widgets de modulos (proximas reservas, mensajes, etc.)
class _ModuleWidgetsSection extends ConsumerWidget {
  final Function(String)? onNavigateToModule;

  const _ModuleWidgetsSection({this.onNavigateToModule});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colorScheme = Theme.of(context).colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Semantics(
          header: true,
          child: Text(
            'Proximas citas',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 12),

        // Widget de proximas reservas
        _UpcomingReservationsWidget(
          onViewAll: () {
            Haptics.light();
            onNavigateToModule?.call('my_tickets');
          },
        ),
      ],
    );
  }
}

/// Widget de proximas reservas
class _UpcomingReservationsWidget extends StatelessWidget {
  final VoidCallback? onViewAll;

  const _UpcomingReservationsWidget({this.onViewAll});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    // Datos de ejemplo - en produccion vendrian del API
    final reservas = [
      _ReservationPreview(
        title: 'Reserva de espacio',
        date: DateTime.now().add(const Duration(days: 2)),
        location: 'Sala de reuniones A',
        color: Colors.blue,
      ),
      _ReservationPreview(
        title: 'Clase de yoga',
        date: DateTime.now().add(const Duration(days: 5)),
        location: 'Gimnasio comunitario',
        color: Colors.green,
      ),
    ];

    if (reservas.isEmpty) {
      return Semantics(
        label: 'No tienes reservas proximas',
        child: Card(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                Icon(
                  Icons.calendar_today_outlined,
                  size: 48,
                  color: colorScheme.onSurfaceVariant.withOpacity(0.5),
                ),
                const SizedBox(height: 12),
                Text(
                  'Sin reservas proximas',
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(
          color: colorScheme.outlineVariant,
          width: 1,
        ),
      ),
      child: Column(
        children: [
          ...reservas.map((reserva) => _ReservationPreviewTile(reserva: reserva)),
          if (onViewAll != null)
            ListTile(
              title: Text(
                'Ver todas mis reservas',
                style: TextStyle(
                  color: colorScheme.primary,
                  fontWeight: FontWeight.w500,
                ),
                textAlign: TextAlign.center,
              ),
              onTap: onViewAll,
            ),
        ],
      ),
    );
  }
}

class _ReservationPreview {
  final String title;
  final DateTime date;
  final String location;
  final Color color;

  const _ReservationPreview({
    required this.title,
    required this.date,
    required this.location,
    required this.color,
  });
}

class _ReservationPreviewTile extends StatelessWidget {
  final _ReservationPreview reserva;

  const _ReservationPreviewTile({required this.reserva});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final dateFormat = DateFormat('EEE, d MMM', 'es');
    final timeFormat = DateFormat('HH:mm');

    return ListTile(
      leading: Container(
        width: 48,
        height: 48,
        decoration: BoxDecoration(
          color: reserva.color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              reserva.date.day.toString(),
              style: TextStyle(
                color: reserva.color,
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            Text(
              DateFormat('MMM', 'es').format(reserva.date).toUpperCase(),
              style: TextStyle(
                color: reserva.color,
                fontSize: 10,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
      title: Text(
        reserva.title,
        style: const TextStyle(fontWeight: FontWeight.w600),
      ),
      subtitle: Row(
        children: [
          Icon(
            Icons.location_on_outlined,
            size: 14,
            color: colorScheme.onSurfaceVariant,
          ),
          const SizedBox(width: 4),
          Expanded(
            child: Text(
              reserva.location,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
      trailing: Text(
        timeFormat.format(reserva.date),
        style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: colorScheme.onSurfaceVariant,
            ),
      ),
    );
  }
}

/// Sheet de notificaciones
class _NotificationsSheet extends ConsumerWidget {
  const _NotificationsSheet();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notificationsAsync = ref.watch(userNotificationsProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      minChildSize: 0.4,
      maxChildSize: 0.9,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: colorScheme.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            children: [
              // Handle
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: colorScheme.onSurfaceVariant.withOpacity(0.3),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              // Titulo
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Text(
                      'Notificaciones',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const Spacer(),
                    TextButton(
                      onPressed: () {
                        Haptics.light();
                        // Marcar todas como leidas
                      },
                      child: const Text('Marcar leidas'),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              // Lista de notificaciones
              Expanded(
                child: notificationsAsync.when(
                  data: (notifications) {
                    if (notifications.isEmpty) {
                      return Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.notifications_none,
                              size: 64,
                              color: colorScheme.onSurfaceVariant.withOpacity(0.5),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              'Sin notificaciones',
                              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                                    color: colorScheme.onSurfaceVariant,
                                  ),
                            ),
                          ],
                        ),
                      );
                    }

                    return ListView.separated(
                      controller: scrollController,
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      itemCount: notifications.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final notification = notifications[index];
                        return ListTile(
                          leading: CircleAvatar(
                            backgroundColor: notification.isRead
                                ? colorScheme.surfaceContainerHighest
                                : colorScheme.primaryContainer,
                            child: Icon(
                              _getIconForNotificationType(notification.type),
                              color: notification.isRead
                                  ? colorScheme.onSurfaceVariant
                                  : colorScheme.primary,
                              size: 20,
                            ),
                          ),
                          title: Text(
                            notification.title,
                            style: TextStyle(
                              fontWeight: notification.isRead ? FontWeight.normal : FontWeight.bold,
                            ),
                          ),
                          subtitle: Text(
                            notification.body,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          trailing: Text(
                            _formatNotificationTime(notification.createdAt),
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                  color: colorScheme.onSurfaceVariant,
                                ),
                          ),
                          onTap: () {
                            Haptics.light();
                            // Navegar al detalle
                          },
                        );
                      },
                    );
                  },
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (_, __) => const Center(child: Text('Error al cargar notificaciones')),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  IconData _getIconForNotificationType(String type) {
    final typeIcons = {
      'reservation': Icons.calendar_today,
      'message': Icons.message,
      'payment': Icons.payment,
      'promotion': Icons.local_offer,
      'system': Icons.info,
    };
    return typeIcons[type] ?? Icons.notifications;
  }

  String _formatNotificationTime(DateTime time) {
    final now = DateTime.now();
    final difference = now.difference(time);

    if (difference.inMinutes < 60) {
      return '${difference.inMinutes}m';
    } else if (difference.inHours < 24) {
      return '${difference.inHours}h';
    } else if (difference.inDays < 7) {
      return '${difference.inDays}d';
    }
    return DateFormat('d/M').format(time);
  }
}

/// Genera actividades de ejemplo para cuando no hay endpoint
List<UserActivity> _getExampleActivities() {
  return [
    UserActivity(
      id: '1',
      title: 'Reserva confirmada',
      description: 'Tu reserva para el 15 de febrero ha sido confirmada',
      timestamp: DateTime.now().subtract(const Duration(hours: 2)),
      iconName: 'check_circle',
      colorHex: '#4CAF50',
      type: 'reservation',
      actionLabel: 'Ver reserva',
      actionRoute: 'my_tickets',
    ),
    UserActivity(
      id: '2',
      title: 'Nuevo pedido disponible',
      description: 'Grupo de Consumo Ecologico tiene nuevos productos',
      timestamp: DateTime.now().subtract(const Duration(days: 1)),
      iconName: 'shopping_basket',
      colorHex: '#FF9800',
      type: 'purchase',
      actionLabel: 'Ver pedido',
      actionRoute: 'grupos_consumo',
    ),
    UserActivity(
      id: '3',
      title: 'Servicio intercambiado',
      description: 'Has ganado 2 horas en Banco de Tiempo',
      timestamp: DateTime.now().subtract(const Duration(days: 3)),
      iconName: 'volunteer_activism',
      colorHex: '#009688',
      type: 'service',
    ),
  ];
}
