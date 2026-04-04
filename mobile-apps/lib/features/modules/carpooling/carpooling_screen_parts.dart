part of 'carpooling_screen.dart';

class NuevoViajeScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? viajeExistente;
  final VoidCallback onViajeCreado;

  const NuevoViajeScreen({
    super.key,
    this.viajeExistente,
    required this.onViajeCreado,
  });

  @override
  ConsumerState<NuevoViajeScreen> createState() => _NuevoViajeScreenState();
}

class _NuevoViajeScreenState extends ConsumerState<NuevoViajeScreen> {
  final _formKey = GlobalKey<FormState>();
  final _origenController = TextEditingController();
  final _destinoController = TextEditingController();
  final _precioController = TextEditingController();
  final _plazasController = TextEditingController();
  final _notasController = TextEditingController();

  DateTime _fechaSeleccionada = DateTime.now().add(const Duration(days: 1));
  TimeOfDay _horaSeleccionada = const TimeOfDay(hour: 9, minute: 0);
  String _tipoViaje = 'ofrezco';
  bool _guardando = false;

  bool get _esEdicion => widget.viajeExistente != null;

  @override
  void initState() {
    super.initState();
    if (widget.viajeExistente != null) {
      final viaje = widget.viajeExistente!;
      _origenController.text = viaje['origen'] ?? viaje['from'] ?? '';
      _destinoController.text = viaje['destino'] ?? viaje['to'] ?? '';
      _precioController.text = (viaje['precio'] ?? viaje['price'] ?? '').toString();
      _plazasController.text = (viaje['plazas_disponibles'] ?? viaje['plazas'] ?? '').toString();
      _notasController.text = viaje['notas'] ?? viaje['descripcion'] ?? '';
      _tipoViaje = viaje['tipo'] ?? 'ofrezco';

      // Parsear fecha si existe
      final fechaStr = viaje['fecha_salida'] ?? viaje['fecha'];
      if (fechaStr != null && fechaStr.toString().isNotEmpty) {
        try {
          _fechaSeleccionada = DateTime.parse(fechaStr.toString().split(' ')[0]);
        } catch (e) {
          debugPrint('Error parseando fecha de viaje: $e');
        }
      }

      // Parsear hora si existe
      final horaStr = viaje['hora_salida'] ?? viaje['hora'];
      if (horaStr != null && horaStr.toString().isNotEmpty) {
        try {
          final partes = horaStr.toString().split(':');
          _horaSeleccionada = TimeOfDay(
            hour: int.parse(partes[0]),
            minute: int.parse(partes[1]),
          );
        } catch (e) {
          debugPrint('Error parseando hora de viaje: $e');
        }
      }
    }
  }

  @override
  void dispose() {
    _origenController.dispose();
    _destinoController.dispose();
    _precioController.dispose();
    _plazasController.dispose();
    _notasController.dispose();
    super.dispose();
  }

  Future<void> _seleccionarFecha() async {
    final fecha = await showDatePicker(
      context: context,
      initialDate: _fechaSeleccionada,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (fecha != null) {
      setState(() => _fechaSeleccionada = fecha);
    }
  }

  Future<void> _seleccionarHora() async {
    final hora = await showTimePicker(
      context: context,
      initialTime: _horaSeleccionada,
    );
    if (hora != null) {
      setState(() => _horaSeleccionada = hora);
    }
  }

  Future<void> _guardarViaje() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _guardando = true);

    try {
      final clienteApi = ref.read(apiClientProvider);
      final fechaFormateada = '${_fechaSeleccionada.year}-${_fechaSeleccionada.month.toString().padLeft(2, '0')}-${_fechaSeleccionada.day.toString().padLeft(2, '0')}';
      final horaFormateada = '${_horaSeleccionada.hour.toString().padLeft(2, '0')}:${_horaSeleccionada.minute.toString().padLeft(2, '0')}';

      final datos = {
        'origen': _origenController.text.trim(),
        'destino': _destinoController.text.trim(),
        'fecha_salida': fechaFormateada,
        'hora_salida': horaFormateada,
        'precio': double.tryParse(_precioController.text) ?? 0,
        'plazas_disponibles': int.tryParse(_plazasController.text) ?? 1,
        'tipo': _tipoViaje,
        'notas': _notasController.text.trim(),
      };

      ApiResponse respuesta;
      if (_esEdicion) {
        final viajeId = widget.viajeExistente!['id']?.toString() ?? '';
        respuesta = await clienteApi.put('/carpooling/viajes/$viajeId', data: datos);
      } else {
        respuesta = await clienteApi.post('/carpooling/viajes', data: datos);
      }

      if (mounted) {
        setState(() => _guardando = false);

        if (respuesta.success) {
          FlavorSnackbar.showSuccess(context, _esEdicion ? 'Viaje actualizado' : 'Viaje publicado');
          widget.onViajeCreado();
          Navigator.pop(context);
        } else {
          FlavorSnackbar.showError(context, respuesta.error ?? 'Error al guardar');
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _guardando = false);
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_esEdicion ? 'Editar viaje' : 'Nuevo viaje'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Tipo de viaje
            const Text('Tipo de anuncio', style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(
                  value: 'ofrezco',
                  label: Text('Ofrezco viaje'),
                  icon: Icon(Icons.directions_car),
                ),
                ButtonSegment(
                  value: 'busco',
                  label: Text('Busco viaje'),
                  icon: Icon(Icons.search),
                ),
              ],
              selected: {_tipoViaje},
              onSelectionChanged: (values) {
                setState(() => _tipoViaje = values.first);
              },
            ),
            const SizedBox(height: 24),

            // Origen
            TextFormField(
              controller: _origenController,
              decoration: const InputDecoration(
                labelText: 'Origen *',
                hintText: 'Ciudad o punto de salida',
                prefixIcon: Icon(Icons.trip_origin, color: Colors.green),
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El origen es obligatorio';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Destino
            TextFormField(
              controller: _destinoController,
              decoration: const InputDecoration(
                labelText: 'Destino *',
                hintText: 'Ciudad o punto de llegada',
                prefixIcon: Icon(Icons.location_on, color: Colors.red),
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El destino es obligatorio';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Fecha y hora
            Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: _seleccionarFecha,
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'Fecha *',
                        prefixIcon: Icon(Icons.calendar_today),
                        border: OutlineInputBorder(),
                      ),
                      child: Text(
                        '${_fechaSeleccionada.day}/${_fechaSeleccionada.month}/${_fechaSeleccionada.year}',
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: InkWell(
                    onTap: _seleccionarHora,
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'Hora *',
                        prefixIcon: Icon(Icons.schedule),
                        border: OutlineInputBorder(),
                      ),
                      child: Text(
                        '${_horaSeleccionada.hour.toString().padLeft(2, '0')}:${_horaSeleccionada.minute.toString().padLeft(2, '0')}',
                      ),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Plazas y precio
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _plazasController,
                    decoration: const InputDecoration(
                      labelText: 'Plazas disponibles *',
                      prefixIcon: Icon(Icons.event_seat),
                      border: OutlineInputBorder(),
                    ),
                    keyboardType: TextInputType.number,
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Requerido';
                      }
                      final num = int.tryParse(value);
                      if (num == null || num < 1 || num > 8) {
                        return '1-8 plazas';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: TextFormField(
                    controller: _precioController,
                    decoration: const InputDecoration(
                      labelText: 'Precio por plaza',
                      prefixIcon: Icon(Icons.euro),
                      suffixText: '€',
                      border: OutlineInputBorder(),
                    ),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Notas
            TextFormField(
              controller: _notasController,
              decoration: const InputDecoration(
                labelText: 'Notas adicionales',
                hintText: 'Punto de encuentro, equipaje permitido, etc.',
                prefixIcon: Icon(Icons.notes),
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 24),

            // Boton guardar
            FilledButton.icon(
              onPressed: _guardando ? null : _guardarViaje,
              icon: _guardando
                  ? const FlavorInlineSpinner()
                  : const Icon(Icons.check),
              label: Text(_guardando
                  ? 'Guardando...'
                  : (_esEdicion ? 'Guardar cambios' : 'Publicar viaje')),
              style: FilledButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
