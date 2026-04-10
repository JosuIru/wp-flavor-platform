import 'dart:async';
import 'package:flutter/material.dart';

/// Tipos de mensaje
enum MessageType {
  text,
  image,
  video,
  audio,
  voice,
  file,
  location,
  contact,
  sticker,
  gif,
  poll,
  link,
  system,
}

/// Estado del mensaje
enum MessageStatus {
  sending,
  sent,
  delivered,
  read,
  failed,
}

/// Privacidad del grupo
enum GroupPrivacy {
  public,
  private,
  secret,
}

/// Rol en grupo
enum GroupRole {
  owner,
  admin,
  member,
}

/// Tipo de estado/historia
enum StatusType {
  text,
  image,
  video,
}

/// Modelo de mensaje
class ChatMessage {
  final String id;
  final String conversationId;
  final String senderId;
  final String senderName;
  final String? senderAvatar;
  final MessageType type;
  final String content;
  final String? mediaUrl;
  final String? thumbnailUrl;
  final int? mediaDuration;
  final Map<String, dynamic>? metadata;
  final DateTime timestamp;
  final MessageStatus status;
  final String? replyToId;
  final ChatMessage? replyTo;
  final List<String> reactions;
  final bool isEdited;
  final bool isDeleted;
  final bool isPinned;

  const ChatMessage({
    required this.id,
    required this.conversationId,
    required this.senderId,
    required this.senderName,
    this.senderAvatar,
    required this.type,
    required this.content,
    this.mediaUrl,
    this.thumbnailUrl,
    this.mediaDuration,
    this.metadata,
    required this.timestamp,
    this.status = MessageStatus.sent,
    this.replyToId,
    this.replyTo,
    this.reactions = const [],
    this.isEdited = false,
    this.isDeleted = false,
    this.isPinned = false,
  });

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: json['id']?.toString() ?? '',
      conversationId: json['conversation_id']?.toString() ?? '',
      senderId: json['sender_id']?.toString() ?? '',
      senderName: json['sender_name'] ?? 'Usuario',
      senderAvatar: json['sender_avatar'],
      type: MessageType.values.firstWhere(
        (e) => e.name == json['type'],
        orElse: () => MessageType.text,
      ),
      content: json['content'] ?? '',
      mediaUrl: json['media_url'],
      thumbnailUrl: json['thumbnail_url'],
      mediaDuration: json['media_duration'],
      metadata: json['metadata'],
      timestamp: DateTime.tryParse(json['timestamp'] ?? '') ?? DateTime.now(),
      status: MessageStatus.values.firstWhere(
        (e) => e.name == json['status'],
        orElse: () => MessageStatus.sent,
      ),
      replyToId: json['reply_to_id']?.toString(),
      reactions: List<String>.from(json['reactions'] ?? []),
      isEdited: json['is_edited'] ?? false,
      isDeleted: json['is_deleted'] ?? false,
      isPinned: json['is_pinned'] ?? false,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'conversation_id': conversationId,
    'sender_id': senderId,
    'sender_name': senderName,
    'sender_avatar': senderAvatar,
    'type': type.name,
    'content': content,
    'media_url': mediaUrl,
    'thumbnail_url': thumbnailUrl,
    'media_duration': mediaDuration,
    'metadata': metadata,
    'timestamp': timestamp.toIso8601String(),
    'status': status.name,
    'reply_to_id': replyToId,
    'reactions': reactions,
    'is_edited': isEdited,
    'is_deleted': isDeleted,
    'is_pinned': isPinned,
  };

  ChatMessage copyWith({
    MessageStatus? status,
    List<String>? reactions,
    bool? isEdited,
    bool? isDeleted,
    bool? isPinned,
  }) {
    return ChatMessage(
      id: id,
      conversationId: conversationId,
      senderId: senderId,
      senderName: senderName,
      senderAvatar: senderAvatar,
      type: type,
      content: content,
      mediaUrl: mediaUrl,
      thumbnailUrl: thumbnailUrl,
      mediaDuration: mediaDuration,
      metadata: metadata,
      timestamp: timestamp,
      status: status ?? this.status,
      replyToId: replyToId,
      replyTo: replyTo,
      reactions: reactions ?? this.reactions,
      isEdited: isEdited ?? this.isEdited,
      isDeleted: isDeleted ?? this.isDeleted,
      isPinned: isPinned ?? this.isPinned,
    );
  }
}

/// Modelo de conversación
class ChatConversation {
  final String id;
  final String name;
  final String? avatarUrl;
  final bool isGroup;
  final String lastMessage;
  final MessageType lastMessageType;
  final DateTime lastMessageTime;
  final bool lastMessageIsFromMe;
  final MessageStatus lastMessageStatus;
  final int unreadCount;
  final bool isOnline;
  final bool isMuted;
  final bool isPinned;
  final bool isArchived;

  const ChatConversation({
    required this.id,
    required this.name,
    this.avatarUrl,
    this.isGroup = false,
    this.lastMessage = '',
    this.lastMessageType = MessageType.text,
    required this.lastMessageTime,
    this.lastMessageIsFromMe = false,
    this.lastMessageStatus = MessageStatus.sent,
    this.unreadCount = 0,
    this.isOnline = false,
    this.isMuted = false,
    this.isPinned = false,
    this.isArchived = false,
  });

  factory ChatConversation.fromJson(Map<String, dynamic> json) {
    return ChatConversation(
      id: json['id']?.toString() ?? '',
      name: json['name'] ?? 'Sin nombre',
      avatarUrl: json['avatar_url'],
      isGroup: json['is_group'] ?? false,
      lastMessage: json['last_message'] ?? '',
      lastMessageType: MessageType.values.firstWhere(
        (e) => e.name == json['last_message_type'],
        orElse: () => MessageType.text,
      ),
      lastMessageTime: DateTime.tryParse(json['last_message_time'] ?? '') ?? DateTime.now(),
      lastMessageIsFromMe: json['last_message_is_from_me'] ?? false,
      lastMessageStatus: MessageStatus.values.firstWhere(
        (e) => e.name == json['last_message_status'],
        orElse: () => MessageStatus.sent,
      ),
      unreadCount: json['unread_count'] ?? 0,
      isOnline: json['is_online'] ?? false,
      isMuted: json['is_muted'] ?? false,
      isPinned: json['is_pinned'] ?? false,
      isArchived: json['is_archived'] ?? false,
    );
  }
}

/// Modelo de usuario
class ChatUser {
  final String id;
  final String name;
  final String? avatarUrl;
  final bool isOnline;
  final DateTime? lastSeen;
  final String? bio;
  final String? phone;
  final String? email;

  const ChatUser({
    required this.id,
    required this.name,
    this.avatarUrl,
    this.isOnline = false,
    this.lastSeen,
    this.bio,
    this.phone,
    this.email,
  });

  factory ChatUser.fromJson(Map<String, dynamic> json) {
    return ChatUser(
      id: json['id']?.toString() ?? '',
      name: json['name'] ?? 'Usuario',
      avatarUrl: json['avatar_url'],
      isOnline: json['is_online'] ?? false,
      lastSeen: DateTime.tryParse(json['last_seen'] ?? ''),
      bio: json['bio'],
      phone: json['phone'],
      email: json['email'],
    );
  }
}

/// Modelo de grupo
class ChatGroup {
  final String id;
  final String name;
  final String? description;
  final String? avatarUrl;
  final GroupPrivacy privacy;
  final int memberCount;
  final String? inviteLink;
  final DateTime createdAt;

  const ChatGroup({
    required this.id,
    required this.name,
    this.description,
    this.avatarUrl,
    this.privacy = GroupPrivacy.private,
    this.memberCount = 0,
    this.inviteLink,
    required this.createdAt,
  });

  factory ChatGroup.fromJson(Map<String, dynamic> json) {
    return ChatGroup(
      id: json['id']?.toString() ?? '',
      name: json['name'] ?? 'Grupo',
      description: json['description'],
      avatarUrl: json['avatar_url'],
      privacy: GroupPrivacy.values.firstWhere(
        (e) => e.name == json['privacy'],
        orElse: () => GroupPrivacy.private,
      ),
      memberCount: json['member_count'] ?? 0,
      inviteLink: json['invite_link'],
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
    );
  }
}

/// Modelo de miembro de grupo
class GroupMember {
  final String odp;
  final String name;
  final String? avatarUrl;
  final GroupRole role;
  final DateTime joinedAt;

  String get userId => odp;

  const GroupMember({
    required this.odp,
    required this.name,
    this.avatarUrl,
    this.role = GroupRole.member,
    required this.joinedAt,
  });

  factory GroupMember.fromJson(Map<String, dynamic> json) {
    return GroupMember(
      odp: json['user_id']?.toString() ?? '',
      name: json['name'] ?? 'Miembro',
      avatarUrl: json['avatar_url'],
      role: GroupRole.values.firstWhere(
        (e) => e.name == json['role'],
        orElse: () => GroupRole.member,
      ),
      joinedAt: DateTime.tryParse(json['joined_at'] ?? '') ?? DateTime.now(),
    );
  }
}

/// Modelo de estado/historia
class ChatStatus {
  final String id;
  final String odp;
  final String userName;
  final String? userAvatar;
  final StatusType type;
  final String content;
  final String? mediaUrl;
  final String? backgroundColor;
  final DateTime createdAt;
  final DateTime expiresAt;
  final int viewCount;
  final List<String> viewerIds;

  String get userId => odp;

  const ChatStatus({
    required this.id,
    required this.odp,
    required this.userName,
    this.userAvatar,
    required this.type,
    required this.content,
    this.mediaUrl,
    this.backgroundColor,
    required this.createdAt,
    required this.expiresAt,
    this.viewCount = 0,
    this.viewerIds = const [],
  });

  factory ChatStatus.fromJson(Map<String, dynamic> json) {
    return ChatStatus(
      id: json['id']?.toString() ?? '',
      odp: json['user_id']?.toString() ?? '',
      userName: json['user_name'] ?? 'Usuario',
      userAvatar: json['user_avatar'],
      type: StatusType.values.firstWhere(
        (e) => e.name == json['type'],
        orElse: () => StatusType.text,
      ),
      content: json['content'] ?? '',
      mediaUrl: json['media_url'],
      backgroundColor: json['background_color'],
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      expiresAt: DateTime.tryParse(json['expires_at'] ?? '') ?? DateTime.now().add(const Duration(hours: 24)),
      viewCount: json['view_count'] ?? 0,
      viewerIds: List<String>.from(json['viewer_ids'] ?? []),
    );
  }

  bool get isExpired => DateTime.now().isAfter(expiresAt);
}

/// Modelo de encuesta
class ChatPoll {
  final String id;
  final String question;
  final List<PollOption> options;
  final bool allowMultiple;
  final bool isAnonymous;
  final DateTime? expiresAt;

  const ChatPoll({
    required this.id,
    required this.question,
    required this.options,
    this.allowMultiple = false,
    this.isAnonymous = false,
    this.expiresAt,
  });

  factory ChatPoll.fromJson(Map<String, dynamic> json) {
    return ChatPoll(
      id: json['id']?.toString() ?? '',
      question: json['question'] ?? '',
      options: (json['options'] as List?)
          ?.map((o) => PollOption.fromJson(o))
          .toList() ?? [],
      allowMultiple: json['allow_multiple'] ?? false,
      isAnonymous: json['is_anonymous'] ?? false,
      expiresAt: DateTime.tryParse(json['expires_at'] ?? ''),
    );
  }
}

/// Opción de encuesta
class PollOption {
  final String id;
  final String text;
  final int voteCount;
  final List<String> voterIds;

  const PollOption({
    required this.id,
    required this.text,
    this.voteCount = 0,
    this.voterIds = const [],
  });

  factory PollOption.fromJson(Map<String, dynamic> json) {
    return PollOption(
      id: json['id']?.toString() ?? '',
      text: json['text'] ?? '',
      voteCount: json['vote_count'] ?? 0,
      voterIds: List<String>.from(json['voter_ids'] ?? []),
    );
  }
}

/// Servicio completo de chat tipo Telegram/WhatsApp
class ChatService extends ChangeNotifier {
  static ChatService? _instance;

  ChatUser? _currentUser;

  final Map<String, ChatConversation> _conversations = {};
  final Map<String, List<ChatMessage>> _messages = {};
  final Map<String, ChatUser> _users = {};
  final Map<String, ChatGroup> _groups = {};
  final List<ChatStatus> _statuses = [];
  final Set<String> _mutedChats = {};
  final Set<String> _archivedChats = {};
  final Set<String> _blockedUsers = {};
  final Set<String> _pinnedChats = {};

  ChatService._();

  factory ChatService() {
    _instance ??= ChatService._();
    return _instance!;
  }

  ChatUser? get currentUser => _currentUser;

  // === CONVERSACIONES ===

  Future<List<ChatConversation>> getConversations() async {
    // TODO: Llamar API real
    return _conversations.values
        .where((c) => !_archivedChats.contains(c.id))
        .toList()
      ..sort((a, b) {
        if (_pinnedChats.contains(a.id) && !_pinnedChats.contains(b.id)) return -1;
        if (!_pinnedChats.contains(a.id) && _pinnedChats.contains(b.id)) return 1;
        return b.lastMessageTime.compareTo(a.lastMessageTime);
      });
  }

  Future<List<ChatConversation>> getArchivedConversations() async {
    return _conversations.values
        .where((c) => _archivedChats.contains(c.id))
        .toList();
  }

  Future<List<ChatMessage>> getMessages(String conversationId, {int limit = 50, String? beforeId}) async {
    // TODO: Llamar API real
    return _messages[conversationId] ?? [];
  }

  Future<ChatMessage?> sendMessage({
    required String conversationId,
    required String content,
    MessageType type = MessageType.text,
    String? mediaUrl,
    String? replyToId,
    Map<String, dynamic>? metadata,
  }) async {
    final message = ChatMessage(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      conversationId: conversationId,
      senderId: _currentUser?.id ?? '',
      senderName: _currentUser?.name ?? 'Yo',
      senderAvatar: _currentUser?.avatarUrl,
      type: type,
      content: content,
      mediaUrl: mediaUrl,
      timestamp: DateTime.now(),
      status: MessageStatus.sending,
      replyToId: replyToId,
      metadata: metadata,
    );

    _messages.putIfAbsent(conversationId, () => []);
    _messages[conversationId]!.insert(0, message);
    notifyListeners();

    // TODO: Enviar a API
    return message;
  }

  Future<bool> deleteMessage(String conversationId, String messageId, {bool forEveryone = false}) async {
    final messages = _messages[conversationId];
    if (messages != null) {
      messages.removeWhere((m) => m.id == messageId);
    }
    notifyListeners();
    return true;
  }

  Future<void> markAsRead(String conversationId) async {
    // TODO: Llamar API para marcar como leídos
    notifyListeners();
  }

  Future<ChatMessage?> sendMediaMessage({
    required String conversationId,
    required dynamic file,
    required MessageType type,
    String? replyToId,
  }) async {
    // TODO: Subir archivo y enviar mensaje
    return sendMessage(
      conversationId: conversationId,
      content: type == MessageType.image ? '📷 Imagen' : type == MessageType.video ? '🎬 Video' : '📎 Archivo',
      type: type,
      replyToId: replyToId,
    );
  }

  Future<ChatMessage?> sendVoiceMessage({
    required String conversationId,
    required dynamic audioFile,
    required Duration duration,
    String? replyToId,
  }) async {
    // TODO: Subir audio y enviar mensaje
    return sendMessage(
      conversationId: conversationId,
      content: '🎤 Mensaje de voz',
      type: MessageType.voice,
      replyToId: replyToId,
      metadata: {'duration': duration.inSeconds},
    );
  }

  Future<bool> pinMessage(String conversationId, String messageId) async {
    final messages = _messages[conversationId];
    if (messages != null) {
      final index = messages.indexWhere((m) => m.id == messageId);
      if (index != -1) {
        final msg = messages[index];
        messages[index] = msg.copyWith(isPinned: !msg.isPinned);
      }
    }
    notifyListeners();
    return true;
  }

  void sendTypingIndicator(String conversationId) {
    // TODO: Enviar indicador de escritura via WebSocket
  }

  bool isOnline(String odp) {
    return _users[odp]?.isOnline ?? false;
  }

  bool isTyping(String conversationId) {
    // TODO: Obtener estado de escritura via WebSocket
    return false;
  }

  bool isMuted(String conversationId) {
    return _mutedChats.contains(conversationId);
  }

  Future<bool> pinConversation(String conversationId) async {
    _pinnedChats.add(conversationId);
    notifyListeners();
    return true;
  }

  Future<bool> unpinConversation(String conversationId) async {
    _pinnedChats.remove(conversationId);
    notifyListeners();
    return true;
  }

  Future<bool> muteConversation(String conversationId) async {
    _mutedChats.add(conversationId);
    notifyListeners();
    return true;
  }

  Future<bool> unmuteConversation(String conversationId) async {
    _mutedChats.remove(conversationId);
    notifyListeners();
    return true;
  }

  Future<bool> archiveConversation(String conversationId) async {
    _archivedChats.add(conversationId);
    notifyListeners();
    return true;
  }

  Future<bool> unarchiveConversation(String conversationId) async {
    _archivedChats.remove(conversationId);
    notifyListeners();
    return true;
  }

  Future<bool> deleteConversation(String conversationId) async {
    _conversations.remove(conversationId);
    _messages.remove(conversationId);
    notifyListeners();
    return true;
  }

  // === GRUPOS ===

  Future<ChatGroup?> createGroup({
    required String name,
    String? description,
    required List<String> members,
    GroupPrivacy privacy = GroupPrivacy.private,
    String? avatarPath,
  }) async {
    final group = ChatGroup(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      name: name,
      description: description,
      privacy: privacy,
      memberCount: members.length + 1,
      createdAt: DateTime.now(),
    );
    _groups[group.id] = group;
    notifyListeners();
    return group;
  }

  Future<ChatGroup?> getGroupInfo(String groupId) async {
    return _groups[groupId];
  }

  Future<List<GroupMember>> getGroupMembers(String groupId) async {
    // TODO: Llamar API
    return [];
  }

  Future<bool> updateGroup(String groupId, {String? name, String? description, String? avatarUrl}) async {
    notifyListeners();
    return true;
  }

  Future<bool> addMember(String groupId, String userId) async {
    notifyListeners();
    return true;
  }

  Future<bool> removeMember(String groupId, String userId) async {
    notifyListeners();
    return true;
  }

  Future<bool> makeAdmin(String groupId, String userId) async {
    notifyListeners();
    return true;
  }

  Future<bool> removeAdmin(String groupId, String userId) async {
    notifyListeners();
    return true;
  }

  Future<bool> leaveGroup(String groupId) async {
    _groups.remove(groupId);
    notifyListeners();
    return true;
  }

  Future<bool> deleteGroup(String groupId) async {
    _groups.remove(groupId);
    notifyListeners();
    return true;
  }

  // === USUARIOS ===

  Future<ChatUser?> getUserInfo(String odp) async {
    return _users[odp];
  }

  Future<List<ChatUser>> getContacts() async {
    return _users.values.toList();
  }

  Future<List<ChatUser>> searchUsers(String query) async {
    return _users.values
        .where((u) => u.name.toLowerCase().contains(query.toLowerCase()))
        .toList();
  }

  Future<List<ChatGroup>> searchGroups(String query) async {
    return _groups.values
        .where((g) => g.name.toLowerCase().contains(query.toLowerCase()))
        .toList();
  }

  Future<List<ChatGroup>> getCommonGroups(String userId) async {
    // TODO: Llamar API real para obtener grupos en común
    // Por ahora devuelve grupos donde ambos usuarios son miembros
    return _groups.values.toList();
  }

  Future<List<ChatMessage>> searchMessages(String query, {String? conversationId}) async {
    final allMessages = conversationId != null
        ? _messages[conversationId] ?? []
        : _messages.values.expand((m) => m).toList();

    return allMessages
        .where((m) => m.content.toLowerCase().contains(query.toLowerCase()))
        .toList();
  }

  Future<bool> isUserBlocked(String odp) async {
    return _blockedUsers.contains(odp);
  }

  Future<bool> blockUser(String odp) async {
    _blockedUsers.add(odp);
    notifyListeners();
    return true;
  }

  Future<bool> unblockUser(String odp) async {
    _blockedUsers.remove(odp);
    notifyListeners();
    return true;
  }

  // === ESTADOS ===

  Future<List<ChatStatus>> getStatuses() async {
    return _statuses.where((s) => !s.isExpired).toList();
  }

  Future<List<ChatStatus>> getMyStatuses() async {
    return _statuses
        .where((s) => s.userId == _currentUser?.id && !s.isExpired)
        .toList();
  }

  Future<ChatStatus?> createStatus({
    required StatusType type,
    required String content,
    String? mediaUrl,
    String? backgroundColor,
  }) async {
    final status = ChatStatus(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      odp: _currentUser?.id ?? '',
      userName: _currentUser?.name ?? 'Yo',
      userAvatar: _currentUser?.avatarUrl,
      type: type,
      content: content,
      mediaUrl: mediaUrl,
      backgroundColor: backgroundColor,
      createdAt: DateTime.now(),
      expiresAt: DateTime.now().add(const Duration(hours: 24)),
    );
    _statuses.add(status);
    notifyListeners();
    return status;
  }

  Future<bool> deleteStatus(String statusId) async {
    _statuses.removeWhere((s) => s.id == statusId);
    notifyListeners();
    return true;
  }

  Future<bool> viewStatus(String statusId) async {
    // TODO: Registrar vista
    return true;
  }

  // === LLAMADAS ===

  Future<void> startCall(String odp, {bool isVideo = false}) async {
    // TODO: Implementar con WebRTC
    debugPrint('[ChatService] Iniciando llamada ${isVideo ? 'video' : 'voz'} a $odp');
  }

  // === REACCIONES ===

  Future<bool> addReaction(String conversationId, String messageId, String emoji) async {
    final messages = _messages[conversationId];
    if (messages != null) {
      final index = messages.indexWhere((m) => m.id == messageId);
      if (index != -1) {
        final msg = messages[index];
        final reactions = List<String>.from(msg.reactions);
        if (!reactions.contains(emoji)) {
          reactions.add(emoji);
          messages[index] = msg.copyWith(reactions: reactions);
        }
      }
    }
    notifyListeners();
    return true;
  }

  Future<bool> removeReaction(String conversationId, String messageId, String emoji) async {
    final messages = _messages[conversationId];
    if (messages != null) {
      final index = messages.indexWhere((m) => m.id == messageId);
      if (index != -1) {
        final msg = messages[index];
        final reactions = List<String>.from(msg.reactions);
        reactions.remove(emoji);
        messages[index] = msg.copyWith(reactions: reactions);
      }
    }
    notifyListeners();
    return true;
  }
}
