import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class ReservasScreen extends ConsumerStatefulWidget {
  const ReservasScreen({super.key});

  @override
  ConsumerState<ReservasScreen> createState() => _ReservasScreenState();
}

class _ReservasScreenState extends ConsumerState<ReservasScreen> {
  List<dynamic> _reservas = [];
  List<dynamic> _recursos = [];
  bool _loading = true;
  String? _error;
  String? _filtroEstado;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final apiClient = ref.read(apiClientProvider);

      // Cargar reservas y recursos en paralelo
      final futures = await Future.wait([
        apiClient.get('/reservas'),
        apiClient.get('/reservas/recursos'),
      ]);

      final responseReservas = futures[0];
      final responseRecursos = futures[1];

      if (responseReservas.success && responseReservas.data != null) {
        setState(() {
          _reservas = responseReservas.data!['reservas'] ??
              responseReservas.data!['items'] ??
              responseReservas.data!['data'] ??
              [];
        });
      }

      if (responseRecursos.success && responseRecursos.data != null) {
        setState(() {
          _recursos = responseRecursos.data!['recursos'] ??
              responseRecursos.data!['items'] ??
              responseRecursos.data!['data'] ??
              [];
        });
      }

      setState(() => _loading = false);

      if (!responseReservas.success) {
        setState(() => _error = responseReservas.error ?? 'Error al cargar reservas');
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  List<dynamic> get _reservasFiltradas {
    if (_filtroEstado == null) return _reservas;
    return _reservas.where((r) {
      final reserva = r as Map<String, dynamic>;
      final estado = (reserva['estado'] ?? reserva['status'] ?? '').toString().toLowerCase();
      return estado == _filtroEstado!.toLowerCase();
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sistema de Reservas'),
        actions: [
          if (_reservas.isNotEmpty)
            PopupMenuButton<String?>(
              icon: Badge(
                isLabelVisible: _filtroEstado != null,
                child: const Icon(Icons.filter_list),
              ),
              onSelected: (value) {
                setState(() => _filtroEstado = value);
              },
              itemBuilder: (context) => [
                const PopupMenuItem(
                  value: null,
                  child: Text('Todas'),
                ),
                const PopupMenuItem(
                  value: 'confirmada',
                  child: Row(
                    children: [
                      Icon(Icons.check_circle, color: Colors.green, size: 18),
                      SizedBox(width: 8),
                      Text('Confirmadas'),
                    ],
                  ),
                ),
                const PopupMenuItem(
                  value: 'pendiente',
                  child: Row(
                    children: [
                      Icon(Icons.schedule, color: Colors.orange, size: 18),
                      SizedBox(width: 8),
                      Text('Pendientes'),
                    ],
                  ),
                ),
                const PopupMenuItem(
                  value: 'cancelada',
                  child: Row(
                    children: [
                      Icon(Icons.cancel, color: Colors.red, size: 18),
                      SizedBox(width: 8),
                      Text('Canceladas'),
                    ],
                  ),
                ),
              ],
            ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.event_seat,
                          size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_error!, textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadData,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _reservasFiltradas.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.event_seat,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          Text(_filtroEstado != null
                              ? 'No hay reservas $_filtroEstado'
                              : 'No tienes reservas'),
                          const SizedBox(height: 8),
                          if (_filtroEstado == null)
                            const Text(
                              'Crea una nueva reserva para comenzar',
                              style: TextStyle(color: Colors.grey),
                            ),
                          if (_filtroEstado != null)
                            TextButton(
                              onPressed: () => setState(() => _filtroEstado = null),
                              child: const Text('Ver todas'),
                            ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadData,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _reservasFiltradas.length,
                        itemBuilder: (context, index) =>
                            _buildReservaCard(_reservasFiltradas[index]),
                      ),
                    ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _crearNuevaReserva(context),
        icon: const Icon(Icons.add),
        label: const Text('Nueva Reserva'),
      ),
    );
  }

  Widget _buildReservaCard(dynamic item) {
    final reservaMap = item as Map<String, dynamic>;
    final id = reservaMap['id'] ?? reservaMap['ID'] ?? 0;
    final recurso = reservaMap['recurso'] ??
        reservaMap['resource'] ??
        reservaMap['nombre'] ??
        reservaMap['title'] ??
        'Recurso';
    final fecha = reservaMap['fecha'] ??
        reservaMap['date'] ??
        reservaMap['fecha_reserva'] ??
        '';
    final horaInicio = reservaMap['hora_inicio'] ??
        reservaMap['start_time'] ??
        reservaMap['inicio'] ??
        '';
    final horaFin = reservaMap['hora_fin'] ??
        reservaMap['end_time'] ??
        reservaMap['fin'] ??
        '';
    final estado = reservaMap['estado'] ??
        reservaMap['status'] ??
        'pendiente';
    final notas = reservaMap['notas'] ?? reservaMap['notes'] ?? '';

    Color estadoColor;
    IconData estadoIcon;
    switch (estado.toString().toLowerCase()) {
      case 'confirmada':
      case 'confirmed':
      case 'aprobada':
        estadoColor = Colors.green;
        estadoIcon = Icons.check_circle;
        break;
      case 'pendiente':
      case 'pending':
        estadoColor = Colors.orange;
        estadoIcon = Icons.schedule;
        break;
      case 'cancelada':
      case 'cancelled':
        estadoColor = Colors.red;
        estadoIcon = Icons.cancel;
        break;
      default:
        estadoColor = Colors.grey;
        estadoIcon = Icons.info;
    }

    final puedeModificar = estado.toString().toLowerCase() == 'pendiente' ||
        estado.toString().toLowerCase() == 'pending';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () => _verDetalleReserva(context, reservaMap),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    backgroundColor: estadoColor.withOpacity(0.1),
                    child: Icon(Icons.event_seat, color: estadoColor),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          recurso.toString(),
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        Row(
                          children: [
                            Icon(estadoIcon, size: 14, color: estadoColor),
                            const SizedBox(width: 4),
                            Text(
                              estado.toString(),
                              style: TextStyle(
                                color: estadoColor,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  if (puedeModificar)
                    PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'cancelar') {
                          _cancelarReserva(id, recurso.toString());
                        } else if (value == 'editar') {
                          _editarReserva(context, reservaMap);
                        }
                      },
                      itemBuilder: (context) => [
                        const PopupMenuItem(
                          value: 'editar',
                          child: Row(
                            children: [
                              Icon(Icons.edit, size: 18),
                              SizedBox(width: 8),
                              Text('Editar'),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'cancelar',
                          child: Row(
                            children: [
                              Icon(Icons.cancel, size: 18, color: Colors.red),
                              SizedBox(width: 8),
                              Text('Cancelar', style: TextStyle(color: Colors.red)),
                            ],
                          ),
                        ),
                      ],
                    ),
                ],
              ),
              const Divider(height: 24),
              Row(
                children: [
                  Expanded(
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today, size: 16, color: Colors.grey),
                        const SizedBox(width: 8),
                        Text(
                          _formatDate(fecha.toString()),
                          style: const TextStyle(fontSize: 14),
                        ),
                      ],
                    ),
                  ),
                  if (horaInicio.toString().isNotEmpty || horaFin.toString().isNotEmpty)
                    Row(
                      children: [
                        const Icon(Icons.access_time, size: 16, color: Colors.grey),
                        const SizedBox(width: 8),
                        Text(
                          '${horaInicio.toString()} - ${horaFin.toString()}',
                          style: const TextStyle(fontSize: 14),
                        ),
                      ],
                    ),
                ],
              ),
              if (notas.toString().isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  notas.toString(),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  void _verDetalleReserva(BuildContext context, Map<String, dynamic> reserva) {
    final recurso = reserva['recurso'] ?? reserva['resource'] ?? 'Recurso';
    final fecha = reserva['fecha'] ?? reserva['date'] ?? '';
    final horaInicio = reserva['hora_inicio'] ?? reserva['start_time'] ?? '';
    final horaFin = reserva['hora_fin'] ?? reserva['end_time'] ?? '';
    final estado = reserva['estado'] ?? reserva['status'] ?? 'pendiente';
    final notas = reserva['notas'] ?? reserva['notes'] ?? '';
    final fechaCreacion = reserva['fecha_creacion'] ?? reserva['created_at'] ?? '';
    final ubicacion = reserva['ubicacion'] ?? reserva['location'] ?? '';

    Color estadoColor;
    switch (estado.toString().toLowerCase()) {
      case 'confirmada':
      case 'confirmed':
        estadoColor = Colors.green;
        break;
      case 'pendiente':
      case 'pending':
        estadoColor = Colors.orange;
        break;
      case 'cancelada':
      case 'cancelled':
        estadoColor = Colors.red;
        break;
      default:
        estadoColor = Colors.grey;
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.6,
        minChildSize: 0.3,
        maxChildSize: 0.9,
        expand: false,
        builder: (context, scrollController) => ListView(
          controller: scrollController,
          padding: const EdgeInsets.all(16),
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            Row(
              children: [
                Expanded(
                  child: Text(
                    recurso.toString(),
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                Chip(
                  label: Text(
                    estado.toString(),
                    style: const TextStyle(color: Colors.white),
                  ),
                  backgroundColor: estadoColor,
                ),
              ],
            ),
            const Divider(height: 32),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.calendar_today),
                      title: const Text('Fecha'),
                      subtitle: Text(_formatDate(fecha.toString())),
                    ),
                    if (horaInicio.toString().isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.access_time),
                        title: const Text('Horario'),
                        subtitle: Text('${horaInicio.toString()} - ${horaFin.toString()}'),
                      ),
                    if (ubicacion.toString().isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.location_on),
                        title: const Text('Ubicación'),
                        subtitle: Text(ubicacion.toString()),
                      ),
                    if (fechaCreacion.toString().isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.history),
                        title: const Text('Reservado el'),
                        subtitle: Text(_formatDate(fechaCreacion.toString())),
                      ),
                  ],
                ),
              ),
            ),
            if (notas.toString().isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Notas',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const Divider(),
                      Text(notas.toString()),
                    ],
                  ),
                ),
              ),
            ],
            const SizedBox(height: 24),
            if (estado.toString().toLowerCase() == 'pendiente')
              FilledButton.icon(
                onPressed: () {
                  Navigator.pop(context);
                  _cancelarReserva(
                    reserva['id'] ?? reserva['ID'],
                    recurso.toString(),
                  );
                },
                icon: const Icon(Icons.cancel),
                label: const Text('Cancelar reserva'),
                style: FilledButton.styleFrom(backgroundColor: Colors.red),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _crearNuevaReserva(BuildContext context) async {
    final resultado = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (context) => NuevaReservaScreen(recursos: _recursos),
      ),
    );

    if (resultado == true) {
      _loadData();
    }
  }

  Future<void> _editarReserva(BuildContext context, Map<String, dynamic> reserva) async {
    final resultado = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (context) => NuevaReservaScreen(
          recursos: _recursos,
          reservaExistente: reserva,
        ),
      ),
    );

    if (resultado == true) {
      _loadData();
    }
  }

  Future<void> _cancelarReserva(dynamic id, String recurso) async {
    final confirmado = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancelar reserva'),
        content: Text('¿Seguro que deseas cancelar la reserva de "$recurso"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('No'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Sí, cancelar'),
          ),
        ],
      ),
    );

    if (confirmado != true) return;

    final api = ref.read(apiClientProvider);
    final response = await api.post(
      '/reservas/$id/cancelar',
      data: {},
    );

    if (mounted) {
      if (response.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Reserva cancelada correctamente'),
            backgroundColor: Colors.orange,
          ),
        );
        _loadData();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al cancelar'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
    } catch (_) {
      return dateStr;
    }
  }
}

/// Pantalla para crear/editar reserva
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
        } catch (_) {}
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
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
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
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Selecciona un recurso'),
          backgroundColor: Colors.red,
        ),
      );
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
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_esEdicion
                  ? 'Reserva actualizada correctamente'
                  : 'Reserva creada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          Navigator.of(context).pop(true);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al guardar'),
              backgroundColor: Colors.red,
            ),
          );
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
