part of 'agregador_contenido_screen.dart';

// =============================================================================
// TARJETA DE NOTICIA
// =============================================================================

class _NoticiaCard extends StatelessWidget {
  final dynamic noticia;
  final VoidCallback onTap;

  const _NoticiaCard({
    required this.noticia,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final titulo = noticia['title'] ?? 'Sin título';
    final extracto = noticia['excerpt'] ?? '';
    final fecha = noticia['date'] ?? '';
    final fuente = noticia['source_name'] ?? '';
    final thumbnail = noticia['thumbnail'];

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (thumbnail != null && thumbnail.toString().isNotEmpty)
              AspectRatio(
                aspectRatio: 16 / 9,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    Image.network(
                      thumbnail,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) => Container(
                        color: Colors.grey[200],
                        child: Icon(Icons.image, color: Colors.grey[400], size: 48),
                      ),
                    ),
                    Positioned(
                      top: 8,
                      left: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.orange,
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Text(
                          'Noticia',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    titulo,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (extracto.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      _limpiarHtml(extracto),
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[600],
                      ),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      if (fuente.isNotEmpty) ...[
                        Icon(Icons.rss_feed, size: 14, color: Colors.grey[500]),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            fuente,
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[500],
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                      if (fecha.isNotEmpty) ...[
                        Icon(Icons.schedule, size: 14, color: Colors.grey[500]),
                        const SizedBox(width: 4),
                        Text(
                          _formatearFecha(fecha),
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// TARJETA DE VIDEO (Lista)
// =============================================================================

class _VideoCard extends StatelessWidget {
  final dynamic video;
  final VoidCallback onTap;

  const _VideoCard({
    required this.video,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final titulo = video['title'] ?? 'Sin título';
    final videoId = video['video_id'] ?? '';
    final canal = video['channel_name'] ?? '';
    final duracion = video['duration'] ?? '';
    final thumbnail = video['thumbnail'] ??
        'https://img.youtube.com/vi/$videoId/hqdefault.jpg';

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Row(
          children: [
            // Thumbnail con botón play
            SizedBox(
              width: 140,
              height: 90,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Image.network(
                    thumbnail,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) => Container(
                      color: Colors.grey[200],
                      child: Icon(Icons.videocam, color: Colors.grey[400]),
                    ),
                  ),
                  Container(
                    color: Colors.black26,
                    child: const Center(
                      child: Icon(
                        Icons.play_circle_fill,
                        color: Colors.white,
                        size: 40,
                      ),
                    ),
                  ),
                  if (duracion.isNotEmpty)
                    Positioned(
                      bottom: 4,
                      right: 4,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 4,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.black87,
                          borderRadius: BorderRadius.circular(2),
                        ),
                        child: Text(
                          duracion,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            // Info del video
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      titulo,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    if (canal.isNotEmpty)
                      Row(
                        children: [
                          Icon(Icons.person, size: 14, color: Colors.grey[500]),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              canal,
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey[600],
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// TARJETA DE VIDEO (Grid)
// =============================================================================

class _VideoGridCard extends StatelessWidget {
  final dynamic video;
  final VoidCallback onTap;

  const _VideoGridCard({
    required this.video,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final titulo = video['title'] ?? 'Sin título';
    final videoId = video['video_id'] ?? '';
    final duracion = video['duration'] ?? '';
    final thumbnail = video['thumbnail'] ??
        'https://img.youtube.com/vi/$videoId/hqdefault.jpg';

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Thumbnail
            Expanded(
              flex: 3,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Image.network(
                    thumbnail,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) => Container(
                      color: Colors.grey[200],
                      child: Icon(Icons.videocam, color: Colors.grey[400]),
                    ),
                  ),
                  Container(
                    color: Colors.black12,
                    child: const Center(
                      child: Icon(
                        Icons.play_circle_outline,
                        color: Colors.white,
                        size: 36,
                      ),
                    ),
                  ),
                  if (duracion.isNotEmpty)
                    Positioned(
                      bottom: 4,
                      right: 4,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 4,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.black87,
                          borderRadius: BorderRadius.circular(2),
                        ),
                        child: Text(
                          duracion,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            // Título
            Expanded(
              flex: 2,
              child: Padding(
                padding: const EdgeInsets.all(8),
                child: Text(
                  titulo,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// PANTALLA DETALLE NOTICIA
// =============================================================================

class _NoticiaDetalleScreen extends StatelessWidget {
  final dynamic noticia;

  const _NoticiaDetalleScreen({required this.noticia});

  @override
  Widget build(BuildContext context) {
    final titulo = noticia['title'] ?? 'Sin título';
    final contenido = noticia['excerpt'] ?? '';
    final fecha = noticia['date'] ?? '';
    final fuente = noticia['source_name'] ?? '';
    final urlOriginal = noticia['source_url'] ?? '';
    final thumbnail = noticia['thumbnail'];

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // App Bar con imagen
          SliverAppBar(
            expandedHeight: thumbnail != null ? 200 : 0,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(
                titulo,
                style: const TextStyle(fontSize: 14),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              background: thumbnail != null
                  ? Stack(
                      fit: StackFit.expand,
                      children: [
                        Image.network(
                          thumbnail,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              Container(color: Colors.grey[300]),
                        ),
                        Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [
                                Colors.transparent,
                                Colors.black.withOpacity(0.7),
                              ],
                            ),
                          ),
                        ),
                      ],
                    )
                  : null,
            ),
            actions: [
              if (urlOriginal.isNotEmpty)
                IconButton(
                  icon: const Icon(Icons.open_in_browser),
                  onPressed: () => _abrirEnlace(context, urlOriginal),
                  tooltip: 'Ver artículo original',
                ),
              IconButton(
                icon: const Icon(Icons.share),
                onPressed: () => _compartir(context),
                tooltip: 'Compartir',
              ),
            ],
          ),
          // Contenido
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Meta info
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.orange.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.rss_feed, color: Colors.orange),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (fuente.isNotEmpty)
                                Text(
                                  fuente,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              if (fecha.isNotEmpty)
                                Text(
                                  _formatearFecha(fecha),
                                  style: TextStyle(
                                    color: Colors.grey[600],
                                    fontSize: 12,
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Título completo
                  Text(
                    titulo,
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Contenido
                  Text(
                    _limpiarHtml(contenido),
                    style: const TextStyle(
                      fontSize: 16,
                      height: 1.6,
                    ),
                  ),

                  // Botón para ver original
                  if (urlOriginal.isNotEmpty) ...[
                    const SizedBox(height: 32),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: () => _abrirEnlace(context, urlOriginal),
                        icon: const Icon(Icons.open_in_new),
                        label: const Text('Leer artículo completo'),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _abrirEnlace(BuildContext context, String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No se puede abrir el enlace')),
        );
      }
    }
  }

  void _compartir(BuildContext context) {
    final titulo = noticia['title'] ?? 'Noticia';
    final url = noticia['url'] ?? '';
    Share.share('$titulo\n\n$url');
  }
}

// =============================================================================
// PANTALLA DETALLE VIDEO
// =============================================================================

class _VideoDetalleScreen extends StatelessWidget {
  final dynamic video;

  const _VideoDetalleScreen({required this.video});

  @override
  Widget build(BuildContext context) {
    final titulo = video['title'] ?? 'Sin título';
    final videoId = video['video_id'] ?? '';
    final canal = video['channel_name'] ?? '';
    final fecha = video['date'] ?? '';
    final duracion = video['duration'] ?? '';
    final videoUrl = video['video_url'] ?? '';
    final embedUrl = 'https://www.youtube.com/embed/$videoId';
    final thumbnail = video['thumbnail'] ??
        'https://img.youtube.com/vi/$videoId/maxresdefault.jpg';

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: Text(
          titulo,
          style: const TextStyle(fontSize: 14),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.open_in_new),
            onPressed: () => _abrirEnYouTube(context, videoUrl),
            tooltip: 'Ver en YouTube',
          ),
          IconButton(
            icon: const Icon(Icons.share),
            onPressed: () => _compartir(context),
            tooltip: 'Compartir',
          ),
        ],
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Player placeholder (thumbnail con botón)
            AspectRatio(
              aspectRatio: 16 / 9,
              child: GestureDetector(
                onTap: () => _abrirEnYouTube(context, videoUrl),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    Image.network(
                      thumbnail,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) =>
                          Container(color: Colors.grey[900]),
                    ),
                    Container(
                      color: Colors.black38,
                      child: Center(
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.red,
                            borderRadius: BorderRadius.circular(50),
                          ),
                          child: const Icon(
                            Icons.play_arrow,
                            color: Colors.white,
                            size: 48,
                          ),
                        ),
                      ),
                    ),
                    // Duración
                    if (duracion.isNotEmpty)
                      Positioned(
                        bottom: 12,
                        right: 12,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.black87,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            duracion,
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),

            // Info del video
            Container(
              padding: const EdgeInsets.all(20),
              color: Colors.grey[900],
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    titulo,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Meta info
                  Row(
                    children: [
                      if (canal.isNotEmpty) ...[
                        CircleAvatar(
                          radius: 16,
                          backgroundColor: Colors.red,
                          child: Text(
                            canal.isNotEmpty ? canal[0].toUpperCase() : 'C',
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                canal,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              if (fecha.isNotEmpty)
                                Text(
                                  _formatearFecha(fecha),
                                  style: TextStyle(
                                    color: Colors.grey[400],
                                    fontSize: 12,
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),

            // Botón abrir en YouTube
            Container(
              padding: const EdgeInsets.all(20),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _abrirEnYouTube(context, videoUrl),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.red,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  icon: const Icon(Icons.play_arrow),
                  label: const Text('Ver en YouTube'),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _abrirEnYouTube(BuildContext context, String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No se puede abrir el enlace')),
        );
      }
    }
  }

  void _compartir(BuildContext context) {
    final titulo = video['title'] ?? 'Video';
    final url = video['video_url'] ?? '';
    Share.share('$titulo\n\n$url');
  }
}

// =============================================================================
// BUSCADOR DE CONTENIDO
// =============================================================================

class _ContenidoBusquedaDelegate extends SearchDelegate<dynamic> {
  final List<dynamic> noticias;
  final List<dynamic> videos;

  _ContenidoBusquedaDelegate({
    required this.noticias,
    required this.videos,
  });

  @override
  String get searchFieldLabel => 'Buscar contenido...';

  @override
  List<Widget> buildActions(BuildContext context) {
    return [
      IconButton(
        icon: const Icon(Icons.clear),
        onPressed: () {
          query = '';
        },
      ),
    ];
  }

  @override
  Widget buildLeading(BuildContext context) {
    return IconButton(
      icon: const Icon(Icons.arrow_back),
      onPressed: () {
        close(context, null);
      },
    );
  }

  @override
  Widget buildResults(BuildContext context) {
    return _buildResultados();
  }

  @override
  Widget buildSuggestions(BuildContext context) {
    if (query.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.search, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'Busca noticias y videos',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
        ),
      );
    }
    return _buildResultados();
  }

  Widget _buildResultados() {
    final queryLower = query.toLowerCase();

    final noticiasEncontradas = noticias.where((n) {
      final titulo = (n['title'] ?? '').toString().toLowerCase();
      final extracto = (n['excerpt'] ?? '').toString().toLowerCase();
      return titulo.contains(queryLower) || extracto.contains(queryLower);
    }).toList();

    final videosEncontrados = videos.where((v) {
      final titulo = (v['title'] ?? '').toString().toLowerCase();
      final canal = (v['channel_name'] ?? '').toString().toLowerCase();
      return titulo.contains(queryLower) || canal.contains(queryLower);
    }).toList();

    final totalResultados =
        noticiasEncontradas.length + videosEncontrados.length;

    if (totalResultados == 0) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.search_off, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No se encontraron resultados',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (noticiasEncontradas.isNotEmpty) ...[
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Text(
              'Noticias (${noticiasEncontradas.length})',
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
          ),
          ...noticiasEncontradas.map((n) => _ResultadoItem(
                tipo: 'noticia',
                titulo: n['title'] ?? '',
                subtitulo: n['source_name'] ?? '',
                onTap: () {
                  close(context, n);
                },
              )),
          const SizedBox(height: 16),
        ],
        if (videosEncontrados.isNotEmpty) ...[
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Text(
              'Videos (${videosEncontrados.length})',
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
          ),
          ...videosEncontrados.map((v) => _ResultadoItem(
                tipo: 'video',
                titulo: v['title'] ?? '',
                subtitulo: v['channel_name'] ?? '',
                onTap: () {
                  close(context, v);
                },
              )),
        ],
      ],
    );
  }
}

class _ResultadoItem extends StatelessWidget {
  final String tipo;
  final String titulo;
  final String subtitulo;
  final VoidCallback onTap;

  const _ResultadoItem({
    required this.tipo,
    required this.titulo,
    required this.subtitulo,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(
        backgroundColor: tipo == 'noticia' ? Colors.orange : Colors.red,
        child: Icon(
          tipo == 'noticia' ? Icons.article : Icons.play_arrow,
          color: Colors.white,
        ),
      ),
      title: Text(
        titulo,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: subtitulo.isNotEmpty ? Text(subtitulo) : null,
      onTap: onTap,
    );
  }
}

// =============================================================================
// UTILIDADES
// =============================================================================

String _formatearFecha(String fechaStr) {
  try {
    final fecha = DateTime.parse(fechaStr);
    final ahora = DateTime.now();
    final diferencia = ahora.difference(fecha);

    if (diferencia.inDays == 0) {
      if (diferencia.inHours == 0) {
        return 'Hace ${diferencia.inMinutes} min';
      }
      return 'Hace ${diferencia.inHours}h';
    } else if (diferencia.inDays == 1) {
      return 'Ayer';
    } else if (diferencia.inDays < 7) {
      return 'Hace ${diferencia.inDays} días';
    } else {
      return '${fecha.day}/${fecha.month}/${fecha.year}';
    }
  } catch (e) {
    return fechaStr;
  }
}

String _limpiarHtml(String html) {
  // Eliminar etiquetas HTML básicas
  return html
      .replaceAll(RegExp(r'<[^>]*>'), '')
      .replaceAll('&nbsp;', ' ')
      .replaceAll('&amp;', '&')
      .replaceAll('&lt;', '<')
      .replaceAll('&gt;', '>')
      .replaceAll('&quot;', '"')
      .replaceAll('&#39;', "'")
      .trim();
}
