import 'package:dio/dio.dart';
import '../models/directory_models.dart';

class BusinessDirectoryService {
  final Dio _dio;

  BusinessDirectoryService({required String serverUrl})
      : _dio = Dio(BaseOptions(
          baseUrl: '$serverUrl/wp-json/flavor-network/v1',
          connectTimeout: const Duration(seconds: 10),
          receiveTimeout: const Duration(seconds: 20),
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
        ));

  Future<DirectoryResponse> getDirectory({
    String? search,
    String? tipo,
    String? nivel,
    String? sector,
    String? pais,
    String? ciudad,
    int pagina = 1,
    int porPagina = 20,
  }) async {
    final response = await _dio.get('/directory', queryParameters: {
      if (search != null && search.trim().isNotEmpty) 'busqueda': search.trim(),
      if (tipo != null && tipo.isNotEmpty) 'tipo': tipo,
      if (nivel != null && nivel.isNotEmpty) 'nivel': nivel,
      if (sector != null && sector.isNotEmpty) 'sector': sector,
      if (pais != null && pais.isNotEmpty) 'pais': pais,
      if (ciudad != null && ciudad.isNotEmpty) 'ciudad': ciudad,
      'pagina': pagina,
      'por_pagina': porPagina,
    });

    return DirectoryResponse.fromJson(response.data as Map<String, dynamic>);
  }

  Future<List<DirectoryNode>> getMapData({
    String? tipo,
    String? nivel,
    String? sector,
  }) async {
    final response = await _dio.get('/map', queryParameters: {
      if (tipo != null && tipo.isNotEmpty) 'tipo': tipo,
      if (nivel != null && nivel.isNotEmpty) 'nivel': nivel,
      if (sector != null && sector.isNotEmpty) 'sector': sector,
    });

    final data = response.data as Map<String, dynamic>;
    final nodos = (data['nodos'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(DirectoryNode.fromJson)
        .toList();
    return nodos;
  }

  Future<DirectoryNearbyResponse> getNearby({
    required double lat,
    required double lng,
    double radioKm = 50,
    int limit = 20,
  }) async {
    final response = await _dio.get('/nearby', queryParameters: {
      'lat': lat,
      'lng': lng,
      'radio': radioKm,
      'limit': limit,
    });

    return DirectoryNearbyResponse.fromJson(response.data as Map<String, dynamic>);
  }
}
