import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'participacion_screen_parts.dart';

class ParticipacionScreen extends ConsumerStatefulWidget {
  const ParticipacionScreen({super.key});

  @override
  ConsumerState<ParticipacionScreen> createState() =>
      _ParticipacionScreenState();
}

class _ParticipacionScreenState extends ConsumerState<ParticipacionScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<dynamic> _votaciones = [];
  List<dynamic> _propuestas = [];
  bool _cargandoVotaciones = true;
  bool _cargandoPropuestas = true;
  String? _errorVotaciones;
  String? _errorPropuestas;
  String _filtroPropuestas = '';

  final List<Map<String, String>> _filtrosPropuestas = [
    {'id': '', 'label': 'Todas'},
    {'id': 'pendiente', 'label': 'Pendientes'},
    {'id': 'aprobada', 'label': 'Aprobadas'},
    {'id': 'en_debate', 'label': 'En debate'},
    {'id': 'rechazada', 'label': 'Rechazadas'},
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _cargarVotaciones();
    _cargarPropuestas();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarVotaciones() async {
    setState(() {
      _cargandoVotaciones = true;
      _errorVotaciones = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/participacion/procesos');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _votaciones =
              respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
          _cargandoVotaciones = false;
        });
      } else {
        setState(() {
          _errorVotaciones =
              respuesta.error ?? 'Error al cargar las votaciones';
          _cargandoVotaciones = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _errorVotaciones = excepcion.toString();
        _cargandoVotaciones = false;
      });
    }
  }

  Future<void> _cargarPropuestas() async {
    setState(() {
      _cargandoPropuestas = true;
      _errorPropuestas = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final queryParams = <String, dynamic>{
        'limite': 50,
        if (_filtroPropuestas.isNotEmpty) 'estado': _filtroPropuestas,
      };
      final respuesta = await clienteApi.get('/participacion/propuestas',
          queryParameters: queryParams);
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _propuestas =
              respuesta.data!['propuestas'] ?? respuesta.data!['data'] ?? [];
          _cargandoPropuestas = false;
        });
      } else {
        setState(() {
          _errorPropuestas =
              respuesta.error ?? 'Error al cargar las propuestas';
          _cargandoPropuestas = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _errorPropuestas = excepcion.toString();
        _cargandoPropuestas = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Participación Ciudadana'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(icon: Icon(Icons.how_to_vote), text: 'Votaciones'),
            Tab(icon: Icon(Icons.lightbulb_outline), text: 'Propuestas'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildVotacionesTab(),
          _buildPropuestasTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearPropuesta,
        icon: const Icon(Icons.add),
        label: const Text('Nueva propuesta'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
      ),
    );
  }

  Widget _buildVotacionesTab() {
    if (_cargandoVotaciones) {
      return const FlavorLoadingState();
    }

    if (_errorVotaciones != null) {
      return FlavorErrorState(
        message: _errorVotaciones!,
        onRetry: _cargarVotaciones,
        icon: Icons.how_to_vote,
      );
    }

    if (_votaciones.isEmpty) {
      return const FlavorEmptyState(
        icon: Icons.how_to_vote,
        title: 'No hay votaciones activas',
        message: 'Las votaciones aparecerán aquí cuando estén disponibles',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarVotaciones,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _votaciones.length,
        itemBuilder: (context, indice) =>
            _VotacionCard(
              votacion: _votaciones[indice],
              onTap: () => _abrirVotacion(_votaciones[indice]),
            ),
      ),
    );
  }

  Widget _buildPropuestasTab() {
    return Column(
      children: [
        // Filtros
        Container(
          height: 50,
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            itemCount: _filtrosPropuestas.length,
            itemBuilder: (context, index) {
              final filtro = _filtrosPropuestas[index];
              return Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ChoiceChip(
                  label: Text(filtro['label']!),
                  selected: _filtroPropuestas == filtro['id'],
                  selectedColor: Colors.indigo.shade100,
                  onSelected: (_) {
                    setState(() => _filtroPropuestas = filtro['id']!);
                    _cargarPropuestas();
                  },
                ),
              );
            },
          ),
        ),

        // Lista
        Expanded(
          child: _cargandoPropuestas
              ? const FlavorLoadingState()
              : _errorPropuestas != null
                  ? FlavorErrorState(
                      message: _errorPropuestas!,
                      onRetry: _cargarPropuestas,
                      icon: Icons.lightbulb_outline,
                    )
                  : _propuestas.isEmpty
                      ? FlavorEmptyState(
                          icon: Icons.lightbulb_outline,
                          title: 'No hay propuestas',
                          message: _filtroPropuestas.isNotEmpty
                              ? 'Prueba con otro filtro'
                              : 'Sé el primero en crear una propuesta',
                          action: TextButton.icon(
                            onPressed: _crearPropuesta,
                            icon: const Icon(Icons.add),
                            label: const Text('Crear propuesta'),
                          ),
                        )
                      : RefreshIndicator(
                          onRefresh: _cargarPropuestas,
                          child: ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: _propuestas.length,
                            itemBuilder: (context, index) => _PropuestaCard(
                              propuesta: _propuestas[index],
                              onTap: () =>
                                  _abrirPropuesta(_propuestas[index]),
                            ),
                          ),
                        ),
        ),
      ],
    );
  }

  void _abrirVotacion(dynamic votacion) {
    final mapa = votacion as Map<String, dynamic>;
    final procesoId = mapa['id'];
    if (procesoId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => _VotacionDetalleScreen(procesoId: procesoId),
        ),
      ).then((_) => _cargarVotaciones());
    }
  }

  void _abrirPropuesta(dynamic propuesta) {
    final mapa = propuesta as Map<String, dynamic>;
    final propuestaId = mapa['id'];
    if (propuestaId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => _PropuestaDetalleScreen(propuestaId: propuestaId),
        ),
      ).then((_) => _cargarPropuestas());
    }
  }

  void _crearPropuesta() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => const _CrearPropuestaScreen(),
      ),
    ).then((creada) {
      if (creada == true) {
        _cargarPropuestas();
        _tabController.animateTo(1); // Ir a tab de propuestas
      }
    });
  }
}
