import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:quick_actions/quick_actions.dart';

/// Servicio para gestionar acciones rápidas (App Shortcuts)
/// iOS: 3D Touch / Haptic Touch shortcuts
/// Android: App shortcuts (long press on icon)
class QuickActionsService {
  static QuickActionsService? _instance;

  final QuickActions _quickActions = const QuickActions();
  final List<ShortcutItem> _registeredShortcuts = [];
  Function(String)? _onActionHandler;

  QuickActionsService._();

  factory QuickActionsService() {
    _instance ??= QuickActionsService._();
    return _instance!;
  }

  /// Inicializar el servicio con un handler de acciones
  Future<void> initialize({
    required Function(String actionType) onAction,
    required List<QuickActionItem> actions,
  }) async {
    _onActionHandler = onAction;

    // Configurar handler
    _quickActions.initialize((String shortcutType) {
      debugPrint('[QuickActions] Acción recibida: $shortcutType');
      _onActionHandler?.call(shortcutType);
    });

    // Registrar shortcuts
    await setShortcuts(actions);
  }

  /// Establecer los shortcuts disponibles
  Future<void> setShortcuts(List<QuickActionItem> actions) async {
    final shortcuts = actions.map((action) => ShortcutItem(
      type: action.type,
      localizedTitle: action.title,
      icon: _getPlatformIcon(action.icon),
    )).toList();

    _registeredShortcuts
      ..clear()
      ..addAll(shortcuts);

    await _quickActions.setShortcutItems(shortcuts);
    debugPrint('[QuickActions] ${shortcuts.length} shortcuts registrados');
  }

  /// Obtener icono según la plataforma
  String? _getPlatformIcon(QuickActionIcon? icon) {
    if (icon == null) return null;

    if (Platform.isIOS) {
      return icon.iosIcon;
    } else if (Platform.isAndroid) {
      return icon.androidIcon;
    }
    return null;
  }

  /// Limpiar todos los shortcuts
  Future<void> clearShortcuts() async {
    _registeredShortcuts.clear();
    await _quickActions.clearShortcutItems();
  }

  /// Agregar un shortcut dinámicamente
  Future<void> addShortcut(QuickActionItem action) async {
    final shortcut = ShortcutItem(
      type: action.type,
      localizedTitle: action.title,
      icon: _getPlatformIcon(action.icon),
    );

    _registeredShortcuts.add(shortcut);
    await _quickActions.setShortcutItems(_registeredShortcuts);
  }

  /// Eliminar un shortcut por tipo
  Future<void> removeShortcut(String type) async {
    _registeredShortcuts.removeWhere((s) => s.type == type);
    await _quickActions.setShortcutItems(_registeredShortcuts);
  }
}

/// Modelo para definir una acción rápida
class QuickActionItem {
  final String type;
  final String title;
  final QuickActionIcon? icon;
  final String? subtitle;

  const QuickActionItem({
    required this.type,
    required this.title,
    this.icon,
    this.subtitle,
  });
}

/// Iconos para acciones rápidas
class QuickActionIcon {
  final String? iosIcon;
  final String? androidIcon;

  const QuickActionIcon({
    this.iosIcon,
    this.androidIcon,
  });

  // Iconos predefinidos comunes
  static const QuickActionIcon search = QuickActionIcon(
    iosIcon: 'search',
    androidIcon: 'ic_search',
  );

  static const QuickActionIcon add = QuickActionIcon(
    iosIcon: 'add',
    androidIcon: 'ic_add',
  );

  static const QuickActionIcon share = QuickActionIcon(
    iosIcon: 'share',
    androidIcon: 'ic_share',
  );

  static const QuickActionIcon favorite = QuickActionIcon(
    iosIcon: 'heart',
    androidIcon: 'ic_favorite',
  );

  static const QuickActionIcon calendar = QuickActionIcon(
    iosIcon: 'calendar',
    androidIcon: 'ic_event',
  );

  static const QuickActionIcon cart = QuickActionIcon(
    iosIcon: 'cart',
    androidIcon: 'ic_shopping_cart',
  );

  static const QuickActionIcon message = QuickActionIcon(
    iosIcon: 'message',
    androidIcon: 'ic_message',
  );

  static const QuickActionIcon location = QuickActionIcon(
    iosIcon: 'location',
    androidIcon: 'ic_location_on',
  );

  static const QuickActionIcon scan = QuickActionIcon(
    iosIcon: 'qrcode',
    androidIcon: 'ic_qr_code_scanner',
  );

  static const QuickActionIcon notification = QuickActionIcon(
    iosIcon: 'bell',
    androidIcon: 'ic_notifications',
  );
}

/// Acciones rápidas predefinidas para Flavor Platform
class FlavorQuickActions {
  /// Obtener acciones según módulos activos
  static List<QuickActionItem> getActionsForModules(List<String> activeModules) {
    final List<QuickActionItem> actions = [];

    // Siempre incluir búsqueda
    actions.add(const QuickActionItem(
      type: 'search',
      title: 'Buscar',
      icon: QuickActionIcon.search,
    ));

    // Acciones según módulos
    if (activeModules.contains('eventos')) {
      actions.add(const QuickActionItem(
        type: 'eventos_proximos',
        title: 'Próximos eventos',
        icon: QuickActionIcon.calendar,
      ));
    }

    if (activeModules.contains('marketplace')) {
      actions.add(const QuickActionItem(
        type: 'marketplace_carrito',
        title: 'Mi carrito',
        icon: QuickActionIcon.cart,
      ));
    }

    if (activeModules.contains('grupos-consumo')) {
      actions.add(const QuickActionItem(
        type: 'gc_pedido_activo',
        title: 'Pedido activo',
        icon: QuickActionIcon.cart,
      ));
    }

    if (activeModules.contains('reservas')) {
      actions.add(const QuickActionItem(
        type: 'nueva_reserva',
        title: 'Nueva reserva',
        icon: QuickActionIcon.add,
      ));
    }

    if (activeModules.contains('chat-interno')) {
      actions.add(const QuickActionItem(
        type: 'mensajes',
        title: 'Mensajes',
        icon: QuickActionIcon.message,
      ));
    }

    if (activeModules.contains('incidencias')) {
      actions.add(const QuickActionItem(
        type: 'nueva_incidencia',
        title: 'Reportar incidencia',
        icon: QuickActionIcon.notification,
      ));
    }

    if (activeModules.contains('carpooling')) {
      actions.add(const QuickActionItem(
        type: 'buscar_viaje',
        title: 'Buscar viaje',
        icon: QuickActionIcon.location,
      ));
    }

    // Limitar a 4 acciones (límite de iOS)
    return actions.take(4).toList();
  }

  /// Acciones por defecto para app de comunidad
  static List<QuickActionItem> get defaultCommunityActions => const [
    QuickActionItem(
      type: 'search',
      title: 'Buscar',
      icon: QuickActionIcon.search,
    ),
    QuickActionItem(
      type: 'eventos_proximos',
      title: 'Eventos',
      icon: QuickActionIcon.calendar,
    ),
    QuickActionItem(
      type: 'mensajes',
      title: 'Mensajes',
      icon: QuickActionIcon.message,
    ),
    QuickActionItem(
      type: 'escanear_qr',
      title: 'Escanear QR',
      icon: QuickActionIcon.scan,
    ),
  ];

  /// Acciones por defecto para app de marketplace
  static List<QuickActionItem> get defaultMarketplaceActions => const [
    QuickActionItem(
      type: 'search',
      title: 'Buscar productos',
      icon: QuickActionIcon.search,
    ),
    QuickActionItem(
      type: 'marketplace_carrito',
      title: 'Mi carrito',
      icon: QuickActionIcon.cart,
    ),
    QuickActionItem(
      type: 'favoritos',
      title: 'Favoritos',
      icon: QuickActionIcon.favorite,
    ),
    QuickActionItem(
      type: 'escanear_qr',
      title: 'Escanear',
      icon: QuickActionIcon.scan,
    ),
  ];
}

/// Mixin para manejar acciones rápidas en widgets
mixin QuickActionHandler<T extends StatefulWidget> on State<T> {
  /// Navegar según el tipo de acción
  void handleQuickAction(String actionType) {
    debugPrint('[QuickActionHandler] Manejando: $actionType');

    switch (actionType) {
      case 'search':
        _navigateTo('/search');
        break;
      case 'eventos_proximos':
        _navigateTo('/eventos');
        break;
      case 'marketplace_carrito':
        _navigateTo('/carrito');
        break;
      case 'gc_pedido_activo':
        _navigateTo('/grupos-consumo/pedido-activo');
        break;
      case 'nueva_reserva':
        _navigateTo('/reservas/nueva');
        break;
      case 'mensajes':
        _navigateTo('/mensajes');
        break;
      case 'nueva_incidencia':
        _navigateTo('/incidencias/nueva');
        break;
      case 'buscar_viaje':
        _navigateTo('/carpooling/buscar');
        break;
      case 'favoritos':
        _navigateTo('/favoritos');
        break;
      case 'escanear_qr':
        _navigateTo('/qr-scanner');
        break;
      default:
        debugPrint('[QuickActionHandler] Acción no manejada: $actionType');
    }
  }

  void _navigateTo(String route) {
    if (mounted) {
      Navigator.of(context).pushNamed(route);
    }
  }
}
