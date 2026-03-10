import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class CarpoolingScreen extends ConsumerStatefulWidget {
  const CarpoolingScreen({super.key});

  @override
  ConsumerState<CarpoolingScreen> createState() => _CarpoolingScreenState();
}

class _CarpoolingScreenState extends ConsumerState<CarpoolingScreen> {
  List<dynamic> _viajesCompartidos = [];
  bool _cargandoDatos = true;
  String? _mensajeError;
  String _filtroTipo = 'todos'; // todos, ofrezco, busco

  @override
  void initState() {
    super.initState();
    _cargarViajes();
  }

  Future<void> _cargarViajes() async {
    setState(() {
      _cargandoDatos = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final queryParams = <String, dynamic>{};
      if (_filtroTipo != 'todos') {
        queryParams['tipo'] = _filtroTipo;
      }
      final respuesta = await clienteApi.get('/carpooling/viajes', queryParameters: queryParams);
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _viajesCompartidos = respuesta.data!['viajes'] ??
              respuesta.data!['items'] ??
              respuesta.data!['data'] ??
              [];
          _cargandoDatos = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar viajes';
          _cargandoDatos = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargandoDatos = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Viajes Compartidos'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarViajes,
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtros
          Container(
            height: 50,
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                _construirFiltroChip('Todos', 'todos'),
                const SizedBox(width: 8),
                _construirFiltroChip('Ofrezco viaje', 'ofrezco'),
                const SizedBox(width: 8),
                _construirFiltroChip('Busco viaje', 'busco'),
              ],
            ),
          ),
          // Contenido
          Expanded(
            child: _cargandoDatos
                ? const Center(child: CircularProgressIndicator())
                : _mensajeError != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.directions_car,
                                size: 64, color: Colors.grey),
                            const SizedBox(height: 16),
                            Text(_mensajeError!),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _cargarViajes,
                              child: const Text('Reintentar'),
                            ),
                          ],
                        ),
                      )
                    : _viajesCompartidos.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.directions_car,
                                    size: 64, color: Colors.grey.shade400),
                                const SizedBox(height: 16),
                                const Text('No hay viajes disponibles'),
                                const SizedBox(height: 16),
                                FilledButton.icon(
                                  onPressed: _mostrarFormularioNuevoViaje,
                                  icon: const Icon(Icons.add),
                                  label: const Text('Publicar viaje'),
                                ),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _cargarViajes,
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: _viajesCompartidos.length,
                              itemBuilder: (context, indice) =>
                                  _construirTarjetaViaje(_viajesCompartidos[indice]),
                            ),
                          ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _mostrarFormularioNuevoViaje,
        icon: const Icon(Icons.add),
        label: const Text('Ofrecer viaje'),
      ),
    );
  }

  Widget _construirFiltroChip(String label, String valor) {
    final seleccionado = _filtroTipo == valor;
    return FilterChip(
      label: Text(label),
      selected: seleccionado,
      onSelected: (_) {
        setState(() => _filtroTipo = valor);
        _cargarViajes();
      },
    );
  }

  void _mostrarFormularioNuevoViaje() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => NuevoViajeScreen(onViajeCreado: _cargarViajes),
      ),
    );
  }

  Widget _construirTarjetaViaje(dynamic viaje) {
    final datosViaje = viaje as Map<String, dynamic>;
    final viajeId = datosViaje['id']?.toString() ?? '';
    final origenViaje =
        datosViaje['origen'] ?? datosViaje['from'] ?? 'Origen desconocido';
    final destinoViaje =
        datosViaje['destino'] ?? datosViaje['to'] ?? 'Destino desconocido';
    final fechaSalidaViaje = datosViaje['fecha_salida'] ??
        datosViaje['fecha'] ??
        datosViaje['date'] ??
        '';
    final horaSalidaViaje =
        datosViaje['hora_salida'] ?? datosViaje['hora'] ?? datosViaje['time'] ?? '';
    final plazasDisponibles = datosViaje['plazas_disponibles'] ??
        datosViaje['plazas'] ??
        datosViaje['seats'] ??
        0;
    final precioViaje =
        datosViaje['precio'] ?? datosViaje['price'] ?? datosViaje['cost'] ?? '';
    final conductorViaje = datosViaje['conductor'] ??
        datosViaje['driver'] ??
        datosViaje['nombre_conductor'] ??
        '';
    final tipoViaje = datosViaje['tipo'] ?? 'ofrezco';
    final esMio = datosViaje['es_mio'] == true;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: tipoViaje == 'busco'
                      ? Colors.orange.shade100
                      : Colors.green.shade100,
                  child: Icon(
                    tipoViaje == 'busco' ? Icons.search : Icons.directions_car,
                    color: tipoViaje == 'busco' ? Colors.orange : Colors.green,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.trip_origin,
                              size: 16, color: Colors.green),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              origenViaje,
                              style: const TextStyle(fontWeight: FontWeight.w500),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(Icons.location_on,
                              size: 16, color: Colors.red),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              destinoViaje,
                              style: const TextStyle(fontWeight: FontWeight.w500),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                if (esMio)
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'editar') {
                        _editarViaje(datosViaje);
                      } else if (value == 'eliminar') {
                        _eliminarViaje(viajeId);
                      }
                    },
                    itemBuilder: (context) => [
                      const PopupMenuItem(value: 'editar', child: Text('Editar')),
                      const PopupMenuItem(
                        value: 'eliminar',
                        child: Text('Eliminar', style: TextStyle(color: Colors.red)),
                      ),
                    ],
                  ),
              ],
            ),
            const Divider(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                if (fechaSalidaViaje.toString().isNotEmpty || horaSalidaViaje.toString().isNotEmpty)
                  Row(
                    children: [
                      const Icon(Icons.schedule, size: 16, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text('$fechaSalidaViaje $horaSalidaViaje'.trim()),
                    ],
                  ),
                Row(
                  children: [
                    const Icon(Icons.event_seat, size: 16, color: Colors.grey),
                    const SizedBox(width: 4),
                    Text('$plazasDisponibles plazas'),
                  ],
                ),
                if (precioViaje.toString().isNotEmpty)
                  Text(
                    precioViaje.toString().contains('\$') ||
                            precioViaje.toString().contains('EUR') ||
                            precioViaje.toString().contains('€')
                        ? precioViaje.toString()
                        : '$precioViaje €',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.green,
                    ),
                  ),
              ],
            ),
            if (conductorViaje.toString().isNotEmpty) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.person, size: 16, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text('Conductor: $conductorViaje'),
                ],
              ),
            ],
            const SizedBox(height: 12),
            if (!esMio && plazasDisponibles > 0)
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => _reservarPlaza(viajeId, datosViaje),
                  child: const Text('Reservar plaza'),
                ),
              )
            else if (!esMio && plazasDisponibles == 0)
              const SizedBox(
                width: double.infinity,
                child: Text(
                  'Sin plazas disponibles',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.grey),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _reservarPlaza(String viajeId, Map<String, dynamic> viaje) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Reservar plaza'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Origen: ${viaje['origen'] ?? viaje['from'] ?? 'N/A'}'),
            Text('Destino: ${viaje['destino'] ?? viaje['to'] ?? 'N/A'}'),
            const SizedBox(height: 8),
            Text('Fecha: ${viaje['fecha_salida'] ?? viaje['fecha'] ?? 'N/A'}'),
            Text('Hora: ${viaje['hora_salida'] ?? viaje['hora'] ?? 'N/A'}'),
            const SizedBox(height: 16),
            const Text('¿Confirmas la reserva de una plaza?'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Reservar'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/carpooling/viajes/$viajeId/reservar');

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Plaza reservada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarViajes();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al reservar'),
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

  void _editarViaje(Map<String, dynamic> viaje) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => NuevoViajeScreen(
          viajeExistente: viaje,
          onViajeCreado: _cargarViajes,
        ),
      ),
    );
  }

  Future<void> _eliminarViaje(String viajeId) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar viaje'),
        content: const Text('¿Estas seguro de que quieres eliminar este viaje?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.delete('/carpooling/viajes/$viajeId');

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Viaje eliminado'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarViajes();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al eliminar'),
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

/// Pantalla para crear o editar un viaje compartido
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
        } catch (_) {}
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
        } catch (_) {}
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
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_esEdicion ? 'Viaje actualizado' : 'Viaje publicado'),
              backgroundColor: Colors.green,
            ),
          );
          widget.onViajeCreado();
          Navigator.pop(context);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al guardar'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _guardando = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
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
                    keyboardType: TextInputType.numberWithOptions(decimal: true),
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
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
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
