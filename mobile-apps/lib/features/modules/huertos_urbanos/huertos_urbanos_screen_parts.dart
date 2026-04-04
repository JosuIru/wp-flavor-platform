part of 'huertos_urbanos_screen.dart';

class ParcelaDetailScreen extends ConsumerStatefulWidget {
  final int parcelaId;
  final Map<String, dynamic> parcelaData;

  const ParcelaDetailScreen({
    super.key,
    required this.parcelaId,
    required this.parcelaData,
  });

  @override
  ConsumerState<ParcelaDetailScreen> createState() => _ParcelaDetailScreenState();
}

class _ParcelaDetailScreenState extends ConsumerState<ParcelaDetailScreen> {
  Map<String, dynamic>? _detalleParcela;
  List<Map<String, dynamic>> _tareasParcela = [];
  List<Map<String, dynamic>> _historialCultivos = [];
  bool _cargando = true;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() {
      _cargando = true;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/huertos-urbanos/parcelas/${widget.parcelaId}');

      if (response.success && response.data != null) {
        setState(() {
          _detalleParcela = response.data!['parcela'] as Map<String, dynamic>? ?? response.data!;
          _tareasParcela = (response.data!['tareas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _historialCultivos = (response.data!['historial_cultivos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _cargando = false;
        });
      } else {
        setState(() {
          _detalleParcela = widget.parcelaData;
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _detalleParcela = widget.parcelaData;
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    final parcela = _detalleParcela ?? widget.parcelaData;

    final numero = parcela['numero']?.toString() ?? '';
    final tamanio = parcela['tamanio']?.toString() ?? '';
    final cultivoActual = parcela['cultivo_actual']?.toString() ?? '';
    final fechaSiembra = parcela['fecha_siembra']?.toString() ?? '';
    final imagen = parcela['imagen']?.toString() ?? '';
    final ubicacion = parcela['ubicacion']?.toString() ?? '';
    final estado = parcela['estado']?.toString() ?? 'activa';
    final tipoSuelo = parcela['tipo_suelo']?.toString() ?? '';
    final sistemaRiego = parcela['sistema_riego']?.toString() ?? '';
    final notas = parcela['notas']?.toString() ?? '';

    return Scaffold(
      appBar: AppBar(
        title: Text('${i18n.huertosPlot} $numero'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDetalle,
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : RefreshIndicator(
              onRefresh: _cargarDetalle,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Imagen de la parcela
                    if (imagen.isNotEmpty)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Image.network(
                          imagen,
                          width: double.infinity,
                          height: 200,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => _buildPlaceholderImage(),
                        ),
                      )
                    else
                      _buildPlaceholderImage(),
                    const SizedBox(height: 16),

                    // Información principal
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                const Icon(Icons.grass, color: Colors.green),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    '${i18n.huertosPlot} $numero',
                                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                          fontWeight: FontWeight.bold,
                                        ),
                                  ),
                                ),
                                Chip(
                                  label: Text(estado),
                                  backgroundColor: estado == 'activa'
                                      ? Colors.green.shade100
                                      : Colors.grey.shade200,
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            if (tamanio.isNotEmpty)
                              _buildInfoRow(Icons.square_foot, 'Tamaño', '$tamanio m²'),
                            if (cultivoActual.isNotEmpty)
                              _buildInfoRow(Icons.eco, 'Cultivo actual', cultivoActual),
                            if (fechaSiembra.isNotEmpty)
                              _buildInfoRow(Icons.calendar_today, 'Fecha siembra', fechaSiembra),
                            if (ubicacion.isNotEmpty)
                              _buildInfoRow(Icons.location_on, 'Ubicación', ubicacion),
                            if (tipoSuelo.isNotEmpty)
                              _buildInfoRow(Icons.terrain, 'Tipo de suelo', tipoSuelo),
                            if (sistemaRiego.isNotEmpty)
                              _buildInfoRow(Icons.water_drop, 'Sistema de riego', sistemaRiego),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Notas
                    if (notas.isNotEmpty) ...[
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.notes, color: Colors.orange),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Notas',
                                    style: Theme.of(context).textTheme.titleMedium,
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(notas),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],

                    // Tareas pendientes
                    if (_tareasParcela.isNotEmpty) ...[
                      Text(
                        i18n.huertosPendingTasks,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      ..._tareasParcela.map((tarea) => _buildTareaItem(tarea, i18n)),
                      const SizedBox(height: 16),
                    ],

                    // Historial de cultivos
                    if (_historialCultivos.isNotEmpty) ...[
                      Text(
                        'Historial de cultivos',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      ..._historialCultivos.map(_buildHistorialItem),
                      const SizedBox(height: 16),
                    ],

                    // Acciones
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () => _registrarActividad(context),
                            icon: const Icon(Icons.add_task),
                            label: const Text('Registrar actividad'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: FilledButton.icon(
                            onPressed: () => _registrarCosecha(context),
                            icon: const Icon(Icons.agriculture),
                            label: const Text('Registrar cosecha'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 80),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildPlaceholderImage() {
    return Container(
      width: double.infinity,
      height: 200,
      decoration: BoxDecoration(
        color: Colors.green.shade50,
        borderRadius: BorderRadius.circular(12),
      ),
      child: const Center(
        child: Icon(Icons.grass, size: 64, color: Colors.green),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey),
          const SizedBox(width: 8),
          Text(
            '$label: ',
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }

  Widget _buildTareaItem(Map<String, dynamic> tarea, AppLocalizations i18n) {
    final tareaId = (tarea['id'] as num?)?.toInt() ?? 0;
    final descripcion = tarea['descripcion']?.toString() ?? '';
    final fecha = tarea['fecha']?.toString() ?? '';
    final prioridad = tarea['prioridad']?.toString() ?? 'media';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: _getPrioridadColor(prioridad),
          radius: 16,
          child: const Icon(Icons.task, color: Colors.white, size: 18),
        ),
        title: Text(descripcion),
        subtitle: Text(fecha),
        trailing: IconButton(
          icon: const Icon(Icons.check_circle_outline),
          onPressed: () => _completarTarea(tareaId),
        ),
      ),
    );
  }

  Color _getPrioridadColor(String prioridad) {
    switch (prioridad.toLowerCase()) {
      case 'alta':
        return Colors.red;
      case 'media':
        return Colors.orange;
      case 'baja':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  Widget _buildHistorialItem(Map<String, dynamic> cultivo) {
    final nombre = cultivo['nombre']?.toString() ?? '';
    final fechaInicio = cultivo['fecha_inicio']?.toString() ?? '';
    final fechaFin = cultivo['fecha_fin']?.toString() ?? '';
    final rendimiento = cultivo['rendimiento']?.toString() ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: const CircleAvatar(
          backgroundColor: Colors.green,
          child: Icon(Icons.eco, color: Colors.white, size: 20),
        ),
        title: Text(nombre),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('$fechaInicio${fechaFin.isNotEmpty ? ' - $fechaFin' : ''}'),
            if (rendimiento.isNotEmpty)
              Text(
                'Rendimiento: $rendimiento',
                style: const TextStyle(fontWeight: FontWeight.w500),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _completarTarea(int tareaId) async {
    final api = ref.read(apiClientProvider);
    final response = await api.completarTareaHuerto(tareaId);

    if (mounted) {
      final i18n = AppLocalizations.of(context);
      final mensaje = response.success
          ? i18n.huertosTaskCompleted
          : (response.error ?? i18n.huertosTaskError);
      if (response.success) {
        FlavorSnackbar.showSuccess(context, mensaje);
      } else {
        FlavorSnackbar.showError(context, mensaje);
      }
      if (response.success) {
        _cargarDetalle();
      }
    }
  }

  Future<void> _registrarActividad(BuildContext context) async {
    final i18n = AppLocalizations.of(context);
    final descripcionController = TextEditingController();
    String tipoActividad = 'riego';

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final bottomPadding = MediaQuery.of(context).viewInsets.bottom;
            return Padding(
              padding: EdgeInsets.fromLTRB(16, 16, 16, bottomPadding + 16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Registrar actividad',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    value: tipoActividad,
                    decoration: const InputDecoration(
                      labelText: 'Tipo de actividad',
                      border: OutlineInputBorder(),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'riego', child: Text('Riego')),
                      DropdownMenuItem(value: 'abono', child: Text('Abonado')),
                      DropdownMenuItem(value: 'poda', child: Text('Poda')),
                      DropdownMenuItem(value: 'tratamiento', child: Text('Tratamiento')),
                      DropdownMenuItem(value: 'siembra', child: Text('Siembra')),
                      DropdownMenuItem(value: 'otro', child: Text('Otro')),
                    ],
                    onChanged: (value) {
                      setModalState(() => tipoActividad = value ?? 'riego');
                    },
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: descripcionController,
                    decoration: const InputDecoration(
                      labelText: 'Descripción',
                      border: OutlineInputBorder(),
                    ),
                    maxLines: 3,
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton(
                        onPressed: () => Navigator.pop(context, false),
                        child: Text(i18n.commonCancel),
                      ),
                      const SizedBox(width: 12),
                      FilledButton(
                        onPressed: () => Navigator.pop(context, true),
                        child: const Text('Registrar'),
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        );
      },
    );

    if (result == true && mounted) {
      final api = ref.read(apiClientProvider);
      final response = await api.post(
        '/huertos-urbanos/parcelas/${widget.parcelaId}/actividades',
        data: {
          'tipo': tipoActividad,
          'descripcion': descripcionController.text.trim(),
          'fecha': DateTime.now().toIso8601String().split('T')[0],
        },
      );

      if (mounted) {
        final mensaje = response.success
            ? 'Actividad registrada'
            : (response.error ?? 'Error al registrar actividad');
        if (response.success) {
          FlavorSnackbar.showSuccess(this.context, mensaje);
        } else {
          FlavorSnackbar.showError(this.context, mensaje);
        }
        if (response.success) {
          _cargarDetalle();
        }
      }
    }

    descripcionController.dispose();
  }

  Future<void> _registrarCosecha(BuildContext context) async {
    final i18n = AppLocalizations.of(context);
    final productoController = TextEditingController();
    final cantidadController = TextEditingController();
    String unidad = 'kg';

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final bottomPadding = MediaQuery.of(context).viewInsets.bottom;
            return Padding(
              padding: EdgeInsets.fromLTRB(16, 16, 16, bottomPadding + 16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Registrar cosecha',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: productoController,
                    decoration: const InputDecoration(
                      labelText: 'Producto *',
                      hintText: 'Ej: Tomates, Lechugas...',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        flex: 2,
                        child: TextField(
                          controller: cantidadController,
                          decoration: const InputDecoration(
                            labelText: 'Cantidad *',
                            border: OutlineInputBorder(),
                          ),
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          value: unidad,
                          decoration: const InputDecoration(
                            labelText: 'Unidad',
                            border: OutlineInputBorder(),
                          ),
                          items: const [
                            DropdownMenuItem(value: 'kg', child: Text('kg')),
                            DropdownMenuItem(value: 'unidades', child: Text('uds')),
                            DropdownMenuItem(value: 'manojos', child: Text('manojos')),
                          ],
                          onChanged: (value) {
                            setModalState(() => unidad = value ?? 'kg');
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton(
                        onPressed: () => Navigator.pop(context, false),
                        child: Text(i18n.commonCancel),
                      ),
                      const SizedBox(width: 12),
                      FilledButton.icon(
                        onPressed: () => Navigator.pop(context, true),
                        icon: const Icon(Icons.agriculture),
                        label: const Text('Registrar'),
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        );
      },
    );

    if (result == true && mounted) {
      if (productoController.text.trim().isEmpty || cantidadController.text.trim().isEmpty) {
        FlavorSnackbar.showError(this.context, 'El producto y la cantidad son obligatorios');
        productoController.dispose();
        cantidadController.dispose();
        return;
      }

      final api = ref.read(apiClientProvider);
      final response = await api.post(
        '/huertos-urbanos/parcelas/${widget.parcelaId}/cosechas',
        data: {
          'producto': productoController.text.trim(),
          'cantidad': double.tryParse(cantidadController.text) ?? 0,
          'unidad': unidad,
          'fecha': DateTime.now().toIso8601String().split('T')[0],
        },
      );

      if (mounted) {
        final mensaje = response.success
            ? 'Cosecha registrada'
            : (response.error ?? 'Error al registrar cosecha');
        if (response.success) {
          FlavorSnackbar.showSuccess(this.context, mensaje);
        } else {
          FlavorSnackbar.showError(this.context, mensaje);
        }
        if (response.success) {
          _cargarDetalle();
        }
      }
    }

    productoController.dispose();
    cantidadController.dispose();
  }
}
