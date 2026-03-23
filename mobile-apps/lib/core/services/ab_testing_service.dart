import 'dart:convert';
import 'dart:math';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../config/app_config.dart';

/// Servicio de A/B Testing y Feature Flags
class ABTestingService extends ChangeNotifier {
  static ABTestingService? _instance;

  final ApiClient _apiClient;
  static const String _prefsKeyExperiments = 'ab_experiments';
  static const String _prefsKeyAssignments = 'ab_assignments';
  static const String _prefsKeyUserId = 'ab_user_id';

  Map<String, Experiment> _experiments = {};
  Map<String, String> _assignments = {};
  String? _userId;
  bool _isInitialized = false;

  ABTestingService._({required ApiClient apiClient}) : _apiClient = apiClient;

  factory ABTestingService({required ApiClient apiClient}) {
    _instance ??= ABTestingService._(apiClient: apiClient);
    return _instance!;
  }

  /// Estado de inicialización
  bool get isInitialized => _isInitialized;

  /// ID de usuario para tracking
  String get userId => _userId ?? AppConfig.userId ?? _generateUserId();

  /// Inicializar servicio
  Future<void> initialize() async {
    await _loadLocalData();
    await _fetchExperiments();
    _isInitialized = true;
    notifyListeners();
    debugPrint('[ABTesting] Servicio inicializado con ${_experiments.length} experimentos');
  }

  /// Cargar datos locales
  Future<void> _loadLocalData() async {
    final prefs = await SharedPreferences.getInstance();

    // Cargar user ID
    _userId = prefs.getString(_prefsKeyUserId);
    if (_userId == null) {
      _userId = _generateUserId();
      await prefs.setString(_prefsKeyUserId, _userId!);
    }

    // Cargar asignaciones previas
    final assignmentsJson = prefs.getString(_prefsKeyAssignments);
    if (assignmentsJson != null) {
      _assignments = Map<String, String>.from(jsonDecode(assignmentsJson));
    }

    // Cargar experimentos cacheados
    final experimentsJson = prefs.getString(_prefsKeyExperiments);
    if (experimentsJson != null) {
      final data = jsonDecode(experimentsJson) as Map<String, dynamic>;
      _experiments = data.map((key, value) =>
          MapEntry(key, Experiment.fromJson(value)));
    }
  }

  /// Obtener experimentos del servidor
  Future<void> _fetchExperiments() async {
    try {
      final response = await _apiClient.get(
        '/flavor-app/v2/ab-testing/experiments',
        queryParameters: {'user_id': userId},
      );

      final responseData = response.data as Map<String, dynamic>?;
      if (responseData == null) return;

      if (responseData['experiments'] != null) {
        final data = responseData['experiments'] as Map<String, dynamic>;
        _experiments = data.map((key, value) =>
            MapEntry(key, Experiment.fromJson(value)));

        // Guardar en cache
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(_prefsKeyExperiments, jsonEncode(
            _experiments.map((k, v) => MapEntry(k, v.toJson()))
        ));
      }

      // Aplicar asignaciones del servidor si existen
      if (responseData['assignments'] != null) {
        _assignments = Map<String, String>.from(responseData['assignments']);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(_prefsKeyAssignments, jsonEncode(_assignments));
      }
    } catch (e) {
      debugPrint('[ABTesting] Error fetching experiments: $e');
      // Usar datos cacheados
    }
  }

  /// Obtener variante para un experimento
  String getVariant(String experimentId) {
    // Verificar asignación existente
    if (_assignments.containsKey(experimentId)) {
      return _assignments[experimentId]!;
    }

    // Verificar si el experimento existe y está activo
    final experiment = _experiments[experimentId];
    if (experiment == null || !experiment.isActive) {
      return 'control';
    }

    // Asignar variante basada en user ID
    final variant = _assignVariant(experiment);
    _assignments[experimentId] = variant;
    _saveAssignments();

    // Trackear asignación
    _trackAssignment(experimentId, variant);

    return variant;
  }

  /// Asignar variante usando hash del user ID
  String _assignVariant(Experiment experiment) {
    final hash = _hashUserId(userId + experiment.id);
    final bucket = hash % 100;

    double cumulative = 0;
    for (final variant in experiment.variants) {
      cumulative += variant.weight;
      if (bucket < cumulative) {
        return variant.id;
      }
    }

    return experiment.variants.first.id;
  }

  /// Hash simple para distribución
  int _hashUserId(String input) {
    int hash = 0;
    for (int i = 0; i < input.length; i++) {
      hash = ((hash << 5) - hash) + input.codeUnitAt(i);
      hash = hash & hash; // Convert to 32bit integer
    }
    return hash.abs();
  }

  /// Guardar asignaciones
  Future<void> _saveAssignments() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsKeyAssignments, jsonEncode(_assignments));
  }

  /// Trackear asignación al servidor
  Future<void> _trackAssignment(String experimentId, String variant) async {
    try {
      await _apiClient.post('/flavor-app/v2/ab-testing/assign', data: {
        'experiment_id': experimentId,
        'variant': variant,
        'user_id': userId,
        'timestamp': DateTime.now().toIso8601String(),
      });
    } catch (e) {
      debugPrint('[ABTesting] Error tracking assignment: $e');
    }
  }

  /// Trackear conversión/evento
  Future<void> trackEvent(String experimentId, String eventName, {
    Map<String, dynamic>? metadata,
  }) async {
    final variant = _assignments[experimentId];
    if (variant == null) return;

    try {
      await _apiClient.post('/flavor-app/v2/ab-testing/event', data: {
        'experiment_id': experimentId,
        'variant': variant,
        'event_name': eventName,
        'user_id': userId,
        'metadata': metadata,
        'timestamp': DateTime.now().toIso8601String(),
      });
      debugPrint('[ABTesting] Evento trackeado: $experimentId/$eventName');
    } catch (e) {
      debugPrint('[ABTesting] Error tracking event: $e');
    }
  }

  /// Verificar si usuario está en variante específica
  bool isInVariant(String experimentId, String variantId) {
    return getVariant(experimentId) == variantId;
  }

  /// Verificar si usuario está en grupo de control
  bool isInControl(String experimentId) {
    return isInVariant(experimentId, 'control');
  }

  /// Obtener todos los experimentos activos
  List<Experiment> get activeExperiments =>
      _experiments.values.where((e) => e.isActive).toList();

  /// Forzar variante (para testing)
  void forceVariant(String experimentId, String variant) {
    _assignments[experimentId] = variant;
    _saveAssignments();
    notifyListeners();
  }

  /// Resetear asignaciones
  Future<void> resetAssignments() async {
    _assignments.clear();
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefsKeyAssignments);
    notifyListeners();
  }

  /// Generar ID de usuario único
  String _generateUserId() {
    final random = Random.secure();
    final bytes = List.generate(16, (_) => random.nextInt(256));
    return bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
  }
}

/// Modelo de experimento
class Experiment {
  final String id;
  final String name;
  final String description;
  final List<ExperimentVariant> variants;
  final bool isActive;
  final DateTime? startDate;
  final DateTime? endDate;
  final Map<String, dynamic> metadata;

  const Experiment({
    required this.id,
    required this.name,
    required this.description,
    required this.variants,
    this.isActive = true,
    this.startDate,
    this.endDate,
    this.metadata = const {},
  });

  factory Experiment.fromJson(Map<String, dynamic> json) {
    return Experiment(
      id: json['id'],
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      variants: (json['variants'] as List?)
          ?.map((v) => ExperimentVariant.fromJson(v))
          .toList() ?? [],
      isActive: json['is_active'] ?? true,
      startDate: json['start_date'] != null
          ? DateTime.parse(json['start_date'])
          : null,
      endDate: json['end_date'] != null
          ? DateTime.parse(json['end_date'])
          : null,
      metadata: Map<String, dynamic>.from(json['metadata'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'description': description,
    'variants': variants.map((v) => v.toJson()).toList(),
    'is_active': isActive,
    'start_date': startDate?.toIso8601String(),
    'end_date': endDate?.toIso8601String(),
    'metadata': metadata,
  };
}

/// Variante de experimento
class ExperimentVariant {
  final String id;
  final String name;
  final double weight; // Porcentaje 0-100
  final Map<String, dynamic> config;

  const ExperimentVariant({
    required this.id,
    required this.name,
    required this.weight,
    this.config = const {},
  });

  factory ExperimentVariant.fromJson(Map<String, dynamic> json) {
    return ExperimentVariant(
      id: json['id'],
      name: json['name'] ?? '',
      weight: (json['weight'] ?? 50).toDouble(),
      config: Map<String, dynamic>.from(json['config'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'weight': weight,
    'config': config,
  };
}

/// Experimentos predefinidos para Flavor Platform
class FlavorExperiments {
  // UI/UX Experiments
  static const String bottomNavStyle = 'bottom_nav_style';
  static const String cardLayout = 'card_layout';
  static const String homePageLayout = 'home_page_layout';

  // Feature Experiments
  static const String newCheckoutFlow = 'new_checkout_flow';
  static const String socialFeatures = 'social_features';
  static const String gamification = 'gamification';

  // Conversion Experiments
  static const String ctaButton = 'cta_button_style';
  static const String onboardingFlow = 'onboarding_flow';
  static const String notificationFrequency = 'notification_frequency';
}

/// Widget builder condicional para A/B testing
class ABTestBuilder extends StatelessWidget {
  final String experimentId;
  final Map<String, WidgetBuilder> variants;
  final WidgetBuilder defaultBuilder;
  final ABTestingService service;

  const ABTestBuilder({
    super.key,
    required this.experimentId,
    required this.variants,
    required this.defaultBuilder,
    required this.service,
  });

  @override
  Widget build(BuildContext context) {
    final variant = service.getVariant(experimentId);
    final builder = variants[variant] ?? defaultBuilder;
    return builder(context);
  }
}

/// Widget que trackea eventos automáticamente
class ABTestTracker extends StatefulWidget {
  final String experimentId;
  final String eventName;
  final Widget child;
  final ABTestingService service;
  final bool trackOnMount;

  const ABTestTracker({
    super.key,
    required this.experimentId,
    required this.eventName,
    required this.child,
    required this.service,
    this.trackOnMount = true,
  });

  @override
  State<ABTestTracker> createState() => _ABTestTrackerState();
}

class _ABTestTrackerState extends State<ABTestTracker> {
  @override
  void initState() {
    super.initState();
    if (widget.trackOnMount) {
      widget.service.trackEvent(widget.experimentId, widget.eventName);
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}

/// Extensión para fácil acceso a variantes
extension ABTestingExtension on ABTestingService {
  /// Obtener configuración de variante
  T? getVariantConfig<T>(String experimentId, String configKey) {
    final variant = getVariant(experimentId);
    final experiment = _experiments[experimentId];
    if (experiment == null) return null;

    final variantData = experiment.variants.firstWhere(
      (v) => v.id == variant,
      orElse: () => experiment.variants.first,
    );

    return variantData.config[configKey] as T?;
  }

  /// Verificar si feature está habilitada
  bool isFeatureEnabled(String experimentId) {
    return !isInControl(experimentId);
  }
}
