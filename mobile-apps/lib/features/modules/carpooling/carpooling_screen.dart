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
      final respuesta = await clienteApi.get('/carpooling/viajes');
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
      body: _cargandoDatos
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
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          // TODO: Crear nuevo viaje
        },
        icon: const Icon(Icons.add),
        label: const Text('Ofrecer viaje'),
      ),
    );
  }

  Widget _construirTarjetaViaje(dynamic viaje) {
    final datosViaje = viaje as Map<String, dynamic>;
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
                  backgroundColor: Colors.green.shade100,
                  child: const Icon(Icons.directions_car, color: Colors.green),
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
              ],
            ),
            const Divider(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                if (fechaSalidaViaje.isNotEmpty || horaSalidaViaje.isNotEmpty)
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
                            precioViaje.toString().contains('EUR')
                        ? precioViaje.toString()
                        : '$precioViaje EUR',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.green,
                    ),
                  ),
              ],
            ),
            if (conductorViaje.isNotEmpty) ...[
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
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  // TODO: Reservar plaza en el viaje
                },
                child: const Text('Reservar plaza'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
