part of 'dashboard_screen_v2.dart';

class _GreetingHeader extends StatelessWidget {
  final String date;

  const _GreetingHeader({required this.date});

  @override
  Widget build(BuildContext context) {
    final hour = DateTime.now().hour;
    final colorScheme = Theme.of(context).colorScheme;

    final i18n = AppLocalizations.of(context);
    String greeting;
    IconData icon;

    if (hour < 12) {
      greeting = i18n.adminGreetingMorning;
      icon = Icons.wb_sunny;
    } else if (hour < 19) {
      greeting = i18n.adminGreetingAfternoon;
      icon = Icons.wb_twilight;
    } else {
      greeting = i18n.adminGreetingEvening;
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
    final i18n = AppLocalizations.of(context);

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
  String _localizedMetricTitle(AppLocalizations i18n, _ModuleMetric metric) {
    switch (metric.id) {
      case 'grupos_consumo':
        return i18n.adminMetricOpenOrders;
      case 'banco_tiempo':
        return i18n.adminMetricActiveServices;
      case 'marketplace':
        return i18n.adminMetricActiveListings;
      case 'eventos':
        return i18n.adminMetricUpcomingEvents;
      case 'incidencias':
        return i18n.adminMetricOpenIncidents;
      case 'socios':
        return i18n.adminMetricActiveMembers;
      default:
        return metric.title;
    }
  }

  String? _localizedMetricSubtitle(AppLocalizations i18n, _ModuleMetric metric) {
    switch (metric.id) {
      case 'grupos_consumo':
        return metric.value == '0' ? i18n.adminMetricAllCaughtUp : i18n.adminMetricRequiresAttention;
      case 'incidencias':
        return i18n.adminMetricPendingResolution;
      default:
        return metric.subtitle?.isEmpty ?? true ? null : metric.subtitle;
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context);
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
                    title: _localizedMetricTitle(i18n, metric),
                    value: metric.value,
                    subtitle: _localizedMetricSubtitle(i18n, metric),
                    icon: metric.icon,
                    color: metric.color,
                  ))
              .toList(),
        );
      },
      loading: () => const SizedBox(
        height: 120,
        child: FlavorLoadingState(),
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
    final i18n = AppLocalizations.of(context);
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
        child: FlavorLoadingState(),
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
    final i18n = AppLocalizations.of(context);

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
    final i18n = AppLocalizations.of(context);

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
          AppLocalizations.of(context).adminChartsSectionTitle,
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
          title: AppLocalizations.of(context).adminChartReservationsLast7Days,
          barColor: Theme.of(context).colorScheme.primary,
        );
      },
      loading: () => const SizedBox(
        height: 200,
        child: Card(
          child: FlavorLoadingState(),
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
          title: AppLocalizations.of(context).adminChartRevenueTrendLast7Days,
          lineColor: Colors.green,
        );
      },
      loading: () => const SizedBox(
        height: 200,
        child: Card(
          child: FlavorLoadingState(),
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
          title: AppLocalizations.of(context).adminChartUsageByModule,
        );
      },
      loading: () => const SizedBox(
        height: 200,
        child: Card(
          child: FlavorLoadingState(),
        ),
      ),
      error: (error, stack) {
        debugPrint('[AdminDistributionPieChart] Error: $error');
        return const SizedBox.shrink();
      },
    );
  }
}
