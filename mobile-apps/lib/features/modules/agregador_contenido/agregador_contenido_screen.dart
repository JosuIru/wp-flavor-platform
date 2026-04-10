import 'package:flavor_app/core/api/api_client.dart';
import 'package:flavor_app/core/widgets/flavor_error_widget.dart';
import 'package:flavor_app/core/widgets/flavor_loading_widget.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:share_plus/share_plus.dart';

part 'agregador_contenido_screen_parts.dart';

/// Pantalla principal del Agregador de Contenido
/// Muestra noticias RSS importadas y videos de YouTube de la comunidad
class AgregadorContenidoScreen extends ConsumerStatefulWidget {
  const AgregadorContenidoScreen({super.key});

  @override
  ConsumerState<AgregadorContenidoScreen> createState() =>
      _AgregadorContenidoScreenState();
}

class _AgregadorContenidoScreenState
    extends ConsumerState<AgregadorContenidoScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  List<dynamic> _noticias = [];
  List<dynamic> _videos = [];
  List<dynamic> _feedCombinado = [];

  bool _cargandoNoticias = true;
  bool _cargandoVideos = true;
  String? _errorNoticias;
  String? _errorVideos;

  String _categoriaSeleccionada = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _cargarContenido();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarContenido() async {
    await Future.wait([
      _cargarNoticias(),
      _cargarVideos(),
    ]);
    _generarFeedCombinado();
  }

  Future<void> _cargarNoticias() async {
    setState(() {
      _cargandoNoticias = true;
      _errorNoticias = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      String endpoint = '/flavor-agregador/v1/noticias?per_page=50';
      if (_categoriaSeleccionada.isNotEmpty) {
        endpoint += '&categoria=$_categoriaSeleccionada';
      }

      final response = await apiClient.get(endpoint);

      if (response != null && response['items'] != null) {
        setState(() {
          _noticias = response['items'] as List<dynamic>;
          _cargandoNoticias = false;
        });
      } else {
        setState(() {
          _noticias = [];
          _cargandoNoticias = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorNoticias = e.toString();
        _cargandoNoticias = false;
      });
    }
  }

  Future<void> _cargarVideos() async {
    setState(() {
      _cargandoVideos = true;
      _errorVideos = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      String endpoint = '/flavor-agregador/v1/videos?per_page=50';
      if (_categoriaSeleccionada.isNotEmpty) {
        endpoint += '&categoria=$_categoriaSeleccionada';
      }

      final response = await apiClient.get(endpoint);

      if (response != null && response['items'] != null) {
        setState(() {
          _videos = response['items'] as List<dynamic>;
          _cargandoVideos = false;
        });
      } else {
        setState(() {
          _videos = [];
          _cargandoVideos = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorVideos = e.toString();
        _cargandoVideos = false;
      });
    }
  }

  void _generarFeedCombinado() {
    // Combinar noticias y videos, ordenados por fecha
    final List<Map<String, dynamic>> combined = [];

    for (final noticia in _noticias) {
      combined.add({
        'tipo': 'noticia',
        'data': noticia,
        'fecha': DateTime.tryParse(noticia['date'] ?? '') ?? DateTime.now(),
      });
    }

    for (final video in _videos) {
      combined.add({
        'tipo': 'video',
        'data': video,
        'fecha': DateTime.tryParse(video['date'] ?? '') ?? DateTime.now(),
      });
    }

    // Ordenar por fecha descendente
    combined.sort((a, b) => (b['fecha'] as DateTime).compareTo(a['fecha'] as DateTime));

    setState(() {
      _feedCombinado = combined;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Contenido'),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: () {
              showSearch(
                context: context,
                delegate: _ContenidoBusquedaDelegate(
                  noticias: _noticias,
                  videos: _videos,
                ),
              );
            },
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Feed', icon: Icon(Icons.dynamic_feed)),
            Tab(text: 'Noticias', icon: Icon(Icons.article)),
            Tab(text: 'Videos', icon: Icon(Icons.play_circle)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // Tab 1: Feed combinado
          _buildFeedTab(),
          // Tab 2: Noticias
          _buildNoticiasTab(),
          // Tab 3: Videos
          _buildVideosTab(),
        ],
      ),
    );
  }

  Widget _buildFeedTab() {
    if (_cargandoNoticias || _cargandoVideos) {
      return const FlavorLoadingWidget(message: 'Cargando contenido...');
    }

    if (_feedCombinado.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.inbox, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No hay contenido disponible',
              style: TextStyle(color: Colors.grey[600], fontSize: 16),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarContenido,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _feedCombinado.length,
        itemBuilder: (context, index) {
          final item = _feedCombinado[index];
          final tipo = item['tipo'] as String;
          final data = item['data'];

          if (tipo == 'noticia') {
            return _NoticiaCard(
              noticia: data,
              onTap: () => _abrirNoticia(data),
            );
          } else {
            return _VideoCard(
              video: data,
              onTap: () => _abrirVideo(data),
            );
          }
        },
      ),
    );
  }

  Widget _buildNoticiasTab() {
    if (_cargandoNoticias) {
      return const FlavorLoadingWidget(message: 'Cargando noticias...');
    }

    if (_errorNoticias != null) {
      return FlavorErrorWidget(
        message: _errorNoticias!,
        onRetry: _cargarNoticias,
      );
    }

    if (_noticias.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.article_outlined, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No hay noticias disponibles',
              style: TextStyle(color: Colors.grey[600], fontSize: 16),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarNoticias,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _noticias.length,
        itemBuilder: (context, index) {
          final noticia = _noticias[index];
          return _NoticiaCard(
            noticia: noticia,
            onTap: () => _abrirNoticia(noticia),
          );
        },
      ),
    );
  }

  Widget _buildVideosTab() {
    if (_cargandoVideos) {
      return const FlavorLoadingWidget(message: 'Cargando videos...');
    }

    if (_errorVideos != null) {
      return FlavorErrorWidget(
        message: _errorVideos!,
        onRetry: _cargarVideos,
      );
    }

    if (_videos.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.videocam_off, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No hay videos disponibles',
              style: TextStyle(color: Colors.grey[600], fontSize: 16),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarVideos,
      child: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          childAspectRatio: 16 / 12,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
        ),
        itemCount: _videos.length,
        itemBuilder: (context, index) {
          final video = _videos[index];
          return _VideoGridCard(
            video: video,
            onTap: () => _abrirVideo(video),
          );
        },
      ),
    );
  }

  void _abrirNoticia(dynamic noticia) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _NoticiaDetalleScreen(noticia: noticia),
      ),
    );
  }

  void _abrirVideo(dynamic video) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _VideoDetalleScreen(video: video),
      ),
    );
  }
}
