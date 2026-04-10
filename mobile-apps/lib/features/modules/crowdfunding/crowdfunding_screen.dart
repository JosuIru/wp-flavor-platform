import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/utils/haptics.dart';

part 'crowdfunding_screen_parts.dart';

/// Pantalla principal del módulo de Crowdfunding
/// Sistema de financiación colectiva para proyectos culturales, sociales y comunitarios
class CrowdfundingScreen extends ConsumerStatefulWidget {
  const CrowdfundingScreen({super.key});

  @override
  ConsumerState<CrowdfundingScreen> createState() => _CrowdfundingScreenState();
}

class _CrowdfundingScreenState extends ConsumerState<CrowdfundingScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<_Proyecto> _proyectos = [];
  bool _isLoading = true;
  String? _error;
  String _filtroTipo = '';
  String _filtroEstado = 'activo';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(_onTabChanged);
    _cargarProyectos();
  }

  @override
  void dispose() {
    _tabController.removeListener(_onTabChanged);
    _tabController.dispose();
    super.dispose();
  }

  void _onTabChanged() {
    if (!_tabController.indexIsChanging) {
      final estados = ['activo', 'exitoso', 'fallido', ''];
      setState(() {
        _filtroEstado = estados[_tabController.index];
      });
      _cargarProyectos();
    }
  }

  Future<void> _cargarProyectos() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get(
        '/flavor/v1/crowdfunding/proyectos',
        queryParameters: {
          if (_filtroEstado.isNotEmpty) 'estado': _filtroEstado,
          if (_filtroTipo.isNotEmpty) 'tipo': _filtroTipo,
          'limite': 50,
        },
      );

      if (response.success && response.data != null) {
        final data = response.data!;
        final List<dynamic> items = data['proyectos'] ?? data['data'] ?? [];
        setState(() {
          _proyectos = items.map((json) => _Proyecto.fromJson(json)).toList();
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar proyectos';
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
        title: const Text('Crowdfunding'),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          tabs: const [
            Tab(text: 'Activos'),
            Tab(text: 'Financiados'),
            Tab(text: 'No financiados'),
            Tab(text: 'Todos'),
          ],
        ),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.filter_list),
            tooltip: 'Filtrar por tipo',
            onSelected: (tipo) {
              setState(() => _filtroTipo = tipo);
              _cargarProyectos();
            },
            itemBuilder: (context) => [
              const PopupMenuItem(value: '', child: Text('Todos los tipos')),
              const PopupMenuDivider(),
              const PopupMenuItem(value: 'album', child: Text('🎵 Álbum/Grabación')),
              const PopupMenuItem(value: 'tour', child: Text('🎤 Gira/Tour')),
              const PopupMenuItem(value: 'produccion', child: Text('🎬 Producción')),
              const PopupMenuItem(value: 'equipamiento', child: Text('🔧 Equipamiento')),
              const PopupMenuItem(value: 'espacio', child: Text('🏠 Espacio')),
              const PopupMenuItem(value: 'evento', child: Text('🎉 Evento')),
              const PopupMenuItem(value: 'social', child: Text('🤝 Proyecto Social')),
              const PopupMenuItem(value: 'emergencia', child: Text('🆘 Emergencia')),
              const PopupMenuItem(value: 'otro', child: Text('📦 Otro')),
            ],
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarProyectos,
            tooltip: 'Actualizar',
          ),
        ],
      ),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearProyecto,
        icon: const Icon(Icons.add),
        label: const Text('Nuevo proyecto'),
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
        onRetry: _cargarProyectos,
      );
    }

    if (_proyectos.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.rocket_launch_outlined,
        title: 'Sin proyectos',
        message: _filtroEstado.isEmpty
            ? 'No hay proyectos de crowdfunding disponibles'
            : 'No hay proyectos ${_getEstadoLabel(_filtroEstado).toLowerCase()}',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarProyectos,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _proyectos.length,
        itemBuilder: (context, index) {
          return _ProyectoCard(
            proyecto: _proyectos[index],
            onTap: () => _abrirProyecto(_proyectos[index]),
          );
        },
      ),
    );
  }

  String _getEstadoLabel(String estado) {
    switch (estado) {
      case 'activo':
        return 'Activos';
      case 'exitoso':
        return 'Financiados';
      case 'fallido':
        return 'No financiados';
      case 'borrador':
        return 'Borradores';
      case 'revision':
        return 'En revisión';
      case 'pausado':
        return 'Pausados';
      case 'cancelado':
        return 'Cancelados';
      default:
        return estado;
    }
  }

  void _abrirProyecto(_Proyecto proyecto) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _ProyectoDetalleScreen(proyecto: proyecto),
      ),
    ).then((_) => _cargarProyectos());
  }

  void _crearProyecto() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const _CrearProyectoScreen(),
      ),
    ).then((created) {
      if (created == true) {
        _cargarProyectos();
      }
    });
  }
}
