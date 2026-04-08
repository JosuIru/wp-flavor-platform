import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:just_audio/just_audio.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

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

  void _abrirDetalleEpisodio(Map<String, dynamic> episodio) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => EpisodioDetalleScreen(datosEpisodio: episodio),
      ),
    );
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
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDatos,
                  icon: Icons.podcasts,
                )
              : _listaEpisodios.isEmpty
                  ? const FlavorEmptyState(
                      icon: Icons.podcasts,
                      title: 'No hay episodios disponibles',
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
        mapa['titulo'] ?? mapa['nombre'] ?? mapa['title'] ?? 'Sin titulo';
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
        onTap: () => _abrirDetalleEpisodio(mapa),
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
                          const Icon(Icons.access_time,
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
                          const Icon(Icons.calendar_today,
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
                onPressed: () => _abrirDetalleEpisodio(mapa),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class EpisodioDetalleScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic> datosEpisodio;

  const EpisodioDetalleScreen({super.key, required this.datosEpisodio});

  @override
  ConsumerState<EpisodioDetalleScreen> createState() =>
      _EpisodioDetalleScreenState();
}

class _EpisodioDetalleScreenState extends ConsumerState<EpisodioDetalleScreen> {
  late AudioPlayer _audioPlayer;
  bool _estaReproduciendo = false;
  bool _cargandoAudio = false;
  Duration _duracionTotal = Duration.zero;
  Duration _posicionActual = Duration.zero;
  double _velocidad = 1.0;

  // Stream subscriptions para evitar memory leaks
  StreamSubscription<bool>? _playingSubscription;
  StreamSubscription<Duration?>? _durationSubscription;
  StreamSubscription<Duration>? _positionSubscription;
  StreamSubscription<ProcessingState>? _processingSubscription;

  @override
  void initState() {
    super.initState();
    _audioPlayer = AudioPlayer();
    _configurarListeners();
    _cargarAudio();
  }

  void _configurarListeners() {
    _playingSubscription = _audioPlayer.playingStream.listen((reproduciendo) {
      if (mounted) {
        setState(() => _estaReproduciendo = reproduciendo);
      }
    });

    _durationSubscription = _audioPlayer.durationStream.listen((duracion) {
      if (mounted && duracion != null) {
        setState(() => _duracionTotal = duracion);
      }
    });

    _positionSubscription = _audioPlayer.positionStream.listen((posicion) {
      if (mounted) {
        setState(() => _posicionActual = posicion);
      }
    });

    _processingSubscription = _audioPlayer.processingStateStream.listen((estado) {
      if (mounted) {
        setState(() {
          _cargandoAudio = estado == ProcessingState.loading ||
              estado == ProcessingState.buffering;
        });
      }
    });
  }

  Future<void> _cargarAudio() async {
    final urlAudio = widget.datosEpisodio['audio'] ??
        widget.datosEpisodio['url'] ??
        widget.datosEpisodio['audio_url'] ??
        '';

    if (urlAudio.isEmpty) return;

    try {
      setState(() => _cargandoAudio = true);
      await _audioPlayer.setUrl(urlAudio);
      setState(() => _cargandoAudio = false);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al cargar audio: $e')),
        );
      }
    }
  }

  Future<void> _alternarReproduccion() async {
    if (_estaReproduciendo) {
      await _audioPlayer.pause();
    } else {
      await _audioPlayer.play();
    }
  }

  void _retroceder15s() {
    final nuevaPosicion = _posicionActual - const Duration(seconds: 15);
    _audioPlayer.seek(nuevaPosicion < Duration.zero ? Duration.zero : nuevaPosicion);
  }

  void _adelantar15s() {
    final nuevaPosicion = _posicionActual + const Duration(seconds: 15);
    if (nuevaPosicion < _duracionTotal) {
      _audioPlayer.seek(nuevaPosicion);
    }
  }

  void _cambiarVelocidad() {
    final velocidades = [0.5, 0.75, 1.0, 1.25, 1.5, 2.0];
    final indiceActual = velocidades.indexOf(_velocidad);
    final nuevoIndice = (indiceActual + 1) % velocidades.length;
    setState(() => _velocidad = velocidades[nuevoIndice]);
    _audioPlayer.setSpeed(_velocidad);
  }

  String _formatearDuracion(Duration duracion) {
    final horas = duracion.inHours;
    final minutos = duracion.inMinutes.remainder(60);
    final segundos = duracion.inSeconds.remainder(60);

    if (horas > 0) {
      return '${horas.toString().padLeft(2, '0')}:${minutos.toString().padLeft(2, '0')}:${segundos.toString().padLeft(2, '0')}';
    }
    return '${minutos.toString().padLeft(2, '0')}:${segundos.toString().padLeft(2, '0')}';
  }

  @override
  void dispose() {
    // Cancelar subscriptions antes de dispose del player
    _playingSubscription?.cancel();
    _durationSubscription?.cancel();
    _positionSubscription?.cancel();
    _processingSubscription?.cancel();
    _audioPlayer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final titulo = widget.datosEpisodio['titulo'] ??
        widget.datosEpisodio['nombre'] ??
        widget.datosEpisodio['title'] ??
        'Sin titulo';
    final descripcion =
        widget.datosEpisodio['descripcion'] ?? widget.datosEpisodio['description'] ?? '';
    final urlPortada = widget.datosEpisodio['portada'] ??
        widget.datosEpisodio['imagen'] ??
        widget.datosEpisodio['thumbnail'] ??
        '';
    final numeroEpisodio = widget.datosEpisodio['numero'] ??
        widget.datosEpisodio['episode'] ??
        widget.datosEpisodio['ep'] ??
        0;
    final fechaPublicacion = widget.datosEpisodio['fecha'] ??
        widget.datosEpisodio['date'] ??
        widget.datosEpisodio['publicado'] ??
        '';
    final presentador = widget.datosEpisodio['presentador'] ??
        widget.datosEpisodio['host'] ??
        widget.datosEpisodio['autor'] ??
        '';

    return Scaffold(
      appBar: AppBar(
        title: Text(numeroEpisodio != 0 ? 'Episodio $numeroEpisodio' : 'Episodio'),
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Portada
            Container(
              width: double.infinity,
              height: 250,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [Colors.purple.shade400, Colors.purple.shade800],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: urlPortada.isNotEmpty
                  ? Image.network(
                      urlPortada,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Icon(
                        Icons.podcasts,
                        size: 100,
                        color: Colors.white.withOpacity(0.5),
                      ),
                    )
                  : Icon(
                      Icons.podcasts,
                      size: 100,
                      color: Colors.white.withOpacity(0.5),
                    ),
            ),

            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    titulo,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      if (fechaPublicacion.isNotEmpty) ...[
                        Icon(Icons.calendar_today,
                            size: 14, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Text(
                          fechaPublicacion,
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                      ],
                      if (presentador.isNotEmpty) ...[
                        const SizedBox(width: 16),
                        Icon(Icons.person, size: 14, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Text(
                          presentador,
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                      ],
                    ],
                  ),

                  const SizedBox(height: 24),

                  // Reproductor
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.purple.shade50,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      children: [
                        // Barra de progreso
                        SliderTheme(
                          data: SliderTheme.of(context).copyWith(
                            activeTrackColor: Colors.purple.shade700,
                            inactiveTrackColor: Colors.purple.shade200,
                            thumbColor: Colors.purple.shade700,
                            trackHeight: 4,
                          ),
                          child: Slider(
                            value: _duracionTotal.inMilliseconds > 0
                                ? _posicionActual.inMilliseconds /
                                    _duracionTotal.inMilliseconds
                                : 0,
                            onChanged: (valor) {
                              final nuevaPosicion = Duration(
                                milliseconds:
                                    (valor * _duracionTotal.inMilliseconds).toInt(),
                              );
                              _audioPlayer.seek(nuevaPosicion);
                            },
                          ),
                        ),

                        // Tiempos
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                _formatearDuracion(_posicionActual),
                                style: TextStyle(
                                  color: Colors.grey.shade700,
                                  fontSize: 12,
                                ),
                              ),
                              Text(
                                _formatearDuracion(_duracionTotal),
                                style: TextStyle(
                                  color: Colors.grey.shade700,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),

                        const SizedBox(height: 16),

                        // Controles
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            // Velocidad
                            TextButton(
                              onPressed: _cambiarVelocidad,
                              child: Text(
                                '${_velocidad}x',
                                style: TextStyle(
                                  color: Colors.purple.shade700,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),

                            const SizedBox(width: 8),

                            // Retroceder 15s
                            IconButton(
                              icon: const Icon(Icons.replay_10),
                              iconSize: 36,
                              color: Colors.purple.shade700,
                              onPressed: _retroceder15s,
                            ),

                            const SizedBox(width: 8),

                            // Play/Pause
                            Container(
                              decoration: BoxDecoration(
                                color: Colors.purple.shade700,
                                shape: BoxShape.circle,
                              ),
                              child: IconButton(
                                icon: _cargandoAudio
                                    ? const FlavorInlineSpinner(
                                        size: 32,
                                        strokeWidth: 3,
                                        color: Colors.white,
                                      )
                                    : Icon(
                                        _estaReproduciendo
                                            ? Icons.pause_rounded
                                            : Icons.play_arrow_rounded,
                                        color: Colors.white,
                                      ),
                                iconSize: 40,
                                onPressed: _cargandoAudio ? null : _alternarReproduccion,
                              ),
                            ),

                            const SizedBox(width: 8),

                            // Adelantar 15s
                            IconButton(
                              icon: const Icon(Icons.forward_10),
                              iconSize: 36,
                              color: Colors.purple.shade700,
                              onPressed: _adelantar15s,
                            ),

                            const SizedBox(width: 8),

                            // Placeholder para equilibrar
                            const SizedBox(width: 48),
                          ],
                        ),
                      ],
                    ),
                  ),

                  if (descripcion.isNotEmpty) ...[
                    const SizedBox(height: 24),
                    Text(
                      'Descripcion',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      descripcion,
                      style: TextStyle(
                        color: Colors.grey.shade700,
                        height: 1.5,
                      ),
                    ),
                  ],

                  const SizedBox(height: 32),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
