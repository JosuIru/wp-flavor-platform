import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

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
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.auto_awesome,
                          size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_error!, textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadData,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _modelos.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.auto_awesome,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay modelos ML disponibles'),
                          const SizedBox(height: 8),
                          const Text(
                            'Los modelos de Machine Learning apareceran aqui',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ],
                      ),
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
        onPressed: () {
          // TODO: Implementar nueva prediccion
        },
        child: const Icon(Icons.play_arrow),
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
                  onPressed: () {
                    // TODO: Ejecutar modelo
                  },
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
