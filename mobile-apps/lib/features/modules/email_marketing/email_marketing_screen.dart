import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_state_widgets.dart';

class EmailMarketingScreen extends ConsumerStatefulWidget {
  const EmailMarketingScreen({super.key});

  @override
  ConsumerState<EmailMarketingScreen> createState() => _EmailMarketingScreenState();
}

class _EmailMarketingScreenState extends ConsumerState<EmailMarketingScreen> {
  List<dynamic> _listaCampanas = [];
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarDatos();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/email-marketing/campanas');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaCampanas = respuesta.data!['items'] ?? respuesta.data!['data'] ?? respuesta.data!['campanas'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar campanas';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Email Marketing'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _cargarDatos),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _crearNuevaCampana(context),
        icon: const Icon(Icons.add),
        label: const Text('Nueva campana'),
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDatos,
                  icon: Icons.email_outlined,
                )
              : _listaCampanas.isEmpty
                  ? const FlavorEmptyState(
                      icon: Icons.email_outlined,
                      title: 'No hay campanas disponibles',
                      message: 'Crea tu primera campana de email marketing',
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listaCampanas.length,
                        itemBuilder: (context, indice) => _construirTarjetaCampana(_listaCampanas[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaCampana(dynamic elemento) {
    final mapaDatos = elemento as Map<String, dynamic>;
    final nombreCampana = mapaDatos['nombre'] ?? mapaDatos['titulo'] ?? mapaDatos['title'] ?? 'Sin nombre';
    final asuntoCampana = mapaDatos['asunto'] ?? mapaDatos['subject'] ?? '';
    final estadoCampana = mapaDatos['estado'] ?? mapaDatos['status'] ?? 'borrador';
    final fechaEnvio = mapaDatos['fecha_envio'] ?? mapaDatos['sent_date'] ?? '';
    final totalEnviados = mapaDatos['enviados'] ?? mapaDatos['sent'] ?? 0;
    final totalAbiertos = mapaDatos['abiertos'] ?? mapaDatos['opened'] ?? 0;
    final totalClics = mapaDatos['clics'] ?? mapaDatos['clicks'] ?? 0;

    Color colorEstado;
    IconData iconoEstado;
    switch (estadoCampana.toString().toLowerCase()) {
      case 'enviado':
      case 'sent':
        colorEstado = Colors.green;
        iconoEstado = Icons.check_circle;
        break;
      case 'programado':
      case 'scheduled':
        colorEstado = Colors.blue;
        iconoEstado = Icons.schedule;
        break;
      case 'enviando':
      case 'sending':
        colorEstado = Colors.orange;
        iconoEstado = Icons.send;
        break;
      default:
        colorEstado = Colors.grey;
        iconoEstado = Icons.drafts;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () {
          final idCampana = mapaDatos['id'];
          if (idCampana != null) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => CampanaDetalleScreen(campanaId: idCampana),
              ),
            );
          }
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: colorEstado.withOpacity(0.2),
                  child: Icon(iconoEstado, color: colorEstado),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        nombreCampana,
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      if (asuntoCampana.isNotEmpty)
                        Text(
                          asuntoCampana,
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: colorEstado.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    estadoCampana.toString().toUpperCase(),
                    style: TextStyle(color: colorEstado, fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            if (estadoCampana.toString().toLowerCase() == 'enviado' || estadoCampana.toString().toLowerCase() == 'sent') ...[
              const Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _construirEstadistica('Enviados', totalEnviados.toString(), Icons.send),
                  _construirEstadistica('Abiertos', totalAbiertos.toString(), Icons.visibility),
                  _construirEstadistica('Clics', totalClics.toString(), Icons.touch_app),
                ],
              ),
            ],
            if (fechaEnvio.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                'Enviado: $fechaEnvio',
                style: TextStyle(color: Colors.grey.shade500, fontSize: 12),
              ),
            ],
          ],
          ),
        ),
      ),
    );
  }

  Widget _construirEstadistica(String etiqueta, String valor, IconData icono) {
    return Column(
      children: [
        Icon(icono, size: 20, color: Colors.grey.shade600),
        const SizedBox(height: 4),
        Text(valor, style: const TextStyle(fontWeight: FontWeight.bold)),
        Text(etiqueta, style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
      ],
    );
  }

  void _crearNuevaCampana(BuildContext context) {
    final nombreController = TextEditingController();
    final asuntoController = TextEditingController();
    final contenidoController = TextEditingController();
    bool creando = false;

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
                    const Icon(Icons.email, color: Colors.blue),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text(
                        'Nueva Campana',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: nombreController,
                  decoration: const InputDecoration(
                    labelText: 'Nombre de la campana',
                    prefixIcon: Icon(Icons.campaign),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: asuntoController,
                  decoration: const InputDecoration(
                    labelText: 'Asunto del email',
                    prefixIcon: Icon(Icons.subject),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: contenidoController,
                  decoration: const InputDecoration(
                    labelText: 'Contenido del email',
                    prefixIcon: Icon(Icons.article),
                    border: OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                  maxLines: 5,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: creando
                        ? null
                        : () async {
                            if (nombreController.text.isEmpty || asuntoController.text.isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Completa nombre y asunto')),
                              );
                              return;
                            }
                            setModalState(() => creando = true);
                            await _guardarCampana(
                              nombreController.text,
                              asuntoController.text,
                              contenidoController.text,
                            );
                            if (context.mounted) Navigator.pop(context);
                          },
                    icon: creando
                        ? const FlavorInlineSpinner()
                        : const Icon(Icons.save),
                    label: Text(creando ? 'Guardando...' : 'Crear campana'),
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

  Future<void> _guardarCampana(String nombre, String asunto, String contenido) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/email-marketing/campanas', data: {
        'nombre': nombre,
        'asunto': asunto,
        'contenido': contenido,
        'estado': 'borrador',
      });
      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Campana creada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDatos();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al crear campana'),
              backgroundColor: Colors.red,
            ),
          );
        }
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

class CampanaDetalleScreen extends ConsumerStatefulWidget {
  final dynamic campanaId;
  const CampanaDetalleScreen({super.key, required this.campanaId});

  @override
  ConsumerState<CampanaDetalleScreen> createState() => _CampanaDetalleScreenState();
}

class _CampanaDetalleScreenState extends ConsumerState<CampanaDetalleScreen> {
  Map<String, dynamic>? _datosCampana;
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/email-marketing/campanas/${widget.campanaId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosCampana = respuesta.data!['data'] ?? respuesta.data!;
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar campana';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de Campana'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit),
            onPressed: () => _editarCampana(context),
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDetalle,
                )
              : _datosCampana == null
                  ? const FlavorEmptyState(
                      icon: Icons.mark_email_read_outlined,
                      title: 'No se encontraron datos',
                    )
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        Text(
                          _datosCampana!['nombre'] ?? _datosCampana!['titulo'] ?? 'Campana',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 8),
                        if (_datosCampana!['asunto'] != null)
                          Text(
                            'Asunto: ${_datosCampana!['asunto']}',
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        const SizedBox(height: 24),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Estadisticas', style: TextStyle(fontWeight: FontWeight.bold)),
                                const SizedBox(height: 16),
                                _construirFilaEstadistica('Total enviados', _datosCampana!['enviados']?.toString() ?? '0'),
                                _construirFilaEstadistica('Abiertos', _datosCampana!['abiertos']?.toString() ?? '0'),
                                _construirFilaEstadistica('Clics', _datosCampana!['clics']?.toString() ?? '0'),
                                _construirFilaEstadistica('Rebotes', _datosCampana!['rebotes']?.toString() ?? '0'),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        FilledButton.icon(
                          onPressed: () => _enviarCampana(context),
                          icon: const Icon(Icons.send),
                          label: const Text('Enviar ahora'),
                        ),
                      ],
                    ),
    );
  }

  void _editarCampana(BuildContext context) {
    if (_datosCampana == null) return;

    final nombreController = TextEditingController(
        text: _datosCampana!['nombre'] ?? _datosCampana!['titulo'] ?? '');
    final asuntoController =
        TextEditingController(text: _datosCampana!['asunto'] ?? '');
    final contenidoController =
        TextEditingController(text: _datosCampana!['contenido'] ?? '');
    bool guardando = false;

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
                    const Icon(Icons.edit, color: Colors.blue),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text(
                        'Editar Campana',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: nombreController,
                  decoration: const InputDecoration(
                    labelText: 'Nombre',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: asuntoController,
                  decoration: const InputDecoration(
                    labelText: 'Asunto',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: contenidoController,
                  decoration: const InputDecoration(
                    labelText: 'Contenido',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 5,
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: guardando
                        ? null
                        : () async {
                            setModalState(() => guardando = true);
                            final clienteApi = ref.read(apiClientProvider);
                            final respuesta = await clienteApi.put(
                              '/email-marketing/campanas/${widget.campanaId}',
                              data: {
                                'nombre': nombreController.text,
                                'asunto': asuntoController.text,
                                'contenido': contenidoController.text,
                              },
                            );
                            if (context.mounted) {
                              Navigator.pop(context);
                              if (respuesta.success) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Campana actualizada'),
                                    backgroundColor: Colors.green,
                                  ),
                                );
                                _cargarDetalle();
                              } else {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(
                                    content: Text(respuesta.error ?? 'Error'),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                              }
                            }
                          },
                    icon: guardando
                        ? const FlavorInlineSpinner()
                        : const Icon(Icons.save),
                    label: Text(guardando ? 'Guardando...' : 'Guardar cambios'),
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

  Future<void> _enviarCampana(BuildContext context) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Enviar campana'),
        content: const Text(
          '¿Estas seguro de enviar esta campana ahora? Esta accion no se puede deshacer.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Enviar'),
          ),
        ],
      ),
    );

    if (confirmar != true || !mounted) return;

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post(
        '/email-marketing/campanas/${widget.campanaId}/enviar',
        data: {},
      );

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(this.context).showSnackBar(
            const SnackBar(
              content: Text('Campana enviada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDetalle();
        } else {
          ScaffoldMessenger.of(this.context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al enviar'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(this.context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Widget _construirFilaEstadistica(String etiqueta, String valor) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(etiqueta),
          Text(valor, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}
