import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter_webrtc/flutter_webrtc.dart';

/// Estado de la conexión WebRTC
enum WebRTCConnectionState {
  idle,
  connecting,
  connected,
  reconnecting,
  disconnected,
  failed,
}

/// Configuración de la llamada
class CallConfig {
  final bool enableVideo;
  final bool enableAudio;
  final String? stunServer;
  final String? turnServer;
  final String? turnUsername;
  final String? turnPassword;

  const CallConfig({
    this.enableVideo = true,
    this.enableAudio = true,
    this.stunServer,
    this.turnServer,
    this.turnUsername,
    this.turnPassword,
  });

  Map<String, dynamic> get iceServers {
    final servers = <Map<String, dynamic>>[];

    // STUN server
    servers.add({
      'urls': stunServer ?? 'stun:stun.l.google.com:19302',
    });

    // TURN server (si está configurado)
    if (turnServer != null) {
      servers.add({
        'urls': turnServer,
        'username': turnUsername ?? '',
        'credential': turnPassword ?? '',
      });
    }

    return {'iceServers': servers};
  }
}

/// Callback para eventos de llamada
typedef OnCallStateChanged = void Function(WebRTCConnectionState state);
typedef OnRemoteStream = void Function(MediaStream stream);
typedef OnLocalStream = void Function(MediaStream stream);

/// Servicio para manejar llamadas WebRTC
class WebRTCService {
  static WebRTCService? _instance;

  RTCPeerConnection? _peerConnection;
  MediaStream? _localStream;
  MediaStream? _remoteStream;

  RTCVideoRenderer localRenderer = RTCVideoRenderer();
  RTCVideoRenderer remoteRenderer = RTCVideoRenderer();

  WebRTCConnectionState _connectionState = WebRTCConnectionState.idle;
  CallConfig _config = const CallConfig();

  // Callbacks
  OnCallStateChanged? onStateChanged;
  OnRemoteStream? onRemoteStream;
  OnLocalStream? onLocalStream;

  // Stream controllers para señalización
  final _iceCandidateController = StreamController<RTCIceCandidate>.broadcast();
  final _offerController = StreamController<RTCSessionDescription>.broadcast();
  final _answerController = StreamController<RTCSessionDescription>.broadcast();

  Stream<RTCIceCandidate> get onIceCandidate => _iceCandidateController.stream;
  Stream<RTCSessionDescription> get onOffer => _offerController.stream;
  Stream<RTCSessionDescription> get onAnswer => _answerController.stream;

  WebRTCConnectionState get connectionState => _connectionState;
  bool get isConnected => _connectionState == WebRTCConnectionState.connected;
  MediaStream? get localStream => _localStream;
  MediaStream? get remoteStream => _remoteStream;

  WebRTCService._();

  factory WebRTCService() {
    _instance ??= WebRTCService._();
    return _instance!;
  }

  /// Inicializar renderers
  Future<void> initialize() async {
    await localRenderer.initialize();
    await remoteRenderer.initialize();
  }

  /// Configurar el servicio
  void configure(CallConfig config) {
    _config = config;
  }

  /// Iniciar una llamada saliente
  Future<RTCSessionDescription?> startCall({
    required bool isVideo,
    bool isAudio = true,
  }) async {
    try {
      _updateState(WebRTCConnectionState.connecting);

      // Obtener stream local
      await _getUserMedia(video: isVideo, audio: isAudio);

      // Crear peer connection
      await _createPeerConnection();

      // Añadir tracks locales
      _localStream?.getTracks().forEach((track) {
        _peerConnection?.addTrack(track, _localStream!);
      });

      // Crear offer
      final offer = await _peerConnection!.createOffer({
        'offerToReceiveVideo': isVideo,
        'offerToReceiveAudio': isAudio,
      });

      await _peerConnection!.setLocalDescription(offer);

      _offerController.add(offer);
      return offer;
    } catch (e) {
      debugPrint('[WebRTC] Error al iniciar llamada: $e');
      _updateState(WebRTCConnectionState.failed);
      return null;
    }
  }

  /// Responder a una llamada entrante
  Future<RTCSessionDescription?> answerCall({
    required RTCSessionDescription offer,
    required bool isVideo,
    bool isAudio = true,
  }) async {
    try {
      _updateState(WebRTCConnectionState.connecting);

      // Obtener stream local
      await _getUserMedia(video: isVideo, audio: isAudio);

      // Crear peer connection
      await _createPeerConnection();

      // Añadir tracks locales
      _localStream?.getTracks().forEach((track) {
        _peerConnection?.addTrack(track, _localStream!);
      });

      // Establecer remote description (offer)
      await _peerConnection!.setRemoteDescription(offer);

      // Crear answer
      final answer = await _peerConnection!.createAnswer();
      await _peerConnection!.setLocalDescription(answer);

      _answerController.add(answer);
      return answer;
    } catch (e) {
      debugPrint('[WebRTC] Error al responder llamada: $e');
      _updateState(WebRTCConnectionState.failed);
      return null;
    }
  }

  /// Establecer respuesta remota (para el que inició la llamada)
  Future<void> setRemoteAnswer(RTCSessionDescription answer) async {
    try {
      await _peerConnection?.setRemoteDescription(answer);
    } catch (e) {
      debugPrint('[WebRTC] Error al establecer respuesta: $e');
    }
  }

  /// Añadir ICE candidate remoto
  Future<void> addIceCandidate(RTCIceCandidate candidate) async {
    try {
      await _peerConnection?.addCandidate(candidate);
    } catch (e) {
      debugPrint('[WebRTC] Error al añadir ICE candidate: $e');
    }
  }

  /// Finalizar llamada
  Future<void> endCall() async {
    _updateState(WebRTCConnectionState.disconnected);

    // Detener tracks locales
    _localStream?.getTracks().forEach((track) {
      track.stop();
    });
    await _localStream?.dispose();
    _localStream = null;

    // Cerrar peer connection
    await _peerConnection?.close();
    _peerConnection = null;

    // Limpiar renderers
    localRenderer.srcObject = null;
    remoteRenderer.srcObject = null;

    _updateState(WebRTCConnectionState.idle);
  }

  /// Silenciar/activar micrófono
  void toggleMute(bool muted) {
    final audioTracks = _localStream?.getAudioTracks();
    if (audioTracks != null && audioTracks.isNotEmpty) {
      audioTracks.first.enabled = !muted;
    }
  }

  /// Habilitar/deshabilitar cámara
  void toggleVideo(bool enabled) {
    final videoTracks = _localStream?.getVideoTracks();
    if (videoTracks != null && videoTracks.isNotEmpty) {
      videoTracks.first.enabled = enabled;
    }
  }

  /// Cambiar cámara (frontal/trasera)
  Future<void> switchCamera() async {
    final videoTracks = _localStream?.getVideoTracks();
    if (videoTracks != null && videoTracks.isNotEmpty) {
      await Helper.switchCamera(videoTracks.first);
    }
  }

  /// Cambiar salida de audio (altavoz/auricular)
  Future<void> toggleSpeaker(bool speakerOn) async {
    final audioTracks = _localStream?.getAudioTracks();
    if (audioTracks != null && audioTracks.isNotEmpty) {
      await audioTracks.first.enableSpeakerphone(speakerOn);
    }
  }

  /// Obtener stream de cámara/micrófono
  Future<void> _getUserMedia({required bool video, required bool audio}) async {
    final constraints = <String, dynamic>{
      'audio': audio,
      'video': video
          ? {
              'facingMode': 'user',
              'width': {'ideal': 1280},
              'height': {'ideal': 720},
            }
          : false,
    };

    _localStream = await navigator.mediaDevices.getUserMedia(constraints);
    localRenderer.srcObject = _localStream;

    onLocalStream?.call(_localStream!);
  }

  /// Crear conexión peer
  Future<void> _createPeerConnection() async {
    _peerConnection = await createPeerConnection(_config.iceServers);

    // Manejar ICE candidates
    _peerConnection!.onIceCandidate = (candidate) {
      debugPrint('[WebRTC] ICE candidate: ${candidate.candidate}');
      _iceCandidateController.add(candidate);
    };

    // Manejar cambio de estado de conexión
    _peerConnection!.onConnectionState = (state) {
      debugPrint('[WebRTC] Connection state: $state');
      switch (state) {
        case RTCPeerConnectionState.RTCPeerConnectionStateConnected:
          _updateState(WebRTCConnectionState.connected);
          break;
        case RTCPeerConnectionState.RTCPeerConnectionStateDisconnected:
          _updateState(WebRTCConnectionState.reconnecting);
          break;
        case RTCPeerConnectionState.RTCPeerConnectionStateFailed:
          _updateState(WebRTCConnectionState.failed);
          break;
        case RTCPeerConnectionState.RTCPeerConnectionStateClosed:
          _updateState(WebRTCConnectionState.disconnected);
          break;
        default:
          break;
      }
    };

    // Manejar stream remoto
    _peerConnection!.onTrack = (event) {
      debugPrint('[WebRTC] Remote track received');
      if (event.streams.isNotEmpty) {
        _remoteStream = event.streams.first;
        remoteRenderer.srcObject = _remoteStream;
        onRemoteStream?.call(_remoteStream!);
      }
    };

    // Manejar renegociación
    _peerConnection!.onRenegotiationNeeded = () {
      debugPrint('[WebRTC] Renegotiation needed');
    };
  }

  void _updateState(WebRTCConnectionState state) {
    _connectionState = state;
    onStateChanged?.call(state);
  }

  /// Liberar recursos
  Future<void> dispose() async {
    await endCall();
    await localRenderer.dispose();
    await remoteRenderer.dispose();
    await _iceCandidateController.close();
    await _offerController.close();
    await _answerController.close();
  }
}
