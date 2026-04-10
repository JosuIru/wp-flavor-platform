import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'documentacion_legal_screen_parts.dart';

class DocumentacionLegalScreen extends ConsumerStatefulWidget {
  const DocumentacionLegalScreen({super.key});

  @override
  ConsumerState<DocumentacionLegalScreen> createState() =>
      _DocumentacionLegalScreenState();
}

class _DocumentacionLegalScreenState
    extends ConsumerState<DocumentacionLegalScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> _documentos = [];
  List<dynamic> _favoritos = [];
  List<dynamic> _categorias = [];
  Map<String, dynamic>? _estadisticas;
  bool _cargandoDocumentos = true;
  bool _cargandoFavoritos = true;
  bool _cargandoCategorias = true;
  bool _cargandoEstadisticas = true;
  String _filtroTipo = '';
  String _filtroCategoria = '';
  String _filtroAmbito = '';
  String _textoBusqueda = '';

  final List<Map<String, String>> _tipos = [
    {'id': '', 'label': 'Todos'},
    {'id': 'ley', 'label': 'Leyes'},
    {'id': 'decreto', 'label': 'Decretos'},
    {'id': 'ordenanza', 'label': 'Ordenanzas'},
    {'id': 'sentencia', 'label': 'Sentencias'},
    {'id': 'modelo_denuncia', 'label': 'Modelos denuncia'},
    {'id': 'modelo_recurso', 'label': 'Modelos recurso'},
    {'id': 'guia', 'label': 'Guías'},
    {'id': 'informe', 'label': 'Informes'},
  ];

  final List<Map<String, String>> _ambitos = [
    {'id': '', 'label': 'Todos'},
    {'id': 'europeo', 'label': 'Europeo'},
    {'id': 'estatal', 'label': 'Estatal'},
    {'id': 'autonomico', 'label': 'Autonómico'},
    {'id': 'provincial', 'label': 'Provincial'},
    {'id': 'municipal', 'label': 'Municipal'},
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _cargarDatos();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarDatos() async {
    _cargarDocumentos();
    _cargarCategorias();
    _cargarFavoritos();
    _cargarEstadisticas();
  }

  Future<void> _cargarDocumentos() async {
    setState(() => _cargandoDocumentos = true);
    try {
      final api = ref.read(apiClientProvider);
      final queryParams = <String, dynamic>{
        'limite': 30,
        if (_filtroTipo.isNotEmpty) 'tipo': _filtroTipo,
        if (_filtroCategoria.isNotEmpty) 'categoria': _filtroCategoria,
      };

      String endpoint = '/documentacion-legal';
      if (_textoBusqueda.isNotEmpty) {
        endpoint = '/documentacion-legal/buscar';
        queryParams['q'] = _textoBusqueda;
        if (_filtroAmbito.isNotEmpty) queryParams['ambito'] = _filtroAmbito;
      }

      final respuesta = await api.get(endpoint, queryParameters: queryParams);
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _documentos = respuesta.data!['documentos'] ?? [];
        });
      }
    } finally {
      setState(() => _cargandoDocumentos = false);
    }
  }

  Future<void> _cargarCategorias() async {
    setState(() => _cargandoCategorias = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/documentacion-legal/categorias');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _categorias = respuesta.data!['categorias'] ?? [];
        });
      }
    } finally {
      setState(() => _cargandoCategorias = false);
    }
  }

  Future<void> _cargarFavoritos() async {
    setState(() => _cargandoFavoritos = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/documentacion-legal/mis-favoritos');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _favoritos = respuesta.data!['documentos'] ?? [];
        });
      }
    } catch (_) {
      // Usuario no logueado, ignorar
    } finally {
      setState(() => _cargandoFavoritos = false);
    }
  }

  Future<void> _cargarEstadisticas() async {
    setState(() => _cargandoEstadisticas = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/documentacion-legal/estadisticas');
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
        title: const Text('Documentación Legal'),
        backgroundColor: Colors.indigo.shade700,
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
          isScrollable: true,
          tabs: const [
            Tab(icon: Icon(Icons.folder_open), text: 'Documentos'),
            Tab(icon: Icon(Icons.category), text: 'Categorías'),
            Tab(icon: Icon(Icons.star), text: 'Favoritos'),
            Tab(icon: Icon(Icons.analytics), text: 'Estadísticas'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildDocumentosTab(),
          _buildCategoriasTab(),
          _buildFavoritosTab(),
          _buildEstadisticasTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _subirDocumento,
        icon: const Icon(Icons.upload_file),
        label: const Text('Subir documento'),
        backgroundColor: Colors.indigo.shade700,
        foregroundColor: Colors.white,
      ),
    );
  }

  Widget _buildDocumentosTab() {
    return Column(
      children: [
        _buildFiltros(),
        if (_textoBusqueda.isNotEmpty)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            color: Colors.indigo.shade50,
            child: Row(
              children: [
                Icon(Icons.search, size: 16, color: Colors.indigo.shade700),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Buscando: "$_textoBusqueda"',
                    style: TextStyle(color: Colors.indigo.shade700),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close, size: 18),
                  onPressed: () {
                    setState(() => _textoBusqueda = '');
                    _cargarDocumentos();
                  },
                ),
              ],
            ),
          ),
        Expanded(
          child: _cargandoDocumentos
              ? const FlavorLoadingState()
              : _documentos.isEmpty
                  ? FlavorEmptyState(
                      icon: Icons.folder_open,
                      title: 'No hay documentos',
                      message: _filtroTipo.isNotEmpty ||
                              _filtroCategoria.isNotEmpty ||
                              _textoBusqueda.isNotEmpty
                          ? 'Prueba con otros filtros o busca otra cosa'
                          : null,
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDocumentos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _documentos.length,
                        itemBuilder: (context, index) => _DocumentoCard(
                          documento: _documentos[index],
                          onTap: () => _abrirDetalle(_documentos[index]),
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
          // Filtro por tipo
          SizedBox(
            height: 40,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              itemCount: _tipos.length,
              itemBuilder: (context, index) {
                final tipo = _tipos[index];
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(tipo['label']!),
                    selected: _filtroTipo == tipo['id'],
                    selectedColor: Colors.indigo.shade100,
                    onSelected: (_) {
                      setState(() => _filtroTipo = tipo['id']!);
                      _cargarDocumentos();
                    },
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 4),
          // Filtro por ámbito
          SizedBox(
            height: 40,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              itemCount: _ambitos.length,
              itemBuilder: (context, index) {
                final ambito = _ambitos[index];
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(ambito['label']!),
                    selected: _filtroAmbito == ambito['id'],
                    selectedColor: Colors.indigo.shade100,
                    onSelected: (_) {
                      setState(() => _filtroAmbito = ambito['id']!);
                      _cargarDocumentos();
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

  Widget _buildCategoriasTab() {
    if (_cargandoCategorias) {
      return const FlavorLoadingState();
    }

    if (_categorias.isEmpty) {
      return const FlavorEmptyState(
        icon: Icons.category,
        title: 'No hay categorías',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarCategorias,
      child: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          childAspectRatio: 1.3,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
        ),
        itemCount: _categorias.length,
        itemBuilder: (context, index) {
          final categoria = _categorias[index] as Map<String, dynamic>;
          return _CategoriaCard(
            categoria: categoria,
            onTap: () {
              setState(() {
                _filtroCategoria = categoria['slug'] ?? '';
                _tabController.animateTo(0);
              });
              _cargarDocumentos();
            },
          );
        },
      ),
    );
  }

  Widget _buildFavoritosTab() {
    if (_cargandoFavoritos) {
      return const FlavorLoadingState();
    }

    if (_favoritos.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.star_border,
        title: 'Sin favoritos',
        message: 'Guarda documentos para acceder rápidamente',
        action: TextButton.icon(
          onPressed: () => _tabController.animateTo(0),
          icon: const Icon(Icons.folder_open),
          label: const Text('Ver documentos'),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarFavoritos,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _favoritos.length,
        itemBuilder: (context, index) => _DocumentoCard(
          documento: _favoritos[index],
          esFavorito: true,
          onTap: () => _abrirDetalle(_favoritos[index]),
        ),
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
    final porAmbito = _estadisticas!['por_ambito'] as List<dynamic>? ?? [];
    final porCategoria =
        _estadisticas!['por_categoria'] as List<dynamic>? ?? [];

    return RefreshIndicator(
      onRefresh: _cargarEstadisticas,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Resumen general
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Resumen General',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      _buildStatCard(
                        'Total',
                        '${stats?['total_documentos'] ?? 0}',
                        Colors.indigo,
                        Icons.folder,
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
                        'Descargas',
                        '${stats?['total_descargas'] ?? 0}',
                        Colors.blue,
                        Icons.download,
                      ),
                      const SizedBox(width: 12),
                      _buildStatCard(
                        'Visitas',
                        '${stats?['total_visitas'] ?? 0}',
                        Colors.orange,
                        Icons.visibility,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Por tipo de documento
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Por Tipo',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 12),
                  _buildTipoBar('Leyes', stats?['leyes'] ?? 0, Colors.purple),
                  _buildTipoBar(
                      'Decretos', stats?['decretos'] ?? 0, Colors.blue),
                  _buildTipoBar(
                      'Ordenanzas', stats?['ordenanzas'] ?? 0, Colors.teal),
                  _buildTipoBar(
                      'Sentencias', stats?['sentencias'] ?? 0, Colors.red),
                  _buildTipoBar(
                      'Modelos', stats?['modelos'] ?? 0, Colors.orange),
                  _buildTipoBar('Guías', stats?['guias'] ?? 0, Colors.green),
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
                    ...porAmbito.map((item) => _buildTipoBar(
                          _formatearAmbito(item['ambito'] ?? ''),
                          int.tryParse('${item['total']}') ?? 0,
                          _getColorAmbito(item['ambito'] ?? ''),
                        )),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 16),

          // Top categorías
          if (porCategoria.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Top Categorías',
                      style:
                          TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 12),
                    ...porCategoria.take(5).map((item) => ListTile(
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          leading: CircleAvatar(
                            backgroundColor: Colors.indigo.shade100,
                            radius: 16,
                            child: Icon(Icons.folder,
                                size: 16, color: Colors.indigo.shade700),
                          ),
                          title: Text(item['categoria'] ?? 'Sin categoría'),
                          trailing: Text(
                            '${item['total']}',
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                        )),
                  ],
                ),
              ),
            ),
        ],
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
            Icon(icon, color: color, size: 32),
            const SizedBox(height: 8),
            Text(
              value,
              style: TextStyle(
                fontSize: 24,
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
    final total =
        int.tryParse('${_estadisticas?['estadisticas']?['total_documentos']}') ??
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
              Text(label),
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

  String _formatearAmbito(String ambito) {
    final map = {
      'europeo': 'Europeo',
      'estatal': 'Estatal',
      'autonomico': 'Autonómico',
      'provincial': 'Provincial',
      'municipal': 'Municipal',
    };
    return map[ambito] ?? ambito;
  }

  Color _getColorAmbito(String ambito) {
    final map = {
      'europeo': Colors.blue.shade700,
      'estatal': Colors.red.shade700,
      'autonomico': Colors.purple.shade700,
      'provincial': Colors.orange.shade700,
      'municipal': Colors.green.shade700,
    };
    return map[ambito] ?? Colors.grey;
  }

  void _mostrarBusqueda() {
    showSearch(
      context: context,
      delegate: _DocumentoBusquedaDelegate(
        onBuscar: (query) {
          setState(() => _textoBusqueda = query);
          _cargarDocumentos();
          _tabController.animateTo(0);
        },
      ),
    );
  }

  void _abrirDetalle(dynamic documento) {
    final mapa = documento as Map<String, dynamic>;
    final documentoId = mapa['id'];
    if (documentoId != null) {
      Navigator.of(context)
          .push(
            MaterialPageRoute(
              builder: (_) => _DocumentoDetalleScreen(documentoId: documentoId),
            ),
          )
          .then((_) => _cargarDatos());
    }
  }

  void _subirDocumento() {
    Navigator.of(context)
        .push(
          MaterialPageRoute(
            builder: (_) => _SubirDocumentoScreen(categorias: _categorias),
          ),
        )
        .then((subido) {
      if (subido == true) {
        FlavorSnackbar.show(
          context,
          'Documento subido. Pendiente de revisión.',
          type: SnackbarType.success,
        );
        _cargarDocumentos();
      }
    });
  }
}
