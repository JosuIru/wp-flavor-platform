import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class HuellaEcologicaScreen extends ConsumerStatefulWidget {
  const HuellaEcologicaScreen({super.key});

  @override
  ConsumerState<HuellaEcologicaScreen> createState() => _HuellaEcologicaScreenState();
}

class _HuellaEcologicaScreenState extends ConsumerState<HuellaEcologicaScreen> {
  bool _isLoading = true;
  double _huellaTotal = 0;
  Map<String, double> _categorias = {};
  List<Map<String, dynamic>> _consejos = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/huella-ecologica/mi-huella');
      if (response.success && response.data != null) {
        setState(() {
          _huellaTotal = (response.data!['huella_total'] as num?)?.toDouble() ?? 0;
          _categorias = Map<String, double>.from(response.data!['categorias'] ?? {});
          _consejos = (response.data!['consejos'] as List<dynamic>? ?? [])
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
        title: const Text('Huella Ecologica'),
        actions: [
          IconButton(icon: const Icon(Icons.history), onPressed: _verHistorial),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _buildHuellaCard(),
                    const SizedBox(height: 24),
                    _buildCategoriasCard(),
                    const SizedBox(height: 24),
                    _buildConsejosCard(),
                  ],
                ),
              ),
            ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _calcularHuella,
        icon: const Icon(Icons.calculate),
        label: const Text('Calcular'),
      ),
    );
  }

  Widget _buildHuellaCard() {
    final color = _huellaTotal < 4 ? Colors.green : _huellaTotal < 6 ? Colors.orange : Colors.red;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Text('Tu huella ecologica', style: TextStyle(color: Colors.grey.shade600)),
            const SizedBox(height: 16),
            Stack(
              alignment: Alignment.center,
              children: [
                SizedBox(
                  width: 150,
                  height: 150,
                  child: CircularProgressIndicator(
                    value: _huellaTotal / 10,
                    strokeWidth: 12,
                    backgroundColor: Colors.grey.shade200,
                    color: color,
                  ),
                ),
                Column(
                  children: [
                    Text(
                      _huellaTotal.toStringAsFixed(1),
                      style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: color),
                    ),
                    const Text('toneladas CO2/ano'),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            Text(
              _huellaTotal < 4 ? 'Excelente!' : _huellaTotal < 6 ? 'Puedes mejorar' : 'Hay trabajo por hacer',
              style: TextStyle(fontWeight: FontWeight.w500, color: color),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCategoriasCard() {
    final categoriaIcons = {
      'transporte': Icons.directions_car,
      'hogar': Icons.home,
      'alimentacion': Icons.restaurant,
      'consumo': Icons.shopping_bag,
    };

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Por categoria', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 16),
            ..._categorias.entries.map((e) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Row(
                    children: [
                      Icon(categoriaIcons[e.key] ?? Icons.eco, color: Colors.teal),
                      const SizedBox(width: 12),
                      Expanded(child: Text(e.key[0].toUpperCase() + e.key.substring(1))),
                      Text('${e.value.toStringAsFixed(1)} t'),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }

  Widget _buildConsejosCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Consejos para reducir', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),
            if (_consejos.isEmpty)
              const Text('Calcula tu huella para obtener consejos personalizados')
            else
              ..._consejos.take(3).map((c) => ListTile(
                    leading: Icon(Icons.lightbulb, color: Colors.amber.shade600),
                    title: Text(c['titulo'] ?? ''),
                    subtitle: Text(c['descripcion'] ?? ''),
                    contentPadding: EdgeInsets.zero,
                  )),
          ],
        ),
      ),
    );
  }

  void _calcularHuella() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Calculadora de huella - proximamente')),
    );
  }

  void _verHistorial() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Historial de huella - proximamente')),
    );
  }
}
