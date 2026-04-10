import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:path/path.dart' as path;

/// Resultado de una subida de archivo
class UploadResult {
  final bool success;
  final String? url;
  final String? error;
  final String? fileId;

  const UploadResult({
    required this.success,
    this.url,
    this.error,
    this.fileId,
  });

  factory UploadResult.success(String url, {String? fileId}) {
    return UploadResult(
      success: true,
      url: url,
      fileId: fileId,
    );
  }

  factory UploadResult.failure(String error) {
    return UploadResult(
      success: false,
      error: error,
    );
  }
}

/// Progreso de subida
class UploadProgress {
  final int bytesSent;
  final int bytesTotal;
  final double percentage;

  const UploadProgress({
    required this.bytesSent,
    required this.bytesTotal,
    required this.percentage,
  });

  factory UploadProgress.zero() {
    return const UploadProgress(
      bytesSent: 0,
      bytesTotal: 0,
      percentage: 0,
    );
  }
}

/// Tipo de media para subir
enum MediaType {
  image,
  video,
  audio,
  document,
}

/// Servicio para subir archivos multimedia al servidor
class MediaUploadService {
  static MediaUploadService? _instance;

  // URL base del endpoint de subida (configurar según el backend)
  String _uploadEndpoint = '/wp-json/flavor-chat/v1/media/upload';

  // Token de autenticación
  String? _authToken;

  MediaUploadService._();

  factory MediaUploadService() {
    _instance ??= MediaUploadService._();
    return _instance!;
  }

  /// Configurar el servicio
  void configure({
    String? uploadEndpoint,
    String? authToken,
  }) {
    if (uploadEndpoint != null) _uploadEndpoint = uploadEndpoint;
    if (authToken != null) _authToken = authToken;
  }

  /// Subir una imagen
  Future<UploadResult> uploadImage(
    File file, {
    ValueChanged<UploadProgress>? onProgress,
    int? maxWidth,
    int? maxHeight,
    int quality = 85,
  }) async {
    return _uploadFile(
      file,
      MediaType.image,
      onProgress: onProgress,
      metadata: {
        'max_width': maxWidth,
        'max_height': maxHeight,
        'quality': quality,
      },
    );
  }

  /// Subir un video
  Future<UploadResult> uploadVideo(
    File file, {
    ValueChanged<UploadProgress>? onProgress,
  }) async {
    return _uploadFile(file, MediaType.video, onProgress: onProgress);
  }

  /// Subir un archivo de audio
  Future<UploadResult> uploadAudio(
    File file, {
    ValueChanged<UploadProgress>? onProgress,
  }) async {
    return _uploadFile(file, MediaType.audio, onProgress: onProgress);
  }

  /// Subir un documento
  Future<UploadResult> uploadDocument(
    File file, {
    ValueChanged<UploadProgress>? onProgress,
  }) async {
    return _uploadFile(file, MediaType.document, onProgress: onProgress);
  }

  /// Subir archivo genérico
  Future<UploadResult> _uploadFile(
    File file,
    MediaType type, {
    ValueChanged<UploadProgress>? onProgress,
    Map<String, dynamic>? metadata,
  }) async {
    try {
      // Verificar que el archivo existe
      if (!await file.exists()) {
        return UploadResult.failure('El archivo no existe');
      }

      // Obtener información del archivo
      final fileName = path.basename(file.path);
      final fileSize = await file.length();
      final extension = path.extension(file.path).toLowerCase();

      // Validar tipo de archivo
      if (!_isValidFileType(extension, type)) {
        return UploadResult.failure('Tipo de archivo no permitido');
      }

      // Validar tamaño (máximo 50MB para imágenes, 100MB para video)
      final maxSize = type == MediaType.video ? 100 * 1024 * 1024 : 50 * 1024 * 1024;
      if (fileSize > maxSize) {
        return UploadResult.failure('El archivo es demasiado grande');
      }

      // Simular progreso inicial
      onProgress?.call(UploadProgress(
        bytesSent: 0,
        bytesTotal: fileSize,
        percentage: 0,
      ));

      // TODO: Implementar subida real con http/dio
      // Por ahora simulamos una subida exitosa con URL temporal

      // Simular progreso de subida
      for (int i = 1; i <= 10; i++) {
        await Future.delayed(const Duration(milliseconds: 100));
        onProgress?.call(UploadProgress(
          bytesSent: (fileSize * i / 10).round(),
          bytesTotal: fileSize,
          percentage: i * 10,
        ));
      }

      // Generar URL temporal (en producción esto vendría del servidor)
      final timestamp = DateTime.now().millisecondsSinceEpoch;
      final tempUrl = 'https://placeholder.com/uploads/$timestamp/$fileName';

      debugPrint('[MediaUploadService] Archivo subido: $fileName -> $tempUrl');

      return UploadResult.success(
        tempUrl,
        fileId: timestamp.toString(),
      );
    } catch (e) {
      debugPrint('[MediaUploadService] Error al subir archivo: $e');
      return UploadResult.failure('Error al subir el archivo: $e');
    }
  }

  /// Validar tipo de archivo según la categoría
  bool _isValidFileType(String extension, MediaType type) {
    switch (type) {
      case MediaType.image:
        return ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.heic'].contains(extension);
      case MediaType.video:
        return ['.mp4', '.mov', '.avi', '.webm', '.mkv'].contains(extension);
      case MediaType.audio:
        return ['.mp3', '.m4a', '.wav', '.ogg', '.aac'].contains(extension);
      case MediaType.document:
        return ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.txt', '.zip'].contains(extension);
    }
  }

  /// Cancelar subida en progreso
  void cancelUpload(String fileId) {
    // TODO: Implementar cancelación de subida
    debugPrint('[MediaUploadService] Cancelando subida: $fileId');
  }

  /// Eliminar archivo subido
  Future<bool> deleteFile(String fileId) async {
    // TODO: Implementar eliminación de archivo en el servidor
    debugPrint('[MediaUploadService] Eliminando archivo: $fileId');
    return true;
  }
}
