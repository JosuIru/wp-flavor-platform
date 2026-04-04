import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class ThemacleScreen extends ConsumerStatefulWidget {
  const ThemacleScreen({super.key});

  @override
  ConsumerState<ThemacleScreen> createState() => _ThemacleScreenState();
}

class _ThemacleScreenState extends ConsumerState<ThemacleScreen> {
  List<dynamic> _modelos = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/themacle');
      if (response.success && response.data != null) {
        setState(() {
          _modelos = response.data!['modelos'] ??
              response.data!['models'] ??
              response.data!['items'] ??
              response.data!['data'] ??
              [];
          _loading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar modelos ML';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Tema ML'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _loading
          ? const FlavorLoadingState()
          : _error != null
              ? FlavorErrorState(
                  message: _error!,
                  onRetry: _loadData,
                  icon: Icons.auto_awesome,
                )
              : _modelos.isEmpty
                  ? const FlavorEmptyState(
                      icon: Icons.auto_awesome,
                      title: 'No hay modelos ML disponibles',
                      message: 'Los modelos de Machine Learning apareceran aqui',
                    )
                  : RefreshIndicator(
                      onRefresh: _loadData,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _modelos.length,
                        itemBuilder: (context, index) =>
                            _buildModeloCard(_modelos[index]),
                      ),
                    ),
      floatingActionButton: FloatingActionButton(
        onPressed: _nuevaPrediccion,
        child: const Icon(Icons.play_arrow),
      ),
    );
  }

  void _nuevaPrediccion() {
    final inputController = TextEditingController();
    String? modeloSeleccionado;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.auto_awesome, color: Colors.purple),
                  const SizedBox(width: 12),
                  const Text(
                    'Nueva Prediccion',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              if (_modelos.isNotEmpty) ...[
                DropdownButtonFormField<String>(
                  value: modeloSeleccionado,
                  decoration: const InputDecoration(
                    labelText: 'Seleccionar modelo',
                    prefixIcon: Icon(Icons.auto_awesome),
                    border: OutlineInputBorder(),
                  ),
                  hint: const Text('Elige un modelo'),
                  items: _modelos.map((modelo) {
                    final modeloMap = modelo as Map<String, dynamic>;
                    final nombre = modeloMap['nombre'] ?? modeloMap['name'] ?? 'Modelo';
                    final id = modeloMap['id']?.toString() ?? nombre.toString();
                    return DropdownMenuItem<String>(
                      value: id,
                      child: Text(nombre.toString()),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setModalState(() => modeloSeleccionado = value);
                  },
                ),
                const SizedBox(height: 16),
              ],
              TextFormField(
                controller: inputController,
                decoration: const InputDecoration(
                  labelText: 'Datos de entrada',
                  prefixIcon: Icon(Icons.input),
                  border: OutlineInputBorder(),
                  hintText: 'Introduce los datos para la prediccion...',
                ),
                maxLines: 3,
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () async {
                    if (inputController.text.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Introduce datos para la prediccion')),
                      );
                      return;
                    }
                    Navigator.pop(context);
                    await _ejecutarPrediccion(
                      modeloId: modeloSeleccionado,
                      inputData: inputController.text,
                    );
                  },
                  icon: const Icon(Icons.play_arrow),
                  label: const Text('Ejecutar prediccion'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _ejecutarPrediccion({String? modeloId, required String inputData}) async {
    final scaffoldMessenger = ScaffoldMessenger.of(context);

    // Mostrar indicador de carga
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(
        child: Card(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                FlavorInlineSpinner(size: 28),
                SizedBox(height: 16),
                Text('Ejecutando prediccion...'),
              ],
            ),
          ),
        ),
      ),
    );

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post('/themacle/predict', data: {
        if (modeloId != null) 'modelo_id': modeloId,
        'input': inputData,
      });

      if (mounted) Navigator.pop(context); // Cerrar diálogo de carga

      if (response.success && response.data != null) {
        final resultado = response.data!['resultado'] ?? response.data!['result'] ?? response.data!['prediction'];
        final confianza = response.data!['confianza'] ?? response.data!['confidence'] ?? 0;

        if (mounted) {
          _mostrarResultadoPrediccion(resultado, confianza);
        }
      } else {
        throw Exception(response.error ?? 'Error en la prediccion');
      }
    } catch (e) {
      if (mounted) {
        Navigator.pop(context); // Cerrar diálogo de carga si hay error
        scaffoldMessenger.showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _mostrarResultadoPrediccion(dynamic resultado, dynamic confianza) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.check_circle, color: Colors.green),
            SizedBox(width: 8),
            Text('Resultado'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Card(
              color: Colors.purple.withOpacity(0.1),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Prediccion:', style: TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    Text(
                      resultado.toString(),
                      style: const TextStyle(fontSize: 18),
                    ),
                  ],
                ),
              ),
            ),
            if (confianza is num && confianza > 0) ...[
              const SizedBox(height: 12),
              Row(
                children: [
                  const Icon(Icons.analytics, size: 16, color: Colors.blue),
                  const SizedBox(width: 4),
                  Text(
                    'Confianza: ${(confianza * 100).toStringAsFixed(1)}%',
                    style: const TextStyle(color: Colors.blue),
                  ),
                ],
              ),
            ],
          ],
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

  void _ejecutarModelo(Map<String, dynamic> modelo) {
    final nombre = modelo['nombre'] ?? modelo['name'] ?? 'Modelo';
    final modeloId = modelo['id']?.toString();
    final inputController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: MediaQuery.of(context).viewInsets.bottom + 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.purple.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.auto_awesome, color: Colors.purple),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Ejecutar Modelo',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      Text(
                        nombre.toString(),
                        style: TextStyle(color: Colors.grey.shade600, fontSize: 14),
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: inputController,
              decoration: const InputDecoration(
                labelText: 'Datos de entrada',
                prefixIcon: Icon(Icons.input),
                border: OutlineInputBorder(),
                hintText: 'Introduce los datos para procesar...',
              ),
              maxLines: 4,
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: () async {
                  if (inputController.text.isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Introduce los datos de entrada')),
                    );
                    return;
                  }
                  Navigator.pop(context);
                  await _ejecutarPrediccion(
                    modeloId: modeloId,
                    inputData: inputController.text,
                  );
                },
                icon: const Icon(Icons.play_arrow),
                label: const Text('Procesar'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildModeloCard(dynamic item) {
    final modeloMap = item as Map<String, dynamic>;
    final nombre = modeloMap['nombre'] ??
        modeloMap['name'] ??
        modeloMap['titulo'] ??
        modeloMap['title'] ??
        'Modelo ML';
    final descripcion = modeloMap['descripcion'] ??
        modeloMap['description'] ??
        '';
    final tipo = modeloMap['tipo'] ??
        modeloMap['type'] ??
        modeloMap['categoria'] ??
        'General';
    final precision = modeloMap['precision'] ??
        modeloMap['accuracy'] ??
        modeloMap['score'] ??
        0;
    final estado = modeloMap['estado'] ??
        modeloMap['status'] ??
        'activo';

    Color estadoColor;
    switch (estado.toString().toLowerCase()) {
      case 'activo':
      case 'active':
      case 'entrenado':
        estadoColor = Colors.green;
        break;
      case 'entrenando':
      case 'training':
        estadoColor = Colors.orange;
        break;
      case 'error':
      case 'failed':
        estadoColor = Colors.red;
        break;
      default:
        estadoColor = Colors.grey;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.purple.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.auto_awesome, color: Colors.purple),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        nombre.toString(),
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade200,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              tipo.toString(),
                              style: const TextStyle(fontSize: 11),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(
                              color: estadoColor,
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            estado.toString(),
                            style: TextStyle(
                              fontSize: 11,
                              color: estadoColor,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (descripcion.toString().isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(
                descripcion.toString(),
                style: TextStyle(color: Colors.grey[600]),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                if (precision is num && precision > 0)
                  Row(
                    children: [
                      const Icon(Icons.analytics, size: 16, color: Colors.blue),
                      const SizedBox(width: 4),
                      Text(
                        'Precision: ${(precision * 100).toStringAsFixed(1)}%',
                        style: const TextStyle(
                          fontSize: 12,
                          color: Colors.blue,
                        ),
                      ),
                    ],
                  ),
                ElevatedButton.icon(
                  onPressed: () => _ejecutarModelo(modeloMap),
                  icon: const Icon(Icons.play_arrow, size: 18),
                  label: const Text('Ejecutar'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
