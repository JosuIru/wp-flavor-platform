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
    final descripcionController = TextEditingController();
    String tipoSeleccionado = 'vecinal';
    String? mediadorSeleccionado;

    final tipos = [
      {'id': 'vecinal', 'nombre': 'Conflicto vecinal'},
      {'id': 'comunitario', 'nombre': 'Conflicto comunitario'},
      {'id': 'familiar', 'nombre': 'Conflicto familiar'},
      {'id': 'laboral', 'nombre': 'Conflicto laboral'},
      {'id': 'otro', 'nombre': 'Otro'},
    ];

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
                    Icon(Icons.handshake, color: Colors.indigo.shade600),
                    const SizedBox(width: 12),
                    const Text(
                      'Solicitar Mediacion',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'Describe la situacion que deseas resolver mediante el dialogo.',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
                const SizedBox(height: 20),
                DropdownButtonFormField<String>(
                  value: tipoSeleccionado,
                  decoration: const InputDecoration(
                    labelText: 'Tipo de conflicto',
                    prefixIcon: Icon(Icons.category),
                    border: OutlineInputBorder(),
                  ),
                  items: tipos.map((tipo) {
                    return DropdownMenuItem<String>(
                      value: tipo['id'],
                      child: Text(tipo['nombre']!),
                    );
                  }).toList(),
                  onChanged: (value) {
                    if (value != null) {
                      setModalState(() => tipoSeleccionado = value);
                    }
                  },
                ),
                const SizedBox(height: 16),
                if (_mediadores.isNotEmpty)
                  DropdownButtonFormField<String>(
                    value: mediadorSeleccionado,
                    decoration: const InputDecoration(
                      labelText: 'Mediador preferido (opcional)',
                      prefixIcon: Icon(Icons.person),
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      const DropdownMenuItem<String>(
                        value: null,
                        child: Text('Sin preferencia'),
                      ),
                      ..._mediadores.map((mediador) {
                        return DropdownMenuItem<String>(
                          value: mediador['id']?.toString(),
                          child: Text(mediador['nombre'] ?? 'Mediador'),
                        );
                      }),
                    ],
                    onChanged: (value) {
                      setModalState(() => mediadorSeleccionado = value);
                    },
                  ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: descripcionController,
                  decoration: const InputDecoration(
                    labelText: 'Describe la situacion',
                    prefixIcon: Icon(Icons.description),
                    border: OutlineInputBorder(),
                    hintText: 'Explica brevemente el conflicto...',
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 8),
                Card(
                  color: Colors.blue.shade50,
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Row(
                      children: [
                        Icon(Icons.privacy_tip, color: Colors.blue.shade600, size: 20),
                        const SizedBox(width: 8),
                        const Expanded(
                          child: Text(
                            'Tu solicitud sera tratada con confidencialidad.',
                            style: TextStyle(fontSize: 12),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () async {
                      if (descripcionController.text.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Describe la situacion')),
                        );
                        return;
                      }
                      Navigator.pop(context);
                      await _enviarSolicitudMediacion(
                        tipo: tipoSeleccionado,
                        mediadorId: mediadorSeleccionado,
                        descripcion: descripcionController.text,
                      );
                    },
                    icon: const Icon(Icons.send),
                    label: const Text('Enviar solicitud'),
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

  Future<void> _enviarSolicitudMediacion({
    required String tipo,
    String? mediadorId,
    required String descripcion,
  }) async {
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/justicia-restaurativa/solicitar', data: {
        'tipo': tipo,
        'mediador_id': mediadorId,
        'descripcion': descripcion,
      });

      if (response.success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Solicitud enviada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al enviar');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _verDetalle(Map<String, dynamic> proceso) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: Colors.indigo.shade100,
                    child: Icon(Icons.balance, color: Colors.indigo.shade600),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          proceso['titulo'] ?? 'Proceso',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        _buildEstadoChip(proceso['estado'] ?? ''),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              if ((proceso['descripcion'] ?? '').isNotEmpty) ...[
                const Text(
                  'Descripcion',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(proceso['descripcion']),
                const SizedBox(height: 20),
              ],
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _buildInfoRow(
                        Icons.category,
                        'Tipo',
                        proceso['tipo'] ?? 'No especificado',
                      ),
                      const Divider(),
                      _buildInfoRow(
                        Icons.person,
                        'Mediador',
                        proceso['mediador_nombre'] ?? 'Por asignar',
                      ),
                      const Divider(),
                      _buildInfoRow(
                        Icons.calendar_today,
                        'Fecha inicio',
                        proceso['fecha_inicio'] ?? 'Pendiente',
                      ),
                      if ((proceso['proxima_sesion'] ?? '').isNotEmpty) ...[
                        const Divider(),
                        _buildInfoRow(
                          Icons.event,
                          'Proxima sesion',
                          proceso['proxima_sesion'],
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              if (proceso['estado'] == 'activo') ...[
                const Text(
                  'Acciones',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          Navigator.pop(context);
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Abriendo mensajes...')),
                          );
                        },
                        icon: const Icon(Icons.message),
                        label: const Text('Mensajes'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () {
                          Navigator.pop(context);
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Abriendo documentos...')),
                          );
                        },
                        icon: const Icon(Icons.folder),
                        label: const Text('Documentos'),
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEstadoChip(String estado) {
    final Color estadoColor;
    switch (estado.toLowerCase()) {
      case 'activo':
        estadoColor = Colors.green;
        break;
      case 'pendiente':
        estadoColor = Colors.orange;
        break;
      case 'finalizado':
        estadoColor = Colors.blue;
        break;
      default:
        estadoColor = Colors.grey;
    }

    return Container(
      margin: const EdgeInsets.only(top: 4),
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: estadoColor.withOpacity(0.2),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        estado,
        style: TextStyle(fontSize: 12, color: estadoColor, fontWeight: FontWeight.w500),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(color: Colors.grey.shade600)),
          const Spacer(),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}
