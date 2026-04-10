part of 'crowdfunding_screen.dart';

// ============================================================
// MODELOS
// ============================================================

class _Proyecto {
  final int id;
  final String titulo;
  final String slug;
  final String descripcion;
  final String? contenido;
  final String tipo;
  final String categoria;
  final String estado;
  final String modalidad;
  final double objetivoEur;
  final double objetivoSemilla;
  final double objetivoHours;
  final double recaudadoEur;
  final double recaudadoSemilla;
  final double recaudadoHours;
  final double porcentajeRecaudado;
  final double recaudadoTotalEur;
  final double objetivoTotalEur;
  final String monedaPrincipal;
  final bool aceptaEur;
  final bool aceptaSemilla;
  final bool aceptaHours;
  final double minimoAportacion;
  final bool permiteAportacionLibre;
  final DateTime? fechaInicio;
  final DateTime? fechaFin;
  final int? diasRestantes;
  final bool finalizado;
  final int diasDuracion;
  final int aportantesCount;
  final int visualizaciones;
  final String? imagenPrincipal;
  final String? videoPrincipal;
  final List<String> galeria;
  final int creadorId;
  final String? creadorNombre;
  final String? creadorAvatar;
  final int? colectivoId;
  final String? colectivoNombre;
  final int? comunidadId;
  final String? moduloOrigen;
  final bool destacado;
  final bool verificado;
  final String visibilidad;
  final List<_Tier> tiers;
  final DateTime createdAt;

  _Proyecto({
    required this.id,
    required this.titulo,
    required this.slug,
    required this.descripcion,
    this.contenido,
    required this.tipo,
    required this.categoria,
    required this.estado,
    required this.modalidad,
    required this.objetivoEur,
    required this.objetivoSemilla,
    required this.objetivoHours,
    required this.recaudadoEur,
    required this.recaudadoSemilla,
    required this.recaudadoHours,
    required this.porcentajeRecaudado,
    required this.recaudadoTotalEur,
    required this.objetivoTotalEur,
    required this.monedaPrincipal,
    required this.aceptaEur,
    required this.aceptaSemilla,
    required this.aceptaHours,
    required this.minimoAportacion,
    required this.permiteAportacionLibre,
    this.fechaInicio,
    this.fechaFin,
    this.diasRestantes,
    required this.finalizado,
    required this.diasDuracion,
    required this.aportantesCount,
    required this.visualizaciones,
    this.imagenPrincipal,
    this.videoPrincipal,
    required this.galeria,
    required this.creadorId,
    this.creadorNombre,
    this.creadorAvatar,
    this.colectivoId,
    this.colectivoNombre,
    this.comunidadId,
    this.moduloOrigen,
    required this.destacado,
    required this.verificado,
    required this.visibilidad,
    required this.tiers,
    required this.createdAt,
  });

  factory _Proyecto.fromJson(Map<String, dynamic> json) {
    return _Proyecto(
      id: json['id'] ?? 0,
      titulo: json['titulo'] ?? '',
      slug: json['slug'] ?? '',
      descripcion: json['descripcion'] ?? '',
      contenido: json['contenido'],
      tipo: json['tipo'] ?? 'otro',
      categoria: json['categoria'] ?? '',
      estado: json['estado'] ?? 'activo',
      modalidad: json['modalidad'] ?? 'flexible',
      objetivoEur: (json['objetivo_eur'] ?? 0).toDouble(),
      objetivoSemilla: (json['objetivo_semilla'] ?? 0).toDouble(),
      objetivoHours: (json['objetivo_hours'] ?? 0).toDouble(),
      recaudadoEur: (json['recaudado_eur'] ?? 0).toDouble(),
      recaudadoSemilla: (json['recaudado_semilla'] ?? 0).toDouble(),
      recaudadoHours: (json['recaudado_hours'] ?? 0).toDouble(),
      porcentajeRecaudado: (json['porcentaje_recaudado'] ?? 0).toDouble(),
      recaudadoTotalEur: (json['recaudado_total_eur'] ?? json['recaudado_eur'] ?? 0).toDouble(),
      objetivoTotalEur: (json['objetivo_total_eur'] ?? json['objetivo_eur'] ?? 0).toDouble(),
      monedaPrincipal: json['moneda_principal'] ?? 'eur',
      aceptaEur: json['acepta_eur'] == true || json['acepta_eur'] == 1,
      aceptaSemilla: json['acepta_semilla'] == true || json['acepta_semilla'] == 1,
      aceptaHours: json['acepta_hours'] == true || json['acepta_hours'] == 1,
      minimoAportacion: (json['minimo_aportacion'] ?? 1).toDouble(),
      permiteAportacionLibre: json['permite_aportacion_libre'] == true || json['permite_aportacion_libre'] == 1,
      fechaInicio: json['fecha_inicio'] != null ? DateTime.tryParse(json['fecha_inicio']) : null,
      fechaFin: json['fecha_fin'] != null ? DateTime.tryParse(json['fecha_fin']) : null,
      diasRestantes: json['dias_restantes'],
      finalizado: json['finalizado'] == true,
      diasDuracion: json['dias_duracion'] ?? 30,
      aportantesCount: json['aportantes_count'] ?? 0,
      visualizaciones: json['visualizaciones'] ?? 0,
      imagenPrincipal: json['imagen_principal'],
      videoPrincipal: json['video_principal'],
      galeria: (json['galeria'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? [],
      creadorId: json['creador_id'] ?? 0,
      creadorNombre: json['creador_nombre'],
      creadorAvatar: json['creador_avatar'],
      colectivoId: json['colectivo_id'],
      colectivoNombre: json['colectivo_nombre'],
      comunidadId: json['comunidad_id'],
      moduloOrigen: json['modulo_origen'],
      destacado: json['destacado'] == true || json['destacado'] == 1,
      verificado: json['verificado'] == true || json['verificado'] == 1,
      visibilidad: json['visibilidad'] ?? 'publico',
      tiers: (json['tiers'] as List<dynamic>?)?.map((t) => _Tier.fromJson(t)).toList() ?? [],
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
    );
  }

  String get tipoEmoji {
    switch (tipo) {
      case 'album': return '🎵';
      case 'tour': return '🎤';
      case 'produccion': return '🎬';
      case 'equipamiento': return '🔧';
      case 'espacio': return '🏠';
      case 'evento': return '🎉';
      case 'social': return '🤝';
      case 'emergencia': return '🆘';
      default: return '📦';
    }
  }

  String get tipoLabel {
    switch (tipo) {
      case 'album': return 'Álbum/Grabación';
      case 'tour': return 'Gira/Tour';
      case 'produccion': return 'Producción';
      case 'equipamiento': return 'Equipamiento';
      case 'espacio': return 'Espacio';
      case 'evento': return 'Evento';
      case 'social': return 'Proyecto Social';
      case 'emergencia': return 'Emergencia';
      default: return 'Otro';
    }
  }

  Color get tipoColor {
    switch (tipo) {
      case 'album': return Colors.pink;
      case 'tour': return Colors.purple;
      case 'produccion': return Colors.indigo;
      case 'equipamiento': return Colors.blueGrey;
      case 'espacio': return Colors.amber;
      case 'evento': return Colors.cyan;
      case 'social': return Colors.green;
      case 'emergencia': return Colors.red;
      default: return Colors.grey;
    }
  }

  String get estadoLabel {
    switch (estado) {
      case 'borrador': return 'Borrador';
      case 'revision': return 'En revisión';
      case 'activo': return 'Activo';
      case 'pausado': return 'Pausado';
      case 'exitoso': return 'Financiado';
      case 'fallido': return 'No financiado';
      case 'cancelado': return 'Cancelado';
      default: return estado;
    }
  }

  Color get estadoColor {
    switch (estado) {
      case 'borrador': return Colors.grey;
      case 'revision': return Colors.orange;
      case 'activo': return Colors.blue;
      case 'pausado': return Colors.amber;
      case 'exitoso': return Colors.green;
      case 'fallido': return Colors.red;
      case 'cancelado': return Colors.grey;
      default: return Colors.grey;
    }
  }

  String get modalidadLabel {
    switch (modalidad) {
      case 'todo_o_nada': return 'Todo o nada';
      case 'flexible': return 'Flexible';
      case 'donacion': return 'Donación';
      default: return modalidad;
    }
  }

  String formatCurrency(double amount, String moneda) {
    if (moneda == 'eur') {
      return '${amount.toStringAsFixed(0)}€';
    } else if (moneda == 'semilla') {
      return '${amount.toStringAsFixed(0)} SEMILLA';
    } else if (moneda == 'hours') {
      return '${amount.toStringAsFixed(1)} HOURS';
    }
    return amount.toStringAsFixed(2);
  }
}

class _Tier {
  final int id;
  final int proyectoId;
  final String nombre;
  final String descripcion;
  final double? importeEur;
  final double? importeSemilla;
  final double? importeHours;
  final List<String> recompensas;
  final int? cantidadLimitada;
  final int cantidadVendida;
  final bool disponible;
  final int? restantes;
  final String? fechaEntregaEstimada;
  final bool requiereEnvio;
  final String? imagen;
  final bool destacado;
  final int orden;

  _Tier({
    required this.id,
    required this.proyectoId,
    required this.nombre,
    required this.descripcion,
    this.importeEur,
    this.importeSemilla,
    this.importeHours,
    required this.recompensas,
    this.cantidadLimitada,
    required this.cantidadVendida,
    required this.disponible,
    this.restantes,
    this.fechaEntregaEstimada,
    required this.requiereEnvio,
    this.imagen,
    required this.destacado,
    required this.orden,
  });

  factory _Tier.fromJson(Map<String, dynamic> json) {
    return _Tier(
      id: json['id'] ?? 0,
      proyectoId: json['proyecto_id'] ?? 0,
      nombre: json['nombre'] ?? '',
      descripcion: json['descripcion'] ?? '',
      importeEur: json['importe_eur'] != null ? (json['importe_eur']).toDouble() : null,
      importeSemilla: json['importe_semilla'] != null ? (json['importe_semilla']).toDouble() : null,
      importeHours: json['importe_hours'] != null ? (json['importe_hours']).toDouble() : null,
      recompensas: (json['recompensas'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? [],
      cantidadLimitada: json['cantidad_limitada'],
      cantidadVendida: json['cantidad_vendida'] ?? 0,
      disponible: json['disponible'] != false,
      restantes: json['restantes'],
      fechaEntregaEstimada: json['fecha_entrega_estimada'],
      requiereEnvio: json['requiere_envio'] == true || json['requiere_envio'] == 1,
      imagen: json['imagen'],
      destacado: json['destacado'] == true || json['destacado'] == 1,
      orden: json['orden'] ?? 0,
    );
  }

  String get importeDisplay {
    if (importeEur != null && importeEur! > 0) {
      return '${importeEur!.toStringAsFixed(0)}€';
    }
    if (importeSemilla != null && importeSemilla! > 0) {
      return '${importeSemilla!.toStringAsFixed(0)} SEMILLA';
    }
    if (importeHours != null && importeHours! > 0) {
      return '${importeHours!.toStringAsFixed(1)} HOURS';
    }
    return 'Libre';
  }
}

class _Aportacion {
  final int id;
  final int proyectoId;
  final int? tierId;
  final String nombre;
  final double importe;
  final String moneda;
  final String estado;
  final String? mensajePublico;
  final bool anonimo;
  final DateTime? fechaPago;

  _Aportacion({
    required this.id,
    required this.proyectoId,
    this.tierId,
    required this.nombre,
    required this.importe,
    required this.moneda,
    required this.estado,
    this.mensajePublico,
    required this.anonimo,
    this.fechaPago,
  });

  factory _Aportacion.fromJson(Map<String, dynamic> json) {
    return _Aportacion(
      id: json['id'] ?? 0,
      proyectoId: json['proyecto_id'] ?? 0,
      tierId: json['tier_id'],
      nombre: json['nombre'] ?? 'Anónimo',
      importe: (json['importe'] ?? 0).toDouble(),
      moneda: json['moneda'] ?? 'eur',
      estado: json['estado'] ?? 'pendiente',
      mensajePublico: json['mensaje_publico'],
      anonimo: json['anonimo'] == true || json['anonimo'] == 1,
      fechaPago: json['fecha_pago'] != null ? DateTime.tryParse(json['fecha_pago']) : null,
    );
  }
}

class _Actualizacion {
  final int id;
  final int proyectoId;
  final String titulo;
  final String contenido;
  final String tipo;
  final String? imagen;
  final bool soloAportantes;
  final DateTime createdAt;

  _Actualizacion({
    required this.id,
    required this.proyectoId,
    required this.titulo,
    required this.contenido,
    required this.tipo,
    this.imagen,
    required this.soloAportantes,
    required this.createdAt,
  });

  factory _Actualizacion.fromJson(Map<String, dynamic> json) {
    return _Actualizacion(
      id: json['id'] ?? 0,
      proyectoId: json['proyecto_id'] ?? 0,
      titulo: json['titulo'] ?? '',
      contenido: json['contenido'] ?? '',
      tipo: json['tipo'] ?? 'novedad',
      imagen: json['imagen'],
      soloAportantes: json['solo_aportantes'] == true || json['solo_aportantes'] == 1,
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
    );
  }

  String get tipoEmoji {
    switch (tipo) {
      case 'novedad': return '📢';
      case 'hito': return '🎯';
      case 'agradecimiento': return '🙏';
      case 'problema': return '⚠️';
      case 'entrega': return '📦';
      default: return '📝';
    }
  }
}

// ============================================================
// WIDGETS
// ============================================================

class _ProyectoCard extends StatelessWidget {
  final _Proyecto proyecto;
  final VoidCallback onTap;

  const _ProyectoCard({
    required this.proyecto,
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
            // Imagen
            AspectRatio(
              aspectRatio: 16 / 9,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (proyecto.imagenPrincipal != null)
                    Image.network(
                      proyecto.imagenPrincipal!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => _buildPlaceholder(),
                    )
                  else
                    _buildPlaceholder(),
                  // Badges superiores
                  Positioned(
                    top: 8,
                    left: 8,
                    child: Row(
                      children: [
                        _TipoBadge(tipo: proyecto.tipo),
                        const SizedBox(width: 4),
                        _EstadoBadge(estado: proyecto.estado),
                      ],
                    ),
                  ),
                  // Destacado
                  if (proyecto.destacado)
                    Positioned(
                      top: 8,
                      right: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.amber,
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.star, size: 14, color: Colors.white),
                            SizedBox(width: 4),
                            Text(
                              'Destacado',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                          ],
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
                  // Título
                  Text(
                    proyecto.titulo,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),

                  const SizedBox(height: 4),

                  // Creador
                  if (proyecto.creadorNombre != null || proyecto.colectivoNombre != null)
                    Text(
                      proyecto.colectivoNombre ?? proyecto.creadorNombre ?? '',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: colorScheme.onSurfaceVariant,
                          ),
                    ),

                  const SizedBox(height: 12),

                  // Barra de progreso
                  _ProgresoWidget(proyecto: proyecto),

                  const SizedBox(height: 12),

                  // Stats
                  Row(
                    children: [
                      _StatChip(
                        icon: Icons.people_outline,
                        value: '${proyecto.aportantesCount}',
                        label: 'mecenas',
                      ),
                      const SizedBox(width: 16),
                      if (proyecto.diasRestantes != null && !proyecto.finalizado) ...[
                        _StatChip(
                          icon: Icons.schedule,
                          value: '${proyecto.diasRestantes}',
                          label: 'días',
                        ),
                      ] else if (proyecto.finalizado) ...[
                        _StatChip(
                          icon: Icons.check_circle_outline,
                          value: proyecto.estadoLabel,
                          label: '',
                          color: proyecto.estadoColor,
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

  Widget _buildPlaceholder() {
    return Container(
      color: proyecto.tipoColor.withAlpha(51),
      child: Center(
        child: Text(
          proyecto.tipoEmoji,
          style: const TextStyle(fontSize: 48),
        ),
      ),
    );
  }
}

class _ProgresoWidget extends StatelessWidget {
  final _Proyecto proyecto;

  const _ProgresoWidget({required this.proyecto});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final porcentaje = proyecto.porcentajeRecaudado.clamp(0, 100);
    final superaObjetivo = proyecto.porcentajeRecaudado > 100;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Barra
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(
            value: porcentaje / 100,
            minHeight: 8,
            backgroundColor: colorScheme.surfaceContainerHighest,
            valueColor: AlwaysStoppedAnimation(
              superaObjetivo ? Colors.green : colorScheme.primary,
            ),
          ),
        ),

        const SizedBox(height: 8),

        // Cantidades
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  proyecto.formatCurrency(proyecto.recaudadoTotalEur, 'eur'),
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: superaObjetivo ? Colors.green : colorScheme.primary,
                      ),
                ),
                Text(
                  'de ${proyecto.formatCurrency(proyecto.objetivoTotalEur, 'eur')}',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                ),
              ],
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: (superaObjetivo ? Colors.green : colorScheme.primary).withAlpha(26),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                '${proyecto.porcentajeRecaudado.toStringAsFixed(0)}%',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: superaObjetivo ? Colors.green : colorScheme.primary,
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _TipoBadge extends StatelessWidget {
  final String tipo;

  const _TipoBadge({required this.tipo});

  String get _emoji {
    switch (tipo) {
      case 'album': return '🎵';
      case 'tour': return '🎤';
      case 'produccion': return '🎬';
      case 'equipamiento': return '🔧';
      case 'espacio': return '🏠';
      case 'evento': return '🎉';
      case 'social': return '🤝';
      case 'emergencia': return '🆘';
      default: return '📦';
    }
  }

  String get _label {
    switch (tipo) {
      case 'album': return 'Álbum';
      case 'tour': return 'Gira';
      case 'produccion': return 'Producción';
      case 'equipamiento': return 'Equipamiento';
      case 'espacio': return 'Espacio';
      case 'evento': return 'Evento';
      case 'social': return 'Social';
      case 'emergencia': return 'Emergencia';
      default: return 'Otro';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.black54,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        '$_emoji $_label',
        style: const TextStyle(
          fontSize: 11,
          color: Colors.white,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }
}

class _EstadoBadge extends StatelessWidget {
  final String estado;

  const _EstadoBadge({required this.estado});

  Color get _color {
    switch (estado) {
      case 'borrador': return Colors.grey;
      case 'revision': return Colors.orange;
      case 'activo': return Colors.blue;
      case 'pausado': return Colors.amber;
      case 'exitoso': return Colors.green;
      case 'fallido': return Colors.red;
      case 'cancelado': return Colors.grey;
      default: return Colors.grey;
    }
  }

  String get _label {
    switch (estado) {
      case 'borrador': return 'Borrador';
      case 'revision': return 'En revisión';
      case 'activo': return 'Activo';
      case 'pausado': return 'Pausado';
      case 'exitoso': return 'Financiado';
      case 'fallido': return 'No financiado';
      case 'cancelado': return 'Cancelado';
      default: return estado;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: _color,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        _label,
        style: const TextStyle(
          fontSize: 11,
          color: Colors.white,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;
  final Color? color;

  const _StatChip({
    required this.icon,
    required this.value,
    required this.label,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveColor = color ?? Theme.of(context).colorScheme.onSurfaceVariant;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: effectiveColor),
        const SizedBox(width: 4),
        Text(
          value,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            color: effectiveColor,
          ),
        ),
        if (label.isNotEmpty) ...[
          const SizedBox(width: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: effectiveColor,
            ),
          ),
        ],
      ],
    );
  }
}

class _ModalidadBadge extends StatelessWidget {
  final String modalidad;

  const _ModalidadBadge({required this.modalidad});

  String get _label {
    switch (modalidad) {
      case 'todo_o_nada': return '🎯 Todo o nada';
      case 'flexible': return '🔄 Flexible';
      case 'donacion': return '💝 Donación';
      default: return modalidad;
    }
  }

  String get _descripcion {
    switch (modalidad) {
      case 'todo_o_nada': return 'Solo se financia si alcanza el objetivo';
      case 'flexible': return 'Se entrega lo recaudado aunque no alcance el objetivo';
      case 'donacion': return 'Sin objetivo mínimo';
      default: return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: _descripcion,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surfaceContainerHighest,
          borderRadius: BorderRadius.circular(4),
        ),
        child: Text(
          _label,
          style: TextStyle(
            fontSize: 12,
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }
}

// ============================================================
// PANTALLA DE DETALLE
// ============================================================

class _ProyectoDetalleScreen extends ConsumerStatefulWidget {
  final _Proyecto proyecto;

  const _ProyectoDetalleScreen({required this.proyecto});

  @override
  ConsumerState<_ProyectoDetalleScreen> createState() =>
      _ProyectoDetalleScreenState();
}

class _ProyectoDetalleScreenState extends ConsumerState<_ProyectoDetalleScreen>
    with SingleTickerProviderStateMixin {
  late _Proyecto _proyecto;
  late TabController _tabController;
  List<_Aportacion> _aportaciones = [];
  List<_Actualizacion> _actualizaciones = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _proyecto = widget.proyecto;
    _tabController = TabController(length: 3, vsync: this);
    _cargarDetalle();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarDetalle() async {
    setState(() => _isLoading = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/flavor/v1/crowdfunding/proyectos/${_proyecto.id}');

      if (response.success && response.data != null) {
        final data = response.data!;
        if (data['proyecto'] != null) {
          setState(() {
            _proyecto = _Proyecto.fromJson(data['proyecto']);
          });
        }
        if (data['aportaciones'] != null) {
          setState(() {
            _aportaciones = (data['aportaciones'] as List)
                .map((json) => _Aportacion.fromJson(json))
                .toList();
          });
        }
        if (data['actualizaciones'] != null) {
          setState(() {
            _actualizaciones = (data['actualizaciones'] as List)
                .map((json) => _Actualizacion.fromJson(json))
                .toList();
          });
        }
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
          // Indicador de carga
          if (_isLoading)
            const SliverToBoxAdapter(
              child: LinearProgressIndicator(),
            ),

          // App bar con imagen
          SliverAppBar(
            expandedHeight: 200,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: _proyecto.imagenPrincipal != null
                  ? Image.network(
                      _proyecto.imagenPrincipal!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => _buildHeaderPlaceholder(),
                    )
                  : _buildHeaderPlaceholder(),
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.share),
                onPressed: _compartir,
              ),
            ],
          ),

          // Contenido principal
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Badges
                  Row(
                    children: [
                      _TipoBadge(tipo: _proyecto.tipo),
                      const SizedBox(width: 8),
                      _EstadoBadge(estado: _proyecto.estado),
                      const SizedBox(width: 8),
                      _ModalidadBadge(modalidad: _proyecto.modalidad),
                      if (_proyecto.verificado) ...[
                        const SizedBox(width: 8),
                        Tooltip(
                          message: 'Proyecto verificado',
                          child: Icon(
                            Icons.verified,
                            size: 20,
                            color: Colors.blue.shade600,
                          ),
                        ),
                      ],
                    ],
                  ),

                  const SizedBox(height: 16),

                  // Título
                  Text(
                    _proyecto.titulo,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),

                  // Creador
                  if (_proyecto.creadorNombre != null || _proyecto.colectivoNombre != null) ...[
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 16,
                          backgroundImage: _proyecto.creadorAvatar != null
                              ? NetworkImage(_proyecto.creadorAvatar!)
                              : null,
                          child: _proyecto.creadorAvatar == null
                              ? const Icon(Icons.person, size: 16)
                              : null,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _proyecto.colectivoNombre ?? _proyecto.creadorNombre ?? '',
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      ],
                    ),
                  ],

                  const SizedBox(height: 24),

                  // Progreso
                  _ProgresoWidget(proyecto: _proyecto),

                  const SizedBox(height: 16),

                  // Stats
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _StatColumn(
                        value: '${_proyecto.aportantesCount}',
                        label: 'Mecenas',
                        icon: Icons.people_outline,
                      ),
                      _StatColumn(
                        value: _proyecto.diasRestantes?.toString() ?? '-',
                        label: 'Días restantes',
                        icon: Icons.schedule,
                      ),
                      _StatColumn(
                        value: '${_proyecto.visualizaciones}',
                        label: 'Visitas',
                        icon: Icons.visibility_outlined,
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // Botón de aportar
                  if (_proyecto.estado == 'activo')
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: _mostrarAportarSheet,
                        icon: const Icon(Icons.favorite),
                        label: const Text('Apoyar este proyecto'),
                      ),
                    ),

                  // Monedas aceptadas
                  if (_proyecto.estado == 'activo') ...[
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'Acepta: ',
                          style: TextStyle(
                            fontSize: 12,
                            color: colorScheme.onSurfaceVariant,
                          ),
                        ),
                        if (_proyecto.aceptaEur)
                          const _MonedaChip(moneda: 'EUR', activa: true),
                        if (_proyecto.aceptaSemilla)
                          const _MonedaChip(moneda: 'SEMILLA', activa: true),
                        if (_proyecto.aceptaHours)
                          const _MonedaChip(moneda: 'HOURS', activa: true),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),

          // Tabs
          SliverPersistentHeader(
            pinned: true,
            delegate: _TabBarDelegate(
              TabBar(
                controller: _tabController,
                tabs: [
                  Tab(text: 'Info (${_proyecto.tiers.length})'),
                  Tab(text: 'Mecenas (${_aportaciones.length})'),
                  Tab(text: 'Actualizaciones (${_actualizaciones.length})'),
                ],
              ),
            ),
          ),

          // Contenido de tabs
          SliverFillRemaining(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildInfoTab(),
                _buildMecenasTab(),
                _buildActualizacionesTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeaderPlaceholder() {
    return Container(
      color: _proyecto.tipoColor.withAlpha(51),
      child: Center(
        child: Text(
          _proyecto.tipoEmoji,
          style: const TextStyle(fontSize: 72),
        ),
      ),
    );
  }

  Widget _buildInfoTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Descripción
          Text(
            'Descripción',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
          const SizedBox(height: 8),
          Text(_proyecto.descripcion),

          const SizedBox(height: 24),

          // Recompensas/Tiers
          if (_proyecto.tiers.isNotEmpty) ...[
            Text(
              'Recompensas',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            ..._proyecto.tiers.map((tier) => _TierCard(
                  tier: tier,
                  onSelect: _proyecto.estado == 'activo'
                      ? () => _mostrarAportarSheet(tier: tier)
                      : null,
                )),
          ],
        ],
      ),
    );
  }

  Widget _buildMecenasTab() {
    if (_aportaciones.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.people_outline, size: 64, color: Colors.grey),
            SizedBox(height: 16),
            Text('Aún no hay mecenas'),
            Text(
              '¡Sé el primero en apoyar!',
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _aportaciones.length,
      itemBuilder: (context, index) {
        final aportacion = _aportaciones[index];
        return _AportacionTile(aportacion: aportacion);
      },
    );
  }

  Widget _buildActualizacionesTab() {
    if (_actualizaciones.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.article_outlined, size: 64, color: Colors.grey),
            SizedBox(height: 16),
            Text('Sin actualizaciones'),
            Text(
              'El creador aún no ha publicado novedades',
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _actualizaciones.length,
      itemBuilder: (context, index) {
        final actualizacion = _actualizaciones[index];
        return _ActualizacionCard(actualizacion: actualizacion);
      },
    );
  }

  void _compartir() {
    Haptics.light();
    FlavorSnackbar.showInfo(context, 'Compartir: ${_proyecto.titulo}');
  }

  void _mostrarAportarSheet({_Tier? tier}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) => _AportarSheet(
        proyecto: _proyecto,
        tierSeleccionado: tier,
        onAportado: () {
          _cargarDetalle();
        },
      ),
    );
  }
}

class _StatColumn extends StatelessWidget {
  final String value;
  final String label;
  final IconData icon;

  const _StatColumn({
    required this.value,
    required this.label,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, size: 24, color: Theme.of(context).colorScheme.primary),
        const SizedBox(height: 4),
        Text(
          value,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
        Text(
          label,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
        ),
      ],
    );
  }
}

class _MonedaChip extends StatelessWidget {
  final String moneda;
  final bool activa;

  const _MonedaChip({required this.moneda, required this.activa});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(left: 4),
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: activa ? Colors.green.withAlpha(26) : Colors.grey.withAlpha(26),
        borderRadius: BorderRadius.circular(4),
        border: Border.all(
          color: activa ? Colors.green : Colors.grey,
          width: 0.5,
        ),
      ),
      child: Text(
        moneda,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          color: activa ? Colors.green : Colors.grey,
        ),
      ),
    );
  }
}

class _TabBarDelegate extends SliverPersistentHeaderDelegate {
  final TabBar tabBar;

  _TabBarDelegate(this.tabBar);

  @override
  double get minExtent => tabBar.preferredSize.height;

  @override
  double get maxExtent => tabBar.preferredSize.height;

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return Material(
      color: Theme.of(context).scaffoldBackgroundColor,
      child: tabBar,
    );
  }

  @override
  bool shouldRebuild(_TabBarDelegate oldDelegate) => false;
}

class _TierCard extends StatelessWidget {
  final _Tier tier;
  final VoidCallback? onSelect;

  const _TierCard({required this.tier, this.onSelect});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final disponible = tier.disponible && onSelect != null;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: disponible ? onSelect : null,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      tier.nombre,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: colorScheme.primary,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      tier.importeDisplay,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 8),

              Text(
                tier.descripcion,
                style: TextStyle(color: colorScheme.onSurfaceVariant),
              ),

              if (tier.recompensas.isNotEmpty) ...[
                const SizedBox(height: 12),
                Wrap(
                  spacing: 4,
                  runSpacing: 4,
                  children: tier.recompensas.map((recompensa) {
                    return Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: colorScheme.surfaceContainerHighest,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        '✓ $recompensa',
                        style: const TextStyle(fontSize: 12),
                      ),
                    );
                  }).toList(),
                ),
              ],

              if (tier.cantidadLimitada != null) ...[
                const SizedBox(height: 8),
                Text(
                  tier.disponible
                      ? 'Quedan ${tier.restantes} de ${tier.cantidadLimitada}'
                      : '¡Agotado!',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: tier.disponible ? Colors.orange : Colors.red,
                  ),
                ),
              ],

              if (onSelect != null && disponible) ...[
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    onPressed: onSelect,
                    child: const Text('Seleccionar'),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _AportacionTile extends StatelessWidget {
  final _Aportacion aportacion;

  const _AportacionTile({required this.aportacion});

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(
        child: Text(
          aportacion.anonimo ? '?' : aportacion.nombre.substring(0, 1).toUpperCase(),
        ),
      ),
      title: Text(aportacion.anonimo ? 'Anónimo' : aportacion.nombre),
      subtitle: aportacion.mensajePublico != null
          ? Text(
              aportacion.mensajePublico!,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            )
          : null,
      trailing: Text(
        '${aportacion.importe.toStringAsFixed(0)}${aportacion.moneda == 'eur' ? '€' : ' ${aportacion.moneda.toUpperCase()}'}',
        style: const TextStyle(fontWeight: FontWeight.bold),
      ),
    );
  }
}

class _ActualizacionCard extends StatelessWidget {
  final _Actualizacion actualizacion;

  const _ActualizacionCard({required this.actualizacion});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(
                  actualizacion.tipoEmoji,
                  style: const TextStyle(fontSize: 20),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    actualizacion.titulo,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(actualizacion.contenido),
            const SizedBox(height: 8),
            Text(
              _formatFecha(actualizacion.createdAt),
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatFecha(DateTime fecha) {
    final ahora = DateTime.now();
    final diferencia = ahora.difference(fecha);

    if (diferencia.inDays == 0) {
      return 'Hoy';
    } else if (diferencia.inDays == 1) {
      return 'Ayer';
    } else if (diferencia.inDays < 7) {
      return 'Hace ${diferencia.inDays} días';
    } else {
      return '${fecha.day}/${fecha.month}/${fecha.year}';
    }
  }
}

// ============================================================
// SHEET DE APORTAR
// ============================================================

class _AportarSheet extends ConsumerStatefulWidget {
  final _Proyecto proyecto;
  final _Tier? tierSeleccionado;
  final VoidCallback onAportado;

  const _AportarSheet({
    required this.proyecto,
    this.tierSeleccionado,
    required this.onAportado,
  });

  @override
  ConsumerState<_AportarSheet> createState() => _AportarSheetState();
}

class _AportarSheetState extends ConsumerState<_AportarSheet> {
  final _formKey = GlobalKey<FormState>();
  final _importeController = TextEditingController();
  final _nombreController = TextEditingController();
  final _emailController = TextEditingController();
  final _mensajeController = TextEditingController();
  String _moneda = 'eur';
  bool _anonimo = false;
  bool _isLoading = false;
  _Tier? _tierSeleccionado;

  @override
  void initState() {
    super.initState();
    _tierSeleccionado = widget.tierSeleccionado;
    if (_tierSeleccionado != null) {
      if (_tierSeleccionado!.importeEur != null) {
        _importeController.text = _tierSeleccionado!.importeEur!.toStringAsFixed(0);
        _moneda = 'eur';
      } else if (_tierSeleccionado!.importeSemilla != null) {
        _importeController.text = _tierSeleccionado!.importeSemilla!.toStringAsFixed(0);
        _moneda = 'semilla';
      } else if (_tierSeleccionado!.importeHours != null) {
        _importeController.text = _tierSeleccionado!.importeHours!.toStringAsFixed(1);
        _moneda = 'hours';
      }
    }
  }

  @override
  void dispose() {
    _importeController.dispose();
    _nombreController.dispose();
    _emailController.dispose();
    _mensajeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.9,
      minChildSize: 0.5,
      maxChildSize: 0.95,
      expand: false,
      builder: (context, scrollController) {
        return Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
          ),
          child: Column(
            children: [
              // Handle
              Container(
                margin: const EdgeInsets.symmetric(vertical: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),

              // Título
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  children: [
                    Text(
                      '💝 Apoyar proyecto',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
              ),

              const Divider(),

              // Contenido
              Expanded(
                child: SingleChildScrollView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(16),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Tier seleccionado
                        if (_tierSeleccionado != null) ...[
                          Card(
                            color: Theme.of(context).colorScheme.primaryContainer,
                            child: ListTile(
                              leading: const Icon(Icons.card_giftcard),
                              title: Text(_tierSeleccionado!.nombre),
                              subtitle: Text(_tierSeleccionado!.importeDisplay),
                              trailing: IconButton(
                                icon: const Icon(Icons.close),
                                onPressed: () {
                                  setState(() {
                                    _tierSeleccionado = null;
                                    _importeController.clear();
                                  });
                                },
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                        ],

                        // Moneda
                        Text(
                          'Moneda',
                          style: Theme.of(context).textTheme.titleSmall,
                        ),
                        const SizedBox(height: 8),
                        SegmentedButton<String>(
                          segments: [
                            if (widget.proyecto.aceptaEur)
                              const ButtonSegment(value: 'eur', label: Text('EUR €')),
                            if (widget.proyecto.aceptaSemilla)
                              const ButtonSegment(value: 'semilla', label: Text('SEMILLA')),
                            if (widget.proyecto.aceptaHours)
                              const ButtonSegment(value: 'hours', label: Text('HOURS')),
                          ],
                          selected: {_moneda},
                          onSelectionChanged: (value) {
                            setState(() => _moneda = value.first);
                          },
                        ),

                        const SizedBox(height: 16),

                        // Importe
                        TextFormField(
                          controller: _importeController,
                          decoration: InputDecoration(
                            labelText: 'Importe',
                            hintText: 'Mínimo: ${widget.proyecto.minimoAportacion}',
                            prefixIcon: const Icon(Icons.attach_money),
                            suffixText: _moneda == 'eur' ? '€' : _moneda.toUpperCase(),
                          ),
                          keyboardType: TextInputType.number,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Introduce un importe';
                            }
                            final importe = double.tryParse(value);
                            if (importe == null || importe < widget.proyecto.minimoAportacion) {
                              return 'Mínimo: ${widget.proyecto.minimoAportacion}';
                            }
                            return null;
                          },
                        ),

                        const SizedBox(height: 16),

                        // Nombre
                        TextFormField(
                          controller: _nombreController,
                          decoration: const InputDecoration(
                            labelText: 'Tu nombre',
                            prefixIcon: Icon(Icons.person_outline),
                          ),
                          validator: (value) {
                            if (!_anonimo && (value == null || value.isEmpty)) {
                              return 'Introduce tu nombre';
                            }
                            return null;
                          },
                        ),

                        const SizedBox(height: 16),

                        // Email
                        TextFormField(
                          controller: _emailController,
                          decoration: const InputDecoration(
                            labelText: 'Email',
                            prefixIcon: Icon(Icons.email_outlined),
                          ),
                          keyboardType: TextInputType.emailAddress,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Introduce tu email';
                            }
                            if (!value.contains('@')) {
                              return 'Email no válido';
                            }
                            return null;
                          },
                        ),

                        const SizedBox(height: 16),

                        // Mensaje
                        TextFormField(
                          controller: _mensajeController,
                          decoration: const InputDecoration(
                            labelText: 'Mensaje (opcional)',
                            hintText: 'Deja un mensaje de apoyo...',
                            prefixIcon: Icon(Icons.message_outlined),
                          ),
                          maxLines: 3,
                        ),

                        const SizedBox(height: 16),

                        // Anónimo
                        SwitchListTile(
                          title: const Text('Aportación anónima'),
                          subtitle: const Text('Tu nombre no será visible públicamente'),
                          value: _anonimo,
                          onChanged: (value) {
                            setState(() => _anonimo = value);
                          },
                        ),

                        const SizedBox(height: 24),

                        // Botón aportar
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: _isLoading ? null : _realizarAportacion,
                            icon: _isLoading
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Icon(Icons.favorite),
                            label: Text(_isLoading ? 'Procesando...' : 'Realizar aportación'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _realizarAportacion() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/flavor/v1/crowdfunding/proyectos/${widget.proyecto.id}/aportacion',
        data: {
          'importe': double.parse(_importeController.text),
          'moneda': _moneda,
          'nombre': _nombreController.text,
          'email': _emailController.text,
          'mensaje_publico': _mensajeController.text,
          'anonimo': _anonimo,
          if (_tierSeleccionado != null) 'tier_id': _tierSeleccionado!.id,
        },
      );

      if (!mounted) return;

      if (response.success) {
        Haptics.success();
        Navigator.pop(context);
        FlavorSnackbar.showSuccess(context, '¡Gracias por tu apoyo! 💝');
        widget.onAportado();
      } else {
        Haptics.error();
        FlavorSnackbar.showError(context, response.error ?? 'Error al procesar la aportación');
      }
    } catch (e) {
      if (!mounted) return;
      Haptics.error();
      FlavorSnackbar.showError(context, 'Error: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }
}

// ============================================================
// PANTALLA CREAR PROYECTO
// ============================================================

class _CrearProyectoScreen extends ConsumerStatefulWidget {
  const _CrearProyectoScreen();

  @override
  ConsumerState<_CrearProyectoScreen> createState() => _CrearProyectoScreenState();
}

class _CrearProyectoScreenState extends ConsumerState<_CrearProyectoScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _objetivoController = TextEditingController();
  String _tipo = 'otro';
  String _modalidad = 'flexible';
  int _diasDuracion = 30;
  bool _aceptaEur = true;
  bool _aceptaSemilla = true;
  bool _aceptaHours = false;
  bool _isLoading = false;

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    _objetivoController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Nuevo proyecto'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Tipo
            Text(
              'Tipo de proyecto',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _TipoChip(tipo: 'album', selected: _tipo == 'album', onTap: () => setState(() => _tipo = 'album')),
                _TipoChip(tipo: 'tour', selected: _tipo == 'tour', onTap: () => setState(() => _tipo = 'tour')),
                _TipoChip(tipo: 'produccion', selected: _tipo == 'produccion', onTap: () => setState(() => _tipo = 'produccion')),
                _TipoChip(tipo: 'evento', selected: _tipo == 'evento', onTap: () => setState(() => _tipo = 'evento')),
                _TipoChip(tipo: 'social', selected: _tipo == 'social', onTap: () => setState(() => _tipo = 'social')),
                _TipoChip(tipo: 'emergencia', selected: _tipo == 'emergencia', onTap: () => setState(() => _tipo = 'emergencia')),
                _TipoChip(tipo: 'otro', selected: _tipo == 'otro', onTap: () => setState(() => _tipo = 'otro')),
              ],
            ),

            const SizedBox(height: 24),

            // Título
            TextFormField(
              controller: _tituloController,
              decoration: const InputDecoration(
                labelText: 'Título del proyecto',
                hintText: 'Ej: Grabación de mi primer álbum',
                prefixIcon: Icon(Icons.title),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'El título es obligatorio';
                }
                return null;
              },
            ),

            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción',
                hintText: 'Cuéntanos sobre tu proyecto...',
                prefixIcon: Icon(Icons.description),
                alignLabelWithHint: true,
              ),
              maxLines: 5,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'La descripción es obligatoria';
                }
                return null;
              },
            ),

            const SizedBox(height: 24),

            // Modalidad
            Text(
              'Modalidad de financiación',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(
                  value: 'flexible',
                  label: Text('Flexible'),
                  icon: Icon(Icons.swap_horiz),
                ),
                ButtonSegment(
                  value: 'todo_o_nada',
                  label: Text('Todo o nada'),
                  icon: Icon(Icons.gpp_maybe),
                ),
                ButtonSegment(
                  value: 'donacion',
                  label: Text('Donación'),
                  icon: Icon(Icons.favorite),
                ),
              ],
              selected: {_modalidad},
              onSelectionChanged: (value) {
                setState(() => _modalidad = value.first);
              },
            ),
            const SizedBox(height: 4),
            Text(
              _getModalidadDescripcion(_modalidad),
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
            ),

            const SizedBox(height: 24),

            // Objetivo
            TextFormField(
              controller: _objetivoController,
              decoration: const InputDecoration(
                labelText: 'Objetivo (€)',
                hintText: 'Ej: 5000',
                prefixIcon: Icon(Icons.euro),
              ),
              keyboardType: TextInputType.number,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'El objetivo es obligatorio';
                }
                final objetivo = double.tryParse(value);
                if (objetivo == null || objetivo < 100) {
                  return 'Mínimo 100€';
                }
                if (objetivo > 100000) {
                  return 'Máximo 100.000€';
                }
                return null;
              },
            ),

            const SizedBox(height: 24),

            // Duración
            Text(
              'Duración: $_diasDuracion días',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            Slider(
              value: _diasDuracion.toDouble(),
              min: 7,
              max: 90,
              divisions: 83,
              label: '$_diasDuracion días',
              onChanged: (value) {
                setState(() => _diasDuracion = value.round());
              },
            ),

            const SizedBox(height: 24),

            // Monedas
            Text(
              'Monedas aceptadas',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            CheckboxListTile(
              title: const Text('Euros (€)'),
              value: _aceptaEur,
              onChanged: (value) => setState(() => _aceptaEur = value ?? true),
            ),
            CheckboxListTile(
              title: const Text('SEMILLA (moneda social)'),
              value: _aceptaSemilla,
              onChanged: (value) => setState(() => _aceptaSemilla = value ?? false),
            ),
            CheckboxListTile(
              title: const Text('HOURS (banco de tiempo)'),
              value: _aceptaHours,
              onChanged: (value) => setState(() => _aceptaHours = value ?? false),
            ),

            const SizedBox(height: 32),

            // Botón crear
            FilledButton.icon(
              onPressed: _isLoading ? null : _crearProyecto,
              icon: _isLoading
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.rocket_launch),
              label: Text(_isLoading ? 'Creando...' : 'Crear proyecto'),
            ),
          ],
        ),
      ),
    );
  }

  String _getModalidadDescripcion(String modalidad) {
    switch (modalidad) {
      case 'flexible':
        return 'Recibes lo recaudado aunque no alcances el objetivo';
      case 'todo_o_nada':
        return 'Solo recibes el dinero si alcanzas el objetivo';
      case 'donacion':
        return 'Sin objetivo mínimo, siempre exitoso';
      default:
        return '';
    }
  }

  Future<void> _crearProyecto() async {
    if (!_formKey.currentState!.validate()) return;

    if (!_aceptaEur && !_aceptaSemilla && !_aceptaHours) {
      FlavorSnackbar.showInfo(context, 'Debes aceptar al menos una moneda');
      return;
    }

    setState(() => _isLoading = true);

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/flavor/v1/crowdfunding/proyectos',
        data: {
          'titulo': _tituloController.text,
          'descripcion': _descripcionController.text,
          'tipo': _tipo,
          'modalidad': _modalidad,
          'objetivo_eur': double.parse(_objetivoController.text),
          'dias_duracion': _diasDuracion,
          'acepta_eur': _aceptaEur,
          'acepta_semilla': _aceptaSemilla,
          'acepta_hours': _aceptaHours,
        },
      );

      if (!mounted) return;

      if (response.success) {
        Haptics.success();
        FlavorSnackbar.showSuccess(context, '¡Proyecto creado con éxito!');
        Navigator.pop(context, true);
      } else {
        Haptics.error();
        FlavorSnackbar.showError(context, response.error ?? 'Error al crear el proyecto');
      }
    } catch (e) {
      if (!mounted) return;
      Haptics.error();
      FlavorSnackbar.showError(context, 'Error: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }
}

class _TipoChip extends StatelessWidget {
  final String tipo;
  final bool selected;
  final VoidCallback onTap;

  const _TipoChip({
    required this.tipo,
    required this.selected,
    required this.onTap,
  });

  String get _emoji {
    switch (tipo) {
      case 'album': return '🎵';
      case 'tour': return '🎤';
      case 'produccion': return '🎬';
      case 'equipamiento': return '🔧';
      case 'espacio': return '🏠';
      case 'evento': return '🎉';
      case 'social': return '🤝';
      case 'emergencia': return '🆘';
      default: return '📦';
    }
  }

  String get _label {
    switch (tipo) {
      case 'album': return 'Álbum';
      case 'tour': return 'Gira';
      case 'produccion': return 'Producción';
      case 'equipamiento': return 'Equipamiento';
      case 'espacio': return 'Espacio';
      case 'evento': return 'Evento';
      case 'social': return 'Social';
      case 'emergencia': return 'Emergencia';
      default: return 'Otro';
    }
  }

  @override
  Widget build(BuildContext context) {
    return FilterChip(
      label: Text('$_emoji $_label'),
      selected: selected,
      onSelected: (_) => onTap(),
    );
  }
}
