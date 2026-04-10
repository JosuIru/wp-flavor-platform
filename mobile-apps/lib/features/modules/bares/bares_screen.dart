import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_contact_launcher.dart';
import '../../../core/utils/map_launch_helper.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';

part 'bares_screen_parts.dart';

class BaresScreen extends ConsumerStatefulWidget {
  const BaresScreen({super.key});

  @override
  ConsumerState<BaresScreen> createState() => _BaresScreenState();
}

class _BaresScreenState extends ConsumerState<BaresScreen> {
  List<_Bar> _bares = [];
  bool _cargando = true;
  String _filtroTipo = '';
  bool _soloAbiertos = false;

  final List<String> _tiposBar = [
    '',
    'cerveceria',
    'cocteleria',
    'taberna',
    'pub',
    'tapas',
    'terraza',
  ];

  @override
  void initState() {
    super.initState();
    _cargarBares();
  }

  Future<void> _cargarBares() async {
    setState(() => _cargando = true);
    try {
      final api = ref.read(apiClientProvider);
      final queryParams = <String, dynamic>{
        'limite': 50,
        if (_filtroTipo.isNotEmpty) 'tipo': _filtroTipo,
        if (_soloAbiertos) 'abierto': 'true',
      };
      final response = await api.get('/bares', queryParameters: queryParams);

      if (response.success && response.data != null) {
        final items = response.data!['bares'] as List<dynamic>? ?? [];
        setState(() {
          _bares = items.map((json) => _Bar.fromJson(json)).toList();
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
        title: const Text('Bares'),
        backgroundColor: Colors.amber.shade800,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: Icon(_soloAbiertos ? Icons.access_time_filled : Icons.access_time),
            onPressed: () {
              setState(() => _soloAbiertos = !_soloAbiertos);
              _cargarBares();
            },
            tooltip: _soloAbiertos ? 'Mostrar todos' : 'Solo abiertos',
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtros por tipo
          _buildFiltrosTipo(),

          // Lista de bares
          Expanded(
            child: _cargando
                ? const FlavorLoadingState()
                : _bares.isEmpty
                    ? FlavorEmptyState(
                        icon: Icons.local_bar_outlined,
                        title: 'No hay bares disponibles',
                        message: _filtroTipo.isNotEmpty || _soloAbiertos
                            ? 'Prueba con otros filtros'
                            : null,
                        action: TextButton.icon(
                          onPressed: () {
                            setState(() {
                              _filtroTipo = '';
                              _soloAbiertos = false;
                            });
                            _cargarBares();
                          },
                          icon: const Icon(Icons.refresh),
                          label: const Text('Quitar filtros'),
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _cargarBares,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _bares.length,
                          itemBuilder: (context, index) {
                            return _BarCard(
                              bar: _bares[index],
                              onTap: () => _abrirDetalle(_bares[index]),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildFiltrosTipo() {
    return Container(
      height: 50,
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        itemCount: _tiposBar.length,
        itemBuilder: (context, index) {
          final tipo = _tiposBar[index];
          final label = tipo.isEmpty ? 'Todos' : tipo[0].toUpperCase() + tipo.substring(1);
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: FilterChip(
              label: Text(label),
              selected: _filtroTipo == tipo,
              selectedColor: Colors.amber.shade200,
              onSelected: (_) {
                setState(() => _filtroTipo = tipo);
                _cargarBares();
              },
            ),
          );
        },
      ),
    );
  }

  void _abrirDetalle(_Bar bar) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _BarDetalleScreen(bar: bar),
      ),
    ).then((_) => _cargarBares());
  }
}
