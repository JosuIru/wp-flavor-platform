import 'package:flutter/material.dart';
import '../../../../core/widgets/flavor_initials_avatar.dart';
import '../../../../core/widgets/flavor_image_viewer.dart';

class RedSocialPostCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final VoidCallback onLikeToggle;
  final VoidCallback onComment;
  final VoidCallback onShare;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onReport;

  const RedSocialPostCard({
    super.key,
    required this.item,
    required this.onLikeToggle,
    required this.onComment,
    required this.onShare,
    required this.onEdit,
    required this.onDelete,
    required this.onReport,
  });

  @override
  Widget build(BuildContext context) {
    final publicacionId = item['id']?.toString() ?? '';
    final autor =
        item['autor'] ?? item['author'] ?? item['usuario'] ?? 'Usuario';
    final contenido =
        item['contenido'] ?? item['content'] ?? item['texto'] ?? '';
    final fecha =
        item['fecha'] ?? item['date'] ?? item['created_at'] ?? '';
    final likes = item['likes'] ?? item['me_gusta'] ?? 0;
    final comentarios = item['comentarios'] ?? item['comments'] ?? 0;
    final imagen = item['imagen'] ?? item['image'] ?? '';
    final meGusta = item['me_gusta_user'] == true;
    final esMio = item['es_mio'] == true;

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                FlavorInitialsAvatar(
                  name: autor.toString(),
                  backgroundColor: Colors.blue.shade100,
                  textStyle: const TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        autor.toString(),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      if (fecha.toString().isNotEmpty)
                        Text(
                          fecha.toString(),
                          style:
                              TextStyle(fontSize: 12, color: Colors.grey[600]),
                        ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (value) {
                    if (value == 'editar') {
                      onEdit();
                    } else if (value == 'eliminar') {
                      onDelete();
                    } else if (value == 'reportar') {
                      onReport();
                    }
                  },
                  itemBuilder: (context) => [
                    if (esMio) ...[
                      const PopupMenuItem(
                        value: 'editar',
                        child: Text('Editar'),
                      ),
                      const PopupMenuItem(
                        value: 'eliminar',
                        child: Text(
                          'Eliminar',
                          style: TextStyle(color: Colors.red),
                        ),
                      ),
                    ] else
                      const PopupMenuItem(
                        value: 'reportar',
                        child: Text('Reportar'),
                      ),
                  ],
                ),
              ],
            ),
          ),
          if (contenido.toString().isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Text(contenido.toString()),
            ),
          if (imagen.toString().isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: InkWell(
                onTap: () => _openImageViewer(context, imagen.toString()),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: Image.network(
                    imagen.toString(),
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                  ),
                ),
              ),
            ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                if (likes > 0) ...[
                  Icon(Icons.thumb_up, size: 14, color: Colors.blue.shade600),
                  const SizedBox(width: 4),
                  Text(
                    '$likes',
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontSize: 13,
                    ),
                  ),
                ],
                if (likes > 0 && comentarios > 0) const SizedBox(width: 16),
                if (comentarios > 0)
                  Text(
                    '$comentarios comentarios',
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontSize: 13,
                    ),
                  ),
              ],
            ),
          ),
          const Divider(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Row(
              children: [
                Expanded(
                  child: TextButton.icon(
                    onPressed: onLikeToggle,
                    icon: Icon(
                      meGusta ? Icons.thumb_up : Icons.thumb_up_outlined,
                      size: 20,
                      color: meGusta ? Colors.blue : null,
                    ),
                    label: Text(
                      'Me gusta',
                      style: TextStyle(color: meGusta ? Colors.blue : null),
                    ),
                  ),
                ),
                Expanded(
                  child: TextButton.icon(
                    onPressed: onComment,
                    icon: const Icon(Icons.comment_outlined, size: 20),
                    label: const Text('Comentar'),
                  ),
                ),
                Expanded(
                  child: TextButton.icon(
                    onPressed: onShare,
                    icon: const Icon(Icons.share_outlined, size: 20),
                    label: const Text('Compartir'),
                  ),
                ),
              ],
            ),
          ),
          if (publicacionId.isNotEmpty) const SizedBox(height: 4),
        ],
      ),
    );
  }

  void _openImageViewer(BuildContext context, String imageUrl) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => FlavorImageViewer(
          images: [
            FlavorImageViewerItem(url: imageUrl),
          ],
        ),
      ),
    );
  }
}
