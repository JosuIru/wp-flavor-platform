part of 'chat_main_screen.dart';

extension _ChatMainScreenParts on _ChatMainScreenState {
  void _onMessageTap(ChatMessage message) {
    if (_isSelectionMode) {
      _toggleMessageSelection(message.id);
    }
  }

  void _onMessageLongPress(ChatMessage message) {
    if (!_isSelectionMode) {
      // ignore: invalid_use_of_protected_member
      setState(() {
        _isSelectionMode = true;
        _selectedMessages.add(message.id);
      });
    } else {
      _showMessageOptions(message);
    }
  }

  void _toggleMessageSelection(String messageId) {
    // ignore: invalid_use_of_protected_member
    setState(() {
      if (_selectedMessages.contains(messageId)) {
        _selectedMessages.remove(messageId);
        if (_selectedMessages.isEmpty) {
          _isSelectionMode = false;
        }
      } else {
        _selectedMessages.add(messageId);
      }
    });
  }

  void _clearSelection() {
    // ignore: invalid_use_of_protected_member
    setState(() {
      _selectedMessages.clear();
      _isSelectionMode = false;
    });
  }

  void _showMessageOptions(ChatMessage message) {
    showModalBottomSheet(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.reply),
              title: const Text('Responder'),
              onTap: () {
                Navigator.pop(context);
                // ignore: invalid_use_of_protected_member
                setState(() => _replyingTo = message);
                _focusNode.requestFocus();
              },
            ),
            ListTile(
              leading: const Icon(Icons.copy),
              title: const Text('Copiar'),
              onTap: () {
                Navigator.pop(context);
                Clipboard.setData(ClipboardData(text: message.content));
                _showSnackBar('Mensaje copiado');
              },
            ),
            ListTile(
              leading: const Icon(Icons.forward),
              title: const Text('Reenviar'),
              onTap: () {
                Navigator.pop(context);
                _showForwardDialog([message.id]);
              },
            ),
            if (message.senderId == _currentUserId)
              ListTile(
                leading: const Icon(Icons.delete, color: Colors.red),
                title: const Text('Eliminar', style: TextStyle(color: Colors.red)),
                onTap: () {
                  Navigator.pop(context);
                  _showDeleteDialog(message);
                },
              ),
            ListTile(
              leading: const Icon(Icons.push_pin),
              title: Text(message.isPinned ? 'Desfijar' : 'Fijar mensaje'),
              onTap: () {
                Navigator.pop(context);
                _chatService.pinMessage(widget.conversationId, message.id);
              },
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: ['👍', '❤️', '😂', '😮', '😢', '🙏'].map((emoji) {
                  return GestureDetector(
                    onTap: () {
                      Navigator.pop(context);
                      _chatService.addReaction(widget.conversationId, message.id, emoji);
                    },
                    child: Text(emoji, style: const TextStyle(fontSize: 28)),
                  );
                }).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showDeleteDialog(ChatMessage message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar mensaje'),
        content: const Text('¿Cómo quieres eliminar este mensaje?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _chatService.deleteMessage(widget.conversationId, message.id);
            },
            child: const Text('Solo para mí'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              _chatService.deleteMessage(widget.conversationId, message.id, forEveryone: true);
            },
            child: const Text('Para todos', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  void _showForwardDialog(List<String> messageIds) {
    _showSnackBar('Reenviar próximamente');
  }

  void _onTextChanged(String text) {
    if (text.isNotEmpty && !_isTyping) {
      // ignore: invalid_use_of_protected_member
      setState(() => _isTyping = true);
      _chatService.sendTypingIndicator(widget.conversationId);
    } else if (text.isEmpty && _isTyping) {
      // ignore: invalid_use_of_protected_member
      setState(() => _isTyping = false);
    }
  }

  void _showError(String message) {
    FlavorSnackbar.showError(context, message);
  }

  void _showSnackBar(String message) {
    FlavorSnackbar.showInfo(context, message);
  }

  void _startVoiceCall() {
    if (_usesLegacyApi && widget.legacyOtherUserId == null) {
      _showSnackBar('Llamadas no disponibles en esta conversación');
      return;
    }
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => CallScreen(
          recipientId: widget.conversationId,
          recipientName: widget.name,
          recipientAvatar: widget.avatarUrl,
          isVideo: false,
        ),
      ),
    );
  }

  void _startVideoCall() {
    if (_usesLegacyApi && widget.legacyOtherUserId == null) {
      _showSnackBar('Videollamadas no disponibles en esta conversación');
      return;
    }
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => CallScreen(
          recipientId: widget.conversationId,
          recipientName: widget.name,
          recipientAvatar: widget.avatarUrl,
          isVideo: true,
        ),
      ),
    );
  }

  PreferredSizeWidget _buildMainAppBar(bool isOnline, bool isTyping) {
    return AppBar(
      leadingWidth: 40,
      titleSpacing: 0,
      title: GestureDetector(
        onTap: _openInfo,
        child: Row(
          children: [
            CircleAvatar(
              radius: 20,
              backgroundImage: widget.avatarUrl != null
                  ? CachedNetworkImageProvider(widget.avatarUrl!)
                  : null,
              child: widget.avatarUrl == null ? Text(widget.name[0].toUpperCase()) : null,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.name,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    isTyping
                        ? 'escribiendo...'
                        : isOnline
                            ? 'en línea'
                            : widget.isGroup
                                ? 'Grupo'
                                : '',
                    style: TextStyle(
                      fontSize: 12,
                      color: isTyping || isOnline ? Colors.green : Colors.grey,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      actions: [
        IconButton(
          icon: const Icon(Icons.videocam),
          onPressed: _startVideoCall,
        ),
        IconButton(
          icon: const Icon(Icons.call),
          onPressed: _startVoiceCall,
        ),
        PopupMenuButton<String>(
          onSelected: (value) {
            switch (value) {
              case 'search':
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const SearchScreen(),
                  ),
                );
                break;
              case 'mute':
                if (!_usesLegacyApi) {
                  _chatService.muteConversation(widget.conversationId);
                }
                break;
              case 'archive':
                if (!_usesLegacyApi) {
                  _chatService.archiveConversation(widget.conversationId);
                  Navigator.pop(context);
                }
                break;
              case 'clear':
                break;
              case 'block':
                if (!_usesLegacyApi) {
                  _chatService.blockUser(widget.conversationId);
                }
                break;
            }
          },
          itemBuilder: (context) => [
            const PopupMenuItem(
              value: 'search',
              child: ListTile(
                leading: Icon(Icons.search),
                title: Text('Buscar'),
                contentPadding: EdgeInsets.zero,
              ),
            ),
            PopupMenuItem(
              value: 'mute',
              child: ListTile(
                leading: Icon(
                  _chatService.isMuted(widget.conversationId)
                      ? Icons.notifications_active
                      : Icons.notifications_off,
                ),
                title: Text(
                  _chatService.isMuted(widget.conversationId)
                      ? 'Activar sonido'
                      : 'Silenciar',
                ),
                contentPadding: EdgeInsets.zero,
              ),
            ),
            const PopupMenuItem(
              value: 'archive',
              child: ListTile(
                leading: Icon(Icons.archive),
                title: Text('Archivar'),
                contentPadding: EdgeInsets.zero,
              ),
            ),
            const PopupMenuItem(
              value: 'clear',
              child: ListTile(
                leading: Icon(Icons.delete_sweep),
                title: Text('Limpiar chat'),
                contentPadding: EdgeInsets.zero,
              ),
            ),
            if (!widget.isGroup)
              const PopupMenuItem(
                value: 'block',
                child: ListTile(
                  leading: Icon(Icons.block, color: Colors.red),
                  title: Text('Bloquear', style: TextStyle(color: Colors.red)),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
          ],
        ),
      ],
    );
  }

  PreferredSizeWidget _buildSelectionAppBar() {
    return AppBar(
      leading: IconButton(
        icon: const Icon(Icons.close),
        onPressed: _clearSelection,
      ),
      title: Text('${_selectedMessages.length} seleccionados'),
      actions: [
        IconButton(
          icon: const Icon(Icons.reply),
          onPressed: () {
            final message = _messages.firstWhere((m) => m.id == _selectedMessages.first);
            // ignore: invalid_use_of_protected_member
            setState(() {
              _replyingTo = message;
              _clearSelection();
            });
            _focusNode.requestFocus();
          },
        ),
        IconButton(
          icon: const Icon(Icons.copy),
          onPressed: () {
            final texts = _messages
                .where((m) => _selectedMessages.contains(m.id))
                .map((m) => m.content)
                .join('\n');
            Clipboard.setData(ClipboardData(text: texts));
            _showSnackBar('${_selectedMessages.length} mensajes copiados');
            _clearSelection();
          },
        ),
        IconButton(
          icon: const Icon(Icons.forward),
          onPressed: () {
            _showForwardDialog(_selectedMessages.toList());
            _clearSelection();
          },
        ),
        IconButton(
          icon: const Icon(Icons.delete),
          onPressed: () {
            for (final id in _selectedMessages) {
              _chatService.deleteMessage(widget.conversationId, id);
            }
            _clearSelection();
          },
        ),
      ],
    );
  }

  Widget _buildPinnedMessage() {
    final pinnedMessage = _messages.cast<ChatMessage?>().firstWhere(
      (m) => m?.isPinned == true,
      orElse: () => null,
    );

    if (pinnedMessage == null) return const SizedBox.shrink();

    return GestureDetector(
      onTap: () => _scrollToMessage(pinnedMessage.id),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        color: Theme.of(context).colorScheme.surfaceContainerHighest,
        child: Row(
          children: [
            const Icon(Icons.push_pin, size: 16),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                pinnedMessage.content,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 13),
              ),
            ),
            IconButton(
              icon: const Icon(Icons.close, size: 16),
              onPressed: () {
                _chatService.pinMessage(widget.conversationId, pinnedMessage.id);
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReplyBar() {
    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceContainerHighest,
        border: Border(
          left: BorderSide(
            color: Theme.of(context).colorScheme.primary,
            width: 4,
          ),
        ),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _replyingTo!.senderName,
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                ),
                Text(
                  _replyingTo!.content,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.close),
            // ignore: invalid_use_of_protected_member
            onPressed: () => setState(() => _replyingTo = null),
          ),
        ],
      ),
    );
  }

  Widget _buildAttachmentPicker() {
    final isLegacy = _usesLegacyApi;
    return Container(
      padding: const EdgeInsets.all(16),
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _buildAttachmentOption(
            icon: Icons.photo,
            label: 'Galería',
            color: Colors.purple,
            onTap: _sendImage,
          ),
          _buildAttachmentOption(
            icon: Icons.camera_alt,
            label: 'Cámara',
            color: Colors.pink,
            onTap: _sendCamera,
          ),
          if (!isLegacy) ...[
            _buildAttachmentOption(
              icon: Icons.videocam,
              label: 'Video',
              color: Colors.red,
              onTap: _sendVideo,
            ),
            _buildAttachmentOption(
              icon: Icons.insert_drive_file,
              label: 'Archivo',
              color: Colors.blue,
              onTap: _sendFile,
            ),
            _buildAttachmentOption(
              icon: Icons.location_on,
              label: 'Ubicación',
              color: Colors.green,
              onTap: _sendLocation,
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildAttachmentOption({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: color,
            child: Icon(icon, color: Colors.white),
          ),
          const SizedBox(height: 8),
          Text(label, style: const TextStyle(fontSize: 12)),
        ],
      ),
    );
  }

  Widget _buildDateSeparator(DateTime date) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Center(
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Text(
            _formatDate(date),
            style: const TextStyle(fontSize: 12, color: Colors.grey),
          ),
        ),
      ),
    );
  }

  bool _isSameDay(DateTime a, DateTime b) {
    return a.year == b.year && a.month == b.month && a.day == b.day;
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    if (_isSameDay(date, now)) return 'Hoy';
    if (_isSameDay(date, now.subtract(const Duration(days: 1)))) return 'Ayer';
    return '${date.day}/${date.month}/${date.year}';
  }

  void _scrollToMessage(String messageId) {
    final index = _messages.indexWhere((m) => m.id == messageId);
    if (index != -1 && _scrollController.hasClients) {
      _scrollController.animateTo(
        index * 80.0,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    }
  }

  void _openInfo() {
    if (_usesLegacyApi) return;
    if (widget.isGroup) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => GroupInfoScreen(groupId: widget.conversationId),
        ),
      );
    } else {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => UserProfileScreen(userId: widget.conversationId),
        ),
      );
    }
  }
}
