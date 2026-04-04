import 'dart:io';
import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../config/app_config.dart';
import '../utils/flavor_url_launcher.dart';

/// Información de actualización disponible
class UpdateInfo {
  final bool updateAvailable;
  final String latestVersion;
  final int latestBuild;
  final String? downloadUrl;
  final String? changelog;
  final bool isMandatory;
  final int? fileSize;
  final String? minOsVersion;

  UpdateInfo({
    required this.updateAvailable,
    required this.latestVersion,
    required this.latestBuild,
    this.downloadUrl,
    this.changelog,
    this.isMandatory = false,
    this.fileSize,
    this.minOsVersion,
  });

  factory UpdateInfo.noUpdate() => UpdateInfo(
    updateAvailable: false,
    latestVersion: '',
    latestBuild: 0,
  );

  factory UpdateInfo.fromJson(Map<String, dynamic> json) {
    return UpdateInfo(
      updateAvailable: json['update_available'] == true,
      latestVersion: json['latest_version'] ?? '',
      latestBuild: json['latest_build'] ?? 0,
      downloadUrl: json['download_url'],
      changelog: json['changelog'],
      isMandatory: json['is_mandatory'] == true,
      fileSize: json['file_size'],
      minOsVersion: json['min_os_version'],
    );
  }

  String get fileSizeFormatted {
    if (fileSize == null) return '';
    final mb = fileSize! / (1024 * 1024);
    return '${mb.toStringAsFixed(1)} MB';
  }
}

/// Resultado de verificación de actualización
enum UpdateCheckResult {
  upToDate,
  optionalUpdate,
  mandatoryUpdate,
  error,
}

/// Servicio de gestión de actualizaciones
class AppUpdateService {
  static AppUpdateService? _instance;

  final ApiClient _apiClient;
  UpdateInfo? _lastUpdateInfo;
  DateTime? _lastCheckTime;
  static const Duration _checkInterval = Duration(hours: 6);

  AppUpdateService._({required ApiClient apiClient}) : _apiClient = apiClient;

  factory AppUpdateService({required ApiClient apiClient}) {
    _instance ??= AppUpdateService._(apiClient: apiClient);
    return _instance!;
  }

  /// Última información de actualización
  UpdateInfo? get lastUpdateInfo => _lastUpdateInfo;

  /// ¿Hay actualización disponible?
  bool get hasUpdate => _lastUpdateInfo?.updateAvailable ?? false;

  /// ¿Es actualización obligatoria?
  bool get isMandatory => _lastUpdateInfo?.isMandatory ?? false;

  /// Verificar actualizaciones
  Future<UpdateCheckResult> checkForUpdates({bool force = false}) async {
    // Usar cache si no ha pasado el intervalo
    if (!force && _lastCheckTime != null && _lastUpdateInfo != null) {
      final elapsed = DateTime.now().difference(_lastCheckTime!);
      if (elapsed < _checkInterval) {
        return _getResult(_lastUpdateInfo!);
      }
    }

    try {
      final platform = Platform.isIOS ? 'ios' : 'android';
      const appType = AppConfig.isAdminApp ? 'admin' : 'client';

      final response = await _apiClient.get(
        '/flavor-app/v2/releases/check-update',
        queryParameters: {
          'app_type': appType,
          'platform': platform,
          'current_version': AppConfig.appVersion,
          'current_build': AppConfig.appBuild,
        },
      );

      _lastUpdateInfo = UpdateInfo.fromJson(response.data ?? {});
      _lastCheckTime = DateTime.now();

      debugPrint('[AppUpdateService] Update check: ${_lastUpdateInfo!.updateAvailable}');

      return _getResult(_lastUpdateInfo!);
    } catch (e) {
      debugPrint('[AppUpdateService] Error verificando: $e');
      return UpdateCheckResult.error;
    }
  }

  UpdateCheckResult _getResult(UpdateInfo info) {
    if (!info.updateAvailable) return UpdateCheckResult.upToDate;
    if (info.isMandatory) return UpdateCheckResult.mandatoryUpdate;
    return UpdateCheckResult.optionalUpdate;
  }

  /// Descargar/abrir actualización
  Future<bool> downloadUpdate() async {
    if (_lastUpdateInfo?.downloadUrl == null) return false;

    try {
      return FlavorUrlLauncher.openExternalRaw(_lastUpdateInfo!.downloadUrl!);
    } catch (e) {
      debugPrint('[AppUpdateService] Error abriendo descarga: $e');
      return false;
    }
  }

  /// Abrir tienda de apps
  Future<bool> openStore() async {
    try {
      final storeUrl = Platform.isIOS
          ? 'https://apps.apple.com/app/id${AppConfig.appStoreId}'
          : 'https://play.google.com/store/apps/details?id=${AppConfig.packageName}';

      return FlavorUrlLauncher.openExternalRaw(storeUrl);
    } catch (e) {
      debugPrint('[AppUpdateService] Error abriendo store: $e');
      return false;
    }
  }

  /// Mostrar diálogo de actualización
  Future<void> showUpdateDialog(BuildContext context) async {
    if (_lastUpdateInfo == null || !_lastUpdateInfo!.updateAvailable) return;

    final info = _lastUpdateInfo!;

    await showDialog(
      context: context,
      barrierDismissible: !info.isMandatory,
      builder: (context) => UpdateDialog(
        updateInfo: info,
        onUpdate: () async {
          Navigator.pop(context);
          await downloadUpdate();
        },
        onLater: info.isMandatory
            ? null
            : () => Navigator.pop(context),
      ),
    );
  }
}

/// Diálogo de actualización
class UpdateDialog extends StatelessWidget {
  final UpdateInfo updateInfo;
  final VoidCallback onUpdate;
  final VoidCallback? onLater;

  const UpdateDialog({
    super.key,
    required this.updateInfo,
    required this.onUpdate,
    this.onLater,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return PopScope(
      canPop: !updateInfo.isMandatory,
      child: AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        contentPadding: const EdgeInsets.all(24),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Icono
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: theme.primaryColor.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(
                updateInfo.isMandatory
                    ? Icons.system_update
                    : Icons.update,
                size: 48,
                color: theme.primaryColor,
              ),
            ),

            const SizedBox(height: 24),

            // Título
            Text(
              updateInfo.isMandatory
                  ? 'Actualización Requerida'
                  : 'Nueva Versión Disponible',
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
              textAlign: TextAlign.center,
            ),

            const SizedBox(height: 12),

            // Versión
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: theme.primaryColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                'v${updateInfo.latestVersion}',
                style: TextStyle(
                  color: theme.primaryColor,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),

            const SizedBox(height: 16),

            // Changelog
            if (updateInfo.changelog != null) ...[
              Container(
                constraints: const BoxConstraints(maxHeight: 150),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: SingleChildScrollView(
                  child: Text(
                    updateInfo.changelog!,
                    style: TextStyle(
                      color: Colors.grey.shade700,
                      fontSize: 14,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Tamaño
            if (updateInfo.fileSize != null)
              Text(
                'Tamaño: ${updateInfo.fileSizeFormatted}',
                style: TextStyle(
                  color: Colors.grey.shade600,
                  fontSize: 13,
                ),
              ),

            const SizedBox(height: 24),

            // Botones
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: onUpdate,
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'Actualizar Ahora',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),

            if (onLater != null) ...[
              const SizedBox(height: 12),
              TextButton(
                onPressed: onLater,
                child: Text(
                  'Más tarde',
                  style: TextStyle(
                    color: Colors.grey.shade600,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
