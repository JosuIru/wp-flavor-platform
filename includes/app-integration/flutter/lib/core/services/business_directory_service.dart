import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/business.dart';
import '../models/business_filter.dart';
import '../config/api_config.dart';

/// Servicio para gestionar el directorio de negocios
class BusinessDirectoryService {
  static final BusinessDirectoryService _instance = BusinessDirectoryService._internal();
  factory BusinessDirectoryService() => _instance;
  BusinessDirectoryService._internal();

  /// Obtiene la lista de negocios públicos
  Future<List<Business>> getBusinesses({
    BusinessFilter? filter,
  }) async {
    try {
      // Construir URL con parámetros de filtro
      final uri = _buildUri('/app-discovery/v1/businesses', filter);

      final response = await http.get(
        uri,
        headers: {
          'Content-Type': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['success'] == true && data['businesses'] != null) {
          final List<dynamic> businessList = data['businesses'];
          return businessList
              .map((json) => Business.fromJson(json))
              .toList();
        }
      }

      throw Exception('Error al cargar negocios: ${response.statusCode}');
    } catch (e) {
      print('Error en getBusinesses: $e');
      rethrow;
    }
  }

  /// Verifica si una URL tiene el plugin instalado
  Future<Business?> verifyBusiness(String url) async {
    try {
      // Limpiar URL
      url = url.trim();
      if (!url.startsWith('http')) {
        url = 'https://$url';
      }
      url = url.replaceAll(RegExp(r'/$'), ''); // Eliminar trailing slash

      final response = await http.post(
        Uri.parse('$url/wp-json/app-discovery/v1/businesses/verify'),
        headers: {
          'Content-Type': 'application/json',
        },
        body: json.encode({'url': url}),
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        if (data['success'] == true && data['business'] != null) {
          return Business.fromJson(data['business']);
        }
      }

      return null;
    } catch (e) {
      print('Error en verifyBusiness: $e');
      return null;
    }
  }

  /// Obtiene información completa de un negocio
  Future<Business?> getBusinessInfo(String baseUrl) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/wp-json/app-discovery/v1/info'),
        headers: {
          'Content-Type': 'application/json',
        },
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);

        return Business(
          url: baseUrl,
          name: data['app_name'] ?? data['site_name'] ?? '',
          description: data['app_description'] ?? data['site_description'] ?? '',
          logoUrl: data['theme']?['logo_url'] ?? '',
          region: '', // No disponible en este endpoint
          category: '', // No disponible en este endpoint
          systems: (data['active_systems'] as List?)
              ?.map((s) => s['id'].toString())
              .toList() ?? [],
          modules: [], // Se obtiene de otro endpoint
          language: data['language'] ?? 'es',
          lastUpdated: DateTime.now(),
        );
      }

      return null;
    } catch (e) {
      print('Error en getBusinessInfo: $e');
      return null;
    }
  }

  /// Construye URI con parámetros de filtro
  Uri _buildUri(String path, BusinessFilter? filter) {
    final queryParams = <String, String>{};

    if (filter != null) {
      if (filter.region != null && filter.region!.isNotEmpty) {
        queryParams['region'] = filter.region!;
      }
      if (filter.category != null && filter.category!.isNotEmpty) {
        queryParams['category'] = filter.category!;
      }
      if (filter.search != null && filter.search!.isNotEmpty) {
        queryParams['search'] = filter.search!;
      }
      if (filter.limit != null) {
        queryParams['limit'] = filter.limit.toString();
      }
    }

    // Usar una URL base por defecto o la configurada
    // En producción, esto debería apuntar al servidor de directorio central
    final baseUrl = ApiConfig.directoryServerUrl ?? ApiConfig.baseUrl;

    return Uri.parse('$baseUrl$path').replace(queryParameters: queryParams);
  }

  /// Obtiene las regiones disponibles
  Future<Map<String, String>> getAvailableRegions() async {
    return {
      'euskal_herria': 'Euskal Herria',
      'cataluna': 'Cataluña',
      'madrid': 'Madrid',
      'andalucia': 'Andalucía',
      'other_spain': 'Otras regiones de España',
      'international': 'Internacional',
    };
  }

  /// Obtiene las categorías disponibles
  Future<Map<String, String>> getAvailableCategories() async {
    return {
      'cooperativa': 'Cooperativa',
      'asociacion': 'Asociación',
      'comunidad': 'Comunidad',
      'grupo_consumo': 'Grupo de Consumo',
      'economia_social': 'Economía Social',
      'comercio_local': 'Comercio Local',
      'other': 'Otra',
    };
  }
}
