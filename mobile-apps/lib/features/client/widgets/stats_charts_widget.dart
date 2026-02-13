import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../../core/utils/haptics.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/chart_widgets.dart';

/// Modelo para estadistica rapida (quick stat)
class QuickStat {
  final String id;
  final String label;
  final String value;
  final double numericValue;
  final String iconName;
  final String colorHex;
  final double? trendPercentage;
  final bool? isTrendUp;

  const QuickStat({
    required this.id,
    required this.label,
    required this.value,
    required this.numericValue,
    required this.iconName,
    required this.colorHex,
    this.trendPercentage,
    this.isTrendUp,
  });

  bool get hasTrend => trendPercentage != null;

  factory QuickStat.fromJson(Map<String, dynamic> json) {
    return QuickStat(
      id: json['id']?.toString() ?? '',
      label: json['label'] as String? ?? '',
      value: json['value']?.toString() ?? '0',
      numericValue: (json['numeric_value'] as num?)?.toDouble() ?? 0,
      iconName: json['icon'] as String? ?? 'analytics',
      colorHex: json['color'] as String? ?? '#2196F3',
      trendPercentage: (json['trend_percentage'] as num?)?.toDouble(),
      isTrendUp: json['is_trend_up'] as bool?,
    );
  }
}

/// Modelo para datos de grafico de actividad semanal
class WeeklyActivityData {
  final String dayLabel;
  final double value;
  final String? detailLabel;

  const WeeklyActivityData({
    required this.dayLabel,
    required this.value,
    this.detailLabel,
  });

  factory WeeklyActivityData.fromJson(Map<String, dynamic> json) {
    return WeeklyActivityData(
      dayLabel: json['day'] as String? ?? '',
      value: (json['value'] as num?)?.toDouble() ?? 0,
      detailLabel: json['detail'] as String?,
    );
  }
}

/// Modelo para datos de grafico de distribucion
class DistributionData {
  final String label;
  final double value;
  final String colorHex;
  final double percentage;

  const DistributionData({
    required this.label,
    required this.value,
    required this.colorHex,
    required this.percentage,
  });

  factory DistributionData.fromJson(Map<String, dynamic> json, double total) {
    final value = (json['value'] as num?)?.toDouble() ?? 0;
    return DistributionData(
      label: json['label'] as String? ?? '',
      value: value,
      colorHex: json['color'] as String? ?? '#2196F3',
      percentage: total > 0 ? (value / total) * 100 : 0,
    );
  }
}

/// Provider para quick stats del usuario
final quickStatsProvider = FutureProvider<List<QuickStat>>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.get('/client/quick-stats');
    if (response.success && response.data != null) {
      final statsJson = response.data!['stats'] as List? ?? [];
      return statsJson
          .map((stat) => QuickStat.fromJson(stat as Map<String, dynamic>))
          .toList();
    }
  } catch (error) {
    debugPrint('[QuickStats] Error: $error');
  }

  return _getExampleQuickStats();
});

/// Provider para datos de grafico de actividad semanal
final weeklyActivityProvider = FutureProvider<List<WeeklyActivityData>>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.getDashboardCharts(type: 'weekly_activity', days: 7);
    if (response.success && response.data != null) {
      final dataJson = response.data!['data'] as List? ?? [];
      return dataJson
          .map((item) => WeeklyActivityData.fromJson(item as Map<String, dynamic>))
          .toList();
    }
  } catch (error) {
    debugPrint('[WeeklyActivity] Error: $error');
  }

  return _getExampleWeeklyActivity();
});

/// Provider para datos de grafico de distribucion
final distributionChartProvider = FutureProvider<List<DistributionData>>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.getDashboardCharts(type: 'distribution', days: 30);
    if (response.success && response.data != null) {
      final dataJson = response.data!['data'] as List? ?? [];
      final total = dataJson.fold<double>(
        0,
        (sum, item) => sum + ((item['value'] as num?)?.toDouble() ?? 0),
      );
      return dataJson
          .map((item) => DistributionData.fromJson(item as Map<String, dynamic>, total))
          .toList();
    }
  } catch (error) {
    debugPrint('[DistributionChart] Error: $error');
  }

  return _getExampleDistribution();
});

/// Widget de panel de quick stats horizontal
class QuickStatsPanel extends ConsumerWidget {
  final Function(QuickStat)? onStatTap;
  final double itemWidth;
  final double height;

  const QuickStatsPanel({
    super.key,
    this.onStatTap,
    this.itemWidth = 140,
    this.height = 100,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final statsAsync = ref.watch(quickStatsProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Titulo
        Semantics(
          header: true,
          child: Text(
            'Resumen rapido',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 12),

        // Panel horizontal de stats
        SizedBox(
          height: height,
          child: statsAsync.when(
            data: (stats) => _buildStatsRow(context, stats, colorScheme),
            loading: () => _buildLoadingRow(colorScheme),
            error: (_, __) => _buildErrorState(colorScheme),
          ),
        ),
      ],
    );
  }

  Widget _buildStatsRow(
    BuildContext context,
    List<QuickStat> stats,
    ColorScheme colorScheme,
  ) {
    if (stats.isEmpty) {
      return _buildEmptyState(colorScheme);
    }

    return Semantics(
      label: 'Panel de estadisticas rapidas con ${stats.length} elementos',
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        physics: const BouncingScrollPhysics(),
        itemCount: stats.length,
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemBuilder: (context, index) {
          final stat = stats[index];
          return _QuickStatCard(
            stat: stat,
            width: itemWidth,
            onTap: onStatTap != null ? () => onStatTap!(stat) : null,
          );
        },
      ),
    );
  }

  Widget _buildLoadingRow(ColorScheme colorScheme) {
    return ListView.separated(
      scrollDirection: Axis.horizontal,
      itemCount: 4,
      separatorBuilder: (_, __) => const SizedBox(width: 12),
      itemBuilder: (context, index) => _QuickStatCardSkeleton(
        width: itemWidth,
        colorScheme: colorScheme,
      ),
    );
  }

  Widget _buildEmptyState(ColorScheme colorScheme) {
    return Center(
      child: Text(
        'Sin estadisticas disponibles',
        style: TextStyle(color: colorScheme.onSurfaceVariant),
      ),
    );
  }

  Widget _buildErrorState(ColorScheme colorScheme) {
    return Center(
      child: Text(
        'Error al cargar estadisticas',
        style: TextStyle(color: colorScheme.error),
      ),
    );
  }
}

/// Card individual de quick stat
class _QuickStatCard extends StatelessWidget {
  final QuickStat stat;
  final double width;
  final VoidCallback? onTap;

  const _QuickStatCard({
    required this.stat,
    required this.width,
    this.onTap,
  });

  Color _getColorFromHex(String hexColor) {
    final buffer = StringBuffer();
    if (hexColor.length == 6 || hexColor.length == 7) buffer.write('ff');
    buffer.write(hexColor.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  IconData _getIconFromName(String iconName) {
    final iconMap = {
      'shopping_cart': Icons.shopping_cart_outlined,
      'shopping_basket': Icons.shopping_basket_outlined,
      'volunteer_activism': Icons.volunteer_activism_outlined,
      'event': Icons.event_outlined,
      'people': Icons.people_outlined,
      'star': Icons.star_outlined,
      'favorite': Icons.favorite_outlined,
      'timer': Icons.timer_outlined,
      'attach_money': Icons.attach_money,
      'trending_up': Icons.trending_up,
      'analytics': Icons.analytics_outlined,
      'eco': Icons.eco_outlined,
      'local_library': Icons.local_library_outlined,
    };
    return iconMap[iconName] ?? Icons.analytics_outlined;
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final statColor = _getColorFromHex(stat.colorHex);
    final icon = _getIconFromName(stat.iconName);

    final accessibilityLabel = _buildAccessibilityLabel();

    return Semantics(
      label: accessibilityLabel,
      button: onTap != null,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap != null
              ? () {
                  Haptics.light();
                  onTap!();
                }
              : null,
          borderRadius: BorderRadius.circular(12),
          child: Container(
            width: width,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: colorScheme.surfaceContainerLow,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: colorScheme.outlineVariant,
                width: 1,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                // Icono y tendencia
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    ExcludeSemantics(
                      child: Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: statColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Icon(
                          icon,
                          color: statColor,
                          size: 18,
                        ),
                      ),
                    ),
                    if (stat.hasTrend) _buildTrendIndicator(stat),
                  ],
                ),

                // Valor
                ExcludeSemantics(
                  child: Text(
                    stat.value,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: colorScheme.onSurface,
                        ),
                  ),
                ),

                // Label
                ExcludeSemantics(
                  child: Text(
                    stat.label,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTrendIndicator(QuickStat stat) {
    final isUp = stat.isTrendUp ?? (stat.trendPercentage! >= 0);
    final color = isUp ? Colors.green : Colors.red;
    final icon = isUp ? Icons.trending_up : Icons.trending_down;

    return ExcludeSemantics(
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 2),
          Text(
            '${stat.trendPercentage!.abs().toStringAsFixed(0)}%',
            style: TextStyle(
              color: color,
              fontSize: 10,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  String _buildAccessibilityLabel() {
    final buffer = StringBuffer();
    buffer.write('${stat.label}: ${stat.value}');

    if (stat.hasTrend) {
      final direction = (stat.isTrendUp ?? stat.trendPercentage! >= 0) ? 'subio' : 'bajo';
      buffer.write('. $direction ${stat.trendPercentage!.abs().toStringAsFixed(0)} por ciento');
    }

    return buffer.toString();
  }
}

/// Skeleton para quick stat card
class _QuickStatCardSkeleton extends StatefulWidget {
  final double width;
  final ColorScheme colorScheme;

  const _QuickStatCardSkeleton({
    required this.width,
    required this.colorScheme,
  });

  @override
  State<_QuickStatCardSkeleton> createState() => _QuickStatCardSkeletonState();
}

class _QuickStatCardSkeletonState extends State<_QuickStatCardSkeleton>
    with SingleTickerProviderStateMixin {
  late AnimationController _shimmerController;

  @override
  void initState() {
    super.initState();
    _shimmerController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat();
  }

  @override
  void dispose() {
    _shimmerController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _shimmerController,
      builder: (context, _) {
        return Container(
          width: widget.width,
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: widget.colorScheme.surfaceContainerLow,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: widget.colorScheme.outlineVariant,
              width: 1,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(
                  color: _getShimmerColor(),
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              Container(
                width: 60,
                height: 24,
                decoration: BoxDecoration(
                  color: _getShimmerColor(),
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              Container(
                width: widget.width - 24,
                height: 14,
                decoration: BoxDecoration(
                  color: _getShimmerColor(),
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Color _getShimmerColor() {
    final baseColor = widget.colorScheme.surfaceContainerHighest;
    final highlightColor = widget.colorScheme.surface;
    final progress = _shimmerController.value;
    return Color.lerp(baseColor, highlightColor, (progress * 2 - 1).abs())!;
  }
}

/// Widget de grafico de barras de actividad semanal con animaciones
class AnimatedWeeklyActivityChart extends ConsumerStatefulWidget {
  final String title;
  final Function(WeeklyActivityData)? onBarTap;

  const AnimatedWeeklyActivityChart({
    super.key,
    this.title = 'Actividad semanal',
    this.onBarTap,
  });

  @override
  ConsumerState<AnimatedWeeklyActivityChart> createState() =>
      _AnimatedWeeklyActivityChartState();
}

class _AnimatedWeeklyActivityChartState
    extends ConsumerState<AnimatedWeeklyActivityChart>
    with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  late Animation<double> _animation;
  int? _touchedIndex;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    _animation = CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeOutCubic,
    );
    _animationController.forward();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final activityAsync = ref.watch(weeklyActivityProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return activityAsync.when(
      data: (data) => _buildChart(context, data, colorScheme),
      loading: () => _buildLoadingState(colorScheme),
      error: (_, __) => _buildErrorState(colorScheme),
    );
  }

  Widget _buildChart(
    BuildContext context,
    List<WeeklyActivityData> data,
    ColorScheme colorScheme,
  ) {
    if (data.isEmpty) {
      return const SizedBox.shrink();
    }

    final maxY = data.map((d) => d.value).reduce((a, b) => a > b ? a : b) * 1.2;

    return Semantics(
      label: 'Grafico de actividad semanal',
      child: Card(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(
            color: colorScheme.outlineVariant,
            width: 1,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.title,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                height: 180,
                child: AnimatedBuilder(
                  animation: _animation,
                  builder: (context, _) {
                    return BarChart(
                      BarChartData(
                        alignment: BarChartAlignment.spaceAround,
                        maxY: maxY,
                        barTouchData: BarTouchData(
                          enabled: true,
                          handleBuiltInTouches: true,
                          touchCallback: (event, response) {
                            if (event.isInterestedForInteractions &&
                                response != null &&
                                response.spot != null) {
                              final index = response.spot!.touchedBarGroupIndex;
                              if (index != _touchedIndex) {
                                Haptics.selection();
                                setState(() => _touchedIndex = index);
                                if (widget.onBarTap != null && index < data.length) {
                                  widget.onBarTap!(data[index]);
                                }
                              }
                            } else {
                              if (_touchedIndex != null) {
                                setState(() => _touchedIndex = null);
                              }
                            }
                          },
                          touchTooltipData: BarTouchTooltipData(
                            tooltipBgColor: colorScheme.surfaceContainerHighest,
                            tooltipRoundedRadius: 8,
                            getTooltipItem: (group, groupIndex, rod, rodIndex) {
                              final item = data[groupIndex];
                              return BarTooltipItem(
                                '${item.dayLabel}\n${rod.toY.toInt()}',
                                TextStyle(
                                  color: colorScheme.onSurface,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 12,
                                ),
                              );
                            },
                          ),
                        ),
                        titlesData: FlTitlesData(
                          show: true,
                          rightTitles: const AxisTitles(
                            sideTitles: SideTitles(showTitles: false),
                          ),
                          topTitles: const AxisTitles(
                            sideTitles: SideTitles(showTitles: false),
                          ),
                          bottomTitles: AxisTitles(
                            sideTitles: SideTitles(
                              showTitles: true,
                              getTitlesWidget: (value, meta) {
                                if (value.toInt() >= 0 && value.toInt() < data.length) {
                                  return Padding(
                                    padding: const EdgeInsets.only(top: 8),
                                    child: Text(
                                      data[value.toInt()].dayLabel,
                                      style: TextStyle(
                                        color: colorScheme.onSurfaceVariant,
                                        fontSize: 11,
                                        fontWeight: _touchedIndex == value.toInt()
                                            ? FontWeight.bold
                                            : FontWeight.normal,
                                      ),
                                    ),
                                  );
                                }
                                return const SizedBox.shrink();
                              },
                              reservedSize: 30,
                            ),
                          ),
                          leftTitles: AxisTitles(
                            sideTitles: SideTitles(
                              showTitles: true,
                              getTitlesWidget: (value, meta) {
                                return Text(
                                  value.toInt().toString(),
                                  style: TextStyle(
                                    color: colorScheme.onSurfaceVariant,
                                    fontSize: 10,
                                  ),
                                );
                              },
                              reservedSize: 28,
                            ),
                          ),
                        ),
                        borderData: FlBorderData(show: false),
                        gridData: FlGridData(
                          show: true,
                          drawVerticalLine: false,
                          horizontalInterval: maxY / 4,
                          getDrawingHorizontalLine: (value) {
                            return FlLine(
                              color: colorScheme.outlineVariant.withOpacity(0.3),
                              strokeWidth: 1,
                              dashArray: [4, 4],
                            );
                          },
                        ),
                        barGroups: data.asMap().entries.map((entry) {
                          final index = entry.key;
                          final item = entry.value;
                          final isTouched = _touchedIndex == index;

                          return BarChartGroupData(
                            x: index,
                            barRods: [
                              BarChartRodData(
                                toY: item.value * _animation.value,
                                color: isTouched
                                    ? colorScheme.primary
                                    : colorScheme.primary.withOpacity(0.7),
                                width: isTouched ? 20 : 16,
                                borderRadius: const BorderRadius.vertical(
                                  top: Radius.circular(6),
                                ),
                                backDrawRodData: BackgroundBarChartRodData(
                                  show: true,
                                  toY: maxY,
                                  color: colorScheme.surfaceContainerHighest,
                                ),
                              ),
                            ],
                          );
                        }).toList(),
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLoadingState(ColorScheme colorScheme) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: colorScheme.outlineVariant,
          width: 1,
        ),
      ),
      child: const SizedBox(
        height: 250,
        child: Center(child: CircularProgressIndicator()),
      ),
    );
  }

  Widget _buildErrorState(ColorScheme colorScheme) {
    return const SizedBox.shrink();
  }
}

/// Widget de grafico circular de distribucion con animaciones
class AnimatedDistributionPieChart extends ConsumerStatefulWidget {
  final String title;
  final Function(DistributionData)? onSectionTap;

  const AnimatedDistributionPieChart({
    super.key,
    this.title = 'Distribucion por actividad',
    this.onSectionTap,
  });

  @override
  ConsumerState<AnimatedDistributionPieChart> createState() =>
      _AnimatedDistributionPieChartState();
}

class _AnimatedDistributionPieChartState
    extends ConsumerState<AnimatedDistributionPieChart>
    with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  late Animation<double> _animation;
  int? _touchedIndex;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    );
    _animation = CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeOutCubic,
    );
    _animationController.forward();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  Color _getColorFromHex(String hexColor) {
    final buffer = StringBuffer();
    if (hexColor.length == 6 || hexColor.length == 7) buffer.write('ff');
    buffer.write(hexColor.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  @override
  Widget build(BuildContext context) {
    final distributionAsync = ref.watch(distributionChartProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return distributionAsync.when(
      data: (data) => _buildChart(context, data, colorScheme),
      loading: () => _buildLoadingState(colorScheme),
      error: (_, __) => _buildErrorState(colorScheme),
    );
  }

  Widget _buildChart(
    BuildContext context,
    List<DistributionData> data,
    ColorScheme colorScheme,
  ) {
    if (data.isEmpty) {
      return const SizedBox.shrink();
    }

    return Semantics(
      label: 'Grafico circular de distribucion por actividad',
      child: Card(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(
            color: colorScheme.outlineVariant,
            width: 1,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.title,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                height: 200,
                child: Row(
                  children: [
                    // Grafico circular
                    Expanded(
                      flex: 3,
                      child: AnimatedBuilder(
                        animation: _animation,
                        builder: (context, _) {
                          return PieChart(
                            PieChartData(
                              pieTouchData: PieTouchData(
                                touchCallback: (event, response) {
                                  if (event.isInterestedForInteractions &&
                                      response != null &&
                                      response.touchedSection != null) {
                                    final index = response.touchedSection!
                                        .touchedSectionIndex;
                                    if (index != _touchedIndex && index >= 0) {
                                      Haptics.selection();
                                      setState(() => _touchedIndex = index);
                                      if (widget.onSectionTap != null &&
                                          index < data.length) {
                                        widget.onSectionTap!(data[index]);
                                      }
                                    }
                                  } else {
                                    if (_touchedIndex != null) {
                                      setState(() => _touchedIndex = null);
                                    }
                                  }
                                },
                              ),
                              borderData: FlBorderData(show: false),
                              sectionsSpace: 2,
                              centerSpaceRadius: 45,
                              sections: data.asMap().entries.map((entry) {
                                final index = entry.key;
                                final item = entry.value;
                                final isTouched = _touchedIndex == index;
                                final color = _getColorFromHex(item.colorHex);

                                return PieChartSectionData(
                                  value: item.value * _animation.value,
                                  title: isTouched
                                      ? '${item.percentage.toStringAsFixed(0)}%'
                                      : '',
                                  color: isTouched
                                      ? color
                                      : color.withOpacity(0.8),
                                  radius: isTouched ? 65 : 55,
                                  titleStyle: const TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                  ),
                                  badgeWidget: isTouched
                                      ? null
                                      : _animation.value > 0.8
                                          ? Text(
                                              '${item.percentage.toStringAsFixed(0)}%',
                                              style: const TextStyle(
                                                fontSize: 10,
                                                fontWeight: FontWeight.bold,
                                                color: Colors.white,
                                              ),
                                            )
                                          : null,
                                  badgePositionPercentageOffset: 0.6,
                                );
                              }).toList(),
                            ),
                          );
                        },
                      ),
                    ),
                    const SizedBox(width: 16),
                    // Leyenda
                    Expanded(
                      flex: 2,
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: data.asMap().entries.map((entry) {
                          final index = entry.key;
                          final item = entry.value;
                          final color = _getColorFromHex(item.colorHex);
                          final isTouched = _touchedIndex == index;

                          return Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: GestureDetector(
                              onTap: () {
                                Haptics.light();
                                setState(() => _touchedIndex = index);
                                widget.onSectionTap?.call(item);
                              },
                              child: Row(
                                children: [
                                  AnimatedContainer(
                                    duration: const Duration(milliseconds: 200),
                                    width: isTouched ? 14 : 12,
                                    height: isTouched ? 14 : 12,
                                    decoration: BoxDecoration(
                                      color: color,
                                      borderRadius: BorderRadius.circular(3),
                                      boxShadow: isTouched
                                          ? [
                                              BoxShadow(
                                                color: color.withOpacity(0.4),
                                                blurRadius: 4,
                                              ),
                                            ]
                                          : null,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      item.label,
                                      style: Theme.of(context)
                                          .textTheme
                                          .bodySmall
                                          ?.copyWith(
                                            fontWeight: isTouched
                                                ? FontWeight.bold
                                                : FontWeight.normal,
                                          ),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        }).toList(),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLoadingState(ColorScheme colorScheme) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: colorScheme.outlineVariant,
          width: 1,
        ),
      ),
      child: const SizedBox(
        height: 250,
        child: Center(child: CircularProgressIndicator()),
      ),
    );
  }

  Widget _buildErrorState(ColorScheme colorScheme) {
    return const SizedBox.shrink();
  }
}

/// Panel completo de graficos estadisticos
class StatsChartsPanel extends StatelessWidget {
  final Function(QuickStat)? onQuickStatTap;
  final Function(WeeklyActivityData)? onBarTap;
  final Function(DistributionData)? onSectionTap;

  const StatsChartsPanel({
    super.key,
    this.onQuickStatTap,
    this.onBarTap,
    this.onSectionTap,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Quick stats panel horizontal
        QuickStatsPanel(onStatTap: onQuickStatTap),
        const SizedBox(height: 24),

        // Grafico de actividad semanal
        AnimatedWeeklyActivityChart(onBarTap: onBarTap),
        const SizedBox(height: 16),

        // Grafico de distribucion
        AnimatedDistributionPieChart(onSectionTap: onSectionTap),
      ],
    );
  }
}

// Datos de ejemplo
List<QuickStat> _getExampleQuickStats() {
  return const [
    QuickStat(
      id: '1',
      label: 'Reservas',
      value: '12',
      numericValue: 12,
      iconName: 'event',
      colorHex: '#2196F3',
      trendPercentage: 15,
      isTrendUp: true,
    ),
    QuickStat(
      id: '2',
      label: 'Pedidos',
      value: '8',
      numericValue: 8,
      iconName: 'shopping_basket',
      colorHex: '#4CAF50',
      trendPercentage: 25,
      isTrendUp: true,
    ),
    QuickStat(
      id: '3',
      label: 'Horas ganadas',
      value: '24h',
      numericValue: 24,
      iconName: 'timer',
      colorHex: '#009688',
      trendPercentage: 10,
      isTrendUp: true,
    ),
    QuickStat(
      id: '4',
      label: 'Puntos',
      value: '450',
      numericValue: 450,
      iconName: 'star',
      colorHex: '#FF9800',
      trendPercentage: -5,
      isTrendUp: false,
    ),
    QuickStat(
      id: '5',
      label: 'Favoritos',
      value: '23',
      numericValue: 23,
      iconName: 'favorite',
      colorHex: '#E91E63',
    ),
  ];
}

List<WeeklyActivityData> _getExampleWeeklyActivity() {
  return const [
    WeeklyActivityData(dayLabel: 'Lun', value: 5),
    WeeklyActivityData(dayLabel: 'Mar', value: 8),
    WeeklyActivityData(dayLabel: 'Mie', value: 3),
    WeeklyActivityData(dayLabel: 'Jue', value: 12),
    WeeklyActivityData(dayLabel: 'Vie', value: 7),
    WeeklyActivityData(dayLabel: 'Sab', value: 15),
    WeeklyActivityData(dayLabel: 'Dom', value: 4),
  ];
}

List<DistributionData> _getExampleDistribution() {
  return const [
    DistributionData(label: 'Reservas', value: 35, colorHex: '#2196F3', percentage: 35),
    DistributionData(label: 'Pedidos', value: 25, colorHex: '#4CAF50', percentage: 25),
    DistributionData(label: 'Intercambios', value: 20, colorHex: '#009688', percentage: 20),
    DistributionData(label: 'Eventos', value: 15, colorHex: '#FF9800', percentage: 15),
    DistributionData(label: 'Otros', value: 5, colorHex: '#9E9E9E', percentage: 5),
  ];
}
