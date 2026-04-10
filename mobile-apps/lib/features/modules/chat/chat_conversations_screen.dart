import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/services/chat_service.dart';
import '../../../core/widgets/flavor_initials_avatar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import 'services/chat_conversations_legacy_bridge.dart';
import 'chat_main_screen.dart';
import 'screens/create_group_screen.dart';
import 'screens/search_messages_screen.dart';
import 'screens/status_screen.dart';
import 'screens/calls_history_screen.dart';
import 'screens/chat_settings_screen.dart';

/// Pantalla principal de conversaciones (lista de chats)
class ChatConversationsScreen extends ConsumerStatefulWidget {
  final String title;
  final String? legacyNotice;
  final int initialTabIndex;

  const ChatConversationsScreen({
    super.key,
    this.title = 'Chat',
    this.legacyNotice,
    this.initialTabIndex = 0,
  });

  @override
  ConsumerState<ChatConversationsScreen> createState() => _ChatConversationsScreenState();
}

class _ChatConversationsScreenState extends ConsumerState<ChatConversationsScreen>
    with SingleTickerProviderStateMixin {
  final ChatService _chatService = ChatService();
  late TabController _tabController;

  List<ChatConversation> _conversations = [];
  List<ChatConversation> _archivedConversations = [];
  bool _isLoading = true;
  bool _isSelectionMode = false;
  final Set<String> _selectedConversations = {};

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: 3,
      vsync: this,
      initialIndex: widget.initialTabIndex.clamp(0, 2),
    );
    _loadConversations();

    // Escuchar cambios en tiempo real
    _chatService.addListener(_onChatServiceUpdate);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _chatService.removeListener(_onChatServiceUpdate);
    super.dispose();
  }

  void _onChatServiceUpdate() {
    _loadConversations();
  }

  Future<void> _loadConversations() async {
    try {
      final archived = await _chatService.getArchivedConversations();
      final mergedConversations =
          await ChatConversationsLegacyBridge.loadMergedConversations(
        _chatService,
        ref.read(apiClientProvider),
      );

      setState(() {
        _conversations = mergedConversations;
        _archivedConversations = archived;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  void _toggleSelection(String conversationId) {
    setState(() {
      if (_selectedConversations.contains(conversationId)) {
        _selectedConversations.remove(conversationId);
        if (_selectedConversations.isEmpty) {
          _isSelectionMode = false;
        }
      } else {
        _selectedConversations.add(conversationId);
      }
    });
  }

  void _clearSelection() {
    setState(() {
      _selectedConversations.clear();
      _isSelectionMode = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: _isSelectionMode
          ? _buildSelectionAppBar()
          : _buildNormalAppBar(),
      body: _isLoading
          ? const FlavorLoadingState()
          : TabBarView(
              controller: _tabController,
              children: [
                _buildChatsTab(),
                const StatusScreen(),
                _buildCallsTab(),
              ],
            ),
      floatingActionButton: _tabController.index == 0
          ? FloatingActionButton(
              onPressed: _showNewChatOptions,
              child: const Icon(Icons.chat),
            )
          : _tabController.index == 1
              ? FloatingActionButton(
                  onPressed: () {
                    // Crear nuevo estado
                  },
                  child: const Icon(Icons.camera_alt),
                )
              : FloatingActionButton(
                  onPressed: () {
                    // Nueva llamada
                  },
                  child: const Icon(Icons.add_call),
                ),
    );
  }

  AppBar _buildNormalAppBar() {
    return AppBar(
      title: Text(widget.title),
      actions: [
        IconButton(
          icon: const Icon(Icons.search),
          onPressed: () {
            Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const SearchScreen()),
            );
          },
        ),
        PopupMenuButton<String>(
          onSelected: _handleMenuAction,
          itemBuilder: (context) => [
            const PopupMenuItem(
              value: 'new_group',
              child: ListTile(
                leading: Icon(Icons.group_add),
                title: Text('Nuevo grupo'),
                contentPadding: EdgeInsets.zero,
              ),
            ),
            const PopupMenuItem(
              value: 'archived',
              child: ListTile(
                leading: Icon(Icons.archive),
                title: Text('Archivados'),
                contentPadding: EdgeInsets.zero,
              ),
            ),
            const PopupMenuItem(
              value: 'starred',
              child: ListTile(
                leading: Icon(Icons.star),
                title: Text('Destacados'),
                contentPadding: EdgeInsets.zero,
              ),
            ),
            const PopupMenuItem(
              value: 'settings',
              child: ListTile(
                leading: Icon(Icons.settings),
                title: Text('Configuración'),
                contentPadding: EdgeInsets.zero,
              ),
            ),
          ],
        ),
      ],
      bottom: TabBar(
        controller: _tabController,
        tabs: const [
          Tab(text: 'Chats'),
          Tab(text: 'Estados'),
          Tab(text: 'Llamadas'),
        ],
      ),
    );
  }

  AppBar _buildSelectionAppBar() {
    return AppBar(
      leading: IconButton(
        icon: const Icon(Icons.close),
        onPressed: _clearSelection,
      ),
      title: Text('${_selectedConversations.length} seleccionados'),
      actions: [
        IconButton(
          icon: const Icon(Icons.push_pin),
          onPressed: () => _bulkAction('pin'),
          tooltip: 'Fijar',
        ),
        IconButton(
          icon: const Icon(Icons.archive),
          onPressed: () => _bulkAction('archive'),
          tooltip: 'Archivar',
        ),
        IconButton(
          icon: const Icon(Icons.notifications_off),
          onPressed: () => _bulkAction('mute'),
          tooltip: 'Silenciar',
        ),
        IconButton(
          icon: const Icon(Icons.delete),
          onPressed: () => _bulkAction('delete'),
          tooltip: 'Eliminar',
        ),
      ],
    );
  }

  Widget _buildChatsTab() {
    final notice = widget.legacyNotice;

    if (_conversations.isEmpty) {
      return Column(
        children: [
          if (notice != null) _buildLegacyNotice(notice),
          Expanded(
            child: _buildEmptyState(
              icon: Icons.chat_bubble_outline,
              title: 'No hay conversaciones',
              subtitle: 'Inicia una nueva conversación',
              action: 'Nuevo chat',
              onAction: _showNewChatOptions,
            ),
          ),
        ],
      );
    }

    // Separar fijados y normales
    final pinnedConversations = _conversations.where((c) => c.isPinned).toList();
    final normalConversations = _conversations.where((c) => !c.isPinned).toList();

    return RefreshIndicator(
      onRefresh: _loadConversations,
      child: ListView(
        children: [
          if (notice != null) _buildLegacyNotice(notice),
          // Archivados (si hay)
          if (_archivedConversations.isNotEmpty)
            ListTile(
              leading: const CircleAvatar(
                child: Icon(Icons.archive),
              ),
              title: const Text('Archivados'),
              trailing: Text(
                '${_archivedConversations.length}',
                style: TextStyle(
                  color: Theme.of(context).colorScheme.primary,
                  fontWeight: FontWeight.bold,
                ),
              ),
              onTap: _showArchivedChats,
            ),

          // Chats fijados
          if (pinnedConversations.isNotEmpty) ...[
            _buildSectionHeader('Fijados'),
            ...pinnedConversations.map((conv) => _buildConversationTile(conv)),
          ],

          // Chats normales
          if (normalConversations.isNotEmpty) ...[
            if (pinnedConversations.isNotEmpty)
              _buildSectionHeader('Todos los chats'),
            ...normalConversations.map((conv) => _buildConversationTile(conv)),
          ],
        ],
      ),
    );
  }

  Widget _buildLegacyNotice(String text) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.secondaryContainer.withOpacity(0.6),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.only(top: 2),
            child: Icon(Icons.merge_type, size: 18),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: Theme.of(context).colorScheme.primary,
        ),
      ),
    );
  }

  Widget _buildConversationTile(ChatConversation conversation) {
    final colorScheme = Theme.of(context).colorScheme;
    final isSelected = _selectedConversations.contains(conversation.id);
    final hasUnread = conversation.unreadCount > 0;

    return InkWell(
      onTap: () {
        if (_isSelectionMode) {
          _toggleSelection(conversation.id);
        } else {
          _openChat(conversation);
        }
      },
      onLongPress: () {
        if (!_isSelectionMode) {
          setState(() {
            _isSelectionMode = true;
            _selectedConversations.add(conversation.id);
          });
        }
      },
      child: Container(
        color: isSelected ? colorScheme.primaryContainer.withOpacity(0.3) : null,
        child: ListTile(
          leading: Stack(
            children: [
              CircleAvatar(
                radius: 28,
                backgroundImage: conversation.avatarUrl != null
                    ? CachedNetworkImageProvider(conversation.avatarUrl!)
                    : null,
                child: conversation.avatarUrl == null
                    ? Text(
                        FlavorInitialsAvatar.initialsFor(conversation.name),
                        style: const TextStyle(fontSize: 20),
                      )
                    : null,
              ),
              if (conversation.isOnline && !conversation.isGroup)
                Positioned(
                  bottom: 0,
                  right: 0,
                  child: Container(
                    width: 16,
                    height: 16,
                    decoration: BoxDecoration(
                      color: Colors.green,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: Theme.of(context).scaffoldBackgroundColor,
                        width: 2,
                      ),
                    ),
                  ),
                ),
              if (_isSelectionMode)
                Positioned(
                  bottom: 0,
                  right: 0,
                  child: Container(
                    width: 20,
                    height: 20,
                    decoration: BoxDecoration(
                      color: isSelected ? colorScheme.primary : Colors.white,
                      shape: BoxShape.circle,
                      border: Border.all(
                        color: colorScheme.primary,
                        width: 2,
                      ),
                    ),
                    child: isSelected
                        ? const Icon(Icons.check, size: 14, color: Colors.white)
                        : null,
                  ),
                ),
            ],
          ),
          title: Row(
            children: [
              Expanded(
                child: Text(
                  conversation.name,
                  style: TextStyle(
                    fontWeight: hasUnread ? FontWeight.bold : FontWeight.normal,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Text(
                _formatTime(conversation.lastMessageTime),
                style: TextStyle(
                  fontSize: 12,
                  color: hasUnread ? colorScheme.primary : colorScheme.outline,
                  fontWeight: hasUnread ? FontWeight.bold : FontWeight.normal,
                ),
              ),
            ],
          ),
          subtitle: Row(
            children: [
              // Estado del mensaje (enviado, leído)
              if (conversation.lastMessageIsFromMe) ...[
                Icon(
                  conversation.lastMessageStatus == MessageStatus.read
                      ? Icons.done_all
                      : conversation.lastMessageStatus == MessageStatus.delivered
                          ? Icons.done_all
                          : Icons.done,
                  size: 16,
                  color: conversation.lastMessageStatus == MessageStatus.read
                      ? Colors.blue
                      : colorScheme.outline,
                ),
                const SizedBox(width: 4),
              ],

              // Icono de tipo de mensaje
              if (conversation.lastMessageType != MessageType.text)
                Padding(
                  padding: const EdgeInsets.only(right: 4),
                  child: Icon(
                    _getMessageTypeIcon(conversation.lastMessageType),
                    size: 16,
                    color: colorScheme.outline,
                  ),
                ),

              // Preview del mensaje
              Expanded(
                child: Text(
                  conversation.lastMessage,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: hasUnread ? colorScheme.onSurface : colorScheme.outline,
                    fontWeight: hasUnread ? FontWeight.w500 : FontWeight.normal,
                  ),
                ),
              ),

              // Badge no leídos
              if (hasUnread)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: conversation.isMuted ? colorScheme.outline : colorScheme.primary,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    conversation.unreadCount > 99 ? '99+' : '${conversation.unreadCount}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),

              // Indicadores
              if (conversation.isPinned)
                Padding(
                  padding: const EdgeInsets.only(left: 4),
                  child: Icon(
                    Icons.push_pin,
                    size: 16,
                    color: colorScheme.outline,
                  ),
                ),
              if (conversation.isMuted)
                Padding(
                  padding: const EdgeInsets.only(left: 4),
                  child: Icon(
                    Icons.notifications_off,
                    size: 16,
                    color: colorScheme.outline,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCallsTab() {
    // Usar el widget de historial de llamadas
    return const CallsHistoryWidget();
  }

  Widget _buildEmptyState({
    required IconData icon,
    required String title,
    required String subtitle,
    String? action,
    VoidCallback? onAction,
  }) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 80, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            subtitle,
            style: TextStyle(color: Colors.grey[600]),
          ),
          if (action != null && onAction != null) ...[
            const SizedBox(height: 24),
            FilledButton.icon(
              onPressed: onAction,
              icon: const Icon(Icons.add),
              label: Text(action),
            ),
          ],
        ],
      ),
    );
  }

  // Handlers
  void _handleMenuAction(String action) {
    switch (action) {
      case 'new_group':
        _createNewGroup();
        break;
      case 'archived':
        _showArchivedChats();
        break;
      case 'starred':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const SearchScreen(showOnlyStarred: true),
          ),
        );
        break;
      case 'settings':
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const ChatSettingsScreen(),
          ),
        );
        break;
    }
  }

  void _showNewChatOptions() {
    showModalBottomSheet(
      context: context,
      builder: (context) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: const CircleAvatar(child: Icon(Icons.person_add)),
            title: const Text('Nuevo chat'),
            subtitle: const Text('Inicia una conversación privada'),
            onTap: () {
              Navigator.pop(context);
              _startNewChat();
            },
          ),
          ListTile(
            leading: const CircleAvatar(child: Icon(Icons.group_add)),
            title: const Text('Nuevo grupo'),
            subtitle: const Text('Crea un grupo con varias personas'),
            onTap: () {
              Navigator.pop(context);
              _createNewGroup();
            },
          ),
          ListTile(
            leading: const CircleAvatar(child: Icon(Icons.qr_code)),
            title: const Text('Escanear código QR'),
            subtitle: const Text('Añade contactos escaneando su código'),
            onTap: () {
              Navigator.pop(context);
              _scanQRCode();
            },
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  void _startNewChat() async {
    final result = await Navigator.push<List<ChatUser>>(
      context,
      MaterialPageRoute(
        builder: (context) => const ContactPickerScreen(
          title: 'Seleccionar contacto',
          multiSelect: false,
        ),
      ),
    );

    if (result != null && result.isNotEmpty) {
      final user = result.first;
      // Abrir o crear conversación con este usuario
      _openChatWithUser(user);
    }
  }

  void _createNewGroup() async {
    final result = await Navigator.push<ChatGroup>(
      context,
      MaterialPageRoute(builder: (context) => const CreateGroupScreen()),
    );

    if (result != null) {
      // Abrir el nuevo grupo
      _loadConversations();
    }
  }

  void _showArchivedChats() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Text(
                    'Chats archivados',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: _archivedConversations.isEmpty
                  ? const Center(child: Text('No hay chats archivados'))
                  : ListView.builder(
                      controller: scrollController,
                      itemCount: _archivedConversations.length,
                      itemBuilder: (context, index) =>
                          _buildConversationTile(_archivedConversations[index]),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  void _openChat(ChatConversation conversation) {
    if (conversation.id.startsWith(
      ChatConversationsLegacyBridge.legacyGroupPrefix,
    )) {
      final groupId = int.tryParse(
        conversation.id.substring(
          ChatConversationsLegacyBridge.legacyGroupPrefix.length,
        ),
      );
      if (groupId != null) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ChatMainScreen(
              conversationId: conversation.id,
              name: conversation.name,
              avatarUrl: conversation.avatarUrl,
              isGroup: true,
              legacyGroupId: groupId,
            ),
          ),
        );
      }
      return;
    }

    if (conversation.id.startsWith(
      ChatConversationsLegacyBridge.legacyInternalPrefix,
    )) {
      final payload = conversation.id.substring(
        ChatConversationsLegacyBridge.legacyInternalPrefix.length,
      );
      final parts = payload.split(':');
      final conversationId = parts.isNotEmpty ? int.tryParse(parts[0]) : null;
      final otherUserId = parts.length > 1 ? int.tryParse(parts[1]) : null;
      if (conversationId != null && otherUserId != null) {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ChatMainScreen(
              conversationId: conversation.id,
              name: conversation.name,
              avatarUrl: conversation.avatarUrl,
              isGroup: false,
              legacyInternalConversationId: conversationId,
              legacyOtherUserId: otherUserId,
            ),
          ),
        );
      }
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ChatMainScreen(
          conversationId: conversation.id,
          name: conversation.name,
          avatarUrl: conversation.avatarUrl,
          isGroup: conversation.isGroup,
        ),
      ),
    );
  }

  void _openChatWithUser(ChatUser user) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ChatMainScreen(
          conversationId: user.id, // Se creará la conversación si no existe
          name: user.name,
          avatarUrl: user.avatarUrl,
          isGroup: false,
        ),
      ),
    );
  }

  Future<void> _bulkAction(String action) async {
    final selectedIds = _selectedConversations.toList();

    switch (action) {
      case 'pin':
        for (final id in selectedIds) {
          await _chatService.pinConversation(id);
        }
        break;
      case 'archive':
        for (final id in selectedIds) {
          await _chatService.archiveConversation(id);
        }
        break;
      case 'mute':
        for (final id in selectedIds) {
          await _chatService.muteConversation(id);
        }
        break;
      case 'delete':
        final confirmed = await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Eliminar chats'),
            content: Text('¿Eliminar ${selectedIds.length} conversaciones?'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Cancelar'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Eliminar', style: TextStyle(color: Colors.red)),
              ),
            ],
          ),
        );

        if (confirmed == true) {
          for (final id in selectedIds) {
            await _chatService.deleteConversation(id);
          }
        }
        break;
    }

    _clearSelection();
    _loadConversations();
  }

  // Helpers
  String _formatTime(DateTime time) {
    final now = DateTime.now();
    final diff = now.difference(time);

    if (diff.inDays == 0) {
      return '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
    } else if (diff.inDays == 1) {
      return 'Ayer';
    } else if (diff.inDays < 7) {
      const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
      return days[time.weekday % 7];
    } else {
      return '${time.day}/${time.month}/${time.year}';
    }
  }

  IconData _getMessageTypeIcon(MessageType type) {
    switch (type) {
      case MessageType.image:
        return Icons.photo;
      case MessageType.video:
        return Icons.videocam;
      case MessageType.audio:
      case MessageType.voice:
        return Icons.mic;
      case MessageType.file:
        return Icons.attach_file;
      case MessageType.location:
        return Icons.location_on;
      case MessageType.contact:
        return Icons.person;
      case MessageType.sticker:
        return Icons.emoji_emotions;
      case MessageType.gif:
        return Icons.gif;
      case MessageType.poll:
        return Icons.poll;
      case MessageType.link:
        return Icons.link;
      default:
        return Icons.message;
    }
  }

  void _openChatWithUser(ChatUser user) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ChatMainScreen(
          conversationId: 'user_${user.id}',
          name: user.name,
          avatarUrl: user.avatarUrl,
          isGroup: false,
        ),
      ),
    );
  }

  void _scanQRCode() {
    // Mostrar mensaje informativo mientras se implementa el scanner
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Escáner de código QR próximamente'),
        duration: Duration(seconds: 2),
      ),
    );
  }
}
