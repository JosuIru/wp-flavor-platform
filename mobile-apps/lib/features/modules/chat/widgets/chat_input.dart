import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:record/record.dart';
import 'package:permission_handler/permission_handler.dart';

/// Widget de entrada de chat con soporte para texto y grabación de voz
class ChatInput extends StatefulWidget {
  final TextEditingController controller;
  final FocusNode focusNode;
  final bool isSending;
  final bool enableAttachments;
  final bool enableCamera;
  final bool enableVoiceRecording;
  final VoidCallback onSend;
  final ValueChanged<String> onTextChanged;
  final VoidCallback onAttachmentTap;
  final Function(File, Duration) onVoiceMessage;

  const ChatInput({
    super.key,
    required this.controller,
    required this.focusNode,
    required this.isSending,
    this.enableAttachments = true,
    this.enableCamera = true,
    this.enableVoiceRecording = true,
    required this.onSend,
    required this.onTextChanged,
    required this.onAttachmentTap,
    required this.onVoiceMessage,
  });

  @override
  State<ChatInput> createState() => _ChatInputState();
}

class _ChatInputState extends State<ChatInput> with SingleTickerProviderStateMixin {
  bool _isRecording = false;
  bool _isLocked = false;
  Duration _recordDuration = Duration.zero;
  Timer? _timer;
  final AudioRecorder _recorder = AudioRecorder();
  String? _recordingPath;
  double _dragOffset = 0;

  late AnimationController _animationController;
  late Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1000),
    )..repeat(reverse: true);

    _pulseAnimation = Tween<double>(begin: 1.0, end: 1.3).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    _recorder.dispose();
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _startRecording() async {
    // Verificar permisos
    final status = await Permission.microphone.request();
    if (!mounted) return;
    if (!status.isGranted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Se necesita permiso de micrófono')),
      );
      return;
    }

    // Obtener ruta temporal
    final tempDir = await getTemporaryDirectory();
    _recordingPath = '${tempDir.path}/voice_${DateTime.now().millisecondsSinceEpoch}.m4a';

    // Iniciar grabación
    await _recorder.start(
      const RecordConfig(encoder: AudioEncoder.aacLc),
      path: _recordingPath!,
    );

    setState(() {
      _isRecording = true;
      _recordDuration = Duration.zero;
    });

    // Timer para duración
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() {
        _recordDuration += const Duration(seconds: 1);
      });
    });
  }

  Future<void> _stopRecording({bool send = true}) async {
    _timer?.cancel();

    final path = await _recorder.stop();

    setState(() {
      _isRecording = false;
      _isLocked = false;
      _dragOffset = 0;
    });

    if (send && path != null && _recordDuration.inSeconds > 0) {
      widget.onVoiceMessage(File(path), _recordDuration);
    } else if (path != null) {
      // Cancelado, eliminar archivo
      final file = File(path);
      if (await file.exists()) {
        await file.delete();
      }
    }
  }

  void _cancelRecording() {
    _stopRecording(send: false);
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final hasText = widget.controller.text.trim().isNotEmpty;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 4,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        child: _isRecording ? _buildRecordingUI(colorScheme) : _buildInputUI(colorScheme, hasText),
      ),
    );
  }

  Widget _buildInputUI(ColorScheme colorScheme, bool hasText) {
    return Row(
      children: [
        // Botón de adjuntos
        IconButton(
          icon: const Icon(Icons.attach_file),
          onPressed: widget.enableAttachments ? widget.onAttachmentTap : null,
          color: colorScheme.onSurfaceVariant,
          tooltip: widget.enableAttachments ? 'Adjuntar' : 'No disponible aquí',
        ),

        // Campo de texto
        Expanded(
          child: Container(
            decoration: BoxDecoration(
              color: colorScheme.surfaceContainerHighest,
              borderRadius: BorderRadius.circular(24),
            ),
            child: Row(
              children: [
                // Emoji
                IconButton(
                  icon: const Icon(Icons.emoji_emotions_outlined),
                  onPressed: () {
                    // TODO: Mostrar selector de emojis
                  },
                  iconSize: 22,
                  color: colorScheme.onSurfaceVariant,
                ),

                // Input
                Expanded(
                  child: TextField(
                    controller: widget.controller,
                    focusNode: widget.focusNode,
                    onChanged: widget.onTextChanged,
                    textCapitalization: TextCapitalization.sentences,
                    maxLines: 5,
                    minLines: 1,
                    decoration: const InputDecoration(
                      hintText: 'Mensaje',
                      border: InputBorder.none,
                      contentPadding: EdgeInsets.symmetric(vertical: 10),
                    ),
                    textInputAction: TextInputAction.newline,
                  ),
                ),

                // Cámara (cuando no hay texto)
                if (!hasText)
                  IconButton(
                    icon: const Icon(Icons.camera_alt_outlined),
                    onPressed: widget.enableCamera
                        ? () {
                      // TODO: Abrir cámara
                    }
                        : null,
                    iconSize: 22,
                    color: colorScheme.onSurfaceVariant,
                    tooltip:
                        widget.enableCamera ? 'Cámara' : 'No disponible aquí',
                  ),
              ],
            ),
          ),
        ),

        const SizedBox(width: 8),

        // Botón de enviar o grabar
        GestureDetector(
          onLongPressStart: hasText || !widget.enableVoiceRecording
              ? null
              : (_) => _startRecording(),
          onLongPressEnd: hasText || !widget.enableVoiceRecording
              ? null
              : (_) => _stopRecording(),
          onLongPressMoveUpdate: hasText || !widget.enableVoiceRecording
              ? null
              : (details) {
                  setState(() {
                    _dragOffset = details.localOffsetFromOrigin.dx;
                    if (_dragOffset < -100) {
                      _isLocked = true;
                    }
                  });
                },
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: colorScheme.primary,
              shape: BoxShape.circle,
            ),
            child: IconButton(
              icon: Icon(
                hasText ? Icons.send : Icons.mic,
                color: colorScheme.onPrimary,
              ),
              onPressed: hasText
                  ? widget.isSending
                      ? null
                      : widget.onSend
                  : widget.enableVoiceRecording
                      ? null
                      : () {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text(
                                'Notas de voz no disponibles en este chat',
                              ),
                            ),
                          );
                        },
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildRecordingUI(ColorScheme colorScheme) {
    return Row(
      children: [
        // Cancelar
        if (!_isLocked)
          GestureDetector(
            onTap: _cancelRecording,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  const Icon(Icons.delete, color: Colors.red),
                  const SizedBox(width: 8),
                  Text(
                    '< Desliza para cancelar',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ],
              ),
            ),
          ),

        // Indicador de grabación
        Expanded(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Icono pulsante
              ScaleTransition(
                scale: _pulseAnimation,
                child: Container(
                  width: 12,
                  height: 12,
                  decoration: const BoxDecoration(
                    color: Colors.red,
                    shape: BoxShape.circle,
                  ),
                ),
              ),
              const SizedBox(width: 12),

              // Duración
              Text(
                _formatDuration(_recordDuration),
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),

        // Botón para enviar (si está bloqueado) o indicador
        if (_isLocked)
          Row(
            children: [
              IconButton(
                icon: const Icon(Icons.close, color: Colors.red),
                onPressed: _cancelRecording,
              ),
              GestureDetector(
                onTap: () => _stopRecording(send: true),
                child: Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: colorScheme.primary,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.send, color: colorScheme.onPrimary),
                ),
              ),
            ],
          )
        else
          AnimatedBuilder(
            animation: _pulseAnimation,
            builder: (context, child) {
              return Transform.translate(
                offset: Offset(_dragOffset, 0),
                child: Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: colorScheme.primary,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.mic, color: colorScheme.onPrimary),
                ),
              );
            },
          ),
      ],
    );
  }

  String _formatDuration(Duration duration) {
    final minutes = duration.inMinutes.toString().padLeft(2, '0');
    final seconds = (duration.inSeconds % 60).toString().padLeft(2, '0');
    return '$minutes:$seconds';
  }
}

/// Widget selector de adjuntos
class AttachmentPicker extends StatelessWidget {
  final VoidCallback onImageTap;
  final VoidCallback onCameraTap;
  final VoidCallback onVideoTap;
  final VoidCallback onFileTap;
  final VoidCallback onLocationTap;
  final VoidCallback onClose;

  const AttachmentPicker({
    super.key,
    required this.onImageTap,
    required this.onCameraTap,
    required this.onVideoTap,
    required this.onFileTap,
    required this.onLocationTap,
    required this.onClose,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Handle
          Container(
            width: 40,
            height: 4,
            margin: const EdgeInsets.only(bottom: 16),
            decoration: BoxDecoration(
              color: Colors.grey[300],
              borderRadius: BorderRadius.circular(2),
            ),
          ),

          // Grid de opciones
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _AttachmentOption(
                icon: Icons.photo,
                label: 'Galería',
                color: Colors.purple,
                onTap: onImageTap,
              ),
              _AttachmentOption(
                icon: Icons.camera_alt,
                label: 'Cámara',
                color: Colors.red,
                onTap: onCameraTap,
              ),
              _AttachmentOption(
                icon: Icons.videocam,
                label: 'Video',
                color: Colors.pink,
                onTap: onVideoTap,
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _AttachmentOption(
                icon: Icons.insert_drive_file,
                label: 'Archivo',
                color: Colors.indigo,
                onTap: onFileTap,
              ),
              _AttachmentOption(
                icon: Icons.location_on,
                label: 'Ubicación',
                color: Colors.green,
                onTap: onLocationTap,
              ),
              _AttachmentOption(
                icon: Icons.person,
                label: 'Contacto',
                color: Colors.blue,
                onTap: () {
                  // TODO: Selector de contactos
                },
              ),
            ],
          ),

          const SizedBox(height: 16),

          // Botón cerrar
          TextButton(
            onPressed: onClose,
            child: const Text('Cancelar'),
          ),
        ],
      ),
    );
  }
}

class _AttachmentOption extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _AttachmentOption({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: Colors.white, size: 26),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[700],
            ),
          ),
        ],
      ),
    );
  }
}
