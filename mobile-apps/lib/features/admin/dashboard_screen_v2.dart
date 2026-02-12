import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/admin_modules_provider.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/widgets/chart_widgets.dart';
import '../../core/api/api_client.dart';
import 'qr_scanner_screen.dart';
import 'export_screen.dart';
import 'stats_screen.dart';
import 'calendar_view_screen.dart';
import 'customers_screen.dart';
import 'manual_customers_screen.dart';
import 'escalated_chats_screen.dart';
import 'camps/camps_management_screen.dart';

/// Modelo para módulo con métricas
class _ModuleMetric {
  final String id;
  final String title;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color color;
  final VoidCallback? onTap;

  const _ModuleMetric({
    required this.id,
    required this.title,
    required this.value,
    this.subtitle,
    required this.icon,
    required this.color,
    this.onTap,
  });
}

/// Modelo para notificación
class _Notification {
  final String id;
  final String title;
  final String message;
  final String time;
  final IconData icon;
  final Color color;
  final bool isRead;

  const _Notification({
    required this.id,
    required this.title,
    required this.message,
    required this.time,
    required this.icon,
    required this.color,
    this.isRead = false,
  });
}

/// Provider mejorado para métricas de módulos
final adminModuleMetricsProviderV2 = FutureProvider<List<_ModuleMetric>>((ref) async {
  final api = ref.read(apiClientProvider);
  final modules = await ref.watch(adminModulesProvider.future);
  final metrics = <_ModuleMetric>[];

  try {
    // Grupos de Consumo
    if (modules.contains('grupos_consumo') || modules.contains('grupos-consumo')) {
      final pedidos = await api.getGruposConsumoPedidos(estado: 'abierto', perPage: 50, page: 1);
      final count = pedidos.success && pedidos.data != null
          ? (pedidos.data!['data'] as List<dynamic>? ?? []).length
          : 0;
      metrics.add(_ModuleMetric(
        id: 'grupos_consumo',
        title: 'Pedidos Abiertos',
        value: count.toString(),
        subtitle: count > 0 ? 'Requieren atención' : 'Todo al día',
        icon: Icons.shopping_basket_outlined,
        color: count > 0 ? Colors.orange : Colors.green,
      ));
    }

    // Banco de Tiempo
    if (modules.contains('banco_tiempo') || modules.contains('banco-tiempo')) {
      final servicios = await api.getBancoTiempoServicios(limite: 50, pagina: 1);
      final count = servicios.success && servicios.data != null
          ? (servicios.data!['servicios'] as List<dynamic>? ?? []).length
          : 0;
      metrics.add(_ModuleMetric(
        id: 'banco_tiempo',
        title: 'Servicios Activos',
        value: count.toString(),
        icon: Icons.volunteer_activism_outlined,
        color: Colors.teal,
      ));
    }

    // Marketplace
    if (modules.contains('marketplace')) {
      final anuncios = await api.getMarketplaceAnuncios(limite: 50, pagina: 1);
      final count = anuncios.success && anuncios.data != null
          ? (anuncios.data!['anuncios'] as List<dynamic>? ?? []).length
          : 0;
      metrics.add(_ModuleMetric(
        id: 'marketplace',
        title: 'Anuncios Activos',
        value: count.toString(),
        icon: Icons.storefront_outlined,
        color: Colors.orange,
      ));
    }

    // Eventos
    if (modules.contains('eventos')) {
      // Aquí podrías hacer una llamada a eventos si existe el endpoint
      metrics.add(_ModuleMetric(
        id: 'eventos',
        title: 'Próximos Eventos',
        value: '5', // Mock - reemplazar con API real
        icon: Icons.event_outlined,
        color: Colors.pink,
      ));
    }

    // Incidencias
    if (modules.contains('incidencias')) {
      metrics.add(_ModuleMetric(
        id: 'incidencias',
        title: 'Incidencias Abiertas',
        value: '3', // Mock - reemplazar con API real
        subtitle: 'Pendientes de resolver',
        icon: Icons.report_problem_outlined,
        color: Colors.red,
      ));
    }

    // Socios
    if (modules.contains('socios')) {
      metrics.add(_ModuleMetric(
        id: 'socios',
        title: 'Socios Activos',
        value: '42', // Mock - reemplazar con API real
        icon: Icons.people_outlined,
        color: Colors.indigo,
      ));
    }
  } catch (e) {
    debugPrint('[AdminModuleMetricsV2] Error: $e');
  }

  return metrics;
});

/// Provider para notificaciones (mock - reemplazar con API real)
final adminNotificationsProvider = FutureProvider<List<_Notification>>((ref) async {
  // Mock data - en producción vendría del API
  return [
    _Notification(
      id: '1',
      title: 'Nueva reserva',
      message: 'Reserva para Mesa 5 - 4 personas',
      time: 'Hace 5 min',
      icon: Icons.calendar_today,
      color: Colors.blue,
      isRead: false,
    ),
    _Notification(
      id: '2',
      title: 'Pedido completado',
      message: 'Grupo de Consumo Ecológico',
      time: 'Hace 1 hora',
      icon: Icons.check_circle,
      color: Colors.green,
      isRead: false,
    ),
    _Notification(
      id: '3',
      title: 'Chat escalado',
      message: 'Cliente requiere atención humana',
      time: 'Hace 2 horas',
      icon: Icons.support_agent,
      color: Colors.orange,
      isRead: true,
    ),
  ];
});

/// Provider para datos de gráficos del admin
final adminChartDataProvider = FutureProvider.family<List<ChartData>, String>(
  (ref, type) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.getDashboardCharts(type: type, days: 7);
      if (response.success && response.data != null) {
        final List<dynamic> chartData = response.data!['data'] as List? ?? [];
        return chartData.map((item) => ChartData.fromJson(item as Map<String, dynamic>)).toList();
      }
    } catch (e) {
      debugPrint('[AdminChartData] Error getting $type chart: $e');
    }

    return [];
  },
);

/// Dashboard V2 para administradores con diseño mejorado
class DashboardScreenV2 extends ConsumerWidget {
  final VoidCallback? onNavigateToReservations;
  final VoidCallback? onNavigateToChat;

  const DashboardScreenV2({
    super.key,
    this.onNavigateToReservations,
    this.onNavigateToChat,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final dashboardAsync = ref.watch(dashboardProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      backgroundColor: colorScheme.surface,
      appBar: AppBar(
        title: Text(i18n.dashboard2938c7),
        actions: [
          // Notificaciones
          Consumer(
            builder: (context, ref, child) {
              final notificationsAsync = ref.watch(adminNotificationsProvider);
              return notificationsAsync.when(
                data: (notifications) {
                  final unreadCount = notifications.where((n) => !n.isRead).length;
                  return Stack(
                    children: [
                      IconButton(
                        icon: const Icon(Icons.notifications_outlined),
                        onPressed: () => _showNotifications(context, notifications),
                      ),
                      if (unreadCount > 0)
                        Positioned(
                          right: 8,
                          top: 8,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                            ),
                            constraints: const BoxConstraints(
                              minWidth: 16,
                              minHeight: 16,
                            ),
                            child: Text(
                              unreadCount.toString(),
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ),
                        ),
                    ],
                  );
                },
                loading: () => IconButton(
                  icon: const Icon(Icons.notifications_outlined),
                  onPressed: () {},
                ),
                error: (_, __) => const SizedBox.shrink(),
              );
            },
          ),
          IconButton(
            onPressed: () {
              ref.invalidate(dashboardProvider);
              ref.invalidate(adminModuleMetricsProviderV2);
            },
            icon: const Icon(Icons.refresh),
            tooltip: i18n.actualizar2e7be1,
          ),
        ],
      ),
      body: dashboardAsync.when(
        data: (data) => RefreshableList(
          onRefresh: () async {
            ref.invalidate(dashboardProvider);
            ref.invalidate(adminModuleMetricsProviderV2);
          },
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Saludo y fecha
                _GreetingHeader(date: data.today.date),

                const SizedBox(height: 24),

                // Estadísticas principales de hoy
                Text(
                  i18n.hoy,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                _TodayStatsGrid(todayData: data.today),

                const SizedBox(height: 24),

                // Módulos activos (métricas dinámicas)
                Text(
                  i18n.adminModulesDashboardTitle,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                _ModuleMetricsGridV2(),

                const SizedBox(height: 24),

                // Accesos rápidos mejorados
                Text(
                  i18n.accesosRapidos,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                _QuickActionsV2(
                  onNavigateToReservations: onNavigateToReservations,
                  onNavigateToChat: onNavigateToChat,
                ),

                const SizedBox(height: 24),

                // Gráficos de rendimiento
                _AdminChartsSection(),

                const SizedBox(height: 24),

                // Estadísticas de la semana
                Text(
                  i18n.estaSemana,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                _WeekStats(weekData: data.week),

                const SizedBox(height: 24),

                // Estadísticas del mes
                Text(
                  i18n.esteMes,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 12),
                _MonthStats(monthData: data.month),

                const SizedBox(height: 24),
              ],
            ),
          ),
        ),
        loading: () => LoadingScreen(message: i18n.loadingDashboard),
        error: (error, stack) => ErrorScreen(
          message: i18n.dashboardLoadError,
          onRetry: () => ref.invalidate(dashboardProvider),
        ),
      ),
    );
  }

  void _showNotifications(BuildContext context, List<_Notification> notifications) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Notificaciones',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            if (notifications.isEmpty)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(32),
                  child: Text('No hay notificaciones'),
                ),
              )
            else
              ...notifications.map((notif) => ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: notif.color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(notif.icon, color: notif.color),
                ),
                title: Text(
                  notif.title,
                  style: TextStyle(
                    fontWeight: notif.isRead ? FontWeight.normal : FontWeight.bold,
                  ),
                ),
                subtitle: Text(notif.message),
                trailing: Text(
                  notif.time,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              )),
          ],
        ),
      ),
    );
  }
}

/// Header de saludo
class _GreetingHeader extends StatelessWidget {
  final String date;

  const _GreetingHeader({required this.date});

  @override
  Widget build(BuildContext context) {
    final hour = DateTime.now().hour;
    final colorScheme = Theme.of(context).colorScheme;

    String greeting;
    IconData icon;

    if (hour < 12) {
      greeting = 'Buenos días';
      icon = Icons.wb_sunny;
    } else if (hour < 19) {
      greeting = 'Buenas tardes';
      icon = Icons.wb_twilight;
    } else {
      greeting = 'Buenas noches';
      icon = Icons.nightlight;
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            colorScheme.primary,
            colorScheme.secondary,
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
              Icon(icon, color: Colors.white, size: 28),
              const SizedBox(width: 12),
              Text(
                greeting,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            _formatDate(date, context),
            style: Theme.of(context).textTheme.bodyLarge?.copyWith(
              color: Colors.white.withOpacity(0.9),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(String date, BuildContext context) {
    try {
      final dateObj = DateTime.parse(date);
      final locale = Localizations.localeOf(context).toLanguageTag();
      return DateFormat('EEEE, d \'de\' MMMM', locale).format(dateObj);
    } catch (e) {
      return date;
    }
  }
}

/// Grid de estadísticas de hoy
class _TodayStatsGrid extends StatelessWidget {
  final dynamic todayData;

  const _TodayStatsGrid({required this.todayData});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.3,
      children: [
        _ModernStatCard(
          title: i18n.dashboardReservationsLabel,
          value: '${todayData.reservations}',
          icon: Icons.calendar_today,
          color: Colors.blue,
        ),
        _ModernStatCard(
          title: i18n.dashboardCheckinsLabel,
          value: '${todayData.checkins}',
          subtitle: i18n.dashboardPending(todayData.pendingCheckins),
          icon: Icons.check_circle,
          color: Colors.green,
        ),
      ],
    );
  }
}

/// Card de estadística moderno
class _ModernStatCard extends StatelessWidget {
  final String title;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color color;

  const _ModernStatCard({
    required this.title,
    required this.value,
    this.subtitle,
    required this.icon,
    required this.color,
  });

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
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icon, color: color, size: 20),
                ),
              ],
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  value,
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  title,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                if (subtitle != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    subtitle!,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: color.withOpacity(0.8),
                      fontWeight: FontWeight.w500,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Grid de métricas de módulos V2
class _ModuleMetricsGridV2 extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final metricsAsync = ref.watch(adminModuleMetricsProviderV2);

    return metricsAsync.when(
      data: (metrics) {
        if (metrics.isEmpty) {
          return Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Icon(Icons.info_outline, color: Theme.of(context).colorScheme.primary),
                  const SizedBox(width: 12),
                  Expanded(child: Text(i18n.adminModulesDashboardEmpty)),
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
          children: metrics
              .map((metric) => _ModernStatCard(
                    title: metric.title,
                    value: metric.value,
                    subtitle: metric.subtitle,
                    icon: metric.icon,
                    color: metric.color,
                  ))
              .toList(),
        );
      },
      loading: () => const SizedBox(
        height: 120,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => Text(i18n.adminModulesDashboardEmpty),
    );
  }
}

/// Accesos rápidos V2 mejorado
class _QuickActionsV2 extends ConsumerWidget {
  final VoidCallback? onNavigateToReservations;
  final VoidCallback? onNavigateToChat;

  const _QuickActionsV2({
    this.onNavigateToReservations,
    this.onNavigateToChat,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final modulesAsync = ref.watch(adminModulesProvider);

    return modulesAsync.when(
      data: (modules) {
        final hasReservations = modules.any((m) =>
            m == 'reservas' || m == 'reservations' || m == 'experiences');
        final hasChat = modules.any((m) => m.contains('chat'));
        final hasCamps = modules.any((m) => m.contains('camp'));

        return Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _QuickActionButton(
              icon: Icons.qr_code_scanner,
              label: i18n.dashboardScanQr,
              color: Colors.purple,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => const QRScannerScreen()),
                );
              },
            ),
            if (hasReservations)
              _QuickActionButton(
                icon: Icons.calendar_today,
                label: i18n.dashboardViewReservations,
                color: Colors.blue,
                onTap: onNavigateToReservations ?? () {},
              ),
            if (hasChat)
              _QuickActionButton(
                icon: Icons.smart_toy,
                label: i18n.dashboardChatIa,
                color: Colors.teal,
                onTap: onNavigateToChat ?? () {},
              ),
            _QuickActionButton(
              icon: Icons.bar_chart,
              label: i18n.dashboardStats,
              color: Colors.orange,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => const StatsScreen()),
                );
              },
            ),
            if (hasReservations)
              _QuickActionButton(
                icon: Icons.calendar_month,
                label: i18n.dashboardCalendar,
                color: Colors.pink,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const CalendarViewScreen()),
                  );
                },
              ),
            _QuickActionButton(
              icon: Icons.people,
              label: i18n.dashboardCustomers,
              color: Colors.indigo,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => const CustomersScreen()),
                );
              },
            ),
            if (hasChat)
              _QuickActionButton(
                icon: Icons.support_agent,
                label: i18n.dashboardEscalatedChats,
                color: Colors.red,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const EscalatedChatsScreen()),
                  );
                },
              ),
            if (hasCamps)
              _QuickActionButton(
                icon: Icons.cabin,
                label: i18n.dashboardCamps,
                color: Colors.brown,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const CampsManagementScreen()),
                  );
                },
              ),
          ],
        );
      },
      loading: () => const SizedBox(
        height: 64,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => const SizedBox.shrink(),
    );
  }
}

/// Botón de acción rápida mejorado
class _QuickActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _QuickActionButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color.withOpacity(0.1),
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 20, color: color),
              const SizedBox(width: 8),
              Text(
                label,
                style: TextStyle(
                  color: color,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Estadísticas de la semana
class _WeekStats extends StatelessWidget {
  final dynamic weekData;

  const _WeekStats({required this.weekData});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return _ModernStatCard(
      title: i18n.dashboardTotalReservationsLabel,
      value: '${weekData.reservations}',
      icon: Icons.event_note,
      color: Colors.purple,
    );
  }
}

/// Estadísticas del mes
class _MonthStats extends StatelessWidget {
  final dynamic monthData;

  const _MonthStats({required this.monthData});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return _ModernStatCard(
      title: i18n.dashboardRevenueLabel,
      value: monthData.formattedRevenue,
      icon: Icons.euro,
      color: Colors.amber,
    );
  }
}

/// Sección de gráficos para admin
class _AdminChartsSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Análisis y Tendencias',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),

        // Gráfico de actividad diaria
        _AdminActivityBarChartWidget(),

        const SizedBox(height: 16),

        // Gráfico de tendencias
        _AdminTrendsLineChartWidget(),

        const SizedBox(height: 16),

        // Gráfico de distribución por módulo
        _AdminDistributionPieChartWidget(),
      ],
    );
  }
}

/// Widget de gráfico de barras para admin
class _AdminActivityBarChartWidget extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final chartDataAsync = ref.watch(adminChartDataProvider('activity'));

    return chartDataAsync.when(
      data: (data) {
        if (data.isEmpty) {
          return const SizedBox.shrink();
        }
        return ActivityBarChart(
          data: data,
          title: 'Reservaciones por día (últimos 7 días)',
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
        debugPrint('[AdminActivityBarChart] Error: $error');
        return const SizedBox.shrink();
      },
    );
  }
}

/// Widget de gráfico de líneas de tendencias para admin
class _AdminTrendsLineChartWidget extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final chartDataAsync = ref.watch(adminChartDataProvider('trends'));

    return chartDataAsync.when(
      data: (data) {
        if (data.isEmpty) {
          return const SizedBox.shrink();
        }
        return TrendsLineChart(
          data: data,
          title: 'Tendencia de ingresos (últimos 7 días)',
          lineColor: Colors.green,
        );
      },
      loading: () => SizedBox(
        height: 200,
        child: Card(
          child: Center(child: CircularProgressIndicator()),
        ),
      ),
      error: (error, stack) {
        debugPrint('[AdminTrendsLineChart] Error: $error');
        return const SizedBox.shrink();
      },
    );
  }
}

/// Widget de gráfico circular de distribución para admin
class _AdminDistributionPieChartWidget extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final chartDataAsync = ref.watch(adminChartDataProvider('distribution'));

    return chartDataAsync.when(
      data: (data) {
        if (data.isEmpty) {
          return const SizedBox.shrink();
        }
        return DistributionPieChart(
          data: data,
          title: 'Distribución de uso por módulo',
        );
      },
      loading: () => SizedBox(
        height: 200,
        child: Card(
          child: Center(child: CircularProgressIndicator()),
        ),
      ),
      error: (error, stack) {
        debugPrint('[AdminDistributionPieChart] Error: $error');
        return const SizedBox.shrink();
      },
    );
  }
}
