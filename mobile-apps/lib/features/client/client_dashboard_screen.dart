import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/admin_modules_provider.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/widgets/chart_widgets.dart';

/// Modelo para métricas del usuario
class _UserMetric {
  final String id;
  final String title;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color color;

  const _UserMetric({
    required this.id,
    required this.title,
    required this.value,
    this.subtitle,
    required this.icon,
    required this.color,
  });
}

/// Modelo para acción rápida
class _QuickAction {
  final String id;
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _QuickAction({
    required this.id,
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });
}

/// Modelo para item de actividad
class _ActivityItem {
  final String id;
  final String title;
  final String subtitle;
  final String time;
  final IconData icon;
  final Color color;

  const _ActivityItem({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.time,
    required this.icon,
    required this.color,
  });
}

/// Provider para métricas del usuario
final userMetricsProvider = FutureProvider<List<_UserMetric>>((ref) async {
  final api = ref.read(apiClientProvider);
  final metrics = <_UserMetric>[];

  try {
    // Obtener mis pedidos (WooCommerce)
    final pedidos = await api.getWooPedidos(limite: 100);
    if (pedidos.success && pedidos.data != null) {
      final list = pedidos.data!['pedidos'] as List? ?? [];
      metrics.add(_UserMetric(
        id: 'my_orders',
        title: 'Mis Pedidos',
        value: list.length.toString(),
        icon: Icons.shopping_cart_outlined,
        color: Colors.blue,
      ));
    }

    // Obtener módulos activos para cargar más métricas
    final modulesAsync = await ref.watch(adminModulesProvider.future);

    // Grupos de Consumo
    if (modulesAsync.contains('grupos_consumo') || modulesAsync.contains('grupos-consumo')) {
      final pedidos = await api.getGruposConsumoPedidos(estado: 'abierto', perPage: 10, page: 1);
      if (pedidos.success && pedidos.data != null) {
        final list = pedidos.data!['data'] as List? ?? [];
        metrics.add(_UserMetric(
          id: 'gc_pedidos',
          title: 'Pedidos Activos',
          value: list.length.toString(),
          icon: Icons.shopping_basket_outlined,
          color: Colors.green,
        ));
      }
    }

    // Banco de Tiempo
    if (modulesAsync.contains('banco_tiempo') || modulesAsync.contains('banco-tiempo')) {
      final servicios = await api.getBancoTiempoServicios(limite: 50, pagina: 1);
      if (servicios.success && servicios.data != null) {
        final list = servicios.data!['servicios'] as List? ?? [];
        metrics.add(_UserMetric(
          id: 'bt_servicios',
          title: 'Servicios Disponibles',
          value: list.length.toString(),
          icon: Icons.volunteer_activism_outlined,
          color: Colors.teal,
        ));
      }
    }

    // Marketplace
    if (modulesAsync.contains('marketplace')) {
      final anuncios = await api.getMarketplaceAnuncios(limite: 50, pagina: 1);
      if (anuncios.success && anuncios.data != null) {
        final list = anuncios.data!['anuncios'] as List? ?? [];
        metrics.add(_UserMetric(
          id: 'marketplace_anuncios',
          title: 'Anuncios Activos',
          value: list.length.toString(),
          icon: Icons.storefront_outlined,
          color: Colors.orange,
        ));
      }
    }
  } catch (e) {
    debugPrint('[UserMetrics] Error: $e');
  }

  return metrics;
});

/// Provider para datos de gráficos del usuario
final userChartDataProvider = FutureProvider.family<List<ChartData>, String>(
  (ref, type) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.getDashboardCharts(type: type, days: 7);
      if (response.success && response.data != null) {
        final List<dynamic> chartData = response.data!['data'] as List? ?? [];
        return chartData.map((item) => ChartData.fromJson(item as Map<String, dynamic>)).toList();
      }
    } catch (e) {
      debugPrint('[UserChartData] Error getting $type chart: $e');
    }

    return [];
  },
);

/// Dashboard para clientes - Home Screen moderno
class ClientDashboardScreen extends ConsumerWidget {
  final Function(String)? onNavigateToModule;

  const ClientDashboardScreen({
    super.key,
    this.onNavigateToModule,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      backgroundColor: colorScheme.surface,
      body: RefreshableList(
        onRefresh: () async {
          ref.invalidate(userMetricsProvider);
        },
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Saludo personalizado
              _GreetingSection(),

              const SizedBox(height: 24),

              // Métricas del usuario
              _MetricsSection(),

              const SizedBox(height: 24),

              // Gráficos de actividad
              _ChartsSection(),

              const SizedBox(height: 24),

              // Accesos rápidos
              Text(
                i18n.accesosRapidos,
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 12),
              _QuickActionsSection(onNavigateToModule: onNavigateToModule),

              const SizedBox(height: 24),

              // Actividad reciente
              _ActivitySection(),

              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }
}

/// Sección de saludo personalizado
class _GreetingSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;
    final hour = DateTime.now().hour;

    String greeting;
    IconData greetingIcon;

    if (hour < 12) {
      greeting = 'Buenos días';
      greetingIcon = Icons.wb_sunny_outlined;
    } else if (hour < 19) {
      greeting = 'Buenas tardes';
      greetingIcon = Icons.wb_twilight_outlined;
    } else {
      greeting = 'Buenas noches';
      greetingIcon = Icons.nightlight_outlined;
    }

    return Container(
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
              Icon(
                greetingIcon,
                color: colorScheme.onPrimaryContainer,
                size: 28,
              ),
              const SizedBox(width: 12),
              Text(
                greeting,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: colorScheme.onPrimaryContainer,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            _formatDate(DateTime.now(), context),
            style: Theme.of(context).textTheme.bodyLarge?.copyWith(
              color: colorScheme.onPrimaryContainer.withOpacity(0.8),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(DateTime date, BuildContext context) {
    final locale = Localizations.localeOf(context).toLanguageTag();
    return DateFormat('EEEE, d \'de\' MMMM', locale).format(date);
  }
}

/// Sección de métricas del usuario
class _MetricsSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final metricsAsync = ref.watch(userMetricsProvider);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Mi Resumen',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),
        metricsAsync.when(
          data: (metrics) {
            if (metrics.isEmpty) {
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(Icons.info_outline, color: Theme.of(context).colorScheme.primary),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text('No hay actividad reciente'),
                      ),
                    ],
                  ),
                ),
              );
            }

            return GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 1.3,
              children: metrics.map((metric) => _MetricCard(metric: metric)).toList(),
            );
          },
          loading: () => const Center(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: CircularProgressIndicator(),
            ),
          ),
          error: (error, stack) => Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Text('Error al cargar métricas'),
            ),
          ),
        ),
      ],
    );
  }
}

/// Card individual de métrica
class _MetricCard extends StatelessWidget {
  final _UserMetric metric;

  const _MetricCard({required this.metric});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(
          color: colorScheme.outlineVariant,
          width: 1,
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: metric.color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    metric.icon,
                    color: metric.color,
                    size: 20,
                  ),
                ),
              ],
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  metric.value,
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: colorScheme.onSurface,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  metric.title,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Sección de accesos rápidos
class _QuickActionsSection extends ConsumerWidget {
  final Function(String)? onNavigateToModule;

  const _QuickActionsSection({this.onNavigateToModule});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final modulesAsync = ref.watch(adminModulesProvider);

    return modulesAsync.when(
      data: (modules) {
        final actions = _buildQuickActions(context, modules);

        return Wrap(
          spacing: 12,
          runSpacing: 12,
          children: actions.map((action) => _QuickActionChip(action: action)).toList(),
        );
      },
      loading: () => const SizedBox(
        height: 64,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => const SizedBox.shrink(),
    );
  }

  List<_QuickAction> _buildQuickActions(BuildContext context, List<String> modules) {
    final actions = <_QuickAction>[];

    // Reservas (siempre disponible)
    actions.add(_QuickAction(
      id: 'reservations',
      label: 'Reservar',
      icon: Icons.calendar_today,
      color: Colors.blue,
      onTap: () => onNavigateToModule?.call('reservations'),
    ));

    // Chat IA
    actions.add(_QuickAction(
      id: 'chat',
      label: 'Chat IA',
      icon: Icons.smart_toy_outlined,
      color: Colors.purple,
      onTap: () => onNavigateToModule?.call('chat'),
    ));

    // Grupos de Consumo
    if (modules.contains('grupos_consumo') || modules.contains('grupos-consumo')) {
      actions.add(_QuickAction(
        id: 'grupos_consumo',
        label: 'Grupos Consumo',
        icon: Icons.shopping_basket,
        color: Colors.green,
        onTap: () => onNavigateToModule?.call('grupos_consumo'),
      ));
    }

    // Banco de Tiempo
    if (modules.contains('banco_tiempo') || modules.contains('banco-tiempo')) {
      actions.add(_QuickAction(
        id: 'banco_tiempo',
        label: 'Banco Tiempo',
        icon: Icons.volunteer_activism,
        color: Colors.teal,
        onTap: () => onNavigateToModule?.call('banco_tiempo'),
      ));
    }

    // Marketplace
    if (modules.contains('marketplace')) {
      actions.add(_QuickAction(
        id: 'marketplace',
        label: 'Marketplace',
        icon: Icons.storefront,
        color: Colors.orange,
        onTap: () => onNavigateToModule?.call('marketplace'),
      ));
    }

    // Eventos
    if (modules.contains('eventos')) {
      actions.add(_QuickAction(
        id: 'eventos',
        label: 'Eventos',
        icon: Icons.event,
        color: Colors.pink,
        onTap: () => onNavigateToModule?.call('eventos'),
      ));
    }

    return actions;
  }
}

/// Chip de acción rápida
class _QuickActionChip extends StatelessWidget {
  final _QuickAction action;

  const _QuickActionChip({required this.action});

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      avatar: Icon(
        action.icon,
        size: 18,
        color: action.color,
      ),
      label: Text(action.label),
      onPressed: action.onTap,
      side: BorderSide(
        color: action.color.withOpacity(0.3),
      ),
    );
  }
}

/// Sección de actividad reciente
class _ActivitySection extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    // Mock data - en producción vendría del API
    final activities = <_ActivityItem>[
      _ActivityItem(
        id: '1',
        title: 'Reserva confirmada',
        subtitle: 'Tu reserva para el 15 de febrero',
        time: 'Hace 2 horas',
        icon: Icons.check_circle,
        color: Colors.green,
      ),
      _ActivityItem(
        id: '2',
        title: 'Nuevo pedido disponible',
        subtitle: 'Grupo de Consumo Ecológico',
        time: 'Ayer',
        icon: Icons.shopping_basket,
        color: Colors.orange,
      ),
      _ActivityItem(
        id: '3',
        title: 'Servicio intercambiado',
        subtitle: 'Has ganado 2 horas en Banco Tiempo',
        time: 'Hace 3 días',
        icon: Icons.volunteer_activism,
        color: Colors.teal,
      ),
    ];

    if (activities.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Actividad Reciente',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),
        ...activities.map((activity) => _ActivityItemCard(item: activity)),
      ],
    );
  }
}

/// Card de item de actividad
class _ActivityItemCard extends StatelessWidget {
  final _ActivityItem item;

  const _ActivityItemCard({required this.item});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: item.color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(
            item.icon,
            color: item.color,
            size: 20,
          ),
        ),
        title: Text(
          item.title,
          style: const TextStyle(fontWeight: FontWeight.w600),
        ),
        subtitle: Text(item.subtitle),
        trailing: Text(
          item.time,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }
}

/// Sección de gráficos de actividad
class _ChartsSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colorScheme = Theme.of(context).colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Mi Actividad',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),

        // Gráfico de actividad semanal (barras)
        _ActivityBarChartWidget(),

        const SizedBox(height: 16),

        // Gráfico de distribución por módulo (pie)
        _DistributionPieChartWidget(),
      ],
    );
  }
}

/// Widget de gráfico de barras de actividad
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
          title: 'Actividad de los últimos 7 días',
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

/// Widget de gráfico circular de distribución
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
          title: 'Distribución por módulo',
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
