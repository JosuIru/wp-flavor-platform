part of 'empresas_screen.dart';

// =============================================================================
// TARJETA DE EMPRESA (Directorio)
// =============================================================================

class _EmpresaCard extends StatelessWidget {
  final dynamic empresa;
  final VoidCallback onTap;

  const _EmpresaCard({
    required this.empresa,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final nombre = empresa['nombre'] ?? 'Sin nombre';
    final tipo = empresa['tipo'] ?? '';
    final sector = empresa['sector'] ?? '';
    final ciudad = empresa['ciudad'] ?? '';
    final descripcion = empresa['descripcion'] ?? '';
    final logoUrl = empresa['logo_url'];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Logo o avatar
              Container(
                width: 60,
                height: 60,
                decoration: BoxDecoration(
                  color: _getColorPorTipo(tipo),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: logoUrl != null && logoUrl.toString().isNotEmpty
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.network(
                          logoUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) => Center(
                            child: Text(
                              nombre.isNotEmpty ? nombre[0].toUpperCase() : 'E',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ),
                      )
                    : Center(
                        child: Text(
                          nombre.isNotEmpty ? nombre[0].toUpperCase() : 'E',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
              ),
              const SizedBox(width: 16),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      nombre,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        _TipoBadge(tipo: tipo),
                        if (sector.isNotEmpty) ...[
                          const SizedBox(width: 8),
                          _SectorBadge(sector: sector),
                        ],
                      ],
                    ),
                    if (ciudad.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.location_on,
                              size: 14, color: Colors.grey[500]),
                          const SizedBox(width: 4),
                          Text(
                            ciudad,
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
              Icon(Icons.chevron_right, color: Colors.grey[400]),
            ],
          ),
        ),
      ),
    );
  }
}

// =============================================================================
// TARJETA MI EMPRESA
// =============================================================================

class _MiEmpresaCard extends StatelessWidget {
  final dynamic empresa;
  final VoidCallback onTap;

  const _MiEmpresaCard({
    required this.empresa,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final nombre = empresa['nombre'] ?? 'Sin nombre';
    final tipo = empresa['tipo'] ?? '';
    final rol = empresa['rol'] ?? '';
    final cargo = empresa['cargo'] ?? '';
    final logoUrl = empresa['logo_url'];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Column(
          children: [
            // Header con color de rol
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: _getColorPorRol(rol).withOpacity(0.1),
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(12),
                ),
              ),
              child: Row(
                children: [
                  // Logo
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      color: _getColorPorTipo(tipo),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: logoUrl != null && logoUrl.toString().isNotEmpty
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: Image.network(
                              logoUrl,
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) =>
                                  Center(
                                child: Text(
                                  nombre.isNotEmpty
                                      ? nombre[0].toUpperCase()
                                      : 'E',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ),
                          )
                        : Center(
                            child: Text(
                              nombre.isNotEmpty ? nombre[0].toUpperCase() : 'E',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          nombre,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        _TipoBadge(tipo: tipo),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            // Info de rol
            Container(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Icon(Icons.badge, color: _getColorPorRol(rol), size: 20),
                  const SizedBox(width: 8),
                  Text(
                    _getNombreRol(rol),
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: _getColorPorRol(rol),
                    ),
                  ),
                  if (cargo.isNotEmpty) ...[
                    const SizedBox(width: 8),
                    Text(
                      '• $cargo',
                      style: TextStyle(
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                  const Spacer(),
                  Icon(Icons.chevron_right, color: Colors.grey[400]),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// BADGES
// =============================================================================

class _TipoBadge extends StatelessWidget {
  final String tipo;

  const _TipoBadge({required this.tipo});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: _getColorPorTipo(tipo).withOpacity(0.15),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        _getNombreTipo(tipo),
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: _getColorPorTipo(tipo),
        ),
      ),
    );
  }
}

class _SectorBadge extends StatelessWidget {
  final String sector;

  const _SectorBadge({required this.sector});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        _formatearSector(sector),
        style: TextStyle(
          fontSize: 11,
          color: Colors.grey[700],
        ),
      ),
    );
  }
}

// =============================================================================
// PANTALLA DETALLE EMPRESA
// =============================================================================

class _EmpresaDetalleScreen extends ConsumerStatefulWidget {
  final int empresaId;
  final String empresaNombre;

  const _EmpresaDetalleScreen({
    required this.empresaId,
    required this.empresaNombre,
  });

  @override
  ConsumerState<_EmpresaDetalleScreen> createState() =>
      _EmpresaDetalleScreenState();
}

class _EmpresaDetalleScreenState extends ConsumerState<_EmpresaDetalleScreen> {
  Map<String, dynamic>? _empresa;
  List<dynamic> _miembros = [];
  Map<String, dynamic>? _estadisticas;

  bool _cargando = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargarDatos();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);

      // Cargar empresa
      final empresaResponse = await apiClient.get(
        '/flavor/v1/empresas/${widget.empresaId}',
      );

      if (empresaResponse != null && empresaResponse['data'] != null) {
        setState(() {
          _empresa = empresaResponse['data'] as Map<String, dynamic>;
        });

        // Intentar cargar miembros (puede fallar si no tenemos acceso)
        try {
          final miembrosResponse = await apiClient.get(
            '/flavor/v1/empresas/${widget.empresaId}/miembros',
          );
          if (miembrosResponse != null && miembrosResponse['data'] != null) {
            setState(() {
              _miembros = miembrosResponse['data'] as List<dynamic>;
            });
          }
        } catch (e) {
          // Ignorar error de miembros
        }

        // Intentar cargar estadísticas
        try {
          final statsResponse = await apiClient.get(
            '/flavor/v1/empresas/${widget.empresaId}/estadisticas',
          );
          if (statsResponse != null && statsResponse['data'] != null) {
            setState(() {
              _estadisticas = statsResponse['data'] as Map<String, dynamic>;
            });
          }
        } catch (e) {
          // Ignorar error de estadísticas
        }

        setState(() {
          _cargando = false;
        });
      } else {
        setState(() {
          _error = 'No se pudo cargar la empresa';
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

  @override
  Widget build(BuildContext context) {
    if (_cargando) {
      return Scaffold(
        appBar: AppBar(title: Text(widget.empresaNombre)),
        body: const FlavorLoadingWidget(message: 'Cargando empresa...'),
      );
    }

    if (_error != null || _empresa == null) {
      return Scaffold(
        appBar: AppBar(title: Text(widget.empresaNombre)),
        body: FlavorErrorWidget(
          message: _error ?? 'Error desconocido',
          onRetry: _cargarDatos,
        ),
      );
    }

    final nombre = _empresa!['nombre'] ?? '';
    final razonSocial = _empresa!['razon_social'] ?? '';
    final tipo = _empresa!['tipo'] ?? '';
    final sector = _empresa!['sector'] ?? '';
    final descripcion = _empresa!['descripcion'] ?? '';
    final email = _empresa!['email'] ?? '';
    final telefono = _empresa!['telefono'] ?? '';
    final web = _empresa!['web'] ?? '';
    final direccion = _empresa!['direccion'] ?? '';
    final ciudad = _empresa!['ciudad'] ?? '';
    final provincia = _empresa!['provincia'] ?? '';
    final logoUrl = _empresa!['logo_url'];

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // App Bar con logo
          SliverAppBar(
            expandedHeight: 180,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(
                nombre,
                style: const TextStyle(fontSize: 16),
              ),
              background: Container(
                color: _getColorPorTipo(tipo),
                child: Center(
                  child: logoUrl != null && logoUrl.toString().isNotEmpty
                      ? Image.network(
                          logoUrl,
                          width: 80,
                          height: 80,
                          errorBuilder: (context, error, stackTrace) =>
                              _buildLogoPlaceholder(nombre),
                        )
                      : _buildLogoPlaceholder(nombre),
                ),
              ),
            ),
          ),
          // Contenido
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Badges
                  Row(
                    children: [
                      _TipoBadge(tipo: tipo),
                      if (sector.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        _SectorBadge(sector: sector),
                      ],
                    ],
                  ),

                  // Razón social
                  if (razonSocial.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    Text(
                      razonSocial,
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],

                  // Descripción
                  if (descripcion.isNotEmpty) ...[
                    const SizedBox(height: 20),
                    const Text(
                      'Acerca de',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      descripcion,
                      style: const TextStyle(
                        fontSize: 14,
                        height: 1.5,
                      ),
                    ),
                  ],

                  // Estadísticas
                  if (_estadisticas != null) ...[
                    const SizedBox(height: 24),
                    _buildEstadisticas(),
                  ],

                  // Contacto
                  const SizedBox(height: 24),
                  const Text(
                    'Contacto',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _buildContactoCard(
                    email: email,
                    telefono: telefono,
                    web: web,
                    direccion: direccion,
                    ciudad: ciudad,
                    provincia: provincia,
                  ),

                  // Miembros
                  if (_miembros.isNotEmpty) ...[
                    const SizedBox(height: 24),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Equipo',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          '${_miembros.length} miembros',
                          style: TextStyle(
                            color: Colors.grey[600],
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    ...(_miembros.take(5).map((m) => _MiembroItem(miembro: m))),
                    if (_miembros.length > 5) ...[
                      const SizedBox(height: 8),
                      Center(
                        child: TextButton(
                          onPressed: () => _verTodosMiembros(),
                          child: Text('Ver todos (${_miembros.length})'),
                        ),
                      ),
                    ],
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLogoPlaceholder(String nombre) {
    return Container(
      width: 80,
      height: 80,
      decoration: BoxDecoration(
        color: Colors.white24,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Center(
        child: Text(
          nombre.isNotEmpty ? nombre[0].toUpperCase() : 'E',
          style: const TextStyle(
            color: Colors.white,
            fontSize: 36,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _buildEstadisticas() {
    final miembrosActivos = _estadisticas!['miembros_activos'] ?? 0;
    final documentos = _estadisticas!['documentos'] ?? 0;
    final ingresosMes = _estadisticas!['ingresos_mes'] ?? 0;
    final gastosMes = _estadisticas!['gastos_mes'] ?? 0;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _StatItem(
            icono: Icons.people,
            valor: miembrosActivos.toString(),
            label: 'Miembros',
            color: Colors.blue,
          ),
          _StatItem(
            icono: Icons.folder,
            valor: documentos.toString(),
            label: 'Documentos',
            color: Colors.orange,
          ),
          if (ingresosMes > 0 || gastosMes > 0)
            _StatItem(
              icono: Icons.trending_up,
              valor: '${(ingresosMes - gastosMes).toStringAsFixed(0)}€',
              label: 'Balance',
              color: (ingresosMes - gastosMes) >= 0 ? Colors.green : Colors.red,
            ),
        ],
      ),
    );
  }

  Widget _buildContactoCard({
    required String email,
    required String telefono,
    required String web,
    required String direccion,
    required String ciudad,
    required String provincia,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            if (email.isNotEmpty)
              _ContactoItem(
                icono: Icons.email,
                texto: email,
                onTap: () => _abrirEmail(email),
              ),
            if (telefono.isNotEmpty)
              _ContactoItem(
                icono: Icons.phone,
                texto: telefono,
                onTap: () => _llamar(telefono),
              ),
            if (web.isNotEmpty)
              _ContactoItem(
                icono: Icons.language,
                texto: web,
                onTap: () => _abrirWeb(web),
              ),
            if (direccion.isNotEmpty || ciudad.isNotEmpty)
              _ContactoItem(
                icono: Icons.location_on,
                texto: [direccion, ciudad, provincia]
                    .where((s) => s.isNotEmpty)
                    .join(', '),
                onTap: () => _abrirMapa(direccion, ciudad),
              ),
          ],
        ),
      ),
    );
  }

  void _verTodosMiembros() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        maxChildSize: 0.95,
        minChildSize: 0.5,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Equipo (${_miembros.length})',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView.builder(
                controller: scrollController,
                itemCount: _miembros.length,
                itemBuilder: (context, index) =>
                    _MiembroItem(miembro: _miembros[index]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _abrirEmail(String email) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Email: $email')),
    );
  }

  void _llamar(String telefono) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Llamar: $telefono')),
    );
  }

  void _abrirWeb(String web) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Web: $web')),
    );
  }

  void _abrirMapa(String direccion, String ciudad) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Mapa: $direccion, $ciudad')),
    );
  }
}

// =============================================================================
// WIDGETS AUXILIARES
// =============================================================================

class _StatItem extends StatelessWidget {
  final IconData icono;
  final String valor;
  final String label;
  final Color color;

  const _StatItem({
    required this.icono,
    required this.valor,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icono, color: color, size: 24),
        const SizedBox(height: 4),
        Text(
          valor,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey[600],
          ),
        ),
      ],
    );
  }
}

class _ContactoItem extends StatelessWidget {
  final IconData icono;
  final String texto;
  final VoidCallback? onTap;

  const _ContactoItem({
    required this.icono,
    required this.texto,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          children: [
            Icon(icono, size: 20, color: Colors.grey[600]),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                texto,
                style: const TextStyle(fontSize: 14),
              ),
            ),
            if (onTap != null)
              Icon(Icons.open_in_new, size: 16, color: Colors.grey[400]),
          ],
        ),
      ),
    );
  }
}

class _MiembroItem extends StatelessWidget {
  final dynamic miembro;

  const _MiembroItem({required this.miembro});

  @override
  Widget build(BuildContext context) {
    final nombre = miembro['nombre'] ?? miembro['display_name'] ?? 'Miembro';
    final rol = miembro['rol'] ?? '';
    final cargo = miembro['cargo'] ?? '';

    return ListTile(
      leading: CircleAvatar(
        backgroundColor: _getColorPorRol(rol),
        child: Text(
          nombre.isNotEmpty ? nombre[0].toUpperCase() : 'M',
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      title: Text(nombre),
      subtitle: Text(
        cargo.isNotEmpty ? '$cargo • ${_getNombreRol(rol)}' : _getNombreRol(rol),
        style: TextStyle(color: Colors.grey[600]),
      ),
      trailing: Icon(
        _getIconoPorRol(rol),
        color: _getColorPorRol(rol),
        size: 20,
      ),
    );
  }
}

// =============================================================================
// BUSCADOR
// =============================================================================

class _EmpresaBusquedaDelegate extends SearchDelegate<dynamic> {
  final List<dynamic> empresas;

  _EmpresaBusquedaDelegate({required this.empresas});

  @override
  String get searchFieldLabel => 'Buscar empresa...';

  @override
  List<Widget> buildActions(BuildContext context) {
    return [
      IconButton(
        icon: const Icon(Icons.clear),
        onPressed: () => query = '',
      ),
    ];
  }

  @override
  Widget buildLeading(BuildContext context) {
    return IconButton(
      icon: const Icon(Icons.arrow_back),
      onPressed: () => close(context, null),
    );
  }

  @override
  Widget buildResults(BuildContext context) => _buildResultados();

  @override
  Widget buildSuggestions(BuildContext context) {
    if (query.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.search, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'Busca por nombre o sector',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
        ),
      );
    }
    return _buildResultados();
  }

  Widget _buildResultados() {
    final queryLower = query.toLowerCase();
    final resultados = empresas.where((e) {
      final nombre = (e['nombre'] ?? '').toString().toLowerCase();
      final sector = (e['sector'] ?? '').toString().toLowerCase();
      final ciudad = (e['ciudad'] ?? '').toString().toLowerCase();
      return nombre.contains(queryLower) ||
          sector.contains(queryLower) ||
          ciudad.contains(queryLower);
    }).toList();

    if (resultados.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.search_off, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              'No se encontraron empresas',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: resultados.length,
      itemBuilder: (context, index) {
        final empresa = resultados[index];
        return _EmpresaCard(
          empresa: empresa,
          onTap: () => close(context, empresa),
        );
      },
    );
  }
}

// =============================================================================
// UTILIDADES
// =============================================================================

Color _getColorPorTipo(String tipo) {
  switch (tipo.toLowerCase()) {
    case 'sl':
      return Colors.blue;
    case 'sa':
      return Colors.indigo;
    case 'autonomo':
      return Colors.orange;
    case 'cooperativa':
      return Colors.green;
    case 'asociacion':
      return Colors.purple;
    case 'comunidad_bienes':
      return Colors.teal;
    case 'sociedad_civil':
      return Colors.cyan;
    default:
      return Colors.grey;
  }
}

String _getNombreTipo(String tipo) {
  switch (tipo.toLowerCase()) {
    case 'sl':
      return 'S.L.';
    case 'sa':
      return 'S.A.';
    case 'autonomo':
      return 'Autónomo';
    case 'cooperativa':
      return 'Cooperativa';
    case 'asociacion':
      return 'Asociación';
    case 'comunidad_bienes':
      return 'C.B.';
    case 'sociedad_civil':
      return 'S.C.';
    default:
      return tipo.toUpperCase();
  }
}

String _formatearSector(String sector) {
  return sector
      .replaceAll('_', ' ')
      .split(' ')
      .map((word) =>
          word.isNotEmpty ? '${word[0].toUpperCase()}${word.substring(1)}' : '')
      .join(' ');
}

Color _getColorPorRol(String rol) {
  switch (rol.toLowerCase()) {
    case 'admin':
      return Colors.red;
    case 'contable':
      return Colors.blue;
    case 'empleado':
      return Colors.green;
    case 'colaborador':
      return Colors.orange;
    case 'observador':
      return Colors.grey;
    default:
      return Colors.grey;
  }
}

String _getNombreRol(String rol) {
  switch (rol.toLowerCase()) {
    case 'admin':
      return 'Administrador';
    case 'contable':
      return 'Contable';
    case 'empleado':
      return 'Empleado';
    case 'colaborador':
      return 'Colaborador';
    case 'observador':
      return 'Observador';
    default:
      return rol;
  }
}

IconData _getIconoPorRol(String rol) {
  switch (rol.toLowerCase()) {
    case 'admin':
      return Icons.admin_panel_settings;
    case 'contable':
      return Icons.calculate;
    case 'empleado':
      return Icons.work;
    case 'colaborador':
      return Icons.handshake;
    case 'observador':
      return Icons.visibility;
    default:
      return Icons.person;
  }
}
