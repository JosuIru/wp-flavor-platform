import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
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
                          _abrirMensajesProceso(proceso);
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
                          _abrirDocumentosProceso(proceso);
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

  void _abrirMensajesProceso(Map<String, dynamic> proceso) {
    final procesoId = proceso['id'];
    final mensajeController = TextEditingController();
    List<Map<String, dynamic>> mensajes = [];
    bool cargando = true;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) {
          // Cargar mensajes al abrir
          if (cargando) {
            _cargarMensajesProceso(procesoId).then((lista) {
              setModalState(() {
                mensajes = lista;
                cargando = false;
              });
            });
          }

          return DraggableScrollableSheet(
            initialChildSize: 0.8,
            minChildSize: 0.5,
            maxChildSize: 0.95,
            expand: false,
            builder: (context, scrollController) => Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(Icons.message, color: Colors.indigo.shade600),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Text(
                          'Mensajes del proceso',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1),
                Expanded(
                  child: cargando
                      ? const Center(child: CircularProgressIndicator())
                      : mensajes.isEmpty
                          ? Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.chat_bubble_outline, size: 48, color: Colors.grey.shade400),
                                  const SizedBox(height: 8),
                                  Text('No hay mensajes', style: TextStyle(color: Colors.grey.shade600)),
                                ],
                              ),
                            )
                          : ListView.builder(
                              controller: scrollController,
                              padding: const EdgeInsets.all(16),
                              itemCount: mensajes.length,
                              itemBuilder: (context, index) {
                                final mensaje = mensajes[index];
                                final esPropio = mensaje['es_propio'] == true;
                                return Align(
                                  alignment: esPropio ? Alignment.centerRight : Alignment.centerLeft,
                                  child: Container(
                                    margin: const EdgeInsets.only(bottom: 8),
                                    padding: const EdgeInsets.all(12),
                                    constraints: BoxConstraints(
                                      maxWidth: MediaQuery.of(context).size.width * 0.7,
                                    ),
                                    decoration: BoxDecoration(
                                      color: esPropio ? Colors.indigo.shade100 : Colors.grey.shade200,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        if (!esPropio)
                                          Text(
                                            mensaje['autor'] ?? '',
                                            style: TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 12,
                                              color: Colors.grey.shade700,
                                            ),
                                          ),
                                        Text(mensaje['contenido'] ?? ''),
                                        const SizedBox(height: 4),
                                        Text(
                                          mensaje['fecha'] ?? '',
                                          style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                ),
                Padding(
                  padding: EdgeInsets.only(
                    left: 16,
                    right: 16,
                    top: 8,
                    bottom: MediaQuery.of(context).viewInsets.bottom + 16,
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: mensajeController,
                          decoration: const InputDecoration(
                            hintText: 'Escribe un mensaje...',
                            border: OutlineInputBorder(),
                            contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          ),
                          maxLines: 2,
                          minLines: 1,
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton.filled(
                        onPressed: () async {
                          if (mensajeController.text.trim().isEmpty) return;

                          final api = ref.read(apiClientProvider);
                          final response = await api.post(
                            '/justicia-restaurativa/procesos/$procesoId/mensajes',
                            data: {'contenido': mensajeController.text.trim()},
                          );

                          if (response.success) {
                            mensajeController.clear();
                            final lista = await _cargarMensajesProceso(procesoId);
                            setModalState(() {
                              mensajes = lista;
                            });
                          } else if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(response.error ?? 'Error al enviar'),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
                        },
                        icon: const Icon(Icons.send),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<List<Map<String, dynamic>>> _cargarMensajesProceso(dynamic procesoId) async {
    final api = ref.read(apiClientProvider);
    final response = await api.get('/justicia-restaurativa/procesos/$procesoId/mensajes');

    if (response.success && response.data != null) {
      return (response.data!['mensajes'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .toList();
    }
    return [];
  }

  void _abrirDocumentosProceso(Map<String, dynamic> proceso) {
    final procesoId = proceso['id'];
    List<Map<String, dynamic>> documentos = [];
    bool cargando = true;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) {
          // Cargar documentos al abrir
          if (cargando) {
            _cargarDocumentosProceso(procesoId).then((lista) {
              setModalState(() {
                documentos = lista;
                cargando = false;
              });
            });
          }

          return DraggableScrollableSheet(
            initialChildSize: 0.6,
            minChildSize: 0.4,
            maxChildSize: 0.9,
            expand: false,
            builder: (context, scrollController) => Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(Icons.folder, color: Colors.indigo.shade600),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Text(
                          'Documentos del proceso',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1),
                Expanded(
                  child: cargando
                      ? const Center(child: CircularProgressIndicator())
                      : documentos.isEmpty
                          ? Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.folder_open, size: 48, color: Colors.grey.shade400),
                                  const SizedBox(height: 8),
                                  Text('No hay documentos', style: TextStyle(color: Colors.grey.shade600)),
                                ],
                              ),
                            )
                          : ListView.builder(
                              controller: scrollController,
                              padding: const EdgeInsets.all(16),
                              itemCount: documentos.length,
                              itemBuilder: (context, index) {
                                final doc = documentos[index];
                                final nombre = doc['nombre'] ?? doc['titulo'] ?? 'Documento';
                                final tipo = doc['tipo'] ?? 'archivo';
                                final fecha = doc['fecha'] ?? '';

                                IconData icono;
                                switch (tipo.toString().toLowerCase()) {
                                  case 'pdf':
                                    icono = Icons.picture_as_pdf;
                                    break;
                                  case 'imagen':
                                  case 'image':
                                    icono = Icons.image;
                                    break;
                                  case 'video':
                                    icono = Icons.video_file;
                                    break;
                                  default:
                                    icono = Icons.insert_drive_file;
                                }

                                return Card(
                                  margin: const EdgeInsets.only(bottom: 8),
                                  child: ListTile(
                                    leading: CircleAvatar(
                                      backgroundColor: Colors.indigo.shade100,
                                      child: Icon(icono, color: Colors.indigo.shade600),
                                    ),
                                    title: Text(nombre.toString()),
                                    subtitle: Text('$tipo • $fecha'),
                                    trailing: const Icon(Icons.download),
                                    onTap: () => _descargarDocumento(doc),
                                  ),
                                );
                              },
                            ),
                ),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () => _subirDocumento(procesoId, setModalState, (lista) {
                        setModalState(() => documentos = lista);
                      }),
                      icon: const Icon(Icons.upload_file),
                      label: const Text('Subir documento'),
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<List<Map<String, dynamic>>> _cargarDocumentosProceso(dynamic procesoId) async {
    final api = ref.read(apiClientProvider);
    final response = await api.get('/justicia-restaurativa/procesos/$procesoId/documentos');

    if (response.success && response.data != null) {
      return (response.data!['documentos'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .toList();
    }
    return [];
  }

  Future<void> _descargarDocumento(Map<String, dynamic> documento) async {
    final url = documento['url'] ?? documento['enlace'] ?? '';
    if (url.toString().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('URL del documento no disponible'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final urlString = url.toString();
    final uri = Uri.tryParse(
      urlString.startsWith('http') ? urlString : 'https://$urlString',
    );

    if (uri != null && await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No se puede abrir el documento'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  void _subirDocumento(
    dynamic procesoId,
    void Function(void Function()) setModalState,
    void Function(List<Map<String, dynamic>>) actualizarLista,
  ) {
    final nombreController = TextEditingController();
    String tipoSeleccionado = 'documento';

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Subir documento'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nombreController,
                decoration: const InputDecoration(
                  labelText: 'Nombre del documento',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                value: tipoSeleccionado,
                decoration: const InputDecoration(
                  labelText: 'Tipo',
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(value: 'documento', child: Text('Documento')),
                  DropdownMenuItem(value: 'pdf', child: Text('PDF')),
                  DropdownMenuItem(value: 'imagen', child: Text('Imagen')),
                  DropdownMenuItem(value: 'otro', child: Text('Otro')),
                ],
                onChanged: (value) {
                  if (value != null) {
                    setDialogState(() => tipoSeleccionado = value);
                  }
                },
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              onPressed: () async {
                if (nombreController.text.isEmpty) return;
                Navigator.pop(context);

                final api = ref.read(apiClientProvider);
                final response = await api.post(
                  '/justicia-restaurativa/procesos/$procesoId/documentos',
                  data: {
                    'nombre': nombreController.text,
                    'tipo': tipoSeleccionado,
                  },
                );

                if (response.success) {
                  ScaffoldMessenger.of(this.context).showSnackBar(
                    const SnackBar(
                      content: Text('Documento subido correctamente'),
                      backgroundColor: Colors.green,
                    ),
                  );
                  final lista = await _cargarDocumentosProceso(procesoId);
                  actualizarLista(lista);
                } else {
                  ScaffoldMessenger.of(this.context).showSnackBar(
                    SnackBar(
                      content: Text(response.error ?? 'Error al subir'),
                      backgroundColor: Colors.red,
                    ),
                  );
                }
              },
              child: const Text('Subir'),
            ),
          ],
        ),
      ),
    );
  }
}
