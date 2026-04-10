import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/utils/haptics.dart';

part 'encuestas_screen_parts.dart';

/// Pantalla principal del módulo de encuestas
class EncuestasScreen extends ConsumerStatefulWidget {
  const EncuestasScreen({super.key});

  @override
  ConsumerState<EncuestasScreen> createState() => _EncuestasScreenState();
}

class _EncuestasScreenState extends ConsumerState<EncuestasScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<_Encuesta> _encuestas = [];
  bool _isLoading = true;
  String? _error;
  String _filtroEstado = 'activa';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(_onTabChanged);
    _cargarEncuestas();
  }

  @override
  void dispose() {
    _tabController.removeListener(_onTabChanged);
    _tabController.dispose();
    super.dispose();
  }

  void _onTabChanged() {
    if (!_tabController.indexIsChanging) {
      final estados = ['activa', 'cerrada', ''];
      setState(() {
        _filtroEstado = estados[_tabController.index];
      });
      _cargarEncuestas();
    }
  }

  Future<void> _cargarEncuestas() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get(
        '/flavor/v1/encuestas',
        queryParameters: {
          if (_filtroEstado.isNotEmpty) 'estado': _filtroEstado,
          'limit': 50,
        },
      );

      if (response.success && response.data != null) {
        final List<dynamic> items = response.data?['data'] ?? [];
        setState(() {
          _encuestas = items.map((encuestaJson) => _Encuesta.fromJson(encuestaJson)).toList();
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar encuestas';
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
        title: const Text('Encuestas'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(
              icon: Icon(Icons.how_to_vote),
              text: 'Activas',
            ),
            Tab(
              icon: Icon(Icons.lock_clock),
              text: 'Cerradas',
            ),
            Tab(
              icon: Icon(Icons.list_alt),
              text: 'Todas',
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarEncuestas,
            tooltip: 'Actualizar',
          ),
        ],
      ),
      body: _buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearEncuesta,
        icon: const Icon(Icons.add),
        label: const Text('Nueva encuesta'),
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
        onRetry: _cargarEncuestas,
      );
    }

    if (_encuestas.isEmpty) {
      return const FlavorEmptyState(
        icon: Icons.poll_outlined,
        title: 'Sin encuestas',
        message: 'No hay encuestas disponibles',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarEncuestas,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _encuestas.length,
        itemBuilder: (context, index) {
          return _EncuestaCard(
            encuesta: _encuestas[index],
            onTap: () => _abrirEncuesta(_encuestas[index]),
          );
        },
      ),
    );
  }

  void _abrirEncuesta(_Encuesta encuesta) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _EncuestaDetalleScreen(encuesta: encuesta),
      ),
    ).then((_) => _cargarEncuestas());
  }

  void _crearEncuesta() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const _CrearEncuestaScreen(),
      ),
    ).then((created) {
      if (created == true) {
        _cargarEncuestas();
      }
    });
  }
}
