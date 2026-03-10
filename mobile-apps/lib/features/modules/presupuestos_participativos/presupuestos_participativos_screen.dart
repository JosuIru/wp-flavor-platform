import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class PresupuestosParticipativosScreen extends ConsumerStatefulWidget {
  const PresupuestosParticipativosScreen({super.key});

  @override
  ConsumerState<PresupuestosParticipativosScreen> createState() =>
      _PresupuestosParticipativosScreenState();
}

class _PresupuestosParticipativosScreenState
    extends ConsumerState<PresupuestosParticipativosScreen> {
  List<dynamic> _listaPropuestas = [];
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
      final respuesta = await clienteApi.get('/presupuestos/propuestas');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaPropuestas =
              respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar las propuestas';
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

  void _abrirDetallePropuesta(Map<String, dynamic> propuesta) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PropuestaDetalleScreen(
          datosPropuesta: propuesta,
          onActualizado: _cargarDatos,
        ),
      ),
    );
  }

  void _crearNuevaPropuesta() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => _FormularioNuevaPropuesta(
        onCreada: () {
          Navigator.pop(context);
          _cargarDatos();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Presupuestos Participativos'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDatos,
          ),
        ],
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.account_balance, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_mensajeError!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarDatos,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _listaPropuestas.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.account_balance,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay propuestas disponibles'),
                          const SizedBox(height: 16),
                          FilledButton.icon(
                            onPressed: _crearNuevaPropuesta,
                            icon: const Icon(Icons.add),
                            label: const Text('Crear primera propuesta'),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listaPropuestas.length,
                        itemBuilder: (context, indice) =>
                            _construirTarjetaPropuesta(_listaPropuestas[indice]),
                      ),
                    ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearNuevaPropuesta,
        icon: const Icon(Icons.add),
        label: const Text('Nueva Propuesta'),
      ),
    );
  }

  Widget _construirTarjetaPropuesta(dynamic elemento) {
    final mapa = elemento as Map<String, dynamic>;
    final tituloPropuesta =
        mapa['titulo'] ?? mapa['nombre'] ?? mapa['title'] ?? 'Sin titulo';
    final descripcionPropuesta =
        mapa['descripcion'] ?? mapa['description'] ?? '';
    final presupuestoSolicitado =
        mapa['presupuesto'] ?? mapa['budget'] ?? mapa['importe'] ?? 0;
    final estadoPropuesta = mapa['estado'] ?? mapa['status'] ?? 'pendiente';
    final cantidadApoyos = mapa['apoyos'] ?? mapa['votos'] ?? mapa['votes'] ?? 0;
    final categoriaPropuesta =
        mapa['categoria'] ?? mapa['category'] ?? mapa['area'] ?? '';
    final autorPropuesta = mapa['autor'] ?? mapa['author'] ?? '';

    Color colorEstado;
    String textoEstado;
    IconData iconoEstado;
    switch (estadoPropuesta.toString().toLowerCase()) {
      case 'aprobado':
      case 'approved':
      case 'aceptado':
        colorEstado = Colors.green;
        textoEstado = 'Aprobada';
        iconoEstado = Icons.check_circle;
        break;
      case 'rechazado':
      case 'rejected':
      case 'denegado':
        colorEstado = Colors.red;
        textoEstado = 'Rechazada';
        iconoEstado = Icons.cancel;
        break;
      case 'evaluacion':
      case 'review':
      case 'revision':
        colorEstado = Colors.orange;
        textoEstado = 'En Evaluacion';
        iconoEstado = Icons.pending;
        break;
      case 'ejecucion':
      case 'executing':
        colorEstado = Colors.blue;
        textoEstado = 'En Ejecucion';
        iconoEstado = Icons.build;
        break;
      default:
        colorEstado = Colors.grey;
        textoEstado = 'Pendiente';
        iconoEstado = Icons.hourglass_empty;
    }

    final presupuestoFormateado = presupuestoSolicitado is num
        ? '${presupuestoSolicitado.toStringAsFixed(0)} EUR'
        : presupuestoSolicitado.toString();

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: InkWell(
        onTap: () => _abrirDetallePropuesta(mapa),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    backgroundColor: Colors.teal.shade100,
                    child:
                        Icon(Icons.account_balance, color: Colors.teal.shade700),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tituloPropuesta,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        if (categoriaPropuesta.isNotEmpty)
                          Text(
                            categoriaPropuesta,
                            style: TextStyle(
                              color: Colors.teal.shade700,
                              fontSize: 12,
                            ),
                          ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: colorEstado.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(iconoEstado, size: 14, color: colorEstado),
                        const SizedBox(width: 4),
                        Text(
                          textoEstado,
                          style: TextStyle(
                            color: colorEstado,
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              if (descripcionPropuesta.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  descripcionPropuesta,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: Colors.grey.shade600),
                ),
              ],
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.teal.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    Column(
                      children: [
                        Icon(Icons.euro, color: Colors.teal.shade700, size: 20),
                        const SizedBox(height: 4),
                        Text(
                          presupuestoFormateado,
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Colors.teal.shade700,
                          ),
                        ),
                        Text(
                          'Presupuesto',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                    Container(
                      width: 1,
                      height: 40,
                      color: Colors.teal.shade200,
                    ),
                    Column(
                      children: [
                        Icon(Icons.thumb_up,
                            color: Colors.teal.shade700, size: 20),
                        const SizedBox(height: 4),
                        Text(
                          '$cantidadApoyos',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Colors.teal.shade700,
                          ),
                        ),
                        Text(
                          'Apoyos',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              if (autorPropuesta.isNotEmpty) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    Icon(Icons.person, size: 14, color: Colors.grey),
                    const SizedBox(width: 4),
                    Text(
                      'Propuesto por: $autorPropuesta',
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 12,
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
}

class _FormularioNuevaPropuesta extends ConsumerStatefulWidget {
  final VoidCallback onCreada;

  const _FormularioNuevaPropuesta({required this.onCreada});

  @override
  ConsumerState<_FormularioNuevaPropuesta> createState() =>
      _FormularioNuevaPropuestaState();
}

class _FormularioNuevaPropuestaState
    extends ConsumerState<_FormularioNuevaPropuesta> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _presupuestoController = TextEditingController();
  final _justificacionController = TextEditingController();
  String? _categoriaSeleccionada;
  bool _enviando = false;

  final List<String> _categorias = [
    'Infraestructura',
    'Educacion',
    'Cultura',
    'Deportes',
    'Medio Ambiente',
    'Servicios Sociales',
    'Movilidad',
    'Seguridad',
    'Otros',
  ];

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    _presupuestoController.dispose();
    _justificacionController.dispose();
    super.dispose();
  }

  Future<void> _enviarPropuesta() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _enviando = true);

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/presupuestos/propuestas', data: {
        'titulo': _tituloController.text.trim(),
        'descripcion': _descripcionController.text.trim(),
        'presupuesto': double.tryParse(_presupuestoController.text) ?? 0,
        'categoria': _categoriaSeleccionada,
        'justificacion': _justificacionController.text.trim(),
      });

      if (!mounted) return;

      if (respuesta.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Propuesta enviada correctamente'),
            backgroundColor: Colors.green,
          ),
        );
        widget.onCreada();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(respuesta.error ?? 'Error al enviar propuesta'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _enviando = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        padding: const EdgeInsets.all(20),
        child: Form(
          key: _formKey,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Icon(Icons.account_balance, color: Colors.teal.shade700),
                    const SizedBox(width: 12),
                    Text(
                      'Nueva Propuesta',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                  ],
                ),
                const SizedBox(height: 20),

                TextFormField(
                  controller: _tituloController,
                  decoration: const InputDecoration(
                    labelText: 'Titulo de la propuesta',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.title),
                  ),
                  validator: (v) =>
                      v == null || v.trim().isEmpty ? 'Campo requerido' : null,
                ),
                const SizedBox(height: 16),

                DropdownButtonFormField<String>(
                  value: _categoriaSeleccionada,
                  decoration: const InputDecoration(
                    labelText: 'Categoria',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.category),
                  ),
                  items: _categorias
                      .map((c) => DropdownMenuItem(value: c, child: Text(c)))
                      .toList(),
                  onChanged: (v) => setState(() => _categoriaSeleccionada = v),
                  validator: (v) => v == null ? 'Selecciona una categoria' : null,
                ),
                const SizedBox(height: 16),

                TextFormField(
                  controller: _presupuestoController,
                  decoration: const InputDecoration(
                    labelText: 'Presupuesto estimado (EUR)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.euro),
                  ),
                  keyboardType: TextInputType.number,
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return 'Campo requerido';
                    if (double.tryParse(v) == null) return 'Introduce un numero valido';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                TextFormField(
                  controller: _descripcionController,
                  decoration: const InputDecoration(
                    labelText: 'Descripcion',
                    border: OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                  maxLines: 3,
                  validator: (v) =>
                      v == null || v.trim().isEmpty ? 'Campo requerido' : null,
                ),
                const SizedBox(height: 16),

                TextFormField(
                  controller: _justificacionController,
                  decoration: const InputDecoration(
                    labelText: 'Justificacion (por que es necesario)',
                    border: OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 24),

                FilledButton.icon(
                  onPressed: _enviando ? null : _enviarPropuesta,
                  icon: _enviando
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.send),
                  label: Text(_enviando ? 'Enviando...' : 'Enviar Propuesta'),
                ),
                const SizedBox(height: 16),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class PropuestaDetalleScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic> datosPropuesta;
  final VoidCallback onActualizado;

  const PropuestaDetalleScreen({
    super.key,
    required this.datosPropuesta,
    required this.onActualizado,
  });

  @override
  ConsumerState<PropuestaDetalleScreen> createState() =>
      _PropuestaDetalleScreenState();
}

class _PropuestaDetalleScreenState
    extends ConsumerState<PropuestaDetalleScreen> {
  bool _apoyando = false;
  bool _yaApoyo = false;
  int _cantidadApoyos = 0;

  @override
  void initState() {
    super.initState();
    _yaApoyo = widget.datosPropuesta['ya_apoyo'] == true ||
        widget.datosPropuesta['user_voted'] == true;
    _cantidadApoyos = widget.datosPropuesta['apoyos'] ??
        widget.datosPropuesta['votos'] ??
        widget.datosPropuesta['votes'] ??
        0;
  }

  Future<void> _apoyarPropuesta() async {
    if (_yaApoyo || _apoyando) return;

    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Apoyar propuesta'),
        content: const Text(
          'Tu apoyo es importante para que esta propuesta sea considerada. Una vez apoyada, no podras retirar tu apoyo.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Apoyar'),
          ),
        ],
      ),
    );

    if (confirmar != true || !mounted) return;

    setState(() => _apoyando = true);

    try {
      final idPropuesta = widget.datosPropuesta['id'];
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/presupuestos/propuestas/$idPropuesta/apoyar');

      if (!mounted) return;

      if (respuesta.success) {
        setState(() {
          _yaApoyo = true;
          _cantidadApoyos++;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Has apoyado esta propuesta'),
            backgroundColor: Colors.green,
          ),
        );
        widget.onActualizado();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(respuesta.error ?? 'Error al apoyar'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _apoyando = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final titulo = widget.datosPropuesta['titulo'] ??
        widget.datosPropuesta['nombre'] ??
        widget.datosPropuesta['title'] ??
        'Sin titulo';
    final descripcion =
        widget.datosPropuesta['descripcion'] ?? widget.datosPropuesta['description'] ?? '';
    final presupuesto =
        widget.datosPropuesta['presupuesto'] ?? widget.datosPropuesta['budget'] ?? 0;
    final estado =
        widget.datosPropuesta['estado'] ?? widget.datosPropuesta['status'] ?? 'pendiente';
    final categoria =
        widget.datosPropuesta['categoria'] ?? widget.datosPropuesta['category'] ?? '';
    final autor = widget.datosPropuesta['autor'] ?? widget.datosPropuesta['author'] ?? '';
    final fechaCreacion =
        widget.datosPropuesta['fecha'] ?? widget.datosPropuesta['created_at'] ?? '';
    final justificacion =
        widget.datosPropuesta['justificacion'] ?? widget.datosPropuesta['reason'] ?? '';

    Color colorEstado;
    String textoEstado;
    switch (estado.toString().toLowerCase()) {
      case 'aprobado':
      case 'approved':
        colorEstado = Colors.green;
        textoEstado = 'Aprobada';
        break;
      case 'rechazado':
      case 'rejected':
        colorEstado = Colors.red;
        textoEstado = 'Rechazada';
        break;
      case 'evaluacion':
      case 'review':
        colorEstado = Colors.orange;
        textoEstado = 'En Evaluacion';
        break;
      case 'ejecucion':
      case 'executing':
        colorEstado = Colors.blue;
        textoEstado = 'En Ejecucion';
        break;
      default:
        colorEstado = Colors.grey;
        textoEstado = 'Pendiente';
    }

    final presupuestoFormateado =
        presupuesto is num ? '${presupuesto.toStringAsFixed(0)} EUR' : presupuesto.toString();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de Propuesta'),
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header con estado
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [Colors.teal.shade400, Colors.teal.shade700],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      textoEstado,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    titulo,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  if (categoria.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(Icons.category, color: Colors.white70, size: 16),
                        const SizedBox(width: 4),
                        Text(
                          categoria,
                          style: const TextStyle(color: Colors.white70),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),

            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Estadisticas
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.teal.shade50,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            children: [
                              Icon(Icons.euro, color: Colors.teal.shade700, size: 28),
                              const SizedBox(height: 4),
                              Text(
                                presupuestoFormateado,
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 18,
                                  color: Colors.teal.shade700,
                                ),
                              ),
                              Text(
                                'Presupuesto',
                                style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                              ),
                            ],
                          ),
                        ),
                        Container(width: 1, height: 50, color: Colors.teal.shade200),
                        Expanded(
                          child: Column(
                            children: [
                              Icon(
                                _yaApoyo ? Icons.thumb_up : Icons.thumb_up_outlined,
                                color: Colors.teal.shade700,
                                size: 28,
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '$_cantidadApoyos',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 18,
                                  color: Colors.teal.shade700,
                                ),
                              ),
                              Text(
                                'Apoyos',
                                style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 24),

                  // Descripcion
                  if (descripcion.isNotEmpty) ...[
                    Text(
                      'Descripcion',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      descripcion,
                      style: TextStyle(color: Colors.grey.shade700, height: 1.5),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Justificacion
                  if (justificacion.isNotEmpty) ...[
                    Text(
                      'Justificacion',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      justificacion,
                      style: TextStyle(color: Colors.grey.shade700, height: 1.5),
                    ),
                    const SizedBox(height: 24),
                  ],

                  // Info adicional
                  if (autor.isNotEmpty || fechaCreacion.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Column(
                        children: [
                          if (autor.isNotEmpty)
                            Row(
                              children: [
                                Icon(Icons.person, size: 16, color: Colors.grey.shade600),
                                const SizedBox(width: 8),
                                Text(
                                  'Propuesto por: $autor',
                                  style: TextStyle(color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          if (fechaCreacion.isNotEmpty) ...[
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Icon(Icons.calendar_today,
                                    size: 16, color: Colors.grey.shade600),
                                const SizedBox(width: 8),
                                Text(
                                  'Fecha: $fechaCreacion',
                                  style: TextStyle(color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),

                  const SizedBox(height: 32),

                  // Boton de apoyo
                  if (estado.toString().toLowerCase() == 'pendiente' ||
                      estado.toString().toLowerCase() == 'evaluacion')
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: _yaApoyo || _apoyando ? null : _apoyarPropuesta,
                        icon: _apoyando
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : Icon(_yaApoyo ? Icons.check : Icons.thumb_up),
                        label: Text(
                          _yaApoyo
                              ? 'Ya has apoyado esta propuesta'
                              : _apoyando
                                  ? 'Apoyando...'
                                  : 'Apoyar esta propuesta',
                        ),
                        style: FilledButton.styleFrom(
                          backgroundColor: _yaApoyo ? Colors.grey : Colors.teal,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                      ),
                    ),

                  const SizedBox(height: 32),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
