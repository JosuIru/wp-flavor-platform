part of 'campanias_screen.dart';

// =============================================================================
// MODELOS
// =============================================================================

/// Campaña ciudadana
class _Campania {
  final int id;
  final String titulo;
  final String descripcion;
  final String tipo;
  final String estado;
  final String? objetivoDescripcion;
  final int objetivoFirmas;
  final int firmasActuales;
  final String? fechaInicio;
  final String? fechaFin;
  final String? ubicacion;
  final double? latitud;
  final double? longitud;
  final String? imagen;
  final String? hashtags;
  final int? colectivoId;
  final String? colectivoNombre;
  final int creadorId;
  final String? creadorNombre;
  final String? creadorAvatar;
  final String visibilidad;
  final bool destacada;
  final String? createdAt;
  final int participantesCount;
  final int accionesCount;

  _Campania({
    required this.id,
    required this.titulo,
    required this.descripcion,
    required this.tipo,
    required this.estado,
    this.objetivoDescripcion,
    this.objetivoFirmas = 0,
    this.firmasActuales = 0,
    this.fechaInicio,
    this.fechaFin,
    this.ubicacion,
    this.latitud,
    this.longitud,
    this.imagen,
    this.hashtags,
    this.colectivoId,
    this.colectivoNombre,
    required this.creadorId,
    this.creadorNombre,
    this.creadorAvatar,
    this.visibilidad = 'publica',
    this.destacada = false,
    this.createdAt,
    this.participantesCount = 0,
    this.accionesCount = 0,
  });

  factory _Campania.fromJson(Map<String, dynamic> json) {
    return _Campania(
      id: json['id'] ?? 0,
      titulo: json['titulo'] ?? '',
      descripcion: json['descripcion'] ?? '',
      tipo: json['tipo'] ?? 'otra',
      estado: json['estado'] ?? 'planificada',
      objetivoDescripcion: json['objetivo_descripcion'],
      objetivoFirmas: json['objetivo_firmas'] ?? 0,
      firmasActuales: json['firmas_actuales'] ?? 0,
      fechaInicio: json['fecha_inicio'],
      fechaFin: json['fecha_fin'],
      ubicacion: json['ubicacion'],
      latitud: (json['latitud'] as num?)?.toDouble(),
      longitud: (json['longitud'] as num?)?.toDouble(),
      imagen: json['imagen'],
      hashtags: json['hashtags'],
      colectivoId: json['colectivo_id'],
      colectivoNombre: json['colectivo_nombre'],
      creadorId: json['creador_id'] ?? 0,
      creadorNombre: json['creador_nombre'],
      creadorAvatar: json['creador_avatar'],
      visibilidad: json['visibilidad'] ?? 'publica',
      destacada: json['destacada'] == true || json['destacada'] == 1,
      createdAt: json['created_at'],
      participantesCount: json['participantes_count'] ?? 0,
      accionesCount: json['acciones_count'] ?? 0,
    );
  }

  double get progreso {
    if (objetivoFirmas <= 0) return 0;
    return (firmasActuales / objetivoFirmas).clamp(0.0, 1.0);
  }

  bool get esRecogidaFirmas => tipo == 'recogida_firmas';

  String get tipoLabel {
    switch (tipo) {
      case 'protesta':
        return 'Protesta';
      case 'recogida_firmas':
        return 'Recogida de firmas';
      case 'concentracion':
        return 'Concentración';
      case 'boicot':
        return 'Boicot';
      case 'denuncia_publica':
        return 'Denuncia pública';
      case 'sensibilizacion':
        return 'Sensibilización';
      case 'accion_legal':
        return 'Acción legal';
      default:
        return 'Otra';
    }
  }

  String get tipoEmoji {
    switch (tipo) {
      case 'protesta':
        return '✊';
      case 'recogida_firmas':
        return '🖊️';
      case 'concentracion':
        return '👥';
      case 'boicot':
        return '🚫';
      case 'denuncia_publica':
        return '📢';
      case 'sensibilizacion':
        return '💡';
      case 'accion_legal':
        return '⚖️';
      default:
        return '📋';
    }
  }

  Color get tipoColor {
    switch (tipo) {
      case 'protesta':
        return Colors.red;
      case 'recogida_firmas':
        return Colors.blue;
      case 'concentracion':
        return Colors.purple;
      case 'boicot':
        return Colors.orange;
      case 'denuncia_publica':
        return Colors.amber;
      case 'sensibilizacion':
        return Colors.green;
      case 'accion_legal':
        return Colors.indigo;
      default:
        return Colors.grey;
    }
  }

  Color get estadoColor {
    switch (estado) {
      case 'activa':
        return Colors.green;
      case 'planificada':
        return Colors.grey;
      case 'pausada':
        return Colors.amber;
      case 'completada':
        return Colors.blue;
      case 'cancelada':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}

/// Acción programada de una campaña
class _Accion {
  final int id;
  final int campaniaId;
  final String titulo;
  final String? descripcion;
  final String tipo;
  final String fecha;
  final String? ubicacion;
  final String? puntoEncuentro;
  final int asistentesEsperados;
  final int asistentesConfirmados;
  final String estado;

  _Accion({
    required this.id,
    required this.campaniaId,
    required this.titulo,
    this.descripcion,
    required this.tipo,
    required this.fecha,
    this.ubicacion,
    this.puntoEncuentro,
    this.asistentesEsperados = 0,
    this.asistentesConfirmados = 0,
    this.estado = 'programada',
  });

  factory _Accion.fromJson(Map<String, dynamic> json) {
    return _Accion(
      id: json['id'] ?? 0,
      campaniaId: json['campania_id'] ?? 0,
      titulo: json['titulo'] ?? '',
      descripcion: json['descripcion'],
      tipo: json['tipo'] ?? 'otra',
      fecha: json['fecha'] ?? '',
      ubicacion: json['ubicacion'],
      puntoEncuentro: json['punto_encuentro'],
      asistentesEsperados: json['asistentes_esperados'] ?? 0,
      asistentesConfirmados: json['asistentes_confirmados'] ?? 0,
      estado: json['estado'] ?? 'programada',
    );
  }

  String get tipoLabel {
    switch (tipo) {
      case 'concentracion':
        return 'Concentración';
      case 'manifestacion':
        return 'Manifestación';
      case 'charla':
        return 'Charla';
      case 'taller':
        return 'Taller';
      case 'difusion':
        return 'Difusión';
      case 'reunion':
        return 'Reunión';
      case 'entrega_firmas':
        return 'Entrega de firmas';
      case 'rueda_prensa':
        return 'Rueda de prensa';
      default:
        return 'Otra';
    }
  }
}

// =============================================================================
// WIDGETS
// =============================================================================

/// Tarjeta de campaña
class _CampaniaCard extends StatelessWidget {
  final _Campania campania;
  final VoidCallback onTap;

  const _CampaniaCard({
    required this.campania,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen de cabecera
            if (campania.imagen != null && campania.imagen!.isNotEmpty)
              AspectRatio(
                aspectRatio: 16 / 9,
                child: Image.network(
                  campania.imagen!,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Container(
                    color: campania.tipoColor.withAlpha(51),
                    child: Center(
                      child: Text(
                        campania.tipoEmoji,
                        style: const TextStyle(fontSize: 48),
                      ),
                    ),
                  ),
                ),
              )
            else
              Container(
                height: 100,
                color: campania.tipoColor.withAlpha(51),
                child: Center(
                  child: Text(
                    campania.tipoEmoji,
                    style: const TextStyle(fontSize: 48),
                  ),
                ),
              ),

            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Badges
                  Row(
                    children: [
                      _TipoBadge(tipo: campania.tipo),
                      const SizedBox(width: 8),
                      _EstadoBadge(estado: campania.estado),
                      if (campania.destacada) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.amber,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.star, size: 12, color: Colors.white),
                              SizedBox(width: 4),
                              Text(
                                'Destacada',
                                style: TextStyle(
                                  fontSize: 10,
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),

                  const SizedBox(height: 12),

                  // Título
                  Text(
                    campania.titulo,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),

                  const SizedBox(height: 8),

                  // Descripción
                  Text(
                    campania.descripcion,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),

                  // Barra de progreso (si es recogida de firmas)
                  if (campania.esRecogidaFirmas && campania.objetivoFirmas > 0) ...[
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: LinearProgressIndicator(
                                  value: campania.progreso,
                                  minHeight: 8,
                                  backgroundColor: colorScheme.surfaceContainerHighest,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${campania.firmasActuales} de ${campania.objetivoFirmas} firmas',
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 16),
                        Text(
                          '${(campania.progreso * 100).toInt()}%',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                                color: colorScheme.primary,
                              ),
                        ),
                      ],
                    ),
                  ],

                  const SizedBox(height: 12),

                  // Footer
                  Row(
                    children: [
                      if (campania.colectivoNombre != null) ...[
                        Icon(
                          Icons.groups_outlined,
                          size: 16,
                          color: colorScheme.outline,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            campania.colectivoNombre!,
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                  color: colorScheme.outline,
                                ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ] else if (campania.creadorNombre != null) ...[
                        Icon(
                          Icons.person_outline,
                          size: 16,
                          color: colorScheme.outline,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            campania.creadorNombre!,
                            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                  color: colorScheme.outline,
                                ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                      if (campania.participantesCount > 0) ...[
                        const SizedBox(width: 16),
                        Icon(
                          Icons.people_outline,
                          size: 16,
                          color: colorScheme.outline,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${campania.participantesCount}',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: colorScheme.outline,
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

/// Badge de tipo
class _TipoBadge extends StatelessWidget {
  final String tipo;

  const _TipoBadge({required this.tipo});

  @override
  Widget build(BuildContext context) {
    final campania = _Campania(
      id: 0,
      titulo: '',
      descripcion: '',
      tipo: tipo,
      estado: '',
      creadorId: 0,
    );

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: campania.tipoColor.withAlpha(26),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: campania.tipoColor.withAlpha(77)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(campania.tipoEmoji, style: const TextStyle(fontSize: 12)),
          const SizedBox(width: 4),
          Text(
            campania.tipoLabel,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: campania.tipoColor,
            ),
          ),
        ],
      ),
    );
  }
}

/// Badge de estado
class _EstadoBadge extends StatelessWidget {
  final String estado;

  const _EstadoBadge({required this.estado});

  String get label {
    switch (estado) {
      case 'activa':
        return 'Activa';
      case 'planificada':
        return 'Planificada';
      case 'pausada':
        return 'Pausada';
      case 'completada':
        return 'Completada';
      case 'cancelada':
        return 'Cancelada';
      default:
        return estado;
    }
  }

  Color get color {
    switch (estado) {
      case 'activa':
        return Colors.green;
      case 'planificada':
        return Colors.grey;
      case 'pausada':
        return Colors.amber;
      case 'completada':
        return Colors.blue;
      case 'cancelada':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withAlpha(26),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

// =============================================================================
// PANTALLA DE DETALLE
// =============================================================================

class _CampaniaDetalleScreen extends ConsumerStatefulWidget {
  final _Campania campania;

  const _CampaniaDetalleScreen({required this.campania});

  @override
  ConsumerState<_CampaniaDetalleScreen> createState() =>
      _CampaniaDetalleScreenState();
}

class _CampaniaDetalleScreenState
    extends ConsumerState<_CampaniaDetalleScreen> {
  late _Campania _campania;
  List<_Accion> _acciones = [];
  bool _isLoading = true;
  bool _yaParticipa = false;
  bool _yaFirmo = false;

  @override
  void initState() {
    super.initState();
    _campania = widget.campania;
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() => _isLoading = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/flavor/v1/campanias/${_campania.id}');

      if (response.success && response.data != null) {
        final data = response.data!;
        if (data['campania'] != null) {
          setState(() {
            _campania = _Campania.fromJson(data['campania']);
          });
        }
        if (data['acciones'] != null) {
          setState(() {
            _acciones = (data['acciones'] as List)
                .map((json) => _Accion.fromJson(json))
                .toList();
          });
        }
        setState(() {
          _yaParticipa = data['ya_participa'] == true;
          _yaFirmo = data['ya_firmo'] == true;
        });
      }
    } catch (e) {
      debugPrint('Error cargando detalle: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // App bar con imagen
          SliverAppBar(
            expandedHeight: 200,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: _campania.imagen != null
                  ? Image.network(
                      _campania.imagen!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        color: _campania.tipoColor.withAlpha(51),
                        child: Center(
                          child: Text(
                            _campania.tipoEmoji,
                            style: const TextStyle(fontSize: 72),
                          ),
                        ),
                      ),
                    )
                  : Container(
                      color: _campania.tipoColor.withAlpha(51),
                      child: Center(
                        child: Text(
                          _campania.tipoEmoji,
                          style: const TextStyle(fontSize: 72),
                        ),
                      ),
                    ),
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.share),
                onPressed: _compartir,
              ),
            ],
          ),

          // Indicador de carga
          if (_isLoading)
            const SliverToBoxAdapter(
              child: LinearProgressIndicator(),
            ),

          // Contenido
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Badges
                  Row(
                    children: [
                      _TipoBadge(tipo: _campania.tipo),
                      const SizedBox(width: 8),
                      _EstadoBadge(estado: _campania.estado),
                    ],
                  ),

                  const SizedBox(height: 16),

                  // Título
                  Text(
                    _campania.titulo,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),

                  // Organizador
                  if (_campania.colectivoNombre != null ||
                      _campania.creadorNombre != null) ...[
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 16,
                          backgroundImage: _campania.creadorAvatar != null
                              ? NetworkImage(_campania.creadorAvatar!)
                              : null,
                          child: _campania.creadorAvatar == null
                              ? const Icon(Icons.person, size: 16)
                              : null,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _campania.colectivoNombre ?? _campania.creadorNombre ?? '',
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      ],
                    ),
                  ],

                  const SizedBox(height: 24),

                  // Progreso de firmas
                  if (_campania.esRecogidaFirmas && _campania.objetivoFirmas > 0)
                    _ProgresoFirmasWidget(campania: _campania),

                  // Descripción
                  const SizedBox(height: 24),
                  Text(
                    'Descripción',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _campania.descripcion,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),

                  // Objetivo
                  if (_campania.objetivoDescripcion != null &&
                      _campania.objetivoDescripcion!.isNotEmpty) ...[
                    const SizedBox(height: 24),
                    Text(
                      'Objetivo',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      _campania.objetivoDescripcion!,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ],

                  // Fechas
                  if (_campania.fechaInicio != null) ...[
                    const SizedBox(height: 24),
                    _InfoRow(
                      icon: Icons.calendar_today,
                      label: 'Fecha inicio',
                      value: _campania.fechaInicio!,
                    ),
                    if (_campania.fechaFin != null)
                      _InfoRow(
                        icon: Icons.event,
                        label: 'Fecha fin',
                        value: _campania.fechaFin!,
                      ),
                  ],

                  // Ubicación
                  if (_campania.ubicacion != null &&
                      _campania.ubicacion!.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    _InfoRow(
                      icon: Icons.location_on,
                      label: 'Ubicación',
                      value: _campania.ubicacion!,
                    ),
                  ],

                  // Hashtags
                  if (_campania.hashtags != null &&
                      _campania.hashtags!.isNotEmpty) ...[
                    const SizedBox(height: 24),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: _campania.hashtags!.split(' ').map((tag) {
                        return Chip(
                          label: Text(tag),
                          backgroundColor: colorScheme.primaryContainer,
                          labelStyle: TextStyle(color: colorScheme.onPrimaryContainer),
                        );
                      }).toList(),
                    ),
                  ],

                  // Acciones programadas
                  if (_acciones.isNotEmpty) ...[
                    const SizedBox(height: 32),
                    Text(
                      'Próximas acciones',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 12),
                    ..._acciones.map((accion) => _AccionCard(accion: accion)),
                  ],

                  const SizedBox(height: 100), // Espacio para FAB
                ],
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: _buildFAB(),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
    );
  }

  Widget? _buildFAB() {
    if (_campania.estado != 'activa') return null;

    if (_campania.esRecogidaFirmas && !_yaFirmo) {
      return FloatingActionButton.extended(
        onPressed: _firmar,
        icon: const Icon(Icons.edit),
        label: const Text('Firmar campaña'),
      );
    }

    if (!_yaParticipa) {
      return FloatingActionButton.extended(
        onPressed: _participar,
        icon: const Icon(Icons.group_add),
        label: const Text('Unirse'),
      );
    }

    return null;
  }

  void _compartir() {
    final titulo = _campania.titulo;
    final descripcion = _campania.descripcion ?? '';
    final mensaje = '¡Apoya esta campaña!\n\n$titulo\n\n$descripcion';
    Share.share(mensaje);
  }

  Future<void> _firmar() async {
    final resultado = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) => _FirmarSheet(campaniaId: _campania.id),
    );

    if (resultado == true) {
      _cargarDetalle();
    }
  }

  Future<void> _participar() async {
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/flavor/v1/campanias/${_campania.id}/participar',
        data: {},
      );

      if (!mounted) return;

      if (response.success) {
        Haptics.success();
        FlavorSnackbar.showSuccess(context, 'Te has unido a la campaña');
        _cargarDetalle();
      } else {
        FlavorSnackbar.showError(context, response.error ?? 'Error al unirse');
      }
    } catch (e) {
      if (!mounted) return;
      FlavorSnackbar.showError(context, 'Error: $e');
    }
  }
}

/// Widget de progreso de firmas
class _ProgresoFirmasWidget extends StatelessWidget {
  final _Campania campania;

  const _ProgresoFirmasWidget({required this.campania});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: colorScheme.primaryContainer.withAlpha(77),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '${campania.firmasActuales}',
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: colorScheme.primary,
                        ),
                  ),
                  Text(
                    'firmas conseguidas',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${campania.objetivoFirmas}',
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  Text(
                    'objetivo',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: campania.progreso,
              minHeight: 12,
              backgroundColor: colorScheme.surfaceContainerHighest,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            '${(campania.progreso * 100).toInt()}% del objetivo',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
          ),
        ],
      ),
    );
  }
}

/// Fila de información
class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Theme.of(context).colorScheme.outline),
          const SizedBox(width: 12),
          Text(
            '$label: ',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: Theme.of(context).colorScheme.outline,
                ),
          ),
          Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium,
          ),
        ],
      ),
    );
  }
}

/// Tarjeta de acción
class _AccionCard extends StatelessWidget {
  final _Accion accion;

  const _AccionCard({required this.accion});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Theme.of(context).colorScheme.primaryContainer,
          child: Icon(
            Icons.event,
            color: Theme.of(context).colorScheme.primary,
          ),
        ),
        title: Text(accion.titulo),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(accion.tipoLabel),
            if (accion.fecha.isNotEmpty)
              Text(
                accion.fecha,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.primary,
                  fontWeight: FontWeight.w500,
                ),
              ),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              '${accion.asistentesConfirmados}',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const Text('asistentes', style: TextStyle(fontSize: 10)),
          ],
        ),
        isThreeLine: true,
      ),
    );
  }
}

// =============================================================================
// SHEETS Y FORMULARIOS
// =============================================================================

/// Sheet para firmar campaña
class _FirmarSheet extends ConsumerStatefulWidget {
  final int campaniaId;

  const _FirmarSheet({required this.campaniaId});

  @override
  ConsumerState<_FirmarSheet> createState() => _FirmarSheetState();
}

class _FirmarSheetState extends ConsumerState<_FirmarSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nombreController = TextEditingController();
  final _emailController = TextEditingController();
  final _localidadController = TextEditingController();
  final _comentarioController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nombreController.dispose();
    _emailController.dispose();
    _localidadController.dispose();
    _comentarioController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Firmar campaña',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 24),
              TextFormField(
                controller: _nombreController,
                decoration: const InputDecoration(
                  labelText: 'Nombre completo *',
                  prefixIcon: Icon(Icons.person),
                ),
                validator: (v) =>
                    v?.isEmpty ?? true ? 'Ingresa tu nombre' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _emailController,
                decoration: const InputDecoration(
                  labelText: 'Email *',
                  prefixIcon: Icon(Icons.email),
                ),
                keyboardType: TextInputType.emailAddress,
                validator: (v) {
                  if (v?.isEmpty ?? true) return 'Ingresa tu email';
                  if (!v!.contains('@')) return 'Email inválido';
                  return null;
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _localidadController,
                decoration: const InputDecoration(
                  labelText: 'Localidad (opcional)',
                  prefixIcon: Icon(Icons.location_city),
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _comentarioController,
                decoration: const InputDecoration(
                  labelText: 'Comentario de apoyo (opcional)',
                  prefixIcon: Icon(Icons.comment),
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 24),
              FilledButton(
                onPressed: _isSubmitting ? null : _firmar,
                child: _isSubmitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Firmar'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _firmar() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/flavor/v1/campanias/${widget.campaniaId}/firmar',
        data: {
          'nombre': _nombreController.text,
          'email': _emailController.text,
          if (_localidadController.text.isNotEmpty)
            'localidad': _localidadController.text,
          if (_comentarioController.text.isNotEmpty)
            'comentario': _comentarioController.text,
        },
      );

      if (!mounted) return;

      if (response.success) {
        Haptics.success();
        FlavorSnackbar.showSuccess(context, '¡Gracias por tu firma!');
        Navigator.pop(context, true);
      } else {
        FlavorSnackbar.showError(context, response.error ?? 'Error al firmar');
      }
    } catch (e) {
      if (!mounted) return;
      FlavorSnackbar.showError(context, 'Error: $e');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }
}

// =============================================================================
// CREAR CAMPAÑA
// =============================================================================

class _CrearCampaniaScreen extends ConsumerStatefulWidget {
  const _CrearCampaniaScreen();

  @override
  ConsumerState<_CrearCampaniaScreen> createState() =>
      _CrearCampaniaScreenState();
}

class _CrearCampaniaScreenState extends ConsumerState<_CrearCampaniaScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _objetivoController = TextEditingController();
  final _objetivoFirmasController = TextEditingController();
  final _ubicacionController = TextEditingController();

  String _tipo = 'recogida_firmas';
  String _visibilidad = 'publica';
  bool _isSubmitting = false;

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    _objetivoController.dispose();
    _objetivoFirmasController.dispose();
    _ubicacionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Nueva campaña'),
        actions: [
          TextButton.icon(
            onPressed: _isSubmitting ? null : _crear,
            icon: _isSubmitting
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.check),
            label: const Text('Crear'),
          ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Tipo
            DropdownButtonFormField<String>(
              value: _tipo,
              decoration: const InputDecoration(
                labelText: 'Tipo de campaña *',
                prefixIcon: Icon(Icons.category),
              ),
              items: const [
                DropdownMenuItem(
                  value: 'recogida_firmas',
                  child: Text('🖊️ Recogida de firmas'),
                ),
                DropdownMenuItem(
                  value: 'protesta',
                  child: Text('✊ Protesta'),
                ),
                DropdownMenuItem(
                  value: 'concentracion',
                  child: Text('👥 Concentración'),
                ),
                DropdownMenuItem(
                  value: 'boicot',
                  child: Text('🚫 Boicot'),
                ),
                DropdownMenuItem(
                  value: 'sensibilizacion',
                  child: Text('💡 Sensibilización'),
                ),
                DropdownMenuItem(
                  value: 'denuncia_publica',
                  child: Text('📢 Denuncia pública'),
                ),
                DropdownMenuItem(
                  value: 'accion_legal',
                  child: Text('⚖️ Acción legal'),
                ),
                DropdownMenuItem(
                  value: 'otra',
                  child: Text('📋 Otra'),
                ),
              ],
              onChanged: (v) => setState(() => _tipo = v!),
            ),

            const SizedBox(height: 16),

            // Título
            TextFormField(
              controller: _tituloController,
              decoration: const InputDecoration(
                labelText: 'Título *',
                prefixIcon: Icon(Icons.title),
              ),
              validator: (v) =>
                  v?.isEmpty ?? true ? 'Ingresa un título' : null,
            ),

            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción *',
                prefixIcon: Icon(Icons.description),
                alignLabelWithHint: true,
              ),
              maxLines: 4,
              validator: (v) =>
                  v?.isEmpty ?? true ? 'Ingresa una descripción' : null,
            ),

            const SizedBox(height: 16),

            // Objetivo
            TextFormField(
              controller: _objetivoController,
              decoration: const InputDecoration(
                labelText: 'Objetivo de la campaña',
                prefixIcon: Icon(Icons.flag),
                alignLabelWithHint: true,
              ),
              maxLines: 2,
            ),

            // Objetivo de firmas (solo si es recogida)
            if (_tipo == 'recogida_firmas') ...[
              const SizedBox(height: 16),
              TextFormField(
                controller: _objetivoFirmasController,
                decoration: const InputDecoration(
                  labelText: 'Objetivo de firmas',
                  prefixIcon: Icon(Icons.edit),
                  suffixText: 'firmas',
                ),
                keyboardType: TextInputType.number,
              ),
            ],

            const SizedBox(height: 16),

            // Ubicación
            TextFormField(
              controller: _ubicacionController,
              decoration: const InputDecoration(
                labelText: 'Ubicación (opcional)',
                prefixIcon: Icon(Icons.location_on),
              ),
            ),

            const SizedBox(height: 16),

            // Visibilidad
            DropdownButtonFormField<String>(
              value: _visibilidad,
              decoration: const InputDecoration(
                labelText: 'Visibilidad',
                prefixIcon: Icon(Icons.visibility),
              ),
              items: const [
                DropdownMenuItem(value: 'publica', child: Text('Pública')),
                DropdownMenuItem(value: 'miembros', child: Text('Solo miembros')),
                DropdownMenuItem(value: 'privada', child: Text('Privada')),
              ],
              onChanged: (v) => setState(() => _visibilidad = v!),
            ),

            const SizedBox(height: 32),

            FilledButton.icon(
              onPressed: _isSubmitting ? null : _crear,
              icon: _isSubmitting
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.campaign),
              label: const Text('Crear campaña'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _crear() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/flavor/v1/campanias',
        data: {
          'titulo': _tituloController.text,
          'descripcion': _descripcionController.text,
          'tipo': _tipo,
          'visibilidad': _visibilidad,
          if (_objetivoController.text.isNotEmpty)
            'objetivo_descripcion': _objetivoController.text,
          if (_objetivoFirmasController.text.isNotEmpty)
            'objetivo_firmas': int.tryParse(_objetivoFirmasController.text) ?? 0,
          if (_ubicacionController.text.isNotEmpty)
            'ubicacion': _ubicacionController.text,
        },
      );

      if (!mounted) return;

      if (response.success) {
        Haptics.success();
        FlavorSnackbar.showSuccess(context, 'Campaña creada');
        Navigator.pop(context, true);
      } else {
        FlavorSnackbar.showError(context, response.error ?? 'Error al crear');
      }
    } catch (e) {
      if (!mounted) return;
      FlavorSnackbar.showError(context, 'Error: $e');
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }
}
