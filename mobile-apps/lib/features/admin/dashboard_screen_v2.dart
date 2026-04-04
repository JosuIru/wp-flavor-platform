import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/admin_modules_provider.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/widgets/chart_widgets.dart';
import '../../core/widgets/flavor_state_widgets.dart';
import 'qr_scanner_screen.dart';
import 'stats_screen.dart';
import 'calendar_view_screen.dart';
import 'customers_screen.dart';
import 'escalated_chats_screen.dart';
import 'camps/camps_management_screen.dart';

part 'dashboard_screen_v2_widgets.dart';

/// Modelo para módulo con métricas
class _ModuleMetric {
  final String id;
  final String title;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color color;
  const _ModuleMetric({
    required this.id,
    required this.title,
    required this.value,
    this.subtitle,
    required this.icon,
    required this.color,
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
        title: '',
        value: count.toString(),
        subtitle: '',
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
        title: '',
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
        title: '',
        value: count.toString(),
        icon: Icons.storefront_outlined,
        color: Colors.orange,
      ));
    }

    // Eventos
    if (modules.contains('eventos')) {
      // Aquí podrías hacer una llamada a eventos si existe el endpoint
      metrics.add(const _ModuleMetric(
        id: 'eventos',
        title: '',
        value: '5', // Mock - reemplazar con API real
        icon: Icons.event_outlined,
        color: Colors.pink,
      ));
    }

    // Incidencias
    if (modules.contains('incidencias')) {
      metrics.add(const _ModuleMetric(
        id: 'incidencias',
        title: '',
        value: '3', // Mock - reemplazar con API real
        subtitle: '',
        icon: Icons.report_problem_outlined,
        color: Colors.red,
      ));
    }

    // Socios
    if (modules.contains('socios')) {
      metrics.add(const _ModuleMetric(
        id: 'socios',
        title: '',
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
    const _Notification(
      id: '1',
      title: '',
      message: '',
      time: '',
      icon: Icons.calendar_today,
      color: Colors.blue,
      isRead: false,
    ),
    const _Notification(
      id: '2',
      title: '',
      message: '',
      time: '',
      icon: Icons.check_circle,
      color: Colors.green,
      isRead: false,
    ),
    const _Notification(
      id: '3',
      title: '',
      message: '',
      time: '',
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
    final i18n = AppLocalizations.of(context);
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
                            decoration: const BoxDecoration(
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

  String _notificationTitle(BuildContext context, _Notification notification) {
    final i18n = AppLocalizations.of(context);
    switch (notification.id) {
      case '1':
        return i18n.adminNotificationNewReservation;
      case '2':
        return i18n.adminNotificationOrderCompleted;
      case '3':
        return i18n.adminNotificationEscalatedChat;
      default:
        return notification.title;
    }
  }

  String _notificationMessage(BuildContext context, _Notification notification) {
    final i18n = AppLocalizations.of(context);
    switch (notification.id) {
      case '1':
        return i18n.adminNotificationTableReservation;
      case '2':
        return i18n.adminNotificationConsumptionGroup;
      case '3':
        return i18n.adminNotificationHumanAttentionRequired;
      default:
        return notification.message;
    }
  }

  String _notificationTime(BuildContext context, _Notification notification) {
    final i18n = AppLocalizations.of(context);
    switch (notification.id) {
      case '1':
        return i18n.adminNotificationFiveMinutesAgo;
      case '2':
        return i18n.adminNotificationOneHourAgo;
      case '3':
        return i18n.adminNotificationTwoHoursAgo;
      default:
        return notification.time;
    }
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
              AppLocalizations.of(context).notificationsTitle,
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            if (notifications.isEmpty)
              Center(
                child: Padding(
                  padding: const EdgeInsets.all(32),
                  child: Text(AppLocalizations.of(context).notificationsEmpty),
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
                  _notificationTitle(context, notif),
                  style: TextStyle(
                    fontWeight: notif.isRead ? FontWeight.normal : FontWeight.bold,
                  ),
                ),
                subtitle: Text(_notificationMessage(context, notif)),
                trailing: Text(
                  _notificationTime(context, notif),
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
