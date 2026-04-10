import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_contact_launcher.dart';
import '../../../core/utils/map_launch_helper.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';

part 'eventos_screen_parts.dart';

class EventosScreen extends ConsumerStatefulWidget {
  const EventosScreen({super.key});

  @override
  ConsumerState<EventosScreen> createState() => _EventosScreenState();
}

class _EventosScreenState extends ConsumerState<EventosScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<_Evento> _eventos = [];
  bool _cargando = true;
  String _filtroCategoria = '';
  String _filtroFecha = '';

  final List<String> _categorias = [
    '',
    'cultural',
    'deportivo',
    'social',
    'formativo',
    'festivo',
    'solidario',
  ];

  final List<Map<String, String>> _filtrosFecha = [
    {'id': '', 'label': 'Todos'},
    {'id': 'hoy', 'label': 'Hoy'},
    {'id': 'semana', 'label': 'Esta semana'},
    {'id': 'mes', 'label': 'Este mes'},
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _cargarEventos();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarEventos() async {
    setState(() => _cargando = true);
    try {
      final api = ref.read(apiClientProvider);
      final queryParams = <String, dynamic>{
        'limite': 50,
        if (_filtroCategoria.isNotEmpty) 'categoria': _filtroCategoria,
        if (_filtroFecha.isNotEmpty) 'fecha': _filtroFecha,
      };
      final response = await api.get('/eventos', queryParameters: queryParams);

      if (response.success && response.data != null) {
        final items = response.data!['data'] as List<dynamic>? ??
            response.data!['eventos'] as List<dynamic>? ??
            [];
        setState(() {
          _eventos = items.map((json) => _Evento.fromJson(json)).toList();
        });
      }
    } finally {
      setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Eventos'),
        backgroundColor: Colors.deepPurple,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(icon: Icon(Icons.list), text: 'Lista'),
            Tab(icon: Icon(Icons.calendar_month), text: 'Calendario'),
          ],
        ),
      ),
      body: Column(
        children: [
          // Filtros
          _buildFiltros(),

          // Contenido
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildListaTab(),
                _buildCalendarioTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFiltros() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Column(
        children: [
          // Filtros de fecha
          SizedBox(
            height: 40,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              children: _filtrosFecha.map((filtro) {
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(filtro['label']!),
                    selected: _filtroFecha == filtro['id'],
                    selectedColor: Colors.deepPurple.shade100,
                    onSelected: (_) {
                      setState(() => _filtroFecha = filtro['id']!);
                      _cargarEventos();
                    },
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 4),
          // Filtros de categoria
          SizedBox(
            height: 40,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              children: _categorias.map((cat) {
                final label = cat.isEmpty ? 'Todas' : cat[0].toUpperCase() + cat.substring(1);
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilterChip(
                    label: Text(label),
                    selected: _filtroCategoria == cat,
                    selectedColor: Colors.deepPurple.shade100,
                    avatar: cat.isNotEmpty
                        ? Icon(_getIconForCategoria(cat), size: 16)
                        : null,
                    onSelected: (_) {
                      setState(() => _filtroCategoria = cat);
                      _cargarEventos();
                    },
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildListaTab() {
    if (_cargando) {
      return const FlavorLoadingState();
    }

    if (_eventos.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.event_busy,
        title: 'No hay eventos',
        message: _filtroCategoria.isNotEmpty || _filtroFecha.isNotEmpty
            ? 'Prueba con otros filtros'
            : 'No hay eventos programados',
        action: TextButton.icon(
          onPressed: () {
            setState(() {
              _filtroCategoria = '';
              _filtroFecha = '';
            });
            _cargarEventos();
          },
          icon: const Icon(Icons.refresh),
          label: const Text('Quitar filtros'),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarEventos,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _eventos.length,
        itemBuilder: (context, index) {
          return _EventoCard(
            evento: _eventos[index],
            onTap: () => _abrirDetalle(_eventos[index]),
          );
        },
      ),
    );
  }

  Widget _buildCalendarioTab() {
    if (_cargando) {
      return const FlavorLoadingState();
    }

    // Agrupar eventos por fecha
    final eventosAgrupadosPorFecha = <String, List<_Evento>>{};
    for (final evento in _eventos) {
      final fechaKey = evento.fechaInicio?.toString().split(' ').first ?? 'Sin fecha';
      eventosAgrupadosPorFecha.putIfAbsent(fechaKey, () => []).add(evento);
    }

    final fechasOrdenadas = eventosAgrupadosPorFecha.keys.toList()..sort();

    if (fechasOrdenadas.isEmpty) {
      return const FlavorEmptyState(
        icon: Icons.calendar_today,
        title: 'Sin eventos en el calendario',
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarEventos,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: fechasOrdenadas.length,
        itemBuilder: (context, index) {
          final fecha = fechasOrdenadas[index];
          final eventosDelDia = eventosAgrupadosPorFecha[fecha]!;

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header de fecha
              Container(
                padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                margin: const EdgeInsets.only(bottom: 8),
                decoration: BoxDecoration(
                  color: Colors.deepPurple.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.calendar_today, color: Colors.deepPurple.shade700, size: 20),
                    const SizedBox(width: 8),
                    Text(
                      _formatearFechaHeader(fecha),
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.deepPurple.shade700,
                      ),
                    ),
                    const Spacer(),
                    Text(
                      '${eventosDelDia.length} evento${eventosDelDia.length > 1 ? 's' : ''}',
                      style: TextStyle(color: Colors.deepPurple.shade400),
                    ),
                  ],
                ),
              ),
              // Eventos del dia
              ...eventosDelDia.map((evento) => _EventoMiniCard(
                    evento: evento,
                    onTap: () => _abrirDetalle(evento),
                  )),
              const SizedBox(height: 16),
            ],
          );
        },
      ),
    );
  }

  String _formatearFechaHeader(String fecha) {
    try {
      final partes = fecha.split('-');
      if (partes.length >= 3) {
        final dia = int.parse(partes[2]);
        final mes = int.parse(partes[1]);
        final anio = int.parse(partes[0]);
        final fechaDateTime = DateTime(anio, mes, dia);
        final diasSemana = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
        final meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return '${diasSemana[fechaDateTime.weekday - 1]}, $dia ${meses[mes - 1]}';
      }
    } catch (_) {}
    return fecha;
  }

  IconData _getIconForCategoria(String categoria) {
    switch (categoria.toLowerCase()) {
      case 'cultural':
        return Icons.theater_comedy;
      case 'deportivo':
        return Icons.sports_soccer;
      case 'social':
        return Icons.groups;
      case 'formativo':
        return Icons.school;
      case 'festivo':
        return Icons.celebration;
      case 'solidario':
        return Icons.volunteer_activism;
      default:
        return Icons.event;
    }
  }

  void _abrirDetalle(_Evento evento) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _EventoDetalleScreen(evento: evento),
      ),
    ).then((_) => _cargarEventos());
  }
}
