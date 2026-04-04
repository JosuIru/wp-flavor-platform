import '../../../../core/api/api_client.dart';
import '../../../../core/services/chat_service.dart';

class ChatLegacyBridge {
  const ChatLegacyBridge._();

  static Future<List<ChatMessage>> loadLegacyGroupMessages(
    ApiClient apiClient, {
    required int groupId,
    required String conversationId,
  }) async {
    final response = await apiClient.getChatGrupoMensajes(grupoId: groupId);
    if (!response.success || response.data == null) {
      throw Exception(response.error ?? 'Error al cargar mensajes');
    }

    return _mapLegacyGroupMessages(
      (response.data!['mensajes'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .toList(),
      conversationId: conversationId,
    );
  }

  static Future<List<ChatMessage>> loadLegacyInternalMessages(
    ApiClient apiClient, {
    required int conversationLegacyId,
    required String conversationId,
    String? currentUserId,
  }) async {
    final response = await apiClient.getChatInternoMensajes(
      conversacionId: conversationLegacyId,
    );
    if (!response.success || response.data == null) {
      throw Exception(response.error ?? 'Error al cargar mensajes');
    }

    return _mapLegacyInternalMessages(
      (response.data!['mensajes'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .toList(),
      conversationId: conversationId,
      currentUserId: currentUserId,
    );
  }

  static Future<void> sendLegacyGroupMessage(
    ApiClient apiClient, {
    required int groupId,
    required String text,
    List<Map<String, dynamic>>? adjuntos,
    int? replyToLegacyId,
  }) async {
    final response = await apiClient.sendChatGrupoMensaje(
      grupoId: groupId,
      mensaje: text,
      adjuntos: adjuntos,
      respondeA: replyToLegacyId,
    );
    if (!response.success) {
      throw Exception(response.error ?? 'Error al enviar mensaje');
    }
  }

  static Future<void> sendLegacyInternalMessage(
    ApiClient apiClient, {
    required int conversationLegacyId,
    required String text,
    String tipo = 'texto',
    Map<String, dynamic>? adjuntos,
    int? replyToLegacyId,
  }) async {
    final response = await apiClient.sendChatInternoMensaje(
      conversacionId: conversationLegacyId,
      mensaje: text,
      tipo: tipo,
      adjuntos: adjuntos,
      respondeA: replyToLegacyId,
    );
    if (!response.success) {
      throw Exception(response.error ?? 'Error al enviar mensaje');
    }
  }

  static Future<void> sendLegacyGroupImage(
    ApiClient apiClient, {
    required int groupId,
    required dynamic image,
    int? replyToLegacyId,
  }) async {
    final upload = await apiClient.uploadSingleImage(image, context: 'chat_legacy_group');
    if (!upload.success || upload.data == null) {
      throw Exception(upload.error ?? 'Error al subir imagen');
    }

    final imageData = upload.data!;
    await sendLegacyGroupMessage(
      apiClient,
      groupId: groupId,
      text: '',
      adjuntos: [
        {
          'id': imageData['id'],
          'url': imageData['url'],
          'nombre': imageData['url']?.toString().split('/').last,
          'es_imagen': true,
          'thumbnail': imageData['thumbnail'],
          'medium': imageData['medium'],
        },
      ],
      replyToLegacyId: replyToLegacyId,
    );
  }

  static Future<void> sendLegacyInternalImage(
    ApiClient apiClient, {
    required int conversationLegacyId,
    required dynamic image,
    int? replyToLegacyId,
  }) async {
    final upload = await apiClient.uploadSingleImage(image, context: 'chat_legacy_internal');
    if (!upload.success || upload.data == null) {
      throw Exception(upload.error ?? 'Error al subir imagen');
    }

    final imageData = upload.data!;
    await sendLegacyInternalMessage(
      apiClient,
      conversationLegacyId: conversationLegacyId,
      text: '',
      tipo: 'imagen',
      adjuntos: {
        'url': imageData['url'],
        'nombre': imageData['url']?.toString().split('/').last,
        'tamano': 0,
        'tipo': 'image/jpeg',
      },
      replyToLegacyId: replyToLegacyId,
    );
  }

  static Future<void> sendLegacyGroupAudio(
    ApiClient apiClient, {
    required int groupId,
    required String audioPath,
    int? replyToLegacyId,
  }) async {
    final upload = await apiClient.uploadSingleFile(
      audioPath,
      context: 'chat_legacy_group',
    );
    if (!upload.success || upload.data == null) {
      throw Exception(upload.error ?? 'Error al subir audio');
    }

    final audioData = upload.data!;
    await sendLegacyGroupMessage(
      apiClient,
      groupId: groupId,
      text: '',
      adjuntos: [
        {
          'id': audioData['id'],
          'url': audioData['url'],
          'nombre': audioData['filename'] ?? audioData['url']?.toString().split('/').last,
          'tamano': audioData['size'] ?? 0,
          'tipo': audioData['mime_type'] ?? 'audio/m4a',
          'es_audio': audioData['is_audio'] == true,
          'es_imagen': audioData['is_image'] == true,
        },
      ],
      replyToLegacyId: replyToLegacyId,
    );
  }

  static Future<void> sendLegacyInternalAudio(
    ApiClient apiClient, {
    required int conversationLegacyId,
    required String audioPath,
    int? replyToLegacyId,
  }) async {
    final upload = await apiClient.uploadSingleFile(
      audioPath,
      context: 'chat_legacy_internal',
    );
    if (!upload.success || upload.data == null) {
      throw Exception(upload.error ?? 'Error al subir audio');
    }

    final audioData = upload.data!;
    await sendLegacyInternalMessage(
      apiClient,
      conversationLegacyId: conversationLegacyId,
      text: '',
      tipo: 'audio',
      adjuntos: {
        'url': audioData['url'],
        'nombre': audioData['filename'] ?? audioData['url']?.toString().split('/').last,
        'tamano': audioData['size'] ?? 0,
        'tipo': audioData['mime_type'] ?? 'audio/m4a',
      },
      replyToLegacyId: replyToLegacyId,
    );
  }

  static Future<void> sendLegacyGroupFile(
    ApiClient apiClient, {
    required int groupId,
    required dynamic file,
    int? replyToLegacyId,
  }) async {
    final upload = await apiClient.uploadSingleFile(
      file,
      context: 'chat_legacy_group',
    );
    if (!upload.success || upload.data == null) {
      throw Exception(upload.error ?? 'Error al subir archivo');
    }

    final fileData = upload.data!;
    await sendLegacyGroupMessage(
      apiClient,
      groupId: groupId,
      text: '',
      adjuntos: [
        {
          'id': fileData['id'],
          'url': fileData['url'],
          'nombre': fileData['filename'] ?? fileData['url']?.toString().split('/').last,
          'tamano': fileData['size'] ?? 0,
          'tipo': fileData['mime_type'] ?? 'application/octet-stream',
          'es_audio': fileData['is_audio'] == true,
          'es_imagen': fileData['is_image'] == true,
        },
      ],
      replyToLegacyId: replyToLegacyId,
    );
  }

  static Future<void> sendLegacyInternalFile(
    ApiClient apiClient, {
    required int conversationLegacyId,
    required dynamic file,
    int? replyToLegacyId,
  }) async {
    final upload = await apiClient.uploadSingleFile(
      file,
      context: 'chat_legacy_internal',
    );
    if (!upload.success || upload.data == null) {
      throw Exception(upload.error ?? 'Error al subir archivo');
    }

    final fileData = upload.data!;
    await sendLegacyInternalMessage(
      apiClient,
      conversationLegacyId: conversationLegacyId,
      text: '',
      tipo: 'archivo',
      adjuntos: {
        'url': fileData['url'],
        'nombre': fileData['filename'] ?? fileData['url']?.toString().split('/').last,
        'tamano': fileData['size'] ?? 0,
        'tipo': fileData['mime_type'] ?? 'application/octet-stream',
      },
      replyToLegacyId: replyToLegacyId,
    );
  }

  static Future<void> sendLegacyInternalLocation(
    ApiClient apiClient, {
    required int conversationLegacyId,
    required double latitude,
    required double longitude,
    String? address,
    int? replyToLegacyId,
  }) async {
    final mapUrl =
        'https://www.google.com/maps/search/?api=1&query=$latitude,$longitude';
    final label = address?.trim().isNotEmpty == true
        ? address!.trim()
        : 'Ubicación compartida';

    await sendLegacyInternalMessage(
      apiClient,
      conversationLegacyId: conversationLegacyId,
      text: label,
      tipo: 'ubicacion',
      adjuntos: {
        'url': mapUrl,
        'nombre': '$latitude,$longitude',
        'tamano': 0,
        'tipo': 'application/location',
      },
      replyToLegacyId: replyToLegacyId,
    );
  }

  static List<ChatMessage> _mapLegacyGroupMessages(
    List<Map<String, dynamic>> rawMessages, {
    required String conversationId,
  }) {
    final indexed = <String, ChatMessage>{};
    final ordered = <ChatMessage>[];

    for (final m in rawMessages) {
      final senderId = (m['autor_id'] ?? m['sender_id'] ?? '').toString();
      final attachments =
          (m['adjuntos'] as List<dynamic>? ?? const []).whereType<Map<String, dynamic>>().toList();
      final firstAttachment = attachments.isNotEmpty ? attachments.first : null;
      final isImage = firstAttachment?['es_imagen'] == true;
      final isAudio = firstAttachment?['es_audio'] == true ||
          (firstAttachment?['tipo']?.toString().startsWith('audio/') ?? false);
      final id = (m['id'] ?? DateTime.now().microsecondsSinceEpoch).toString();

      final message = ChatMessage(
        id: id,
        conversationId: conversationId,
        senderId: senderId,
        senderName: m['autor_nombre']?.toString() ??
            m['sender_name']?.toString() ??
            'Usuario',
        type: isImage
            ? MessageType.image
            : isAudio
                ? MessageType.audio
            : firstAttachment != null
                ? MessageType.file
                : MessageType.text,
        content: m['mensaje']?.toString() ?? '',
        mediaUrl: firstAttachment?['url']?.toString(),
        metadata: firstAttachment == null
            ? null
            : {
                'url': firstAttachment['url'],
                'thumbnail': firstAttachment['thumbnail'] ??
                    firstAttachment['medium'] ??
                    firstAttachment['url'],
                'filename': firstAttachment['nombre'],
                'size': firstAttachment['tamano'],
                'contentType': firstAttachment['tipo'],
              },
        timestamp: DateTime.tryParse(
              m['fecha']?.toString() ??
                  m['created_at']?.toString() ??
                  m['timestamp']?.toString() ??
                  '',
            ) ??
            DateTime.now(),
        status: MessageStatus.sent,
        replyToId: m['responde_a']?.toString(),
      );

      indexed[id] = message;
      ordered.add(message);
    }

    final linked = ordered
        .map((message) => ChatMessage(
              id: message.id,
              conversationId: message.conversationId,
              senderId: message.senderId,
              senderName: message.senderName,
              senderAvatar: message.senderAvatar,
              type: message.type,
              content: message.content,
              mediaUrl: message.mediaUrl,
              thumbnailUrl: message.thumbnailUrl,
              mediaDuration: message.mediaDuration,
              metadata: message.metadata,
              timestamp: message.timestamp,
              status: message.status,
              replyToId: message.replyToId,
              replyTo: indexed[message.replyToId],
              reactions: message.reactions,
              isEdited: message.isEdited,
              isDeleted: message.isDeleted,
              isPinned: message.isPinned,
            ))
        .toList();

    linked.sort((a, b) => a.timestamp.compareTo(b.timestamp));
    return linked;
  }

  static List<ChatMessage> _mapLegacyInternalMessages(
    List<Map<String, dynamic>> rawMessages, {
    required String conversationId,
    String? currentUserId,
  }) {
    final indexed = <String, ChatMessage>{};
    final ordered = <ChatMessage>[];

    for (final m in rawMessages) {
      final senderId = (m['remitente_id'] ?? m['sender_id'] ?? '').toString();
      final content =
          m['mensaje']?.toString() ?? m['texto_descifrado']?.toString() ?? '';
      final attachment = m['adjunto'] is Map<String, dynamic>
          ? m['adjunto'] as Map<String, dynamic>
          : null;
      final id = (m['id'] ?? DateTime.now().microsecondsSinceEpoch).toString();
      final backendType = m['tipo']?.toString();
      final parsedLocation = _parseLegacyLocation(attachment, content);
      final type = backendType == 'ubicacion'
          ? MessageType.location
          : attachment == null
              ? MessageType.text
              : attachment['es_imagen'] == true
                  ? MessageType.image
                  : attachment['es_audio'] == true
                      ? MessageType.audio
                      : MessageType.file;
      final replyData = m['responde_a'] is Map<String, dynamic>
          ? m['responde_a'] as Map<String, dynamic>
          : null;

      final message = ChatMessage(
        id: id,
        conversationId: conversationId,
        senderId: senderId,
        senderName: m['remitente_nombre']?.toString() ??
            m['autor_nombre']?.toString() ??
            'Usuario',
        type: type,
        content: content,
        mediaUrl: attachment?['url']?.toString(),
        metadata: attachment == null
            ? parsedLocation
            : {
                'url': attachment['url'],
                'filename': attachment['nombre'],
                'size': attachment['tamano'],
                'contentType': attachment['tipo'],
                if (parsedLocation != null) ...parsedLocation,
              },
        timestamp: DateTime.tryParse(
              m['fecha']?.toString() ??
                  m['created_at']?.toString() ??
                  m['timestamp']?.toString() ??
                  '',
            ) ??
            DateTime.now(),
        status: senderId == currentUserId
            ? MessageStatus.read
            : MessageStatus.sent,
        replyToId: replyData?['id']?.toString(),
        replyTo: replyData == null
            ? null
            : ChatMessage(
                id: replyData['id']?.toString() ?? '',
                conversationId: conversationId,
                senderId: '',
                senderName: replyData['autor']?.toString() ?? 'Usuario',
                type: _mapLegacyReplyType(replyData['tipo']?.toString()),
                content: replyData['mensaje']?.toString() ?? '',
                timestamp: DateTime.now(),
              ),
      );

      indexed[id] = message;
      ordered.add(message);
    }

    final linked = ordered
        .map((message) => ChatMessage(
              id: message.id,
              conversationId: message.conversationId,
              senderId: message.senderId,
              senderName: message.senderName,
              senderAvatar: message.senderAvatar,
              type: message.type,
              content: message.content,
              mediaUrl: message.mediaUrl,
              thumbnailUrl: message.thumbnailUrl,
              mediaDuration: message.mediaDuration,
              metadata: message.metadata,
              timestamp: message.timestamp,
              status: message.status,
              replyToId: message.replyToId,
              replyTo: indexed[message.replyToId] ?? message.replyTo,
              reactions: message.reactions,
              isEdited: message.isEdited,
              isDeleted: message.isDeleted,
              isPinned: message.isPinned,
            ))
        .toList();

    linked.sort((a, b) => a.timestamp.compareTo(b.timestamp));
    return linked;
  }

  static MessageType _mapLegacyReplyType(String? type) {
    switch (type) {
      case 'imagen':
      case 'image':
        return MessageType.image;
      case 'audio':
        return MessageType.audio;
      case 'archivo':
      case 'file':
        return MessageType.file;
      case 'ubicacion':
      case 'location':
        return MessageType.location;
      default:
        return MessageType.text;
    }
  }

  static Map<String, dynamic>? _parseLegacyLocation(
    Map<String, dynamic>? attachment,
    String content,
  ) {
    final rawUrl = attachment?['url']?.toString() ?? '';
    if (rawUrl.isEmpty) {
      return null;
    }

    final uri = Uri.tryParse(rawUrl);
    if (uri == null) {
      return null;
    }

    String? query = uri.queryParameters['query'];
    query ??= uri.queryParameters['q'];
    if (query == null || !query.contains(',')) {
      return null;
    }

    final parts = query.split(',');
    if (parts.length != 2) {
      return null;
    }

    final lat = double.tryParse(parts[0].trim());
    final lng = double.tryParse(parts[1].trim());
    if (lat == null || lng == null) {
      return null;
    }

    return {
      'latitude': lat,
      'longitude': lng,
      'address': content,
      'url': rawUrl,
    };
  }
}
