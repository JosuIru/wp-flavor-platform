import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';

/// Pantalla de detalle de chat escalado con conversación y respuestas
class EscalatedChatDetailScreen extends ConsumerStatefulWidget {
  final String sessionId;

  const EscalatedChatDetailScreen({
    super.key,
    required this.sessionId,
  });

  @override
  ConsumerState<EscalatedChatDetailScreen> createState() =>
      _EscalatedChatDetailScreenState();
}

class _EscalatedChatDetailScreenState
    extends ConsumerState<EscalatedChatDetailScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  EscalatedChatDetail? _chatDetail;
  bool _isLoading = true;
  String? _error;

  final TextEditingController _messageController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _isSending = false;

  @override
  void initState() {
    super.initState();
    _loadChatDetail();
  }

  @override
  void dispose() {
    _messageController.dispose();
    _notesController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadChatDetail() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getEscalatedChatDetail(widget.sessionId);

      if (response.success && response.data != null) {
        setState(() {
          _chatDetail = EscalatedChatDetail.fromJson(response.data!);
          _isLoading = false;
        });

        // Auto-scroll al final
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (_scrollController.hasClients) {
            _scrollController.animateTo(
              _scrollController.position.maxScrollExtent,
              duration: const Duration(milliseconds: 300),
              curve: Curves.easeOut,
            );
          }
        });
      } else {
        setState(() {
          _error = response.error ?? i18n.escalatedChatLoadError;
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = i18n.escalatedChatConnectionError(e.toString());
        _isLoading = false;
      });
    }
  }

  Future<void> _sendReply() async {
    final message = _messageController.text.trim();
    if (message.isEmpty) return;

    setState(() {
      _isSending = true;
    });

    try {
      final api = ref.read(apiClientProvider);
      final adminUser = ref.read(adminUserProvider);
      final response = await api.replyEscalatedChat(
        widget.sessionId,
        message,
        adminUser?.name ?? i18n.commonAdmin,
      );

      if (response.success) {
        _messageController.clear();
        // Recargar para ver el nuevo mensaje
        await _loadChatDetail();

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(i18n.escalatedChatReplySent)),
          );
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? i18n.escalatedChatSendError),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.escalatedChatError(e.toString())),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      setState(() {
        _isSending = false;
      });
    }
  }

  Future<void> _resolveChat() async {
    final notes = _notesController.text.trim();

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.escalatedChatResolveTitle),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(i18n.escalatedChatResolveConfirm),
            const SizedBox(height: 16),
            TextField(
              controller: _notesController,
              decoration: InputDecoration(
                labelText: i18n.escalatedChatResolveNotesLabel,
                hintText: i18n.escalatedChatResolveNotesHint,
                border: const OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.escalatedChatResolveAction),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.resolveEscalatedChat(
        widget.sessionId,
        notes: notes.isNotEmpty ? notes : null,
      );

      if (response.success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(i18n.escalatedChatResolvedToast)),
          );
          Navigator.pop(context, true); // Volver con resultado true
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? i18n.escalatedChatResolveError),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.escalatedChatError(e.toString())),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.escalatedChatTitle),
        actions: [
          if (_chatDetail?.escalation?.status != 'resolved')
            IconButton(
              onPressed: _resolveChat,
              icon: const Icon(Icons.check_circle),
              tooltip: i18n.escalatedChatResolveTooltip,
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.error_outline,
                        size: 48,
                        color: colorScheme.error,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _error!,
                        style: TextStyle(color: colorScheme.error),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      FilledButton.icon(
                        onPressed: _loadChatDetail,
                        icon: const Icon(Icons.refresh),
                        label: Text(i18n.commonRetry),
                      ),
                    ],
                  ),
                )
              : _chatDetail == null
                  ? Center(child: Text(i18n.escalatedChatNotFound))
                  : Column(
                      children: [
                        // Info del escalado
                        if (_chatDetail!.escalation != null)
                          Container(
                            padding: const EdgeInsets.all(16),
                            color: colorScheme.surfaceContainerHighest,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(
                                      Icons.warning_amber_rounded,
                                      color: Colors.orange,
                                      size: 20,
                                    ),
                                    const SizedBox(width: 8),
                                    Text(
                                      _chatDetail!.escalation!.reason,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 16,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  _chatDetail!.escalation!.summary,
                                  style: TextStyle(
                                    color: colorScheme.onSurfaceVariant,
                                    fontSize: 14,
                                  ),
                                ),
                              ],
                            ),
                          ),

                        // Draft de reserva (si existe)
                        if (_chatDetail!.draft != null)
                          Container(
                            margin: const EdgeInsets.all(16),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.blue.shade50,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.blue.shade200),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Icon(
                                      Icons.shopping_cart,
                                      size: 16,
                                      color: Colors.blue.shade700,
                                    ),
                                    const SizedBox(width: 8),
                                    Text(
                                      i18n.escalatedChatDraftTitle,
                                      style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        color: Colors.blue.shade700,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  i18n.escalatedChatDraftDate(
                                    _chatDetail!.draft!.date,
                                  ),
                                ),
                                Text(
                                  i18n.escalatedChatDraftTotal(
                                    _chatDetail!.draft!.total.toStringAsFixed(2),
                                  ),
                                ),
                              ],
                            ),
                          ),

                        const Divider(height: 1),

                        // Conversación
                        Expanded(
                          child: ListView.builder(
                            controller: _scrollController,
                            padding: const EdgeInsets.all(16),
                            itemCount: _chatDetail!.messages.length,
                            itemBuilder: (context, index) {
                              final message = _chatDetail!.messages[index];
                              return _MessageBubble(message: message);
                            },
                          ),
                        ),

                        const Divider(height: 1),

                        // Input de respuesta
                        if (_chatDetail!.escalation?.status != 'resolved')
                          Container(
                            padding: const EdgeInsets.all(16),
                            color: colorScheme.surfaceContainerHighest,
                            child: Row(
                              children: [
                                Expanded(
                                  child: TextField(
                                    controller: _messageController,
                                    decoration: InputDecoration(
                                      hintText: i18n.escalatedChatReplyHint,
                                      border: OutlineInputBorder(
                                        borderRadius: BorderRadius.circular(24),
                                      ),
                                      contentPadding: const EdgeInsets.symmetric(
                                        horizontal: 16,
                                        vertical: 12,
                                      ),
                                    ),
                                    maxLines: null,
                                    enabled: !_isSending,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                FilledButton(
                                  onPressed: _isSending ? null : _sendReply,
                                  style: FilledButton.styleFrom(
                                    shape: const CircleBorder(),
                                    padding: const EdgeInsets.all(16),
                                  ),
                                  child: _isSending
                                      ? const SizedBox(
                                          width: 20,
                                          height: 20,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2,
                                            color: Colors.white,
                                          ),
                                        )
                                      : const Icon(Icons.send),
                                ),
                              ],
                            ),
                          )
                        else
                          Container(
                            padding: const EdgeInsets.all(16),
                            color: Colors.green.shade50,
                            child: Row(
                              children: [
                                Icon(
                                  Icons.check_circle,
                                  color: Colors.green.shade700,
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    i18n.escalatedChatResolvedBanner,
                                    style: TextStyle(
                                      color: Colors.green.shade700,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
    );
  }
}

/// Burbuja de mensaje
class _MessageBubble extends StatelessWidget {
  final ChatMessage message;

  const _MessageBubble({required this.message});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final isAdminReply = message.content.startsWith('[') &&
        message.content.contains(']') &&
        !message.isUser;

    return Align(
      alignment: message.isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        decoration: BoxDecoration(
          color: message.isUser
              ? colorScheme.primaryContainer
              : isAdminReply
                  ? Colors.blue.shade100
                  : colorScheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (isAdminReply)
              Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Row(
                  children: [
                    Icon(
                      Icons.admin_panel_settings,
                      size: 14,
                      color: Colors.blue.shade700,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      message.content.substring(
                        message.content.indexOf('[') + 1,
                        message.content.indexOf(']'),
                      ),
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: Colors.blue.shade700,
                      ),
                    ),
                  ],
                ),
              ),
            Text(
              isAdminReply
                  ? message.content.substring(message.content.indexOf(']') + 2)
                  : message.content,
              style: TextStyle(
                color: message.isUser
                    ? colorScheme.onPrimaryContainer
                    : colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              _formatTime(message.timestamp),
              style: TextStyle(
                fontSize: 10,
                color: (message.isUser
                        ? colorScheme.onPrimaryContainer
                        : colorScheme.onSurface)
                    .withOpacity(0.6),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatTime(DateTime time) {
    return '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
  }
}
