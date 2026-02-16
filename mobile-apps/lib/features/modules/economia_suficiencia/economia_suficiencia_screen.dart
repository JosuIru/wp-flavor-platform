import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class EconomiaSuficienciaScreen extends ConsumerStatefulWidget {
  const EconomiaSuficienciaScreen({super.key});

  @override
  ConsumerState<EconomiaSuficienciaScreen> createState() => _EconomiaSuficienciaScreenState();
}

class _EconomiaSuficienciaScreenState extends ConsumerState<EconomiaSuficienciaScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _miProgreso;
  List<Map<String, dynamic>> _recursos = [];
  List<Map<String, dynamic>> _retos = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/economia-suficiencia/dashboard');
      if (response.success && response.data != null) {
        setState(() {
          _miProgreso = response.data!['mi_progreso'];
          _recursos = (response.data!['recursos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _retos = (response.data!['retos'] as List<dynamic>? ?? [])
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
      appBar: AppBar(title: const Text('Economia de Suficiencia')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildProgresoCard(),
                    const SizedBox(height: 24),
                    _buildSeccionRetos(),
                    const SizedBox(height: 24),
                    _buildSeccionRecursos(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildProgresoCard() {
    final nivel = _miProgreso?['nivel'] ?? 'Principiante';
    final puntos = _miProgreso?['puntos'] ?? 0;
    final retosCompletados = _miProgreso?['retos_completados'] ?? 0;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            CircleAvatar(
              radius: 40,
              backgroundColor: Colors.teal.shade100,
              child: Icon(Icons.eco, size: 40, color: Colors.teal.shade600),
            ),
            const SizedBox(height: 16),
            Text('Nivel: $nivel', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _buildStatItem(Icons.stars, '$puntos pts'),
                const SizedBox(width: 24),
                _buildStatItem(Icons.check_circle, '$retosCompletados retos'),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.teal),
        const SizedBox(width: 4),
        Text(text),
      ],
    );
  }

  Widget _buildSeccionRetos() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Retos activos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_retos.isEmpty)
          const Text('No hay retos disponibles')
        else
          ..._retos.take(3).map((reto) => _buildRetoCard(reto)),
      ],
    );
  }

  Widget _buildRetoCard(Map<String, dynamic> reto) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.amber.shade100,
          child: Icon(Icons.flag, color: Colors.amber.shade700),
        ),
        title: Text(reto['titulo'] ?? ''),
        subtitle: Text(reto['descripcion'] ?? ''),
        trailing: TextButton(onPressed: () {}, child: const Text('Unirse')),
      ),
    );
  }

  Widget _buildSeccionRecursos() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Recursos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_recursos.isEmpty)
          const Text('No hay recursos disponibles')
        else
          ..._recursos.take(5).map((recurso) => ListTile(
                leading: const Icon(Icons.article),
                title: Text(recurso['titulo'] ?? ''),
                subtitle: Text(recurso['tipo'] ?? ''),
                onTap: () {},
              )),
      ],
    );
  }
}
