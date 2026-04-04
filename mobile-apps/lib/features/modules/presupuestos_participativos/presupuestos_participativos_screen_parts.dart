part of 'presupuestos_participativos_screen.dart';

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
        FlavorSnackbar.showSuccess(context, 'Propuesta enviada correctamente');
        widget.onCreada();
      } else {
        FlavorSnackbar.showError(
          context,
          respuesta.error ?? 'Error al enviar propuesta',
        );
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
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
                      ? const FlavorInlineSpinner(color: Colors.white)
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
      final respuesta = await clienteApi.post(
        '/presupuestos/propuestas/$idPropuesta/apoyar',
        data: {},
      );

      if (!mounted) return;

      if (respuesta.success) {
        setState(() {
          _yaApoyo = true;
          _cantidadApoyos++;
        });
        FlavorSnackbar.showSuccess(context, 'Has apoyado esta propuesta');
        widget.onActualizado();
      } else {
        FlavorSnackbar.showError(context, respuesta.error ?? 'Error al apoyar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
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

    String textoEstado;
    switch (estado.toString().toLowerCase()) {
      case 'aprobado':
      case 'approved':
        textoEstado = 'Aprobada';
        break;
      case 'rechazado':
      case 'rejected':
        textoEstado = 'Rechazada';
        break;
      case 'evaluacion':
      case 'review':
        textoEstado = 'En Evaluacion';
        break;
      case 'ejecucion':
      case 'executing':
        textoEstado = 'En Ejecucion';
        break;
      default:
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
                                Icon(Icons.calendar_today, size: 16, color: Colors.grey.shade600),
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
                  if (estado.toString().toLowerCase() == 'pendiente' ||
                      estado.toString().toLowerCase() == 'evaluacion')
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: _yaApoyo || _apoyando ? null : _apoyarPropuesta,
                        icon: _apoyando
                            ? const FlavorInlineSpinner(color: Colors.white)
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
