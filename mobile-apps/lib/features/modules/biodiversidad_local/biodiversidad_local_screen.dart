import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class BiodiversidadLocalScreen extends ConsumerStatefulWidget {
  const BiodiversidadLocalScreen({super.key});

  @override
  ConsumerState<BiodiversidadLocalScreen> createState() => _BiodiversidadLocalScreenState();
}

class _BiodiversidadLocalScreenState extends ConsumerState<BiodiversidadLocalScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _especies = [];
  List<Map<String, dynamic>> _avistamientos = [];
  String _categoriaSeleccionada = 'todas';

  final _categorias = [
    {'id': 'todas', 'nombre': 'Todas', 'icono': Icons.eco},
    {'id': 'flora', 'nombre': 'Flora', 'icono': Icons.local_florist},
    {'id': 'fauna', 'nombre': 'Fauna', 'icono': Icons.pets},
    {'id': 'aves', 'nombre': 'Aves', 'icono': Icons.flutter_dash},
    {'id': 'insectos', 'nombre': 'Insectos', 'icono': Icons.bug_report},
  ];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/biodiversidad/especies', queryParameters: {
        if (_categoriaSeleccionada != 'todas') 'categoria': _categoriaSeleccionada,
      });

      if (response.success && response.data != null) {
        setState(() {
          _especies = (response.data!['especies'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _avistamientos = (response.data!['avistamientos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Biodiversidad Local'),
        actions: [
          IconButton(
            icon: const Icon(Icons.map),
            onPressed: _showMapaAvistamientos,
            tooltip: 'Mapa de avistamientos',
          ),
        ],
      ),
      body: Column(
        children: [
          _buildCategoriaFilter(),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _especies.isEmpty
                    ? _buildEmptyState()
                    : RefreshIndicator(
                        onRefresh: _loadData,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _especies.length,
                          itemBuilder: (context, index) => _buildEspecieCard(_especies[index]),
                        ),
                      ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _registrarAvistamiento,
        icon: const Icon(Icons.add_a_photo),
        label: const Text('Registrar avistamiento'),
      ),
    );
  }

  Widget _buildCategoriaFilter() {
    return Container(
      height: 60,
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        itemCount: _categorias.length,
        itemBuilder: (context, index) {
          final cat = _categorias[index];
          final isSelected = _categoriaSeleccionada == cat['id'];
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: FilterChip(
              avatar: Icon(cat['icono'] as IconData, size: 18),
              label: Text(cat['nombre'] as String),
              selected: isSelected,
              onSelected: (_) {
                setState(() => _categoriaSeleccionada = cat['id'] as String);
                _loadData();
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildEspecieCard(Map<String, dynamic> especie) {
    final nombre = especie['nombre'] ?? 'Especie desconocida';
    final nombreCientifico = especie['nombre_cientifico'] ?? '';
    final categoria = especie['categoria'] ?? '';
    final estado = especie['estado_conservacion'] ?? '';
    final imagen = especie['imagen'] ?? '';
    final avistamientos = especie['total_avistamientos'] ?? 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => _showDetalleEspecie(especie),
        child: Row(
          children: [
            Container(
              width: 100,
              height: 100,
              color: Colors.green.shade100,
              child: imagen.isNotEmpty
                  ? Image.network(imagen, fit: BoxFit.cover)
                  : Icon(Icons.eco, size: 48, color: Colors.green.shade400),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(nombre, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    if (nombreCientifico.isNotEmpty)
                      Text(nombreCientifico, style: TextStyle(fontStyle: FontStyle.italic, color: Colors.grey.shade600)),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        if (estado.isNotEmpty)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: _getEstadoColor(estado).withOpacity(0.2),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(estado, style: TextStyle(fontSize: 11, color: _getEstadoColor(estado))),
                          ),
                        const Spacer(),
                        Icon(Icons.visibility, size: 14, color: Colors.grey.shade500),
                        const SizedBox(width: 4),
                        Text('$avistamientos', style: TextStyle(color: Colors.grey.shade600)),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.eco_outlined, size: 64, color: Colors.grey.shade400),
          const SizedBox(height: 16),
          Text('No hay especies registradas', style: TextStyle(color: Colors.grey.shade600)),
          const SizedBox(height: 8),
          TextButton.icon(
            onPressed: _registrarAvistamiento,
            icon: const Icon(Icons.add),
            label: const Text('Registrar la primera'),
          ),
        ],
      ),
    );
  }

  Color _getEstadoColor(String estado) {
    switch (estado.toLowerCase()) {
      case 'en peligro':
      case 'peligro critico':
        return Colors.red;
      case 'vulnerable':
        return Colors.orange;
      case 'preocupacion menor':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  void _showDetalleEspecie(Map<String, dynamic> especie) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(especie['nombre'] ?? '', style: Theme.of(context).textTheme.headlineSmall),
              if ((especie['nombre_cientifico'] ?? '').isNotEmpty)
                Text(especie['nombre_cientifico'], style: const TextStyle(fontStyle: FontStyle.italic)),
              const SizedBox(height: 16),
              Text(especie['descripcion'] ?? 'Sin descripcion disponible'),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: () {
                  Navigator.pop(context);
                  _registrarAvistamiento(especieId: especie['id']?.toString());
                },
                icon: const Icon(Icons.add_a_photo),
                label: const Text('Registrar avistamiento'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showMapaAvistamientos() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Mapa de avistamientos - proximamente')),
    );
  }

  void _registrarAvistamiento({String? especieId}) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Formulario de avistamiento - proximamente')),
    );
  }
}
