import 'package:flutter/material.dart';
import '../../../core/utils/haptics.dart';

/// Modelo para una accion rapida
class QuickAction {
  final String id;
  final String label;
  final String iconName;
  final String colorHex;
  final String? route;
  final int? badgeCount;
  final bool isEnabled;
  final VoidCallback? onTap;

  const QuickAction({
    required this.id,
    required this.label,
    required this.iconName,
    this.colorHex = '#2196F3',
    this.route,
    this.badgeCount,
    this.isEnabled = true,
    this.onTap,
  });

  /// Indica si hay badge para mostrar
  bool get hasBadge => badgeCount != null && badgeCount! > 0;

  factory QuickAction.fromJson(Map<String, dynamic> json) {
    return QuickAction(
      id: json['id'] as String? ?? '',
      label: json['label'] as String? ?? '',
      iconName: json['icon'] as String? ?? 'star',
      colorHex: json['color'] as String? ?? '#2196F3',
      route: json['route'] as String?,
      badgeCount: json['badge_count'] as int?,
      isEnabled: json['enabled'] as bool? ?? true,
    );
  }
}

/// Grid de acciones rapidas para el dashboard del cliente
class QuickActionsGrid extends StatelessWidget {
  final List<QuickAction> actions;
  final Function(QuickAction) onActionTap;
  final int crossAxisCount;
  final double spacing;
  final bool showLabels;

  const QuickActionsGrid({
    super.key,
    required this.actions,
    required this.onActionTap,
    this.crossAxisCount = 4,
    this.spacing = 16,
    this.showLabels = true,
  });

  @override
  Widget build(BuildContext context) {
    if (actions.isEmpty) {
      return const SizedBox.shrink();
    }

    return Semantics(
      label: 'Acciones rapidas con ${actions.length} opciones',
      child: LayoutBuilder(
        builder: (context, constraints) {
          // Calcular tamano de cada item basado en el ancho disponible
          final itemWidth = (constraints.maxWidth - (spacing * (crossAxisCount - 1))) / crossAxisCount;

          return Wrap(
            spacing: spacing,
            runSpacing: spacing,
            alignment: WrapAlignment.start,
            children: actions.map((action) {
              return SizedBox(
                width: itemWidth,
                child: QuickActionItem(
                  action: action,
                  onTap: () => onActionTap(action),
                  showLabel: showLabels,
                ),
              );
            }).toList(),
          );
        },
      ),
    );
  }
}

/// Item individual de accion rapida
class QuickActionItem extends StatefulWidget {
  final QuickAction action;
  final VoidCallback onTap;
  final bool showLabel;
  final double iconSize;

  const QuickActionItem({
    super.key,
    required this.action,
    required this.onTap,
    this.showLabel = true,
    this.iconSize = 28,
  });

  @override
  State<QuickActionItem> createState() => _QuickActionItemState();
}

class _QuickActionItemState extends State<QuickActionItem>
    with SingleTickerProviderStateMixin {
  late AnimationController _scaleController;
  late Animation<double> _scaleAnimation;
  bool _isPressed = false;

  @override
  void initState() {
    super.initState();
    _scaleController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 150),
    );
    _scaleAnimation = Tween<double>(begin: 1.0, end: 0.92).animate(
      CurvedAnimation(parent: _scaleController, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _scaleController.dispose();
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
      // Reservas y calendario
      'calendar_today': Icons.calendar_today,
      'event': Icons.event,
      'schedule': Icons.schedule,
      'bookmark': Icons.bookmark,

      // Chat y comunicacion
      'chat': Icons.chat_bubble,
      'chat_bubble': Icons.chat_bubble,
      'smart_toy': Icons.smart_toy,
      'message': Icons.message,
      'forum': Icons.forum,
      'notifications': Icons.notifications,

      // Compras y mercado
      'shopping_cart': Icons.shopping_cart,
      'shopping_basket': Icons.shopping_basket,
      'storefront': Icons.storefront,
      'store': Icons.store,
      'local_offer': Icons.local_offer,

      // Comunidad
      'people': Icons.people,
      'groups': Icons.groups,
      'volunteer_activism': Icons.volunteer_activism,
      'handshake': Icons.handshake,
      'diversity_3': Icons.diversity_3,

      // Finanzas
      'wallet': Icons.account_balance_wallet,
      'attach_money': Icons.attach_money,
      'payment': Icons.payment,
      'receipt': Icons.receipt_long,

      // Ubicacion
      'location_on': Icons.location_on,
      'map': Icons.map,
      'directions': Icons.directions,
      'explore': Icons.explore,

      // Varios
      'star': Icons.star,
      'favorite': Icons.favorite,
      'settings': Icons.settings,
      'help': Icons.help,
      'info': Icons.info,
      'qr_code': Icons.qr_code,
      'search': Icons.search,
      'person': Icons.person,
      'home': Icons.home,
      'article': Icons.article,
      'photo_library': Icons.photo_library,
      'play_circle': Icons.play_circle,
      'mic': Icons.mic,
      'eco': Icons.eco,
      'local_florist': Icons.local_florist,
      'recycling': Icons.recycling,
      'directions_bike': Icons.directions_bike,
      'local_parking': Icons.local_parking,
    };
    return iconMap[iconName] ?? Icons.touch_app;
  }

  void _handleTapDown(TapDownDetails details) {
    if (!widget.action.isEnabled) return;
    setState(() => _isPressed = true);
    _scaleController.forward();
  }

  void _handleTapUp(TapUpDetails details) {
    if (!widget.action.isEnabled) return;
    setState(() => _isPressed = false);
    _scaleController.reverse();
  }

  void _handleTapCancel() {
    if (!widget.action.isEnabled) return;
    setState(() => _isPressed = false);
    _scaleController.reverse();
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final actionColor = _getColorFromHex(widget.action.colorHex);
    final iconData = _getIconFromName(widget.action.iconName);

    // Etiqueta de accesibilidad
    final String accessibilityLabel = _buildAccessibilityLabel();

    return Semantics(
      label: accessibilityLabel,
      button: true,
      enabled: widget.action.isEnabled,
      child: GestureDetector(
        onTapDown: _handleTapDown,
        onTapUp: _handleTapUp,
        onTapCancel: _handleTapCancel,
        onTap: widget.action.isEnabled
            ? () {
                Haptics.selection();
                widget.onTap();
              }
            : null,
        child: AnimatedBuilder(
          animation: _scaleAnimation,
          builder: (context, child) {
            return Transform.scale(
              scale: _scaleAnimation.value,
              child: child,
            );
          },
          child: Opacity(
            opacity: widget.action.isEnabled ? 1.0 : 0.5,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Contenedor del icono con badge
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    // Circulo con icono
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: _isPressed
                            ? actionColor.withOpacity(0.2)
                            : actionColor.withOpacity(0.1),
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: actionColor.withOpacity(_isPressed ? 0.4 : 0.2),
                          width: 2,
                        ),
                      ),
                      child: ExcludeSemantics(
                        child: Icon(
                          iconData,
                          color: actionColor,
                          size: widget.iconSize,
                        ),
                      ),
                    ),

                    // Badge de notificacion
                    if (widget.action.hasBadge)
                      Positioned(
                        right: -4,
                        top: -4,
                        child: _buildBadge(colorScheme),
                      ),
                  ],
                ),

                // Label
                if (widget.showLabel) ...[
                  const SizedBox(height: 8),
                  ExcludeSemantics(
                    child: Text(
                      widget.action.label,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: colorScheme.onSurface,
                            fontWeight: FontWeight.w500,
                          ),
                      textAlign: TextAlign.center,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBadge(ColorScheme colorScheme) {
    final badgeCount = widget.action.badgeCount!;
    final displayText = badgeCount > 99 ? '99+' : badgeCount.toString();

    return ExcludeSemantics(
      child: Container(
        padding: EdgeInsets.symmetric(
          horizontal: badgeCount > 9 ? 6 : 4,
          vertical: 2,
        ),
        constraints: const BoxConstraints(minWidth: 20),
        decoration: BoxDecoration(
          color: colorScheme.error,
          borderRadius: BorderRadius.circular(10),
          boxShadow: [
            BoxShadow(
              color: colorScheme.error.withOpacity(0.3),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Text(
          displayText,
          style: TextStyle(
            color: colorScheme.onError,
            fontSize: 11,
            fontWeight: FontWeight.bold,
          ),
          textAlign: TextAlign.center,
        ),
      ),
    );
  }

  String _buildAccessibilityLabel() {
    final buffer = StringBuffer();
    buffer.write(widget.action.label);

    if (widget.action.hasBadge) {
      buffer.write('. ${widget.action.badgeCount} notificaciones pendientes');
    }

    if (!widget.action.isEnabled) {
      buffer.write('. No disponible');
    }

    return buffer.toString();
  }
}

/// Variante compacta de acciones rapidas usando chips
class QuickActionsChips extends StatelessWidget {
  final List<QuickAction> actions;
  final Function(QuickAction) onActionTap;
  final double spacing;

  const QuickActionsChips({
    super.key,
    required this.actions,
    required this.onActionTap,
    this.spacing = 8,
  });

  Color _getColorFromHex(String hexColor) {
    final buffer = StringBuffer();
    if (hexColor.length == 6 || hexColor.length == 7) buffer.write('ff');
    buffer.write(hexColor.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  IconData _getIconFromName(String iconName) {
    final iconMap = {
      'calendar_today': Icons.calendar_today,
      'chat_bubble': Icons.chat_bubble,
      'shopping_basket': Icons.shopping_basket,
      'volunteer_activism': Icons.volunteer_activism,
      'storefront': Icons.storefront,
      'event': Icons.event,
      'smart_toy': Icons.smart_toy,
    };
    return iconMap[iconName] ?? Icons.touch_app;
  }

  @override
  Widget build(BuildContext context) {
    if (actions.isEmpty) {
      return const SizedBox.shrink();
    }

    return Semantics(
      label: 'Acciones rapidas',
      child: Wrap(
        spacing: spacing,
        runSpacing: spacing,
        children: actions.map((action) {
          final actionColor = _getColorFromHex(action.colorHex);
          final iconData = _getIconFromName(action.iconName);

          return Semantics(
            label: '${action.label}${action.hasBadge ? ', ${action.badgeCount} pendientes' : ''}',
            button: true,
            enabled: action.isEnabled,
            child: Badge(
              isLabelVisible: action.hasBadge,
              label: Text(
                action.badgeCount.toString(),
                style: const TextStyle(fontSize: 10),
              ),
              child: ActionChip(
                avatar: Icon(
                  iconData,
                  size: 18,
                  color: action.isEnabled ? actionColor : Colors.grey,
                ),
                label: Text(action.label),
                onPressed: action.isEnabled
                    ? () {
                        Haptics.selection();
                        onActionTap(action);
                      }
                    : null,
                side: BorderSide(
                  color: actionColor.withOpacity(action.isEnabled ? 0.3 : 0.1),
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

/// Seccion de acciones rapidas con titulo
class QuickActionsSection extends StatelessWidget {
  final String title;
  final List<QuickAction> actions;
  final Function(QuickAction) onActionTap;
  final bool useChips;
  final int gridColumns;

  const QuickActionsSection({
    super.key,
    this.title = 'Acciones rapidas',
    required this.actions,
    required this.onActionTap,
    this.useChips = false,
    this.gridColumns = 4,
  });

  @override
  Widget build(BuildContext context) {
    if (actions.isEmpty) {
      return const SizedBox.shrink();
    }

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
        if (useChips)
          QuickActionsChips(
            actions: actions,
            onActionTap: onActionTap,
          )
        else
          QuickActionsGrid(
            actions: actions,
            onActionTap: onActionTap,
            crossAxisCount: gridColumns,
          ),
      ],
    );
  }
}
