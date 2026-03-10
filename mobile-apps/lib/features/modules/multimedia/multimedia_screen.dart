import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class MultimediaScreen extends ConsumerStatefulWidget {
  const MultimediaScreen({super.key});

  @override
  ConsumerState<MultimediaScreen> createState() => _MultimediaScreenState();
}

class _MultimediaScreenState extends ConsumerState<MultimediaScreen> {
  List<dynamic> _galeriaItems = [];
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarDatos();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/multimedia');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _galeriaItems =
              respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar la galería';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Galería Multimedia'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDatos,
          ),
        ],
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.perm_media, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_mensajeError!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarDatos,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _galeriaItems.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.perm_media,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay contenido multimedia disponible'),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: GridView.builder(
                        padding: const EdgeInsets.all(16),
                        gridDelegate:
                            const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                          childAspectRatio: 1.0,
                        ),
                        itemCount: _galeriaItems.length,
                        itemBuilder: (context, indice) =>
                            _construirTarjetaMultimedia(_galeriaItems[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaMultimedia(dynamic elemento) {
    final mapa = elemento as Map<String, dynamic>;
    final titulo =
        mapa['titulo'] ?? mapa['nombre'] ?? mapa['title'] ?? 'Sin título';
    final urlImagen = mapa['imagen'] ?? mapa['thumbnail'] ?? mapa['url'] ?? '';
    final tipoContenido = mapa['tipo'] ?? mapa['type'] ?? 'imagen';

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => _verDetalleMultimedia(context, mapa),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: urlImagen.isNotEmpty
                  ? Image.network(
                      urlImagen,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) => Container(
                        color: Colors.grey.shade200,
                        child: const Icon(Icons.perm_media,
                            size: 48, color: Colors.grey),
                      ),
                    )
                  : Container(
                      color: Colors.grey.shade200,
                      child: const Icon(Icons.perm_media,
                          size: 48, color: Colors.grey),
                    ),
            ),
            Padding(
              padding: const EdgeInsets.all(8),
              child: Row(
                children: [
                  Icon(
                    tipoContenido == 'video'
                        ? Icons.play_circle_outline
                        : Icons.image,
                    size: 16,
                    color: Colors.grey,
                  ),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      titulo,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _verDetalleMultimedia(BuildContext context, Map<String, dynamic> item) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => MultimediaDetalleScreen(item: item),
      ),
    );
  }
}

/// Pantalla de detalle de contenido multimedia
class MultimediaDetalleScreen extends StatelessWidget {
  final Map<String, dynamic> item;

  const MultimediaDetalleScreen({super.key, required this.item});

  @override
  Widget build(BuildContext context) {
    final titulo = item['titulo'] ?? item['nombre'] ?? item['title'] ?? 'Sin título';
    final descripcion = item['descripcion'] ?? item['description'] ?? '';
    final urlMedia = item['url'] ?? item['imagen'] ?? item['video_url'] ?? '';
    final urlImagen = item['imagen'] ?? item['thumbnail'] ?? item['url'] ?? '';
    final tipoContenido = item['tipo'] ?? item['type'] ?? 'imagen';
    final fecha = item['fecha'] ?? item['date'] ?? item['created_at'] ?? '';
    final autor = item['autor'] ?? item['author'] ?? '';
    final categoria = item['categoria'] ?? item['category'] ?? '';
    final esVideo = tipoContenido.toString().toLowerCase() == 'video';

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(
          titulo,
          style: const TextStyle(color: Colors.white),
        ),
      ),
      body: Column(
        children: [
          // Área de visualización
          Expanded(
            flex: 3,
            child: GestureDetector(
              onTap: esVideo ? () => _reproducirVideo(context, urlMedia) : null,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  if (urlImagen.isNotEmpty)
                    Image.network(
                      urlImagen,
                      fit: BoxFit.contain,
                      width: double.infinity,
                      errorBuilder: (context, error, stackTrace) => Container(
                        color: Colors.grey.shade900,
                        child: const Center(
                          child: Icon(Icons.broken_image, size: 64, color: Colors.grey),
                        ),
                      ),
                      loadingBuilder: (context, child, loadingProgress) {
                        if (loadingProgress == null) return child;
                        return Center(
                          child: CircularProgressIndicator(
                            value: loadingProgress.expectedTotalBytes != null
                                ? loadingProgress.cumulativeBytesLoaded /
                                    loadingProgress.expectedTotalBytes!
                                : null,
                            color: Colors.white,
                          ),
                        );
                      },
                    )
                  else
                    Container(
                      color: Colors.grey.shade900,
                      child: const Center(
                        child: Icon(Icons.perm_media, size: 64, color: Colors.grey),
                      ),
                    ),
                  // Indicador de video
                  if (esVideo)
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.black54,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.play_arrow,
                        color: Colors.white,
                        size: 48,
                      ),
                    ),
                ],
              ),
            ),
          ),
          // Panel de información
          Expanded(
            flex: 2,
            child: Container(
              width: double.infinity,
              decoration: BoxDecoration(
                color: Theme.of(context).scaffoldBackgroundColor,
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(24),
                ),
              ),
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Handle de arrastre
                    Center(
                      child: Container(
                        width: 40,
                        height: 4,
                        margin: const EdgeInsets.only(bottom: 16),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade300,
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    // Título
                    Text(
                      titulo,
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 8),
                    // Metadata
                    Wrap(
                      spacing: 16,
                      runSpacing: 8,
                      children: [
                        if (tipoContenido.isNotEmpty)
                          Chip(
                            avatar: Icon(
                              esVideo ? Icons.videocam : Icons.image,
                              size: 16,
                            ),
                            label: Text(tipoContenido.toString().toUpperCase()),
                            visualDensity: VisualDensity.compact,
                          ),
                        if (categoria.isNotEmpty)
                          Chip(
                            avatar: const Icon(Icons.folder, size: 16),
                            label: Text(categoria),
                            visualDensity: VisualDensity.compact,
                          ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    // Info adicional
                    if (autor.isNotEmpty)
                      Row(
                        children: [
                          const Icon(Icons.person, size: 18, color: Colors.grey),
                          const SizedBox(width: 8),
                          Text(
                            'Por $autor',
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                    if (fecha.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today, size: 18, color: Colors.grey),
                          const SizedBox(width: 8),
                          Text(
                            _formatDate(fecha),
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                    ],
                    // Descripción
                    if (descripcion.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      const Divider(),
                      const SizedBox(height: 8),
                      Text(
                        descripcion,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ],
                    const SizedBox(height: 24),
                    // Botones de acción
                    Row(
                      children: [
                        if (esVideo)
                          Expanded(
                            child: FilledButton.icon(
                              onPressed: () => _reproducirVideo(context, urlMedia),
                              icon: const Icon(Icons.play_arrow),
                              label: const Text('Reproducir'),
                            ),
                          ),
                        if (esVideo) const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () => _compartir(context, titulo, urlMedia),
                            icon: const Icon(Icons.share),
                            label: const Text('Compartir'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _reproducirVideo(BuildContext context, String urlVideo) {
    if (urlVideo.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('URL del video no disponible')),
      );
      return;
    }
    // En una implementación real, se usaría video_player o chewie
    // Por ahora mostramos un diálogo informativo
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Reproducir video'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Para reproducir el video, se necesita implementar un reproductor de video.'),
            const SizedBox(height: 12),
            Text(
              'URL: $urlVideo',
              style: const TextStyle(fontSize: 12, color: Colors.grey),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cerrar'),
          ),
        ],
      ),
    );
  }

  Future<void> _compartir(BuildContext context, String titulo, String url) async {
    if (url.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('URL no disponible para compartir')),
      );
      return;
    }

    final textoCompartir = '$titulo\n$url';
    await Clipboard.setData(ClipboardData(text: textoCompartir));

    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Enlace copiado al portapapeles'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
    } catch (_) {
      return dateStr;
    }
  }
}
