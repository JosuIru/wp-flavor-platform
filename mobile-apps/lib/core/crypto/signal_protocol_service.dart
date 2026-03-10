import 'dart:convert';
import 'dart:typed_data';
import 'package:cryptography/cryptography.dart';
import 'e2e_key_store.dart';
import 'e2e_api_client.dart';

/// Implementacion de Signal Protocol para Flutter
/// Incluye X3DH para establecimiento de sesion y Double Ratchet para mensajes
class SignalProtocolService {
  final E2EKeyStore _keyStore;
  final E2EApiClient _apiClient;
  final int _userId;

  // Constantes del protocolo
  static const int _maxSkip = 100;
  static const String _protocolInfo = 'FlavorE2E';

  SignalProtocolService({
    required E2EKeyStore keyStore,
    required E2EApiClient apiClient,
    required int userId,
  })  : _keyStore = keyStore,
        _apiClient = apiClient,
        _userId = userId;

  // ==========================================
  // INICIALIZACION Y REGISTRO DE CLAVES
  // ==========================================

  /// Inicializa el protocolo: genera claves si no existen y las registra
  Future<bool> initialize() async {
    await _keyStore.initialize();

    // Verificar si ya tenemos claves de identidad
    final existingKeys = await _keyStore.getIdentityKeyPair(_userId);

    if (existingKeys == null) {
      // Generar nuevas claves
      return await _generateAndRegisterKeys();
    }

    // Verificar que las claves estan registradas en el servidor
    final status = await _apiClient.getE2EStatus();
    if (status == null || status['has_keys'] != true) {
      return await _generateAndRegisterKeys();
    }

    return true;
  }

  /// Genera todas las claves necesarias y las registra en el servidor
  Future<bool> _generateAndRegisterKeys() async {
    try {
      final deviceId = await _keyStore.getOrCreateDeviceId();

      // 1. Generar par de claves de identidad (Curve25519)
      final identityKeyPair = await _generateX25519KeyPair();

      // 2. Generar signed prekey
      final signedPrekey = await _generateX25519KeyPair();
      final prekeyId = DateTime.now().millisecondsSinceEpoch ~/ 1000;

      // 3. Firmar el signed prekey con la identity key
      // Nota: Usamos Ed25519 para firmas, derivada de X25519
      final signature = await _signPrekey(
        identityKeyPair['privateKey']!,
        signedPrekey['publicKey']!,
      );

      // 4. Generar one-time prekeys
      final oneTimePrekeys = <Map<String, dynamic>>[];
      for (var i = 0; i < 20; i++) {
        final otpk = await _generateX25519KeyPair();
        oneTimePrekeys.add({
          'prekey_id': prekeyId + i + 1,
          'public_key': base64Encode(otpk['publicKey']!),
        });
      }

      // 5. Guardar claves localmente
      await _keyStore.saveIdentityKeyPair(
        userId: _userId,
        publicKey: identityKeyPair['publicKey']!,
        privateKey: identityKeyPair['privateKey']!,
      );

      await _keyStore.saveSignedPrekey(
        userId: _userId,
        prekeyId: prekeyId,
        publicKey: signedPrekey['publicKey']!,
        privateKey: signedPrekey['privateKey']!,
        signature: signature,
      );

      // 6. Registrar en el servidor
      final success = await _apiClient.registerKeys(
        deviceId: deviceId,
        identityKey: base64Encode(identityKeyPair['publicKey']!),
        signedPrekey: {
          'prekey_id': prekeyId,
          'public_key': base64Encode(signedPrekey['publicKey']!),
          'signature': base64Encode(signature),
        },
        oneTimePrekeys: oneTimePrekeys,
      );

      if (success) {
        // Registrar dispositivo
        await _apiClient.registerDevice(
          deviceId: deviceId,
          deviceName: 'Flutter App',
          deviceType: 'mobile',
        );
      }

      return success;
    } catch (e) {
      print('Error generando claves E2E: $e');
      return false;
    }
  }

  // ==========================================
  // CIFRADO DE MENSAJES
  // ==========================================

  /// Cifra un mensaje para un usuario
  Future<EncryptedMessage?> encryptMessage(int recipientId, String plaintext) async {
    try {
      // Verificar/crear sesion
      var session = await _keyStore.getSession(
        localUserId: _userId,
        remoteUserId: recipientId,
      );

      if (session == null) {
        // Crear nueva sesion con X3DH
        session = await _initializeSessionX3DH(recipientId);
        if (session == null) {
          print('No se pudo inicializar sesion E2E');
          return null;
        }
      }

      // Cifrar con Double Ratchet
      return await _encryptWithRatchet(session, recipientId, plaintext);
    } catch (e) {
      print('Error cifrando mensaje: $e');
      return null;
    }
  }

  /// Descifra un mensaje recibido
  Future<String?> decryptMessage(int senderId, EncryptedMessage encrypted) async {
    try {
      var session = await _keyStore.getSession(
        localUserId: _userId,
        remoteUserId: senderId,
      );

      if (session == null && encrypted.isPreKeyMessage) {
        // Mensaje inicial X3DH - crear sesion como receptor
        session = await _processX3DHMessage(senderId, encrypted);
        if (session == null) {
          print('No se pudo procesar mensaje X3DH');
          return null;
        }
      }

      if (session == null) {
        print('No hay sesion E2E con usuario $senderId');
        return null;
      }

      // Descifrar con Double Ratchet
      return await _decryptWithRatchet(session, senderId, encrypted);
    } catch (e) {
      print('Error descifrando mensaje: $e');
      return null;
    }
  }

  // ==========================================
  // X3DH - ESTABLECIMIENTO DE SESION
  // ==========================================

  /// Inicializa una sesion como iniciador usando X3DH
  Future<Map<String, dynamic>?> _initializeSessionX3DH(int recipientId) async {
    // Obtener prekey bundle del receptor
    final bundle = await _apiClient.getPrekeyBundle(recipientId);
    if (bundle == null) {
      print('No se pudo obtener prekey bundle de usuario $recipientId');
      return null;
    }

    // Obtener nuestras claves
    final identityKeyPair = await _keyStore.getIdentityKeyPair(_userId);
    if (identityKeyPair == null) return null;

    // Generar ephemeral key
    final ephemeralKeyPair = await _generateX25519KeyPair();

    // Extraer claves del bundle
    final remoteIdentityKey = base64Decode(bundle['identity_key'] as String);
    final remoteSignedPrekey = base64Decode(bundle['signed_prekey']['public_key'] as String);
    final remoteSignedPrekeyId = bundle['signed_prekey']['prekey_id'] as int;
    final remoteOneTimePrekey = bundle['one_time_prekey'] != null
        ? base64Decode(bundle['one_time_prekey']['public_key'] as String)
        : null;
    final remoteOneTimePrekeyId = bundle['one_time_prekey']?['prekey_id'] as int?;

    // Verificar firma del signed prekey
    // TODO: Implementar verificacion de firma

    // Calcular DH compartidos
    // DH1 = DH(IKa, SPKb)
    final dh1 = await _calculateDH(identityKeyPair['privateKey']!, remoteSignedPrekey);
    // DH2 = DH(EKa, IKb)
    final dh2 = await _calculateDH(ephemeralKeyPair['privateKey']!, remoteIdentityKey);
    // DH3 = DH(EKa, SPKb)
    final dh3 = await _calculateDH(ephemeralKeyPair['privateKey']!, remoteSignedPrekey);

    Uint8List? dh4;
    if (remoteOneTimePrekey != null) {
      // DH4 = DH(EKa, OPKb)
      dh4 = await _calculateDH(ephemeralKeyPair['privateKey']!, remoteOneTimePrekey);
    }

    // Derivar shared secret con HKDF
    final dhConcat = _concatenateBytes([dh1, dh2, dh3, if (dh4 != null) dh4]);
    final sharedSecret = await _hkdf(dhConcat, 64, info: _protocolInfo);

    // Inicializar estado Double Ratchet
    final rootKey = Uint8List.fromList(sharedSecret.sublist(0, 32));
    final chainKey = Uint8List.fromList(sharedSecret.sublist(32, 64));

    // Guardar identity key remota
    await _keyStore.saveRemoteIdentityKey(
      remoteUserId: recipientId,
      publicKey: remoteIdentityKey,
    );

    // Crear estado de sesion
    final session = {
      'rootKey': base64Encode(rootKey),
      'sendingChainKey': base64Encode(chainKey),
      'receivingChainKey': null,
      'sendingRatchetKey': base64Encode(ephemeralKeyPair['publicKey']!),
      'sendingRatchetPrivateKey': base64Encode(ephemeralKeyPair['privateKey']!),
      'receivingRatchetKey': base64Encode(remoteSignedPrekey),
      'sendingCounter': 0,
      'receivingCounter': 0,
      'previousCounter': 0,
      'isInitiator': true,
      // Datos para el header del primer mensaje
      'prekeyHeader': {
        'identityKey': base64Encode(identityKeyPair['publicKey']!),
        'ephemeralKey': base64Encode(ephemeralKeyPair['publicKey']!),
        'signedPrekeyId': remoteSignedPrekeyId,
        'oneTimePrekeyId': remoteOneTimePrekeyId,
      },
    };

    await _keyStore.saveSession(
      localUserId: _userId,
      remoteUserId: recipientId,
      sessionState: session,
    );

    return session;
  }

  /// Procesa un mensaje X3DH como receptor
  Future<Map<String, dynamic>?> _processX3DHMessage(
    int senderId,
    EncryptedMessage encrypted,
  ) async {
    if (encrypted.prekeyHeader == null) return null;

    final header = encrypted.prekeyHeader!;
    final remoteIdentityKey = base64Decode(header['identityKey'] as String);
    final remoteEphemeralKey = base64Decode(header['ephemeralKey'] as String);
    final signedPrekeyId = header['signedPrekeyId'] as int;

    // Obtener nuestras claves
    final identityKeyPair = await _keyStore.getIdentityKeyPair(_userId);
    final signedPrekey = await _keyStore.getSignedPrekey(_userId);
    if (identityKeyPair == null || signedPrekey == null) return null;

    // Verificar que el signed prekey ID coincide
    if (signedPrekey['prekeyId'] != signedPrekeyId) {
      print('Signed prekey ID no coincide');
      // TODO: Buscar en prekeys antiguas
      return null;
    }

    // Calcular DH compartidos (orden inverso al iniciador)
    // DH1 = DH(SPKb, IKa)
    final dh1 = await _calculateDH(
      signedPrekey['privateKey'] as Uint8List,
      remoteIdentityKey,
    );
    // DH2 = DH(IKb, EKa)
    final dh2 = await _calculateDH(identityKeyPair['privateKey']!, remoteEphemeralKey);
    // DH3 = DH(SPKb, EKa)
    final dh3 = await _calculateDH(
      signedPrekey['privateKey'] as Uint8List,
      remoteEphemeralKey,
    );

    // TODO: Manejar one-time prekey si existe
    Uint8List? dh4;

    // Derivar shared secret
    final dhConcat = _concatenateBytes([dh1, dh2, dh3, if (dh4 != null) dh4]);
    final sharedSecret = await _hkdf(dhConcat, 64, info: _protocolInfo);

    final rootKey = Uint8List.fromList(sharedSecret.sublist(0, 32));
    final chainKey = Uint8List.fromList(sharedSecret.sublist(32, 64));

    // Guardar identity key remota
    await _keyStore.saveRemoteIdentityKey(
      remoteUserId: senderId,
      publicKey: remoteIdentityKey,
    );

    // Crear estado de sesion como receptor
    final session = {
      'rootKey': base64Encode(rootKey),
      'sendingChainKey': null,
      'receivingChainKey': base64Encode(chainKey),
      'sendingRatchetKey': null,
      'sendingRatchetPrivateKey': null,
      'receivingRatchetKey': base64Encode(remoteEphemeralKey),
      'sendingCounter': 0,
      'receivingCounter': 0,
      'previousCounter': 0,
      'isInitiator': false,
    };

    await _keyStore.saveSession(
      localUserId: _userId,
      remoteUserId: senderId,
      sessionState: session,
    );

    return session;
  }

  // ==========================================
  // DOUBLE RATCHET
  // ==========================================

  /// Cifra un mensaje usando Double Ratchet
  Future<EncryptedMessage?> _encryptWithRatchet(
    Map<String, dynamic> session,
    int recipientId,
    String plaintext,
  ) async {
    // Verificar si necesitamos hacer DH ratchet
    if (session['sendingChainKey'] == null) {
      session = await _performDHRatchetSend(session, recipientId);
    }

    // Derivar message key de la chain key
    final chainKey = base64Decode(session['sendingChainKey'] as String);
    final messageKey = await _deriveMessageKey(chainKey);
    final newChainKey = await _deriveChainKey(chainKey);

    // Actualizar chain key
    session['sendingChainKey'] = base64Encode(newChainKey);
    final messageNumber = session['sendingCounter'] as int;
    session['sendingCounter'] = messageNumber + 1;

    // Cifrar mensaje
    final ciphertext = await _encryptAesGcm(
      Uint8List.fromList(utf8.encode(plaintext)),
      messageKey,
    );

    // Guardar sesion actualizada
    await _keyStore.saveSession(
      localUserId: _userId,
      remoteUserId: recipientId,
      sessionState: session,
    );

    // Construir mensaje cifrado
    final header = RatchetHeader(
      publicKey: base64Decode(session['sendingRatchetKey'] as String),
      messageNumber: messageNumber,
      previousCounter: session['previousCounter'] as int,
    );

    return EncryptedMessage(
      ciphertext: ciphertext,
      header: header,
      isPreKeyMessage: session['prekeyHeader'] != null,
      prekeyHeader: session['prekeyHeader'] as Map<String, dynamic>?,
    );
  }

  /// Descifra un mensaje usando Double Ratchet
  Future<String?> _decryptWithRatchet(
    Map<String, dynamic> session,
    int senderId,
    EncryptedMessage encrypted,
  ) async {
    final header = encrypted.header;

    // Verificar si es una clave saltada
    final skippedKey = await _keyStore.consumeSkippedMessageKey(
      localUserId: _userId,
      remoteUserId: senderId,
      ratchetKey: header.publicKey,
      messageNumber: header.messageNumber,
    );

    if (skippedKey != null) {
      return _decryptAesGcm(encrypted.ciphertext, skippedKey);
    }

    // Verificar si necesitamos hacer DH ratchet
    final currentReceivingKey = session['receivingRatchetKey'] != null
        ? base64Decode(session['receivingRatchetKey'] as String)
        : null;

    if (currentReceivingKey == null ||
        !_bytesEqual(currentReceivingKey, header.publicKey)) {
      // Nuevo ratchet key - saltar claves pendientes y hacer DH ratchet
      await _skipMessageKeys(session, senderId, header.previousCounter);
      session = await _performDHRatchetReceive(session, senderId, header.publicKey);
    }

    // Saltar hasta el mensaje actual
    await _skipMessageKeys(session, senderId, header.messageNumber);

    // Derivar message key
    final chainKey = base64Decode(session['receivingChainKey'] as String);
    final messageKey = await _deriveMessageKey(chainKey);
    final newChainKey = await _deriveChainKey(chainKey);

    session['receivingChainKey'] = base64Encode(newChainKey);
    session['receivingCounter'] = header.messageNumber + 1;

    await _keyStore.saveSession(
      localUserId: _userId,
      remoteUserId: senderId,
      sessionState: session,
    );

    return _decryptAesGcm(encrypted.ciphertext, messageKey);
  }

  /// Realiza DH ratchet para envio
  Future<Map<String, dynamic>> _performDHRatchetSend(
    Map<String, dynamic> session,
    int recipientId,
  ) async {
    // Generar nuevo par de claves DH
    final newKeyPair = await _generateX25519KeyPair();

    // Calcular nuevo DH compartido
    final receivingRatchetKey = base64Decode(session['receivingRatchetKey'] as String);
    final dhOutput = await _calculateDH(newKeyPair['privateKey']!, receivingRatchetKey);

    // Derivar nuevas root key y chain key
    final rootKey = base64Decode(session['rootKey'] as String);
    final kdfOutput = await _hkdfRatchet(rootKey, dhOutput);

    session['previousCounter'] = session['sendingCounter'];
    session['sendingCounter'] = 0;
    session['rootKey'] = base64Encode(kdfOutput['rootKey']!);
    session['sendingChainKey'] = base64Encode(kdfOutput['chainKey']!);
    session['sendingRatchetKey'] = base64Encode(newKeyPair['publicKey']!);
    session['sendingRatchetPrivateKey'] = base64Encode(newKeyPair['privateKey']!);
    session['prekeyHeader'] = null; // Ya no es mensaje inicial

    return session;
  }

  /// Realiza DH ratchet para recepcion
  Future<Map<String, dynamic>> _performDHRatchetReceive(
    Map<String, dynamic> session,
    int senderId,
    Uint8List newRemoteKey,
  ) async {
    // Calcular DH con nuestra clave de envio actual
    final sendingPrivateKey = session['sendingRatchetPrivateKey'] != null
        ? base64Decode(session['sendingRatchetPrivateKey'] as String)
        : null;

    if (sendingPrivateKey != null) {
      final dhOutput = await _calculateDH(sendingPrivateKey, newRemoteKey);
      final rootKey = base64Decode(session['rootKey'] as String);
      final kdfOutput = await _hkdfRatchet(rootKey, dhOutput);

      session['rootKey'] = base64Encode(kdfOutput['rootKey']!);
      session['receivingChainKey'] = base64Encode(kdfOutput['chainKey']!);
    }

    session['receivingRatchetKey'] = base64Encode(newRemoteKey);
    session['receivingCounter'] = 0;
    session['sendingChainKey'] = null; // Forzar DH ratchet en siguiente envio

    return session;
  }

  /// Salta message keys para mensajes fuera de orden
  Future<void> _skipMessageKeys(
    Map<String, dynamic> session,
    int remoteUserId,
    int until,
  ) async {
    final currentCounter = session['receivingCounter'] as int? ?? 0;
    if (until <= currentCounter) return;

    if (until - currentCounter > _maxSkip) {
      throw Exception('Demasiados mensajes saltados');
    }

    if (session['receivingChainKey'] == null) return;

    var chainKey = base64Decode(session['receivingChainKey'] as String);
    final ratchetKey = base64Decode(session['receivingRatchetKey'] as String);

    for (var i = currentCounter; i < until; i++) {
      final messageKey = await _deriveMessageKey(chainKey);
      await _keyStore.saveSkippedMessageKey(
        localUserId: _userId,
        remoteUserId: remoteUserId,
        ratchetKey: ratchetKey,
        messageNumber: i,
        messageKey: messageKey,
      );
      chainKey = await _deriveChainKey(chainKey);
    }

    session['receivingChainKey'] = base64Encode(chainKey);
    session['receivingCounter'] = until;
  }

  // ==========================================
  // FUNCIONES CRIPTOGRAFICAS
  // ==========================================

  /// Genera un par de claves X25519
  Future<Map<String, Uint8List>> _generateX25519KeyPair() async {
    final algorithm = X25519();
    final keyPair = await algorithm.newKeyPair();
    final publicKey = await keyPair.extractPublicKey();
    final privateKeyData = await keyPair.extractPrivateKeyBytes();

    return {
      'publicKey': Uint8List.fromList(publicKey.bytes),
      'privateKey': Uint8List.fromList(privateKeyData),
    };
  }

  /// Calcula DH compartido
  Future<Uint8List> _calculateDH(Uint8List privateKey, Uint8List publicKey) async {
    final algorithm = X25519();
    final keyPair = await algorithm.newKeyPairFromSeed(privateKey);
    final remotePublicKey = SimplePublicKey(publicKey, type: KeyPairType.x25519);
    final sharedSecret = await algorithm.sharedSecretKey(
      keyPair: keyPair,
      remotePublicKey: remotePublicKey,
    );
    return Uint8List.fromList(await sharedSecret.extractBytes());
  }

  /// HKDF para derivar claves
  Future<Uint8List> _hkdf(Uint8List input, int length, {String? info}) async {
    final algorithm = Hkdf(hmac: Hmac.sha256(), outputLength: length);
    final secretKey = SecretKey(input);
    final derivedKey = await algorithm.deriveKey(
      secretKey: secretKey,
      info: info != null ? utf8.encode(info) : [],
      nonce: Uint8List(32), // Salt vacio
    );
    return Uint8List.fromList(await derivedKey.extractBytes());
  }

  /// HKDF para ratchet (devuelve root key y chain key)
  Future<Map<String, Uint8List>> _hkdfRatchet(
    Uint8List rootKey,
    Uint8List dhOutput,
  ) async {
    final input = _concatenateBytes([rootKey, dhOutput]);
    final output = await _hkdf(input, 64, info: '$_protocolInfo-ratchet');
    return {
      'rootKey': Uint8List.fromList(output.sublist(0, 32)),
      'chainKey': Uint8List.fromList(output.sublist(32, 64)),
    };
  }

  /// Deriva message key de chain key
  Future<Uint8List> _deriveMessageKey(Uint8List chainKey) async {
    final hmac = Hmac.sha256();
    final mac = await hmac.calculateMac(
      [0x01],
      secretKey: SecretKey(chainKey),
    );
    return Uint8List.fromList(mac.bytes);
  }

  /// Deriva nueva chain key
  Future<Uint8List> _deriveChainKey(Uint8List chainKey) async {
    final hmac = Hmac.sha256();
    final mac = await hmac.calculateMac(
      [0x02],
      secretKey: SecretKey(chainKey),
    );
    return Uint8List.fromList(mac.bytes);
  }

  /// Firma un prekey con la identity key
  Future<Uint8List> _signPrekey(Uint8List identityPrivateKey, Uint8List prekeyPublic) async {
    // Usar Ed25519 para firma
    // Nota: En produccion deberiamos convertir X25519 a Ed25519 correctamente
    final algorithm = Ed25519();
    final keyPair = await algorithm.newKeyPairFromSeed(identityPrivateKey);
    final signature = await algorithm.sign(prekeyPublic, keyPair: keyPair);
    return Uint8List.fromList(signature.bytes);
  }

  /// Cifra con AES-GCM
  Future<Uint8List> _encryptAesGcm(Uint8List plaintext, Uint8List key) async {
    final algorithm = AesGcm.with256bits();
    final secretBox = await algorithm.encrypt(
      plaintext,
      secretKey: SecretKey(key),
    );
    // Concatenar: nonce (12) + ciphertext + mac (16)
    return _concatenateBytes([
      Uint8List.fromList(secretBox.nonce),
      Uint8List.fromList(secretBox.cipherText),
      Uint8List.fromList(secretBox.mac.bytes),
    ]);
  }

  /// Descifra con AES-GCM
  Future<String?> _decryptAesGcm(Uint8List combined, Uint8List key) async {
    try {
      final nonce = combined.sublist(0, 12);
      final ciphertext = combined.sublist(12, combined.length - 16);
      final mac = combined.sublist(combined.length - 16);

      final algorithm = AesGcm.with256bits();
      final secretBox = SecretBox(ciphertext, nonce: nonce, mac: Mac(mac));
      final decrypted = await algorithm.decrypt(
        secretBox,
        secretKey: SecretKey(key),
      );
      return utf8.decode(decrypted);
    } catch (e) {
      print('Error descifrando AES-GCM: $e');
      return null;
    }
  }

  /// Concatena multiples Uint8List
  Uint8List _concatenateBytes(List<Uint8List> arrays) {
    final totalLength = arrays.fold<int>(0, (sum, arr) => sum + arr.length);
    final result = Uint8List(totalLength);
    var offset = 0;
    for (final arr in arrays) {
      result.setRange(offset, offset + arr.length, arr);
      offset += arr.length;
    }
    return result;
  }

  /// Compara dos Uint8List
  bool _bytesEqual(Uint8List a, Uint8List b) {
    if (a.length != b.length) return false;
    for (var i = 0; i < a.length; i++) {
      if (a[i] != b[i]) return false;
    }
    return true;
  }

  // ==========================================
  // UTILIDADES PUBLICAS
  // ==========================================

  /// Verifica si hay sesion activa con un usuario
  Future<bool> hasSessionWith(int userId) async {
    return await _keyStore.hasSession(
      localUserId: _userId,
      remoteUserId: userId,
    );
  }

  /// Elimina la sesion con un usuario (para resetear E2E)
  Future<void> deleteSession(int userId) async {
    await _keyStore.deleteSession(
      localUserId: _userId,
      remoteUserId: userId,
    );
  }

  /// Obtiene el fingerprint de la identity key local
  Future<String?> getLocalFingerprint() async {
    final keyPair = await _keyStore.getIdentityKeyPair(_userId);
    if (keyPair == null) return null;
    return _generateFingerprint(keyPair['publicKey']!);
  }

  /// Obtiene el fingerprint de la identity key de un usuario remoto
  Future<String?> getRemoteFingerprint(int userId) async {
    final publicKey = await _keyStore.getRemoteIdentityKey(userId);
    if (publicKey == null) return null;
    return _generateFingerprint(publicKey);
  }

  /// Genera fingerprint legible de una clave publica
  String _generateFingerprint(Uint8List publicKey) {
    // Generar fingerprint de 60 digitos (grupos de 5)
    final hash = publicKey; // En produccion usar SHA-256
    final digits = <String>[];
    for (var i = 0; i < 12 && i < hash.length; i++) {
      digits.add(hash[i].toString().padLeft(5, '0').substring(0, 5));
    }
    return digits.join(' ');
  }
}

/// Mensaje cifrado con header para Double Ratchet
class EncryptedMessage {
  final Uint8List ciphertext;
  final RatchetHeader header;
  final bool isPreKeyMessage;
  final Map<String, dynamic>? prekeyHeader;

  EncryptedMessage({
    required this.ciphertext,
    required this.header,
    this.isPreKeyMessage = false,
    this.prekeyHeader,
  });

  /// Serializa para enviar por red
  Map<String, dynamic> toJson() => {
        'ciphertext': base64Encode(ciphertext),
        'header': header.toJson(),
        'isPreKeyMessage': isPreKeyMessage,
        if (prekeyHeader != null) 'prekeyHeader': prekeyHeader,
      };

  /// Deserializa desde red
  factory EncryptedMessage.fromJson(Map<String, dynamic> json) {
    return EncryptedMessage(
      ciphertext: base64Decode(json['ciphertext'] as String),
      header: RatchetHeader.fromJson(json['header'] as Map<String, dynamic>),
      isPreKeyMessage: json['isPreKeyMessage'] as bool? ?? false,
      prekeyHeader: json['prekeyHeader'] as Map<String, dynamic>?,
    );
  }
}

/// Header del Double Ratchet
class RatchetHeader {
  final Uint8List publicKey;
  final int messageNumber;
  final int previousCounter;

  RatchetHeader({
    required this.publicKey,
    required this.messageNumber,
    required this.previousCounter,
  });

  Map<String, dynamic> toJson() => {
        'publicKey': base64Encode(publicKey),
        'messageNumber': messageNumber,
        'previousCounter': previousCounter,
      };

  factory RatchetHeader.fromJson(Map<String, dynamic> json) {
    return RatchetHeader(
      publicKey: base64Decode(json['publicKey'] as String),
      messageNumber: json['messageNumber'] as int,
      previousCounter: json['previousCounter'] as int,
    );
  }
}
