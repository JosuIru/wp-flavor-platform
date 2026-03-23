import 'dart:convert';
import 'package:flutter/foundation.dart';
import '../api/api_client.dart';

/// Servicio genérico de CRUD para módulos
///
/// Proporciona operaciones Create, Read, Update, Delete para cualquier módulo
/// con soporte para:
/// - Paginación
/// - Filtros
/// - Búsqueda
/// - Ordenamiento
/// - Caché local
/// - Modo offline
class CrudService<T> {
  final ApiClient _apiClient;
  final String _moduleName;
  final String _endpoint;
  final T Function(Map<String, dynamic>) _fromJson;
  final Map<String, dynamic> Function(T) _toJson;

  /// Cache local de items
  final Map<String, T> _cache = {};

  /// Items pendientes de sincronizar (offline)
  final List<PendingOperation<T>> _pendingOperations = [];

  /// Listeners para cambios
  final List<Function(CrudEvent<T>)> _listeners = [];

  /// Getter protegido para acceso a apiClient desde subclases
  @protected
  ApiClient get apiClient => _apiClient;

  /// Getter protegido para acceso a cache desde subclases
  @protected
  Map<String, T> get cache => _cache;

  /// Notificar a listeners de cambios
  @protected
  void notifyListeners(CrudEvent<T> event) {
    for (final listener in _listeners) {
      listener(event);
    }
  }

  CrudService({
    required ApiClient apiClient,
    required String moduleName,
    required String endpoint,
    required T Function(Map<String, dynamic>) fromJson,
    required Map<String, dynamic> Function(T) toJson,
  })  : _apiClient = apiClient,
        _moduleName = moduleName,
        _endpoint = endpoint,
        _fromJson = fromJson,
        _toJson = toJson;

  // ===========================================================================
  // READ Operations
  // ===========================================================================

  /// Obtener lista de items con paginación y filtros
  Future<PaginatedResult<T>> getList({
    int page = 1,
    int perPage = 20,
    Map<String, dynamic>? filters,
    String? search,
    String? orderBy,
    String? order,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        'per_page': perPage,
        if (search != null && search.isNotEmpty) 'search': search,
        if (orderBy != null) 'orderby': orderBy,
        if (order != null) 'order': order,
        ...?filters,
      };

      final response = await _apiClient.get(
        _endpoint,
        queryParameters: queryParams,
      );

      final responseData = response.data as Map<String, dynamic>?;
      final List<dynamic> itemsJson = responseData?['items'] ?? response.data ?? [];
      final items = itemsJson.map((json) => _fromJson(json)).toList();

      // Actualizar cache
      for (final item in items) {
        final id = _getItemId(item);
        if (id != null) {
          _cache[id] = item;
        }
      }

      return PaginatedResult<T>(
        items: items,
        total: responseData?['total'] ?? items.length,
        page: page,
        perPage: perPage,
        totalPages: responseData?['total_pages'] ?? 1,
      );
    } catch (e) {
      debugPrint('[CrudService] Error getting list: $e');

      // En modo offline, devolver cache
      if (_cache.isNotEmpty) {
        final cachedItems = _cache.values.toList();
        return PaginatedResult<T>(
          items: cachedItems,
          total: cachedItems.length,
          page: 1,
          perPage: cachedItems.length,
          totalPages: 1,
          isFromCache: true,
        );
      }

      rethrow;
    }
  }

  /// Obtener un item por ID
  Future<T?> getById(String id, {bool forceRefresh = false}) async {
    // Primero buscar en cache (si no forzamos refrescar)
    if (!forceRefresh && _cache.containsKey(id)) {
      return _cache[id];
    }

    try {
      final response = await _apiClient.get('$_endpoint/$id');
      final responseData = response.data as Map<String, dynamic>?;
      if (responseData == null) return null;
      final item = _fromJson(responseData);
      _cache[id] = item;
      return item;
    } catch (e) {
      debugPrint('[CrudService] Error getting by id: $e');
      return null;
    }
  }

  /// Buscar items
  Future<List<T>> search(String query, {Map<String, dynamic>? additionalFilters}) async {
    final result = await getList(
      search: query,
      filters: additionalFilters,
      perPage: 50,
    );
    return result.items;
  }

  // ===========================================================================
  // CREATE Operations
  // ===========================================================================

  /// Crear nuevo item
  Future<T?> create(T item, {bool offline = false}) async {
    final json = _toJson(item);

    if (offline || !await _apiClient.isOnline()) {
      // Guardar operación pendiente
      final pendingId = 'pending_${DateTime.now().millisecondsSinceEpoch}';
      _pendingOperations.add(PendingOperation(
        type: OperationType.create,
        data: json,
        localId: pendingId,
        createdAt: DateTime.now(),
      ));

      // Agregar al cache con ID temporal
      json['_pending'] = true;
      json['_local_id'] = pendingId;
      final tempItem = _fromJson(json);
      _cache[pendingId] = tempItem;

      _notifyListeners(CrudEvent(
        type: CrudEventType.created,
        item: tempItem,
        isPending: true,
      ));

      return tempItem;
    }

    try {
      final response = await _apiClient.post(_endpoint, data: json);
      final responseData = response.data as Map<String, dynamic>?;
      if (responseData == null) return null;
      final createdItem = _fromJson(responseData);

      final id = _getItemId(createdItem);
      if (id != null) {
        _cache[id] = createdItem;
      }

      _notifyListeners(CrudEvent(
        type: CrudEventType.created,
        item: createdItem,
      ));

      return createdItem;
    } catch (e) {
      debugPrint('[CrudService] Error creating: $e');
      rethrow;
    }
  }

  // ===========================================================================
  // UPDATE Operations
  // ===========================================================================

  /// Actualizar item existente
  Future<T?> update(String id, T item, {bool offline = false}) async {
    final json = _toJson(item);

    if (offline || !await _apiClient.isOnline()) {
      // Guardar operación pendiente
      _pendingOperations.add(PendingOperation(
        type: OperationType.update,
        id: id,
        data: json,
        createdAt: DateTime.now(),
      ));

      // Actualizar cache local
      json['_pending'] = true;
      final tempItem = _fromJson(json);
      _cache[id] = tempItem;

      _notifyListeners(CrudEvent(
        type: CrudEventType.updated,
        item: tempItem,
        isPending: true,
      ));

      return tempItem;
    }

    try {
      final response = await _apiClient.put('$_endpoint/$id', data: json);
      final responseData = response.data as Map<String, dynamic>?;
      if (responseData == null) return null;
      final updatedItem = _fromJson(responseData);
      _cache[id] = updatedItem;

      _notifyListeners(CrudEvent(
        type: CrudEventType.updated,
        item: updatedItem,
      ));

      return updatedItem;
    } catch (e) {
      debugPrint('[CrudService] Error updating: $e');
      rethrow;
    }
  }

  /// Actualización parcial (PATCH)
  Future<T?> patch(String id, Map<String, dynamic> changes) async {
    try {
      final response = await _apiClient.patch('$_endpoint/$id', data: changes);
      final responseData = response.data as Map<String, dynamic>?;
      if (responseData == null) return null;
      final updatedItem = _fromJson(responseData);
      _cache[id] = updatedItem;

      _notifyListeners(CrudEvent(
        type: CrudEventType.updated,
        item: updatedItem,
      ));

      return updatedItem;
    } catch (e) {
      debugPrint('[CrudService] Error patching: $e');
      rethrow;
    }
  }

  // ===========================================================================
  // DELETE Operations
  // ===========================================================================

  /// Eliminar item
  Future<bool> delete(String id, {bool offline = false}) async {
    final cachedItem = _cache[id];

    if (offline || !await _apiClient.isOnline()) {
      // Guardar operación pendiente
      _pendingOperations.add(PendingOperation(
        type: OperationType.delete,
        id: id,
        createdAt: DateTime.now(),
      ));

      // Marcar como eliminado en cache
      _cache.remove(id);

      _notifyListeners(CrudEvent(
        type: CrudEventType.deleted,
        item: cachedItem,
        isPending: true,
      ));

      return true;
    }

    try {
      await _apiClient.delete('$_endpoint/$id');
      _cache.remove(id);

      _notifyListeners(CrudEvent(
        type: CrudEventType.deleted,
        item: cachedItem,
      ));

      return true;
    } catch (e) {
      debugPrint('[CrudService] Error deleting: $e');
      return false;
    }
  }

  /// Eliminar múltiples items
  Future<int> deleteMultiple(List<String> ids) async {
    int deleted = 0;
    for (final id in ids) {
      if (await delete(id)) {
        deleted++;
      }
    }
    return deleted;
  }

  // ===========================================================================
  // SYNC Operations
  // ===========================================================================

  /// Sincronizar operaciones pendientes
  Future<SyncResult> syncPending() async {
    if (_pendingOperations.isEmpty) {
      return SyncResult(synced: 0, failed: 0, pending: 0);
    }

    if (!await _apiClient.isOnline()) {
      return SyncResult(
        synced: 0,
        failed: 0,
        pending: _pendingOperations.length,
      );
    }

    int synced = 0;
    int failed = 0;
    final toRemove = <PendingOperation<T>>[];

    for (final op in _pendingOperations) {
      try {
        switch (op.type) {
          case OperationType.create:
            if (op.data == null) {
              failed++;
              continue;
            }
            final response = await _apiClient.post(_endpoint, data: op.data!);
            final responseData = response.data as Map<String, dynamic>?;
            if (responseData == null) {
              failed++;
              continue;
            }
            final createdItem = _fromJson(responseData);
            final newId = _getItemId(createdItem);

            // Actualizar cache con nuevo ID
            if (op.localId != null) {
              _cache.remove(op.localId);
            }
            if (newId != null) {
              _cache[newId] = createdItem;
            }

            synced++;
            toRemove.add(op);
            break;

          case OperationType.update:
            if (op.id != null && op.data != null) {
              await _apiClient.put('$_endpoint/${op.id}', data: op.data!);
              synced++;
              toRemove.add(op);
            }
            break;

          case OperationType.delete:
            if (op.id != null) {
              await _apiClient.delete('$_endpoint/${op.id}');
              synced++;
              toRemove.add(op);
            }
            break;
        }
      } catch (e) {
        debugPrint('[CrudService] Sync failed for op: $e');
        failed++;
      }
    }

    _pendingOperations.removeWhere((op) => toRemove.contains(op));

    _notifyListeners(CrudEvent(
      type: CrudEventType.synced,
      syncResult: SyncResult(
        synced: synced,
        failed: failed,
        pending: _pendingOperations.length,
      ),
    ));

    return SyncResult(
      synced: synced,
      failed: failed,
      pending: _pendingOperations.length,
    );
  }

  /// Verificar si hay operaciones pendientes
  bool get hasPendingOperations => _pendingOperations.isNotEmpty;

  /// Número de operaciones pendientes
  int get pendingCount => _pendingOperations.length;

  // ===========================================================================
  // CACHE Operations
  // ===========================================================================

  /// Limpiar cache
  void clearCache() {
    _cache.clear();
  }

  /// Obtener todos los items del cache
  List<T> getCachedItems() {
    return _cache.values.toList();
  }

  /// Verificar si un item está en cache
  bool isCached(String id) {
    return _cache.containsKey(id);
  }

  // ===========================================================================
  // LISTENERS
  // ===========================================================================

  /// Agregar listener para cambios
  void addListener(Function(CrudEvent<T>) listener) {
    _listeners.add(listener);
  }

  /// Remover listener
  void removeListener(Function(CrudEvent<T>) listener) {
    _listeners.remove(listener);
  }

  /// Notificar a todos los listeners
  void _notifyListeners(CrudEvent<T> event) {
    for (final listener in _listeners) {
      listener(event);
    }
  }

  // ===========================================================================
  // HELPERS
  // ===========================================================================

  /// Obtener ID de un item (asume que tiene campo 'id')
  String? _getItemId(T item) {
    final json = _toJson(item);
    final id = json['id'] ?? json['ID'];
    return id?.toString();
  }

  /// Nombre del módulo
  String get moduleName => _moduleName;
}

// ===========================================================================
// MODELOS DE SOPORTE
// ===========================================================================

/// Resultado paginado
class PaginatedResult<T> {
  final List<T> items;
  final int total;
  final int page;
  final int perPage;
  final int totalPages;
  final bool isFromCache;

  PaginatedResult({
    required this.items,
    required this.total,
    required this.page,
    required this.perPage,
    required this.totalPages,
    this.isFromCache = false,
  });

  bool get hasMore => page < totalPages;
  bool get isEmpty => items.isEmpty;
}

/// Operación pendiente
class PendingOperation<T> {
  final OperationType type;
  final String? id;
  final String? localId;
  final Map<String, dynamic>? data;
  final DateTime createdAt;

  PendingOperation({
    required this.type,
    this.id,
    this.localId,
    this.data,
    required this.createdAt,
  });
}

/// Tipo de operación
enum OperationType { create, update, delete }

/// Evento de CRUD
class CrudEvent<T> {
  final CrudEventType type;
  final T? item;
  final bool isPending;
  final SyncResult? syncResult;

  CrudEvent({
    required this.type,
    this.item,
    this.isPending = false,
    this.syncResult,
  });
}

/// Tipo de evento
enum CrudEventType { created, updated, deleted, synced, error }

/// Resultado de sincronización
class SyncResult {
  final int synced;
  final int failed;
  final int pending;

  SyncResult({
    required this.synced,
    required this.failed,
    required this.pending,
  });

  bool get isComplete => pending == 0;
  bool get hasErrors => failed > 0;
}

// ===========================================================================
// EXTENSIONES PARA MÓDULOS ESPECÍFICOS
// ===========================================================================

/// Mixin para agregar funcionalidad específica de marketplace
mixin MarketplaceCrudMixin<T> on CrudService<T> {
  /// Marcar como vendido
  Future<T?> markAsSold(String id) async {
    return patch(id, {'status': 'sold'});
  }

  /// Marcar como destacado
  Future<T?> markAsFeatured(String id, bool featured) async {
    return patch(id, {'featured': featured});
  }

  /// Renovar publicación
  Future<T?> renew(String id) async {
    return patch(id, {'renewed_at': DateTime.now().toIso8601String()});
  }
}

/// Mixin para agregar funcionalidad específica de eventos
mixin EventosCrudMixin<T> on CrudService<T> {
  /// Inscribirse a evento
  Future<bool> inscribirse(String eventoId) async {
    try {
      await _apiClient.post('$_endpoint/$eventoId/inscribir');
      return true;
    } catch (e) {
      return false;
    }
  }

  /// Cancelar inscripción
  Future<bool> cancelarInscripcion(String eventoId) async {
    try {
      await _apiClient.delete('$_endpoint/$eventoId/inscripcion');
      return true;
    } catch (e) {
      return false;
    }
  }
}

/// Mixin para agregar funcionalidad de banco de tiempo
mixin BancoTiempoCrudMixin<T> on CrudService<T> {
  /// Solicitar servicio
  Future<bool> solicitarServicio(String servicioId, String mensaje) async {
    try {
      await _apiClient.post('$_endpoint/$servicioId/solicitar', data: {
        'mensaje': mensaje,
      });
      return true;
    } catch (e) {
      return false;
    }
  }

  /// Ofrecer servicio
  Future<T?> ofrecerServicio(T servicio) async {
    return create(servicio);
  }
}
