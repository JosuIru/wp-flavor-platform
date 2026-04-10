import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'mapa_actores_screen_parts.dart';

class MapaActoresScreen extends ConsumerStatefulWidget {
  const MapaActoresScreen({super.key});

  @override
  ConsumerState<MapaActoresScreen> createState() => _MapaActoresScreenState();
}

class _MapaActoresScreenState extends ConsumerState<MapaActoresScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> _actores = [];
  Map<String, dynamic>? _estadisticas;
  Map<String, dynamic>? _tipos;
  bool _cargandoActores = true;
  bool _cargandoEstadisticas = true;
  bool _cargandoTipos = true;
  String _filtroTipo = '';
  String _filtroPosicion = '';
  String _filtroAmbito = '';
  String _textoBusqueda = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _cargarDatos();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarDatos() async {
    _cargarTipos();
    _cargarActores();
    _cargarEstadisticas();
  }

  Future<void> _cargarTipos() async {
    setState(() => _cargandoTipos = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/actores/tipos');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _tipos = respuesta.data;
        });
      }
    } finally {
      setState(() => _cargandoTipos = false);
    }
  }

  Future<void> _cargarActores() async {
    setState(() => _cargandoActores = true);
    try {
      final api = ref.read(apiClientProvider);
      final queryParams = <String, dynamic>{
        'limite': 50,
        if (_filtroTipo.isNotEmpty) 'tipo': _filtroTipo,
        if (_filtroPosicion.isNotEmpty) 'posicion': _filtroPosicion,
        if (_filtroAmbito.isNotEmpty) 'ambito': _filtroAmbito,
      };

      String endpoint = '/actores';
      if (_textoBusqueda.isNotEmpty) {
        endpoint = '/actores/buscar';
        queryParams['q'] = _textoBusqueda;
      }

      final respuesta = await api.get(endpoint, queryParameters: queryParams);
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _actores = respuesta.data!['actores'] ?? [];
        });
      }
    } finally {
      setState(() => _cargandoActores = false);
    }
  }

  Future<void> _cargarEstadisticas() async {
    setState(() => _cargandoEstadisticas = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/actores/estadisticas');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _estadisticas = respuesta.data;
        });
      }
    } finally {
      setState(() => _cargandoEstadisticas = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mapa de Actores'),
        backgroundColor: Colors.teal.shade700,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: _mostrarBusqueda,
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(icon: Icon(Icons.people), text: 'Actores'),
            Tab(icon: Icon(Icons.analytics), text: 'Estadísticas'),
            Tab(icon: Icon(Icons.hub), text: 'Por Tipo'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildActoresTab(),
          _buildEstadisticasTab(),
          _buildPorTipoTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearActor,
        icon: const Icon(Icons.add),
        label: const Text('Nuevo actor'),
        backgroundColor: Colors.teal.shade700,
        foregroundColor: Colors.white,
      ),
    );
  }

  Widget _buildActoresTab() {
    return Column(
      children: [
        _buildFiltros(),
        if (_textoBusqueda.isNotEmpty)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            color: Colors.teal.shade50,
            child: Row(
              children: [
                Icon(Icons.search, size: 16, color: Colors.teal.shade700),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Buscando: "$_textoBusqueda"',
                    style: TextStyle(color: Colors.teal.shade700),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 18),
                  onPressed: () {
                    setState(() => _textoBusqueda = '');
                    _cargarActores();
                  },
                ),
              ],
            ),
          ),
        Expanded(
          child: _cargandoActores
              ? const FlavorLoadingState()
              : _actores.isEmpty
                  ? FlavorEmptyState(
                      icon: Icons.people_outline,
                      title: 'No hay actores',
                      message: _filtroTipo.isNotEmpty ||
                              _filtroPosicion.isNotEmpty ||
                              _textoBusqueda.isNotEmpty
                          ? 'Prueba con otros filtros'
                          : null,
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarActores,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _actores.length,
                        itemBuilder: (context, index) => _ActorCard(
                          actor: _actores[index],
                          onTap: () => _abrirDetalle(_actores[index]),
                        ),
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildFiltros() {
    final posiciones = [
      {'id': '', 'label': 'Todos'},
      {'id': 'aliado', 'label': 'Aliados'},
      {'id': 'neutro', 'label': 'Neutros'},
      {'id': 'opositor', 'label': 'Opositores'},
    ];

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Column(
        children: [
          // Filtro por posición
          SizedBox(
            height: 40,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              itemCount: posiciones.length,
              itemBuilder: (context, index) {
                final pos = posiciones[index];
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(pos['label']!),
                    selected: _filtroPosicion == pos['id'],
                    selectedColor: _getColorPosicion(pos['id']!).withOpacity(0.2),
                    onSelected: (_) {
                      setState(() => _filtroPosicion = pos['id']!);
                      _cargarActores();
                    },
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 4),
          // Filtro por tipo (si hay tipos cargados)
          if (_tipos != null && _tipos!['tipos'] != null)
            SizedBox(
              height: 40,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                children: [
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: FilterChip(
                      label: const Text('Todos'),
                      selected: _filtroTipo.isEmpty,
                      selectedColor: Colors.teal.shade100,
                      onSelected: (_) {
                        setState(() => _filtroTipo = '');
                        _cargarActores();
                      },
                    ),
                  ),
                  ...(_tipos!['tipos'] as Map<String, dynamic>)
                      .entries
                      .take(6)
                      .map((entry) => Padding(
                            padding: const EdgeInsets.only(right: 8),
                            child: FilterChip(
                              label: Text(entry.value),
                              selected: _filtroTipo == entry.key,
                              selectedColor: Colors.teal.shade100,
                              onSelected: (_) {
                                setState(() => _filtroTipo = entry.key);
                                _cargarActores();
                              },
                            ),
                          )),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildEstadisticasTab() {
    if (_cargandoEstadisticas) {
      return const FlavorLoadingState();
    }

    if (_estadisticas == null) {
      return const FlavorEmptyState(
        icon: Icons.analytics,
        title: 'Sin estadísticas',
      );
    }

    final stats = _estadisticas!['estadisticas'] as Map<String, dynamic>?;
    final porTipo = _estadisticas!['por_tipo'] as List<dynamic>? ?? [];
    final porAmbito = _estadisticas!['por_ambito'] as List<dynamic>? ?? [];

    return RefreshIndicator(
      onRefresh: _cargarEstadisticas,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Resumen
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Resumen',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      _buildStatCard(
                        'Total',
                        '${stats?['total'] ?? 0}',
                        Colors.teal,
                        Icons.people,
                      ),
                      const SizedBox(width: 12),
                      _buildStatCard(
                        'Verificados',
                        '${stats?['verificados'] ?? 0}',
                        Colors.green,
                        Icons.verified,
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      _buildStatCard(
                        'Aliados',
                        '${stats?['aliados'] ?? 0}',
                        Colors.blue,
                        Icons.handshake,
                      ),
                      const SizedBox(width: 12),
                      _buildStatCard(
                        'Opositores',
                        '${stats?['opositores'] ?? 0}',
                        Colors.red,
                        Icons.gpp_bad,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Relaciones e interacciones
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      children: [
                        Icon(Icons.link, color: Colors.purple, size: 32),
                        const SizedBox(height: 8),
                        Text(
                          '${_estadisticas!['total_relaciones'] ?? 0}',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 20,
                          ),
                        ),
                        const Text('Relaciones'),
                      ],
                    ),
                  ),
                  Container(
                    width: 1,
                    height: 60,
                    color: Colors.grey.shade300,
                  ),
                  Expanded(
                    child: Column(
                      children: [
                        Icon(Icons.event, color: Colors.orange, size: 32),
                        const SizedBox(height: 8),
                        Text(
                          '${_estadisticas!['total_interacciones'] ?? 0}',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 20,
                          ),
                        ),
                        const Text('Interacciones'),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Por tipo
          if (porTipo.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Por Tipo',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 12),
                    ...porTipo.map((item) {
                      final tipoLabel =
                          _tipos?['tipos']?[item['tipo']] ?? item['tipo'];
                      return _buildTipoBar(
                        tipoLabel,
                        int.tryParse('${item['total']}') ?? 0,
                        _getColorTipo(item['tipo'] ?? ''),
                      );
                    }),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 16),

          // Por ámbito
          if (porAmbito.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Por Ámbito',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 12),
                    ...porAmbito.map((item) {
                      final ambitoLabel =
                          _tipos?['ambitos']?[item['ambito']] ?? item['ambito'];
                      return _buildTipoBar(
                        ambitoLabel,
                        int.tryParse('${item['total']}') ?? 0,
                        Colors.teal,
                      );
                    }),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildPorTipoTab() {
    if (_cargandoTipos || _tipos == null) {
      return const FlavorLoadingState();
    }

    final tipos = _tipos!['tipos'] as Map<String, dynamic>? ?? {};

    return RefreshIndicator(
      onRefresh: _cargarTipos,
      child: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          childAspectRatio: 1.3,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
        ),
        itemCount: tipos.length,
        itemBuilder: (context, index) {
          final entry = tipos.entries.elementAt(index);
          return _TipoCard(
            tipoId: entry.key,
            nombre: entry.value,
            color: _getColorTipo(entry.key),
            icono: _getIconoTipo(entry.key),
            onTap: () {
              setState(() {
                _filtroTipo = entry.key;
                _tabController.animateTo(0);
              });
              _cargarActores();
            },
          );
        },
      ),
    );
  }

  Widget _buildStatCard(
      String label, String value, Color color, IconData icon) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 28),
            const SizedBox(height: 8),
            Text(
              value,
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            Text(
              label,
              style: TextStyle(color: color, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTipoBar(String label, int valor, Color color) {
    final total = int.tryParse(
            '${_estadisticas?['estadisticas']?['total']}') ??
        1;
    final porcentaje = total > 0 ? valor / total : 0.0;

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(child: Text(label, overflow: TextOverflow.ellipsis)),
              Text('$valor'),
            ],
          ),
          const SizedBox(height: 4),
          LinearProgressIndicator(
            value: porcentaje,
            backgroundColor: Colors.grey.shade200,
            valueColor: AlwaysStoppedAnimation(color),
          ),
        ],
      ),
    );
  }

  Color _getColorPosicion(String posicion) {
    switch (posicion) {
      case 'aliado':
        return Colors.blue;
      case 'neutro':
        return Colors.grey;
      case 'opositor':
        return Colors.red;
      default:
        return Colors.teal;
    }
  }

  Color _getColorTipo(String tipo) {
    switch (tipo) {
      case 'administracion_publica':
        return Colors.blue.shade700;
      case 'empresa':
        return Colors.green.shade700;
      case 'institucion':
        return Colors.purple.shade700;
      case 'medio_comunicacion':
        return Colors.orange.shade700;
      case 'partido_politico':
        return Colors.red.shade700;
      case 'sindicato':
        return Colors.amber.shade700;
      case 'ong':
        return Colors.teal.shade700;
      case 'colectivo':
        return Colors.indigo.shade700;
      case 'persona':
        return Colors.brown.shade700;
      default:
        return Colors.grey.shade700;
    }
  }

  IconData _getIconoTipo(String tipo) {
    switch (tipo) {
      case 'administracion_publica':
        return Icons.account_balance;
      case 'empresa':
        return Icons.business;
      case 'institucion':
        return Icons.school;
      case 'medio_comunicacion':
        return Icons.newspaper;
      case 'partido_politico':
        return Icons.how_to_vote;
      case 'sindicato':
        return Icons.groups;
      case 'ong':
        return Icons.volunteer_activism;
      case 'colectivo':
        return Icons.diversity_3;
      case 'persona':
        return Icons.person;
      default:
        return Icons.category;
    }
  }

  void _mostrarBusqueda() {
    showSearch(
      context: context,
      delegate: _ActorBusquedaDelegate(
        onBuscar: (query) {
          setState(() => _textoBusqueda = query);
          _cargarActores();
          _tabController.animateTo(0);
        },
      ),
    );
  }

  void _abrirDetalle(dynamic actor) {
    final mapa = actor as Map<String, dynamic>;
    final actorId = mapa['id'];
    if (actorId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => _ActorDetalleScreen(actorId: actorId),
        ),
      );
    }
  }

  void _crearActor() {
    Navigator.of(context)
        .push(
          MaterialPageRoute(
            builder: (_) => _CrearActorScreen(tipos: _tipos),
          ),
        )
        .then((creado) {
      if (creado == true) {
        FlavorSnackbar.show(
          context,
          'Actor creado correctamente',
          type: SnackbarType.success,
        );
        _cargarActores();
        _cargarEstadisticas();
      }
    });
  }
}
