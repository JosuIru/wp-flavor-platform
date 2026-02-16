import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class JusticiaRestaurativaScreen extends ConsumerStatefulWidget {
  const JusticiaRestaurativaScreen({super.key});

  @override
  ConsumerState<JusticiaRestaurativaScreen> createState() => _JusticiaRestaurativaScreenState();
}

class _JusticiaRestaurativaScreenState extends ConsumerState<JusticiaRestaurativaScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _procesos = [];
  List<Map<String, dynamic>> _mediadores = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/justicia-restaurativa/dashboard');
      if (response.success && response.data != null) {
        setState(() {
          _procesos = (response.data!['mis_procesos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _mediadores = (response.data!['mediadores'] as List<dynamic>? ?? [])
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
        title: const Text('Justicia Restaurativa'),
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
                    _buildIntroCard(),
                    const SizedBox(height: 24),
                    _buildMisProcesos(),
                    const SizedBox(height: 24),
                    _buildMediadores(),
                  ],
                ),
              ),
            ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _solicitarMediacion,
        icon: const Icon(Icons.handshake),
        label: const Text('Solicitar mediacion'),
      ),
    );
  }

  Widget _buildIntroCard() {
    return Card(
      color: Colors.indigo.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(Icons.balance, size: 48, color: Colors.indigo.shade400),
            const SizedBox(width: 16),
            const Expanded(
              child: Text(
                'La justicia restaurativa busca reparar el dano y restaurar relaciones a traves del dialogo.',
                style: TextStyle(fontSize: 14),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMisProcesos() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Mis procesos', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_procesos.isEmpty)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Center(
                child: Column(
                  children: [
                    Icon(Icons.folder_open, size: 48, color: Colors.grey.shade400),
                    const SizedBox(height: 8),
                    Text('No tienes procesos activos', style: TextStyle(color: Colors.grey.shade600)),
                  ],
                ),
              ),
            ),
          )
        else
          ..._procesos.map((p) => _buildProcesoCard(p)),
      ],
    );
  }

  Widget _buildProcesoCard(Map<String, dynamic> proceso) {
    final estado = proceso['estado'] ?? '';
    final estadoColor = estado == 'activo' ? Colors.green : estado == 'pendiente' ? Colors.orange : Colors.grey;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: estadoColor.withOpacity(0.2),
          child: Icon(Icons.assignment, color: estadoColor),
        ),
        title: Text(proceso['titulo'] ?? 'Proceso'),
        subtitle: Text(proceso['descripcion'] ?? ''),
        trailing: Chip(
          label: Text(estado),
          backgroundColor: estadoColor.withOpacity(0.2),
          labelStyle: TextStyle(color: estadoColor, fontSize: 12),
        ),
        onTap: () => _verDetalle(proceso),
      ),
    );
  }

  Widget _buildMediadores() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Mediadores disponibles', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_mediadores.isEmpty)
          const Text('No hay mediadores disponibles en este momento')
        else
          SizedBox(
            height: 120,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: _mediadores.length,
              itemBuilder: (context, index) => _buildMediadorCard(_mediadores[index]),
            ),
          ),
      ],
    );
  }

  Widget _buildMediadorCard(Map<String, dynamic> mediador) {
    return Card(
      margin: const EdgeInsets.only(right: 12),
      child: Container(
        width: 140,
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            CircleAvatar(
              radius: 24,
              backgroundColor: Colors.indigo.shade100,
              child: Text(
                (mediador['nombre'] ?? 'M')[0].toUpperCase(),
                style: TextStyle(color: Colors.indigo.shade700, fontWeight: FontWeight.bold),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              mediador['nombre'] ?? '',
              style: const TextStyle(fontWeight: FontWeight.w500),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            Text(
              mediador['especialidad'] ?? '',
              style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  void _mostrarInfo() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Justicia Restaurativa'),
        content: const Text(
          'Este espacio ofrece procesos de mediacion y dialogo para resolver conflictos '
          'de manera pacifica, reparando el dano causado y restaurando las relaciones comunitarias.',
        ),
        actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Entendido'))],
      ),
    );
  }

  void _solicitarMediacion() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Formulario de mediacion - proximamente')),
    );
  }

  void _verDetalle(Map<String, dynamic> proceso) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Detalle de ${proceso['titulo']} - proximamente')),
    );
  }
}
