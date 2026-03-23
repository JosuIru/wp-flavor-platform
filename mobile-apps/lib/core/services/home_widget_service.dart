import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:home_widget/home_widget.dart';
import '../api/api_client.dart';
import '../config/app_config.dart';

/// Servicio para gestionar widgets de pantalla de inicio
/// iOS: WidgetKit widgets
/// Android: App Widgets (HomeScreen Widgets)
class HomeWidgetService {
  static HomeWidgetService? _instance;

  final ApiClient _apiClient;
  static const String _appGroupId = 'group.com.flavor.app';
  static const String _androidWidgetName = 'FlavorHomeWidget';
  static const String _iosWidgetName = 'FlavorWidget';

  HomeWidgetService._({required ApiClient apiClient}) : _apiClient = apiClient;

  factory HomeWidgetService({required ApiClient apiClient}) {
    _instance ??= HomeWidgetService._(apiClient: apiClient);
    return _instance!;
  }

  /// Inicializar servicio de widgets
  Future<void> initialize() async {
    // Configurar App Group para iOS
    await HomeWidget.setAppGroupId(_appGroupId);

    // Registrar callback para interacciones
    HomeWidget.registerInteractivityCallback(backgroundCallback);

    debugPrint('[HomeWidget] Servicio inicializado');
  }

  /// Callback para manejar interacciones en background
  @pragma('vm:entry-point')
  static Future<void> backgroundCallback(Uri? uri) async {
    if (uri == null) return;

    debugPrint('[HomeWidget] Callback recibido: $uri');

    // Manejar diferentes acciones
    final action = uri.host;
    final params = uri.queryParameters;

    switch (action) {
      case 'open_event':
        // Abrir evento específico
        final eventId = params['id'];
        debugPrint('[HomeWidget] Abrir evento: $eventId');
        break;
      case 'open_cart':
        debugPrint('[HomeWidget] Abrir carrito');
        break;
      case 'refresh':
        debugPrint('[HomeWidget] Refrescar datos');
        break;
    }
  }

  /// Actualizar widget de eventos próximos
  Future<void> updateEventsWidget() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/eventos', queryParameters: {
        'limit': '3',
        'upcoming': 'true',
      });

      final responseData = response.data;
      final events = responseData?['events'] as List? ?? [];

      // Guardar datos para el widget
      await HomeWidget.saveWidgetData('events_count', events.length);
      await HomeWidget.saveWidgetData('events_data', jsonEncode(events));

      if (events.isNotEmpty) {
        final nextEvent = events.first;
        await HomeWidget.saveWidgetData('next_event_title', nextEvent['title'] ?? '');
        await HomeWidget.saveWidgetData('next_event_date', nextEvent['date'] ?? '');
        await HomeWidget.saveWidgetData('next_event_image', nextEvent['image'] ?? '');
      }

      await HomeWidget.saveWidgetData('last_update', DateTime.now().toIso8601String());

      // Actualizar widget
      await _updateWidget();

      debugPrint('[HomeWidget] Widget de eventos actualizado');
    } catch (e) {
      debugPrint('[HomeWidget] Error actualizando eventos: $e');
    }
  }

  /// Actualizar widget de estadísticas
  Future<void> updateStatsWidget() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/user/stats');
      final stats = response.data;

      await HomeWidget.saveWidgetData('stats_points', stats?['points'] ?? 0);
      await HomeWidget.saveWidgetData('stats_orders', stats?['orders_count'] ?? 0);
      await HomeWidget.saveWidgetData('stats_events', stats?['events_attended'] ?? 0);
      await HomeWidget.saveWidgetData('stats_badge', stats?['current_badge'] ?? '');

      await _updateWidget();

      debugPrint('[HomeWidget] Widget de estadísticas actualizado');
    } catch (e) {
      debugPrint('[HomeWidget] Error actualizando estadísticas: $e');
    }
  }

  /// Actualizar widget de carrito / pedido activo
  Future<void> updateCartWidget() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/cart/summary');
      final cart = response.data;

      await HomeWidget.saveWidgetData('cart_items', cart?['items_count'] ?? 0);
      await HomeWidget.saveWidgetData('cart_total', cart?['total'] ?? '0.00');
      await HomeWidget.saveWidgetData('cart_currency', cart?['currency'] ?? '€');

      // Para grupos de consumo: pedido activo
      if (cart?['active_order'] != null) {
        final order = cart?['active_order'] as Map<String, dynamic>?;
        await HomeWidget.saveWidgetData('order_status', order?['status'] ?? '');
        await HomeWidget.saveWidgetData('order_deadline', order?['deadline'] ?? '');
      }

      await _updateWidget();

      debugPrint('[HomeWidget] Widget de carrito actualizado');
    } catch (e) {
      debugPrint('[HomeWidget] Error actualizando carrito: $e');
    }
  }

  /// Actualizar widget de notificaciones
  Future<void> updateNotificationsWidget() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/notifications/unread');
      final notifications = response.data?['notifications'] as List? ?? [];

      await HomeWidget.saveWidgetData('notifications_count', notifications.length);

      if (notifications.isNotEmpty) {
        final latest = notifications.first;
        await HomeWidget.saveWidgetData('notification_title', latest['title'] ?? '');
        await HomeWidget.saveWidgetData('notification_body', latest['body'] ?? '');
        await HomeWidget.saveWidgetData('notification_time', latest['time'] ?? '');
      }

      await _updateWidget();

      debugPrint('[HomeWidget] Widget de notificaciones actualizado');
    } catch (e) {
      debugPrint('[HomeWidget] Error actualizando notificaciones: $e');
    }
  }

  /// Actualizar widget de reservas
  Future<void> updateReservationsWidget() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/reservas/proximas');
      final reservations = response.data?['reservations'] as List? ?? [];

      await HomeWidget.saveWidgetData('reservations_count', reservations.length);
      await HomeWidget.saveWidgetData('reservations_data', jsonEncode(reservations));

      if (reservations.isNotEmpty) {
        final next = reservations.first;
        await HomeWidget.saveWidgetData('next_reservation_name', next['resource_name'] ?? '');
        await HomeWidget.saveWidgetData('next_reservation_date', next['date'] ?? '');
        await HomeWidget.saveWidgetData('next_reservation_time', next['time'] ?? '');
      }

      await _updateWidget();

      debugPrint('[HomeWidget] Widget de reservas actualizado');
    } catch (e) {
      debugPrint('[HomeWidget] Error actualizando reservas: $e');
    }
  }

  /// Actualizar todos los widgets
  Future<void> updateAllWidgets() async {
    await Future.wait([
      updateEventsWidget(),
      updateStatsWidget(),
      updateCartWidget(),
      updateNotificationsWidget(),
      updateReservationsWidget(),
    ]);
  }

  /// Guardar dato genérico en widget
  Future<void> saveData(String key, dynamic value) async {
    if (value is String) {
      await HomeWidget.saveWidgetData(key, value);
    } else if (value is int) {
      await HomeWidget.saveWidgetData(key, value);
    } else if (value is double) {
      await HomeWidget.saveWidgetData(key, value);
    } else if (value is bool) {
      await HomeWidget.saveWidgetData(key, value);
    } else {
      await HomeWidget.saveWidgetData(key, jsonEncode(value));
    }
  }

  /// Obtener dato del widget
  Future<T?> getData<T>(String key) async {
    return await HomeWidget.getWidgetData<T>(key);
  }

  /// Actualizar el widget nativo
  Future<void> _updateWidget() async {
    try {
      await HomeWidget.updateWidget(
        iOSName: _iosWidgetName,
        androidName: _androidWidgetName,
      );
    } catch (e) {
      debugPrint('[HomeWidget] Error actualizando widget nativo: $e');
    }
  }

  /// Registrar widget específico para actualización
  Future<void> updateSpecificWidget(String widgetName) async {
    try {
      await HomeWidget.updateWidget(
        iOSName: widgetName,
        androidName: widgetName,
      );
    } catch (e) {
      debugPrint('[HomeWidget] Error actualizando $widgetName: $e');
    }
  }

  /// Obtener URI inicial si la app fue lanzada desde widget
  Future<Uri?> getInitialUri() async {
    return await HomeWidget.initiallyLaunchedFromHomeWidget();
  }

  /// Stream de URIs cuando se interactúa con widgets
  Stream<Uri?> get widgetClicks => HomeWidget.widgetClicked;
}

/// Tipos de widgets disponibles
enum HomeWidgetType {
  events('FlavorEventsWidget', 'Próximos Eventos'),
  stats('FlavorStatsWidget', 'Mis Estadísticas'),
  cart('FlavorCartWidget', 'Mi Carrito'),
  notifications('FlavorNotificationsWidget', 'Notificaciones'),
  reservations('FlavorReservationsWidget', 'Mis Reservas'),
  quickActions('FlavorQuickActionsWidget', 'Acciones Rápidas');

  final String widgetName;
  final String displayName;

  const HomeWidgetType(this.widgetName, this.displayName);
}

/// Configuración de widget
class HomeWidgetConfig {
  final HomeWidgetType type;
  final Duration refreshInterval;
  final bool showBadge;
  final String? backgroundColor;
  final String? accentColor;

  const HomeWidgetConfig({
    required this.type,
    this.refreshInterval = const Duration(minutes: 30),
    this.showBadge = true,
    this.backgroundColor,
    this.accentColor,
  });

  Map<String, dynamic> toJson() => {
    'type': type.widgetName,
    'refresh_interval_minutes': refreshInterval.inMinutes,
    'show_badge': showBadge,
    'background_color': backgroundColor,
    'accent_color': accentColor,
  };
}

/// Extensión para configurar widgets según módulos activos
extension HomeWidgetModules on HomeWidgetService {
  /// Obtener widgets disponibles según módulos activos
  List<HomeWidgetType> getAvailableWidgets(List<String> activeModules) {
    final available = <HomeWidgetType>[
      HomeWidgetType.quickActions, // Siempre disponible
    ];

    if (activeModules.contains('eventos')) {
      available.add(HomeWidgetType.events);
    }

    if (activeModules.contains('marketplace') || activeModules.contains('grupos-consumo')) {
      available.add(HomeWidgetType.cart);
    }

    if (activeModules.contains('reservas') || activeModules.contains('espacios-comunes')) {
      available.add(HomeWidgetType.reservations);
    }

    if (activeModules.contains('socios')) {
      available.add(HomeWidgetType.stats);
    }

    // Notificaciones siempre disponibles
    available.add(HomeWidgetType.notifications);

    return available;
  }
}
