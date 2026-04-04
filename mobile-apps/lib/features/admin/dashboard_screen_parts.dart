part of 'dashboard_screen.dart';

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
    final i18n = AppLocalizations.of(context);
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
          Text(AppLocalizations.of(context).herramientas,
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
  String _localizedMetricTitle(AppLocalizations i18n, _ModuleMetric metric) {
    switch (metric.id) {
      case 'grupos_consumo':
        return i18n.adminMetricOpenOrders;
      case 'banco_tiempo':
        return i18n.adminMetricActiveServices;
      case 'marketplace':
        return i18n.adminMetricActiveListings;
      default:
        return metric.title;
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context);
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
                    title: _localizedMetricTitle(i18n, metric),
                    value: metric.value,
                    icon: metric.icon,
                    color: metric.color,
                  ))
              .toList(),
        );
      },
      loading: () => const SizedBox(height: 64, child: FlavorLoadingState()),
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
