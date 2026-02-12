import 'package:flutter/material.dart';
import '../../../core/services/client_dashboard_service.dart';
import '../../../core/utils/haptics.dart';

/// Widget de timeline para mostrar actividad reciente del usuario
/// Muestra una lista vertical con indicadores de tiempo e iconos por tipo
class ActivityTimeline extends StatelessWidget {
  final List<UserActivity> activities;
  final bool isLoading;
  final Function(UserActivity)? onActivityTap;
  final int maxItems;
  final VoidCallback? onViewAll;
  final String? viewAllLabel;

  const ActivityTimeline({
    super.key,
    required this.activities,
    this.isLoading = false,
    this.onActivityTap,
    this.maxItems = 5,
    this.onViewAll,
    this.viewAllLabel,
  });

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return _buildSkeletonTimeline(context);
    }

    if (activities.isEmpty) {
      return _buildEmptyState(context);
    }

    final displayActivities = activities.take(maxItems).toList();

    return Semantics(
      label: 'Timeline de actividad reciente con ${displayActivities.length} elementos',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Lista de actividades
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: displayActivities.length,
            itemBuilder: (context, index) {
              final activity = displayActivities[index];
              final isLast = index == displayActivities.length - 1;

              return ActivityTimelineItem(
                activity: activity,
                isLast: isLast,
                onTap: onActivityTap != null ? () => onActivityTap!(activity) : null,
              );
            },
          ),

          // Boton ver mas
          if (onViewAll != null && activities.length > maxItems) ...[
            const SizedBox(height: 12),
            Center(
              child: TextButton.icon(
                onPressed: () {
                  Haptics.light();
                  onViewAll!();
                },
                icon: const Icon(Icons.expand_more, size: 20),
                label: Text(viewAllLabel ?? 'Ver toda la actividad'),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildSkeletonTimeline(BuildContext context) {
    return Column(
      children: List.generate(
        3,
        (index) => _SkeletonTimelineItem(
          isLast: index == 2,
          colorScheme: Theme.of(context).colorScheme,
        ),
      ),
    );
  }

  Widget _buildEmptyState(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Semantics(
      label: 'No hay actividad reciente',
      child: Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: colorScheme.surfaceContainerLow,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.history,
              size: 48,
              color: colorScheme.onSurfaceVariant.withOpacity(0.5),
            ),
            const SizedBox(height: 12),
            Text(
              'Sin actividad reciente',
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
            ),
            const SizedBox(height: 4),
            Text(
              'Tu actividad aparecera aqui',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: colorScheme.onSurfaceVariant.withOpacity(0.7),
                  ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Item individual del timeline de actividad
class ActivityTimelineItem extends StatelessWidget {
  final UserActivity activity;
  final bool isLast;
  final VoidCallback? onTap;

  const ActivityTimelineItem({
    super.key,
    required this.activity,
    this.isLast = false,
    this.onTap,
  });

  Color _getColorFromHex(String hexColor) {
    final buffer = StringBuffer();
    if (hexColor.length == 6 || hexColor.length == 7) buffer.write('ff');
    buffer.write(hexColor.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  IconData _getIconForType(String type, String iconName) {
    // Primero intentar por nombre de icono explicito
    final iconMap = {
      'check_circle': Icons.check_circle,
      'shopping_basket': Icons.shopping_basket,
      'volunteer_activism': Icons.volunteer_activism,
      'event': Icons.event,
      'message': Icons.message,
      'notifications': Icons.notifications,
      'star': Icons.star,
      'favorite': Icons.favorite,
      'payment': Icons.payment,
      'local_offer': Icons.local_offer,
      'person_add': Icons.person_add,
      'edit': Icons.edit,
      'delete': Icons.delete,
      'upload': Icons.upload,
      'download': Icons.download,
      'share': Icons.share,
    };

    if (iconMap.containsKey(iconName)) {
      return iconMap[iconName]!;
    }

    // Fallback por tipo de actividad
    final typeIconMap = {
      'reservation': Icons.calendar_today,
      'purchase': Icons.shopping_cart,
      'message': Icons.chat_bubble,
      'notification': Icons.notifications,
      'achievement': Icons.emoji_events,
      'social': Icons.people,
      'payment': Icons.payment,
      'service': Icons.handshake,
      'update': Icons.update,
      'login': Icons.login,
      'general': Icons.circle,
    };

    return typeIconMap[type] ?? Icons.circle;
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final activityColor = _getColorFromHex(activity.colorHex);
    final iconData = _getIconForType(activity.type, activity.iconName);

    // Etiqueta de accesibilidad
    final String accessibilityLabel = _buildAccessibilityLabel();

    return Semantics(
      label: accessibilityLabel,
      button: onTap != null || activity.actionRoute != null,
      child: InkWell(
        onTap: onTap != null
            ? () {
                Haptics.light();
                onTap!();
              }
            : null,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Columna del indicador (circulo + linea)
                _buildTimelineIndicator(colorScheme, activityColor, iconData),

                const SizedBox(width: 12),

                // Contenido de la actividad
                Expanded(
                  child: _buildActivityContent(context, colorScheme, activityColor),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTimelineIndicator(
    ColorScheme colorScheme,
    Color activityColor,
    IconData iconData,
  ) {
    return Column(
      children: [
        // Circulo con icono
        ExcludeSemantics(
          child: Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: activityColor.withOpacity(0.1),
              shape: BoxShape.circle,
              border: Border.all(
                color: activityColor.withOpacity(0.3),
                width: 2,
              ),
            ),
            child: Icon(
              iconData,
              color: activityColor,
              size: 20,
            ),
          ),
        ),

        // Linea vertical (excepto en el ultimo item)
        if (!isLast)
          Expanded(
            child: Container(
              width: 2,
              margin: const EdgeInsets.symmetric(vertical: 4),
              decoration: BoxDecoration(
                color: colorScheme.outlineVariant,
                borderRadius: BorderRadius.circular(1),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildActivityContent(
    BuildContext context,
    ColorScheme colorScheme,
    Color activityColor,
  ) {
    return Container(
      margin: EdgeInsets.only(bottom: isLast ? 0 : 16),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: colorScheme.surfaceContainerLow,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: colorScheme.outlineVariant.withOpacity(0.5),
          width: 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Encabezado: titulo y tiempo
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: ExcludeSemantics(
                  child: Text(
                    activity.title,
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w600,
                          color: colorScheme.onSurface,
                        ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              ExcludeSemantics(
                child: Text(
                  activity.relativeTime,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 4),

          // Descripcion
          ExcludeSemantics(
            child: Text(
              activity.description,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: colorScheme.onSurfaceVariant,
                  ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),

          // Boton de accion si existe
          if (activity.actionLabel != null && activity.actionRoute != null) ...[
            const SizedBox(height: 8),
            _buildActionButton(context, activityColor),
          ],
        ],
      ),
    );
  }

  Widget _buildActionButton(BuildContext context, Color activityColor) {
    return ExcludeSemantics(
      child: Align(
        alignment: Alignment.centerLeft,
        child: TextButton(
          onPressed: () {
            Haptics.light();
            // La navegacion se maneja desde el onTap principal
            onTap?.call();
          },
          style: TextButton.styleFrom(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            minimumSize: Size.zero,
            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                activity.actionLabel!,
                style: TextStyle(
                  color: activityColor,
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
              const SizedBox(width: 4),
              Icon(
                Icons.arrow_forward,
                size: 14,
                color: activityColor,
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _buildAccessibilityLabel() {
    final buffer = StringBuffer();
    buffer.write(activity.title);
    buffer.write('. ${activity.description}');
    buffer.write('. ${activity.relativeTime}');

    if (activity.actionLabel != null) {
      buffer.write('. Toca para ${activity.actionLabel}');
    }

    return buffer.toString();
  }
}

/// Skeleton item para estado de carga
class _SkeletonTimelineItem extends StatefulWidget {
  final bool isLast;
  final ColorScheme colorScheme;

  const _SkeletonTimelineItem({
    required this.isLast,
    required this.colorScheme,
  });

  @override
  State<_SkeletonTimelineItem> createState() => _SkeletonTimelineItemState();
}

class _SkeletonTimelineItemState extends State<_SkeletonTimelineItem>
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
        return Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Skeleton indicador
                Column(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: _getShimmerColor(),
                        shape: BoxShape.circle,
                      ),
                    ),
                    if (!widget.isLast)
                      Expanded(
                        child: Container(
                          width: 2,
                          margin: const EdgeInsets.symmetric(vertical: 4),
                          color: widget.colorScheme.outlineVariant,
                        ),
                      ),
                  ],
                ),

                const SizedBox(width: 12),

                // Skeleton contenido
                Expanded(
                  child: Container(
                    margin: EdgeInsets.only(bottom: widget.isLast ? 0 : 16),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: widget.colorScheme.surfaceContainerLow,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Container(
                                height: 16,
                                decoration: BoxDecoration(
                                  color: _getShimmerColor(),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                              ),
                            ),
                            const SizedBox(width: 16),
                            Container(
                              width: 60,
                              height: 12,
                              decoration: BoxDecoration(
                                color: _getShimmerColor(),
                                borderRadius: BorderRadius.circular(4),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Container(
                          width: double.infinity,
                          height: 12,
                          decoration: BoxDecoration(
                            color: _getShimmerColor(),
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Container(
                          width: 150,
                          height: 12,
                          decoration: BoxDecoration(
                            color: _getShimmerColor(),
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                      ],
                    ),
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

/// Seccion de actividad con titulo
class ActivitySection extends StatelessWidget {
  final String title;
  final List<UserActivity> activities;
  final bool isLoading;
  final Function(UserActivity)? onActivityTap;
  final int maxItems;
  final VoidCallback? onViewAll;

  const ActivitySection({
    super.key,
    this.title = 'Actividad reciente',
    required this.activities,
    this.isLoading = false,
    this.onActivityTap,
    this.maxItems = 5,
    this.onViewAll,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Semantics(
          header: true,
          child: Text(
            title,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 16),
        ActivityTimeline(
          activities: activities,
          isLoading: isLoading,
          onActivityTap: onActivityTap,
          maxItems: maxItems,
          onViewAll: onViewAll,
        ),
      ],
    );
  }
}
