import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/api/api_client.dart';

/// Modelo de slide de onboarding
class OnboardingSlide {
  final String id;
  final String title;
  final String description;
  final String? imageUrl;
  final String? iconName;
  final String? backgroundColor;
  final String? textColor;
  final String? buttonText;
  final String? buttonAction;
  final int order;

  OnboardingSlide({
    required this.id,
    required this.title,
    required this.description,
    this.imageUrl,
    this.iconName,
    this.backgroundColor,
    this.textColor,
    this.buttonText,
    this.buttonAction,
    required this.order,
  });

  factory OnboardingSlide.fromJson(Map<String, dynamic> json) {
    return OnboardingSlide(
      id: json['id']?.toString() ?? '',
      title: json['title'] ?? json['titulo'] ?? '',
      description: json['description'] ?? json['descripcion'] ?? '',
      imageUrl: json['image_url'] ?? json['imagen'],
      iconName: json['icon'] ?? json['icono'],
      backgroundColor: json['background_color'] ?? json['color_fondo'],
      textColor: json['text_color'] ?? json['color_texto'],
      buttonText: json['button_text'] ?? json['texto_boton'],
      buttonAction: json['button_action'] ?? json['accion_boton'],
      order: json['order'] ?? json['orden'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'title': title,
    'description': description,
    'image_url': imageUrl,
    'icon': iconName,
    'background_color': backgroundColor,
    'text_color': textColor,
    'button_text': buttonText,
    'button_action': buttonAction,
    'order': order,
  };
}

/// Configuración de onboarding
class OnboardingConfig {
  final bool enabled;
  final bool showOnEveryLaunch;
  final bool skipEnabled;
  final String? skipButtonText;
  final String? finishButtonText;
  final String? nextButtonText;
  final bool showProgressDots;
  final bool showProgressBar;
  final List<OnboardingSlide> slides;
  final String? version; // Para forzar mostrar de nuevo

  OnboardingConfig({
    this.enabled = true,
    this.showOnEveryLaunch = false,
    this.skipEnabled = true,
    this.skipButtonText,
    this.finishButtonText,
    this.nextButtonText,
    this.showProgressDots = true,
    this.showProgressBar = false,
    this.slides = const [],
    this.version,
  });

  factory OnboardingConfig.fromJson(Map<String, dynamic> json) {
    final slidesList = (json['slides'] ?? []) as List;
    return OnboardingConfig(
      enabled: json['enabled'] != false,
      showOnEveryLaunch: json['show_on_every_launch'] == true,
      skipEnabled: json['skip_enabled'] != false,
      skipButtonText: json['skip_button_text'],
      finishButtonText: json['finish_button_text'],
      nextButtonText: json['next_button_text'],
      showProgressDots: json['show_progress_dots'] != false,
      showProgressBar: json['show_progress_bar'] == true,
      slides: slidesList.map((s) => OnboardingSlide.fromJson(s)).toList()
        ..sort((a, b) => a.order.compareTo(b.order)),
      version: json['version']?.toString(),
    );
  }

  factory OnboardingConfig.defaults() {
    return OnboardingConfig(
      slides: [
        OnboardingSlide(
          id: 'welcome',
          title: 'Bienvenido',
          description: 'Descubre todo lo que puedes hacer con nuestra app',
          iconName: 'waving_hand',
          backgroundColor: '#667eea',
          order: 0,
        ),
        OnboardingSlide(
          id: 'features',
          title: 'Funcionalidades',
          description: 'Explora módulos, eventos, marketplace y mucho más',
          iconName: 'apps',
          backgroundColor: '#764ba2',
          order: 1,
        ),
        OnboardingSlide(
          id: 'community',
          title: 'Comunidad',
          description: 'Conecta con otros miembros de la comunidad',
          iconName: 'people',
          backgroundColor: '#f093fb',
          order: 2,
        ),
      ],
    );
  }
}

/// Servicio de Onboarding
class OnboardingService {
  static OnboardingService? _instance;

  final ApiClient _apiClient;
  static const String _prefsKeyCompleted = 'onboarding_completed';
  static const String _prefsKeyVersion = 'onboarding_version';
  static const String _prefsKeyConfig = 'onboarding_config';

  OnboardingConfig? _config;
  bool _hasCompleted = false;

  OnboardingService._({required ApiClient apiClient}) : _apiClient = apiClient;

  factory OnboardingService({required ApiClient apiClient}) {
    _instance ??= OnboardingService._(apiClient: apiClient);
    return _instance!;
  }

  /// Configuración actual
  OnboardingConfig? get config => _config;

  /// ¿Se ha completado el onboarding?
  bool get hasCompleted => _hasCompleted;

  /// ¿Debe mostrarse el onboarding?
  bool get shouldShow {
    if (_config == null || !_config!.enabled || _config!.slides.isEmpty) {
      return false;
    }
    if (_config!.showOnEveryLaunch) {
      return true;
    }
    return !_hasCompleted;
  }

  /// Inicializar servicio
  Future<void> initialize() async {
    await _loadLocalState();
    await _fetchConfig();
  }

  /// Cargar estado local
  Future<void> _loadLocalState() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _hasCompleted = prefs.getBool(_prefsKeyCompleted) ?? false;

      // Cargar config cacheada
      final configJson = prefs.getString(_prefsKeyConfig);
      if (configJson != null) {
        _config = OnboardingConfig.fromJson(jsonDecode(configJson));
      }
    } catch (e) {
      debugPrint('[OnboardingService] Error cargando estado: $e');
    }
  }

  /// Obtener configuración del servidor
  Future<void> _fetchConfig() async {
    try {
      final response = await _apiClient.get('/flavor-app/v2/onboarding/config');
      _config = OnboardingConfig.fromJson(response.data ?? {});

      // Guardar en cache
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_prefsKeyConfig, jsonEncode(response.data));

      // Verificar si hay nueva versión
      final savedVersion = prefs.getString(_prefsKeyVersion);
      if (_config!.version != null && _config!.version != savedVersion) {
        // Nueva versión, resetear estado de completado
        _hasCompleted = false;
        await prefs.setBool(_prefsKeyCompleted, false);
      }

      debugPrint('[OnboardingService] Config cargada: ${_config!.slides.length} slides');
    } catch (e) {
      debugPrint('[OnboardingService] Error obteniendo config: $e');
      // Usar defaults si no hay cache
      _config ??= OnboardingConfig.defaults();
    }
  }

  /// Marcar onboarding como completado
  Future<void> markCompleted() async {
    _hasCompleted = true;

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool(_prefsKeyCompleted, true);

      if (_config?.version != null) {
        await prefs.setString(_prefsKeyVersion, _config!.version!);
      }

      // Notificar al servidor
      await _apiClient.post('/flavor-app/v2/onboarding/completed');

      debugPrint('[OnboardingService] Onboarding completado');
    } catch (e) {
      debugPrint('[OnboardingService] Error marcando completado: $e');
    }
  }

  /// Saltar onboarding
  Future<void> skip() async {
    await markCompleted();

    try {
      await _apiClient.post('/flavor-app/v2/onboarding/skipped');
    } catch (e) {
      debugPrint('[OnboardingService] Error notificando skip: $e');
    }
  }

  /// Resetear onboarding (para testing)
  Future<void> reset() async {
    _hasCompleted = false;

    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_prefsKeyCompleted);
      await prefs.remove(_prefsKeyVersion);
    } catch (e) {
      debugPrint('[OnboardingService] Error reseteando: $e');
    }
  }

  /// Trackear progreso de slide
  Future<void> trackSlideView(String slideId, int slideIndex) async {
    try {
      await _apiClient.post('/flavor-app/v2/analytics/event', data: {
        'event_type': 'onboarding_slide_view',
        'event_name': 'slide_$slideId',
        'properties': {
          'slide_id': slideId,
          'slide_index': slideIndex,
        },
      });
    } catch (e) {
      // Silenciar errores de tracking
    }
  }
}
