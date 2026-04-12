part of 'participacion_screen.dart';

// =============================================================================
// TARJETAS
// =============================================================================

class _VotacionCard extends StatelessWidget {
  final dynamic votacion;
  final VoidCallback onTap;

  const _VotacionCard({required this.votacion, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final mapa = votacion as Map<String, dynamic>;
    final titulo = mapa['titulo'] ?? mapa['nombre'] ?? 'Sin título';
    final descripcion = mapa['descripcion'] ?? '';
    final estadoVotacion = mapa['estado'] ?? 'activo';
    final fechaLimite = mapa['fecha_limite'] ?? mapa['fecha_fin'] ?? '';
    final totalVotos = mapa['total_votos'] ?? mapa['votos'] ?? 0;
    final yaVotado = mapa['votado'] ?? false;

    final esActiva = estadoVotacion.toString().toLowerCase() == 'activo' ||
        estadoVotacion.toString().toLowerCase() == 'abierto';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    backgroundColor: esActiva
                        ? Colors.green.shade100
                        : Colors.grey.shade200,
                    child: Icon(
                      Icons.how_to_vote,
                      color: esActiva ? Colors.green.shade700 : Colors.grey,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          titulo,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            _EstadoBadge(
                              estado: esActiva ? 'Activa' : 'Cerrada',
                              color: esActiva ? Colors.green : Colors.grey,
                            ),
                            if (yaVotado) ...[
                              const SizedBox(width: 8),
                              const Icon(Icons.check_circle,
                                  size: 16, color: Colors.green),
                              const SizedBox(width: 4),
                              Text(
                                'Votado',
                                style: TextStyle(
                                  color: Colors.green.shade700,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right),
                ],
              ),
              if (descripcion.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  descripcion,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: Colors.grey.shade600),
                ),
              ],
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(Icons.people, size: 16, color: Colors.grey.shade500),
                  const SizedBox(width: 4),
                  Text(
                    '$totalVotos votos',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                  ),
                  if (fechaLimite.isNotEmpty) ...[
                    const SizedBox(width: 16),
                    Icon(Icons.schedule, size: 16, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Text(
                      'Hasta: $fechaLimite',
                      style:
                          TextStyle(color: Colors.grey.shade600, fontSize: 12),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PropuestaCard extends StatelessWidget {
  final dynamic propuesta;
  final VoidCallback onTap;

  const _PropuestaCard({required this.propuesta, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final mapa = propuesta as Map<String, dynamic>;
    final titulo = mapa['titulo'] ?? mapa['nombre'] ?? 'Sin título';
    final descripcion = mapa['descripcion'] ?? '';
    final estado = mapa['estado'] ?? 'pendiente';
    final autor = mapa['autor'] ?? mapa['autor_nombre'] ?? '';
    final fechaCreacion = mapa['fecha_creacion'] ?? mapa['fecha'] ?? '';
    final apoyos = mapa['apoyos'] ?? mapa['votos_favor'] ?? 0;
    final comentarios = mapa['comentarios'] ?? mapa['num_comentarios'] ?? 0;
    final categoria = mapa['categoria'] ?? '';
    final esMia = mapa['es_mia'] ?? false;

    Color colorEstado;
    IconData iconoEstado;
    switch (estado.toString().toLowerCase()) {
      case 'aprobada':
        colorEstado = Colors.green;
        iconoEstado = Icons.check_circle;
        break;
      case 'rechazada':
        colorEstado = Colors.red;
        iconoEstado = Icons.cancel;
        break;
      case 'en_debate':
        colorEstado = Colors.orange;
        iconoEstado = Icons.forum;
        break;
      case 'implementada':
        colorEstado = Colors.teal;
        iconoEstado = Icons.done_all;
        break;
      default:
        colorEstado = Colors.blue;
        iconoEstado = Icons.pending;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    backgroundColor: colorEstado.withOpacity(0.1),
                    child: Icon(iconoEstado, color: colorEstado),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                titulo,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                              ),
                            ),
                            if (esMia)
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.indigo.shade50,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  'Tu propuesta',
                                  style: TextStyle(
                                    fontSize: 10,
                                    color: Colors.indigo.shade700,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            _EstadoBadge(
                              estado: _formatearEstado(estado.toString()),
                              color: colorEstado,
                            ),
                            if (categoria.isNotEmpty) ...[
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.grey.shade200,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  categoria,
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: Colors.grey.shade700,
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right),
                ],
              ),
              if (descripcion.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  descripcion,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: Colors.grey.shade600),
                ),
              ],
              const SizedBox(height: 12),
              Row(
                children: [
                  if (autor.isNotEmpty) ...[
                    Icon(Icons.person, size: 14, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text(
                        autor,
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 12,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 12),
                  ],
                  Icon(Icons.thumb_up, size: 14, color: Colors.grey.shade500),
                  const SizedBox(width: 4),
                  Text(
                    '$apoyos',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                  ),
                  const SizedBox(width: 12),
                  Icon(Icons.comment, size: 14, color: Colors.grey.shade500),
                  const SizedBox(width: 4),
                  Text(
                    '$comentarios',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                  ),
                  if (fechaCreacion.isNotEmpty) ...[
                    const Spacer(),
                    Text(
                      _formatearFecha(fechaCreacion),
                      style:
                          TextStyle(color: Colors.grey.shade500, fontSize: 11),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatearEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'pendiente':
        return 'Pendiente';
      case 'aprobada':
        return 'Aprobada';
      case 'rechazada':
        return 'Rechazada';
      case 'en_debate':
        return 'En debate';
      case 'implementada':
        return 'Implementada';
      default:
        return estado;
    }
  }

  String _formatearFecha(String fecha) {
    try {
      final partes = fecha.split(' ').first.split('-');
      if (partes.length >= 3) {
        return '${partes[2]}/${partes[1]}/${partes[0]}';
      }
    } catch (_) {}
    return fecha;
  }
}

class _EstadoBadge extends StatelessWidget {
  final String estado;
  final Color color;

  const _EstadoBadge({required this.estado, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        estado,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }
}

// =============================================================================
// PANTALLA DETALLE VOTACION
// =============================================================================

class _VotacionDetalleScreen extends ConsumerStatefulWidget {
  final dynamic procesoId;

  const _VotacionDetalleScreen({required this.procesoId});

  @override
  ConsumerState<_VotacionDetalleScreen> createState() =>
      _VotacionDetalleScreenState();
}

class _VotacionDetalleScreenState
    extends ConsumerState<_VotacionDetalleScreen> {
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
      final respuesta =
          await clienteApi.get('/participacion/procesos/${widget.procesoId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosProceso = respuesta.data!['data'] ?? respuesta.data!;
          _opciones =
              _datosProceso?['opciones'] ?? _datosProceso?['options'] ?? [];
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
      FlavorSnackbar.showError(context, 'Selecciona una opción para votar');
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
        title: const Text('Votación'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
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
    final titulo = _datosProceso!['titulo'] ??
        _datosProceso!['nombre'] ??
        'Votación';
    final descripcion = _datosProceso!['descripcion'] ?? '';
    final estado = _datosProceso!['estado'] ?? 'activo';
    final yaVotado = _datosProceso!['votado'] ?? false;
    final totalVotos = _datosProceso!['total_votos'] ?? 0;
    final fechaLimite = _datosProceso!['fecha_limite'] ?? '';
    final esActivo =
        estado.toString().toLowerCase() == 'activo' ||
        estado.toString().toLowerCase() == 'abierto';

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
                      backgroundColor: Colors.indigo.shade100,
                      radius: 24,
                      child: Icon(Icons.how_to_vote,
                          color: Colors.indigo.shade700, size: 28),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            titulo,
                            style: const TextStyle(
                                fontWeight: FontWeight.bold, fontSize: 18),
                          ),
                          if (fechaLimite.isNotEmpty)
                            Text(
                              'Hasta: $fechaLimite',
                              style: TextStyle(
                                  color: Colors.grey.shade600, fontSize: 12),
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
                      backgroundColor:
                          esActivo ? Colors.green.shade100 : Colors.red.shade100,
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
          ..._opciones
              .map((opcion) => _buildOpcionCard(opcion, yaVotado, esActivo)),

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
          ? Colors.indigo.shade50
          : esSeleccionada
              ? Colors.indigo.shade100
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
                      onChanged: (value) =>
                          setState(() => _opcionSeleccionada = value),
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
                  valueColor: const AlwaysStoppedAnimation(Colors.indigo),
                ),
                const SizedBox(height: 4),
                Text(
                  '${porcentaje is num ? porcentaje.toStringAsFixed(1) : porcentaje}%',
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

// =============================================================================
// PANTALLA DETALLE PROPUESTA
// =============================================================================

class _PropuestaDetalleScreen extends ConsumerStatefulWidget {
  final dynamic propuestaId;

  const _PropuestaDetalleScreen({required this.propuestaId});

  @override
  ConsumerState<_PropuestaDetalleScreen> createState() =>
      _PropuestaDetalleScreenState();
}

class _PropuestaDetalleScreenState
    extends ConsumerState<_PropuestaDetalleScreen> {
  Map<String, dynamic>? _propuesta;
  List<dynamic> _comentarios = [];
  bool _cargando = true;
  String? _error;
  bool _enviandoApoyo = false;
  final _comentarioController = TextEditingController();
  bool _enviandoComentario = false;

  @override
  void initState() {
    super.initState();
    _cargarPropuesta();
  }

  @override
  void dispose() {
    _comentarioController.dispose();
    super.dispose();
  }

  Future<void> _cargarPropuesta() async {
    setState(() {
      _cargando = true;
      _error = null;
    });
    try {
      final api = ref.read(apiClientProvider);
      final respuesta =
          await api.get('/participacion/propuestas/${widget.propuestaId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _propuesta = respuesta.data!['propuesta'] ?? respuesta.data!;
          _comentarios = _propuesta?['comentarios'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _error = respuesta.error ?? 'Error al cargar la propuesta';
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _cargando = false;
      });
    }
  }

  Future<void> _apoyarPropuesta() async {
    setState(() => _enviandoApoyo = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.post(
        '/participacion/propuestas/${widget.propuestaId}/apoyar',
        data: {},
      );
      if (!mounted) return;

      if (respuesta.success) {
        FlavorSnackbar.showSuccess(context, 'Apoyo registrado');
        _cargarPropuesta();
      } else {
        FlavorSnackbar.showError(
            context, respuesta.error ?? 'Error al apoyar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    } finally {
      if (mounted) {
        setState(() => _enviandoApoyo = false);
      }
    }
  }

  Future<void> _enviarComentario() async {
    final texto = _comentarioController.text.trim();
    if (texto.isEmpty) {
      FlavorSnackbar.showError(context, 'Escribe un comentario');
      return;
    }

    setState(() => _enviandoComentario = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.post(
        '/participacion/propuestas/${widget.propuestaId}/comentarios',
        data: {'contenido': texto},
      );
      if (!mounted) return;

      if (respuesta.success) {
        _comentarioController.clear();
        FlavorSnackbar.showSuccess(context, 'Comentario enviado');
        _cargarPropuesta();
      } else {
        FlavorSnackbar.showError(
            context, respuesta.error ?? 'Error al comentar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    } finally {
      if (mounted) {
        setState(() => _enviandoComentario = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Propuesta'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarPropuesta,
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _error != null
              ? FlavorErrorState(
                  message: _error!,
                  onRetry: _cargarPropuesta,
                  icon: Icons.lightbulb_outline,
                )
              : _propuesta == null
                  ? const FlavorEmptyState(
                      icon: Icons.lightbulb_outline,
                      title: 'Propuesta no encontrada',
                    )
                  : _buildContenido(),
    );
  }

  Widget _buildContenido() {
    final titulo = _propuesta!['titulo'] ?? 'Sin título';
    final descripcion = _propuesta!['descripcion'] ?? '';
    final estado = _propuesta!['estado'] ?? 'pendiente';
    final autor = _propuesta!['autor'] ?? _propuesta!['autor_nombre'] ?? '';
    final fechaCreacion = _propuesta!['fecha_creacion'] ?? '';
    final apoyos = _propuesta!['apoyos'] ?? 0;
    final yaApoyada = _propuesta!['ya_apoyada'] ?? _propuesta!['apoyada'] ?? false;
    final categoria = _propuesta!['categoria'] ?? '';
    final esMia = _propuesta!['es_mia'] ?? false;
    final respuestaOficial = _propuesta!['respuesta_oficial'] ?? '';

    Color colorEstado;
    switch (estado.toString().toLowerCase()) {
      case 'aprobada':
        colorEstado = Colors.green;
        break;
      case 'rechazada':
        colorEstado = Colors.red;
        break;
      case 'en_debate':
        colorEstado = Colors.orange;
        break;
      case 'implementada':
        colorEstado = Colors.teal;
        break;
      default:
        colorEstado = Colors.blue;
    }

    return Column(
      children: [
        Expanded(
          child: ListView(
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
                          Expanded(
                            child: Text(
                              titulo,
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 20,
                              ),
                            ),
                          ),
                          if (esMia)
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.indigo.shade50,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                'Tu propuesta',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.indigo.shade700,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          _EstadoBadge(
                            estado: _formatearEstado(estado.toString()),
                            color: colorEstado,
                          ),
                          if (categoria.isNotEmpty) ...[
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.grey.shade200,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                categoria,
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.grey.shade700,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                      const Divider(height: 24),
                      if (autor.isNotEmpty)
                        Row(
                          children: [
                            CircleAvatar(
                              radius: 16,
                              backgroundColor: Colors.grey.shade200,
                              child: const Icon(Icons.person, size: 18),
                            ),
                            const SizedBox(width: 8),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  autor,
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w500),
                                ),
                                if (fechaCreacion.isNotEmpty)
                                  Text(
                                    _formatearFecha(fechaCreacion),
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey.shade600,
                                    ),
                                  ),
                              ],
                            ),
                          ],
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Descripcion
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Descripción',
                        style: TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        descripcion.isNotEmpty
                            ? descripcion
                            : 'Sin descripción',
                        style: TextStyle(
                          color: descripcion.isNotEmpty
                              ? null
                              : Colors.grey.shade500,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Apoyos
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      CircleAvatar(
                        backgroundColor: Colors.green.shade100,
                        child: Icon(Icons.thumb_up, color: Colors.green.shade700),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              '$apoyos apoyos',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 18,
                              ),
                            ),
                            Text(
                              yaApoyada
                                  ? 'Ya has apoyado esta propuesta'
                                  : 'Apoya esta propuesta',
                              style: TextStyle(
                                color: Colors.grey.shade600,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                      if (!yaApoyada && !esMia)
                        FilledButton.icon(
                          onPressed: _enviandoApoyo ? null : _apoyarPropuesta,
                          icon: _enviandoApoyo
                              ? const FlavorInlineSpinner()
                              : const Icon(Icons.thumb_up),
                          label: const Text('Apoyar'),
                        ),
                      if (yaApoyada)
                        const Chip(
                          avatar: Icon(Icons.check, size: 16),
                          label: Text('Apoyada'),
                          backgroundColor: Color(0xFFE8F5E9),
                        ),
                    ],
                  ),
                ),
              ),

              // Respuesta oficial
              if (respuestaOficial.isNotEmpty) ...[
                const SizedBox(height: 16),
                Card(
                  color: Colors.blue.shade50,
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.verified,
                                color: Colors.blue.shade700, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              'Respuesta oficial',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Colors.blue.shade700,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(respuestaOficial),
                      ],
                    ),
                  ),
                ),
              ],

              // Comentarios
              const SizedBox(height: 16),
              Row(
                children: [
                  const Text(
                    'Comentarios',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade200,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      '${_comentarios.length}',
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              if (_comentarios.isEmpty)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Center(
                      child: Text(
                        'Sin comentarios aún',
                        style: TextStyle(color: Colors.grey.shade500),
                      ),
                    ),
                  ),
                )
              else
                ..._comentarios.map((c) => _buildComentarioCard(c)),

              const SizedBox(height: 80), // Espacio para el input
            ],
          ),
        ),

        // Input comentario
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Theme.of(context).scaffoldBackgroundColor,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.1),
                blurRadius: 4,
                offset: const Offset(0, -2),
              ),
            ],
          ),
          child: SafeArea(
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _comentarioController,
                    decoration: InputDecoration(
                      hintText: 'Escribe un comentario...',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(24),
                      ),
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                    ),
                    maxLines: null,
                    textInputAction: TextInputAction.send,
                    onSubmitted: (_) => _enviarComentario(),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filled(
                  onPressed: _enviandoComentario ? null : _enviarComentario,
                  icon: _enviandoComentario
                      ? const FlavorInlineSpinner()
                      : const Icon(Icons.send),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildComentarioCard(dynamic comentario) {
    final mapa = comentario as Map<String, dynamic>;
    final autor = mapa['autor'] ?? mapa['autor_nombre'] ?? 'Usuario';
    final contenido = mapa['contenido'] ?? mapa['texto'] ?? '';
    final fecha = mapa['fecha'] ?? mapa['fecha_creacion'] ?? '';
    final esOficial = mapa['es_oficial'] ?? false;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      color: esOficial ? Colors.blue.shade50 : null,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 14,
                  backgroundColor:
                      esOficial ? Colors.blue.shade100 : Colors.grey.shade200,
                  child: Icon(
                    esOficial ? Icons.verified : Icons.person,
                    size: 16,
                    color: esOficial ? Colors.blue : Colors.grey,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            autor,
                            style: const TextStyle(fontWeight: FontWeight.w500),
                          ),
                          if (esOficial) ...[
                            const SizedBox(width: 4),
                            Icon(Icons.verified,
                                size: 14, color: Colors.blue.shade700),
                          ],
                        ],
                      ),
                      if (fecha.isNotEmpty)
                        Text(
                          _formatearFecha(fecha),
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade500,
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(contenido),
          ],
        ),
      ),
    );
  }

  String _formatearEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'pendiente':
        return 'Pendiente';
      case 'aprobada':
        return 'Aprobada';
      case 'rechazada':
        return 'Rechazada';
      case 'en_debate':
        return 'En debate';
      case 'implementada':
        return 'Implementada';
      default:
        return estado;
    }
  }

  String _formatearFecha(String fecha) {
    try {
      final partes = fecha.split(' ').first.split('-');
      if (partes.length >= 3) {
        return '${partes[2]}/${partes[1]}/${partes[0]}';
      }
    } catch (_) {}
    return fecha;
  }
}

// =============================================================================
// PANTALLA CREAR PROPUESTA
// =============================================================================

class _CrearPropuestaScreen extends ConsumerStatefulWidget {
  const _CrearPropuestaScreen();

  @override
  ConsumerState<_CrearPropuestaScreen> createState() =>
      _CrearPropuestaScreenState();
}

class _CrearPropuestaScreenState extends ConsumerState<_CrearPropuestaScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  String _categoriaSeleccionada = '';
  bool _enviando = false;

  final List<Map<String, String>> _categorias = [
    {'id': '', 'label': 'Selecciona una categoría'},
    {'id': 'urbanismo', 'label': 'Urbanismo'},
    {'id': 'medio_ambiente', 'label': 'Medio ambiente'},
    {'id': 'movilidad', 'label': 'Movilidad'},
    {'id': 'cultura', 'label': 'Cultura'},
    {'id': 'deportes', 'label': 'Deportes'},
    {'id': 'educacion', 'label': 'Educación'},
    {'id': 'servicios_sociales', 'label': 'Servicios sociales'},
    {'id': 'seguridad', 'label': 'Seguridad'},
    {'id': 'economia', 'label': 'Economía local'},
    {'id': 'otros', 'label': 'Otros'},
  ];

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    super.dispose();
  }

  Future<void> _enviarPropuesta() async {
    if (!_formKey.currentState!.validate()) return;
    if (_categoriaSeleccionada.isEmpty) {
      FlavorSnackbar.showError(context, 'Selecciona una categoría');
      return;
    }

    setState(() => _enviando = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.post(
        '/participacion/propuestas',
        data: {
          'titulo': _tituloController.text.trim(),
          'descripcion': _descripcionController.text.trim(),
          'categoria': _categoriaSeleccionada,
        },
      );

      if (!mounted) return;

      if (respuesta.success) {
        FlavorSnackbar.showSuccess(context, 'Propuesta creada correctamente');
        Navigator.pop(context, true);
      } else {
        FlavorSnackbar.showError(
            context, respuesta.error ?? 'Error al crear la propuesta');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    } finally {
      if (mounted) {
        setState(() => _enviando = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Nueva propuesta'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Encabezado
            Card(
              color: Colors.indigo.shade50,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: Colors.indigo.shade100,
                      child: Icon(Icons.lightbulb, color: Colors.indigo.shade700),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Comparte tu idea',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                            ),
                          ),
                          Text(
                            'Tu propuesta será revisada por la comunidad',
                            style: TextStyle(fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Título
            TextFormField(
              controller: _tituloController,
              decoration: const InputDecoration(
                labelText: 'Título de la propuesta',
                hintText: 'Describe brevemente tu idea',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.title),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El título es obligatorio';
                }
                if (value.trim().length < 10) {
                  return 'El título debe tener al menos 10 caracteres';
                }
                return null;
              },
              maxLength: 150,
            ),
            const SizedBox(height: 16),

            // Categoría
            DropdownButtonFormField<String>(
              value: _categoriaSeleccionada,
              decoration: const InputDecoration(
                labelText: 'Categoría',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.category),
              ),
              items: _categorias
                  .map((cat) => DropdownMenuItem(
                        value: cat['id'],
                        child: Text(cat['label']!),
                      ))
                  .toList(),
              onChanged: (value) {
                setState(() => _categoriaSeleccionada = value ?? '');
              },
            ),
            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción detallada',
                hintText: 'Explica tu propuesta con detalle...',
                border: OutlineInputBorder(),
                alignLabelWithHint: true,
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'La descripción es obligatoria';
                }
                if (value.trim().length < 50) {
                  return 'La descripción debe tener al menos 50 caracteres';
                }
                return null;
              },
              maxLines: 6,
              maxLength: 2000,
            ),
            const SizedBox(height: 24),

            // Botón enviar
            FilledButton.icon(
              onPressed: _enviando ? null : _enviarPropuesta,
              icon: _enviando
                  ? const FlavorInlineSpinner()
                  : const Icon(Icons.send),
              label: Text(_enviando ? 'Enviando...' : 'Enviar propuesta'),
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
            ),
            const SizedBox(height: 16),

            // Aviso
            Card(
              color: Colors.amber.shade50,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, color: Colors.amber.shade800),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Las propuestas pasan por un proceso de revisión antes de ser publicadas.',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.amber.shade900,
                        ),
                      ),
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
