import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:just_audio/just_audio.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class RadioScreen extends ConsumerStatefulWidget {
  const RadioScreen({super.key});

  @override
  ConsumerState<RadioScreen> createState() => _RadioScreenState();
}

class _RadioScreenState extends ConsumerState<RadioScreen> {
  Map<String, dynamic>? _datosStream;
  List<dynamic> _programacionRadio = [];
  bool _cargando = true;
  String? _mensajeError;

  // Audio player
  late AudioPlayer _audioPlayer;
  bool _estaReproduciendo = false;
  bool _cargandoAudio = false;
  double _volumen = 0.7;
  String? _streamUrl;

  @override
  void initState() {
    super.initState();
    _audioPlayer = AudioPlayer();
    _setupAudioListeners();
    _cargarDatos();
  }

  void _setupAudioListeners() {
    _audioPlayer.playingStream.listen((playing) {
      if (mounted) {
        setState(() => _estaReproduciendo = playing);
      }
    });

    _audioPlayer.processingStateStream.listen((state) {
      if (mounted) {
        setState(() {
          _cargandoAudio = state == ProcessingState.loading ||
                          state == ProcessingState.buffering;
        });
      }
    });
  }

  @override
  void dispose() {
    _audioPlayer.dispose();
    super.dispose();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/radio/stream');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosStream = respuesta.data;
          _programacionRadio =
              respuesta.data!['programacion'] ?? respuesta.data!['schedule'] ?? [];
          _streamUrl = respuesta.data!['stream_url'] ?? respuesta.data!['url'];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError =
              respuesta.error ?? 'Error al cargar la informacion de la radio';
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

  Future<void> _alternarReproduccion() async {
    if (_streamUrl == null || _streamUrl!.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('URL del stream no disponible')),
      );
      return;
    }

    try {
      if (_estaReproduciendo) {
        await _audioPlayer.pause();
      } else {
        // Si no se ha cargado el audio, cargarlo primero
        if (_audioPlayer.audioSource == null) {
          setState(() => _cargandoAudio = true);
          await _audioPlayer.setUrl(_streamUrl!);
          await _audioPlayer.setVolume(_volumen);
        }
        await _audioPlayer.play();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error al reproducir: $e')),
        );
      }
    }
  }

  void _cambiarVolumen(double valor) {
    setState(() => _volumen = valor);
    _audioPlayer.setVolume(valor);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Radio Comunitaria'),
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
                  icon: Icons.radio,
                )
              : SingleChildScrollView(
                  child: Column(
                    children: [
                      _construirReproductorRadio(),
                      if (_programacionRadio.isNotEmpty) ...[
                        const Padding(
                          padding: EdgeInsets.all(16),
                          child: Row(
                            children: [
                              Icon(Icons.schedule, size: 20),
                              SizedBox(width: 8),
                              Text(
                                'Programacion',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                        ListView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: _programacionRadio.length,
                          itemBuilder: (context, indice) =>
                              _construirItemProgramacion(
                                  _programacionRadio[indice]),
                        ),
                        const SizedBox(height: 16),
                      ],
                    ],
                  ),
                ),
    );
  }

  Widget _construirReproductorRadio() {
    final nombreEstacion = _datosStream?['nombre'] ??
        _datosStream?['name'] ??
        'Radio Comunitaria';
    final programaActual = _datosStream?['programa_actual'] ??
        _datosStream?['current_show'] ??
        'En directo';
    final locutor =
        _datosStream?['locutor'] ?? _datosStream?['host'] ?? '';
    final urlPortadaRadio =
        _datosStream?['imagen'] ?? _datosStream?['cover'] ?? '';
    final oyentesActuales =
        _datosStream?['oyentes'] ?? _datosStream?['listeners'] ?? 0;

    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.deepOrange.shade400, Colors.deepOrange.shade700],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.deepOrange.withOpacity(0.3),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                width: 100,
                height: 100,
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: urlPortadaRadio.isNotEmpty
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(16),
                        child: Image.network(
                          urlPortadaRadio,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              const Icon(Icons.radio,
                                  size: 50, color: Colors.white),
                        ),
                      )
                    : const Icon(Icons.radio, size: 50, color: Colors.white),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: _estaReproduciendo
                                  ? Colors.greenAccent
                                  : Colors.white,
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            _cargandoAudio
                                ? 'CARGANDO...'
                                : _estaReproduciendo
                                    ? 'EN DIRECTO'
                                    : 'PAUSADO',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      nombreEstacion,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      programaActual,
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.9),
                        fontSize: 14,
                      ),
                    ),
                    if (locutor.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        'Con $locutor',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.7),
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Boton anterior (para radio en vivo no tiene sentido, pero lo dejamos deshabilitado)
              const IconButton(
                icon: Icon(Icons.skip_previous,
                    color: Colors.white54, size: 32),
                onPressed: null, // Deshabilitado para streaming en vivo
                tooltip: 'No disponible para radio en vivo',
              ),
              const SizedBox(width: 16),
              Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.2),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: IconButton(
                  icon: _cargandoAudio
                      ? FlavorInlineSpinner(
                          size: 40,
                          strokeWidth: 3,
                          color: Colors.deepOrange.shade700,
                        )
                      : Icon(
                          _estaReproduciendo
                              ? Icons.pause_rounded
                              : Icons.play_arrow_rounded,
                          color: Colors.deepOrange.shade700,
                          size: 40,
                        ),
                  onPressed: _cargandoAudio ? null : _alternarReproduccion,
                  iconSize: 40,
                  padding: const EdgeInsets.all(12),
                ),
              ),
              const SizedBox(width: 16),
              // Boton siguiente (para radio en vivo no tiene sentido)
              const IconButton(
                icon: Icon(Icons.skip_next, color: Colors.white54, size: 32),
                onPressed: null, // Deshabilitado para streaming en vivo
                tooltip: 'No disponible para radio en vivo',
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.headphones,
                  color: Colors.white.withOpacity(0.8), size: 16),
              const SizedBox(width: 6),
              Text(
                '$oyentesActuales oyentes',
                style: TextStyle(
                  color: Colors.white.withOpacity(0.8),
                  fontSize: 12,
                ),
              ),
              const SizedBox(width: 24),
              Icon(
                _volumen == 0 ? Icons.volume_off : Icons.volume_up,
                color: Colors.white.withOpacity(0.8),
                size: 16,
              ),
              Expanded(
                child: Slider(
                  value: _volumen,
                  onChanged: _cambiarVolumen,
                  activeColor: Colors.white,
                  inactiveColor: Colors.white.withOpacity(0.3),
                ),
              ),
            ],
          ),
          // Indicador de URL del stream (solo si no hay URL)
          if (_streamUrl == null || _streamUrl!.isEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                'Stream no configurado',
                style: TextStyle(
                  color: Colors.white.withOpacity(0.6),
                  fontSize: 11,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _construirItemProgramacion(dynamic elemento) {
    final mapa = elemento as Map<String, dynamic>;
    final nombrePrograma =
        mapa['titulo'] ?? mapa['nombre'] ?? mapa['name'] ?? 'Sin titulo';
    final horarioPrograma = mapa['horario'] ?? mapa['time'] ?? mapa['hora'] ?? '';
    final descripcionPrograma = mapa['descripcion'] ?? mapa['description'] ?? '';
    final diaPrograma = mapa['dia'] ?? mapa['day'] ?? '';
    final esActual = mapa['actual'] ?? mapa['current'] ?? false;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      color: esActual ? Colors.deepOrange.shade50 : null,
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor:
              esActual ? Colors.deepOrange : Colors.deepOrange.shade100,
          child: Icon(
            Icons.radio,
            color: esActual ? Colors.white : Colors.deepOrange.shade700,
          ),
        ),
        title: Row(
          children: [
            Expanded(
              child: Text(
                nombrePrograma,
                style: TextStyle(
                  fontWeight: esActual ? FontWeight.bold : FontWeight.normal,
                ),
              ),
            ),
            if (esActual)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.deepOrange,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Text(
                  'AHORA',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                if (diaPrograma.isNotEmpty) ...[
                  Text(
                    diaPrograma,
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontSize: 12,
                    ),
                  ),
                  const Text(' - '),
                ],
                Text(
                  horarioPrograma,
                  style: TextStyle(
                    color: Colors.deepOrange.shade700,
                    fontWeight: FontWeight.w500,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
            if (descripcionPrograma.isNotEmpty)
              Text(
                descripcionPrograma,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12),
              ),
          ],
        ),
      ),
    );
  }
}
