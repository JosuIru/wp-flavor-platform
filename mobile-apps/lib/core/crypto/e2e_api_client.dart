import 'package:dio/dio.dart';
import '../config/server_config.dart';

/// Cliente API para endpoints E2E del servidor
class E2EApiClient {
  final Dio _dio;

  E2EApiClient({required Dio dio}) : _dio = dio;

  /// Obtiene el estado E2E del usuario actual
  Future<Map<String, dynamic>?> getE2EStatus() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get('$serverUrl/wp-json/flavor/v1/e2e/status');

      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      return null;
    } on DioException catch (e) {
      print('Error obteniendo estado E2E: ${e.message}');
      return null;
    }
  }

  /// Registra las claves E2E en el servidor
  Future<bool> registerKeys({
    required String deviceId,
    required String identityKey,
    required Map<String, dynamic> signedPrekey,
    required List<Map<String, dynamic>> oneTimePrekeys,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/e2e/keys/register',
        data: {
          'device_id': deviceId,
          'identity_key': identityKey,
          'signed_prekey': signedPrekey,
          'one_time_prekeys': oneTimePrekeys,
        },
      );

      return response.statusCode == 200 && response.data['success'] == true;
    } on DioException catch (e) {
      print('Error registrando claves E2E: ${e.message}');
      return false;
    }
  }

  /// Obtiene el prekey bundle de un usuario
  Future<Map<String, dynamic>?> getPrekeyBundle(int userId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/e2e/prekey-bundle/$userId',
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        return response.data['bundle'] as Map<String, dynamic>;
      }
      return null;
    } on DioException catch (e) {
      print('Error obteniendo prekey bundle: ${e.message}');
      return null;
    }
  }

  /// Registra un dispositivo
  Future<bool> registerDevice({
    required String deviceId,
    required String deviceName,
    required String deviceType,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/e2e/device/register',
        data: {
          'device_id': deviceId,
          'nombre': deviceName,
          'tipo': deviceType,
        },
      );

      return response.statusCode == 200 && response.data['success'] == true;
    } on DioException catch (e) {
      print('Error registrando dispositivo: ${e.message}');
      return false;
    }
  }

  /// Obtiene la lista de dispositivos del usuario
  Future<List<Map<String, dynamic>>> getDevices() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/e2e/devices',
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        return List<Map<String, dynamic>>.from(response.data['devices'] ?? []);
      }
      return [];
    } on DioException catch (e) {
      print('Error obteniendo dispositivos: ${e.message}');
      return [];
    }
  }

  /// Revoca un dispositivo
  Future<bool> revokeDevice(String deviceId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/e2e/device/$deviceId/revoke',
      );

      return response.statusCode == 200 && response.data['success'] == true;
    } on DioException catch (e) {
      print('Error revocando dispositivo: ${e.message}');
      return false;
    }
  }

  /// Inicializa una sesion E2E con otro usuario
  Future<Map<String, dynamic>?> initSession(int targetUserId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/e2e/session/init',
        data: {'target_user_id': targetUserId},
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        return response.data as Map<String, dynamic>;
      }
      return null;
    } on DioException catch (e) {
      print('Error inicializando sesion E2E: ${e.message}');
      return null;
    }
  }

  /// Verifica si hay sesion activa con un usuario
  Future<bool> checkSession(int targetUserId) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/e2e/session/check',
        queryParameters: {'target_user_id': targetUserId},
      );

      return response.statusCode == 200 &&
          response.data['session_exists'] == true;
    } on DioException catch (e) {
      print('Error verificando sesion E2E: ${e.message}');
      return false;
    }
  }

  /// Verifica la identidad de un usuario
  Future<bool> verifyIdentity({
    required int targetUserId,
    required String fingerprint,
  }) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/e2e/verify',
        data: {
          'target_user_id': targetUserId,
          'fingerprint': fingerprint,
        },
      );

      return response.statusCode == 200 && response.data['success'] == true;
    } on DioException catch (e) {
      print('Error verificando identidad: ${e.message}');
      return false;
    }
  }

  /// Obtiene las identidades verificadas
  Future<List<Map<String, dynamic>>> getVerifiedIdentities() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.get(
        '$serverUrl/wp-json/flavor/v1/e2e/verified',
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        return List<Map<String, dynamic>>.from(
          response.data['verified'] ?? [],
        );
      }
      return [];
    } on DioException catch (e) {
      print('Error obteniendo identidades verificadas: ${e.message}');
      return [];
    }
  }

  /// Crea un backup cifrado de las claves
  Future<Map<String, dynamic>?> createBackup(String backupCode) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/e2e/backup/create',
        data: {'backup_code': backupCode},
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        return response.data as Map<String, dynamic>;
      }
      return null;
    } on DioException catch (e) {
      print('Error creando backup: ${e.message}');
      return null;
    }
  }

  /// Restaura claves desde backup
  Future<Map<String, dynamic>?> restoreBackup(String backupCode) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/e2e/backup/restore',
        data: {'backup_code': backupCode},
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        return response.data as Map<String, dynamic>;
      }
      return null;
    } on DioException catch (e) {
      print('Error restaurando backup: ${e.message}');
      return null;
    }
  }

  /// Regenera one-time prekeys
  Future<bool> regeneratePrekeys(List<Map<String, dynamic>> prekeys) async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final response = await _dio.post(
        '$serverUrl/wp-json/flavor/v1/e2e/prekeys/regenerate',
        data: {'prekeys': prekeys},
      );

      return response.statusCode == 200 && response.data['success'] == true;
    } on DioException catch (e) {
      print('Error regenerando prekeys: ${e.message}');
      return false;
    }
  }
}
