import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../core/providers/providers.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/api/api_client.dart';

/// Provider para estadísticas con rango de fechas
final statsProvider = FutureProvider.family<StatsData, Map<String, String>>((ref, params) async {
  final api = ref.read(apiClientProvider);
  try {
    debugPrint('[Stats] Solicitando datos: from=${params['from']}, to=${params['to']}');
    final response = await api.getAdminStats(from: params['from'], to: params['to']);
    debugPrint('[Stats] Respuesta: success=${response.success}, hasData=${response.data != null}');

    if (response.success && response.data != null) {
      debugPrint('[Stats] Datos recibidos: ${response.data!.keys.toList()}');
      final statsData = StatsData.fromJson(response.data!);
      debugPrint('[Stats] Parseado correctamente: reservations=${statsData.totalReservations}');
      return statsData;
    }
    // Si no hay datos, devolver estadísticas vacías en lugar de error
    if (response.data != null && response.data!['notice'] != null) {
      debugPrint('[Stats] Sin datos (notice): ${response.data!['notice']}');
      return StatsData();
    }
    debugPrint('[Stats] Error del servidor: ${response.error}');
    throw Exception(response.error ?? 'Error al obtener estadísticas');
  } catch (e, stack) {
    debugPrint('[Stats] Excepción: $e');
    debugPrint('[Stats] Stack: $stack');
    rethrow;
  }
});

/// Modelo de estadísticas
class StatsData {
  final int totalReservations;
  final int totalCheckins;
  final int totalCancelled;
  final double totalRevenue;
  final List<DailyStats> dailyStats;
  final Map<String, int> byTicketType;
  final Map<String, double> revenueByTicket;

  StatsData({
    this.totalReservations = 0,
    this.totalCheckins = 0,
    this.totalCancelled = 0,
    this.totalRevenue = 0,
    this.dailyStats = const [],
    this.byTicketType = const {},
    this.revenueByTicket = const {},
  });

  factory StatsData.fromJson(Map<String, dynamic> json) {
    final stats = json['stats'] ?? json;

    // Convertir revenue_by_ticket a Map<String, double>
    final revenueMap = <String, double>{};
    if (stats['revenue_by_ticket'] != null) {
      (stats['revenue_by_ticket'] as Map).forEach((key, value) {
        revenueMap[key.toString()] = (value is num) ? value.toDouble() : 0.0;
      });
    }

    return StatsData(
      totalReservations: stats['total_reservations'] ?? 0,
      totalCheckins: stats['total_checkins'] ?? 0,
      totalCancelled: stats['total_cancelled'] ?? 0,
      totalRevenue: (stats['total_revenue'] ?? 0).toDouble(),
      dailyStats: (stats['daily'] as List?)
              ?.map((d) => DailyStats.fromJson(d))
              .toList() ??
          [],
      byTicketType: Map<String, int>.from(stats['by_ticket_type'] ?? {}),
      revenueByTicket: revenueMap,
    );
  }
}

class DailyStats {
  final String date;
  final int reservations;
  final double revenue;

  DailyStats({
    required this.date,
    this.reservations = 0,
    this.revenue = 0,
  });

  factory DailyStats.fromJson(Map<String, dynamic> json) {
    return DailyStats(
      date: json['date'] ?? '',
      reservations: json['reservations'] ?? 0,
      revenue: (json['revenue'] ?? 0).toDouble(),
    );
  }
}

/// Pantalla de estadísticas con gráficos
class StatsScreen extends ConsumerStatefulWidget {
  const StatsScreen({super.key});

  @override
  ConsumerState<StatsScreen> createState() => _StatsScreenState();
}

class _StatsScreenState extends ConsumerState<StatsScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  DateTime _startDate = DateTime.now().subtract(const Duration(days: 30));
  DateTime _endDate = DateTime.now();

  // Cache del mapa de parámetros para evitar recrearlo en cada build
  late Map<String, String> _cachedParams;

  @override
  void initState() {
    super.initState();
    _updateParams();
  }

  String _formatDateParam(DateTime date) {
    return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  void _updateParams() {
    _cachedParams = {
      'from': _formatDateParam(_startDate),
      'to': _formatDateParam(_endDate),
    };
  }

  Map<String, String> get _params => _cachedParams;

  void _selectDateRange() async {
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime.now().subtract(const Duration(days: 365)),
      lastDate: DateTime.now(),
      initialDateRange: DateTimeRange(start: _startDate, end: _endDate),
    );
    if (range != null) {
      _startDate = range.start;
      _endDate = range.end;
      _updateParams();
      setState(() {});
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final statsAsync = ref.watch(statsProvider(_params));

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.estadSticasF66aff),
        actions: [
          IconButton(
            onPressed: () => ref.invalidate(statsProvider(_params)),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: Column(
        children: [
          // Selector de rango de fechas
          Padding(
            padding: const EdgeInsets.all(16),
            child: OutlinedButton.icon(
              onPressed: _selectDateRange,
              icon: const Icon(Icons.date_range),
              label: Text(
                '${_startDate.day}/${_startDate.month}/${_startDate.year} - ${_endDate.day}/${_endDate.month}/${_endDate.year}',
              ),
            ),
          ),

          Expanded(
            child: statsAsync.when(
              data: (stats) => _StatsContent(stats: stats),
              loading: () => LoadingScreen(message: i18n.loadingStats),
              error: (error, _) => ErrorScreen(
                message: 'Error al cargar estadísticas',
                onRetry: () => ref.invalidate(statsProvider(_params)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatsContent extends StatelessWidget {
  final StatsData stats;

  const _StatsContent({required this.stats});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Resumen en cards
          _SummaryCards(stats: stats),
          const SizedBox(height: 24),

          // Gráfico de reservas por día
          if (stats.dailyStats.isNotEmpty) ...[
            Text(AppLocalizations.of(context)!.reservasPorDia,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              height: 200,
              child: _ReservationsChart(dailyStats: stats.dailyStats),
            ),
            const SizedBox(height: 24),
          ],

          // Gráfico de ingresos
          if (stats.dailyStats.isNotEmpty) ...[
            Text(AppLocalizations.of(context)!.ingresosPorDia,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              height: 200,
              child: _RevenueChart(dailyStats: stats.dailyStats),
            ),
            const SizedBox(height: 24),
          ],

          // Gráfico por tipo de ticket
          if (stats.byTicketType.isNotEmpty) ...[
            Text(AppLocalizations.of(context)!.reservasPorTipoDeTicket,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              height: 200,
              child: _TicketTypePieChart(byTicketType: stats.byTicketType),
            ),
            const SizedBox(height: 24),
          ],

          // Gráfico de ingresos por tipo de ticket
          if (stats.revenueByTicket.isNotEmpty) ...[
            Text(AppLocalizations.of(context)!.ingresosPorTipoDeTicket,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              height: 200,
              child: _RevenueByTicketPieChart(revenueByTicket: stats.revenueByTicket),
            ),
          ],
        ],
      ),
    );
  }
}

class _SummaryCards extends StatelessWidget {
  final StatsData stats;

  const _SummaryCards({required this.stats});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.5,
      children: [
        StatCard(
          title: i18n.statsSummaryReservations,
          value: '${stats.totalReservations}',
          icon: Icons.calendar_today,
          color: Colors.blue,
        ),
        StatCard(
          title: i18n.statsSummaryCheckins,
          value: '${stats.totalCheckins}',
          icon: Icons.check_circle,
          color: Colors.green,
        ),
        StatCard(
          title: i18n.statsSummaryCancelled,
          value: '${stats.totalCancelled}',
          icon: Icons.cancel,
          color: Colors.red,
        ),
        StatCard(
          title: i18n.statsSummaryRevenue,
          value: '${stats.totalRevenue.toStringAsFixed(2)}€',
          icon: Icons.euro,
          color: Colors.amber,
        ),
      ],
    );
  }
}

class _ReservationsChart extends StatelessWidget {
  final List<DailyStats> dailyStats;

  const _ReservationsChart({required this.dailyStats});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;

    return LineChart(
      LineChartData(
        gridData: FlGridData(
          show: true,
          drawVerticalLine: false,
          horizontalInterval: 1,
          getDrawingHorizontalLine: (value) {
            return FlLine(
              color: colorScheme.outline.withOpacity(0.2),
              strokeWidth: 1,
            );
          },
        ),
        titlesData: FlTitlesData(
          show: true,
          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          bottomTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 30,
              interval: (dailyStats.length / 5).ceil().toDouble(),
              getTitlesWidget: (value, meta) {
                final index = value.toInt();
                if (index >= 0 && index < dailyStats.length) {
                  final date = dailyStats[index].date;
                  return Text(
                    i18n.commonShortDate(date),
                    style: const TextStyle(fontSize: 10),
                  );
                }
                return const SizedBox.shrink();
              },
            ),
          ),
          leftTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 30,
              getTitlesWidget: (value, meta) {
                return Text(
                  value.toInt().toString(),
                  style: const TextStyle(fontSize: 10),
                );
              },
            ),
          ),
        ),
        borderData: FlBorderData(show: false),
        lineBarsData: [
          LineChartBarData(
            spots: dailyStats.asMap().entries.map((entry) {
              return FlSpot(entry.key.toDouble(), entry.value.reservations.toDouble());
            }).toList(),
            isCurved: true,
            color: colorScheme.primary,
            barWidth: 3,
            isStrokeCapRound: true,
            dotData: const FlDotData(show: false),
            belowBarData: BarAreaData(
              show: true,
              color: colorScheme.primary.withOpacity(0.1),
            ),
          ),
        ],
      ),
    );
  }
}

class _RevenueChart extends StatelessWidget {
  final List<DailyStats> dailyStats;

  const _RevenueChart({required this.dailyStats});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;

    return BarChart(
      BarChartData(
        gridData: FlGridData(
          show: true,
          drawVerticalLine: false,
          horizontalInterval: 50,
          getDrawingHorizontalLine: (value) {
            return FlLine(
              color: colorScheme.outline.withOpacity(0.2),
              strokeWidth: 1,
            );
          },
        ),
        titlesData: FlTitlesData(
          show: true,
          rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
          bottomTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 30,
              getTitlesWidget: (value, meta) {
                final index = value.toInt();
                if (index >= 0 && index < dailyStats.length && index % 5 == 0) {
                  final date = dailyStats[index].date;
                  return Text(
                    i18n.commonShortDate(date),
                    style: const TextStyle(fontSize: 10),
                  );
                }
                return const SizedBox.shrink();
              },
            ),
          ),
          leftTitles: AxisTitles(
            sideTitles: SideTitles(
              showTitles: true,
              reservedSize: 40,
              getTitlesWidget: (value, meta) {
                return Text(
                  i18n.commonPriceEur(value.toInt().toString()),
                  style: const TextStyle(fontSize: 10),
                );
              },
            ),
          ),
        ),
        borderData: FlBorderData(show: false),
        barGroups: dailyStats.asMap().entries.map((entry) {
          return BarChartGroupData(
            x: entry.key,
            barRods: [
              BarChartRodData(
                toY: entry.value.revenue,
                color: Colors.amber,
                width: dailyStats.length > 20 ? 4 : 8,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
              ),
            ],
          );
        }).toList(),
      ),
    );
  }
}

class _TicketTypePieChart extends StatelessWidget {
  final Map<String, int> byTicketType;

  const _TicketTypePieChart({required this.byTicketType});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colors = [
      Colors.blue,
      Colors.green,
      Colors.orange,
      Colors.purple,
      Colors.red,
      Colors.teal,
      Colors.pink,
      Colors.indigo,
    ];

    final entries = byTicketType.entries.toList();
    final total = entries.fold<int>(0, (sum, e) => sum + e.value);

    return Row(
      children: [
        Expanded(
          child: PieChart(
            PieChartData(
              sectionsSpace: 2,
              centerSpaceRadius: 40,
              sections: entries.asMap().entries.map((entry) {
                final index = entry.key;
                final ticketEntry = entry.value;
                final percentage = total > 0 ? (ticketEntry.value / total * 100) : 0;
                return PieChartSectionData(
                  color: colors[index % colors.length],
                  value: ticketEntry.value.toDouble(),
                  title: '${percentage.toStringAsFixed(0)}%',
                  radius: 50,
                  titleStyle: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                );
              }).toList(),
            ),
          ),
        ),
        const SizedBox(width: 16),
        Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: entries.asMap().entries.map((entry) {
            final index = entry.key;
            final ticketEntry = entry.value;
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 2),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 12,
                    height: 12,
                    decoration: BoxDecoration(
                      color: colors[index % colors.length],
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '${ticketEntry.key}: ${ticketEntry.value}',
                    style: const TextStyle(fontSize: 12),
                  ),
                ],
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}

/// Gráfico circular de ingresos por tipo de ticket
class _RevenueByTicketPieChart extends StatelessWidget {
  final Map<String, double> revenueByTicket;

  const _RevenueByTicketPieChart({required this.revenueByTicket});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colors = [
      Colors.green,
      Colors.blue,
      Colors.orange,
      Colors.purple,
      Colors.red,
      Colors.teal,
      Colors.pink,
      Colors.indigo,
    ];

    final entries = revenueByTicket.entries.toList();
    final total = entries.fold<double>(0, (sum, e) => sum + e.value);

    return Row(
      children: [
        Expanded(
          child: PieChart(
            PieChartData(
              sectionsSpace: 2,
              centerSpaceRadius: 40,
              sections: entries.asMap().entries.map((entry) {
                final index = entry.key;
                final ticketEntry = entry.value;
                final percentage = total > 0 ? (ticketEntry.value / total * 100) : 0;
                return PieChartSectionData(
                  color: colors[index % colors.length],
                  value: ticketEntry.value,
                  title: '${percentage.toStringAsFixed(0)}%',
                  radius: 50,
                  titleStyle: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                );
              }).toList(),
            ),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: entries.asMap().entries.map((entry) {
                final index = entry.key;
                final ticketEntry = entry.value;
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 12,
                        height: 12,
                        decoration: BoxDecoration(
                          color: colors[index % colors.length],
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '${ticketEntry.key}',
                          style: const TextStyle(fontSize: 12),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        '${ticketEntry.value.toStringAsFixed(2)}€',
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
          ),
        ),
      ],
    );
  }
}
