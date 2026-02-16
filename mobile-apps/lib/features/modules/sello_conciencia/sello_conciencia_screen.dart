import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class SelloConcienciaScreen extends ConsumerStatefulWidget {
  const SelloConcienciaScreen({super.key});

  @override
  ConsumerState<SelloConcienciaScreen> createState() => _SelloConcienciaScreenState();
}

class _SelloConcienciaScreenState extends ConsumerState<SelloConcienciaScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _negocios = [];
  List<Map<String, dynamic>> _criterios = [];
  Map<String, dynamic>? _miSolicitud;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/sello-conciencia/dashboard');
      if (response.success && response.data != null) {
        setState(() {
          _negocios = (response.data!['negocios_certificados'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _criterios = (response.data!['criterios'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _miSolicitud = response.data!['mi_solicitud'];
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
        title: const Text('Sello Conciencia'),
        actions: [
          IconButton(icon: const Icon(Icons.info_outline), onPressed: _mostrarInfo),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildBanner(),
                    if (_miSolicitud != null) ...[
                      const SizedBox(height: 16),
                      _buildMiSolicitud(),
                    ],
                    const SizedBox(height: 24),
                    _buildNegociosCertificados(),
                    const SizedBox(height: 24),
                    _buildCriterios(),
                  ],
                ),
              ),
            ),
      floatingActionButton: _miSolicitud == null
          ? FloatingActionButton.extended(
              onPressed: _solicitarSello,
              icon: const Icon(Icons.verified),
              label: const Text('Solicitar sello'),
            )
          : null,
    );
  }

  Widget _buildBanner() {
    return Card(
      color: Colors.green.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: Colors.green.shade100, shape: BoxShape.circle),
              child: Icon(Icons.verified, size: 32, color: Colors.green.shade700),
            ),
            const SizedBox(width: 16),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Sello Conciencia', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  Text('Certificacion para negocios sostenibles y responsables'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMiSolicitud() {
    final estado = _miSolicitud!['estado'] ?? '';
    final Color estadoColor;
    switch (estado.toLowerCase()) {
      case 'aprobada':
        estadoColor = Colors.green;
        break;
      case 'pendiente':
        estadoColor = Colors.orange;
        break;
      case 'rechazada':
        estadoColor = Colors.red;
        break;
      default:
        estadoColor = Colors.grey;
    }

    return Card(
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: estadoColor.withOpacity(0.2),
          child: Icon(Icons.assignment, color: estadoColor),
        ),
        title: const Text('Mi solicitud'),
        subtitle: Text('Estado: $estado'),
        trailing: Chip(
          label: Text(estado),
          backgroundColor: estadoColor.withOpacity(0.2),
          labelStyle: TextStyle(color: estadoColor),
        ),
      ),
    );
  }

  Widget _buildNegociosCertificados() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Negocios certificados', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            TextButton(onPressed: () {}, child: const Text('Ver todos')),
          ],
        ),
        const SizedBox(height: 12),
        if (_negocios.isEmpty)
          const Text('No hay negocios certificados aun')
        else
          ..._negocios.take(5).map((n) => _buildNegocioCard(n)),
      ],
    );
  }

  Widget _buildNegocioCard(Map<String, dynamic> negocio) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.green.shade100,
          child: Icon(Icons.store, color: Colors.green.shade600),
        ),
        title: Text(negocio['nombre'] ?? ''),
        subtitle: Text(negocio['categoria'] ?? ''),
        trailing: const Icon(Icons.verified, color: Colors.green),
        onTap: () {},
      ),
    );
  }

  Widget _buildCriterios() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Criterios de certificacion', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_criterios.isEmpty)
          const Text('Cargando criterios...')
        else
          ..._criterios.map((c) => ListTile(
                leading: Icon(Icons.check_circle, color: Colors.green.shade400),
                title: Text(c['titulo'] ?? ''),
                subtitle: Text(c['descripcion'] ?? ''),
                contentPadding: EdgeInsets.zero,
              )),
      ],
    );
  }

  void _mostrarInfo() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sello Conciencia'),
        content: const Text(
          'El Sello Conciencia certifica a negocios que cumplen con criterios de '
          'sostenibilidad ambiental, responsabilidad social y comercio justo.',
        ),
        actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Entendido'))],
      ),
    );
  }

  void _solicitarSello() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Formulario de solicitud - proximamente')),
    );
  }
}
