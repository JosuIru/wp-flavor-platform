import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:wakelock_plus/wakelock_plus.dart';
import 'package:flutter_webrtc/flutter_webrtc.dart';
import '../../../../core/widgets/flavor_initials_avatar.dart';
import '../../../../core/widgets/flavor_state_widgets.dart';
import '../../../../core/services/chat_service.dart';
import '../../../../core/services/webrtc_service.dart';
import 'contact_picker_screen.dart';

/// Estado de la llamada
enum CallState {
  connecting,
  ringing,
  inCall,
  reconnecting,
  ended,
}

/// Pantalla de llamada de voz/video
class CallScreen extends ConsumerStatefulWidget {
  final String recipientId;
  final String recipientName;
  final String? recipientAvatar;
  final bool isVideo;
  final bool isIncoming;

  const CallScreen({
    super.key,
    required this.recipientId,
    required this.recipientName,
    this.recipientAvatar,
    this.isVideo = false,
    this.isIncoming = false,
  });

  @override
  ConsumerState<CallScreen> createState() => _CallScreenState();
}

class _CallScreenState extends ConsumerState<CallScreen>
    with SingleTickerProviderStateMixin {
  CallState _callState = CallState.connecting;
  Duration _callDuration = Duration.zero;
  Timer? _durationTimer;

  bool _isMuted = false;
  bool _isSpeakerOn = false;
  bool _isVideoEnabled = true;
  bool _isFrontCamera = true;
  bool _showControls = true;
  bool _showChatOverlay = false;

  // WebRTC
  final WebRTCService _webrtcService = WebRTCService();
  bool _webrtcInitialized = false;

  // Mensajes del chat durante la llamada
  final List<_CallChatMessage> _chatMessages = [];
  final TextEditingController _chatController = TextEditingController();

  late AnimationController _pulseController;
  late Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();

    // Mantener pantalla activa
    WakelockPlus.enable();

    // Ocultar barra de estado
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.immersiveSticky);

    // Animación de pulso para estado de llamada
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat(reverse: true);

    _pulseAnimation = Tween<double>(begin: 1.0, end: 1.2).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );

    // Inicializar WebRTC
    _initializeWebRTC();

    // Iniciar llamada
    _initializeCall();
  }

  Future<void> _initializeWebRTC() async {
    try {
      await _webrtcService.initialize();

      // Configurar callbacks
      _webrtcService.onStateChanged = (state) {
        if (mounted) {
          setState(() {
            switch (state) {
              case WebRTCConnectionState.connected:
                _callState = CallState.inCall;
                break;
              case WebRTCConnectionState.reconnecting:
                _callState = CallState.reconnecting;
                break;
              case WebRTCConnectionState.failed:
              case WebRTCConnectionState.disconnected:
                _endCall();
                break;
              default:
                break;
            }
          });
        }
      };

      setState(() => _webrtcInitialized = true);
    } catch (e) {
      debugPrint('[CallScreen] Error al inicializar WebRTC: $e');
    }
  }

  @override
  void dispose() {
    _durationTimer?.cancel();
    _pulseController.dispose();
    _chatController.dispose();
    _webrtcService.endCall();
    WakelockPlus.disable();
    SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
    super.dispose();
  }

  void _toggleChatOverlay() {
    setState(() => _showChatOverlay = !_showChatOverlay);
  }

  void _sendChatMessage() {
    final text = _chatController.text.trim();
    if (text.isEmpty) return;

    setState(() {
      _chatMessages.add(_CallChatMessage(
        text: text,
        isMe: true,
        timestamp: DateTime.now(),
      ));
    });
    _chatController.clear();
  }

  Future<void> _initializeCall() async {
    if (widget.isIncoming) {
      setState(() => _callState = CallState.ringing);
    } else {
      setState(() => _callState = CallState.connecting);

      // Simular conexión
      await Future.delayed(const Duration(seconds: 2));

      if (mounted) {
        setState(() => _callState = CallState.ringing);
      }

      // Simular respuesta
      await Future.delayed(const Duration(seconds: 3));

      if (mounted) {
        _startCall();
      }
    }
  }

  void _startCall() {
    setState(() => _callState = CallState.inCall);

    _durationTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() {
        _callDuration += const Duration(seconds: 1);
      });
    });
  }

  void _endCall() {
    _durationTimer?.cancel();
    setState(() => _callState = CallState.ended);

    Future.delayed(const Duration(milliseconds: 500), () {
      if (mounted) {
        Navigator.pop(context);
      }
    });
  }

  void _answerCall() {
    _startCall();
  }

  void _rejectCall() {
    _endCall();
  }

  void _toggleMute() {
    setState(() => _isMuted = !_isMuted);
    _webrtcService.toggleMute(_isMuted);
  }

  void _toggleSpeaker() async {
    setState(() => _isSpeakerOn = !_isSpeakerOn);
    await _webrtcService.toggleSpeaker(_isSpeakerOn);
  }

  void _toggleVideo() {
    setState(() => _isVideoEnabled = !_isVideoEnabled);
    _webrtcService.toggleVideo(_isVideoEnabled);
  }

  void _switchCamera() async {
    setState(() => _isFrontCamera = !_isFrontCamera);
    await _webrtcService.switchCamera();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onTap: widget.isVideo && _callState == CallState.inCall
            ? () => setState(() => _showControls = !_showControls)
            : null,
        child: Stack(
          children: [
            // Fondo de video o avatar
            _buildBackground(),

            // Video local (PiP)
            if (widget.isVideo && _callState == CallState.inCall && _isVideoEnabled)
              _buildLocalVideo(),

            // Información de llamada
            _buildCallInfo(),

            // Controles
            if (_showControls || _callState != CallState.inCall)
              _buildControls(),

            // Controles de llamada entrante
            if (_callState == CallState.ringing && widget.isIncoming)
              _buildIncomingControls(),

            // Chat overlay durante la llamada
            if (_showChatOverlay && _callState == CallState.inCall)
              _buildChatOverlay(),
          ],
        ),
      ),
    );
  }

  Widget _buildChatOverlay() {
    return Positioned(
      left: 16,
      right: 16,
      bottom: 160,
      child: Container(
        height: 300,
        decoration: BoxDecoration(
          color: Colors.black.withOpacity(0.85),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white24),
        ),
        child: Column(
          children: [
            // Encabezado
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: const BoxDecoration(
                border: Border(bottom: BorderSide(color: Colors.white24)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.chat, color: Colors.white70, size: 20),
                  const SizedBox(width: 8),
                  const Text(
                    'Chat',
                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  GestureDetector(
                    onTap: _toggleChatOverlay,
                    child: const Icon(Icons.close, color: Colors.white70, size: 20),
                  ),
                ],
              ),
            ),

            // Lista de mensajes
            Expanded(
              child: _chatMessages.isEmpty
                  ? const Center(
                      child: Text(
                        'Sin mensajes',
                        style: TextStyle(color: Colors.white54),
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(8),
                      itemCount: _chatMessages.length,
                      itemBuilder: (context, index) {
                        final message = _chatMessages[index];
                        return Align(
                          alignment: message.isMe
                              ? Alignment.centerRight
                              : Alignment.centerLeft,
                          child: Container(
                            margin: const EdgeInsets.symmetric(vertical: 4),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              color: message.isMe
                                  ? Theme.of(context).primaryColor
                                  : Colors.grey[800],
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              message.text,
                              style: const TextStyle(color: Colors.white),
                            ),
                          ),
                        );
                      },
                    ),
            ),

            // Campo de entrada
            Container(
              padding: const EdgeInsets.all(8),
              decoration: const BoxDecoration(
                border: Border(top: BorderSide(color: Colors.white24)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _chatController,
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        hintText: 'Escribe un mensaje...',
                        hintStyle: const TextStyle(color: Colors.white54),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(20),
                          borderSide: BorderSide.none,
                        ),
                        filled: true,
                        fillColor: Colors.white12,
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 8,
                        ),
                      ),
                      onSubmitted: (_) => _sendChatMessage(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  GestureDetector(
                    onTap: _sendChatMessage,
                    child: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Theme.of(context).primaryColor,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.send, color: Colors.white, size: 20),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBackground() {
    if (widget.isVideo && _callState == CallState.inCall) {
      // Video remoto (placeholder)
      return Container(
        color: Colors.grey[900],
        child: const Center(
          child: Text(
            'Video remoto',
            style: TextStyle(color: Colors.white54),
          ),
        ),
      );
    }

    // Fondo para llamada de voz
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            Colors.blueGrey[900]!,
            Colors.black,
          ],
        ),
      ),
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Avatar con animación
            ScaleTransition(
              scale: _callState == CallState.ringing || _callState == CallState.connecting
                  ? _pulseAnimation
                  : const AlwaysStoppedAnimation(1.0),
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                    color: _getStateColor(),
                    width: 3,
                  ),
                ),
                child: CircleAvatar(
                  radius: 60,
                  backgroundImage: widget.recipientAvatar != null
                      ? CachedNetworkImageProvider(widget.recipientAvatar!)
                      : null,
                  child: widget.recipientAvatar == null
                      ? Text(
                          FlavorInitialsAvatar.initialsFor(widget.recipientName),
                          style: const TextStyle(fontSize: 48),
                        )
                      : null,
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Nombre
            Text(
              widget.recipientName,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 28,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),

            // Estado o duración
            Text(
              _getStateText(),
              style: TextStyle(
                color: _getStateColor(),
                fontSize: 16,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLocalVideo() {
    return Positioned(
      top: MediaQuery.of(context).padding.top + 16,
      right: 16,
      child: GestureDetector(
        onTap: _switchCamera,
        child: Container(
          width: 100,
          height: 150,
          decoration: BoxDecoration(
            color: Colors.grey[800],
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.white24),
          ),
          child: Stack(
            children: [
              // Video local placeholder
              const Center(
                child: Icon(Icons.person, color: Colors.white54, size: 40),
              ),

              // Indicador de cámara
              Positioned(
                bottom: 8,
                right: 8,
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Colors.black54,
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Icon(
                    _isFrontCamera ? Icons.camera_front : Icons.camera_rear,
                    color: Colors.white,
                    size: 16,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCallInfo() {
    if (!widget.isVideo || _callState != CallState.inCall) {
      return const SizedBox.shrink();
    }

    return AnimatedOpacity(
      opacity: _showControls ? 1.0 : 0.0,
      duration: const Duration(milliseconds: 200),
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              CircleAvatar(
                radius: 20,
                backgroundImage: widget.recipientAvatar != null
                    ? CachedNetworkImageProvider(widget.recipientAvatar!)
                    : null,
                child: widget.recipientAvatar == null
                    ? Text(widget.recipientName[0])
                    : null,
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    widget.recipientName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    _formatDuration(_callDuration),
                    style: const TextStyle(color: Colors.white70),
                  ),
                ],
              ),
              const Spacer(),
              IconButton(
                icon: const Icon(Icons.flip_camera_ios, color: Colors.white),
                onPressed: _switchCamera,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildControls() {
    return Positioned(
      left: 0,
      right: 0,
      bottom: 0,
      child: AnimatedOpacity(
        opacity: _showControls || _callState != CallState.inCall ? 1.0 : 0.0,
        duration: const Duration(milliseconds: 200),
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Colors.transparent,
                Colors.black.withOpacity(0.8),
              ],
            ),
          ),
          child: SafeArea(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Controles secundarios
                if (_callState == CallState.inCall)
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      _buildControlButton(
                        icon: _isMuted ? Icons.mic_off : Icons.mic,
                        label: _isMuted ? 'Sin silencio' : 'Silenciar',
                        isActive: _isMuted,
                        onTap: _toggleMute,
                      ),
                      if (widget.isVideo)
                        _buildControlButton(
                          icon: _isVideoEnabled ? Icons.videocam : Icons.videocam_off,
                          label: _isVideoEnabled ? 'Cámara' : 'Sin cámara',
                          isActive: !_isVideoEnabled,
                          onTap: _toggleVideo,
                        ),
                      _buildControlButton(
                        icon: _isSpeakerOn ? Icons.volume_up : Icons.volume_down,
                        label: _isSpeakerOn ? 'Altavoz' : 'Auricular',
                        isActive: _isSpeakerOn,
                        onTap: _toggleSpeaker,
                      ),
                      _buildControlButton(
                        icon: Icons.chat,
                        label: 'Chat',
                        isActive: _showChatOverlay,
                        onTap: _toggleChatOverlay,
                      ),
                    ],
                  ),

                const SizedBox(height: 24),

                // Botón de colgar
                _buildEndCallButton(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildControlButton({
    required IconData icon,
    required String label,
    bool isActive = false,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: isActive ? Colors.white : Colors.white24,
              shape: BoxShape.circle,
            ),
            child: Icon(
              icon,
              color: isActive ? Colors.black : Colors.white,
              size: 28,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            style: const TextStyle(color: Colors.white70, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _buildEndCallButton() {
    return GestureDetector(
      onTap: _endCall,
      child: Container(
        width: 72,
        height: 72,
        decoration: const BoxDecoration(
          color: Colors.red,
          shape: BoxShape.circle,
        ),
        child: const Icon(
          Icons.call_end,
          color: Colors.white,
          size: 36,
        ),
      ),
    );
  }

  Widget _buildIncomingControls() {
    return Positioned(
      left: 0,
      right: 0,
      bottom: 48,
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 48),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              // Rechazar
              _buildIncomingButton(
                icon: Icons.call_end,
                label: 'Rechazar',
                color: Colors.red,
                onTap: _rejectCall,
              ),

              // Aceptar
              _buildIncomingButton(
                icon: widget.isVideo ? Icons.videocam : Icons.call,
                label: 'Aceptar',
                color: Colors.green,
                onTap: _answerCall,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildIncomingButton({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        ScaleTransition(
          scale: _pulseAnimation,
          child: GestureDetector(
            onTap: onTap,
            child: Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                color: color,
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(
                    color: color.withOpacity(0.4),
                    blurRadius: 12,
                    spreadRadius: 4,
                  ),
                ],
              ),
              child: Icon(icon, color: Colors.white, size: 36),
            ),
          ),
        ),
        const SizedBox(height: 12),
        Text(
          label,
          style: const TextStyle(color: Colors.white, fontSize: 14),
        ),
      ],
    );
  }

  Color _getStateColor() {
    switch (_callState) {
      case CallState.connecting:
        return Colors.amber;
      case CallState.ringing:
        return Colors.green;
      case CallState.inCall:
        return Colors.green;
      case CallState.reconnecting:
        return Colors.orange;
      case CallState.ended:
        return Colors.red;
    }
  }

  String _getStateText() {
    switch (_callState) {
      case CallState.connecting:
        return 'Conectando...';
      case CallState.ringing:
        return widget.isIncoming ? 'Llamada entrante...' : 'Llamando...';
      case CallState.inCall:
        return _formatDuration(_callDuration);
      case CallState.reconnecting:
        return 'Reconectando...';
      case CallState.ended:
        return 'Llamada finalizada';
    }
  }

  String _formatDuration(Duration duration) {
    final hours = duration.inHours;
    final minutes = (duration.inMinutes % 60).toString().padLeft(2, '0');
    final seconds = (duration.inSeconds % 60).toString().padLeft(2, '0');

    if (hours > 0) {
      return '$hours:$minutes:$seconds';
    }
    return '$minutes:$seconds';
  }
}

/// Pantalla de llamada grupal
class GroupCallScreen extends ConsumerStatefulWidget {
  final String groupId;
  final String groupName;
  final List<GroupMember> participants;
  final bool isVideo;

  const GroupCallScreen({
    super.key,
    required this.groupId,
    required this.groupName,
    required this.participants,
    this.isVideo = false,
  });

  @override
  ConsumerState<GroupCallScreen> createState() => _GroupCallScreenState();
}

class _GroupCallScreenState extends ConsumerState<GroupCallScreen> {
  Duration _callDuration = Duration.zero;
  Timer? _durationTimer;
  bool _isMuted = false;
  bool _isVideoEnabled = true;
  bool _isFrontCamera = true;

  final List<_ParticipantState> _participantStates = [];
  final WebRTCService _webrtcService = WebRTCService();

  @override
  void initState() {
    super.initState();
    WakelockPlus.enable();

    // Inicializar estados de participantes
    for (final participant in widget.participants) {
      _participantStates.add(_ParticipantState(
        member: participant,
        isConnected: false,
        isSpeaking: false,
      ));
    }

    // Simular conexión
    _simulateConnections();

    // Iniciar timer
    _durationTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() {
        _callDuration += const Duration(seconds: 1);
      });
    });
  }

  @override
  void dispose() {
    _durationTimer?.cancel();
    WakelockPlus.disable();
    super.dispose();
  }

  void _simulateConnections() async {
    for (int i = 0; i < _participantStates.length; i++) {
      await Future.delayed(Duration(milliseconds: 500 + (i * 300)));
      if (mounted) {
        setState(() {
          _participantStates[i].isConnected = true;
        });
      }
    }
  }

  Future<void> _addParticipant() async {
    // Obtener IDs de participantes actuales
    final currentParticipantIds = _participantStates.map((p) => p.member.id).toSet();

    final selectedContacts = await Navigator.push<List<ChatUser>>(
      context,
      MaterialPageRoute(
        builder: (_) => ContactPickerScreen(
          title: 'Añadir participante',
          multiSelect: true,
          excludeUserIds: currentParticipantIds.toList(),
        ),
      ),
    );

    if (selectedContacts != null && selectedContacts.isNotEmpty) {
      setState(() {
        for (final contact in selectedContacts) {
          _participantStates.add(_ParticipantState(
            member: GroupMember(
              id: contact.id,
              name: contact.name,
              avatarUrl: contact.avatarUrl,
            ),
            isConnected: false,
          ));
        }
      });

      // Simular conexión de nuevos participantes
      for (int i = _participantStates.length - selectedContacts.length;
          i < _participantStates.length;
          i++) {
        await Future.delayed(const Duration(milliseconds: 500));
        if (mounted) {
          setState(() {
            _participantStates[i].isConnected = true;
          });
        }
      }
    }
  }

  Future<void> _switchCamera() async {
    setState(() => _isFrontCamera = !_isFrontCamera);
    await _webrtcService.switchCamera();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.groupName),
            Text(
              '${_participantStates.where((p) => p.isConnected).length} participantes',
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.normal),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.person_add),
            onPressed: _addParticipant,
          ),
        ],
      ),
      body: Column(
        children: [
          // Grid de participantes
          Expanded(
            child: _buildParticipantsGrid(),
          ),

          // Duración
          Padding(
            padding: const EdgeInsets.all(8),
            child: Text(
              _formatDuration(_callDuration),
              style: const TextStyle(color: Colors.white70),
            ),
          ),

          // Controles
          _buildControls(),
        ],
      ),
    );
  }

  Widget _buildParticipantsGrid() {
    final connectedParticipants = _participantStates.where((p) => p.isConnected).toList();

    if (connectedParticipants.isEmpty) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            FlavorInlineSpinner(color: Colors.white),
            SizedBox(height: 16),
            Text(
              'Esperando participantes...',
              style: TextStyle(color: Colors.white70),
            ),
          ],
        ),
      );
    }

    // Determinar layout según número de participantes
    final crossAxisCount = connectedParticipants.length <= 2
        ? 1
        : connectedParticipants.length <= 4
            ? 2
            : 3;

    return GridView.builder(
      padding: const EdgeInsets.all(8),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        crossAxisSpacing: 8,
        mainAxisSpacing: 8,
        childAspectRatio: widget.isVideo ? 0.75 : 1,
      ),
      itemCount: connectedParticipants.length,
      itemBuilder: (context, index) {
        return _buildParticipantTile(connectedParticipants[index]);
      },
    );
  }

  Widget _buildParticipantTile(_ParticipantState state) {
    final member = state.member;

    return Container(
      decoration: BoxDecoration(
        color: Colors.grey[900],
        borderRadius: BorderRadius.circular(12),
        border: state.isSpeaking
            ? Border.all(color: Colors.green, width: 3)
            : null,
      ),
      child: Stack(
        children: [
          // Video o avatar
          Center(
            child: widget.isVideo
                ? const Icon(Icons.videocam_off, color: Colors.white54, size: 40)
                : CircleAvatar(
                    radius: 32,
                    backgroundImage: member.avatarUrl != null
                        ? CachedNetworkImageProvider(member.avatarUrl!)
                        : null,
                    child: member.avatarUrl == null
                        ? Text(FlavorInitialsAvatar.initialsFor(member.name))
                        : null,
                  ),
          ),

          // Nombre
          Positioned(
            left: 8,
            bottom: 8,
            right: 8,
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    member.name,
                    style: const TextStyle(color: Colors.white),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (state.isMuted)
                  const Icon(Icons.mic_off, color: Colors.red, size: 18),
              ],
            ),
          ),

          // Indicador de conexión
          if (!state.isConnected)
            Container(
              color: Colors.black54,
              child: const Center(
                child: FlavorInlineSpinner(color: Colors.white),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildControls() {
    return Container(
      padding: const EdgeInsets.all(24),
      child: SafeArea(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _buildControlButton(
              icon: _isMuted ? Icons.mic_off : Icons.mic,
              isActive: _isMuted,
              onTap: () => setState(() => _isMuted = !_isMuted),
            ),
            if (widget.isVideo)
              _buildControlButton(
                icon: _isVideoEnabled ? Icons.videocam : Icons.videocam_off,
                isActive: !_isVideoEnabled,
                onTap: () => setState(() => _isVideoEnabled = !_isVideoEnabled),
              ),
            _buildControlButton(
              icon: Icons.call_end,
              color: Colors.red,
              onTap: () => Navigator.pop(context),
            ),
            _buildControlButton(
              icon: Icons.flip_camera_ios,
              onTap: _switchCamera,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildControlButton({
    required IconData icon,
    bool isActive = false,
    Color? color,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 56,
        height: 56,
        decoration: BoxDecoration(
          color: color ?? (isActive ? Colors.white : Colors.white24),
          shape: BoxShape.circle,
        ),
        child: Icon(
          icon,
          color: color != null ? Colors.white : (isActive ? Colors.black : Colors.white),
          size: 28,
        ),
      ),
    );
  }

  String _formatDuration(Duration duration) {
    final hours = duration.inHours;
    final minutes = (duration.inMinutes % 60).toString().padLeft(2, '0');
    final seconds = (duration.inSeconds % 60).toString().padLeft(2, '0');

    if (hours > 0) {
      return '$hours:$minutes:$seconds';
    }
    return '$minutes:$seconds';
  }
}

class _ParticipantState {
  final GroupMember member;
  bool isConnected;
  bool isSpeaking;
  bool isMuted = false;

  _ParticipantState({
    required this.member,
    this.isConnected = false,
    this.isSpeaking = false,
  });
}

/// Mensaje de chat durante una llamada
class _CallChatMessage {
  final String text;
  final bool isMe;
  final DateTime timestamp;

  _CallChatMessage({
    required this.text,
    required this.isMe,
    required this.timestamp,
  });
}
