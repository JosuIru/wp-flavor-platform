part of 'eventos_screen.dart';

// =============================================================================
// MODELO DE EVENTO
// =============================================================================

class _Evento {
  final int id;
  final String titulo;
  final String descripcion;
  final String categoria;
  final DateTime? fechaInicio;
  final DateTime? fechaFin;
  final String horaInicio;
  final String horaFin;
  final String ubicacion;
  final double? latitud;
  final double? longitud;
  final String imagen;
  final double precio;
  final bool gratuito;
  final int plazasDisponibles;
  final int plazasTotales;
  final String organizador;
  final String contacto;
  final bool inscrito;
  final bool requiereInscripcion;

  _Evento({
    required this.id,
    required this.titulo,
    this.descripcion = '',
    this.categoria = '',
    this.fechaInicio,
    this.fechaFin,
    this.horaInicio = '',
    this.horaFin = '',
    this.ubicacion = '',
    this.latitud,
    this.longitud,
    this.imagen = '',
    this.precio = 0,
    this.gratuito = true,
    this.plazasDisponibles = 0,
    this.plazasTotales = 0,
    this.organizador = '',
    this.contacto = '',
    this.inscrito = false,
    this.requiereInscripcion = false,
  });

  factory _Evento.fromJson(Map<String, dynamic> json) {
    final precioValue = _parseDouble(json['precio'] ?? json['price']) ?? 0.0;
    return _Evento(
      id: json['id'] as int? ?? 0,
      titulo: json['titulo'] ?? json['title'] ?? 'Evento',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      categoria: json['categoria'] ?? json['category'] ?? '',
      fechaInicio: _parseDate(json['fecha_inicio'] ?? json['start_date']),
      fechaFin: _parseDate(json['fecha_fin'] ?? json['end_date']),
      horaInicio: json['hora_inicio'] ?? json['start_time'] ?? '',
      horaFin: json['hora_fin'] ?? json['end_time'] ?? '',
      ubicacion: json['ubicacion'] ?? json['location'] ?? '',
      latitud: _parseDouble(json['latitud'] ?? json['lat']),
      longitud: _parseDouble(json['longitud'] ?? json['lng']),
      imagen: json['imagen'] ?? json['image'] ?? '',
      precio: precioValue,
      gratuito: precioValue == 0 || json['gratuito'] == true || json['free'] == true,
      plazasDisponibles: json['plazas_disponibles'] ?? json['available_spots'] ?? 0,
      plazasTotales: json['plazas_totales'] ?? json['total_spots'] ?? 0,
      organizador: json['organizador'] ?? json['organizer'] ?? '',
      contacto: json['contacto'] ?? json['contact'] ?? '',
      inscrito: json['inscrito'] == true || json['registered'] == true,
      requiereInscripcion: json['requiere_inscripcion'] == true || json['requires_registration'] == true,
    );
  }

  static DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String) return DateTime.tryParse(value);
    return null;
  }

  static double? _parseDouble(dynamic value) {
    if (value == null) return null;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }

  String get fechaFormateada {
    if (fechaInicio == null) return '';
    final meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return '${fechaInicio!.day} ${meses[fechaInicio!.month - 1]}';
  }

  String get horarioCompleto {
    if (horaInicio.isEmpty) return '';
    if (horaFin.isEmpty) return horaInicio;
    return '$horaInicio - $horaFin';
  }
}

// =============================================================================
// TARJETA DE EVENTO (LISTA)
// =============================================================================

class _EventoCard extends StatelessWidget {
  final _Evento evento;
  final VoidCallback onTap;

  const _EventoCard({required this.evento, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen
            Stack(
              children: [
                if (evento.imagen.isNotEmpty)
                  Image.network(
                    evento.imagen,
                    height: 150,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => _buildPlaceholder(),
                  )
                else
                  _buildPlaceholder(),

                // Fecha badge
                Positioned(
                  top: 12,
                  left: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 4,
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        Text(
                          evento.fechaInicio?.day.toString() ?? '--',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.deepPurple.shade700,
                          ),
                        ),
                        Text(
                          _getMesCorto(evento.fechaInicio?.month),
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.deepPurple.shade400,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // Categoria badge
                if (evento.categoria.isNotEmpty)
                  Positioned(
                    top: 12,
                    right: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: _getColorForCategoria(evento.categoria),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        evento.categoria[0].toUpperCase() + evento.categoria.substring(1),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ),

                // Inscrito badge
                if (evento.inscrito)
                  Positioned(
                    bottom: 12,
                    right: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.green,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.check, color: Colors.white, size: 14),
                          SizedBox(width: 4),
                          Text(
                            'Inscrito',
                            style: TextStyle(color: Colors.white, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),

            // Info
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Titulo
                  Text(
                    evento.titulo,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),

                  const SizedBox(height: 8),

                  // Hora y ubicacion
                  Row(
                    children: [
                      if (evento.horaInicio.isNotEmpty) ...[
                        Icon(Icons.access_time, size: 16, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Text(
                          evento.horarioCompleto,
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                        const SizedBox(width: 16),
                      ],
                      if (evento.ubicacion.isNotEmpty) ...[
                        Icon(Icons.location_on, size: 16, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            evento.ubicacion,
                            style: TextStyle(color: Colors.grey.shade600),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ],
                  ),

                  const SizedBox(height: 12),

                  // Precio y plazas
                  Row(
                    children: [
                      // Precio
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: evento.gratuito ? Colors.green.shade50 : Colors.deepPurple.shade50,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          evento.gratuito ? 'Gratis' : '${evento.precio.toStringAsFixed(2)}€',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: evento.gratuito ? Colors.green.shade700 : Colors.deepPurple.shade700,
                          ),
                        ),
                      ),

                      const Spacer(),

                      // Plazas
                      if (evento.requiereInscripcion && evento.plazasTotales > 0)
                        Row(
                          children: [
                            Icon(Icons.people, size: 16, color: Colors.grey.shade600),
                            const SizedBox(width: 4),
                            Text(
                              '${evento.plazasDisponibles}/${evento.plazasTotales}',
                              style: TextStyle(color: Colors.grey.shade600),
                            ),
                          ],
                        ),
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
      height: 150,
      color: Colors.deepPurple.shade100,
      child: Center(
        child: Icon(Icons.event, size: 48, color: Colors.deepPurple.shade300),
      ),
    );
  }

  String _getMesCorto(int? mes) {
    if (mes == null) return '--';
    final meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    return meses[mes - 1];
  }

  Color _getColorForCategoria(String categoria) {
    switch (categoria.toLowerCase()) {
      case 'cultural':
        return Colors.purple;
      case 'deportivo':
        return Colors.orange;
      case 'social':
        return Colors.blue;
      case 'formativo':
        return Colors.teal;
      case 'festivo':
        return Colors.pink;
      case 'solidario':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}

// =============================================================================
// TARJETA MINI (CALENDARIO)
// =============================================================================

class _EventoMiniCard extends StatelessWidget {
  final _Evento evento;
  final VoidCallback onTap;

  const _EventoMiniCard({required this.evento, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: Colors.deepPurple.shade100,
            borderRadius: BorderRadius.circular(8),
          ),
          child: evento.imagen.isNotEmpty
              ? ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    evento.imagen,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) =>
                        Icon(Icons.event, color: Colors.deepPurple.shade400),
                  ),
                )
              : Icon(Icons.event, color: Colors.deepPurple.shade400),
        ),
        title: Text(
          evento.titulo,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: Text(
          '${evento.horaInicio.isNotEmpty ? evento.horaInicio : "Todo el día"}${evento.ubicacion.isNotEmpty ? " · ${evento.ubicacion}" : ""}',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: evento.gratuito
            ? const Chip(
                label: Text('Gratis', style: TextStyle(fontSize: 10)),
                backgroundColor: Colors.green,
                labelStyle: TextStyle(color: Colors.white),
                padding: EdgeInsets.zero,
                visualDensity: VisualDensity.compact,
              )
            : Text(
                '${evento.precio.toStringAsFixed(0)}€',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.deepPurple.shade700,
                ),
              ),
        onTap: onTap,
      ),
    );
  }
}

// =============================================================================
// PANTALLA DE DETALLE
// =============================================================================

class _EventoDetalleScreen extends ConsumerStatefulWidget {
  final _Evento evento;

  const _EventoDetalleScreen({required this.evento});

  @override
  ConsumerState<_EventoDetalleScreen> createState() => _EventoDetalleScreenState();
}

class _EventoDetalleScreenState extends ConsumerState<_EventoDetalleScreen> {
  int _numPlazas = 1;
  bool _inscribiendo = false;
  bool _inscrito = false;

  @override
  void initState() {
    super.initState();
    _inscrito = widget.evento.inscrito;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // App bar con imagen
          SliverAppBar(
            expandedHeight: 250,
            pinned: true,
            backgroundColor: Colors.deepPurple,
            foregroundColor: Colors.white,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(
                widget.evento.titulo,
                style: const TextStyle(
                  shadows: [Shadow(blurRadius: 4, color: Colors.black54)],
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              background: Stack(
                fit: StackFit.expand,
                children: [
                  if (widget.evento.imagen.isNotEmpty)
                    Image.network(
                      widget.evento.imagen,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => _buildPlaceholder(),
                    )
                  else
                    _buildPlaceholder(),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          Colors.black.withOpacity(0.7),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.share),
                onPressed: _compartir,
                tooltip: 'Compartir',
              ),
            ],
          ),

          // Contenido
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Info principal
                  _buildInfoCard(),

                  const SizedBox(height: 16),

                  // Descripcion
                  if (widget.evento.descripcion.isNotEmpty) ...[
                    _buildSeccion('Descripcion', Icons.description),
                    Text(
                      widget.evento.descripcion,
                      style: const TextStyle(fontSize: 16, height: 1.5),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Ubicacion
                  if (widget.evento.ubicacion.isNotEmpty) ...[
                    _buildSeccion('Ubicacion', Icons.location_on),
                    Card(
                      child: ListTile(
                        leading: Icon(Icons.place, color: Colors.red.shade400),
                        title: Text(widget.evento.ubicacion),
                        trailing: widget.evento.latitud != null
                            ? IconButton(
                                icon: const Icon(Icons.directions),
                                onPressed: _abrirMapa,
                              )
                            : null,
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Organizador
                  if (widget.evento.organizador.isNotEmpty) ...[
                    _buildSeccion('Organizador', Icons.person),
                    Card(
                      child: ListTile(
                        leading: CircleAvatar(
                          backgroundColor: Colors.deepPurple.shade100,
                          child: Icon(Icons.business, color: Colors.deepPurple.shade600),
                        ),
                        title: Text(widget.evento.organizador),
                        subtitle: widget.evento.contacto.isNotEmpty
                            ? Text(widget.evento.contacto)
                            : null,
                        trailing: widget.evento.contacto.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.message),
                                onPressed: _contactarOrganizador,
                              )
                            : null,
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Inscripcion
                  if (widget.evento.requiereInscripcion) ...[
                    _buildSeccion('Inscripcion', Icons.how_to_reg),
                    _buildInscripcionCard(),
                    const SizedBox(height: 24),
                  ],

                  const SizedBox(height: 32),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: widget.evento.requiereInscripcion && !_inscrito
          ? SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: ElevatedButton.icon(
                  onPressed: _inscribiendo ? null : _inscribirse,
                  icon: _inscribiendo
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Icon(Icons.check_circle),
                  label: Text(_inscribiendo ? 'Inscribiendo...' : 'Inscribirme'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.deepPurple,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                ),
              ),
            )
          : null,
    );
  }

  Widget _buildPlaceholder() {
    return Container(
      color: Colors.deepPurple.shade200,
      child: Center(
        child: Icon(Icons.event, size: 80, color: Colors.deepPurple.shade400),
      ),
    );
  }

  Widget _buildInfoCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            // Fecha y hora
            Row(
              children: [
                _InfoTile(
                  icon: Icons.calendar_today,
                  label: 'Fecha',
                  value: widget.evento.fechaFormateada,
                  color: Colors.deepPurple,
                ),
                const SizedBox(width: 16),
                if (widget.evento.horaInicio.isNotEmpty)
                  _InfoTile(
                    icon: Icons.access_time,
                    label: 'Hora',
                    value: widget.evento.horarioCompleto,
                    color: Colors.blue,
                  ),
              ],
            ),
            const Divider(height: 24),
            // Precio y plazas
            Row(
              children: [
                _InfoTile(
                  icon: widget.evento.gratuito ? Icons.card_giftcard : Icons.euro,
                  label: 'Precio',
                  value: widget.evento.gratuito ? 'Gratis' : '${widget.evento.precio.toStringAsFixed(2)}€',
                  color: Colors.green,
                ),
                const SizedBox(width: 16),
                if (widget.evento.plazasTotales > 0)
                  _InfoTile(
                    icon: Icons.people,
                    label: 'Plazas',
                    value: '${widget.evento.plazasDisponibles} disponibles',
                    color: Colors.orange,
                  ),
              ],
            ),
            if (widget.evento.categoria.isNotEmpty) ...[
              const Divider(height: 24),
              Row(
                children: [
                  Icon(Icons.category, color: Colors.grey.shade600, size: 20),
                  const SizedBox(width: 8),
                  Chip(
                    label: Text(widget.evento.categoria[0].toUpperCase() +
                        widget.evento.categoria.substring(1)),
                    backgroundColor: Colors.deepPurple.shade50,
                  ),
                  if (_inscrito) ...[
                    const Spacer(),
                    Chip(
                      avatar: const Icon(Icons.check, size: 16, color: Colors.white),
                      label: const Text('Inscrito'),
                      backgroundColor: Colors.green,
                      labelStyle: const TextStyle(color: Colors.white),
                    ),
                  ],
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildSeccion(String titulo, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(icon, color: Colors.deepPurple.shade600, size: 22),
          const SizedBox(width: 8),
          Text(
            titulo,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.deepPurple.shade800,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInscripcionCard() {
    return Card(
      color: _inscrito ? Colors.green.shade50 : null,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: _inscrito
            ? Row(
                children: [
                  Icon(Icons.check_circle, color: Colors.green.shade600),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Ya estas inscrito',
                          style: TextStyle(fontWeight: FontWeight.bold),
                        ),
                        Text('Te esperamos en el evento'),
                      ],
                    ),
                  ),
                  TextButton(
                    onPressed: _cancelarInscripcion,
                    child: const Text('Cancelar'),
                  ),
                ],
              )
            : Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Numero de plazas',
                    style: TextStyle(fontWeight: FontWeight.w500),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      IconButton(
                        onPressed: _numPlazas > 1
                            ? () => setState(() => _numPlazas--)
                            : null,
                        icon: const Icon(Icons.remove_circle_outline),
                      ),
                      Text(
                        '$_numPlazas',
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                      ),
                      IconButton(
                        onPressed: _numPlazas < 10 &&
                                _numPlazas < widget.evento.plazasDisponibles
                            ? () => setState(() => _numPlazas++)
                            : null,
                        icon: const Icon(Icons.add_circle_outline),
                      ),
                      const Spacer(),
                      if (!widget.evento.gratuito)
                        Text(
                          'Total: ${(widget.evento.precio * _numPlazas).toStringAsFixed(2)}€',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.deepPurple.shade700,
                          ),
                        ),
                    ],
                  ),
                ],
              ),
      ),
    );
  }

  Future<void> _inscribirse() async {
    setState(() => _inscribiendo = true);

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post(
        '/eventos/${widget.evento.id}/inscribir',
        data: {'num_plazas': _numPlazas},
      );

      if (response.success) {
        setState(() => _inscrito = true);
        if (mounted) {
          FlavorSnackbar.showSuccess(context, 'Inscripcion realizada correctamente');
        }
      } else {
        if (mounted) {
          FlavorSnackbar.showError(context, response.error ?? 'Error al inscribirse');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error de conexion');
      }
    } finally {
      if (mounted) {
        setState(() => _inscribiendo = false);
      }
    }
  }

  Future<void> _cancelarInscripcion() async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancelar inscripcion'),
        content: const Text('¿Seguro que quieres cancelar tu inscripcion?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('No'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Si, cancelar'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post('/eventos/${widget.evento.id}/cancelar-inscripcion', data: {});

      if (response.success) {
        setState(() => _inscrito = false);
        if (mounted) {
          FlavorSnackbar.showInfo(context, 'Inscripcion cancelada');
        }
      } else {
        if (mounted) {
          FlavorSnackbar.showError(context, response.error ?? 'Error al cancelar');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error de conexion');
      }
    }
  }

  void _compartir() {
    final texto = '''
${widget.evento.titulo}

Fecha: ${widget.evento.fechaFormateada}
${widget.evento.horaInicio.isNotEmpty ? 'Hora: ${widget.evento.horarioCompleto}' : ''}
${widget.evento.ubicacion.isNotEmpty ? 'Lugar: ${widget.evento.ubicacion}' : ''}
${widget.evento.gratuito ? 'Entrada gratuita' : 'Precio: ${widget.evento.precio}€'}
''';
    Clipboard.setData(ClipboardData(text: texto));
    FlavorSnackbar.showInfo(context, 'Informacion copiada al portapapeles');
  }

  Future<void> _abrirMapa() async {
    if (widget.evento.latitud == null || widget.evento.longitud == null) return;

    final url = MapLaunchHelper.buildConfiguredMapUri(
      widget.evento.latitud!,
      widget.evento.longitud!,
      query: widget.evento.ubicacion,
    );
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }

  void _contactarOrganizador() {
    if (widget.evento.contacto.contains('@')) {
      FlavorContactLauncher.launchEmail(widget.evento.contacto);
    } else {
      FlavorContactLauncher.call(context, widget.evento.contacto);
    }
  }
}

// =============================================================================
// WIDGET INFO TILE
// =============================================================================

class _InfoTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  const _InfoTile({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
              ),
              Text(
                value,
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
