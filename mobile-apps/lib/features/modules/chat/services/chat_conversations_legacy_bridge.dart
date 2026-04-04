import '../../../../core/api/api_client.dart';
import '../../../../core/services/chat_service.dart';

class ChatConversationsLegacyBridge {
  const ChatConversationsLegacyBridge._();

  static const legacyGroupPrefix = 'legacy-group:';
  static const legacyInternalPrefix = 'legacy-internal:';

  static List<ChatConversation> mapLegacyGroupConversations(dynamic response) {
    if (response.success != true || response.data == null) {
      return const [];
    }

    final data = response.data as Map<String, dynamic>?;
    if (data == null) return const [];
    final items =
        (data['grupos'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>();

    return items.map((group) {
      final id = (group['id'] as num?)?.toInt() ?? 0;
      final lastMessage = group['ultimo_mensaje']?.toString() ??
          group['descripcion']?.toString() ??
          'Grupo disponible';
      return ChatConversation(
        id: '$legacyGroupPrefix$id',
        name: group['nombre']?.toString() ?? 'Grupo',
        avatarUrl: group['imagen']?.toString(),
        isGroup: true,
        lastMessage: lastMessage,
        lastMessageTime: _parseLegacyDate(
          group['ultima_actividad']?.toString() ??
              group['updated_at']?.toString() ??
              group['created_at']?.toString(),
        ),
        unreadCount: (group['no_leidos'] as num?)?.toInt() ?? 0,
      );
    }).toList();
  }

  static List<ChatConversation> mapLegacyInternalConversations(
    dynamic response,
  ) {
    if (response.success != true || response.data == null) {
      return const [];
    }

    final items = (response.data!['conversaciones'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>();

    return items.map((conversation) {
      final id = (conversation['id'] as num?)?.toInt() ?? 0;
      final otherUser =
          (conversation['con_usuario'] as Map?)?.cast<String, dynamic>() ??
              const <String, dynamic>{};
      final otherUserId = (otherUser['id'] as num?)?.toInt() ?? 0;
      final encrypted = conversation['cifrado'] == true || conversation['cifrado'] == 1;
      return ChatConversation(
        id: '$legacyInternalPrefix$id:$otherUserId',
        name: otherUser['nombre']?.toString() ?? 'Usuario',
        avatarUrl: otherUser['avatar']?.toString(),
        isGroup: false,
        lastMessage:
            encrypted ? '[Mensaje cifrado]' : conversation['ultimo_mensaje']?.toString() ?? '',
        lastMessageTime: _parseLegacyDate(
          conversation['updated_at']?.toString() ??
              conversation['ultima_actividad']?.toString() ??
              conversation['created_at']?.toString(),
        ),
        unreadCount: (conversation['no_leidos'] as num?)?.toInt() ?? 0,
      );
    }).toList();
  }

  static Future<List<ChatConversation>> loadMergedConversations(
    ChatService chatService,
    ApiClient apiClient,
  ) async {
    final results = await Future.wait<dynamic>([
      chatService.getConversations(),
      chatService.getArchivedConversations(),
      apiClient.getChatGrupos(),
      apiClient.getChatInternoConversaciones(),
    ]);

    final currentConversations = results[0] as List<ChatConversation>;
    final archivedConversations = results[1] as List<ChatConversation>;
    final legacyGroupResponse = results[2];
    final legacyInternalResponse = results[3];

    final merged = <ChatConversation>[
      ...currentConversations,
      ...archivedConversations,
      ...mapLegacyGroupConversations(legacyGroupResponse),
      ...mapLegacyInternalConversations(legacyInternalResponse),
    ];

    merged.sort((a, b) {
      if (a.isPinned && !b.isPinned) return -1;
      if (!a.isPinned && b.isPinned) return 1;
      return b.lastMessageTime.compareTo(a.lastMessageTime);
    });
    return merged;
  }

  static DateTime _parseLegacyDate(String? rawDate) {
    if (rawDate == null || rawDate.isEmpty) {
      return DateTime.now();
    }

    return DateTime.tryParse(rawDate) ?? DateTime.now();
  }
}
