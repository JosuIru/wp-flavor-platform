import 'dart:convert';
import 'dart:typed_data';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:cryptography/cryptography.dart';

/// Almacenamiento seguro de claves E2E usando flutter_secure_storage
/// Las claves se cifran con AES-GCM antes de almacenarse
class E2EKeyStore {
  static const String _keyPrefix = 'e2e_';
  static const String _identityKeyPrefix = '${_keyPrefix}identity_';
  static const String _sessionKeyPrefix = '${_keyPrefix}session_';
  static const String _signedPrekeyPrefix = '${_keyPrefix}signed_prekey_';
  static const String _deviceIdKey = '${_keyPrefix}device_id';
  static const String _masterKeyKey = '${_keyPrefix}master_key';

  final FlutterSecureStorage _storage;
  SecretKey? _masterKey;

  E2EKeyStore({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage(
          aOptions: AndroidOptions(
            encryptedSharedPreferences: true,
          ),
          iOptions: IOSOptions(
            accessibility: KeychainAccessibility.first_unlock_this_device,
          ),
        );

  /// Inicializa el key store y genera/recupera la master key
  Future<void> initialize() async {
    final existingMasterKey = await _storage.read(key: _masterKeyKey);

    if (existingMasterKey != null) {
      _masterKey = SecretKey(base64Decode(existingMasterKey));
    } else {
      // Generar nueva master key
      final algorithm = AesGcm.with256bits();
      _masterKey = await algorithm.newSecretKey();
      final keyBytes = await _masterKey!.extractBytes();
      await _storage.write(key: _masterKeyKey, value: base64Encode(keyBytes));
    }
  }

  /// Obtiene o genera el ID único del dispositivo
  Future<String> getOrCreateDeviceId() async {
    var deviceId = await _storage.read(key: _deviceIdKey);

    if (deviceId == null) {
      // Generar un ID único para este dispositivo
      final random = SecureRandom.fast;
      final bytes = Uint8List(16);
      for (var i = 0; i < bytes.length; i++) {
        bytes[i] = random.nextInt(256);
      }
      deviceId = base64UrlEncode(bytes).replaceAll('=', '');
      await _storage.write(key: _deviceIdKey, value: deviceId);
    }

    return deviceId;
  }

  // ==========================================
  // IDENTITY KEYS
  // ==========================================

  /// Guarda el par de claves de identidad
  Future<void> saveIdentityKeyPair({
    required int userId,
    required Uint8List publicKey,
    required Uint8List privateKey,
  }) async {
    final data = {
      'publicKey': base64Encode(publicKey),
      'privateKey': base64Encode(privateKey),
    };
    await _saveEncrypted('$_identityKeyPrefix$userId', jsonEncode(data));
  }

  /// Obtiene el par de claves de identidad
  Future<Map<String, Uint8List>?> getIdentityKeyPair(int userId) async {
    final data = await _readEncrypted('$_identityKeyPrefix$userId');
    if (data == null) return null;

    final json = jsonDecode(data) as Map<String, dynamic>;
    return {
      'publicKey': base64Decode(json['publicKey'] as String),
      'privateKey': base64Decode(json['privateKey'] as String),
    };
  }

  /// Guarda la clave pública de identidad de otro usuario
  Future<void> saveRemoteIdentityKey({
    required int remoteUserId,
    required Uint8List publicKey,
  }) async {
    await _saveEncrypted(
      '${_identityKeyPrefix}remote_$remoteUserId',
      base64Encode(publicKey),
    );
  }

  /// Obtiene la clave pública de identidad de otro usuario
  Future<Uint8List?> getRemoteIdentityKey(int remoteUserId) async {
    final data = await _readEncrypted('${_identityKeyPrefix}remote_$remoteUserId');
    if (data == null) return null;
    return base64Decode(data);
  }

  // ==========================================
  // SIGNED PREKEYS
  // ==========================================

  /// Guarda el signed prekey actual
  Future<void> saveSignedPrekey({
    required int userId,
    required int prekeyId,
    required Uint8List publicKey,
    required Uint8List privateKey,
    required Uint8List signature,
  }) async {
    final data = {
      'prekeyId': prekeyId,
      'publicKey': base64Encode(publicKey),
      'privateKey': base64Encode(privateKey),
      'signature': base64Encode(signature),
    };
    await _saveEncrypted('$_signedPrekeyPrefix$userId', jsonEncode(data));
  }

  /// Obtiene el signed prekey
  Future<Map<String, dynamic>?> getSignedPrekey(int userId) async {
    final data = await _readEncrypted('$_signedPrekeyPrefix$userId');
    if (data == null) return null;

    final json = jsonDecode(data) as Map<String, dynamic>;
    return {
      'prekeyId': json['prekeyId'] as int,
      'publicKey': base64Decode(json['publicKey'] as String),
      'privateKey': base64Decode(json['privateKey'] as String),
      'signature': base64Decode(json['signature'] as String),
    };
  }

  // ==========================================
  // SESSIONS (Double Ratchet State)
  // ==========================================

  /// Guarda el estado de una sesión
  Future<void> saveSession({
    required int localUserId,
    required int remoteUserId,
    required Map<String, dynamic> sessionState,
  }) async {
    final key = '${_sessionKeyPrefix}${localUserId}_$remoteUserId';
    await _saveEncrypted(key, jsonEncode(sessionState));
  }

  /// Obtiene el estado de una sesión
  Future<Map<String, dynamic>?> getSession({
    required int localUserId,
    required int remoteUserId,
  }) async {
    final key = '${_sessionKeyPrefix}${localUserId}_$remoteUserId';
    final data = await _readEncrypted(key);
    if (data == null) return null;
    return jsonDecode(data) as Map<String, dynamic>;
  }

  /// Verifica si existe una sesión
  Future<bool> hasSession({
    required int localUserId,
    required int remoteUserId,
  }) async {
    final session = await getSession(
      localUserId: localUserId,
      remoteUserId: remoteUserId,
    );
    return session != null;
  }

  /// Elimina una sesión
  Future<void> deleteSession({
    required int localUserId,
    required int remoteUserId,
  }) async {
    final key = '${_sessionKeyPrefix}${localUserId}_$remoteUserId';
    await _storage.delete(key: key);
  }

  // ==========================================
  // SKIPPED MESSAGE KEYS
  // ==========================================

  /// Guarda una clave de mensaje saltado (para mensajes fuera de orden)
  Future<void> saveSkippedMessageKey({
    required int localUserId,
    required int remoteUserId,
    required Uint8List ratchetKey,
    required int messageNumber,
    required Uint8List messageKey,
  }) async {
    final skippedKeysJson = await _readEncrypted(
      '${_keyPrefix}skipped_${localUserId}_$remoteUserId',
    );

    final skippedKeys = skippedKeysJson != null
        ? List<Map<String, dynamic>>.from(jsonDecode(skippedKeysJson))
        : <Map<String, dynamic>>[];

    skippedKeys.add({
      'ratchetKey': base64Encode(ratchetKey),
      'messageNumber': messageNumber,
      'messageKey': base64Encode(messageKey),
      'timestamp': DateTime.now().millisecondsSinceEpoch,
    });

    // Limitar a 100 claves saltadas
    if (skippedKeys.length > 100) {
      skippedKeys.removeRange(0, skippedKeys.length - 100);
    }

    await _saveEncrypted(
      '${_keyPrefix}skipped_${localUserId}_$remoteUserId',
      jsonEncode(skippedKeys),
    );
  }

  /// Busca y consume una clave de mensaje saltado
  Future<Uint8List?> consumeSkippedMessageKey({
    required int localUserId,
    required int remoteUserId,
    required Uint8List ratchetKey,
    required int messageNumber,
  }) async {
    final skippedKeysJson = await _readEncrypted(
      '${_keyPrefix}skipped_${localUserId}_$remoteUserId',
    );

    if (skippedKeysJson == null) return null;

    final skippedKeys = List<Map<String, dynamic>>.from(jsonDecode(skippedKeysJson));
    final ratchetKeyB64 = base64Encode(ratchetKey);

    for (var i = 0; i < skippedKeys.length; i++) {
      final entry = skippedKeys[i];
      if (entry['ratchetKey'] == ratchetKeyB64 &&
          entry['messageNumber'] == messageNumber) {
        final messageKey = base64Decode(entry['messageKey'] as String);
        skippedKeys.removeAt(i);

        await _saveEncrypted(
          '${_keyPrefix}skipped_${localUserId}_$remoteUserId',
          jsonEncode(skippedKeys),
        );

        return messageKey;
      }
    }

    return null;
  }

  // ==========================================
  // UTILIDADES INTERNAS
  // ==========================================

  /// Guarda datos cifrados con AES-GCM
  Future<void> _saveEncrypted(String key, String value) async {
    if (_masterKey == null) {
      throw StateError('E2EKeyStore no inicializado. Llama a initialize() primero.');
    }

    final algorithm = AesGcm.with256bits();
    final nonce = algorithm.newNonce();

    final secretBox = await algorithm.encrypt(
      utf8.encode(value),
      secretKey: _masterKey!,
      nonce: nonce,
    );

    final combined = {
      'nonce': base64Encode(secretBox.nonce),
      'ciphertext': base64Encode(secretBox.cipherText),
      'mac': base64Encode(secretBox.mac.bytes),
    };

    await _storage.write(key: key, value: jsonEncode(combined));
  }

  /// Lee y descifra datos
  Future<String?> _readEncrypted(String key) async {
    if (_masterKey == null) {
      throw StateError('E2EKeyStore no inicializado. Llama a initialize() primero.');
    }

    final stored = await _storage.read(key: key);
    if (stored == null) return null;

    try {
      final combined = jsonDecode(stored) as Map<String, dynamic>;
      final nonce = base64Decode(combined['nonce'] as String);
      final ciphertext = base64Decode(combined['ciphertext'] as String);
      final mac = base64Decode(combined['mac'] as String);

      final algorithm = AesGcm.with256bits();
      final secretBox = SecretBox(ciphertext, nonce: nonce, mac: Mac(mac));

      final decrypted = await algorithm.decrypt(
        secretBox,
        secretKey: _masterKey!,
      );

      return utf8.decode(decrypted);
    } catch (e) {
      // Si falla el descifrado, eliminar la clave corrupta
      await _storage.delete(key: key);
      return null;
    }
  }

  /// Limpia todas las claves E2E (para logout/reset)
  Future<void> clearAll() async {
    final allKeys = await _storage.readAll();
    for (final key in allKeys.keys) {
      if (key.startsWith(_keyPrefix)) {
        await _storage.delete(key: key);
      }
    }
    _masterKey = null;
  }

  /// Exporta las claves para backup (cifradas)
  Future<String?> exportForBackup(String backupPassword) async {
    // TODO: Implementar exportación cifrada con password
    return null;
  }

  /// Importa claves desde backup
  Future<bool> importFromBackup(String backupData, String backupPassword) async {
    // TODO: Implementar importación desde backup
    return false;
  }
}
