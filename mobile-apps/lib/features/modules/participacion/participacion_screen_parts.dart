part of 'participacion_screen.dart';

class VotacionDetalleScreen extends ConsumerStatefulWidget {
  final dynamic procesoId;

  const VotacionDetalleScreen({super.key, required this.procesoId});

  @override
  ConsumerState<VotacionDetalleScreen> createState() => _VotacionDetalleScreenState();
}

class _VotacionDetalleScreenState extends ConsumerState<VotacionDetalleScreen> {
  Map<String, dynamic>? _datosProceso;
  List<dynamic> _opciones = [];
  bool _cargando = true;
  String? _mensajeError;
  dynamic _opcionSeleccionada;
  bool _enviandoVoto = false;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/participacion/procesos/${widget.procesoId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosProceso = respuesta.data!['data'] ?? respuesta.data!;
          _opciones = _datosProceso?['opciones'] ?? _datosProceso?['options'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar el proceso';
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

  Future<void> _enviarVoto() async {
    if (_opcionSeleccionada == null) {
      FlavorSnackbar.showError(context, 'Selecciona una opcion para votar');
      return;
    }

    setState(() => _enviandoVoto = true);

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post(
        '/participacion/procesos/${widget.procesoId}/votar',
        data: {'opcion_id': _opcionSeleccionada},
      );

      if (!mounted) return;

      if (respuesta.success) {
        FlavorSnackbar.showSuccess(context, 'Voto registrado correctamente');
        _cargarDetalle();
      } else {
        FlavorSnackbar.showError(context, respuesta.error ?? 'Error al votar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    } finally {
      if (mounted) {
        setState(() => _enviandoVoto = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Votacion'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDetalle,
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDetalle,
                  icon: Icons.how_to_vote_outlined,
                )
              : _datosProceso == null
                  ? const FlavorEmptyState(
                      icon: Icons.how_to_vote_outlined,
                      title: 'No se encontraron datos',
                    )
                  : _buildContenido(),
    );
  }

  Widget _buildContenido() {
    final titulo = _datosProceso!['titulo'] ?? _datosProceso!['nombre'] ?? 'Votacion';
    final descripcion = _datosProceso!['descripcion'] ?? '';
    final estado = _datosProceso!['estado'] ?? 'activo';
    final yaVotado = _datosProceso!['votado'] ?? false;
    final totalVotos = _datosProceso!['total_votos'] ?? 0;
    final fechaLimite = _datosProceso!['fecha_limite'] ?? '';
    final esActivo = estado.toString().toLowerCase() == 'activo' || estado.toString().toLowerCase() == 'abierto';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Cabecera
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: Colors.blue.shade100,
                      radius: 24,
                      child: Icon(Icons.how_to_vote, color: Colors.blue.shade700, size: 28),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            titulo,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                          ),
                          if (fechaLimite.isNotEmpty)
                            Text(
                              'Hasta: $fechaLimite',
                              style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
                if (descripcion.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(descripcion),
                ],
                const SizedBox(height: 12),
                Row(
                  children: [
                    Chip(
                      label: Text(esActivo ? 'Activa' : 'Cerrada'),
                      backgroundColor: esActivo ? Colors.green.shade100 : Colors.red.shade100,
                    ),
                    const SizedBox(width: 8),
                    if (yaVotado)
                      Chip(
                        avatar: const Icon(Icons.check, size: 16),
                        label: const Text('Ya has votado'),
                        backgroundColor: Colors.green.shade50,
                      ),
                    const Spacer(),
                    const Icon(Icons.people, size: 16, color: Colors.grey),
                    const SizedBox(width: 4),
                    Text('$totalVotos votos'),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Opciones
        const Text(
          'Opciones',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
        const SizedBox(height: 8),

        if (_opciones.isEmpty)
          const Card(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: Text('No hay opciones disponibles')),
            ),
          )
        else
          ..._opciones.map((opcion) => _buildOpcionCard(opcion, yaVotado, esActivo)),

        // Boton votar
        if (esActivo && !yaVotado) ...[
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: _enviandoVoto ? null : _enviarVoto,
            icon: _enviandoVoto
                ? const FlavorInlineSpinner()
                : const Icon(Icons.how_to_vote),
            label: Text(_enviandoVoto ? 'Enviando...' : 'Confirmar voto'),
            style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(48)),
          ),
        ],
      ],
    );
  }

  Widget _buildOpcionCard(dynamic opcion, bool yaVotado, bool esActivo) {
    final mapa = opcion as Map<String, dynamic>;
    final opcionId = mapa['id'];
    final textoOpcion = mapa['texto'] ?? mapa['nombre'] ?? mapa['title'] ?? '';
    final votos = mapa['votos'] ?? 0;
    final porcentaje = mapa['porcentaje'] ?? 0.0;
    final esSeleccionada = _opcionSeleccionada == opcionId;
    final fueVotada = mapa['votada'] ?? false;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      color: fueVotada
          ? Colors.blue.shade50
          : esSeleccionada
              ? Colors.blue.shade100
              : null,
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: (esActivo && !yaVotado)
            ? () => setState(() => _opcionSeleccionada = opcionId)
            : null,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  if (esActivo && !yaVotado)
                    Radio<dynamic>(
                      value: opcionId,
                      groupValue: _opcionSeleccionada,
                      onChanged: (value) => setState(() => _opcionSeleccionada = value),
                    ),
                  if (fueVotada)
                    const Padding(
                      padding: EdgeInsets.only(right: 8),
                      child: Icon(Icons.check_circle, color: Colors.green),
                    ),
                  Expanded(
                    child: Text(
                      textoOpcion,
                      style: TextStyle(
                        fontWeight: fueVotada ? FontWeight.bold : FontWeight.normal,
                      ),
                    ),
                  ),
                  Text(
                    '$votos votos',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                  ),
                ],
              ),
              if (yaVotado || !esActivo) ...[
                const SizedBox(height: 8),
                LinearProgressIndicator(
                  value: (porcentaje is num ? porcentaje.toDouble() : 0.0) / 100,
                  backgroundColor: Colors.grey.shade200,
                ),
                const SizedBox(height: 4),
                Text(
                  '${porcentaje.toStringAsFixed(1)}%',
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
