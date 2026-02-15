import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class PodcastScreen extends ConsumerStatefulWidget {
  const PodcastScreen({super.key});

  @override
  ConsumerState<PodcastScreen> createState() => _PodcastScreenState();
}

class _PodcastScreenState extends ConsumerState<PodcastScreen> {
  List<dynamic> _listaEpisodios = [];
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
      final respuesta = await clienteApi.get('/podcast/episodios');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaEpisodios =
              respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar los episodios';
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
        title: const Text('Podcast Comunitario'),
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
                      Icon(Icons.podcasts, size: 64, color: Colors.grey),
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
              : _listaEpisodios.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.podcasts,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay episodios disponibles'),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listaEpisodios.length,
                        itemBuilder: (context, indice) =>
                            _construirTarjetaEpisodio(_listaEpisodios[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaEpisodio(dynamic elemento) {
    final mapa = elemento as Map<String, dynamic>;
    final tituloEpisodio =
        mapa['titulo'] ?? mapa['nombre'] ?? mapa['title'] ?? 'Sin título';
    final descripcionEpisodio = mapa['descripcion'] ?? mapa['description'] ?? '';
    final duracionEpisodio =
        mapa['duracion'] ?? mapa['duration'] ?? mapa['tiempo'] ?? '';
    final fechaPublicacion =
        mapa['fecha'] ?? mapa['date'] ?? mapa['publicado'] ?? '';
    final urlPortada =
        mapa['portada'] ?? mapa['imagen'] ?? mapa['thumbnail'] ?? '';
    final numeroEpisodio = mapa['numero'] ?? mapa['episode'] ?? mapa['ep'] ?? 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {},
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: urlPortada.isNotEmpty
                    ? Image.network(
                        urlPortada,
                        width: 80,
                        height: 80,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Container(
                          width: 80,
                          height: 80,
                          color: Colors.purple.shade100,
                          child: Icon(Icons.podcasts,
                              size: 40, color: Colors.purple.shade700),
                        ),
                      )
                    : Container(
                        width: 80,
                        height: 80,
                        color: Colors.purple.shade100,
                        child: Icon(Icons.podcasts,
                            size: 40, color: Colors.purple.shade700),
                      ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (numeroEpisodio != 0)
                      Text(
                        'Episodio $numeroEpisodio',
                        style: TextStyle(
                          color: Colors.purple.shade700,
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    const SizedBox(height: 4),
                    Text(
                      tituloEpisodio,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (descripcionEpisodio.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        descripcionEpisodio,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 13,
                        ),
                      ),
                    ],
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        if (duracionEpisodio.isNotEmpty) ...[
                          Icon(Icons.access_time,
                              size: 14, color: Colors.grey),
                          const SizedBox(width: 4),
                          Text(
                            duracionEpisodio,
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 12,
                            ),
                          ),
                        ],
                        if (fechaPublicacion.isNotEmpty) ...[
                          const SizedBox(width: 12),
                          Icon(Icons.calendar_today,
                              size: 14, color: Colors.grey),
                          const SizedBox(width: 4),
                          Text(
                            fechaPublicacion,
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: Icon(Icons.play_circle_filled,
                    color: Colors.purple.shade700, size: 40),
                onPressed: () {},
              ),
            ],
          ),
        ),
      ),
    );
  }
}
