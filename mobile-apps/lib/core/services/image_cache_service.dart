import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter_cache_manager/flutter_cache_manager.dart';
import 'package:path_provider/path_provider.dart';

/// Configuración del cache de imágenes
class ImageCacheConfig {
  final Duration stalePeriod;
  final int maxNrOfCacheObjects;
  final String cacheKey;

  const ImageCacheConfig({
    this.stalePeriod = const Duration(days: 7),
    this.maxNrOfCacheObjects = 200,
    this.cacheKey = 'flavorImageCache',
  });
}

/// Servicio de cache de imágenes
class ImageCacheService {
  static ImageCacheService? _instance;

  late final CacheManager _cacheManager;
  ImageCacheConfig _config = const ImageCacheConfig();

  // Imágenes críticas para precargar
  final List<String> _criticalImages = [];

  // Placeholders
  static const String defaultPlaceholder = 'assets/images/placeholder.png';
  static const String errorPlaceholder = 'assets/images/error_image.png';

  ImageCacheService._();

  factory ImageCacheService() {
    _instance ??= ImageCacheService._();
    return _instance!;
  }

  /// Inicializar servicio con configuración
  Future<void> initialize({ImageCacheConfig? config}) async {
    _config = config ?? const ImageCacheConfig();

    _cacheManager = CacheManager(
      Config(
        _config.cacheKey,
        stalePeriod: _config.stalePeriod,
        maxNrOfCacheObjects: _config.maxNrOfCacheObjects,
      ),
    );

    debugPrint('[ImageCache] Inicializado con max ${_config.maxNrOfCacheObjects} objetos');
  }

  /// Precargar imagen
  Future<void> precache(String url) async {
    try {
      await _cacheManager.downloadFile(url);
      debugPrint('[ImageCache] Precargada: $url');
    } catch (e) {
      debugPrint('[ImageCache] Error precargando: $e');
    }
  }

  /// Precargar múltiples imágenes
  Future<void> precacheAll(List<String> urls) async {
    await Future.wait(urls.map((url) => precache(url)));
  }

  /// Registrar imágenes críticas para precargar
  void registerCriticalImages(List<String> urls) {
    _criticalImages.addAll(urls);
  }

  /// Precargar imágenes críticas
  Future<void> precacheCritical() async {
    if (_criticalImages.isEmpty) return;
    await precacheAll(_criticalImages);
  }

  /// Obtener archivo cacheado
  Future<File?> getCachedFile(String url) async {
    try {
      final fileInfo = await _cacheManager.getFileFromCache(url);
      return fileInfo?.file;
    } catch (e) {
      return null;
    }
  }

  /// Verificar si una imagen está en cache
  Future<bool> isCached(String url) async {
    final file = await getCachedFile(url);
    return file != null && await file.exists();
  }

  /// Limpiar cache de una imagen específica
  Future<void> evict(String url) async {
    await _cacheManager.removeFile(url);
  }

  /// Limpiar todo el cache
  Future<void> clearAll() async {
    await _cacheManager.emptyCache();
    debugPrint('[ImageCache] Cache limpiado');
  }

  /// Obtener tamaño del cache
  Future<int> getCacheSize() async {
    try {
      final cacheDir = await getTemporaryDirectory();
      final cacheFolder = Directory('${cacheDir.path}/${_config.cacheKey}');

      if (!await cacheFolder.exists()) return 0;

      int totalSize = 0;
      await for (final entity in cacheFolder.list(recursive: true)) {
        if (entity is File) {
          totalSize += await entity.length();
        }
      }

      return totalSize;
    } catch (e) {
      return 0;
    }
  }

  /// Obtener tamaño formateado
  Future<String> getCacheSizeFormatted() async {
    final bytes = await getCacheSize();
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(1)} KB';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
  }

  /// Obtener CacheManager para uso con CachedNetworkImage
  CacheManager get cacheManager => _cacheManager;
}

/// Widget de imagen con cache optimizado
class CachedImage extends StatelessWidget {
  final String imageUrl;
  final double? width;
  final double? height;
  final BoxFit fit;
  final Widget? placeholder;
  final Widget? errorWidget;
  final BorderRadius? borderRadius;
  final Color? color;
  final BlendMode? colorBlendMode;
  final Duration fadeInDuration;
  final Map<String, String>? headers;

  const CachedImage({
    super.key,
    required this.imageUrl,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.placeholder,
    this.errorWidget,
    this.borderRadius,
    this.color,
    this.colorBlendMode,
    this.fadeInDuration = const Duration(milliseconds: 300),
    this.headers,
  });

  @override
  Widget build(BuildContext context) {
    Widget image = CachedNetworkImage(
      imageUrl: imageUrl,
      width: width,
      height: height,
      fit: fit,
      color: color,
      colorBlendMode: colorBlendMode,
      fadeInDuration: fadeInDuration,
      cacheManager: ImageCacheService().cacheManager,
      httpHeaders: headers,
      placeholder: (context, url) => placeholder ?? _buildPlaceholder(),
      errorWidget: (context, url, error) => errorWidget ?? _buildError(),
    );

    if (borderRadius != null) {
      image = ClipRRect(
        borderRadius: borderRadius!,
        child: image,
      );
    }

    return image;
  }

  Widget _buildPlaceholder() {
    return Container(
      width: width,
      height: height,
      color: Colors.grey.shade200,
      child: Center(
        child: SizedBox(
          width: 24,
          height: 24,
          child: CircularProgressIndicator(
            strokeWidth: 2,
            valueColor: AlwaysStoppedAnimation(Colors.grey.shade400),
          ),
        ),
      ),
    );
  }

  Widget _buildError() {
    return Container(
      width: width,
      height: height,
      color: Colors.grey.shade200,
      child: Center(
        child: Icon(
          Icons.broken_image,
          color: Colors.grey.shade400,
          size: 32,
        ),
      ),
    );
  }
}

/// Widget de avatar con cache
class CachedAvatar extends StatelessWidget {
  final String? imageUrl;
  final double radius;
  final String? name;
  final Color? backgroundColor;

  const CachedAvatar({
    super.key,
    this.imageUrl,
    this.radius = 24,
    this.name,
    this.backgroundColor,
  });

  @override
  Widget build(BuildContext context) {
    if (imageUrl == null || imageUrl!.isEmpty) {
      return _buildInitials(context);
    }

    return CachedNetworkImage(
      imageUrl: imageUrl!,
      imageBuilder: (context, imageProvider) => CircleAvatar(
        radius: radius,
        backgroundImage: imageProvider,
      ),
      placeholder: (context, url) => _buildInitials(context),
      errorWidget: (context, url, error) => _buildInitials(context),
      cacheManager: ImageCacheService().cacheManager,
    );
  }

  Widget _buildInitials(BuildContext context) {
    final initials = _getInitials();
    final bgColor = backgroundColor ?? Theme.of(context).primaryColor;

    return CircleAvatar(
      radius: radius,
      backgroundColor: bgColor,
      child: Text(
        initials,
        style: TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.bold,
          fontSize: radius * 0.7,
        ),
      ),
    );
  }

  String _getInitials() {
    if (name == null || name!.isEmpty) return '?';

    final parts = name!.trim().split(' ');
    if (parts.length == 1) {
      return parts[0].substring(0, 1).toUpperCase();
    }

    return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
  }
}

/// Widget de imagen de producto/item con ratio fijo
class CachedProductImage extends StatelessWidget {
  final String? imageUrl;
  final double aspectRatio;
  final BorderRadius borderRadius;

  const CachedProductImage({
    super.key,
    this.imageUrl,
    this.aspectRatio = 1.0,
    this.borderRadius = const BorderRadius.all(Radius.circular(8)),
  });

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: aspectRatio,
      child: ClipRRect(
        borderRadius: borderRadius,
        child: imageUrl != null && imageUrl!.isNotEmpty
            ? CachedNetworkImage(
                imageUrl: imageUrl!,
                fit: BoxFit.cover,
                placeholder: (context, url) => _buildPlaceholder(),
                errorWidget: (context, url, error) => _buildPlaceholder(),
                cacheManager: ImageCacheService().cacheManager,
              )
            : _buildPlaceholder(),
      ),
    );
  }

  Widget _buildPlaceholder() {
    return Container(
      color: Colors.grey.shade200,
      child: Center(
        child: Icon(
          Icons.image,
          color: Colors.grey.shade400,
          size: 48,
        ),
      ),
    );
  }
}
