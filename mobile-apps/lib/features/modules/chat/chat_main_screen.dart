import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/providers/providers.dart' show apiClientProvider, currentUserIdProvider;
import '../../../core/services/chat_service.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import 'services/chat_legacy_bridge.dart';
import 'widgets/message_bubble.dart';
import 'widgets/chat_input.dart';
import 'screens/group_info_screen.dart';
import 'screens/search_messages_screen.dart';
import 'screens/call_screen.dart';

part 'chat_main_screen_parts.dart';

/// Pantalla principal de conversación tipo Telegram/WhatsApp
class ChatMainScreen extends ConsumerStatefulWidget {
  final String conversationId;
  final String name;
  final String? avatarUrl;
  final bool isGroup;
  final int? legacyGroupId;
  final int? legacyInternalConversationId;
  final int? legacyOtherUserId;

  const ChatMainScreen({
    super.key,
    required this.conversationId,
    required this.name,
    this.avatarUrl,
    this.isGroup = false,
    this.legacyGroupId,
    this.legacyInternalConversationId,
    this.legacyOtherUserId,
  });

  @override
  ConsumerState<ChatMainScreen> createState() => _ChatMainScreenState();
}

class _ChatMainScreenState extends ConsumerState<ChatMainScreen> {
  final ScrollController _scrollController = ScrollController();
  final TextEditingController _textController = TextEditingController();
  final FocusNode _focusNode = FocusNode();
  final ImagePicker _imagePicker = ImagePicker();
  final ChatService _chatService = ChatService();

  List<ChatMessage> _messages = [];
  bool _isLoading = true;
  bool _isSending = false;
  bool _isTyping = false;
  bool _showAttachments = false;
  ChatMessage? _replyingTo;
  final Set<String> _selectedMessages = {};
  bool _isSelectionMode = false;

  String? get _currentUserId => _chatService.currentUser?.id;
  bool get _usesLegacyGroupApi => widget.legacyGroupId != null;
  bool get _usesLegacyInternalApi => widget.legacyInternalConversationId != null;
  bool get _usesLegacyApi => _usesLegacyGroupApi || _usesLegacyInternalApi;

  @override
  void initState() {
    super.initState();
    _initChat();
    _scrollController.addListener(_onScroll);
    _chatService.addListener(_onChatUpdate);
  }

  void _onChatUpdate() {
    _loadMessages();
  }

  Future<void> _initChat() async {
    await _loadMessages();
    if (!_usesLegacyApi) {
      _chatService.markAsRead(widget.conversationId);
    }
  }

  Future<void> _loadMessages({bool loadMore = false}) async {
    if (_isLoading && loadMore) return;

    setState(() => _isLoading = true);

    try {
      late final List<ChatMessage> newMessages;
      final apiClient = ref.read(apiClientProvider);

      if (_usesLegacyGroupApi) {
        newMessages = await ChatLegacyBridge.loadLegacyGroupMessages(
          apiClient,
          groupId: widget.legacyGroupId!,
          conversationId: widget.conversationId,
        );
      } else if (_usesLegacyInternalApi) {
        newMessages = await ChatLegacyBridge.loadLegacyInternalMessages(
          apiClient,
          conversationLegacyId: widget.legacyInternalConversationId!,
          conversationId: widget.conversationId,
          currentUserId: ref.read(currentUserIdProvider)?.toString(),
        );
      } else {
        final beforeId = loadMore && _messages.isNotEmpty ? _messages.first.id : null;
        newMessages = await _chatService.getMessages(
          widget.conversationId,
          beforeId: beforeId,
        );
      }

      setState(() {
        if (loadMore) {
          _messages.insertAll(0, newMessages);
        } else {
          _messages = newMessages;
        }
        _isLoading = false;
      });

      if (!loadMore) {
        _scrollToBottom();
      }
    } catch (e) {
      _showError('Error al cargar mensajes');
      setState(() => _isLoading = false);
    }
  }

  void _onScroll() {
    if (_scrollController.position.pixels <= 100) {
      _loadMessages(loadMore: true);
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  // === ENVIAR MENSAJES ===

  Future<void> _sendMessage() async {
    final text = _textController.text.trim();
    if (text.isEmpty || _isSending) return;

    setState(() => _isSending = true);
    _textController.clear();

    try {
      final apiClient = ref.read(apiClientProvider);
      if (_usesLegacyGroupApi) {
        await ChatLegacyBridge.sendLegacyGroupMessage(
          apiClient,
          groupId: widget.legacyGroupId!,
          text: text,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else if (_usesLegacyInternalApi) {
        await ChatLegacyBridge.sendLegacyInternalMessage(
          apiClient,
          conversationLegacyId: widget.legacyInternalConversationId!,
          text: text,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else {
        await _chatService.sendMessage(
          conversationId: widget.conversationId,
          content: text,
          replyToId: _replyingTo?.id,
        );
      }

      setState(() {
        _replyingTo = null;
      });
    } catch (e) {
      _showError('Error al enviar mensaje');
    } finally {
      setState(() => _isSending = false);
    }
  }

  Future<void> _sendImage() async {
    final picked = await _imagePicker.pickImage(source: ImageSource.gallery);
    if (picked == null) return;

    setState(() {
      _isSending = true;
      _showAttachments = false;
    });

    try {
      if (_usesLegacyGroupApi) {
        await ChatLegacyBridge.sendLegacyGroupImage(
          ref.read(apiClientProvider),
          groupId: widget.legacyGroupId!,
          image: picked,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else if (_usesLegacyInternalApi) {
        await ChatLegacyBridge.sendLegacyInternalImage(
          ref.read(apiClientProvider),
          conversationLegacyId: widget.legacyInternalConversationId!,
          image: picked,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else {
        await _chatService.sendMediaMessage(
          conversationId: widget.conversationId,
          file: File(picked.path),
          type: MessageType.image,
          replyToId: _replyingTo?.id,
        );
      }
      setState(() => _replyingTo = null);
    } catch (e) {
      _showError('Error al enviar imagen');
    } finally {
      setState(() => _isSending = false);
    }
  }

  Future<void> _sendCamera() async {
    final picked = await _imagePicker.pickImage(source: ImageSource.camera);
    if (picked == null) return;

    setState(() {
      _isSending = true;
      _showAttachments = false;
    });

    try {
      if (_usesLegacyGroupApi) {
        await ChatLegacyBridge.sendLegacyGroupImage(
          ref.read(apiClientProvider),
          groupId: widget.legacyGroupId!,
          image: picked,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else if (_usesLegacyInternalApi) {
        await ChatLegacyBridge.sendLegacyInternalImage(
          ref.read(apiClientProvider),
          conversationLegacyId: widget.legacyInternalConversationId!,
          image: picked,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else {
        await _chatService.sendMediaMessage(
          conversationId: widget.conversationId,
          file: File(picked.path),
          type: MessageType.image,
          replyToId: _replyingTo?.id,
        );
      }
      setState(() => _replyingTo = null);
    } catch (e) {
      _showError('Error al enviar foto');
    } finally {
      setState(() => _isSending = false);
    }
  }

  Future<void> _sendVideo() async {
    final picked = await _imagePicker.pickVideo(source: ImageSource.gallery);
    if (picked == null) return;

    setState(() {
      _isSending = true;
      _showAttachments = false;
    });

    try {
      if (_usesLegacyGroupApi) {
        await ChatLegacyBridge.sendLegacyGroupFile(
          ref.read(apiClientProvider),
          groupId: widget.legacyGroupId!,
          file: picked,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else if (_usesLegacyInternalApi) {
        await ChatLegacyBridge.sendLegacyInternalFile(
          ref.read(apiClientProvider),
          conversationLegacyId: widget.legacyInternalConversationId!,
          file: picked,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else {
        await _chatService.sendMediaMessage(
          conversationId: widget.conversationId,
          file: File(picked.path),
          type: MessageType.video,
          replyToId: _replyingTo?.id,
        );
      }
      setState(() => _replyingTo = null);
    } catch (e) {
      _showError('Error al enviar video');
    } finally {
      setState(() => _isSending = false);
    }
  }

  Future<void> _sendLocation() async {
    if (_usesLegacyGroupApi) {
      _showSnackBar('Ubicación no disponible en chat de grupos legacy');
      return;
    }
    if (!_usesLegacyInternalApi) {
      setState(() => _showAttachments = false);
      _showSnackBar('Función de ubicación próximamente');
      return;
    }

    setState(() {
      _isSending = true;
      _showAttachments = false;
    });

    try {
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        _showError('Permiso de ubicación no concedido');
        return;
      }

      final position = await Geolocator.getCurrentPosition();
      await ChatLegacyBridge.sendLegacyInternalLocation(
        ref.read(apiClientProvider),
        conversationLegacyId: widget.legacyInternalConversationId!,
        latitude: position.latitude,
        longitude: position.longitude,
        address: 'Ubicación actual',
        replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
      );
      await _loadMessages();
      setState(() => _replyingTo = null);
    } catch (e) {
      _showError('Error al enviar ubicación');
    } finally {
      setState(() => _isSending = false);
    }
  }

  Future<void> _sendFile() async {
    final result = await FilePicker.platform.pickFiles(
      allowMultiple: false,
      withData: false,
    );
    final picked = result?.files.single;
    final path = picked?.path;
    if (picked == null || path == null || path.isEmpty) return;

    setState(() {
      _isSending = true;
      _showAttachments = false;
    });

    try {
      if (_usesLegacyGroupApi) {
        await ChatLegacyBridge.sendLegacyGroupFile(
          ref.read(apiClientProvider),
          groupId: widget.legacyGroupId!,
          file: path,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else if (_usesLegacyInternalApi) {
        await ChatLegacyBridge.sendLegacyInternalFile(
          ref.read(apiClientProvider),
          conversationLegacyId: widget.legacyInternalConversationId!,
          file: path,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else {
        await _chatService.sendMediaMessage(
          conversationId: widget.conversationId,
          file: File(path),
          type: MessageType.file,
          replyToId: _replyingTo?.id,
        );
      }
      setState(() => _replyingTo = null);
    } catch (e) {
      _showError('Error al enviar archivo');
    } finally {
      setState(() => _isSending = false);
    }
  }

  Future<void> _sendVoiceMessage(File audioFile, Duration duration) async {
    setState(() => _isSending = true);

    try {
      if (_usesLegacyGroupApi) {
        await ChatLegacyBridge.sendLegacyGroupAudio(
          ref.read(apiClientProvider),
          groupId: widget.legacyGroupId!,
          audioPath: audioFile.path,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else if (_usesLegacyInternalApi) {
        await ChatLegacyBridge.sendLegacyInternalAudio(
          ref.read(apiClientProvider),
          conversationLegacyId: widget.legacyInternalConversationId!,
          audioPath: audioFile.path,
          replyToLegacyId: int.tryParse(_replyingTo?.id ?? ''),
        );
        await _loadMessages();
      } else {
        await _chatService.sendVoiceMessage(
          conversationId: widget.conversationId,
          audioFile: audioFile,
          duration: duration,
          replyToId: _replyingTo?.id,
        );
      }
      setState(() => _replyingTo = null);
    } catch (e) {
      _showError('Error al enviar audio');
    } finally {
      setState(() => _isSending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isOnline = !widget.isGroup && _chatService.isOnline(widget.conversationId);
    final isTypingRemote = _usesLegacyApi
        ? false
        : _chatService.isTyping(widget.conversationId);

    return Scaffold(
      appBar: _isSelectionMode
          ? _buildSelectionAppBar()
          : _buildMainAppBar(isOnline, isTypingRemote),
      body: Column(
        children: [
          // Mensaje fijado
          _buildPinnedMessage(),

          // Lista de mensajes
          Expanded(
            child: _isLoading && _messages.isEmpty
                ? const FlavorLoadingState()
                : ListView.builder(
                    controller: _scrollController,
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 16),
                    itemCount: _messages.length,
                    itemBuilder: (context, index) {
                      final message = _messages[index];
                      final isSelected = _selectedMessages.contains(message.id);
                      final showDate = index == 0 ||
                          !_isSameDay(
                            _messages[index - 1].timestamp,
                            message.timestamp,
                          );

                      return Column(
                        children: [
                          if (showDate)
                            _buildDateSeparator(message.timestamp),
                          MessageBubble(
                            message: message,
                            isMe: message.senderId == _currentUserId,
                            isSelected: isSelected,
                            onTap: () => _onMessageTap(message),
                            onLongPress: () => _onMessageLongPress(message),
                            onReplyTap: message.replyTo != null
                                ? () => _scrollToMessage(message.replyToId!)
                                : null,
                          ),
                        ],
                      );
                    },
                  ),
          ),

          // Barra de respuesta
          if (_replyingTo != null)
            _buildReplyBar(),

          // Selector de adjuntos
          if (_showAttachments)
            _buildAttachmentPicker(),

          if (_usesLegacyApi)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              color: Theme.of(context).colorScheme.surfaceContainerHighest,
              child: Text(
                _usesLegacyInternalApi
                    ? 'Chat legacy unificado: ahora admite texto, imágenes, audio, vídeo, archivos y ubicación.'
                    : 'Chat legacy unificado: ahora admite texto, imágenes, audio, vídeo y archivos. Ubicación sigue pendiente en grupos legacy.',
                style: TextStyle(
                  fontSize: 12,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ),

          // Campo de entrada
          ChatInput(
            controller: _textController,
            focusNode: _focusNode,
            isSending: _isSending,
            enableAttachments: true,
            enableCamera: true,
            enableVoiceRecording: true,
            onSend: _sendMessage,
            onTextChanged: _onTextChanged,
            onAttachmentTap: () =>
                setState(() => _showAttachments = !_showAttachments),
            onVoiceMessage: _sendVoiceMessage,
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _textController.dispose();
    _focusNode.dispose();
    _chatService.removeListener(_onChatUpdate);
    super.dispose();
  }
}
