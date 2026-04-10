import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/utils/haptics.dart';

part 'campanias_screen_parts.dart';

/// Pantalla principal del módulo de Campañas Ciudadanas
/// Sistema de coordinación de campañas, recogida de firmas y acciones colectivas
class CampaniasScreen extends ConsumerStatefulWidget {
  const CampaniasScreen({super.key});

  @override
  ConsumerState<CampaniasScreen> createState() => _CampaniasScreenState();
}

class _CampaniasScreenState extends ConsumerState<CampaniasScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<_Campania> _campanias = [];
  bool _isLoading = true;
  String? _error;
  String _filtroTipo = '';
  String _filtroEstado = 'activa';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(_onTabChanged);
    _cargarCampanias();
  }

  @override
  void dispose() {
    _tabController.removeListener(_onTabChanged);
    _tabController.dispose();
    super.dispose();
  }

  void _onTabChanged() {
    if (!_tabController.indexIsChanging) {
      final estados = ['activa', 'planificada', 'completada', ''];
      setState(() {
        _filtroEstado = estados[_tabController.index];
      });
      _cargarCampanias();
    }
  }

  Future<void> _cargarCampanias() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get(
        '/flavor/v1/campanias',
        queryParameters: {
          if (_filtroEstado.isNotEmpty) 'estado': _filtroEstado,
          if (_filtroTipo.isNotEmpty) 'tipo': _filtroTipo,
          'limite': 50,
        },
      );

      if (response.success && response.data != null) {
        final data = response.data!;
        final List<dynamic> items = data['data'] ?? data['campanias'] ?? [];
        setState(() {
          _campanias = items.map((json) => _Campania.fromJson(json)).toList();
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar campañas';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Campañas'),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          tabs: const [
            Tab(text: 'Activas'),
            Tab(text: 'Planificadas'),
            Tab(text: 'Completadas'),
            Tab(text: 'Todas'),
          ],
        ),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.filter_list),
            tooltip: 'Filtrar por tipo',
            onSelected: (tipo) {
              setState(() => _filtroTipo = tipo);
              _cargarCampanias();
            },
            itemBuilder: (context) => [
              const PopupMenuItem(value: '', child: Text('Todos los tipos')),
              const PopupMenuDivider(),
              const PopupMenuItem(value: 'recogida_firmas', child: Text('🖊️ Recogida de firmas')),
              const PopupMenuItem(value: 'protesta', child: Text('✊ Protesta')),
              const PopupMenuItem(value: 'concentracion', child: Text('👥 Concentración')),
              const PopupMenuItem(value: 'boicot', child: Text('🚫 Boicot')),
              const PopupMenuItem(value: 'sensibilizacion', child: Text('💡 Sensibilización')),
              const PopupMenuItem(value: 'denuncia_publica', child: Text('📢 Denuncia pública')),
              const PopupMenuItem(value: 'accion_legal', child: Text('⚖️ Acción legal')),
            ],
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarCampanias,
            tooltip: 'Actualizar',
          ),
        ],
      ),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearCampania,
        icon: const Icon(Icons.add),
        label: const Text('Nueva campaña'),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const FlavorLoadingState();
    }

    if (_error != null) {
      return FlavorErrorState(
        message: _error!,
        onRetry: _cargarCampanias,
      );
    }

    if (_campanias.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.campaign_outlined,
        title: 'Sin campañas',
        message: _filtroEstado.isEmpty
            ? 'No hay campañas disponibles'
            : 'No hay campañas ${_getEstadoLabel(_filtroEstado).toLowerCase()}',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarCampanias,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _campanias.length,
        itemBuilder: (context, index) {
          return _CampaniaCard(
            campania: _campanias[index],
            onTap: () => _abrirCampania(_campanias[index]),
          );
        },
      ),
    );
  }

  String _getEstadoLabel(String estado) {
    switch (estado) {
      case 'activa':
        return 'Activas';
      case 'planificada':
        return 'Planificadas';
      case 'completada':
        return 'Completadas';
      case 'pausada':
        return 'Pausadas';
      case 'cancelada':
        return 'Canceladas';
      default:
        return estado;
    }
  }

  void _abrirCampania(_Campania campania) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _CampaniaDetalleScreen(campania: campania),
      ),
    ).then((_) => _cargarCampanias());
  }

  void _crearCampania() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const _CrearCampaniaScreen(),
      ),
    ).then((created) {
      if (created == true) {
        _cargarCampanias();
      }
    });
  }
}
