import 'package:flutter/material.dart';
import '../../../core/services/client_dashboard_service.dart';
import '../../../core/utils/haptics.dart';

/// Widget de tarjeta de estadistica para el dashboard del cliente
/// Incluye animacion de contador, indicador de tendencia y accesibilidad completa
class ClientStatsCard extends StatefulWidget {
  final UserStatistic statistic;
  final VoidCallback? onTap;
  final bool animateOnAppear;
  final Duration animationDuration;

  const ClientStatsCard({
    super.key,
    required this.statistic,
    this.onTap,
    this.animateOnAppear = true,
    this.animationDuration = const Duration(milliseconds: 800),
  });

  @override
  State<ClientStatsCard> createState() => _ClientStatsCardState();
}

class _ClientStatsCardState extends State<ClientStatsCard>
    with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  late Animation<double> _countAnimation;
  late Animation<double> _fadeAnimation;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _setupAnimations();
  }

  void _setupAnimations() {
    _animationController = AnimationController(
      vsync: this,
      duration: widget.animationDuration,
    );

    _countAnimation = Tween<double>(
      begin: 0,
      end: widget.statistic.numericValue,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: Curves.easeOutCubic,
    ));

    _fadeAnimation = Tween<double>(
      begin: 0,
      end: 1,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: const Interval(0, 0.5, curve: Curves.easeOut),
    ));

    _scaleAnimation = Tween<double>(
      begin: 0.8,
      end: 1,
    ).animate(CurvedAnimation(
      parent: _animationController,
      curve: const Interval(0, 0.6, curve: Curves.easeOutBack),
    ));

    if (widget.animateOnAppear) {
      // Iniciar animacion con un pequeno delay para efecto visual
      Future.delayed(const Duration(milliseconds: 100), () {
        if (mounted) {
          _animationController.forward();
        }
      });
    } else {
      _animationController.value = 1.0;
    }
  }

  @override
  void didUpdateWidget(ClientStatsCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.statistic.numericValue != widget.statistic.numericValue) {
      _countAnimation = Tween<double>(
        begin: oldWidget.statistic.numericValue,
        end: widget.statistic.numericValue,
      ).animate(CurvedAnimation(
        parent: _animationController,
        curve: Curves.easeOutCubic,
      ));
      _animationController.forward(from: 0);
    }
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

  IconData _getIconFromName(String iconName) {
    final iconMap = {
      'shopping_cart': Icons.shopping_cart_outlined,
      'shopping_basket': Icons.shopping_basket_outlined,
      'volunteer_activism': Icons.volunteer_activism_outlined,
      'storefront': Icons.storefront_outlined,
      'event': Icons.event_outlined,
      'people': Icons.people_outlined,
      'star': Icons.star_outlined,
      'favorite': Icons.favorite_outlined,
      'message': Icons.message_outlined,
      'notifications': Icons.notifications_outlined,
      'wallet': Icons.account_balance_wallet_outlined,
      'timer': Icons.timer_outlined,
      'check_circle': Icons.check_circle_outlined,
      'trending_up': Icons.trending_up,
      'trending_down': Icons.trending_down,
      'calendar_today': Icons.calendar_today_outlined,
      'local_offer': Icons.local_offer_outlined,
      'attach_money': Icons.attach_money,
    };
    return iconMap[iconName] ?? Icons.analytics_outlined;
  }

  String _formatAnimatedValue(double value) {
    if (widget.statistic.type == 'currency') {
      return '\$${value.toStringAsFixed(2)}';
    } else if (widget.statistic.type == 'percentage') {
      return '${value.toStringAsFixed(1)}%';
    } else if (value >= 1000000) {
      return '${(value / 1000000).toStringAsFixed(1)}M';
    } else if (value >= 1000) {
      return '${(value / 1000).toStringAsFixed(1)}K';
    }
    return value.toInt().toString();
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final statisticColor = _getColorFromHex(widget.statistic.colorHex);
    final iconData = _getIconFromName(widget.statistic.iconName);

    // Construir etiqueta de accesibilidad completa
    final String accessibilityLabel = _buildAccessibilityLabel();

    return AnimatedBuilder(
      animation: _animationController,
      builder: (context, child) {
        return Opacity(
          opacity: _fadeAnimation.value,
          child: Transform.scale(
            scale: _scaleAnimation.value,
            child: child,
          ),
        );
      },
      child: Semantics(
        label: accessibilityLabel,
        button: widget.onTap != null,
        child: Card(
          elevation: 0,
          clipBehavior: Clip.antiAlias,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: BorderSide(
              color: colorScheme.outlineVariant,
              width: 1,
            ),
          ),
          child: InkWell(
            onTap: widget.onTap != null
                ? () {
                    Haptics.light();
                    widget.onTap!();
                  }
                : null,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // Icono y tendencia
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Contenedor del icono
                      ExcludeSemantics(
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: statisticColor.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(
                            iconData,
                            color: statisticColor,
                            size: 24,
                          ),
                        ),
                      ),
                      // Indicador de tendencia
                      if (widget.statistic.hasTrend) _buildTrendIndicator(colorScheme),
                    ],
                  ),

                  const Spacer(),

                  // Valor animado
                  ExcludeSemantics(
                    child: AnimatedBuilder(
                      animation: _countAnimation,
                      builder: (context, _) {
                        return Text(
                          _formatAnimatedValue(_countAnimation.value),
                          style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                                color: colorScheme.onSurface,
                              ),
                        );
                      },
                    ),
                  ),

                  const SizedBox(height: 4),

                  // Titulo
                  ExcludeSemantics(
                    child: Text(
                      widget.statistic.title,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: colorScheme.onSurfaceVariant,
                          ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTrendIndicator(ColorScheme colorScheme) {
    final trendPercentage = widget.statistic.trendPercentage!;
    final isPositive = trendPercentage >= 0;
    final trendColor = isPositive ? Colors.green : Colors.red;
    final trendIcon = isPositive ? Icons.trending_up : Icons.trending_down;

    return ExcludeSemantics(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: trendColor.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              trendIcon,
              color: trendColor,
              size: 14,
            ),
            const SizedBox(width: 2),
            Text(
              '${isPositive ? '+' : ''}${trendPercentage.toStringAsFixed(1)}%',
              style: TextStyle(
                color: trendColor,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _buildAccessibilityLabel() {
    final buffer = StringBuffer();
    buffer.write('${widget.statistic.title}: ${widget.statistic.value}');

    if (widget.statistic.hasTrend) {
      final trendPercentage = widget.statistic.trendPercentage!;
      final trendDirection = trendPercentage >= 0 ? 'subio' : 'bajo';
      buffer.write('. $trendDirection ${trendPercentage.abs().toStringAsFixed(1)} por ciento');
    }

    if (widget.onTap != null) {
      buffer.write('. Toca para ver detalles');
    }

    return buffer.toString();
  }
}

/// Grid de estadisticas del cliente con skeleton loading
class ClientStatsGrid extends StatelessWidget {
  final List<UserStatistic> statistics;
  final bool isLoading;
  final Function(UserStatistic)? onStatisticTap;
  final int crossAxisCount;
  final double childAspectRatio;

  const ClientStatsGrid({
    super.key,
    required this.statistics,
    this.isLoading = false,
    this.onStatisticTap,
    this.crossAxisCount = 2,
    this.childAspectRatio = 1.2,
  });

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return _buildSkeletonGrid(context);
    }

    if (statistics.isEmpty) {
      return _buildEmptyState(context);
    }

    return Semantics(
      label: 'Grid de estadisticas con ${statistics.length} elementos',
      child: GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: crossAxisCount,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: childAspectRatio,
        ),
        itemCount: statistics.length,
        itemBuilder: (context, index) {
          final statistic = statistics[index];
          return ClientStatsCard(
            statistic: statistic,
            onTap: onStatisticTap != null ? () => onStatisticTap!(statistic) : null,
          );
        },
      ),
    );
  }

  Widget _buildSkeletonGrid(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: childAspectRatio,
      ),
      itemCount: 4, // Mostrar 4 skeletons
      itemBuilder: (context, index) {
        return _SkeletonStatsCard(colorScheme: colorScheme);
      },
    );
  }

  Widget _buildEmptyState(BuildContext context) {
    return Semantics(
      label: 'No hay estadisticas disponibles',
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.analytics_outlined,
                size: 48,
                color: Colors.grey[400],
              ),
              const SizedBox(height: 12),
              Text(
                'Sin estadisticas',
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Colors.grey[600],
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Skeleton card para estado de carga
class _SkeletonStatsCard extends StatefulWidget {
  final ColorScheme colorScheme;

  const _SkeletonStatsCard({required this.colorScheme});

  @override
  State<_SkeletonStatsCard> createState() => _SkeletonStatsCardState();
}

class _SkeletonStatsCardState extends State<_SkeletonStatsCard>
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
      builder: (context, child) {
        return Card(
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: BorderSide(
              color: widget.colorScheme.outlineVariant,
              width: 1,
            ),
          ),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                // Skeleton icono
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: _getShimmerColor(),
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                const Spacer(),
                // Skeleton valor
                Container(
                  width: 80,
                  height: 32,
                  decoration: BoxDecoration(
                    color: _getShimmerColor(),
                    borderRadius: BorderRadius.circular(6),
                  ),
                ),
                const SizedBox(height: 8),
                // Skeleton titulo
                Container(
                  width: double.infinity,
                  height: 14,
                  decoration: BoxDecoration(
                    color: _getShimmerColor(),
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ],
            ),
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
