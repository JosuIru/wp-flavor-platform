import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'carpooling_screen_parts.dart';

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
          _mensajeError =
              respuesta.error ?? AppLocalizations.of(context).carpoolingLoadError;
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
    final i18n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.carpoolingTitle),
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
                _construirFiltroChip(i18n.carpoolingFilterAll, 'todos'),
                const SizedBox(width: 8),
                _construirFiltroChip(i18n.carpoolingFilterOffer, 'ofrezco'),
                const SizedBox(width: 8),
                _construirFiltroChip(i18n.carpoolingFilterSearch, 'busco'),
              ],
            ),
          ),
          // Contenido
          Expanded(
            child: _cargandoDatos
                ? const FlavorLoadingState()
                : _mensajeError != null
                    ? FlavorErrorState(
                        message: _mensajeError!,
                        onRetry: _cargarViajes,
                        icon: Icons.directions_car,
                      )
                    : _viajesCompartidos.isEmpty
                        ? FlavorEmptyState(
                            icon: Icons.directions_car,
                            title: i18n.carpoolingEmpty,
                            action: FilledButton.icon(
                              onPressed: _mostrarFormularioNuevoViaje,
                              icon: const Icon(Icons.add),
                              label: Text(i18n.carpoolingPublishRide),
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
        label: Text(i18n.carpoolingOfferRide),
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
    final i18n = AppLocalizations.of(context);
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
                      PopupMenuItem(value: 'editar', child: Text(i18n.commonEdit)),
                      PopupMenuItem(
                        value: 'eliminar',
                        child: Text(i18n.commonDelete,
                            style: const TextStyle(color: Colors.red)),
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
                    Text(i18n.commonPlacesAvailable(
                        int.tryParse(plazasDisponibles.toString()) ?? 0)),
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
                  Text(i18n.carpoolingDriverLabel(conductorViaje.toString())),
                ],
              ),
            ],
            const SizedBox(height: 12),
            if (!esMio && plazasDisponibles > 0)
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => _reservarPlaza(viajeId, datosViaje),
                  child: Text(i18n.carpoolingReserveSeat),
                ),
              )
            else if (!esMio && plazasDisponibles == 0)
              SizedBox(
                width: double.infinity,
                child: Text(
                  i18n.carpoolingNoSeats,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.grey),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _reservarPlaza(String viajeId, Map<String, dynamic> viaje) async {
    final i18n = AppLocalizations.of(context);
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.carpoolingReserveDialogTitle),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(i18n.carpoolingReserveOrigin(
                (viaje['origen'] ?? viaje['from'] ?? 'N/A').toString())),
            Text(i18n.carpoolingReserveDestination(
                (viaje['destino'] ?? viaje['to'] ?? 'N/A').toString())),
            const SizedBox(height: 8),
            Text(i18n.carpoolingReserveDate(
                (viaje['fecha_salida'] ?? viaje['fecha'] ?? 'N/A').toString())),
            Text(i18n.carpoolingReserveTime(
                (viaje['hora_salida'] ?? viaje['hora'] ?? 'N/A').toString())),
            const SizedBox(height: 16),
            Text(i18n.carpoolingReserveConfirmQuestion),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.carpoolingReserveAction),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/carpooling/viajes/$viajeId/reservar', data: {});

      if (mounted) {
        if (respuesta.success) {
          FlavorSnackbar.showSuccess(context, i18n.carpoolingSeatReserved);
          _cargarViajes();
        } else {
          FlavorSnackbar.showError(
              context, respuesta.error ?? i18n.carpoolingReserveError);
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, i18n.commonError(e.toString()));
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
    final i18n = AppLocalizations.of(context);
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.carpoolingDeleteRideTitle),
        content: Text(i18n.carpoolingDeleteRideConfirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.commonDelete),
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
          FlavorSnackbar.showSuccess(context, i18n.carpoolingRideDeleted);
          _cargarViajes();
        } else {
          FlavorSnackbar.showError(
              context, respuesta.error ?? i18n.carpoolingDeleteError);
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, i18n.commonError(e.toString()));
      }
    }
  }
}
