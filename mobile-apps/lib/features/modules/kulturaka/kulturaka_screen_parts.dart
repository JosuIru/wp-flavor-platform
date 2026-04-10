part of 'kulturaka_screen.dart';

// ============================================================
// MODELOS DE DATOS
// ============================================================

class _Espacio {
  final int id;
  final String nombre;
  final String? tipo;
  final String? tipoLabel;
  final String? direccion;
  final String? imagen;
  final int totalEventos;
  final double rating;
  final double? latitud;
  final double? longitud;

  _Espacio({
    required this.id,
    required this.nombre,
    this.tipo,
    this.tipoLabel,
    this.direccion,
    this.imagen,
    this.totalEventos = 0,
    this.rating = 0,
    this.latitud,
    this.longitud,
  });

  factory _Espacio.fromJson(Map<String, dynamic> json) {
    return _Espacio(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      nombre: json['nombre']?.toString() ?? '',
      tipo: json['tipo']?.toString(),
      tipoLabel: json['tipo_label']?.toString(),
      direccion: json['direccion']?.toString(),
      imagen: json['imagen']?.toString(),
      totalEventos: int.tryParse(json['total_eventos']?.toString() ?? '0') ?? 0,
      rating: double.tryParse(json['rating']?.toString() ?? '0') ?? 0,
      latitud: double.tryParse(json['latitud']?.toString() ?? ''),
      longitud: double.tryParse(json['longitud']?.toString() ?? ''),
    );
  }
}

class _Artista {
  final int id;
  final int usuarioId;
  final String nombreArtistico;
  final String? displayName;
  final String? biografia;
  final String? imagen;
  final String? slug;
  final String? categorias;
  final int nivel;
  final double rating;
  final int totalEventos;

  _Artista({
    required this.id,
    required this.usuarioId,
    required this.nombreArtistico,
    this.displayName,
    this.biografia,
    this.imagen,
    this.slug,
    this.categorias,
    this.nivel = 1,
    this.rating = 0,
    this.totalEventos = 0,
  });

  factory _Artista.fromJson(Map<String, dynamic> json) {
    return _Artista(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      usuarioId: int.tryParse(json['usuario_id']?.toString() ?? '0') ?? 0,
      nombreArtistico: json['nombre_artistico']?.toString() ?? json['nombre']?.toString() ?? '',
      displayName: json['display_name']?.toString(),
      biografia: json['biografia']?.toString(),
      imagen: json['imagen']?.toString() ?? json['avatar']?.toString(),
      slug: json['slug']?.toString(),
      categorias: json['categorias']?.toString(),
      nivel: int.tryParse(json['nivel_artista']?.toString() ?? '1') ?? 1,
      rating: double.tryParse(json['rating']?.toString() ?? '0') ?? 0,
      totalEventos: int.tryParse(json['total_eventos']?.toString() ?? '0') ?? 0,
    );
  }
}

class _Evento {
  final int id;
  final String titulo;
  final String? descripcion;
  final String? tipo;
  final DateTime? fechaInicio;
  final String? espacioNombre;
  final String? artistaNombre;
  final String? imagen;
  final int inscritos;

  _Evento({
    required this.id,
    required this.titulo,
    this.descripcion,
    this.tipo,
    this.fechaInicio,
    this.espacioNombre,
    this.artistaNombre,
    this.imagen,
    this.inscritos = 0,
  });

  factory _Evento.fromJson(Map<String, dynamic> json) {
    DateTime? fecha;
    if (json['fecha_inicio'] != null) {
      fecha = DateTime.tryParse(json['fecha_inicio'].toString());
    }

    return _Evento(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      titulo: json['titulo']?.toString() ?? json['nombre']?.toString() ?? '',
      descripcion: json['descripcion']?.toString(),
      tipo: json['tipo']?.toString(),
      fechaInicio: fecha,
      espacioNombre: json['espacio_nombre']?.toString(),
      artistaNombre: json['nombre_artistico']?.toString(),
      imagen: json['imagen']?.toString(),
      inscritos: int.tryParse(json['inscritos_count']?.toString() ?? '0') ?? 0,
    );
  }
}

// ============================================================
// WIDGETS DE TARJETAS
// ============================================================

class _EspacioCard extends StatelessWidget {
  final _Espacio espacio;
  final VoidCallback onTap;

  const _EspacioCard({
    required this.espacio,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen
            Container(
              height: 120,
              width: double.infinity,
              decoration: BoxDecoration(
                color: Colors.pink.shade50,
                image: espacio.imagen != null
                    ? DecorationImage(
                        image: NetworkImage(espacio.imagen!),
                        fit: BoxFit.cover,
                      )
                    : null,
              ),
              child: espacio.imagen == null
                  ? Center(
                      child: Icon(
                        Icons.location_city,
                        size: 48,
                        color: Colors.pink.shade200,
                      ),
                    )
                  : null,
            ),

            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Nombre y tipo
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          espacio.nombre,
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (espacio.tipoLabel != null)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.pink.shade100,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            espacio.tipoLabel!,
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.pink.shade800,
                            ),
                          ),
                        ),
                    ],
                  ),

                  if (espacio.direccion != null) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(Icons.location_on,
                            size: 14, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            espacio.direccion!,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: Colors.grey.shade600,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],

                  const SizedBox(height: 8),

                  // Stats
                  Row(
                    children: [
                      Icon(Icons.event, size: 14, color: Colors.grey.shade600),
                      const SizedBox(width: 4),
                      Text(
                        '${espacio.totalEventos} eventos',
                        style: theme.textTheme.bodySmall,
                      ),
                      if (espacio.rating > 0) ...[
                        const SizedBox(width: 16),
                        Icon(Icons.star, size: 14, color: Colors.amber),
                        const SizedBox(width: 4),
                        Text(
                          espacio.rating.toStringAsFixed(1),
                          style: theme.textTheme.bodySmall,
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

class _ArtistaCard extends StatelessWidget {
  final _Artista artista;
  final VoidCallback onTap;

  const _ArtistaCard({
    required this.artista,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        onTap: onTap,
        leading: CircleAvatar(
          radius: 28,
          backgroundColor: Colors.purple.shade100,
          backgroundImage:
              artista.imagen != null ? NetworkImage(artista.imagen!) : null,
          child: artista.imagen == null
              ? Icon(Icons.person, color: Colors.purple.shade400)
              : null,
        ),
        title: Text(
          artista.nombreArtistico,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (artista.categorias != null)
              Text(
                artista.categorias!,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: Colors.grey.shade600,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            const SizedBox(height: 4),
            Row(
              children: [
                _buildNivelBadge(artista.nivel),
                const SizedBox(width: 8),
                if (artista.rating > 0) ...[
                  Icon(Icons.star, size: 14, color: Colors.amber),
                  const SizedBox(width: 2),
                  Text(
                    artista.rating.toStringAsFixed(1),
                    style: theme.textTheme.bodySmall,
                  ),
                ],
                const SizedBox(width: 8),
                Icon(Icons.event, size: 14, color: Colors.grey.shade600),
                const SizedBox(width: 2),
                Text(
                  '${artista.totalEventos}',
                  style: theme.textTheme.bodySmall,
                ),
              ],
            ),
          ],
        ),
        trailing: const Icon(Icons.chevron_right),
      ),
    );
  }

  Widget _buildNivelBadge(int nivel) {
    final colores = [
      Colors.grey,
      Colors.green,
      Colors.blue,
      Colors.purple,
      Colors.orange,
      Colors.pink,
    ];
    final color = colores[nivel.clamp(0, colores.length - 1)];

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.2),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        'Nivel $nivel',
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          color: color,
        ),
      ),
    );
  }
}

class _EventoCard extends StatelessWidget {
  final _Evento evento;
  final VoidCallback onTap;

  const _EventoCard({
    required this.evento,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              // Fecha
              if (evento.fechaInicio != null)
                Container(
                  width: 50,
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.pink.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    children: [
                      Text(
                        evento.fechaInicio!.day.toString(),
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Colors.pink.shade700,
                        ),
                      ),
                      Text(
                        _getMesCorto(evento.fechaInicio!.month),
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.pink.shade700,
                        ),
                      ),
                    ],
                  ),
                ),

              const SizedBox(width: 12),

              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      evento.titulo,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    if (evento.artistaNombre != null)
                      Row(
                        children: [
                          Icon(Icons.music_note,
                              size: 14, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Text(
                            evento.artistaNombre!,
                            style: theme.textTheme.bodySmall,
                          ),
                        ],
                      ),
                    if (evento.espacioNombre != null)
                      Row(
                        children: [
                          Icon(Icons.location_on,
                              size: 14, color: Colors.grey.shade600),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              evento.espacioNombre!,
                              style: theme.textTheme.bodySmall,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                  ],
                ),
              ),

              // Tipo
              if (evento.tipo != null)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: _getTipoColor(evento.tipo!).withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    evento.tipo!,
                    style: TextStyle(
                      fontSize: 11,
                      color: _getTipoColor(evento.tipo!),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  String _getMesCorto(int mes) {
    const meses = [
      'ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN',
      'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'
    ];
    return meses[mes - 1];
  }

  Color _getTipoColor(String tipo) {
    switch (tipo.toLowerCase()) {
      case 'concierto':
        return Colors.purple;
      case 'teatro':
        return Colors.red;
      case 'danza':
        return Colors.pink;
      case 'exposicion':
        return Colors.blue;
      case 'cine':
        return Colors.indigo;
      case 'taller':
        return Colors.green;
      case 'festival':
        return Colors.orange;
      default:
        return Colors.grey;
    }
  }
}

// ============================================================
// VISTA COMUNIDAD
// ============================================================

class _ComunidadView extends StatelessWidget {
  final Map<String, dynamic> data;
  final Function(int) onEventoTap;

  const _ComunidadView({
    required this.data,
    required this.onEventoTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final eventosCercanos = data['eventos_cercanos'] as List<dynamic>? ?? [];
    final proyectosActivos = data['proyectos_activos'] as List<dynamic>? ?? [];
    final agradecimientos = data['agradecimientos'] as List<dynamic>? ?? [];
    final nodos = data['nodos'] as List<dynamic>? ?? [];

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Eventos cercanos
        if (eventosCercanos.isNotEmpty) ...[
          Text(
            'Próximos eventos',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 140,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: eventosCercanos.length,
              itemBuilder: (context, index) {
                final evento = eventosCercanos[index] as Map<String, dynamic>;
                return _EventoMiniCard(
                  evento: evento,
                  onTap: () => onEventoTap(
                    int.tryParse(evento['id']?.toString() ?? '0') ?? 0,
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 24),
        ],

        // Proyectos crowdfunding activos
        if (proyectosActivos.isNotEmpty) ...[
          Text(
            'Proyectos de crowdfunding',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          ...proyectosActivos.take(3).map((proyecto) {
            final p = proyecto as Map<String, dynamic>;
            return _ProyectoCard(proyecto: p);
          }),
          const SizedBox(height: 24),
        ],

        // Muro de agradecimientos
        if (agradecimientos.isNotEmpty) ...[
          Text(
            'Muro de agradecimientos',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          ...agradecimientos.take(5).map((agradecimiento) {
            final a = agradecimiento as Map<String, dynamic>;
            return _AgradecimientoCard(agradecimiento: a);
          }),
          const SizedBox(height: 24),
        ],

        // Nodos de la red
        if (nodos.isNotEmpty) ...[
          Text(
            'Nodos de la red',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: nodos.map((nodo) {
              final n = nodo as Map<String, dynamic>;
              return Chip(
                avatar: const Icon(Icons.place, size: 16),
                label: Text(n['nombre']?.toString() ?? ''),
                backgroundColor: Colors.pink.shade50,
              );
            }).toList(),
          ),
        ],
      ],
    );
  }
}

class _EventoMiniCard extends StatelessWidget {
  final Map<String, dynamic> evento;
  final VoidCallback onTap;

  const _EventoMiniCard({
    required this.evento,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final titulo = evento['titulo']?.toString() ?? '';
    final espacio = evento['espacio_nombre']?.toString();
    final imagen = evento['imagen']?.toString();

    return Card(
      margin: const EdgeInsets.only(right: 12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: SizedBox(
          width: 160,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                height: 80,
                decoration: BoxDecoration(
                  color: Colors.pink.shade100,
                  image: imagen != null
                      ? DecorationImage(
                          image: NetworkImage(imagen),
                          fit: BoxFit.cover,
                        )
                      : null,
                ),
                child: imagen == null
                    ? Center(
                        child: Icon(Icons.event, color: Colors.pink.shade300),
                      )
                    : null,
              ),
              Padding(
                padding: const EdgeInsets.all(8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      titulo,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (espacio != null)
                      Text(
                        espacio,
                        style: TextStyle(
                          fontSize: 10,
                          color: Colors.grey.shade600,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProyectoCard extends StatelessWidget {
  final Map<String, dynamic> proyecto;

  const _ProyectoCard({required this.proyecto});

  @override
  Widget build(BuildContext context) {
    final nombre = proyecto['nombre']?.toString() ?? '';
    final porcentaje = double.tryParse(proyecto['porcentaje']?.toString() ?? '0') ?? 0;
    final recaudado = double.tryParse(proyecto['recaudado_eur']?.toString() ?? '0') ?? 0;
    final objetivo = double.tryParse(proyecto['objetivo_eur']?.toString() ?? '0') ?? 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              nombre,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            LinearProgressIndicator(
              value: (porcentaje / 100).clamp(0, 1),
              backgroundColor: Colors.grey.shade200,
              valueColor: AlwaysStoppedAnimation(
                porcentaje >= 100 ? Colors.green : Colors.pink,
              ),
            ),
            const SizedBox(height: 4),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${recaudado.toStringAsFixed(0)}€',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.pink,
                  ),
                ),
                Text(
                  'de ${objetivo.toStringAsFixed(0)}€ (${porcentaje.toStringAsFixed(0)}%)',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _AgradecimientoCard extends StatelessWidget {
  final Map<String, dynamic> agradecimiento;

  const _AgradecimientoCard({required this.agradecimiento});

  @override
  Widget build(BuildContext context) {
    final deNombre = agradecimiento['de_nombre']?.toString() ?? 'Anónimo';
    final paraNombre = agradecimiento['para_nombre']?.toString() ?? '';
    final mensaje = agradecimiento['mensaje']?.toString() ?? '';
    final eventoTitulo = agradecimiento['evento_titulo']?.toString();

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      color: Colors.amber.shade50,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.favorite, size: 16, color: Colors.pink),
                const SizedBox(width: 4),
                Text(
                  '$deNombre → $paraNombre',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
            if (mensaje.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(
                '"$mensaje"',
                style: TextStyle(
                  fontStyle: FontStyle.italic,
                  color: Colors.grey.shade700,
                  fontSize: 13,
                ),
              ),
            ],
            if (eventoTitulo != null) ...[
              const SizedBox(height: 4),
              Text(
                '📍 $eventoTitulo',
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.grey.shade600,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// ============================================================
// PANTALLAS DE DETALLE
// ============================================================

class _EspacioDetalleScreen extends ConsumerStatefulWidget {
  final _Espacio espacio;

  const _EspacioDetalleScreen({required this.espacio});

  @override
  ConsumerState<_EspacioDetalleScreen> createState() =>
      _EspacioDetalleScreenState();
}

class _EspacioDetalleScreenState extends ConsumerState<_EspacioDetalleScreen> {
  Map<String, dynamic>? _detalleCompleto;
  bool _cargando = true;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/kulturaka/espacios/${widget.espacio.id}');

      if (response.success && response.data != null) {
        setState(() {
          _detalleCompleto = response.data!;
          _cargando = false;
        });
      } else {
        setState(() => _cargando = false);
      }
    } catch (e) {
      setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.espacio.nombre),
        backgroundColor: Colors.pink.shade700,
        foregroundColor: Colors.white,
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : RefreshIndicator(
              onRefresh: _cargarDetalle,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Imagen
                  Container(
                    height: 200,
                    decoration: BoxDecoration(
                      color: Colors.pink.shade50,
                      borderRadius: BorderRadius.circular(12),
                      image: widget.espacio.imagen != null
                          ? DecorationImage(
                              image: NetworkImage(widget.espacio.imagen!),
                              fit: BoxFit.cover,
                            )
                          : null,
                    ),
                    child: widget.espacio.imagen == null
                        ? Center(
                            child: Icon(
                              Icons.location_city,
                              size: 64,
                              color: Colors.pink.shade200,
                            ),
                          )
                        : null,
                  ),
                  const SizedBox(height: 16),

                  // Info básica
                  if (widget.espacio.direccion != null) ...[
                    ListTile(
                      leading: const Icon(Icons.location_on),
                      title: Text(widget.espacio.direccion!),
                      contentPadding: EdgeInsets.zero,
                    ),
                  ],

                  if (widget.espacio.tipoLabel != null)
                    ListTile(
                      leading: const Icon(Icons.category),
                      title: Text(widget.espacio.tipoLabel!),
                      contentPadding: EdgeInsets.zero,
                    ),

                  // Métricas
                  if (_detalleCompleto?['metricas'] != null) ...[
                    const SizedBox(height: 16),
                    Text(
                      'Métricas de impacto',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    _buildMetricas(_detalleCompleto!['metricas']),
                  ],

                  // Calendario (próximos eventos)
                  if (_detalleCompleto?['calendario'] != null &&
                      (_detalleCompleto!['calendario'] as List).isNotEmpty) ...[
                    const SizedBox(height: 24),
                    Text(
                      'Próximos eventos',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ...(_detalleCompleto!['calendario'] as List)
                        .take(5)
                        .map((e) {
                      final evento = _Evento.fromJson(e as Map<String, dynamic>);
                      return _EventoCard(
                        evento: evento,
                        onTap: () {},
                      );
                    }),
                  ],

                  // Propuestas pendientes
                  if (_detalleCompleto?['propuestas'] != null &&
                      (_detalleCompleto!['propuestas'] as List).isNotEmpty) ...[
                    const SizedBox(height: 24),
                    Text(
                      'Propuestas pendientes',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ...(_detalleCompleto!['propuestas'] as List)
                        .take(3)
                        .map((p) {
                      final propuesta = p as Map<String, dynamic>;
                      return Card(
                        margin: const EdgeInsets.only(bottom: 8),
                        child: ListTile(
                          leading: CircleAvatar(
                            backgroundColor: Colors.orange.shade100,
                            child: const Icon(Icons.pending, color: Colors.orange),
                          ),
                          title: Text(propuesta['titulo']?.toString() ?? ''),
                          subtitle: Text(
                            propuesta['nombre_artistico']?.toString() ?? '',
                          ),
                        ),
                      );
                    }),
                  ],
                ],
              ),
            ),
    );
  }

  Widget _buildMetricas(Map<String, dynamic> metricas) {
    return Wrap(
      spacing: 12,
      runSpacing: 12,
      children: [
        _MetricaChip(
          icon: Icons.event,
          valor: metricas['eventos_realizados']?.toString() ?? '0',
          label: 'Eventos',
        ),
        _MetricaChip(
          icon: Icons.people,
          valor: metricas['audiencia_total']?.toString() ?? '0',
          label: 'Audiencia',
        ),
        _MetricaChip(
          icon: Icons.music_note,
          valor: metricas['artistas_acogidos']?.toString() ?? '0',
          label: 'Artistas',
        ),
        _MetricaChip(
          icon: Icons.eco,
          valor: '${metricas['co2_evitado']?.toString() ?? '0'} kg',
          label: 'CO₂ evitado',
        ),
      ],
    );
  }
}

class _ArtistaDetalleScreen extends ConsumerStatefulWidget {
  final _Artista artista;

  const _ArtistaDetalleScreen({required this.artista});

  @override
  ConsumerState<_ArtistaDetalleScreen> createState() =>
      _ArtistaDetalleScreenState();
}

class _ArtistaDetalleScreenState extends ConsumerState<_ArtistaDetalleScreen> {
  Map<String, dynamic>? _detalleCompleto;
  bool _cargando = true;
  bool _siguiendo = false;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/kulturaka/artistas/${widget.artista.id}');

      if (response.success && response.data != null) {
        setState(() {
          _detalleCompleto = response.data!;
          _siguiendo = response.data!['siguiendo'] == true;
          _cargando = false;
        });
      } else {
        setState(() => _cargando = false);
      }
    } catch (e) {
      setState(() => _cargando = false);
    }
  }

  Future<void> _toggleSeguir() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post(
        '/kulturaka/artistas/${widget.artista.id}/seguir',
        data: {},
      );

      if (response.success) {
        setState(() {
          _siguiendo = response.data?['siguiendo'] == true;
        });
        if (mounted) {
          FlavorSnackbar.showSuccess(
            context,
            _siguiendo ? 'Siguiendo a ${widget.artista.nombreArtistico}' : 'Dejaste de seguir',
          );
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error al actualizar');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.artista.nombreArtistico),
        backgroundColor: Colors.purple.shade700,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: Icon(_siguiendo ? Icons.favorite : Icons.favorite_border),
            onPressed: _toggleSeguir,
            tooltip: _siguiendo ? 'Dejar de seguir' : 'Seguir',
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : RefreshIndicator(
              onRefresh: _cargarDetalle,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Avatar y nombre
                  Center(
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 60,
                          backgroundColor: Colors.purple.shade100,
                          backgroundImage: widget.artista.imagen != null
                              ? NetworkImage(widget.artista.imagen!)
                              : null,
                          child: widget.artista.imagen == null
                              ? Icon(
                                  Icons.person,
                                  size: 60,
                                  color: Colors.purple.shade300,
                                )
                              : null,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          widget.artista.nombreArtistico,
                          style: theme.textTheme.headlineSmall?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        if (widget.artista.categorias != null)
                          Text(
                            widget.artista.categorias!,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: Colors.grey.shade600,
                            ),
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Stats
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      _StatColumn(
                        valor: widget.artista.totalEventos.toString(),
                        label: 'Eventos',
                      ),
                      _StatColumn(
                        valor: widget.artista.rating.toStringAsFixed(1),
                        label: 'Rating',
                      ),
                      _StatColumn(
                        valor: 'Nivel ${widget.artista.nivel}',
                        label: 'Experiencia',
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // Biografía
                  if (widget.artista.biografia != null) ...[
                    Text(
                      'Biografía',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(widget.artista.biografia!),
                    const SizedBox(height: 24),
                  ],

                  // Gira (próximos eventos)
                  if (_detalleCompleto?['gira'] != null &&
                      (_detalleCompleto!['gira'] as List).isNotEmpty) ...[
                    Text(
                      'Próximos conciertos',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ...(_detalleCompleto!['gira'] as List).take(5).map((e) {
                      final evento = _Evento.fromJson(e as Map<String, dynamic>);
                      return _EventoCard(
                        evento: evento,
                        onTap: () {},
                      );
                    }),
                  ],

                  // Proyectos crowdfunding
                  if (_detalleCompleto?['crowdfunding'] != null &&
                      (_detalleCompleto!['crowdfunding'] as List).isNotEmpty) ...[
                    const SizedBox(height: 24),
                    Text(
                      'Proyectos de crowdfunding',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ...(_detalleCompleto!['crowdfunding'] as List)
                        .take(3)
                        .map((p) {
                      return _ProyectoCard(proyecto: p as Map<String, dynamic>);
                    }),
                  ],
                ],
              ),
            ),
    );
  }
}

class _StatColumn extends StatelessWidget {
  final String valor;
  final String label;

  const _StatColumn({
    required this.valor,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          valor,
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey.shade600,
          ),
        ),
      ],
    );
  }
}

class _MetricaChip extends StatelessWidget {
  final IconData icon;
  final String valor;
  final String label;

  const _MetricaChip({
    required this.icon,
    required this.valor,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.pink.shade50,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: Colors.pink.shade700),
          const SizedBox(width: 4),
          Text(
            valor,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              color: Colors.pink.shade700,
            ),
          ),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: Colors.pink.shade600,
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================
// MÉTRICAS BOTTOM SHEET
// ============================================================

class _MetricasSheet extends StatelessWidget {
  final Map<String, dynamic> metricas;

  const _MetricasSheet({required this.metricas});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Métricas de la Red Kulturaka',
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: _MetricaTile(
                  icon: Icons.place,
                  valor: metricas['total_nodos']?.toString() ?? '0',
                  label: 'Nodos activos',
                  color: Colors.blue,
                ),
              ),
              Expanded(
                child: _MetricaTile(
                  icon: Icons.event,
                  valor: metricas['total_eventos']?.toString() ?? '0',
                  label: 'Eventos realizados',
                  color: Colors.green,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _MetricaTile(
                  icon: Icons.music_note,
                  valor: metricas['total_artistas']?.toString() ?? '0',
                  label: 'Artistas apoyados',
                  color: Colors.purple,
                ),
              ),
              Expanded(
                child: _MetricaTile(
                  icon: Icons.location_city,
                  valor: metricas['total_espacios']?.toString() ?? '0',
                  label: 'Espacios culturales',
                  color: Colors.orange,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _MetricaTile(
                  icon: Icons.euro,
                  valor: '${(metricas['fondos_recaudados'] ?? 0).toStringAsFixed(0)}€',
                  label: 'Fondos recaudados',
                  color: Colors.pink,
                ),
              ),
              Expanded(
                child: _MetricaTile(
                  icon: Icons.people,
                  valor: metricas['audiencia_total']?.toString() ?? '0',
                  label: 'Audiencia total',
                  color: Colors.teal,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MetricaTile extends StatelessWidget {
  final IconData icon;
  final String valor;
  final String label;
  final Color color;

  const _MetricaTile({
    required this.icon,
    required this.valor,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.all(4),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          Icon(icon, size: 32, color: color),
          const SizedBox(height: 8),
          Text(
            valor,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade600,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}
