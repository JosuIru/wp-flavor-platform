import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'seguimiento_denuncias_screen_parts.dart';

class SeguimientoDenunciasScreen extends ConsumerStatefulWidget {
  const SeguimientoDenunciasScreen({super.key});

  @override
  ConsumerState<SeguimientoDenunciasScreen> createState() =>
      _SeguimientoDenunciasScreenState();
}

class _SeguimientoDenunciasScreenState
    extends ConsumerState<SeguimientoDenunciasScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> _denunciasPublicas = [];
  List<dynamic> _misDenuncias = [];
  Map<String, dynamic>? _estadisticas;
  bool _cargandoPublicas = true;
  bool _cargandoMias = true;
  bool _cargandoEstadisticas = true;
  String _filtroEstado = '';
  String _filtroTipo = '';

  final List<Map<String, String>> _estados = [
    {'id': '', 'label': 'Todos'},
    {'id': 'presentada', 'label': 'Presentada'},
    {'id': 'en_tramite', 'label': 'En trámite'},
    {'id': 'requerimiento', 'label': 'Requerimiento'},
    {'id': 'silencio', 'label': 'Silencio adm.'},
    {'id': 'resuelta_favorable', 'label': 'Favorable'},
    {'id': 'resuelta_desfavorable', 'label': 'Desfavorable'},
    {'id': 'recurrida', 'label': 'Recurrida'},
  ];

  final List<Map<String, String>> _tipos = [
    {'id': '', 'label': 'Todos'},
    {'id': 'denuncia', 'label': 'Denuncia'},
    {'id': 'queja', 'label': 'Queja'},
    {'id': 'recurso', 'label': 'Recurso'},
    {'id': 'solicitud', 'label': 'Solicitud'},
    {'id': 'peticion', 'label': 'Petición'},
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
    _cargarDenunciasPublicas();
    _cargarMisDenuncias();
    _cargarEstadisticas();
  }

  Future<void> _cargarDenunciasPublicas() async {
    setState(() => _cargandoPublicas = true);
    try {
      final api = ref.read(apiClientProvider);
      final queryParams = <String, dynamic>{
        'limite': 30,
        if (_filtroEstado.isNotEmpty) 'estado': _filtroEstado,
        if (_filtroTipo.isNotEmpty) 'tipo': _filtroTipo,
      };
      final respuesta =
          await api.get('/denuncias', queryParameters: queryParams);
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _denunciasPublicas = respuesta.data!['denuncias'] ?? [];
        });
      }
    } finally {
      setState(() => _cargandoPublicas = false);
    }
  }

  Future<void> _cargarMisDenuncias() async {
    setState(() => _cargandoMias = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/denuncias/mis');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _misDenuncias = respuesta.data!['denuncias'] ?? [];
        });
      }
    } finally {
      setState(() => _cargandoMias = false);
    }
  }

  Future<void> _cargarEstadisticas() async {
    setState(() => _cargandoEstadisticas = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/denuncias/estadisticas');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _estadisticas = respuesta.data!['estadisticas'];
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
        title: const Text('Seguimiento de Denuncias'),
        backgroundColor: Colors.red.shade700,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(icon: Icon(Icons.public), text: 'Públicas'),
            Tab(icon: Icon(Icons.person), text: 'Mis casos'),
            Tab(icon: Icon(Icons.analytics), text: 'Estadísticas'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildPublicasTab(),
          _buildMisCasosTab(),
          _buildEstadisticasTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearDenuncia,
        icon: const Icon(Icons.add),
        label: const Text('Nueva denuncia'),
        backgroundColor: Colors.red.shade700,
        foregroundColor: Colors.white,
      ),
    );
  }

  Widget _buildPublicasTab() {
    return Column(
      children: [
        _buildFiltros(),
        Expanded(
          child: _cargandoPublicas
              ? const FlavorLoadingState()
              : _denunciasPublicas.isEmpty
                  ? FlavorEmptyState(
                      icon: Icons.folder_open,
                      title: 'No hay denuncias públicas',
                      message: _filtroEstado.isNotEmpty || _filtroTipo.isNotEmpty
                          ? 'Prueba con otros filtros'
                          : null,
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDenunciasPublicas,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _denunciasPublicas.length,
                        itemBuilder: (context, index) => _DenunciaCard(
                          denuncia: _denunciasPublicas[index],
                          onTap: () =>
                              _abrirDetalle(_denunciasPublicas[index]),
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
          // Filtro por estado
          SizedBox(
            height: 40,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              itemCount: _estados.length,
              itemBuilder: (context, index) {
                final estado = _estados[index];
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(estado['label']!),
                    selected: _filtroEstado == estado['id'],
                    selectedColor: Colors.red.shade100,
                    onSelected: (_) {
                      setState(() => _filtroEstado = estado['id']!);
                      _cargarDenunciasPublicas();
                    },
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 4),
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
                  child: FilterChip(
                    label: Text(tipo['label']!),
                    selected: _filtroTipo == tipo['id'],
                    selectedColor: Colors.red.shade100,
                    onSelected: (_) {
                      setState(() => _filtroTipo = tipo['id']!);
                      _cargarDenunciasPublicas();
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

  Widget _buildMisCasosTab() {
    if (_cargandoMias) {
      return const FlavorLoadingState();
    }

    if (_misDenuncias.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.folder_special,
        title: 'No tienes denuncias',
        message: 'Crea tu primera denuncia para hacer seguimiento',
        action: TextButton.icon(
          onPressed: _crearDenuncia,
          icon: const Icon(Icons.add),
          label: const Text('Crear denuncia'),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarMisDenuncias,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _misDenuncias.length,
        itemBuilder: (context, index) => _DenunciaCard(
          denuncia: _misDenuncias[index],
          esMia: true,
          onTap: () => _abrirDetalle(_misDenuncias[index]),
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
                        '${_estadisticas!['total'] ?? 0}',
                        Colors.blue,
                        Icons.folder,
                      ),
                      const SizedBox(width: 12),
                      _buildStatCard(
                        'En trámite',
                        '${_estadisticas!['en_tramite'] ?? 0}',
                        Colors.orange,
                        Icons.pending,
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      _buildStatCard(
                        'Silencio',
                        '${_estadisticas!['silencio'] ?? 0}',
                        Colors.grey,
                        Icons.hourglass_empty,
                      ),
                      const SizedBox(width: 12),
                      _buildStatCard(
                        'Favorables',
                        '${_estadisticas!['resueltas_favorable'] ?? 0}',
                        Colors.green,
                        Icons.check_circle,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Estados detallados
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Por Estado',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 12),
                  _buildEstadoBar('Con requerimiento',
                      _estadisticas!['con_requerimiento'] ?? 0, Colors.red),
                  _buildEstadoBar('Desfavorables',
                      _estadisticas!['resueltas_desfavorable'] ?? 0, Colors.red.shade300),
                  _buildEstadoBar(
                      'Recurridas', _estadisticas!['recurridas'] ?? 0, Colors.purple),
                  _buildEstadoBar(
                      'Archivadas', _estadisticas!['archivadas'] ?? 0, Colors.grey),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String label, String value, Color color, IconData icon) {
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

  Widget _buildEstadoBar(String label, int valor, Color color) {
    final total = _estadisticas!['total'] ?? 1;
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

  void _abrirDetalle(dynamic denuncia) {
    final mapa = denuncia as Map<String, dynamic>;
    final denunciaId = mapa['id'];
    if (denunciaId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => _DenunciaDetalleScreen(denunciaId: denunciaId),
        ),
      ).then((_) => _cargarDatos());
    }
  }

  void _crearDenuncia() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => const _CrearDenunciaScreen(),
      ),
    ).then((creada) {
      if (creada == true) {
        _cargarMisDenuncias();
        _tabController.animateTo(1); // Ir a "Mis casos"
      }
    });
  }
}
