import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../../core/utils/flavor_url_launcher.dart';
import '../../../../core/widgets/flavor_image_viewer.dart';
import '../../../../core/widgets/flavor_state_widgets.dart';
import '../../../../core/services/chat_service.dart';

/// Widget de burbuja de mensaje tipo WhatsApp/Telegram
class MessageBubble extends StatelessWidget {
  final ChatMessage message;
  final bool isMe;
  final bool isSelected;
  final VoidCallback? onTap;
  final VoidCallback? onLongPress;
  final VoidCallback? onReplyTap;

  const MessageBubble({
    super.key,
    required this.message,
    required this.isMe,
    this.isSelected = false,
    this.onTap,
    this.onLongPress,
    this.onReplyTap,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return GestureDetector(
      onTap: onTap,
      onLongPress: onLongPress,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 2),
        padding: isSelected
            ? const EdgeInsets.all(4)
            : EdgeInsets.zero,
        decoration: BoxDecoration(
          color: isSelected
              ? colorScheme.primary.withOpacity(0.1)
              : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Align(
          alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
          child: Container(
            constraints: BoxConstraints(
              maxWidth: MediaQuery.of(context).size.width * 0.75,
            ),
            margin: EdgeInsets.only(
              left: isMe ? 48 : 0,
              right: isMe ? 0 : 48,
            ),
            child: Column(
              crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                // Respuesta a otro mensaje
                if (message.replyTo != null)
                  GestureDetector(
                    onTap: onReplyTap,
                    child: Container(
                      margin: const EdgeInsets.only(bottom: 4),
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: colorScheme.surfaceContainerHighest,
                        borderRadius: BorderRadius.circular(8),
                        border: Border(
                          left: BorderSide(
                            color: colorScheme.primary,
                            width: 3,
                          ),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            message.replyTo!.senderName,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: colorScheme.primary,
                            ),
                          ),
                          Text(
                            message.replyTo!.content,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                  ),

                // Contenido principal
                _buildMessageContent(context, colorScheme),

                // Reacciones
                if (message.reactions.isNotEmpty)
                  _buildReactions(context),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildMessageContent(BuildContext context, ColorScheme colorScheme) {
    // Mensaje eliminado
    if (message.isDeleted) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: colorScheme.surfaceContainerHighest,
          borderRadius: _getBorderRadius(),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.block, size: 16, color: Colors.grey[600]),
            const SizedBox(width: 6),
            Text(
              'Mensaje eliminado',
              style: TextStyle(
                fontStyle: FontStyle.italic,
                color: Colors.grey[600],
              ),
            ),
          ],
        ),
      );
    }

    switch (message.type) {
      case MessageType.image:
        return _buildImageMessage(context, colorScheme);
      case MessageType.video:
        return _buildVideoMessage(context, colorScheme);
      case MessageType.voice:
        return _buildVoiceMessage(context, colorScheme);
      case MessageType.audio:
        return _buildAudioMessage(context, colorScheme);
      case MessageType.file:
        return _buildFileMessage(context, colorScheme);
      case MessageType.location:
        return _buildLocationMessage(context, colorScheme);
      case MessageType.contact:
        return _buildContactMessage(context, colorScheme);
      case MessageType.sticker:
        return _buildStickerMessage(context);
      case MessageType.poll:
        return _buildPollMessage(context, colorScheme);
      case MessageType.system:
        return _buildSystemMessage(context);
      default:
        return _buildTextMessage(context, colorScheme);
    }
  }

  Widget _buildTextMessage(BuildContext context, ColorScheme colorScheme) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: isMe
            ? colorScheme.primary.withOpacity(0.15)
            : colorScheme.surfaceContainerHighest,
        borderRadius: _getBorderRadius(),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Nombre del remitente (solo en grupos)
          if (!isMe && message.senderName.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Text(
                message.senderName,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: colorScheme.primary,
                ),
              ),
            ),

          // Texto del mensaje
          Text(
            message.content,
            style: const TextStyle(fontSize: 15),
          ),

          // Hora y estado
          _buildTimeAndStatus(colorScheme),
        ],
      ),
    );
  }

  Widget _buildImageMessage(BuildContext context, ColorScheme colorScheme) {
    final imageUrl = message.metadata?['url'] as String? ?? message.mediaUrl;
    final thumbnail = message.metadata?['thumbnail'] as String?;

    return ClipRRect(
      borderRadius: _getBorderRadius(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (imageUrl != null)
            GestureDetector(
              onTap: () => _openImageViewer(context, imageUrl),
              child: CachedNetworkImage(
                imageUrl: thumbnail ?? imageUrl,
                width: 250,
                height: 200,
                fit: BoxFit.cover,
                placeholder: (_, __) => Container(
                  width: 250,
                  height: 200,
                  color: Colors.grey[300],
                  child: const FlavorLoadingState(),
                ),
                errorWidget: (_, __, ___) => Container(
                  width: 250,
                  height: 200,
                  color: Colors.grey[300],
                  child: const Icon(Icons.broken_image),
                ),
              ),
            ),
          if (message.content.isNotEmpty)
            Container(
              padding: const EdgeInsets.all(10),
              color: isMe
                  ? colorScheme.primary.withOpacity(0.15)
                  : colorScheme.surfaceContainerHighest,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(message.content),
                  _buildTimeAndStatus(colorScheme),
                ],
              ),
            )
          else
            Container(
              padding: const EdgeInsets.all(6),
              color: isMe
                  ? colorScheme.primary.withOpacity(0.15)
                  : colorScheme.surfaceContainerHighest,
              child: _buildTimeAndStatus(colorScheme),
            ),
        ],
      ),
    );
  }

  Widget _buildVideoMessage(BuildContext context, ColorScheme colorScheme) {
    final thumbnail = message.metadata?['thumbnail'] as String?;

    return ClipRRect(
      borderRadius: _getBorderRadius(),
      child: Stack(
        alignment: Alignment.center,
        children: [
          if (thumbnail != null)
            CachedNetworkImage(
              imageUrl: thumbnail,
              width: 250,
              height: 200,
              fit: BoxFit.cover,
            )
          else
            Container(
              width: 250,
              height: 200,
              color: Colors.black,
            ),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: const BoxDecoration(
              color: Colors.black54,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.play_arrow, color: Colors.white, size: 32),
          ),
          Positioned(
            bottom: 8,
            right: 8,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: Colors.black54,
                borderRadius: BorderRadius.circular(4),
              ),
              child: _buildTimeAndStatus(colorScheme, light: true),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVoiceMessage(BuildContext context, ColorScheme colorScheme) {
    final duration = message.metadata?['duration'] as int? ?? 0;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: isMe
            ? colorScheme.primary.withOpacity(0.15)
            : colorScheme.surfaceContainerHighest,
        borderRadius: _getBorderRadius(),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          CircleAvatar(
            radius: 20,
            backgroundColor: colorScheme.primary,
            child: const Icon(Icons.play_arrow, color: Colors.white),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Forma de onda (simplificada)
              Container(
                width: 150,
                height: 30,
                decoration: BoxDecoration(
                  color: colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: CustomPaint(
                  painter: WaveformPainter(color: colorScheme.primary),
                ),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  Text(
                    _formatDuration(duration),
                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                  const SizedBox(width: 8),
                  _buildTimeAndStatus(colorScheme),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAudioMessage(BuildContext context, ColorScheme colorScheme) {
    final filename = message.metadata?['filename'] as String? ?? 'Audio';
    final audioUrl = message.metadata?['url'] as String? ?? message.mediaUrl;

    return InkWell(
      onTap: audioUrl == null ? null : () => _openExternalUrl(context, audioUrl),
      borderRadius: _getBorderRadius(),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isMe
              ? colorScheme.primary.withOpacity(0.15)
              : colorScheme.surfaceContainerHighest,
          borderRadius: _getBorderRadius(),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.audiotrack, size: 32),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(filename, style: const TextStyle(fontWeight: FontWeight.w500)),
                if (audioUrl != null)
                  const Text(
                    'Toca para abrir',
                    style: TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                _buildTimeAndStatus(colorScheme),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFileMessage(BuildContext context, ColorScheme colorScheme) {
    final filename = message.metadata?['filename'] as String? ?? 'Archivo';
    final size = message.metadata?['size'] as int? ?? 0;
    final fileUrl = message.metadata?['url'] as String? ?? message.mediaUrl;

    return InkWell(
      onTap: fileUrl == null ? null : () => _openExternalUrl(context, fileUrl),
      borderRadius: _getBorderRadius(),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isMe
              ? colorScheme.primary.withOpacity(0.15)
              : colorScheme.surfaceContainerHighest,
          borderRadius: _getBorderRadius(),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: colorScheme.primary.withOpacity(0.2),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(Icons.insert_drive_file, color: colorScheme.primary),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  filename,
                  style: const TextStyle(fontWeight: FontWeight.w500),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  _formatFileSize(size),
                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                ),
                if (fileUrl != null)
                  const Text(
                    'Toca para abrir',
                    style: TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                _buildTimeAndStatus(colorScheme),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLocationMessage(BuildContext context, ColorScheme colorScheme) {
    final lat = message.metadata?['latitude'] as double?;
    final lng = message.metadata?['longitude'] as double?;
    final address = message.metadata?['address'] as String?;
    final hasCoordinates = lat != null && lng != null;

    return InkWell(
      onTap: hasCoordinates ? () => _openMapLocation(context, lat, lng) : null,
      borderRadius: _getBorderRadius(),
      child: ClipRRect(
        borderRadius: _getBorderRadius(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 250,
              height: 150,
              color: Colors.grey[300],
              child: Stack(
                alignment: Alignment.center,
                children: [
                  if (hasCoordinates)
                    const Icon(Icons.map, size: 64, color: Colors.grey),
                  const Icon(Icons.location_on, size: 48, color: Colors.red),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.all(10),
              color: isMe
                  ? colorScheme.primary.withOpacity(0.15)
                  : colorScheme.surfaceContainerHighest,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (address != null) ...[
                    Text(address, style: const TextStyle(fontWeight: FontWeight.w500)),
                    const SizedBox(height: 4),
                  ],
                  if (hasCoordinates)
                    const Text(
                      'Toca para abrir en mapas',
                      style: TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                  _buildTimeAndStatus(colorScheme),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContactMessage(BuildContext context, ColorScheme colorScheme) {
    final name = message.metadata?['name'] as String? ?? message.content;
    final phone = message.metadata?['phone'] as String?;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isMe
            ? colorScheme.primary.withOpacity(0.15)
            : colorScheme.surfaceContainerHighest,
        borderRadius: _getBorderRadius(),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          CircleAvatar(
            child: Text(name[0].toUpperCase()),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(name, style: const TextStyle(fontWeight: FontWeight.w500)),
              if (phone != null)
                Text(phone, style: const TextStyle(fontSize: 13, color: Colors.grey)),
              _buildTimeAndStatus(colorScheme),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStickerMessage(BuildContext context) {
    final url = message.metadata?['url'] as String?;

    return url != null
        ? CachedNetworkImage(
            imageUrl: url,
            width: 150,
            height: 150,
          )
        : const SizedBox(width: 150, height: 150);
  }

  Widget _buildPollMessage(BuildContext context, ColorScheme colorScheme) {
    // TODO: Implementar encuestas
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isMe
            ? colorScheme.primary.withOpacity(0.15)
            : colorScheme.surfaceContainerHighest,
        borderRadius: _getBorderRadius(),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.poll, size: 20),
              const SizedBox(width: 8),
              Text(message.content, style: const TextStyle(fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: 8),
          const Text('Encuesta - toca para ver'),
          _buildTimeAndStatus(colorScheme),
        ],
      ),
    );
  }

  Widget _buildSystemMessage(BuildContext context) {
    return Center(
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 8),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Text(
          message.content,
          style: const TextStyle(fontSize: 12, color: Colors.grey),
          textAlign: TextAlign.center,
        ),
      ),
    );
  }

  Widget _buildTimeAndStatus(ColorScheme colorScheme, {bool light = false}) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          _formatTime(message.timestamp),
          style: TextStyle(
            fontSize: 11,
            color: light ? Colors.white70 : Colors.grey,
          ),
        ),
        if (message.isEdited) ...[
          const SizedBox(width: 4),
          Text(
            'editado',
            style: TextStyle(
              fontSize: 11,
              color: light ? Colors.white70 : Colors.grey,
            ),
          ),
        ],
        if (isMe) ...[
          const SizedBox(width: 4),
          _buildStatusIcon(light),
        ],
      ],
    );
  }

  Widget _buildStatusIcon(bool light) {
    IconData icon;
    Color color;

    switch (message.status) {
      case MessageStatus.sending:
        icon = Icons.access_time;
        color = light ? Colors.white70 : Colors.grey;
        break;
      case MessageStatus.sent:
        icon = Icons.check;
        color = light ? Colors.white70 : Colors.grey;
        break;
      case MessageStatus.delivered:
        icon = Icons.done_all;
        color = light ? Colors.white70 : Colors.grey;
        break;
      case MessageStatus.read:
        icon = Icons.done_all;
        color = Colors.blue;
        break;
      case MessageStatus.failed:
        icon = Icons.error;
        color = Colors.red;
        break;
    }

    return Icon(icon, size: 14, color: color);
  }

  Widget _buildReactions(BuildContext context) {
    final reactionGroups = <String, int>{};
    for (final emoji in message.reactions) {
      reactionGroups[emoji] = (reactionGroups[emoji] ?? 0) + 1;
    }

    return Container(
      margin: const EdgeInsets.only(top: 4),
      child: Wrap(
        spacing: 4,
        children: reactionGroups.entries.map((entry) {
          return Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surfaceContainerHighest,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(entry.key, style: const TextStyle(fontSize: 14)),
                if (entry.value > 1) ...[
                  const SizedBox(width: 2),
                  Text('${entry.value}', style: const TextStyle(fontSize: 12)),
                ],
              ],
            ),
          );
        }).toList(),
      ),
    );
  }

  BorderRadius _getBorderRadius() {
    return BorderRadius.only(
      topLeft: const Radius.circular(16),
      topRight: const Radius.circular(16),
      bottomLeft: Radius.circular(isMe ? 16 : 4),
      bottomRight: Radius.circular(isMe ? 4 : 16),
    );
  }

  String _formatTime(DateTime time) {
    return '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
  }

  String _formatDuration(int seconds) {
    final minutes = seconds ~/ 60;
    final secs = seconds % 60;
    return '$minutes:${secs.toString().padLeft(2, '0')}';
  }

  String _formatFileSize(int bytes) {
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(1)} KB';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
  }

  void _openImageViewer(BuildContext context, String url) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => FlavorImageViewer(
          images: [
            FlavorImageViewerItem(url: url),
          ],
        ),
      ),
    );
  }

  Future<void> _openExternalUrl(BuildContext context, String url) async {
    await FlavorUrlLauncher.openExternal(
      context,
      url,
      errorMessage: 'No se pudo abrir el archivo.',
    );
  }

  Future<void> _openMapLocation(BuildContext context, double lat, double lng) async {
    final uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=$lat,$lng');
    await FlavorUrlLauncher.openExternalUri(
      context,
      uri,
      errorMessage: 'No se pudo abrir la ubicacion.',
    );
  }
}

/// Painter para forma de onda de audio
class WaveformPainter extends CustomPainter {
  final Color color;

  WaveformPainter({required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = 2
      ..strokeCap = StrokeCap.round;

    final random = [0.3, 0.5, 0.8, 0.4, 0.9, 0.6, 0.7, 0.5, 0.3, 0.6, 0.8, 0.4, 0.7, 0.5, 0.9];
    final barWidth = size.width / random.length;

    for (int i = 0; i < random.length; i++) {
      final x = i * barWidth + barWidth / 2;
      final height = size.height * random[i];
      final y1 = (size.height - height) / 2;
      final y2 = y1 + height;

      canvas.drawLine(Offset(x, y1), Offset(x, y2), paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
