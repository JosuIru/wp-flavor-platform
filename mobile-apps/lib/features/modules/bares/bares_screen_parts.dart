part of 'bares_screen.dart';

// =============================================================================
// MODELO DE BAR
// =============================================================================

class _Bar {
  final int id;
  final String nombre;
  final String descripcion;
  final String tipo;
  final String direccion;
  final String telefono;
  final String email;
  final String imagen;
  final double? latitud;
  final double? longitud;
  final String horario;
  final bool abierto;
  final double valoracion;
  final int numResenas;
  final bool aceptaReservas;
  final List<String> especialidades;
  final List<String> servicios;

  _Bar({
    required this.id,
    required this.nombre,
    this.descripcion = '',
    this.tipo = '',
    this.direccion = '',
    this.telefono = '',
    this.email = '',
    this.imagen = '',
    this.latitud,
    this.longitud,
    this.horario = '',
    this.abierto = false,
    this.valoracion = 0.0,
    this.numResenas = 0,
    this.aceptaReservas = false,
    this.especialidades = const [],
    this.servicios = const [],
  });

  factory _Bar.fromJson(Map<String, dynamic> json) {
    return _Bar(
      id: json['id'] as int? ?? 0,
      nombre: json['nombre'] ?? json['title'] ?? 'Bar',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      tipo: json['tipo'] ?? json['type'] ?? '',
      direccion: json['direccion'] ?? json['address'] ?? '',
      telefono: json['telefono'] ?? json['phone'] ?? '',
      email: json['email'] ?? '',
      imagen: json['imagen'] ?? json['image'] ?? '',
      latitud: _parseDouble(json['latitud'] ?? json['lat']),
      longitud: _parseDouble(json['longitud'] ?? json['lng']),
      horario: json['horario'] ?? json['schedule'] ?? '',
      abierto: json['abierto'] == true || json['open'] == true,
      valoracion: _parseDouble(json['valoracion'] ?? json['rating']) ?? 0.0,
      numResenas: json['num_resenas'] ?? json['reviews_count'] ?? 0,
      aceptaReservas: json['acepta_reservas'] == true || json['accepts_reservations'] == true,
      especialidades: _parseStringList(json['especialidades'] ?? json['specialties']),
      servicios: _parseStringList(json['servicios'] ?? json['services']),
    );
  }

  static double? _parseDouble(dynamic value) {
    if (value == null) return null;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }

  static List<String> _parseStringList(dynamic value) {
    if (value == null) return [];
    if (value is List) return value.map((e) => e.toString()).toList();
    if (value is String) return value.split(',').map((e) => e.trim()).toList();
    return [];
  }
}

// =============================================================================
// MODELO DE ITEM DE MENU
// =============================================================================

class _MenuItem {
  final int id;
  final String nombre;
  final String descripcion;
  final String categoria;
  final double precio;
  final String imagen;
  final bool disponible;
  final List<String> alergenos;

  _MenuItem({
    required this.id,
    required this.nombre,
    this.descripcion = '',
    this.categoria = '',
    required this.precio,
    this.imagen = '',
    this.disponible = true,
    this.alergenos = const [],
  });

  factory _MenuItem.fromJson(Map<String, dynamic> json) {
    return _MenuItem(
      id: json['id'] as int? ?? 0,
      nombre: json['nombre'] ?? json['name'] ?? '',
      descripcion: json['descripcion'] ?? json['description'] ?? '',
      categoria: json['categoria'] ?? json['category'] ?? '',
      precio: _Bar._parseDouble(json['precio'] ?? json['price']) ?? 0.0,
      imagen: json['imagen'] ?? json['image'] ?? '',
      disponible: json['disponible'] != false,
      alergenos: _Bar._parseStringList(json['alergenos'] ?? json['allergens']),
    );
  }
}

// =============================================================================
// TARJETA DE BAR
// =============================================================================

class _BarCard extends StatelessWidget {
  final _Bar bar;
  final VoidCallback onTap;

  const _BarCard({required this.bar, required this.onTap});

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
                if (bar.imagen.isNotEmpty)
                  Image.network(
                    bar.imagen,
                    height: 160,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => _buildPlaceholderImage(),
                  )
                else
                  _buildPlaceholderImage(),

                // Badge de abierto/cerrado
                Positioned(
                  top: 12,
                  left: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: bar.abierto ? Colors.green : Colors.red,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      bar.abierto ? 'Abierto' : 'Cerrado',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),

                // Tipo de bar
                if (bar.tipo.isNotEmpty)
                  Positioned(
                    top: 12,
                    right: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.black54,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        bar.tipo[0].toUpperCase() + bar.tipo.substring(1),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                        ),
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
                  // Nombre y valoracion
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          bar.nombre,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      if (bar.valoracion > 0) ...[
                        Icon(Icons.star, color: Colors.amber.shade600, size: 20),
                        const SizedBox(width: 4),
                        Text(
                          bar.valoracion.toStringAsFixed(1),
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Colors.amber.shade800,
                          ),
                        ),
                        if (bar.numResenas > 0)
                          Text(
                            ' (${bar.numResenas})',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade600,
                            ),
                          ),
                      ],
                    ],
                  ),

                  if (bar.descripcion.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      bar.descripcion,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(color: Colors.grey.shade700),
                    ),
                  ],

                  const SizedBox(height: 12),

                  // Direccion y horario
                  if (bar.direccion.isNotEmpty)
                    Row(
                      children: [
                        Icon(Icons.location_on, size: 16, color: Colors.grey.shade600),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            bar.direccion,
                            style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),

                  if (bar.horario.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(Icons.access_time, size: 16, color: Colors.grey.shade600),
                        const SizedBox(width: 6),
                        Text(
                          bar.horario,
                          style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  ],

                  // Especialidades
                  if (bar.especialidades.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 6,
                      runSpacing: 4,
                      children: bar.especialidades.take(3).map((especialidad) {
                        return Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.amber.shade50,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.amber.shade200),
                          ),
                          child: Text(
                            especialidad,
                            style: TextStyle(fontSize: 11, color: Colors.amber.shade800),
                          ),
                        );
                      }).toList(),
                    ),
                  ],

                  // Iconos de servicios
                  if (bar.servicios.isNotEmpty || bar.aceptaReservas) ...[
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        if (bar.aceptaReservas)
                          _ServiceIcon(icon: Icons.calendar_today, label: 'Reservas'),
                        if (bar.servicios.contains('wifi'))
                          _ServiceIcon(icon: Icons.wifi, label: 'WiFi'),
                        if (bar.servicios.contains('terraza'))
                          _ServiceIcon(icon: Icons.deck, label: 'Terraza'),
                        if (bar.servicios.contains('musica'))
                          _ServiceIcon(icon: Icons.music_note, label: 'Musica'),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPlaceholderImage() {
    return Container(
      height: 160,
      color: Colors.amber.shade100,
      child: Center(
        child: Icon(Icons.local_bar, size: 64, color: Colors.amber.shade300),
      ),
    );
  }
}

class _ServiceIcon extends StatelessWidget {
  final IconData icon;
  final String label;

  const _ServiceIcon({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 12),
      child: Tooltip(
        message: label,
        child: Icon(icon, size: 18, color: Colors.grey.shade600),
      ),
    );
  }
}

// =============================================================================
// PANTALLA DE DETALLE DEL BAR
// =============================================================================

class _BarDetalleScreen extends ConsumerStatefulWidget {
  final _Bar bar;

  const _BarDetalleScreen({required this.bar});

  @override
  ConsumerState<_BarDetalleScreen> createState() => _BarDetalleScreenState();
}

class _BarDetalleScreenState extends ConsumerState<_BarDetalleScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<_MenuItem> _menuItems = [];
  bool _cargandoMenu = true;
  String _categoriaMenuSeleccionada = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: widget.bar.aceptaReservas ? 3 : 2, vsync: this);
    _cargarMenu();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarMenu() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/bares/${widget.bar.id}/menu');

      if (response.success && response.data != null) {
        final items = response.data!['items'] as List<dynamic>? ?? [];
        setState(() {
          _menuItems = items.map((json) => _MenuItem.fromJson(json)).toList();
        });
      }
    } finally {
      setState(() => _cargandoMenu = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: NestedScrollView(
        headerSliverBuilder: (context, innerBoxIsScrolled) {
          return [
            // App bar con imagen
            SliverAppBar(
              expandedHeight: 220,
              pinned: true,
              backgroundColor: Colors.amber.shade800,
              foregroundColor: Colors.white,
              flexibleSpace: FlexibleSpaceBar(
                title: Text(
                  widget.bar.nombre,
                  style: const TextStyle(
                    shadows: [Shadow(blurRadius: 4, color: Colors.black54)],
                  ),
                ),
                background: Stack(
                  fit: StackFit.expand,
                  children: [
                    if (widget.bar.imagen.isNotEmpty)
                      Image.network(
                        widget.bar.imagen,
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
                    // Badge abierto
                    Positioned(
                      bottom: 60,
                      left: 16,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: widget.bar.abierto ? Colors.green : Colors.red,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              widget.bar.abierto ? Icons.check_circle : Icons.cancel,
                              color: Colors.white,
                              size: 16,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              widget.bar.abierto ? 'Abierto ahora' : 'Cerrado',
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                if (widget.bar.telefono.isNotEmpty)
                  IconButton(
                    icon: const Icon(Icons.phone),
                    onPressed: () => FlavorContactLauncher.call(context, widget.bar.telefono),
                    tooltip: 'Llamar',
                  ),
                if (widget.bar.latitud != null && widget.bar.longitud != null)
                  IconButton(
                    icon: const Icon(Icons.map),
                    onPressed: _abrirMapa,
                    tooltip: 'Ver en mapa',
                  ),
              ],
            ),

            // Tab bar
            SliverPersistentHeader(
              pinned: true,
              delegate: _SliverTabBarDelegate(
                TabBar(
                  controller: _tabController,
                  labelColor: Colors.amber.shade800,
                  unselectedLabelColor: Colors.grey,
                  indicatorColor: Colors.amber.shade800,
                  tabs: [
                    const Tab(text: 'Info', icon: Icon(Icons.info_outline)),
                    const Tab(text: 'Carta', icon: Icon(Icons.restaurant_menu)),
                    if (widget.bar.aceptaReservas)
                      const Tab(text: 'Reservar', icon: Icon(Icons.calendar_today)),
                  ],
                ),
              ),
            ),
          ];
        },
        body: TabBarView(
          controller: _tabController,
          children: [
            _buildInfoTab(),
            _buildMenuTab(),
            if (widget.bar.aceptaReservas) _buildReservasTab(),
          ],
        ),
      ),
    );
  }

  Widget _buildPlaceholder() {
    return Container(
      color: Colors.amber.shade200,
      child: Center(
        child: Icon(Icons.local_bar, size: 80, color: Colors.amber.shade400),
      ),
    );
  }

  // =========== TAB INFO ===========

  Widget _buildInfoTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Valoracion
          if (widget.bar.valoracion > 0)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.amber.shade100,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.star, color: Colors.amber.shade800, size: 28),
                          const SizedBox(width: 6),
                          Text(
                            widget.bar.valoracion.toStringAsFixed(1),
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: Colors.amber.shade800,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 16),
                    Text(
                      '${widget.bar.numResenas} resenas',
                      style: TextStyle(color: Colors.grey.shade600),
                    ),
                  ],
                ),
              ),
            ),

          const SizedBox(height: 16),

          // Descripcion
          if (widget.bar.descripcion.isNotEmpty) ...[
            Text(
              widget.bar.descripcion,
              style: const TextStyle(fontSize: 16, height: 1.5),
            ),
            const SizedBox(height: 24),
          ],

          // Especialidades
          if (widget.bar.especialidades.isNotEmpty) ...[
            _buildSeccionTitulo('Especialidades', Icons.local_bar),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: widget.bar.especialidades.map((especialidad) {
                return Chip(
                  avatar: Icon(Icons.local_drink, size: 18, color: Colors.amber.shade700),
                  label: Text(especialidad),
                  backgroundColor: Colors.amber.shade50,
                );
              }).toList(),
            ),
            const SizedBox(height: 24),
          ],

          // Servicios
          if (widget.bar.servicios.isNotEmpty) ...[
            _buildSeccionTitulo('Servicios', Icons.room_service),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: widget.bar.servicios.map((servicio) {
                return Chip(
                  avatar: Icon(_getServiceIcon(servicio), size: 18),
                  label: Text(servicio[0].toUpperCase() + servicio.substring(1)),
                );
              }).toList(),
            ),
            const SizedBox(height: 24),
          ],

          // Informacion de contacto
          _buildSeccionTitulo('Contacto', Icons.contact_phone),
          Card(
            child: Column(
              children: [
                if (widget.bar.direccion.isNotEmpty)
                  ListTile(
                    leading: Icon(Icons.location_on, color: Colors.red.shade400),
                    title: Text(widget.bar.direccion),
                    trailing: widget.bar.latitud != null
                        ? IconButton(
                            icon: const Icon(Icons.directions),
                            onPressed: _abrirMapa,
                          )
                        : null,
                  ),
                if (widget.bar.telefono.isNotEmpty)
                  ListTile(
                    leading: Icon(Icons.phone, color: Colors.blue.shade400),
                    title: Text(widget.bar.telefono),
                    trailing: IconButton(
                      icon: const Icon(Icons.call),
                      onPressed: () => FlavorContactLauncher.call(context, widget.bar.telefono),
                    ),
                  ),
                if (widget.bar.email.isNotEmpty)
                  ListTile(
                    leading: Icon(Icons.email, color: Colors.orange.shade400),
                    title: Text(widget.bar.email),
                    trailing: IconButton(
                      icon: const Icon(Icons.send),
                      onPressed: () => FlavorContactLauncher.launchEmail(widget.bar.email),
                    ),
                  ),
                if (widget.bar.horario.isNotEmpty)
                  ListTile(
                    leading: Icon(Icons.access_time, color: Colors.green.shade400),
                    title: const Text('Horario'),
                    subtitle: Text(widget.bar.horario),
                  ),
              ],
            ),
          ),

          const SizedBox(height: 32),
        ],
      ),
    );
  }

  Widget _buildSeccionTitulo(String titulo, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(icon, color: Colors.amber.shade700, size: 22),
          const SizedBox(width: 8),
          Text(
            titulo,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.amber.shade900,
            ),
          ),
        ],
      ),
    );
  }

  IconData _getServiceIcon(String servicio) {
    switch (servicio.toLowerCase()) {
      case 'wifi':
        return Icons.wifi;
      case 'terraza':
        return Icons.deck;
      case 'musica':
        return Icons.music_note;
      case 'tv':
        return Icons.tv;
      case 'aparcamiento':
        return Icons.local_parking;
      case 'accesible':
        return Icons.accessible;
      case 'mascotas':
        return Icons.pets;
      default:
        return Icons.check_circle;
    }
  }

  // =========== TAB MENU/CARTA ===========

  Widget _buildMenuTab() {
    if (_cargandoMenu) {
      return const FlavorLoadingState();
    }

    if (_menuItems.isEmpty) {
      return const FlavorEmptyState(
        icon: Icons.restaurant_menu,
        title: 'Carta no disponible',
        message: 'Este bar no ha publicado su carta',
      );
    }

    // Obtener categorias unicas
    final categoriasUnicas = _menuItems.map((item) => item.categoria).toSet().toList();
    categoriasUnicas.sort();

    // Filtrar items por categoria
    final itemsFiltrados = _categoriaMenuSeleccionada.isEmpty
        ? _menuItems
        : _menuItems.where((item) => item.categoria == _categoriaMenuSeleccionada).toList();

    return Column(
      children: [
        // Filtro de categorias
        if (categoriasUnicas.length > 1)
          Container(
            height: 50,
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              children: [
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: const Text('Todos'),
                    selected: _categoriaMenuSeleccionada.isEmpty,
                    selectedColor: Colors.amber.shade200,
                    onSelected: (_) {
                      setState(() => _categoriaMenuSeleccionada = '');
                    },
                  ),
                ),
                ...categoriasUnicas.map((cat) {
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: FilterChip(
                      label: Text(cat.isNotEmpty ? cat[0].toUpperCase() + cat.substring(1) : cat),
                      selected: _categoriaMenuSeleccionada == cat,
                      selectedColor: Colors.amber.shade200,
                      onSelected: (_) {
                        setState(() => _categoriaMenuSeleccionada = cat);
                      },
                    ),
                  );
                }),
              ],
            ),
          ),

        // Lista de items
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: itemsFiltrados.length,
            itemBuilder: (context, index) {
              return _MenuItemCard(item: itemsFiltrados[index]);
            },
          ),
        ),
      ],
    );
  }

  // =========== TAB RESERVAS ===========

  Widget _buildReservasTab() {
    return _ReservaForm(
      barId: widget.bar.id,
      barNombre: widget.bar.nombre,
    );
  }

  Future<void> _abrirMapa() async {
    if (widget.bar.latitud == null || widget.bar.longitud == null) return;

    final url = MapLaunchHelper.buildConfiguredMapUri(
      widget.bar.latitud!,
      widget.bar.longitud!,
      query: widget.bar.nombre,
    );
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }
}

// =============================================================================
// TARJETA DE ITEM DEL MENU
// =============================================================================

class _MenuItemCard extends StatelessWidget {
  final _MenuItem item;

  const _MenuItemCard({required this.item});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            // Imagen o icono
            Container(
              width: 70,
              height: 70,
              decoration: BoxDecoration(
                color: Colors.amber.shade50,
                borderRadius: BorderRadius.circular(8),
              ),
              child: item.imagen.isNotEmpty
                  ? ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.network(
                        item.imagen,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => _buildPlaceholder(),
                      ),
                    )
                  : _buildPlaceholder(),
            ),

            const SizedBox(width: 12),

            // Info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          item.nombre,
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                            color: item.disponible ? null : Colors.grey,
                          ),
                        ),
                      ),
                      if (!item.disponible)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.red.shade100,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            'Agotado',
                            style: TextStyle(fontSize: 10, color: Colors.red.shade700),
                          ),
                        ),
                    ],
                  ),
                  if (item.descripcion.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      item.descripcion,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey.shade600,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Text(
                        '${item.precio.toStringAsFixed(2)} €',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.amber.shade800,
                        ),
                      ),
                      const Spacer(),
                      if (item.alergenos.isNotEmpty)
                        ...item.alergenos.take(3).map((alergeno) {
                          return Padding(
                            padding: const EdgeInsets.only(left: 4),
                            child: Tooltip(
                              message: alergeno,
                              child: CircleAvatar(
                                radius: 10,
                                backgroundColor: Colors.orange.shade100,
                                child: Text(
                                  alergeno[0].toUpperCase(),
                                  style: TextStyle(
                                    fontSize: 10,
                                    color: Colors.orange.shade800,
                                  ),
                                ),
                              ),
                            ),
                          );
                        }),
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
    return Icon(
      _getIconForCategory(item.categoria),
      size: 32,
      color: Colors.amber.shade300,
    );
  }

  IconData _getIconForCategory(String categoria) {
    switch (categoria.toLowerCase()) {
      case 'bebidas':
      case 'drinks':
        return Icons.local_drink;
      case 'cervezas':
      case 'beers':
        return Icons.sports_bar;
      case 'vinos':
      case 'wines':
        return Icons.wine_bar;
      case 'cocteles':
      case 'cocktails':
        return Icons.local_bar;
      case 'tapas':
        return Icons.tapas;
      case 'raciones':
        return Icons.restaurant;
      case 'postres':
      case 'desserts':
        return Icons.cake;
      case 'cafes':
      case 'coffee':
        return Icons.coffee;
      default:
        return Icons.restaurant_menu;
    }
  }
}

// =============================================================================
// FORMULARIO DE RESERVA
// =============================================================================

class _ReservaForm extends ConsumerStatefulWidget {
  final int barId;
  final String barNombre;

  const _ReservaForm({required this.barId, required this.barNombre});

  @override
  ConsumerState<_ReservaForm> createState() => _ReservaFormState();
}

class _ReservaFormState extends ConsumerState<_ReservaForm> {
  final _formKey = GlobalKey<FormState>();
  final _nombreController = TextEditingController();
  final _telefonoController = TextEditingController();
  final _notasController = TextEditingController();

  DateTime _fechaSeleccionada = DateTime.now().add(const Duration(days: 1));
  TimeOfDay _horaSeleccionada = const TimeOfDay(hour: 20, minute: 0);
  int _numPersonas = 2;
  bool _enviando = false;

  @override
  void dispose() {
    _nombreController.dispose();
    _telefonoController.dispose();
    _notasController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Info
          Card(
            color: Colors.amber.shade50,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Icon(Icons.info_outline, color: Colors.amber.shade800),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Reserva tu mesa en ${widget.barNombre}',
                      style: TextStyle(color: Colors.amber.shade900),
                    ),
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 24),

          // Fecha
          ListTile(
            leading: Icon(Icons.calendar_today, color: Colors.amber.shade700),
            title: const Text('Fecha'),
            subtitle: Text(_formatFecha(_fechaSeleccionada)),
            trailing: const Icon(Icons.chevron_right),
            onTap: _seleccionarFecha,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(8),
              side: BorderSide(color: Colors.grey.shade300),
            ),
          ),

          const SizedBox(height: 12),

          // Hora
          ListTile(
            leading: Icon(Icons.access_time, color: Colors.amber.shade700),
            title: const Text('Hora'),
            subtitle: Text(_horaSeleccionada.format(context)),
            trailing: const Icon(Icons.chevron_right),
            onTap: _seleccionarHora,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(8),
              side: BorderSide(color: Colors.grey.shade300),
            ),
          ),

          const SizedBox(height: 12),

          // Numero de personas
          ListTile(
            leading: Icon(Icons.people, color: Colors.amber.shade700),
            title: const Text('Personas'),
            subtitle: Text('$_numPersonas personas'),
            trailing: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                IconButton(
                  icon: const Icon(Icons.remove_circle_outline),
                  onPressed: _numPersonas > 1
                      ? () => setState(() => _numPersonas--)
                      : null,
                ),
                Text(
                  '$_numPersonas',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                IconButton(
                  icon: const Icon(Icons.add_circle_outline),
                  onPressed: _numPersonas < 20
                      ? () => setState(() => _numPersonas++)
                      : null,
                ),
              ],
            ),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(8),
              side: BorderSide(color: Colors.grey.shade300),
            ),
          ),

          const SizedBox(height: 24),

          // Nombre
          TextFormField(
            controller: _nombreController,
            decoration: const InputDecoration(
              labelText: 'Nombre *',
              prefixIcon: Icon(Icons.person),
              border: OutlineInputBorder(),
            ),
            validator: (v) => v == null || v.isEmpty ? 'El nombre es obligatorio' : null,
          ),

          const SizedBox(height: 16),

          // Telefono
          TextFormField(
            controller: _telefonoController,
            decoration: const InputDecoration(
              labelText: 'Telefono *',
              prefixIcon: Icon(Icons.phone),
              border: OutlineInputBorder(),
            ),
            keyboardType: TextInputType.phone,
            validator: (v) => v == null || v.isEmpty ? 'El telefono es obligatorio' : null,
          ),

          const SizedBox(height: 16),

          // Notas
          TextFormField(
            controller: _notasController,
            decoration: const InputDecoration(
              labelText: 'Notas adicionales',
              prefixIcon: Icon(Icons.note),
              border: OutlineInputBorder(),
              hintText: 'Alergias, preferencias, ocasion especial...',
            ),
            maxLines: 3,
          ),

          const SizedBox(height: 32),

          // Boton reservar
          SizedBox(
            height: 50,
            child: ElevatedButton.icon(
              onPressed: _enviando ? null : _enviarReserva,
              icon: _enviando
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.check_circle),
              label: Text(_enviando ? 'Enviando...' : 'Confirmar reserva'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.amber.shade800,
                foregroundColor: Colors.white,
              ),
            ),
          ),

          const SizedBox(height: 16),
        ],
      ),
    );
  }

  String _formatFecha(DateTime fecha) {
    final dias = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
    final meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return '${dias[fecha.weekday - 1]}, ${fecha.day} ${meses[fecha.month - 1]} ${fecha.year}';
  }

  Future<void> _seleccionarFecha() async {
    final fecha = await showDatePicker(
      context: context,
      initialDate: _fechaSeleccionada,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 60)),
    );
    if (fecha != null) {
      setState(() => _fechaSeleccionada = fecha);
    }
  }

  Future<void> _seleccionarHora() async {
    final hora = await showTimePicker(
      context: context,
      initialTime: _horaSeleccionada,
    );
    if (hora != null) {
      setState(() => _horaSeleccionada = hora);
    }
  }

  Future<void> _enviarReserva() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _enviando = true);

    try {
      final api = ref.read(apiClientProvider);
      final fechaHora = DateTime(
        _fechaSeleccionada.year,
        _fechaSeleccionada.month,
        _fechaSeleccionada.day,
        _horaSeleccionada.hour,
        _horaSeleccionada.minute,
      );

      final response = await api.post(
        '/bares/${widget.barId}/reservas',
        data: {
          'nombre': _nombreController.text,
          'telefono': _telefonoController.text,
          'fecha': fechaHora.toIso8601String(),
          'personas': _numPersonas,
          'notas': _notasController.text,
        },
      );

      if (response.success) {
        if (mounted) {
          FlavorSnackbar.showSuccess(context, 'Reserva confirmada');
          Navigator.pop(context, true);
        }
      } else {
        if (mounted) {
          FlavorSnackbar.showError(context, response.error ?? 'Error al reservar');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error de conexion');
      }
    } finally {
      if (mounted) {
        setState(() => _enviando = false);
      }
    }
  }
}

// =============================================================================
// DELEGATE PARA TAB BAR STICKY
// =============================================================================

class _SliverTabBarDelegate extends SliverPersistentHeaderDelegate {
  final TabBar tabBar;

  _SliverTabBarDelegate(this.tabBar);

  @override
  double get minExtent => tabBar.preferredSize.height;

  @override
  double get maxExtent => tabBar.preferredSize.height;

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return Container(
      color: Theme.of(context).scaffoldBackgroundColor,
      child: tabBar,
    );
  }

  @override
  bool shouldRebuild(_SliverTabBarDelegate oldDelegate) {
    return tabBar != oldDelegate.tabBar;
  }
}
