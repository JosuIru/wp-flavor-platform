import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/admin_modules_provider.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/api/api_client.dart';
import '../../core/utils/haptics.dart';
import 'qr_scanner_screen.dart';
import 'export_screen.dart';
import 'stats_screen.dart';
import 'calendar_view_screen.dart';
import 'customers_screen.dart';
import 'manual_customers_screen.dart';
import 'escalated_chats_screen.dart';
import 'camps/camps_management_screen.dart';

class _ModuleMetric {
  final String id;
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const _ModuleMetric({
    required this.id,
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });
}

final adminModuleMetricsProvider = FutureProvider<List<_ModuleMetric>>((ref) async {
  final api = ref.read(apiClientProvider);
  final modules = await ref.watch(adminModulesProvider.future);
  final metrics = <_ModuleMetric>[];

  final hasGc = modules.contains('grupos_consumo') || modules.contains('grupos-consumo');
  if (hasGc) {
    final pedidos = await api.getGruposConsumoPedidos(estado: 'abierto', perPage: 50, page: 1);
    final count = pedidos.success && pedidos.data != null
        ? (pedidos.data!['data'] as List<dynamic>? ?? []).length
        : 0;
    metrics.add(_ModuleMetric(
      id: 'grupos_consumo',
      title: 'Pedidos abiertos',
      value: count.toString(),
      icon: Icons.shopping_basket_outlined,
      color: Colors.green,
    ));
  }

  final hasBanco = modules.contains('banco_tiempo') || modules.contains('banco-tiempo');
  if (hasBanco) {
    final servicios = await api.getBancoTiempoServicios(limite: 50, pagina: 1);
    final count = servicios.success && servicios.data != null
        ? (servicios.data!['servicios'] as List<dynamic>? ?? []).length
        : 0;
    metrics.add(_ModuleMetric(
      id: 'banco_tiempo',
      title: 'Servicios activos',
      value: count.toString(),
      icon: Icons.volunteer_activism_outlined,
      color: Colors.teal,
    ));
  }

  final hasMarketplace = modules.contains('marketplace');
  if (hasMarketplace) {
    final anuncios = await api.getMarketplaceAnuncios(limite: 50, pagina: 1);
    final count = anuncios.success && anuncios.data != null
        ? (anuncios.data!['anuncios'] as List<dynamic>? ?? []).length
        : 0;
    metrics.add(_ModuleMetric(
      id: 'marketplace',
      title: 'Anuncios activos',
      value: count.toString(),
      icon: Icons.storefront_outlined,
      color: Colors.orange,
    ));
  }

  return metrics;
});

/// Dashboard para administradores
class DashboardScreen extends ConsumerWidget {
  final VoidCallback? onNavigateToReservations;
  final VoidCallback? onNavigateToChat;

  const DashboardScreen({
    super.key,
    this.onNavigateToReservations,
    this.onNavigateToChat,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final dashboardAsync = ref.watch(dashboardProvider);
    final modulesAsync = ref.watch(adminModulesProvider);
    final modules = modulesAsync.valueOrNull ?? const <String>[];
    final hasReservations = modules.any((m) =>
        m == 'reservas' ||
        m == 'reservations' ||
        m == 'experiences' ||
        m == 'eventos' ||
        m.contains('calendar'));
    final hasChat = modules.any((m) => m.contains('chat'));
    final hasCamps = modules.any((m) => m.contains('camp'));

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.dashboard2938c7),
        actions: [
          Semantics(
            label: i18n.actualizar2e7be1,
            button: true,
            child: IconButton(
              onPressed: () => ref.invalidate(dashboardProvider),
              icon: const Icon(Icons.refresh),
              tooltip: i18n.actualizar2e7be1,
            ),
          ),
        ],
      ),
      body: dashboardAsync.when(
        data: (data) => RefreshableList(
          onRefresh: () async {
            ref.invalidate(dashboardProvider);
          },
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Fecha de hoy
                Text(
                  _formatDate(data.today.date, context),
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 24),

                // Estadísticas de hoy
                Text(AppLocalizations.of(context)!.hoy,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                GridView.count(
                  crossAxisCount: 2,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 12,
                  crossAxisSpacing: 12,
                  childAspectRatio: 1.3,
                  children: [
                    StatCard(
                      title: i18n.dashboardReservationsLabel,
                      value: '${data.today.reservations}',
                      icon: Icons.calendar_today,
                      color: Colors.blue,
                    ),
                    StatCard(
                      title: i18n.dashboardCheckinsLabel,
                      value: '${data.today.checkins}',
                      icon: Icons.check_circle,
                      color: Colors.green,
                      subtitle: i18n.dashboardPending(data.today.pendingCheckins),
                    ),
                  ],
                ),

                const SizedBox(height: 24),

                // Estadísticas de la semana
                Text(AppLocalizations.of(context)!.estaSemana,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                StatCard(
                  title: i18n.dashboardTotalReservationsLabel,
                  value: '${data.week.reservations}',
                  icon: Icons.event_note,
                  color: Colors.purple,
                ),

                const SizedBox(height: 24),

                // Estadísticas del mes
                Text(AppLocalizations.of(context)!.esteMes,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                StatCard(
                  title: i18n.dashboardRevenueLabel,
                  value: data.month.formattedRevenue,
                  icon: Icons.euro,
                  color: Colors.amber,
                ),

                const SizedBox(height: 24),

                // Módulos activos (datos resumidos)
                Text(
                  i18n.adminModulesDashboardTitle,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                _ModuleMetricsSection(),

                const SizedBox(height: 24),

                // Accesos rápidos
                Text(AppLocalizations.of(context)!.accesosRapidos,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 12),
                _QuickActions(
                  onNavigateToReservations: onNavigateToReservations,
                  onNavigateToChat: onNavigateToChat,
                  showReservations: hasReservations,
                  showChat: hasChat,
                  showSummaries: hasReservations,
                  hasReservations: hasReservations,
                  hasChat: hasChat,
                  hasCamps: hasCamps,
                ),
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

  String _formatDate(String date, BuildContext context) {
    try {
      if (date.isEmpty) {
        final now = DateTime.now();
        date = '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
      }
      final dateObj = DateTime.parse(date);
      final locale = Localizations.localeOf(context).toLanguageTag();
      return DateFormat('EEE, d MMMM', locale).format(dateObj);
    } catch (e) {
      return date;
    }
  }
}

class _QuickActions extends StatelessWidget {
  final VoidCallback? onNavigateToReservations;
  final VoidCallback? onNavigateToChat;
  final bool showReservations;
  final bool showChat;
  final bool showSummaries;
  final bool hasReservations;
  final bool hasChat;
  final bool hasCamps;

  const _QuickActions({
    this.onNavigateToReservations,
    this.onNavigateToChat,
    this.showReservations = false,
    this.showChat = false,
    this.showSummaries = false,
    this.hasReservations = false,
    this.hasChat = false,
    this.hasCamps = false,
  });

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Acciones principales
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            _QuickActionChip(
              icon: Icons.qr_code_scanner,
              label: i18n.dashboardScanQr,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => const QRScannerScreen(),
                  ),
                );
              },
            ),
            if (showReservations)
              _QuickActionChip(
                icon: Icons.calendar_today,
                label: i18n.dashboardViewReservations,
                onTap: onNavigateToReservations ?? () {},
              ),
            if (showChat)
              _QuickActionChip(
                icon: Icons.smart_toy,
                label: i18n.dashboardChatIa,
                onTap: onNavigateToChat ?? () {},
              ),
            if (showSummaries)
              _QuickActionChip(
                icon: Icons.summarize,
                label: i18n.dashboardViewSummaries,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => const ExportScreen(),
                    ),
                  );
                },
              ),
          ],
        ),

        const SizedBox(height: 24),

        // Herramientas
        if (hasReservations || hasChat || hasCamps) ...[
          Text(AppLocalizations.of(context)!.herramientas,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              if (hasReservations)
                _QuickActionChip(
                  icon: Icons.bar_chart,
                  label: i18n.dashboardStats,
                  color: Colors.purple,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const StatsScreen(),
                      ),
                    );
                  },
                ),
              if (hasReservations)
                _QuickActionChip(
                  icon: Icons.calendar_month,
                  label: i18n.dashboardCalendar,
                  color: Colors.teal,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const CalendarViewScreen(),
                      ),
                    );
                  },
                ),
              if (hasReservations)
                _QuickActionChip(
                  icon: Icons.people,
                  label: i18n.dashboardCustomers,
                  color: Colors.indigo,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const CustomersScreen(),
                      ),
                    );
                  },
                ),
              if (hasReservations)
                _QuickActionChip(
                  icon: Icons.people_outline,
                  label: i18n.dashboardWeeklyCustomers,
                  color: Colors.orange,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const ManualCustomersScreen(),
                      ),
                    );
                  },
                ),
              if (hasChat)
                _QuickActionChip(
                  icon: Icons.support_agent,
                  label: i18n.dashboardEscalatedChats,
                  color: Colors.red,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const EscalatedChatsScreen(),
                      ),
                    );
                  },
                ),
              if (hasCamps)
                _QuickActionChip(
                  icon: Icons.cabin,
                  label: i18n.dashboardCamps,
                  color: Colors.brown,
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const CampsManagementScreen(),
                      ),
                    );
                  },
                ),
            ],
          ),
        ],
      ],
    );
  }
}

class _ModuleMetricsSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final metricsAsync = ref.watch(adminModuleMetricsProvider);

    return metricsAsync.when(
      data: (metrics) {
        if (metrics.isEmpty) {
          return Text(i18n.adminModulesDashboardEmpty);
        }
        return GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.3,
          children: metrics
              .map((metric) => StatCard(
                    title: metric.title,
                    value: metric.value,
                    icon: metric.icon,
                    color: metric.color,
                  ))
              .toList(),
        );
      },
      loading: () => const SizedBox(
        height: 64,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => Text(i18n.adminModulesDashboardEmpty),
    );
  }
}

class _QuickActionChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;

  const _QuickActionChip({
    required this.icon,
    required this.label,
    required this.onTap,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: label,
      button: true,
      child: ActionChip(
        avatar: ExcludeSemantics(
          child: Icon(icon, size: 18, color: color),
        ),
        label: Text(label),
        onPressed: () {
          Haptics.light();
          onTap();
        },
        side: color != null ? BorderSide(color: color!.withOpacity(0.3)) : null,
      ),
    );
  }
}
