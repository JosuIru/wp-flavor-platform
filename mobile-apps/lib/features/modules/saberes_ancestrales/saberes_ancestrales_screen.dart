import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class SaberesAncestralesScreen extends ConsumerStatefulWidget {
  const SaberesAncestralesScreen({super.key});

  @override
  ConsumerState<SaberesAncestralesScreen> createState() => _SaberesAncestralesScreenState();
}

class _SaberesAncestralesScreenState extends ConsumerState<SaberesAncestralesScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _saberes = [];
  List<Map<String, dynamic>> _talleres = [];
  String _categoriaSeleccionada = 'todos';

  final _categorias = ['todos', 'medicina', 'agricultura', 'artesania', 'rituales', 'gastronomia'];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/saberes-ancestrales/catalogo', queryParameters: {
        if (_categoriaSeleccionada != 'todos') 'categoria': _categoriaSeleccionada,
      });
      if (response.success && response.data != null) {
        setState(() {
          _saberes = (response.data!['saberes'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _talleres = (response.data!['talleres'] as List<dynamic>? ?? [])
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
      appBar: AppBar(title: const Text('Saberes Ancestrales')),
      body: Column(
        children: [
          _buildCategoriaFilter(),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _loadData,
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (_talleres.isNotEmpty) ...[
                            _buildTalleresSection(),
                            const SizedBox(height: 24),
                          ],
                          _buildSaberesSection(),
                        ],
                      ),
                    ),
                  ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _compartirSaber,
        icon: const Icon(Icons.add),
        label: const Text('Compartir saber'),
      ),
    );
  }

  Widget _buildCategoriaFilter() {
    return Container(
      height: 50,
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        itemCount: _categorias.length,
        itemBuilder: (context, index) {
          final cat = _categorias[index];
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: FilterChip(
              label: Text(cat[0].toUpperCase() + cat.substring(1)),
              selected: _categoriaSeleccionada == cat,
              onSelected: (_) {
                setState(() => _categoriaSeleccionada = cat);
                _loadData();
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildTalleresSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Talleres proximos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        SizedBox(
          height: 160,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: _talleres.length,
            itemBuilder: (context, index) => _buildTallerCard(_talleres[index]),
          ),
        ),
      ],
    );
  }

  Widget _buildTallerCard(Map<String, dynamic> taller) {
    return Card(
      margin: const EdgeInsets.only(right: 12),
      clipBehavior: Clip.antiAlias,
      child: Container(
        width: 200,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              height: 80,
              color: Colors.brown.shade100,
              child: Center(child: Icon(Icons.auto_stories, size: 40, color: Colors.brown.shade400)),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(taller['titulo'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold), maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 4),
                  Text(taller['fecha'] ?? '', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSaberesSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Saberes documentados', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_saberes.isEmpty)
          Center(
            child: Column(
              children: [
                Icon(Icons.auto_stories, size: 64, color: Colors.grey.shade400),
                const SizedBox(height: 16),
                const Text('No hay saberes en esta categoria'),
              ],
            ),
          )
        else
          ..._saberes.map((s) => _buildSaberCard(s)),
      ],
    );
  }

  Widget _buildSaberCard(Map<String, dynamic> saber) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.brown.shade100,
          child: Icon(Icons.elderly, color: Colors.brown.shade600),
        ),
        title: Text(saber['titulo'] ?? ''),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(saber['portador'] ?? '', style: TextStyle(color: Colors.grey.shade600)),
            if ((saber['categoria'] ?? '').isNotEmpty)
              Chip(
                label: Text(saber['categoria']),
                labelStyle: const TextStyle(fontSize: 10),
                padding: EdgeInsets.zero,
                visualDensity: VisualDensity.compact,
              ),
          ],
        ),
        isThreeLine: true,
        onTap: () => _verDetalle(saber),
      ),
    );
  }

  void _compartirSaber() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Formulario para compartir saber - proximamente')),
    );
  }

  void _verDetalle(Map<String, dynamic> saber) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Detalle de ${saber['titulo']} - proximamente')),
    );
  }
}
