import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:app_links/app_links.dart';

/// Información de deep link parseado
class DeepLinkInfo {
  final String path;
  final String? module;
  final String? action;
  final String? itemId;
  final Map<String, String> queryParams;
  final String rawUri;

  DeepLinkInfo({
    required this.path,
    this.module,
    this.action,
    this.itemId,
    this.queryParams = const {},
    required this.rawUri,
  });

  factory DeepLinkInfo.fromUri(Uri uri) {
    final pathSegments = uri.pathSegments.where((s) => s.isNotEmpty).toList();

    String? module;
    String? action;
    String? itemId;

    if (pathSegments.isNotEmpty) {
      module = pathSegments[0];
    }
    if (pathSegments.length > 1) {
      action = pathSegments[1];
    }
    if (pathSegments.length > 2) {
      itemId = pathSegments[2];
    }

    return DeepLinkInfo(
      path: uri.path,
      module: module,
      action: action,
      itemId: itemId,
      queryParams: uri.queryParameters,
      rawUri: uri.toString(),
    );
  }

  @override
  String toString() => 'DeepLinkInfo(path: $path, module: $module, action: $action, itemId: $itemId)';
}

/// Resultado de navegación de deep link
class DeepLinkNavigationResult {
  final bool handled;
  final String? route;
  final Map<String, dynamic>? arguments;
  final String? errorMessage;

  DeepLinkNavigationResult({
    required this.handled,
    this.route,
    this.arguments,
    this.errorMessage,
  });

  factory DeepLinkNavigationResult.success(String route, {Map<String, dynamic>? arguments}) {
    return DeepLinkNavigationResult(
      handled: true,
      route: route,
      arguments: arguments,
    );
  }

  factory DeepLinkNavigationResult.notHandled() {
    return DeepLinkNavigationResult(handled: false);
  }

  factory DeepLinkNavigationResult.error(String message) {
    return DeepLinkNavigationResult(
      handled: false,
      errorMessage: message,
    );
  }
}

/// Handler de navegación personalizado
typedef DeepLinkHandler = Future<DeepLinkNavigationResult> Function(DeepLinkInfo link);

/// Servicio de Deep Linking
class DeepLinkService {
  static DeepLinkService? _instance;

  late final AppLinks _appLinks;
  StreamSubscription<Uri>? _linkSubscription;
  DeepLinkInfo? _initialLink;
  DeepLinkInfo? _pendingLink;

  // Handlers registrados
  final List<DeepLinkHandler> _handlers = [];

  // Stream de links
  final StreamController<DeepLinkInfo> _linkController =
      StreamController<DeepLinkInfo>.broadcast();

  // Callback de navegación
  void Function(String route, {Map<String, dynamic>? arguments})? _navigator;

  DeepLinkService._() {
    _appLinks = AppLinks();
  }

  factory DeepLinkService() {
    _instance ??= DeepLinkService._();
    return _instance!;
  }

  /// Link inicial (si la app se abrió desde un deep link)
  DeepLinkInfo? get initialLink => _initialLink;

  /// Link pendiente de procesar
  DeepLinkInfo? get pendingLink => _pendingLink;

  /// Stream de deep links recibidos
  Stream<DeepLinkInfo> get linkStream => _linkController.stream;

  /// Configurar función de navegación
  void setNavigator(void Function(String route, {Map<String, dynamic>? arguments}) navigator) {
    _navigator = navigator;

    // Procesar link pendiente si existe
    if (_pendingLink != null) {
      _handleLink(_pendingLink!);
      _pendingLink = null;
    }
  }

  /// Inicializar servicio
  Future<void> initialize() async {
    // Obtener link inicial (si la app se abrió desde un deep link)
    try {
      final initialUri = await _appLinks.getInitialLink();
      if (initialUri != null) {
        _initialLink = DeepLinkInfo.fromUri(initialUri);
        debugPrint('[DeepLink] Link inicial: $_initialLink');

        // Si no hay navegador configurado, guardar como pendiente
        if (_navigator == null) {
          _pendingLink = _initialLink;
        } else {
          await _handleLink(_initialLink!);
        }
      }
    } on PlatformException catch (e) {
      debugPrint('[DeepLink] Error obteniendo link inicial: $e');
    }

    // Escuchar links mientras la app está abierta
    _linkSubscription = _appLinks.uriLinkStream.listen(
      (Uri uri) {
        final link = DeepLinkInfo.fromUri(uri);
        debugPrint('[DeepLink] Link recibido: $link');

        _linkController.add(link);

        if (_navigator != null) {
          _handleLink(link);
        } else {
          _pendingLink = link;
        }
      },
      onError: (error) {
        debugPrint('[DeepLink] Error en stream: $error');
      },
    );
  }

  /// Registrar handler personalizado
  void registerHandler(DeepLinkHandler handler) {
    _handlers.add(handler);
  }

  /// Eliminar handler
  void unregisterHandler(DeepLinkHandler handler) {
    _handlers.remove(handler);
  }

  /// Procesar deep link
  Future<void> _handleLink(DeepLinkInfo link) async {
    // Intentar con handlers personalizados primero
    for (final handler in _handlers) {
      try {
        final result = await handler(link);
        if (result.handled) {
          if (result.route != null && _navigator != null) {
            _navigator!(result.route!, arguments: result.arguments);
          }
          return;
        }
      } catch (e) {
        debugPrint('[DeepLink] Error en handler: $e');
      }
    }

    // Handler por defecto
    final result = await _defaultHandler(link);
    if (result.handled && result.route != null && _navigator != null) {
      _navigator!(result.route!, arguments: result.arguments);
    }
  }

  /// Handler por defecto
  Future<DeepLinkNavigationResult> _defaultHandler(DeepLinkInfo link) async {
    if (link.module == null) {
      return DeepLinkNavigationResult.notHandled();
    }

    // Mapeo de módulos a rutas
    final moduleRoutes = {
      'eventos': '/eventos',
      'marketplace': '/marketplace',
      'socios': '/socios',
      'talleres': '/talleres',
      'cursos': '/cursos',
      'reservas': '/reservas',
      'foros': '/foros',
      'perfil': '/perfil',
      'notificaciones': '/notificaciones',
      'ajustes': '/ajustes',
    };

    final baseRoute = moduleRoutes[link.module];
    if (baseRoute == null) {
      return DeepLinkNavigationResult.notHandled();
    }

    String route = baseRoute;
    Map<String, dynamic> arguments = {};

    // Construir ruta con acción e ID
    if (link.action != null) {
      switch (link.action) {
        case 'detalle':
        case 'view':
        case 'ver':
          if (link.itemId != null) {
            route = '$baseRoute/detalle';
            arguments['id'] = link.itemId;
          }
          break;
        case 'crear':
        case 'create':
        case 'nuevo':
          route = '$baseRoute/crear';
          break;
        case 'editar':
        case 'edit':
          if (link.itemId != null) {
            route = '$baseRoute/editar';
            arguments['id'] = link.itemId;
          }
          break;
        default:
          route = '$baseRoute/${link.action}';
          if (link.itemId != null) {
            arguments['id'] = link.itemId;
          }
      }
    }

    // Agregar query params a arguments
    arguments.addAll(link.queryParams);

    debugPrint('[DeepLink] Navegando a: $route con args: $arguments');

    return DeepLinkNavigationResult.success(route, arguments: arguments);
  }

  /// Crear deep link para compartir
  String createLink({
    required String module,
    String? action,
    String? itemId,
    Map<String, String>? params,
  }) {
    final baseUrl = 'https://app.example.com'; // Configurar URL base
    var path = '/$module';

    if (action != null) {
      path += '/$action';
    }
    if (itemId != null) {
      path += '/$itemId';
    }

    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: params);
    return uri.toString();
  }

  /// Parsear URL manualmente (útil para testing)
  DeepLinkInfo parseUrl(String url) {
    return DeepLinkInfo.fromUri(Uri.parse(url));
  }

  /// Simular recepción de deep link (para testing)
  Future<void> simulateLink(String url) async {
    final link = parseUrl(url);
    _linkController.add(link);
    await _handleLink(link);
  }

  /// Limpiar recursos
  void dispose() {
    _linkSubscription?.cancel();
    _linkController.close();
    _handlers.clear();
  }
}
