import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

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
                ? const FlavorLoadingState()
                : _especies.isEmpty
                    ? FlavorEmptyState(
                        icon: Icons.eco_outlined,
                        title: 'No hay especies registradas',
                        action: TextButton.icon(
                          onPressed: _registrarAvistamiento,
                          icon: const Icon(Icons.add),
                          label: const Text('Registrar la primera'),
                        ),
                      )
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
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Text(
                    'Mapa de Avistamientos',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: _avistamientos.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.map_outlined, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay avistamientos registrados'),
                          const SizedBox(height: 8),
                          TextButton.icon(
                            onPressed: () {
                              Navigator.pop(context);
                              _registrarAvistamiento();
                            },
                            icon: const Icon(Icons.add),
                            label: const Text('Registrar el primero'),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      controller: scrollController,
                      padding: const EdgeInsets.all(16),
                      itemCount: _avistamientos.length,
                      itemBuilder: (context, index) {
                        final avistamiento = _avistamientos[index];
                        return Card(
                          margin: const EdgeInsets.only(bottom: 12),
                          child: ListTile(
                            leading: CircleAvatar(
                              backgroundColor: Colors.green.shade100,
                              child: Icon(Icons.location_on, color: Colors.green.shade600),
                            ),
                            title: Text(avistamiento['especie_nombre'] ?? 'Especie desconocida'),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(avistamiento['ubicacion'] ?? ''),
                                Text(
                                  avistamiento['fecha'] ?? '',
                                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                            isThreeLine: true,
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () {
                              // Ver detalle del avistamiento
                              Navigator.pop(context);
                              _verDetalleAvistamiento(avistamiento);
                            },
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  void _verDetalleAvistamiento(Map<String, dynamic> avistamiento) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(avistamiento['especie_nombre'] ?? 'Avistamiento'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if ((avistamiento['imagen'] ?? '').isNotEmpty)
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    avistamiento['imagen'],
                    height: 150,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      height: 100,
                      color: Colors.grey.shade200,
                      child: const Icon(Icons.image_not_supported),
                    ),
                  ),
                ),
              const SizedBox(height: 16),
              _buildInfoRow(Icons.location_on, 'Ubicacion', avistamiento['ubicacion'] ?? 'No especificada'),
              _buildInfoRow(Icons.calendar_today, 'Fecha', avistamiento['fecha'] ?? ''),
              _buildInfoRow(Icons.person, 'Observador', avistamiento['observador'] ?? 'Anonimo'),
              if ((avistamiento['notas'] ?? '').isNotEmpty)
                _buildInfoRow(Icons.note, 'Notas', avistamiento['notas']),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cerrar'),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: Colors.grey.shade600),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                Text(value),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _registrarAvistamiento({String? especieId}) {
    final ubicacionController = TextEditingController();
    final notasController = TextEditingController();
    String? especieSeleccionadaId = especieId;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
            left: 20,
            right: 20,
            top: 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.add_a_photo, color: Colors.green),
                    const SizedBox(width: 12),
                    const Text(
                      'Registrar Avistamiento',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                // Selector de especie
                DropdownButtonFormField<String>(
                  value: especieSeleccionadaId,
                  decoration: const InputDecoration(
                    labelText: 'Especie',
                    prefixIcon: Icon(Icons.eco),
                    border: OutlineInputBorder(),
                  ),
                  items: _especies.map((especie) {
                    return DropdownMenuItem<String>(
                      value: especie['id']?.toString(),
                      child: Text(especie['nombre'] ?? 'Sin nombre'),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setModalState(() => especieSeleccionadaId = value);
                  },
                  hint: const Text('Selecciona una especie'),
                ),
                const SizedBox(height: 16),
                // Ubicacion
                TextFormField(
                  controller: ubicacionController,
                  decoration: const InputDecoration(
                    labelText: 'Ubicacion',
                    prefixIcon: Icon(Icons.location_on),
                    border: OutlineInputBorder(),
                    hintText: 'Ej: Parque Central, zona norte',
                  ),
                ),
                const SizedBox(height: 16),
                // Notas
                TextFormField(
                  controller: notasController,
                  decoration: const InputDecoration(
                    labelText: 'Notas (opcional)',
                    prefixIcon: Icon(Icons.note),
                    border: OutlineInputBorder(),
                    hintText: 'Describe lo que observaste...',
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 24),
                // Boton de enviar
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () async {
                      if (especieSeleccionadaId == null) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Selecciona una especie')),
                        );
                        return;
                      }
                      Navigator.pop(context);
                      await _enviarAvistamiento(
                        especieId: especieSeleccionadaId!,
                        ubicacion: ubicacionController.text,
                        notas: notasController.text,
                      );
                    },
                    icon: const Icon(Icons.send),
                    label: const Text('Registrar avistamiento'),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _enviarAvistamiento({
    required String especieId,
    required String ubicacion,
    required String notas,
  }) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/biodiversidad/avistamientos', data: {
        'especie_id': especieId,
        'ubicacion': ubicacion,
        'notas': notas,
        'fecha': DateTime.now().toIso8601String(),
      });

      if (response.success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Avistamiento registrado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al registrar');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}
