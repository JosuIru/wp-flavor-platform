import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../api/api_client.dart';
import '../providers/providers.dart' show apiClientProvider;
import 'e2e_key_store.dart';
import 'e2e_api_client.dart';
import 'signal_protocol_service.dart';

/// Estado del servicio E2E
class E2EState {
  final bool isInitialized;
  final bool isEnabled;
  final bool hasKeys;
  final String? deviceId;
  final String? error;

  const E2EState({
    this.isInitialized = false,
    this.isEnabled = false,
    this.hasKeys = false,
    this.deviceId,
    this.error,
  });

  E2EState copyWith({
    bool? isInitialized,
    bool? isEnabled,
    bool? hasKeys,
    String? deviceId,
    String? error,
  }) {
    return E2EState(
      isInitialized: isInitialized ?? this.isInitialized,
      isEnabled: isEnabled ?? this.isEnabled,
      hasKeys: hasKeys ?? this.hasKeys,
      deviceId: deviceId ?? this.deviceId,
      error: error,
    );
  }
}

/// Provider del key store E2E
final e2eKeyStoreProvider = Provider<E2EKeyStore>((ref) {
  return E2EKeyStore();
});

/// Provider del cliente API E2E
final e2eApiClientProvider = Provider<E2EApiClient>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return E2EApiClient(dio: apiClient.dio);
});

/// Provider del servicio Signal Protocol
final signalProtocolProvider = Provider.family<SignalProtocolService?, int>((ref, userId) {
  if (userId <= 0) return null;

  final keyStore = ref.watch(e2eKeyStoreProvider);
  final apiClient = ref.watch(e2eApiClientProvider);

  return SignalProtocolService(
    keyStore: keyStore,
    apiClient: apiClient,
    userId: userId,
  );
});

/// Notifier para el estado E2E
class E2ENotifier extends StateNotifier<E2EState> {
  final E2EKeyStore _keyStore;
  final E2EApiClient _apiClient;
  final int _userId;
  SignalProtocolService? _signalService;

  E2ENotifier({
    required E2EKeyStore keyStore,
    required E2EApiClient apiClient,
    required int userId,
  })  : _keyStore = keyStore,
        _apiClient = apiClient,
        _userId = userId,
        super(const E2EState());

  /// Inicializa el servicio E2E
  Future<bool> initialize() async {
    if (_userId <= 0) {
      state = state.copyWith(
        isInitialized: false,
        error: 'Usuario no autenticado',
      );
      return false;
    }

    try {
      // Verificar si E2E esta habilitado en el servidor
      final status = await _apiClient.getE2EStatus();

      if (status == null) {
        state = state.copyWith(
          isInitialized: true,
          isEnabled: false,
          error: 'No se pudo verificar estado E2E',
        );
        return false;
      }

      final isEnabled = status['e2e_enabled'] == true;

      if (!isEnabled) {
        state = state.copyWith(
          isInitialized: true,
          isEnabled: false,
        );
        return true;
      }

      // Inicializar servicio Signal Protocol
      _signalService = SignalProtocolService(
        keyStore: _keyStore,
        apiClient: _apiClient,
        userId: _userId,
      );

      final success = await _signalService!.initialize();
      final deviceId = await _keyStore.getOrCreateDeviceId();

      state = state.copyWith(
        isInitialized: true,
        isEnabled: true,
        hasKeys: success,
        deviceId: deviceId,
        error: success ? null : 'Error inicializando claves E2E',
      );

      return success;
    } catch (e) {
      state = state.copyWith(
        isInitialized: true,
        isEnabled: false,
        error: 'Error: $e',
      );
      return false;
    }
  }

  /// Obtiene el servicio Signal Protocol
  SignalProtocolService? get signalService => _signalService;

  /// Cifra un mensaje para un usuario
  Future<EncryptedMessage?> encryptMessage(int recipientId, String plaintext) async {
    if (_signalService == null || !state.isEnabled) {
      return null;
    }
    return await _signalService!.encryptMessage(recipientId, plaintext);
  }

  /// Descifra un mensaje de un usuario
  Future<String?> decryptMessage(int senderId, EncryptedMessage encrypted) async {
    if (_signalService == null || !state.isEnabled) {
      return null;
    }
    return await _signalService!.decryptMessage(senderId, encrypted);
  }

  /// Descifra un mensaje desde JSON (formato de la API)
  Future<String?> decryptMessageFromJson(int senderId, Map<String, dynamic> encryptedJson) async {
    if (_signalService == null || !state.isEnabled) {
      return null;
    }

    try {
      final encrypted = EncryptedMessage.fromJson(encryptedJson);
      return await _signalService!.decryptMessage(senderId, encrypted);
    } catch (e) {
      print('Error parseando mensaje cifrado: $e');
      return null;
    }
  }

  /// Verifica si hay sesion activa con un usuario
  Future<bool> hasSessionWith(int userId) async {
    if (_signalService == null) return false;
    return await _signalService!.hasSessionWith(userId);
  }

  /// Obtiene el fingerprint local para verificacion
  Future<String?> getLocalFingerprint() async {
    if (_signalService == null) return null;
    return await _signalService!.getLocalFingerprint();
  }

  /// Obtiene el fingerprint de un usuario remoto
  Future<String?> getRemoteFingerprint(int userId) async {
    if (_signalService == null) return null;
    return await _signalService!.getRemoteFingerprint(userId);
  }

  /// Reinicia E2E (limpia claves y regenera)
  Future<bool> reset() async {
    await _keyStore.clearAll();
    state = const E2EState();
    return await initialize();
  }
}

/// Provider del notifier E2E
final e2eNotifierProvider = StateNotifierProvider.family<E2ENotifier, E2EState, int>(
  (ref, userId) {
    final keyStore = ref.watch(e2eKeyStoreProvider);
    final apiClient = ref.watch(e2eApiClientProvider);

    return E2ENotifier(
      keyStore: keyStore,
      apiClient: apiClient,
      userId: userId,
    );
  },
);

// Nota: ApiClient ahora expone Dio directamente via getter .dio
