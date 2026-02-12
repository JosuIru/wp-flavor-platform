import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../providers/providers.dart' show apiClientProvider;
import '../utils/logger.dart';

/// Modelo para estadisticas del usuario
class UserStatistic {
  final String id;
  final String title;
  final String value;
  final double numericValue;
  final double? previousValue;
  final String? trendLabel;
  final double? trendPercentage;
  final String iconName;
  final String colorHex;
  final String type;

  const UserStatistic({
    required this.id,
    required this.title,
    required this.value,
    required this.numericValue,
    this.previousValue,
    this.trendLabel,
    this.trendPercentage,
    required this.iconName,
    required this.colorHex,
    this.type = 'count',
  });

  /// Indica si la tendencia es positiva
  bool get isTrendPositive => (trendPercentage ?? 0) >= 0;

  /// Indica si hay datos de tendencia disponibles
  bool get hasTrend => trendPercentage != null;

  factory UserStatistic.fromJson(Map<String, dynamic> json) {
    return UserStatistic(
      id: json['id'] as String? ?? '',
      title: json['title'] as String? ?? '',
      value: json['value']?.toString() ?? '0',
      numericValue: (json['numeric_value'] as num?)?.toDouble() ?? 0,
      previousValue: (json['previous_value'] as num?)?.toDouble(),
      trendLabel: json['trend_label'] as String?,
      trendPercentage: (json['trend_percentage'] as num?)?.toDouble(),
      iconName: json['icon'] as String? ?? 'star',
      colorHex: json['color'] as String? ?? '#2196F3',
      type: json['type'] as String? ?? 'count',
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'value': value,
        'numeric_value': numericValue,
        'previous_value': previousValue,
        'trend_label': trendLabel,
        'trend_percentage': trendPercentage,
        'icon': iconName,
        'color': colorHex,
        'type': type,
      };
}

/// Modelo para actividad reciente del usuario
class UserActivity {
  final String id;
  final String title;
  final String description;
  final DateTime timestamp;
  final String iconName;
  final String colorHex;
  final String type;
  final String? actionLabel;
  final String? actionRoute;
  final Map<String, dynamic>? metadata;

  const UserActivity({
    required this.id,
    required this.title,
    required this.description,
    required this.timestamp,
    required this.iconName,
    required this.colorHex,
    required this.type,
    this.actionLabel,
    this.actionRoute,
    this.metadata,
  });

  /// Formatea el tiempo relativo (hace X minutos, etc.)
  String get relativeTime {
    final now = DateTime.now();
    final difference = now.difference(timestamp);

    if (difference.inSeconds < 60) {
      return 'Ahora mismo';
    } else if (difference.inMinutes < 60) {
      return 'Hace ${difference.inMinutes} min';
    } else if (difference.inHours < 24) {
      return 'Hace ${difference.inHours} h';
    } else if (difference.inDays == 1) {
      return 'Ayer';
    } else if (difference.inDays < 7) {
      return 'Hace ${difference.inDays} dias';
    } else {
      return '${timestamp.day}/${timestamp.month}/${timestamp.year}';
    }
  }

  factory UserActivity.fromJson(Map<String, dynamic> json) {
    return UserActivity(
      id: json['id']?.toString() ?? '',
      title: json['title'] as String? ?? '',
      description: json['description'] as String? ?? '',
      timestamp: json['timestamp'] != null
          ? DateTime.tryParse(json['timestamp'] as String) ?? DateTime.now()
          : DateTime.now(),
      iconName: json['icon'] as String? ?? 'notifications',
      colorHex: json['color'] as String? ?? '#9E9E9E',
      type: json['type'] as String? ?? 'general',
      actionLabel: json['action_label'] as String?,
      actionRoute: json['action_route'] as String?,
      metadata: json['metadata'] as Map<String, dynamic>?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'description': description,
        'timestamp': timestamp.toIso8601String(),
        'icon': iconName,
        'color': colorHex,
        'type': type,
        'action_label': actionLabel,
        'action_route': actionRoute,
        'metadata': metadata,
      };
}

/// Modelo para notificacion del usuario
class UserNotification {
  final String id;
  final String title;
  final String body;
  final DateTime createdAt;
  final bool isRead;
  final String type;
  final String? actionRoute;
  final Map<String, dynamic>? data;

  const UserNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.createdAt,
    this.isRead = false,
    required this.type,
    this.actionRoute,
    this.data,
  });

  factory UserNotification.fromJson(Map<String, dynamic> json) {
    return UserNotification(
      id: json['id']?.toString() ?? '',
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String) ?? DateTime.now()
          : DateTime.now(),
      isRead: json['is_read'] as bool? ?? false,
      type: json['type'] as String? ?? 'general',
      actionRoute: json['action_route'] as String?,
      data: json['data'] as Map<String, dynamic>?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'body': body,
        'created_at': createdAt.toIso8601String(),
        'is_read': isRead,
        'type': type,
        'action_route': actionRoute,
        'data': data,
      };
}

/// Modelo para perfil del usuario en el dashboard
class UserProfile {
  final String id;
  final String displayName;
  final String email;
  final String? avatarUrl;
  final int level;
  final int points;
  final int pointsToNextLevel;
  final String? memberSince;
  final Map<String, dynamic>? badges;

  const UserProfile({
    required this.id,
    required this.displayName,
    required this.email,
    this.avatarUrl,
    this.level = 1,
    this.points = 0,
    this.pointsToNextLevel = 100,
    this.memberSince,
    this.badges,
  });

  /// Calcula el progreso hacia el siguiente nivel (0.0 - 1.0)
  double get levelProgress {
    if (pointsToNextLevel <= 0) return 1.0;
    return (points % pointsToNextLevel) / pointsToNextLevel;
  }

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      id: json['id']?.toString() ?? '',
      displayName: json['display_name'] as String? ?? 'Usuario',
      email: json['email'] as String? ?? '',
      avatarUrl: json['avatar_url'] as String?,
      level: json['level'] as int? ?? 1,
      points: json['points'] as int? ?? 0,
      pointsToNextLevel: json['points_to_next_level'] as int? ?? 100,
      memberSince: json['member_since'] as String?,
      badges: json['badges'] as Map<String, dynamic>?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'display_name': displayName,
        'email': email,
        'avatar_url': avatarUrl,
        'level': level,
        'points': points,
        'points_to_next_level': pointsToNextLevel,
        'member_since': memberSince,
        'badges': badges,
      };
}

/// Modelo completo de datos del dashboard del cliente
class ClientDashboardData {
  final UserProfile profile;
  final List<UserStatistic> statistics;
  final List<UserActivity> recentActivity;
  final List<UserNotification> notifications;
  final int unreadNotificationsCount;
  final Map<String, dynamic>? moduleWidgets;
  final DateTime lastUpdated;

  const ClientDashboardData({
    required this.profile,
    required this.statistics,
    required this.recentActivity,
    required this.notifications,
    this.unreadNotificationsCount = 0,
    this.moduleWidgets,
    required this.lastUpdated,
  });

  factory ClientDashboardData.fromJson(Map<String, dynamic> json) {
    final statsJson = json['statistics'] as List? ?? [];
    final activityJson = json['recent_activity'] as List? ?? [];
    final notificationsJson = json['notifications'] as List? ?? [];

    return ClientDashboardData(
      profile: UserProfile.fromJson(json['profile'] as Map<String, dynamic>? ?? {}),
      statistics: statsJson.map((statistic) => UserStatistic.fromJson(statistic as Map<String, dynamic>)).toList(),
      recentActivity: activityJson.map((activity) => UserActivity.fromJson(activity as Map<String, dynamic>)).toList(),
      notifications: notificationsJson.map((notification) => UserNotification.fromJson(notification as Map<String, dynamic>)).toList(),
      unreadNotificationsCount: json['unread_notifications_count'] as int? ?? 0,
      moduleWidgets: json['module_widgets'] as Map<String, dynamic>?,
      lastUpdated: json['last_updated'] != null
          ? DateTime.tryParse(json['last_updated'] as String) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() => {
        'profile': profile.toJson(),
        'statistics': statistics.map((statistic) => statistic.toJson()).toList(),
        'recent_activity': recentActivity.map((activity) => activity.toJson()).toList(),
        'notifications': notifications.map((notification) => notification.toJson()).toList(),
        'unread_notifications_count': unreadNotificationsCount,
        'module_widgets': moduleWidgets,
        'last_updated': lastUpdated.toIso8601String(),
      };

  /// Crea una copia con datos actualizados
  ClientDashboardData copyWith({
    UserProfile? profile,
    List<UserStatistic>? statistics,
    List<UserActivity>? recentActivity,
    List<UserNotification>? notifications,
    int? unreadNotificationsCount,
    Map<String, dynamic>? moduleWidgets,
    DateTime? lastUpdated,
  }) {
    return ClientDashboardData(
      profile: profile ?? this.profile,
      statistics: statistics ?? this.statistics,
      recentActivity: recentActivity ?? this.recentActivity,
      notifications: notifications ?? this.notifications,
      unreadNotificationsCount: unreadNotificationsCount ?? this.unreadNotificationsCount,
      moduleWidgets: moduleWidgets ?? this.moduleWidgets,
      lastUpdated: lastUpdated ?? this.lastUpdated,
    );
  }
}

/// Servicio para gestionar datos del dashboard del cliente
class ClientDashboardService {
  final ApiClient _api;
  static const String _cacheKey = 'client_dashboard_cache';
  static const String _cacheTimeKey = 'client_dashboard_cache_time';
  static const int _cacheMaxAgeMinutes = 5;

  ClientDashboardService(this._api);

  /// Obtiene los datos del dashboard del cliente
  /// Primero intenta desde cache, luego del servidor
  Future<ClientDashboardData?> getDashboardData({bool forceRefresh = false}) async {
    try {
      // Intentar cargar desde cache si no se fuerza refresco
      if (!forceRefresh) {
        final cachedData = await _loadFromCache();
        if (cachedData != null) {
          Logger.d('Dashboard cargado desde cache', tag: 'ClientDashboard');
          return cachedData;
        }
      }

      // Obtener del servidor
      final response = await _api.get('/client/dashboard');
      if (response.success && response.data != null) {
        final dashboardData = ClientDashboardData.fromJson(response.data!);

        // Guardar en cache
        await _saveToCache(dashboardData);

        Logger.d('Dashboard cargado del servidor', tag: 'ClientDashboard');
        return dashboardData;
      }

      Logger.e('Error al obtener dashboard: ${response.error}', tag: 'ClientDashboard');
      return null;
    } catch (error, stackTrace) {
      Logger.e('Excepcion en getDashboardData: $error', tag: 'ClientDashboard', error: error);
      debugPrint('Stack: $stackTrace');
      return null;
    }
  }

  /// Obtiene solo las estadisticas del usuario
  Future<List<UserStatistic>> getStatistics() async {
    try {
      final response = await _api.get('/client/statistics');
      if (response.success && response.data != null) {
        final statsJson = response.data!['statistics'] as List? ?? [];
        return statsJson.map((statistic) => UserStatistic.fromJson(statistic as Map<String, dynamic>)).toList();
      }
      return [];
    } catch (error) {
      Logger.e('Error al obtener estadisticas: $error', tag: 'ClientDashboard');
      return [];
    }
  }

  /// Obtiene la actividad reciente del usuario
  Future<List<UserActivity>> getRecentActivity({int limit = 10}) async {
    try {
      final response = await _api.get('/client/activity?limit=$limit');
      if (response.success && response.data != null) {
        final activityJson = response.data!['activity'] as List? ?? [];
        return activityJson.map((activity) => UserActivity.fromJson(activity as Map<String, dynamic>)).toList();
      }
      return [];
    } catch (error) {
      Logger.e('Error al obtener actividad: $error', tag: 'ClientDashboard');
      return [];
    }
  }

  /// Obtiene las notificaciones del usuario
  Future<List<UserNotification>> getNotifications({bool unreadOnly = false}) async {
    try {
      final endpoint = unreadOnly ? '/client/notifications?unread=1' : '/client/notifications';
      final response = await _api.get(endpoint);
      if (response.success && response.data != null) {
        final notificationsJson = response.data!['notifications'] as List? ?? [];
        return notificationsJson.map((notification) => UserNotification.fromJson(notification as Map<String, dynamic>)).toList();
      }
      return [];
    } catch (error) {
      Logger.e('Error al obtener notificaciones: $error', tag: 'ClientDashboard');
      return [];
    }
  }

  /// Marca una notificacion como leida
  Future<bool> markNotificationAsRead(String notificationId) async {
    try {
      final response = await _api.post('/client/notifications/$notificationId/read', data: {});
      return response.success;
    } catch (error) {
      Logger.e('Error al marcar notificacion: $error', tag: 'ClientDashboard');
      return false;
    }
  }

  /// Marca todas las notificaciones como leidas
  Future<bool> markAllNotificationsAsRead() async {
    try {
      final response = await _api.post('/client/notifications/read-all', data: {});
      return response.success;
    } catch (error) {
      Logger.e('Error al marcar notificaciones: $error', tag: 'ClientDashboard');
      return false;
    }
  }

  /// Obtiene el perfil del usuario
  Future<UserProfile?> getUserProfile() async {
    try {
      final response = await _api.get('/client/profile');
      if (response.success && response.data != null) {
        return UserProfile.fromJson(response.data!['profile'] as Map<String, dynamic>? ?? {});
      }
      return null;
    } catch (error) {
      Logger.e('Error al obtener perfil: $error', tag: 'ClientDashboard');
      return null;
    }
  }

  /// Carga datos desde el cache local
  Future<ClientDashboardData?> _loadFromCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedJson = prefs.getString(_cacheKey);
      final cacheTime = prefs.getInt(_cacheTimeKey) ?? 0;
      final now = DateTime.now().millisecondsSinceEpoch;

      // Verificar si el cache ha expirado
      final cacheAgeMinutes = (now - cacheTime) ~/ (1000 * 60);
      if (cacheAgeMinutes > _cacheMaxAgeMinutes) {
        Logger.d('Cache expirado (${cacheAgeMinutes}min)', tag: 'ClientDashboard');
        return null;
      }

      if (cachedJson != null) {
        final jsonData = json.decode(cachedJson) as Map<String, dynamic>;
        return ClientDashboardData.fromJson(jsonData);
      }
    } catch (error) {
      Logger.e('Error al cargar cache: $error', tag: 'ClientDashboard');
    }
    return null;
  }

  /// Guarda datos en el cache local
  Future<void> _saveToCache(ClientDashboardData data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonString = json.encode(data.toJson());
      await prefs.setString(_cacheKey, jsonString);
      await prefs.setInt(_cacheTimeKey, DateTime.now().millisecondsSinceEpoch);
      Logger.d('Dashboard guardado en cache', tag: 'ClientDashboard');
    } catch (error) {
      Logger.e('Error al guardar cache: $error', tag: 'ClientDashboard');
    }
  }

  /// Limpia el cache del dashboard
  Future<void> clearCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_cacheKey);
      await prefs.remove(_cacheTimeKey);
      Logger.d('Cache del dashboard limpiado', tag: 'ClientDashboard');
    } catch (error) {
      Logger.e('Error al limpiar cache: $error', tag: 'ClientDashboard');
    }
  }
}

/// Estado del dashboard del cliente
class ClientDashboardState {
  final ClientDashboardData? data;
  final bool isLoading;
  final bool isRefreshing;
  final String? errorMessage;

  const ClientDashboardState({
    this.data,
    this.isLoading = false,
    this.isRefreshing = false,
    this.errorMessage,
  });

  ClientDashboardState copyWith({
    ClientDashboardData? data,
    bool? isLoading,
    bool? isRefreshing,
    String? errorMessage,
  }) {
    return ClientDashboardState(
      data: data ?? this.data,
      isLoading: isLoading ?? this.isLoading,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      errorMessage: errorMessage,
    );
  }
}

/// Notifier para el estado del dashboard del cliente
class ClientDashboardNotifier extends StateNotifier<ClientDashboardState> {
  final ClientDashboardService _service;

  ClientDashboardNotifier(this._service) : super(const ClientDashboardState()) {
    loadDashboard();
  }

  /// Carga el dashboard (usa cache si esta disponible)
  Future<void> loadDashboard() async {
    state = state.copyWith(isLoading: true, errorMessage: null);

    final data = await _service.getDashboardData();
    if (data != null) {
      state = state.copyWith(data: data, isLoading: false);
    } else {
      state = state.copyWith(
        isLoading: false,
        errorMessage: 'No se pudo cargar el dashboard',
      );
    }
  }

  /// Refresca el dashboard (ignora cache)
  Future<void> refreshDashboard() async {
    state = state.copyWith(isRefreshing: true, errorMessage: null);

    final data = await _service.getDashboardData(forceRefresh: true);
    if (data != null) {
      state = state.copyWith(data: data, isRefreshing: false);
    } else {
      state = state.copyWith(
        isRefreshing: false,
        errorMessage: 'No se pudo actualizar el dashboard',
      );
    }
  }

  /// Marca una notificacion como leida
  Future<void> markNotificationAsRead(String notificationId) async {
    final success = await _service.markNotificationAsRead(notificationId);
    if (success && state.data != null) {
      final updatedNotifications = state.data!.notifications.map((notification) {
        if (notification.id == notificationId) {
          return UserNotification(
            id: notification.id,
            title: notification.title,
            body: notification.body,
            createdAt: notification.createdAt,
            isRead: true,
            type: notification.type,
            actionRoute: notification.actionRoute,
            data: notification.data,
          );
        }
        return notification;
      }).toList();

      final unreadCount = updatedNotifications.where((notification) => !notification.isRead).length;
      state = state.copyWith(
        data: state.data!.copyWith(
          notifications: updatedNotifications,
          unreadNotificationsCount: unreadCount,
        ),
      );
    }
  }

  /// Marca todas las notificaciones como leidas
  Future<void> markAllNotificationsAsRead() async {
    final success = await _service.markAllNotificationsAsRead();
    if (success && state.data != null) {
      final updatedNotifications = state.data!.notifications.map((notification) {
        return UserNotification(
          id: notification.id,
          title: notification.title,
          body: notification.body,
          createdAt: notification.createdAt,
          isRead: true,
          type: notification.type,
          actionRoute: notification.actionRoute,
          data: notification.data,
        );
      }).toList();

      state = state.copyWith(
        data: state.data!.copyWith(
          notifications: updatedNotifications,
          unreadNotificationsCount: 0,
        ),
      );
    }
  }
}

/// Provider para el servicio del dashboard
final clientDashboardServiceProvider = Provider<ClientDashboardService>((ref) {
  final api = ref.read(apiClientProvider);
  return ClientDashboardService(api);
});

/// Provider para el estado del dashboard del cliente
final clientDashboardProvider = StateNotifierProvider<ClientDashboardNotifier, ClientDashboardState>((ref) {
  final service = ref.read(clientDashboardServiceProvider);
  return ClientDashboardNotifier(service);
});
