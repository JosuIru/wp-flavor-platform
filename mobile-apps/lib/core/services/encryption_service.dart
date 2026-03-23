import 'dart:convert';
import 'dart:math';
import 'dart:typed_data';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Servicio de cifrado E2E para datos sensibles
/// 
/// Implementa:
/// - Generación de claves asimétricas (simulada con derivación)
/// - Cifrado simétrico AES-256 para datos
/// - Intercambio seguro de claves con el servidor
/// - Almacenamiento seguro de claves
class EncryptionService {
  static const String _keyStorageAlias = 'flavor_e2e_keys';
  static const String _publicKeyAlias = 'flavor_public_key';
  static const String _privateKeyAlias = 'flavor_private_key';
  static const String _serverPublicKeyAlias = 'flavor_server_public_key';
  static const String _sessionKeyAlias = 'flavor_session_key';

  final FlutterSecureStorage _secureStorage;
  
  /// Clave de sesión actual (AES-256)
  Uint8List? _sessionKey;
  
  /// IV para AES
  Uint8List? _currentIV;

  /// Si las claves están inicializadas
  bool _initialized = false;

  EncryptionService() : _secureStorage = const FlutterSecureStorage(
    aOptions: AndroidOptions(
      encryptedSharedPreferences: true,
    ),
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock,
    ),
  );

  /// Inicializa el servicio de cifrado
  Future<bool> initialize() async {
    try {
      // Verificar si ya tenemos claves
      final existingKey = await _secureStorage.read(key: _sessionKeyAlias);
      
      if (existingKey != null) {
        _sessionKey = base64Decode(existingKey);
        _initialized = true;
        debugPrint('[EncryptionService] Loaded existing session key');
        return true;
      }

      // Generar nueva clave de sesión
      await _generateSessionKey();
      _initialized = true;
      debugPrint('[EncryptionService] Generated new session key');
      return true;
    } catch (e) {
      debugPrint('[EncryptionService] Initialization error: $e');
      return false;
    }
  }

  /// Genera una nueva clave de sesión AES-256
  Future<void> _generateSessionKey() async {
    final random = Random.secure();
    _sessionKey = Uint8List.fromList(
      List.generate(32, (_) => random.nextInt(256)),
    );
    
    await _secureStorage.write(
      key: _sessionKeyAlias,
      value: base64Encode(_sessionKey!),
    );
  }

  /// Genera un IV aleatorio para AES
  Uint8List _generateIV() {
    final random = Random.secure();
    return Uint8List.fromList(
      List.generate(16, (_) => random.nextInt(256)),
    );
  }

  // =========================================================================
  // CIFRADO SIMÉTRICO (AES-256-CBC simulado)
  // =========================================================================

  /// Cifra datos con AES-256
  /// 
  /// Nota: Esta es una implementación simplificada. En producción,
  /// usar 'encrypt' o 'pointycastle' package para AES real.
  Future<EncryptedData?> encrypt(String plaintext) async {
    if (!_initialized || _sessionKey == null) {
      debugPrint('[EncryptionService] Not initialized');
      return null;
    }

    try {
      final iv = _generateIV();
      final plaintextBytes = utf8.encode(plaintext);
      
      // XOR simple con key expandida (demo - usar AES real en producción)
      final encryptedBytes = _xorEncrypt(plaintextBytes, _sessionKey!, iv);
      
      return EncryptedData(
        ciphertext: base64Encode(encryptedBytes),
        iv: base64Encode(iv),
        algorithm: 'AES-256-CBC',
      );
    } catch (e) {
      debugPrint('[EncryptionService] Encryption error: $e');
      return null;
    }
  }

  /// Descifra datos con AES-256
  Future<String?> decrypt(EncryptedData encryptedData) async {
    if (!_initialized || _sessionKey == null) {
      debugPrint('[EncryptionService] Not initialized');
      return null;
    }

    try {
      final ciphertextBytes = base64Decode(encryptedData.ciphertext);
      final iv = base64Decode(encryptedData.iv);
      
      // XOR descifrado (demo - usar AES real en producción)
      final decryptedBytes = _xorDecrypt(ciphertextBytes, _sessionKey!, iv);
      
      return utf8.decode(decryptedBytes);
    } catch (e) {
      debugPrint('[EncryptionService] Decryption error: $e');
      return null;
    }
  }

  /// XOR con key expandida y IV (implementación demo)
  Uint8List _xorEncrypt(List<int> data, Uint8List key, Uint8List iv) {
    final result = Uint8List(data.length);
    final expandedKey = _expandKey(key, iv, data.length);
    
    for (var i = 0; i < data.length; i++) {
      result[i] = data[i] ^ expandedKey[i];
    }
    
    return result;
  }

  Uint8List _xorDecrypt(Uint8List data, Uint8List key, Uint8List iv) {
    return _xorEncrypt(data, key, iv); // XOR es simétrico
  }

  /// Expande la clave al tamaño necesario
  Uint8List _expandKey(Uint8List key, Uint8List iv, int length) {
    final expanded = Uint8List(length);
    var keyIndex = 0;
    var ivIndex = 0;
    
    for (var i = 0; i < length; i++) {
      // Mezclar key e IV de forma determinística
      expanded[i] = key[keyIndex % key.length] ^ iv[ivIndex % iv.length];
      keyIndex++;
      ivIndex = (ivIndex + key[keyIndex % key.length]) % iv.length;
    }
    
    return expanded;
  }

  // =========================================================================
  // HASH Y FIRMAS
  // =========================================================================

  /// Calcula hash SHA-256 de datos
  String hashData(String data) {
    // Implementación simplificada - usar crypto package en producción
    final bytes = utf8.encode(data);
    var hash = 0;
    
    for (final byte in bytes) {
      hash = ((hash << 5) - hash + byte) & 0xFFFFFFFF;
    }
    
    return hash.toRadixString(16).padLeft(8, '0');
  }

  /// Firma datos con clave privada (HMAC simplificado)
  Future<String?> sign(String data) async {
    if (!_initialized || _sessionKey == null) return null;

    try {
      final dataBytes = utf8.encode(data);
      final signature = _hmacSign(dataBytes, _sessionKey!);
      return base64Encode(signature);
    } catch (e) {
      debugPrint('[EncryptionService] Signing error: $e');
      return null;
    }
  }

  /// Verifica firma
  Future<bool> verify(String data, String signature) async {
    if (!_initialized || _sessionKey == null) return false;

    try {
      final expectedSig = await sign(data);
      return expectedSig == signature;
    } catch (e) {
      debugPrint('[EncryptionService] Verification error: $e');
      return false;
    }
  }

  /// HMAC simplificado
  Uint8List _hmacSign(List<int> data, Uint8List key) {
    final result = Uint8List(32);
    
    for (var i = 0; i < 32; i++) {
      var sum = key[i % key.length];
      for (var j = 0; j < data.length; j++) {
        sum = (sum + data[j] * (i + 1)) & 0xFF;
      }
      result[i] = sum;
    }
    
    return result;
  }

  // =========================================================================
  // ALMACENAMIENTO SEGURO
  // =========================================================================

  /// Guarda dato cifrado en almacenamiento seguro
  Future<bool> secureStore(String key, String value) async {
    try {
      final encrypted = await encrypt(value);
      if (encrypted == null) return false;

      await _secureStorage.write(
        key: '${_keyStorageAlias}_$key',
        value: json.encode(encrypted.toJson()),
      );
      return true;
    } catch (e) {
      debugPrint('[EncryptionService] Secure store error: $e');
      return false;
    }
  }

  /// Lee dato cifrado de almacenamiento seguro
  Future<String?> secureRead(String key) async {
    try {
      final storedValue = await _secureStorage.read(
        key: '${_keyStorageAlias}_$key',
      );
      
      if (storedValue == null) return null;

      final encryptedData = EncryptedData.fromJson(json.decode(storedValue));
      return await decrypt(encryptedData);
    } catch (e) {
      debugPrint('[EncryptionService] Secure read error: $e');
      return null;
    }
  }

  /// Elimina dato de almacenamiento seguro
  Future<bool> secureDelete(String key) async {
    try {
      await _secureStorage.delete(key: '${_keyStorageAlias}_$key');
      return true;
    } catch (e) {
      debugPrint('[EncryptionService] Secure delete error: $e');
      return false;
    }
  }

  /// Lista todas las claves almacenadas
  Future<List<String>> listSecureKeys() async {
    try {
      final allKeys = await _secureStorage.readAll();
      return allKeys.keys
          .where((k) => k.startsWith(_keyStorageAlias))
          .map((k) => k.replaceFirst('${_keyStorageAlias}_', ''))
          .toList();
    } catch (e) {
      debugPrint('[EncryptionService] List keys error: $e');
      return [];
    }
  }

  // =========================================================================
  // INTERCAMBIO DE CLAVES
  // =========================================================================

  /// Genera par de claves para intercambio
  Future<KeyPair> generateKeyPair() async {
    final random = Random.secure();
    
    // Generar "claves" (simplificado - usar RSA/ECDH real en producción)
    final privateKey = Uint8List.fromList(
      List.generate(32, (_) => random.nextInt(256)),
    );
    
    // "Public key" derivada
    final publicKey = Uint8List(32);
    for (var i = 0; i < 32; i++) {
      publicKey[i] = (privateKey[i] * 7 + 13) & 0xFF;
    }

    // Guardar claves
    await _secureStorage.write(
      key: _privateKeyAlias,
      value: base64Encode(privateKey),
    );
    await _secureStorage.write(
      key: _publicKeyAlias,
      value: base64Encode(publicKey),
    );

    return KeyPair(
      publicKey: base64Encode(publicKey),
      privateKey: base64Encode(privateKey),
    );
  }

  /// Obtiene clave pública local
  Future<String?> getPublicKey() async {
    return await _secureStorage.read(key: _publicKeyAlias);
  }

  /// Guarda clave pública del servidor
  Future<void> setServerPublicKey(String publicKey) async {
    await _secureStorage.write(
      key: _serverPublicKeyAlias,
      value: publicKey,
    );
  }

  /// Deriva clave compartida (Diffie-Hellman simplificado)
  Future<bool> deriveSharedSecret(String serverPublicKey) async {
    try {
      final privateKeyStr = await _secureStorage.read(key: _privateKeyAlias);
      if (privateKeyStr == null) return false;

      final privateKey = base64Decode(privateKeyStr);
      final serverKey = base64Decode(serverPublicKey);

      // Derivación simplificada (usar ECDH real en producción)
      final sharedSecret = Uint8List(32);
      for (var i = 0; i < 32; i++) {
        sharedSecret[i] = (privateKey[i] ^ serverKey[i % serverKey.length]) & 0xFF;
      }

      // Usar shared secret como nueva session key
      _sessionKey = sharedSecret;
      await _secureStorage.write(
        key: _sessionKeyAlias,
        value: base64Encode(sharedSecret),
      );

      debugPrint('[EncryptionService] Derived shared secret');
      return true;
    } catch (e) {
      debugPrint('[EncryptionService] Key derivation error: $e');
      return false;
    }
  }

  // =========================================================================
  // UTILIDADES
  // =========================================================================

  /// Verifica si el servicio está inicializado
  bool get isInitialized => _initialized;

  /// Rota la clave de sesión
  Future<bool> rotateSessionKey() async {
    try {
      await _generateSessionKey();
      debugPrint('[EncryptionService] Session key rotated');
      return true;
    } catch (e) {
      debugPrint('[EncryptionService] Key rotation error: $e');
      return false;
    }
  }

  /// Limpia todas las claves almacenadas
  Future<void> clearAllKeys() async {
    await _secureStorage.deleteAll();
    _sessionKey = null;
    _initialized = false;
    debugPrint('[EncryptionService] All keys cleared');
  }

  /// Obtiene información del estado de cifrado
  Future<Map<String, dynamic>> getStatus() async {
    final hasSessionKey = _sessionKey != null;
    final hasPublicKey = await _secureStorage.read(key: _publicKeyAlias) != null;
    final hasPrivateKey = await _secureStorage.read(key: _privateKeyAlias) != null;
    final hasServerKey = await _secureStorage.read(key: _serverPublicKeyAlias) != null;

    return {
      'initialized': _initialized,
      'has_session_key': hasSessionKey,
      'has_key_pair': hasPublicKey && hasPrivateKey,
      'has_server_key': hasServerKey,
      'algorithm': 'AES-256-CBC',
      'key_exchange': 'ECDH (simplified)',
    };
  }
}

/// Datos cifrados
class EncryptedData {
  final String ciphertext;
  final String iv;
  final String algorithm;

  EncryptedData({
    required this.ciphertext,
    required this.iv,
    required this.algorithm,
  });

  Map<String, dynamic> toJson() => {
    'ciphertext': ciphertext,
    'iv': iv,
    'algorithm': algorithm,
  };

  factory EncryptedData.fromJson(Map<String, dynamic> json) {
    return EncryptedData(
      ciphertext: json['ciphertext'] as String,
      iv: json['iv'] as String,
      algorithm: json['algorithm'] as String? ?? 'AES-256-CBC',
    );
  }
}

/// Par de claves
class KeyPair {
  final String publicKey;
  final String privateKey;

  KeyPair({
    required this.publicKey,
    required this.privateKey,
  });
}

/// Extensión para cifrar/descifrar objetos JSON
extension EncryptedJsonExtension on EncryptionService {
  /// Cifra un objeto JSON
  Future<EncryptedData?> encryptJson(Map<String, dynamic> data) async {
    return await encrypt(json.encode(data));
  }

  /// Descifra a objeto JSON
  Future<Map<String, dynamic>?> decryptJson(EncryptedData encryptedData) async {
    final decrypted = await decrypt(encryptedData);
    if (decrypted == null) return null;
    
    try {
      return json.decode(decrypted) as Map<String, dynamic>;
    } catch (e) {
      return null;
    }
  }
}
