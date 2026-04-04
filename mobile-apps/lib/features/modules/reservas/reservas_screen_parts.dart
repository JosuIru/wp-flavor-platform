part of 'reservas_screen.dart';

class NuevaReservaScreen extends ConsumerStatefulWidget {
  final List<dynamic> recursos;
  final Map<String, dynamic>? reservaExistente;

  const NuevaReservaScreen({
    super.key,
    required this.recursos,
    this.reservaExistente,
  });

  @override
  ConsumerState<NuevaReservaScreen> createState() => _NuevaReservaScreenState();
}

class _NuevaReservaScreenState extends ConsumerState<NuevaReservaScreen> {
  final _formKey = GlobalKey<FormState>();
  final _notasController = TextEditingController();

  dynamic _recursoSeleccionado;
  DateTime _fechaSeleccionada = DateTime.now();
  TimeOfDay _horaInicio = const TimeOfDay(hour: 9, minute: 0);
  TimeOfDay _horaFin = const TimeOfDay(hour: 10, minute: 0);
  bool _guardando = false;

  bool get _esEdicion => widget.reservaExistente != null;

  @override
  void initState() {
    super.initState();
    if (_esEdicion) {
      final reserva = widget.reservaExistente!;
      _notasController.text = reserva['notas'] ?? reserva['notes'] ?? '';

      // Intentar encontrar el recurso
      final recursoId = reserva['recurso_id'] ?? reserva['resource_id'];
      if (recursoId != null && widget.recursos.isNotEmpty) {
        _recursoSeleccionado = widget.recursos.firstWhere(
          (r) => (r as Map)['id'] == recursoId,
          orElse: () => null,
        );
      }

      // Parsear fecha
      final fechaStr = reserva['fecha'] ?? reserva['date'];
      if (fechaStr != null) {
        try {
          _fechaSeleccionada = DateTime.parse(fechaStr);
        } catch (e) {
          debugPrint('Error parseando fecha de reserva: $e');
        }
      }

      // Parsear horas
      final horaInicioStr = reserva['hora_inicio'] ?? reserva['start_time'];
      final horaFinStr = reserva['hora_fin'] ?? reserva['end_time'];
      if (horaInicioStr != null) {
        _horaInicio = _parseTime(horaInicioStr.toString());
      }
      if (horaFinStr != null) {
        _horaFin = _parseTime(horaFinStr.toString());
      }
    }
  }

  TimeOfDay _parseTime(String timeStr) {
    try {
      final parts = timeStr.split(':');
      return TimeOfDay(
        hour: int.parse(parts[0]),
        minute: int.parse(parts[1]),
      );
    } catch (_) {
      return const TimeOfDay(hour: 9, minute: 0);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_esEdicion ? 'Editar Reserva' : 'Nueva Reserva'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Selección de recurso
            if (widget.recursos.isNotEmpty) ...[
              Text(
                'Recurso a reservar',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              DropdownButtonFormField<dynamic>(
                value: _recursoSeleccionado,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.event_seat),
                  border: OutlineInputBorder(),
                  hintText: 'Selecciona un recurso',
                ),
                items: widget.recursos.map((recurso) {
                  final r = recurso as Map<String, dynamic>;
                  final nombre = r['nombre'] ?? r['name'] ?? r['titulo'] ?? 'Recurso';
                  return DropdownMenuItem(
                    value: recurso,
                    child: Text(nombre.toString()),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() => _recursoSeleccionado = value);
                },
                validator: (value) {
                  if (value == null) {
                    return 'Selecciona un recurso';
                  }
                  return null;
                },
              ),
            ] else
              Card(
                color: Colors.orange.withOpacity(0.1),
                child: const Padding(
                  padding: EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Icon(Icons.warning, color: Colors.orange),
                      SizedBox(width: 12),
                      Expanded(
                        child: Text('No hay recursos disponibles para reservar'),
                      ),
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 24),

            // Selección de fecha
            Text(
              'Fecha',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            InkWell(
              onTap: () => _seleccionarFecha(context),
              child: InputDecorator(
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.calendar_today),
                  border: OutlineInputBorder(),
                ),
                child: Text(_formatDateDisplay(_fechaSeleccionada)),
              ),
            ),
            const SizedBox(height: 24),

            // Selección de horario
            Text(
              'Horario',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () => _seleccionarHora(context, true),
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'Inicio',
                        prefixIcon: Icon(Icons.access_time),
                        border: OutlineInputBorder(),
                      ),
                      child: Text(_formatTime(_horaInicio)),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: InkWell(
                    onTap: () => _seleccionarHora(context, false),
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'Fin',
                        prefixIcon: Icon(Icons.access_time),
                        border: OutlineInputBorder(),
                      ),
                      child: Text(_formatTime(_horaFin)),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Notas
            Text(
              'Notas (opcional)',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            TextFormField(
              controller: _notasController,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.note),
                border: OutlineInputBorder(),
                hintText: 'Añade notas adicionales...',
                alignLabelWithHint: true,
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 32),

            // Botón de guardar
            FilledButton.icon(
              onPressed: (widget.recursos.isEmpty || _guardando) ? null : _guardarReserva,
              icon: _guardando
                  ? const FlavorInlineSpinner()
                  : const Icon(Icons.check),
              label: Text(_guardando
                  ? 'Guardando...'
                  : (_esEdicion ? 'Guardar Cambios' : 'Crear Reserva')),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _seleccionarFecha(BuildContext context) async {
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

  Future<void> _seleccionarHora(BuildContext context, bool esInicio) async {
    final hora = await showTimePicker(
      context: context,
      initialTime: esInicio ? _horaInicio : _horaFin,
    );

    if (hora != null) {
      setState(() {
        if (esInicio) {
          _horaInicio = hora;
          // Ajustar hora fin si es menor que inicio
          if (_timeToMinutes(_horaFin) <= _timeToMinutes(_horaInicio)) {
            _horaFin = TimeOfDay(hour: _horaInicio.hour + 1, minute: _horaInicio.minute);
          }
        } else {
          _horaFin = hora;
        }
      });
    }
  }

  int _timeToMinutes(TimeOfDay time) => time.hour * 60 + time.minute;

  Future<void> _guardarReserva() async {
    if (!_formKey.currentState!.validate()) return;

    if (_recursoSeleccionado == null) {
      FlavorSnackbar.showError(context, 'Selecciona un recurso');
      return;
    }

    setState(() => _guardando = true);

    try {
      final api = ref.read(apiClientProvider);
      final recurso = _recursoSeleccionado as Map<String, dynamic>;
      final recursoId = recurso['id'] ?? recurso['ID'];

      final datos = {
        'recurso_id': recursoId,
        'fecha': '${_fechaSeleccionada.year}-${_fechaSeleccionada.month.toString().padLeft(2, '0')}-${_fechaSeleccionada.day.toString().padLeft(2, '0')}',
        'hora_inicio': _formatTime(_horaInicio),
        'hora_fin': _formatTime(_horaFin),
        'notas': _notasController.text.trim(),
      };

      final ApiResponse<Map<String, dynamic>> response;
      if (_esEdicion) {
        final idReserva = widget.reservaExistente!['id'] ?? widget.reservaExistente!['ID'];
        response = await api.put('/reservas/$idReserva', data: datos);
      } else {
        response = await api.post('/reservas', data: datos);
      }

      if (mounted) {
        if (response.success) {
          FlavorSnackbar.showSuccess(context, _esEdicion ? 'Reserva actualizada correctamente' : 'Reserva creada correctamente');
          Navigator.of(context).pop(true);
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al guardar');
        }
      }
    } finally {
      if (mounted) {
        setState(() => _guardando = false);
      }
    }
  }

  String _formatDateDisplay(DateTime date) {
    const dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return '${dias[date.weekday - 1]}, ${date.day} de ${meses[date.month - 1]} ${date.year}';
  }

  String _formatTime(TimeOfDay time) {
    return '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
  }

  @override
  void dispose() {
    _notasController.dispose();
    super.dispose();
  }
}
