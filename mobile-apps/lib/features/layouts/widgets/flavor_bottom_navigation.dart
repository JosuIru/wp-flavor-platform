/// Flavor Bottom Navigation Widget
///
/// Widget de navegación inferior sincronizado con WordPress.
/// Usa la configuración de layouts para mostrar los items correctos.

import 'package:flutter/material.dart';
import '../layout_config.dart';

/// Bottom Navigation personalizable
class FlavorBottomNavigation extends StatelessWidget {
  final int currentIndex;
  final Function(int) onTap;
  final List<NavigationItem>? items;
  final Color? backgroundColor;
  final Color? selectedColor;
  final Color? unselectedColor;
  final bool showLabels;
  final NavigationDestinationLabelBehavior? labelBehavior;

  const FlavorBottomNavigation({
    super.key,
    required this.currentIndex,
    required this.onTap,
    this.items,
    this.backgroundColor,
    this.selectedColor,
    this.unselectedColor,
    this.showLabels = true,
    this.labelBehavior,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final navItems = items ?? const <NavigationItem>[];

    // Limitar a 5 items máximo para bottom nav
    final displayItems = navItems.take(5).toList();

    // NavigationBar requiere mínimo 2 destinos
    if (displayItems.length < 2) {
      return const SizedBox.shrink();
    }

    return NavigationBar(
      selectedIndex: currentIndex.clamp(0, displayItems.length - 1),
      onDestinationSelected: onTap,
      backgroundColor: backgroundColor ?? theme.colorScheme.surface,
      indicatorColor: (selectedColor ?? theme.colorScheme.primary).withOpacity(0.1),
      labelBehavior: labelBehavior ??
          (showLabels
              ? NavigationDestinationLabelBehavior.alwaysShow
              : NavigationDestinationLabelBehavior.alwaysHide),
      destinations: displayItems.map((item) {
        return NavigationDestination(
          icon: Icon(
            item.iconData,
            color: unselectedColor ?? theme.colorScheme.onSurfaceVariant,
          ),
          selectedIcon: Icon(
            item.iconData,
            color: selectedColor ?? theme.colorScheme.primary,
          ),
          label: item.title,
        );
      }).toList(),
    );
  }
}

/// Bottom Navigation con badges (notificaciones)
class FlavorBottomNavigationWithBadges extends StatelessWidget {
  final int currentIndex;
  final Function(int) onTap;
  final List<NavigationItem>? items;
  final Map<int, int> badges; // index -> badge count
  final Color? badgeColor;
  final Color? backgroundColor;
  final Color? selectedColor;
  final Color? unselectedColor;

  const FlavorBottomNavigationWithBadges({
    super.key,
    required this.currentIndex,
    required this.onTap,
    this.items,
    this.badges = const {},
    this.badgeColor,
    this.backgroundColor,
    this.selectedColor,
    this.unselectedColor,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final navItems = items ?? const <NavigationItem>[];
    final displayItems = navItems.take(5).toList();

    if (displayItems.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      decoration: BoxDecoration(
        color: backgroundColor ?? theme.colorScheme.surface,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: List.generate(displayItems.length, (index) {
              final item = displayItems[index];
              final isSelected = index == currentIndex;
              final badgeCount = badges[index] ?? 0;

              return _NavItem(
                item: item,
                isSelected: isSelected,
                badgeCount: badgeCount,
                badgeColor: badgeColor ?? theme.colorScheme.error,
                selectedColor: selectedColor ?? theme.colorScheme.primary,
                unselectedColor: unselectedColor ?? theme.colorScheme.onSurfaceVariant,
                onTap: () => onTap(index),
              );
            }),
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  final NavigationItem item;
  final bool isSelected;
  final int badgeCount;
  final Color badgeColor;
  final Color selectedColor;
  final Color unselectedColor;
  final VoidCallback onTap;

  const _NavItem({
    required this.item,
    required this.isSelected,
    required this.badgeCount,
    required this.badgeColor,
    required this.selectedColor,
    required this.unselectedColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? selectedColor.withOpacity(0.1) : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Icon(
                  item.iconData,
                  color: isSelected ? selectedColor : unselectedColor,
                  size: 24,
                ),
                if (badgeCount > 0)
                  Positioned(
                    right: -8,
                    top: -4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                      decoration: BoxDecoration(
                        color: badgeColor,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      constraints: const BoxConstraints(minWidth: 16),
                      child: Text(
                        badgeCount > 99 ? '99+' : badgeCount.toString(),
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
            ),
            const SizedBox(height: 4),
            Text(
              item.title,
              style: TextStyle(
                color: isSelected ? selectedColor : unselectedColor,
                fontSize: 11,
                fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
              ),
            ),
            if (isSelected)
              Container(
                margin: const EdgeInsets.only(top: 4),
                width: 4,
                height: 4,
                decoration: BoxDecoration(
                  color: selectedColor,
                  shape: BoxShape.circle,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// Floating Bottom Navigation (estilo moderno)
class FlavorFloatingBottomNav extends StatelessWidget {
  final int currentIndex;
  final Function(int) onTap;
  final List<NavigationItem>? items;
  final Color? backgroundColor;
  final Color? selectedColor;
  final double? elevation;

  const FlavorFloatingBottomNav({
    super.key,
    required this.currentIndex,
    required this.onTap,
    this.items,
    this.backgroundColor,
    this.selectedColor,
    this.elevation,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final navItems = items ?? const <NavigationItem>[];
    final displayItems = navItems.take(5).toList();

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Material(
        elevation: elevation ?? 8,
        borderRadius: BorderRadius.circular(24),
        color: backgroundColor ?? theme.colorScheme.surface,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            mainAxisSize: MainAxisSize.min,
            children: List.generate(displayItems.length, (index) {
              final item = displayItems[index];
              final isSelected = index == currentIndex;

              return GestureDetector(
                onTap: () => onTap(index),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  padding: EdgeInsets.symmetric(
                    horizontal: isSelected ? 20 : 16,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: isSelected
                        ? (selectedColor ?? theme.colorScheme.primary)
                        : Colors.transparent,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        item.iconData,
                        color: isSelected
                            ? Colors.white
                            : theme.colorScheme.onSurfaceVariant,
                        size: 22,
                      ),
                      if (isSelected) ...[
                        const SizedBox(width: 8),
                        Text(
                          item.title,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w600,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              );
            }),
          ),
        ),
      ),
    );
  }
}
