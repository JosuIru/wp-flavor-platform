import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'recetas_screen_parts.dart';

class RecetasScreen extends ConsumerStatefulWidget {
  const RecetasScreen({super.key});

  @override
  ConsumerState<RecetasScreen> createState() => _RecetasScreenState();
}

class _RecetasScreenState extends ConsumerState<RecetasScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> _recetas = [];
  List<dynamic> _destacadas = [];
  List<dynamic> _categorias = [];
  List<dynamic> _tiposCocina = [];
  bool _cargandoRecetas = true;
  bool _cargandoDestacadas = true;
  bool _cargandoCategorias = true;
  String _filtroCategoria = '';
  String _filtroTipoCocina = '';
  String _filtroDificultad = '';
  String _textoBusqueda = '';

  final List<Map<String, String>> _dificultades = [
    {'id': '', 'label': 'Todas'},
    {'id': 'facil', 'label': 'Fácil'},
    {'id': 'media', 'label': 'Media'},
    {'id': 'dificil', 'label': 'Difícil'},
  ];

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
    _cargarRecetas();
    _cargarDestacadas();
    _cargarCategorias();
  }

  Future<void> _cargarRecetas() async {
    setState(() => _cargandoRecetas = true);
    try {
      final api = ref.read(apiClientProvider);
      final queryParams = <String, dynamic>{
        'limite': 30,
        if (_filtroCategoria.isNotEmpty) 'categoria': _filtroCategoria,
        if (_filtroTipoCocina.isNotEmpty) 'tipo_cocina': _filtroTipoCocina,
        if (_filtroDificultad.isNotEmpty) 'dificultad': _filtroDificultad,
      };

      String endpoint = '/recetas';
      if (_textoBusqueda.isNotEmpty) {
        endpoint = '/recetas/buscar';
        queryParams['q'] = _textoBusqueda;
      }

      final respuesta = await api.get(endpoint, queryParameters: queryParams);
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _recetas = respuesta.data!['recetas'] ?? [];
        });
      }
    } finally {
      setState(() => _cargandoRecetas = false);
    }
  }

  Future<void> _cargarDestacadas() async {
    setState(() => _cargandoDestacadas = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta =
          await api.get('/recetas/destacadas', queryParameters: {'limite': 10});
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _destacadas = respuesta.data!['recetas'] ?? [];
        });
      }
    } finally {
      setState(() => _cargandoDestacadas = false);
    }
  }

  Future<void> _cargarCategorias() async {
    setState(() => _cargandoCategorias = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/recetas/categorias');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _categorias = respuesta.data!['categorias'] ?? [];
          _tiposCocina = respuesta.data!['tipos_cocina'] ?? [];
        });
      }
    } finally {
      setState(() => _cargandoCategorias = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Recetas'),
        backgroundColor: Colors.orange.shade700,
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
            Tab(icon: Icon(Icons.restaurant_menu), text: 'Recetas'),
            Tab(icon: Icon(Icons.star), text: 'Destacadas'),
            Tab(icon: Icon(Icons.category), text: 'Categorías'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildRecetasTab(),
          _buildDestacadasTab(),
          _buildCategoriasTab(),
        ],
      ),
    );
  }

  Widget _buildRecetasTab() {
    return Column(
      children: [
        _buildFiltros(),
        if (_textoBusqueda.isNotEmpty)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            color: Colors.orange.shade50,
            child: Row(
              children: [
                Icon(Icons.search, size: 16, color: Colors.orange.shade700),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Buscando: "$_textoBusqueda"',
                    style: TextStyle(color: Colors.orange.shade700),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 18),
                  onPressed: () {
                    setState(() => _textoBusqueda = '');
                    _cargarRecetas();
                  },
                ),
              ],
            ),
          ),
        Expanded(
          child: _cargandoRecetas
              ? const FlavorLoadingState()
              : _recetas.isEmpty
                  ? FlavorEmptyState(
                      icon: Icons.restaurant_menu,
                      title: 'No hay recetas',
                      message: _filtroCategoria.isNotEmpty ||
                              _filtroDificultad.isNotEmpty ||
                              _textoBusqueda.isNotEmpty
                          ? 'Prueba con otros filtros'
                          : null,
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarRecetas,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _recetas.length,
                        itemBuilder: (context, index) => _RecetaCard(
                          receta: _recetas[index],
                          onTap: () => _abrirDetalle(_recetas[index]),
                        ),
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildFiltros() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Column(
        children: [
          // Filtro por dificultad
          SizedBox(
            height: 40,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              itemCount: _dificultades.length,
              itemBuilder: (context, index) {
                final dificultad = _dificultades[index];
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(dificultad['label']!),
                    selected: _filtroDificultad == dificultad['id'],
                    selectedColor: Colors.orange.shade100,
                    onSelected: (_) {
                      setState(() => _filtroDificultad = dificultad['id']!);
                      _cargarRecetas();
                    },
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 4),
          // Filtro por categoría (si hay)
          if (_categorias.isNotEmpty)
            SizedBox(
              height: 40,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                itemCount: _categorias.length + 1,
                itemBuilder: (context, index) {
                  if (index == 0) {
                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: FilterChip(
                        label: const Text('Todas'),
                        selected: _filtroCategoria.isEmpty,
                        selectedColor: Colors.orange.shade100,
                        onSelected: (_) {
                          setState(() => _filtroCategoria = '');
                          _cargarRecetas();
                        },
                      ),
                    );
                  }
                  final cat = _categorias[index - 1] as Map<String, dynamic>;
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: FilterChip(
                      label: Text(cat['nombre'] ?? ''),
                      selected: _filtroCategoria == cat['slug'],
                      selectedColor: Colors.orange.shade100,
                      onSelected: (_) {
                        setState(() => _filtroCategoria = cat['slug'] ?? '');
                        _cargarRecetas();
                      },
                    ),
                  );
                },
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildDestacadasTab() {
    if (_cargandoDestacadas) {
      return const FlavorLoadingState();
    }

    if (_destacadas.isEmpty) {
      return const FlavorEmptyState(
        icon: Icons.star_border,
        title: 'No hay recetas destacadas',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarDestacadas,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _destacadas.length,
        itemBuilder: (context, index) => _RecetaCardDestacada(
          receta: _destacadas[index],
          onTap: () => _abrirDetalle(_destacadas[index]),
        ),
      ),
    );
  }

  Widget _buildCategoriasTab() {
    if (_cargandoCategorias) {
      return const FlavorLoadingState();
    }

    return RefreshIndicator(
      onRefresh: _cargarCategorias,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_categorias.isNotEmpty) ...[
            const Text(
              'Categorías',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
            ),
            const SizedBox(height: 12),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: 1.5,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              itemCount: _categorias.length,
              itemBuilder: (context, index) {
                final cat = _categorias[index] as Map<String, dynamic>;
                return _CategoriaCard(
                  nombre: cat['nombre'] ?? '',
                  total: cat['total'] ?? 0,
                  color: Colors.orange,
                  icono: Icons.restaurant,
                  onTap: () {
                    setState(() {
                      _filtroCategoria = cat['slug'] ?? '';
                      _tabController.animateTo(0);
                    });
                    _cargarRecetas();
                  },
                );
              },
            ),
            const SizedBox(height: 24),
          ],
          if (_tiposCocina.isNotEmpty) ...[
            const Text(
              'Tipos de Cocina',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
            ),
            const SizedBox(height: 12),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: 1.5,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              itemCount: _tiposCocina.length,
              itemBuilder: (context, index) {
                final tipo = _tiposCocina[index] as Map<String, dynamic>;
                return _CategoriaCard(
                  nombre: tipo['nombre'] ?? '',
                  total: tipo['total'] ?? 0,
                  color: Colors.deepOrange,
                  icono: Icons.public,
                  onTap: () {
                    setState(() {
                      _filtroTipoCocina = tipo['slug'] ?? '';
                      _tabController.animateTo(0);
                    });
                    _cargarRecetas();
                  },
                );
              },
            ),
          ],
        ],
      ),
    );
  }

  void _mostrarBusqueda() {
    showSearch(
      context: context,
      delegate: _RecetaBusquedaDelegate(
        onBuscar: (query) {
          setState(() => _textoBusqueda = query);
          _cargarRecetas();
          _tabController.animateTo(0);
        },
      ),
    );
  }

  void _abrirDetalle(dynamic receta) {
    final mapa = receta as Map<String, dynamic>;
    final recetaId = mapa['id'];
    if (recetaId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => _RecetaDetalleScreen(recetaId: recetaId),
        ),
      );
    }
  }
}
