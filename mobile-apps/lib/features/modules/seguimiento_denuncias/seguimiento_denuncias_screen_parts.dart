part of 'seguimiento_denuncias_screen.dart';

// =============================================================================
// TARJETA DE DENUNCIA
// =============================================================================

class _DenunciaCard extends StatelessWidget {
  final dynamic denuncia;
  final bool esMia;
  final VoidCallback onTap;

  const _DenunciaCard({
    required this.denuncia,
    this.esMia = false,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final mapa = denuncia as Map<String, dynamic>;
    final titulo = mapa['titulo'] ?? 'Sin título';
    final tipo = mapa['tipo'] ?? 'denuncia';
    final estado = mapa['estado'] ?? 'presentada';
    final organismo = mapa['organismo_destino'] ?? '';
    final fechaPresentacion = mapa['fecha_presentacion'] ?? '';
    final prioridad = mapa['prioridad'] ?? 'media';
    final diasRestantes = mapa['dias_restantes'];
    final miRol = mapa['mi_rol'];

    final colorEstado = _getColorEstado(estado);
    final iconoEstado = _getIconoEstado(estado);

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
              // Cabecera
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
                            if (miRol != null)
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.blue.shade50,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  _formatearRol(miRol),
                                  style: TextStyle(
                                    fontSize: 10,
                                    color: Colors.blue.shade700,
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
                              estado: _formatearEstado(estado),
                              color: colorEstado,
                            ),
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
                                _formatearTipo(tipo),
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.grey.shade700,
                                ),
                              ),
                            ),
                            if (prioridad == 'alta' || prioridad == 'urgente') ...[
                              const SizedBox(width: 8),
                              Icon(
                                Icons.priority_high,
                                size: 16,
                                color: prioridad == 'urgente'
                                    ? Colors.red
                                    : Colors.orange,
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

              // Organismo
              if (organismo.isNotEmpty) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    Icon(Icons.business, size: 14, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        organismo,
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 13,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ],

              // Fecha y plazo
              const SizedBox(height: 8),
              Row(
                children: [
                  if (fechaPresentacion.isNotEmpty) ...[
                    Icon(Icons.calendar_today,
                        size: 14, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Text(
                      _formatearFecha(fechaPresentacion),
                      style:
                          TextStyle(color: Colors.grey.shade600, fontSize: 12),
                    ),
                  ],
                  if (diasRestantes != null) ...[
                    const Spacer(),
                    Container(
                      padding:
                          const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: diasRestantes < 0
                            ? Colors.red.shade100
                            : diasRestantes <= 5
                                ? Colors.orange.shade100
                                : Colors.green.shade100,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            diasRestantes < 0
                                ? Icons.warning
                                : Icons.hourglass_bottom,
                            size: 12,
                            color: diasRestantes < 0
                                ? Colors.red
                                : diasRestantes <= 5
                                    ? Colors.orange
                                    : Colors.green,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            diasRestantes < 0
                                ? 'Vencido hace ${-diasRestantes} días'
                                : '$diasRestantes días',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w500,
                              color: diasRestantes < 0
                                  ? Colors.red
                                  : diasRestantes <= 5
                                      ? Colors.orange
                                      : Colors.green,
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
      ),
    );
  }

  Color _getColorEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'presentada':
        return Colors.blue;
      case 'en_tramite':
        return Colors.orange;
      case 'requerimiento':
        return Colors.red;
      case 'silencio':
        return Colors.grey;
      case 'resuelta_favorable':
        return Colors.green;
      case 'resuelta_desfavorable':
        return Colors.red.shade700;
      case 'archivada':
        return Colors.grey.shade600;
      case 'recurrida':
        return Colors.purple;
      default:
        return Colors.blue;
    }
  }

  IconData _getIconoEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'presentada':
        return Icons.send;
      case 'en_tramite':
        return Icons.pending;
      case 'requerimiento':
        return Icons.warning;
      case 'silencio':
        return Icons.hourglass_empty;
      case 'resuelta_favorable':
        return Icons.check_circle;
      case 'resuelta_desfavorable':
        return Icons.cancel;
      case 'archivada':
        return Icons.archive;
      case 'recurrida':
        return Icons.gavel;
      default:
        return Icons.folder;
    }
  }

  String _formatearEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'presentada':
        return 'Presentada';
      case 'en_tramite':
        return 'En trámite';
      case 'requerimiento':
        return 'Requerimiento';
      case 'silencio':
        return 'Silencio adm.';
      case 'resuelta_favorable':
        return 'Favorable';
      case 'resuelta_desfavorable':
        return 'Desfavorable';
      case 'archivada':
        return 'Archivada';
      case 'recurrida':
        return 'Recurrida';
      default:
        return estado;
    }
  }

  String _formatearTipo(String tipo) {
    switch (tipo.toLowerCase()) {
      case 'denuncia':
        return 'Denuncia';
      case 'queja':
        return 'Queja';
      case 'recurso':
        return 'Recurso';
      case 'solicitud':
        return 'Solicitud';
      case 'peticion':
        return 'Petición';
      default:
        return tipo;
    }
  }

  String _formatearRol(String rol) {
    switch (rol.toLowerCase()) {
      case 'denunciante':
        return 'Autor';
      case 'colaborador':
        return 'Colaborador';
      case 'seguidor':
        return 'Siguiendo';
      case 'afectado':
        return 'Afectado';
      default:
        return rol;
    }
  }

  String _formatearFecha(String fecha) {
    try {
      final partes = fecha.split('-');
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
// PANTALLA DETALLE DENUNCIA
// =============================================================================

class _DenunciaDetalleScreen extends ConsumerStatefulWidget {
  final dynamic denunciaId;

  const _DenunciaDetalleScreen({required this.denunciaId});

  @override
  ConsumerState<_DenunciaDetalleScreen> createState() =>
      _DenunciaDetalleScreenState();
}

class _DenunciaDetalleScreenState
    extends ConsumerState<_DenunciaDetalleScreen> {
  Map<String, dynamic>? _denuncia;
  List<dynamic> _timeline = [];
  bool _cargando = true;
  String? _error;
  bool _siguiendo = false;

  @override
  void initState() {
    super.initState();
    _cargarDenuncia();
  }

  Future<void> _cargarDenuncia() async {
    setState(() {
      _cargando = true;
      _error = null;
    });
    try {
      final api = ref.read(apiClientProvider);

      // Cargar denuncia
      final respuestaDenuncia =
          await api.get('/denuncias/${widget.denunciaId}');
      if (respuestaDenuncia.success && respuestaDenuncia.data != null) {
        _denuncia = respuestaDenuncia.data!['denuncia'];

        // Verificar si estoy siguiendo
        final participantes = _denuncia?['participantes'] as List<dynamic>? ?? [];
        // Simplificación: si tengo participantes y alguno tiene mi usuario
        _siguiendo = participantes.isNotEmpty;
      } else {
        _error = respuestaDenuncia.error ?? 'Error al cargar la denuncia';
      }

      // Cargar timeline
      final respuestaTimeline =
          await api.get('/denuncias/${widget.denunciaId}/timeline');
      if (respuestaTimeline.success && respuestaTimeline.data != null) {
        _timeline = respuestaTimeline.data!['eventos'] ?? [];
      }
    } catch (e) {
      _error = e.toString();
    } finally {
      setState(() => _cargando = false);
    }
  }

  Future<void> _toggleSeguir() async {
    try {
      final api = ref.read(apiClientProvider);
      final respuesta =
          await api.post('/denuncias/${widget.denunciaId}/seguir');

      if (!mounted) return;

      if (respuesta.success) {
        final siguiendo = respuesta.data?['siguiendo'] ?? false;
        setState(() => _siguiendo = siguiendo);
        FlavorSnackbar.showSuccess(
          context,
          siguiendo
              ? 'Ahora sigues esta denuncia'
              : 'Has dejado de seguir esta denuncia',
        );
      } else {
        FlavorSnackbar.showError(
            context, respuesta.error ?? 'Error al procesar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de Denuncia'),
        backgroundColor: Colors.red.shade700,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: Icon(_siguiendo ? Icons.bookmark : Icons.bookmark_border),
            onPressed: _toggleSeguir,
            tooltip: _siguiendo ? 'Dejar de seguir' : 'Seguir',
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDenuncia,
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _error != null
              ? FlavorErrorState(
                  message: _error!,
                  onRetry: _cargarDenuncia,
                  icon: Icons.folder_off,
                )
              : _denuncia == null
                  ? const FlavorEmptyState(
                      icon: Icons.folder_off,
                      title: 'Denuncia no encontrada',
                    )
                  : _buildContenido(),
    );
  }

  Widget _buildContenido() {
    final titulo = _denuncia!['titulo'] ?? 'Sin título';
    final descripcion = _denuncia!['descripcion'] ?? '';
    final tipo = _denuncia!['tipo'] ?? 'denuncia';
    final estado = _denuncia!['estado'] ?? 'presentada';
    final organismo = _denuncia!['organismo_destino'] ?? '';
    final ambito = _denuncia!['ambito'] ?? '';
    final numeroRegistro = _denuncia!['numero_registro'] ?? '';
    final fechaPresentacion = _denuncia!['fecha_presentacion'] ?? '';
    final fechaLimite = _denuncia!['fecha_limite_respuesta'] ?? '';
    final diasRestantes = _denuncia!['dias_restantes'];
    final prioridad = _denuncia!['prioridad'] ?? 'media';
    final denuncianteNombre = _denuncia!['denunciante_nombre'] ?? '';

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
                Text(
                  titulo,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 20,
                  ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _buildChip(_formatearTipo(tipo), Colors.grey),
                    _buildChip(_formatearEstado(estado), _getColorEstado(estado)),
                    if (ambito.isNotEmpty)
                      _buildChip(_formatearAmbito(ambito), Colors.indigo),
                    if (prioridad == 'alta' || prioridad == 'urgente')
                      _buildChip(
                        prioridad == 'urgente' ? 'URGENTE' : 'Alta prioridad',
                        prioridad == 'urgente' ? Colors.red : Colors.orange,
                      ),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Información del organismo
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Organismo destino',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.business, size: 20),
                    const SizedBox(width: 8),
                    Expanded(child: Text(organismo)),
                  ],
                ),
                if (numeroRegistro.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.tag, size: 20),
                      const SizedBox(width: 8),
                      Text('Nº Registro: $numeroRegistro'),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Fechas y plazo
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Plazos',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Presentación',
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontSize: 12,
                            ),
                          ),
                          Text(
                            _formatearFecha(fechaPresentacion),
                            style: const TextStyle(fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ),
                    if (fechaLimite.isNotEmpty)
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Límite respuesta',
                              style: TextStyle(
                                color: Colors.grey.shade600,
                                fontSize: 12,
                              ),
                            ),
                            Text(
                              _formatearFecha(fechaLimite),
                              style: const TextStyle(fontWeight: FontWeight.w500),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
                if (diasRestantes != null) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: diasRestantes < 0
                          ? Colors.red.shade50
                          : diasRestantes <= 5
                              ? Colors.orange.shade50
                              : Colors.green.shade50,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          diasRestantes < 0
                              ? Icons.warning
                              : Icons.hourglass_bottom,
                          color: diasRestantes < 0
                              ? Colors.red
                              : diasRestantes <= 5
                                  ? Colors.orange
                                  : Colors.green,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          diasRestantes < 0
                              ? 'Plazo vencido hace ${-diasRestantes} días'
                              : '$diasRestantes días restantes',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: diasRestantes < 0
                                ? Colors.red
                                : diasRestantes <= 5
                                    ? Colors.orange
                                    : Colors.green,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),

        // Descripción
        if (descripcion.isNotEmpty) ...[
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Descripción',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 8),
                  Text(descripcion),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
        ],

        // Timeline
        Row(
          children: [
            const Text(
              'Timeline',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: Colors.grey.shade200,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                '${_timeline.length}',
                style: const TextStyle(fontSize: 12),
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),

        if (_timeline.isEmpty)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Center(
                child: Text(
                  'Sin eventos registrados',
                  style: TextStyle(color: Colors.grey.shade500),
                ),
              ),
            ),
          )
        else
          ..._timeline.map((evento) => _buildEventoCard(evento)),
      ],
    );
  }

  Widget _buildChip(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }

  Widget _buildEventoCard(dynamic evento) {
    final mapa = evento as Map<String, dynamic>;
    final tipo = mapa['tipo'] ?? 'nota';
    final titulo = mapa['titulo'] ?? '';
    final descripcion = mapa['descripcion'] ?? '';
    final fecha = mapa['created_at'] ?? '';
    final automatico = mapa['automatico'] == 1 || mapa['automatico'] == true;
    final estadoNuevo = mapa['estado_nuevo'];

    IconData icono;
    Color colorIcono;
    switch (tipo) {
      case 'creacion':
        icono = Icons.add_circle;
        colorIcono = Colors.green;
        break;
      case 'cambio_estado':
        icono = Icons.swap_horiz;
        colorIcono = Colors.blue;
        break;
      case 'documento_recibido':
        icono = Icons.download;
        colorIcono = Colors.purple;
        break;
      case 'documento_enviado':
        icono = Icons.upload;
        colorIcono = Colors.indigo;
        break;
      case 'respuesta':
        icono = Icons.reply;
        colorIcono = Colors.teal;
        break;
      case 'requerimiento':
        icono = Icons.warning;
        colorIcono = Colors.red;
        break;
      case 'recurso':
        icono = Icons.gavel;
        colorIcono = Colors.purple;
        break;
      case 'plazo_vencido':
        icono = Icons.timer_off;
        colorIcono = Colors.red;
        break;
      default:
        icono = Icons.note;
        colorIcono = Colors.grey;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              radius: 16,
              backgroundColor: colorIcono.withOpacity(0.1),
              child: Icon(icono, size: 16, color: colorIcono),
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
                          style: const TextStyle(fontWeight: FontWeight.w500),
                        ),
                      ),
                      if (automatico)
                        const Icon(Icons.smart_toy, size: 14, color: Colors.grey),
                    ],
                  ),
                  if (descripcion.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      descripcion,
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 13,
                      ),
                    ),
                  ],
                  const SizedBox(height: 4),
                  Text(
                    _formatearFechaHora(fecha),
                    style: TextStyle(
                      color: Colors.grey.shade500,
                      fontSize: 11,
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

  String _formatearTipo(String tipo) {
    switch (tipo.toLowerCase()) {
      case 'denuncia':
        return 'Denuncia';
      case 'queja':
        return 'Queja';
      case 'recurso':
        return 'Recurso';
      case 'solicitud':
        return 'Solicitud';
      case 'peticion':
        return 'Petición';
      default:
        return tipo;
    }
  }

  String _formatearEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'presentada':
        return 'Presentada';
      case 'en_tramite':
        return 'En trámite';
      case 'requerimiento':
        return 'Requerimiento';
      case 'silencio':
        return 'Silencio administrativo';
      case 'resuelta_favorable':
        return 'Resuelta favorable';
      case 'resuelta_desfavorable':
        return 'Resuelta desfavorable';
      case 'archivada':
        return 'Archivada';
      case 'recurrida':
        return 'Recurrida';
      default:
        return estado;
    }
  }

  String _formatearAmbito(String ambito) {
    switch (ambito.toLowerCase()) {
      case 'municipal':
        return 'Municipal';
      case 'provincial':
        return 'Provincial';
      case 'autonomico':
        return 'Autonómico';
      case 'estatal':
        return 'Estatal';
      case 'europeo':
        return 'Europeo';
      default:
        return ambito;
    }
  }

  Color _getColorEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'presentada':
        return Colors.blue;
      case 'en_tramite':
        return Colors.orange;
      case 'requerimiento':
        return Colors.red;
      case 'silencio':
        return Colors.grey;
      case 'resuelta_favorable':
        return Colors.green;
      case 'resuelta_desfavorable':
        return Colors.red.shade700;
      case 'archivada':
        return Colors.grey.shade600;
      case 'recurrida':
        return Colors.purple;
      default:
        return Colors.blue;
    }
  }

  String _formatearFecha(String fecha) {
    try {
      final partes = fecha.split('-');
      if (partes.length >= 3) {
        return '${partes[2]}/${partes[1]}/${partes[0]}';
      }
    } catch (_) {}
    return fecha;
  }

  String _formatearFechaHora(String fechaHora) {
    try {
      final partes = fechaHora.split(' ');
      if (partes.isNotEmpty) {
        final fecha = partes[0].split('-');
        if (fecha.length >= 3) {
          final hora = partes.length > 1 ? partes[1].substring(0, 5) : '';
          return '${fecha[2]}/${fecha[1]}/${fecha[0]} ${hora}';
        }
      }
    } catch (_) {}
    return fechaHora;
  }
}

// =============================================================================
// PANTALLA CREAR DENUNCIA
// =============================================================================

class _CrearDenunciaScreen extends ConsumerStatefulWidget {
  const _CrearDenunciaScreen();

  @override
  ConsumerState<_CrearDenunciaScreen> createState() =>
      _CrearDenunciaScreenState();
}

class _CrearDenunciaScreenState extends ConsumerState<_CrearDenunciaScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _organismoController = TextEditingController();
  final _numeroRegistroController = TextEditingController();

  String _tipo = 'denuncia';
  String _ambito = 'municipal';
  String _prioridad = 'media';
  String _visibilidad = 'miembros';
  DateTime _fechaPresentacion = DateTime.now();
  int _plazoRespuesta = 30;
  bool _enviando = false;

  final List<Map<String, String>> _tiposOpciones = [
    {'id': 'denuncia', 'label': 'Denuncia'},
    {'id': 'queja', 'label': 'Queja'},
    {'id': 'recurso', 'label': 'Recurso'},
    {'id': 'solicitud', 'label': 'Solicitud'},
    {'id': 'peticion', 'label': 'Petición'},
  ];

  final List<Map<String, String>> _ambitosOpciones = [
    {'id': 'municipal', 'label': 'Municipal'},
    {'id': 'provincial', 'label': 'Provincial'},
    {'id': 'autonomico', 'label': 'Autonómico'},
    {'id': 'estatal', 'label': 'Estatal'},
    {'id': 'europeo', 'label': 'Europeo'},
  ];

  final List<Map<String, String>> _prioridadOpciones = [
    {'id': 'baja', 'label': 'Baja'},
    {'id': 'media', 'label': 'Media'},
    {'id': 'alta', 'label': 'Alta'},
    {'id': 'urgente', 'label': 'Urgente'},
  ];

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    _organismoController.dispose();
    _numeroRegistroController.dispose();
    super.dispose();
  }

  Future<void> _enviar() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _enviando = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.post(
        '/denuncias',
        data: {
          'titulo': _tituloController.text.trim(),
          'descripcion': _descripcionController.text.trim(),
          'organismo_destino': _organismoController.text.trim(),
          'numero_registro': _numeroRegistroController.text.trim(),
          'tipo': _tipo,
          'ambito': _ambito,
          'prioridad': _prioridad,
          'visibilidad': _visibilidad,
          'fecha_presentacion':
              '${_fechaPresentacion.year}-${_fechaPresentacion.month.toString().padLeft(2, '0')}-${_fechaPresentacion.day.toString().padLeft(2, '0')}',
          'plazo_respuesta': _plazoRespuesta,
        },
      );

      if (!mounted) return;

      if (respuesta.success) {
        FlavorSnackbar.showSuccess(context, 'Denuncia registrada correctamente');
        Navigator.pop(context, true);
      } else {
        FlavorSnackbar.showError(
            context, respuesta.error ?? 'Error al registrar');
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
        title: const Text('Nueva Denuncia'),
        backgroundColor: Colors.red.shade700,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Información básica
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Información básica',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 16),

                    // Título
                    TextFormField(
                      controller: _tituloController,
                      decoration: const InputDecoration(
                        labelText: 'Título de la denuncia',
                        hintText: 'Describe brevemente el asunto',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.title),
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'El título es obligatorio';
                        }
                        if (value.trim().length < 10) {
                          return 'Mínimo 10 caracteres';
                        }
                        return null;
                      },
                      maxLength: 200,
                    ),
                    const SizedBox(height: 16),

                    // Tipo y ámbito
                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _tipo,
                            decoration: const InputDecoration(
                              labelText: 'Tipo',
                              border: OutlineInputBorder(),
                            ),
                            items: _tiposOpciones
                                .map((t) => DropdownMenuItem(
                                      value: t['id'],
                                      child: Text(t['label']!),
                                    ))
                                .toList(),
                            onChanged: (value) =>
                                setState(() => _tipo = value ?? 'denuncia'),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _ambito,
                            decoration: const InputDecoration(
                              labelText: 'Ámbito',
                              border: OutlineInputBorder(),
                            ),
                            items: _ambitosOpciones
                                .map((a) => DropdownMenuItem(
                                      value: a['id'],
                                      child: Text(a['label']!),
                                    ))
                                .toList(),
                            onChanged: (value) =>
                                setState(() => _ambito = value ?? 'municipal'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Descripción
                    TextFormField(
                      controller: _descripcionController,
                      decoration: const InputDecoration(
                        labelText: 'Descripción detallada',
                        hintText: 'Explica los hechos con detalle...',
                        border: OutlineInputBorder(),
                        alignLabelWithHint: true,
                      ),
                      maxLines: 5,
                      maxLength: 5000,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Organismo destino
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Organismo destino',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 16),

                    TextFormField(
                      controller: _organismoController,
                      decoration: const InputDecoration(
                        labelText: 'Nombre del organismo',
                        hintText: 'Ej: Ayuntamiento de..., Consejería de...',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.business),
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'El organismo es obligatorio';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),

                    TextFormField(
                      controller: _numeroRegistroController,
                      decoration: const InputDecoration(
                        labelText: 'Número de registro (opcional)',
                        hintText: 'Si ya lo tienes',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.tag),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Fechas y plazos
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Fechas y plazos',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 16),

                    // Fecha presentación
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.calendar_today),
                      title: const Text('Fecha de presentación'),
                      subtitle: Text(
                        '${_fechaPresentacion.day}/${_fechaPresentacion.month}/${_fechaPresentacion.year}',
                      ),
                      trailing: const Icon(Icons.edit),
                      onTap: () async {
                        final fecha = await showDatePicker(
                          context: context,
                          initialDate: _fechaPresentacion,
                          firstDate:
                              DateTime.now().subtract(const Duration(days: 365)),
                          lastDate: DateTime.now(),
                        );
                        if (fecha != null) {
                          setState(() => _fechaPresentacion = fecha);
                        }
                      },
                    ),
                    const Divider(),

                    // Plazo respuesta
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.timer),
                      title: const Text('Plazo de respuesta'),
                      subtitle: Text('$_plazoRespuesta días'),
                      trailing: SizedBox(
                        width: 120,
                        child: Slider(
                          value: _plazoRespuesta.toDouble(),
                          min: 10,
                          max: 90,
                          divisions: 8,
                          onChanged: (value) =>
                              setState(() => _plazoRespuesta = value.toInt()),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Prioridad y visibilidad
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Opciones',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 16),

                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _prioridad,
                            decoration: const InputDecoration(
                              labelText: 'Prioridad',
                              border: OutlineInputBorder(),
                            ),
                            items: _prioridadOpciones
                                .map((p) => DropdownMenuItem(
                                      value: p['id'],
                                      child: Text(p['label']!),
                                    ))
                                .toList(),
                            onChanged: (value) =>
                                setState(() => _prioridad = value ?? 'media'),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _visibilidad,
                            decoration: const InputDecoration(
                              labelText: 'Visibilidad',
                              border: OutlineInputBorder(),
                            ),
                            items: const [
                              DropdownMenuItem(
                                  value: 'publica', child: Text('Pública')),
                              DropdownMenuItem(
                                  value: 'miembros', child: Text('Miembros')),
                              DropdownMenuItem(
                                  value: 'privada', child: Text('Privada')),
                            ],
                            onChanged: (value) =>
                                setState(() => _visibilidad = value ?? 'miembros'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Botón enviar
            FilledButton.icon(
              onPressed: _enviando ? null : _enviar,
              icon: _enviando
                  ? const FlavorInlineSpinner()
                  : const Icon(Icons.send),
              label: Text(_enviando ? 'Registrando...' : 'Registrar denuncia'),
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
                backgroundColor: Colors.red.shade700,
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
                        'El sistema calculará automáticamente la fecha límite de respuesta y te notificará si se acerca el plazo.',
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
