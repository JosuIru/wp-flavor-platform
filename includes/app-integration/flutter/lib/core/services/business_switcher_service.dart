import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/business.dart';
import '../models/site_config.dart';
import '../config/api_config.dart';
import 'plugin_detector_service.dart';
import 'package:http/http.dart' as http;

/// Servicio para gestionar el cambio entre diferentes negocios
class BusinessSwitcherService {
  static final BusinessSwitcherService _instance = BusinessSwitcherService._internal();
  factory BusinessSwitcherService() => _instance;
  BusinessSwitcherService._internal();

  static const String _currentBusinessKey = 'current_business';
  static const String _savedBusinessesKey = 'saved_businesses';
  static const String _currentSiteConfigKey = 'current_site_config';

  Business? _currentBusiness;
  SiteConfig? _currentSiteConfig;
  List<Business> _savedBusinesses = [];

  /// Obtiene el negocio actual
  Business? get currentBusiness => _currentBusiness;

  /// Obtiene la configuración del sitio actual
  SiteConfig? get currentSiteConfig => _currentSiteConfig;

  /// Obtiene la lista de negocios guardados
  List<Business> get savedBusinesses => _savedBusinesses;

  /// Inicializa el servicio cargando datos guardados
  Future<void> initialize() async {
    final prefs = await SharedPreferences.getInstance();

    // Cargar negocio actual
    final currentBusinessJson = prefs.getString(_currentBusinessKey);
    if (currentBusinessJson != null) {
      _currentBusiness = Business.fromJson(json.decode(currentBusinessJson));
    }

    // Cargar configuración del sitio actual
    final currentSiteConfigJson = prefs.getString(_currentSiteConfigKey);
    if (currentSiteConfigJson != null) {
      _currentSiteConfig = SiteConfig.fromJson(json.decode(currentSiteConfigJson));
    }

    // Cargar lista de negocios guardados
    final savedBusinessesJson = prefs.getStringList(_savedBusinessesKey);
    if (savedBusinessesJson != null) {
      _savedBusinesses = savedBusinessesJson
          .map((json) => Business.fromJson(jsonDecode(json)))
          .toList();
    }
  }

  /// Cambia al negocio especificado
  Future<bool> switchToBusiness(Business business) async {
    try {
      // 1. Obtener configuración del sitio
      final siteConfig = await _fetchSiteConfig(business.url);
      if (siteConfig == null) {
        throw Exception('No se pudo obtener la configuración del sitio');
      }

      // 2. Guardar negocio actual
      _currentBusiness = business;
      _currentSiteConfig = siteConfig;

      // 3. Actualizar ApiConfig
      ApiConfig.baseUrl = business.url;
      // TODO: Aquí deberías actualizar el token de API si cada negocio tiene su propio token

      // 4. Añadir a la lista de negocios guardados si no existe
      if (!_savedBusinesses.contains(business)) {
        _savedBusinesses.add(business);
      }

      // 5. Persistir en SharedPreferences
      await _persist();

      return true;
    } catch (e) {
      print('Error al cambiar de negocio: $e');
      return false;
    }
  }

  /// Obtiene la configuración completa del sitio
  Future<SiteConfig?> _fetchSiteConfig(String baseUrl) async {
    try {
      final detector = PluginDetectorService(baseUrl: baseUrl);
      final info = await detector.detectPlugins();

      if (info == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/wp-json/app-discovery/v1/theme'),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final themeData = json.decode(response.body);

        return SiteConfig(
          baseUrl: baseUrl,
          siteName: info.appName,
          siteDescription: info.siteDescription,
          logoUrl: themeData['logo_url'],
          primaryColor: themeData['primary_color'] ?? '#4CAF50',
          secondaryColor: themeData['secondary_color'] ?? '#8BC34A',
          accentColor: themeData['accent_color'] ?? '#FF9800',
          activeSystems: info.activeSystems,
          availableModules: info.availableModules,
          language: info.language,
          timezone: info.timezone,
        );
      }

      return null;
    } catch (e) {
      print('Error en _fetchSiteConfig: $e');
      return null;
    }
  }

  /// Elimina un negocio de la lista de guardados
  Future<void> removeSavedBusiness(Business business) async {
    _savedBusinesses.removeWhere((b) => b.url == business.url);
    await _persist();
  }

  /// Limpia todos los datos guardados
  Future<void> clearAll() async {
    _currentBusiness = null;
    _currentSiteConfig = null;
    _savedBusinesses = [];

    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_currentBusinessKey);
    await prefs.remove(_currentSiteConfigKey);
    await prefs.remove(_savedBusinessesKey);
  }

  /// Obtiene el negocio por URL
  Business? getBusinessByUrl(String url) {
    return _savedBusinesses.firstWhere(
      (b) => b.url == url,
      orElse: () => Business(
        url: url,
        name: '',
        description: '',
        region: '',
        category: '',
        systems: [],
        modules: [],
        language: 'es',
        lastUpdated: DateTime.now(),
      ),
    );
  }

  /// Verifica si un negocio está guardado
  bool isBusinessSaved(String url) {
    return _savedBusinesses.any((b) => b.url == url);
  }

  /// Recarga la configuración del negocio actual
  Future<bool> reloadCurrentBusiness() async {
    if (_currentBusiness == null) return false;
    return await switchToBusiness(_currentBusiness!);
  }

  /// Persiste los datos en SharedPreferences
  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();

    if (_currentBusiness != null) {
      await prefs.setString(
        _currentBusinessKey,
        json.encode(_currentBusiness!.toJson()),
      );
    }

    if (_currentSiteConfig != null) {
      await prefs.setString(
        _currentSiteConfigKey,
        json.encode(_currentSiteConfig!.toJson()),
      );
    }

    await prefs.setStringList(
      _savedBusinessesKey,
      _savedBusinesses.map((b) => json.encode(b.toJson())).toList(),
    );
  }
}
