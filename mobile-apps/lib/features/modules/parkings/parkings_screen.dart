import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import 'package:intl/intl.dart';

class ParkingsScreen extends ConsumerStatefulWidget {
  const ParkingsScreen({super.key});

  @override
  ConsumerState<ParkingsScreen> createState() => _ParkingsScreenState();
}

class _ParkingsScreenState extends ConsumerState<ParkingsScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getParkings();
  }

  Future<void> _refresh() async {
    setState(() => _future = ref.read(apiClientProvider).getParkings());
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Parkings'),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.parkingsTabAvailable, icon: const Icon(Icons.local_parking)),
              Tab(text: i18n.parkingsTabReservations, icon: const Icon(Icons.bookmark)),
            ],
          ),
        ),
        body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
          future: _future,
          builder: (context, snapshot) {
            if (!snapshot.hasData) {
              return const Center(child: CircularProgressIndicator());
            }

            final response = snapshot.data!;
            if (!response.success || response.data == null) {
              return Center(child: Text(i18n.parkingsError));
            }

            final data = response.data!;
            final parkings = (data['parkings'] as List?)?.cast<Map<String, dynamic>>() ?? [];
            final reservas = (data['mis_reservas'] as List?)?.cast<Map<String, dynamic>>() ?? [];

            return TabBarView(
              children: [
                _buildParkingsTab(context, parkings, i18n),
                _buildReservasTab(context, reservas, i18n),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildParkingsTab(BuildContext context, List<Map<String, dynamic>> parkings, AppLocalizations i18n) {
    if (parkings.isEmpty) {
      return Center(child: Text(i18n.parkingsNoParkings));
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: parkings.length,
        itemBuilder: (context, index) {
          final parking = parkings[index];
          final id = (parking['id'] as num?)?.toInt() ?? 0;
          final nombre = parking['nombre']?.toString() ?? '';
          final direccion = parking['direccion']?.toString() ?? '';
          final plazasDisponibles = (parking['plazas_disponibles'] as num?)?.toInt() ?? 0;
          final plazasTotales = (parking['plazas_totales'] as num?)?.toInt() ?? 0;
          final tarifaHora = parking['tarifa_hora']?.toString() ?? '0';
          final tarifaDia = parking['tarifa_dia']?.toString() ?? '0';
          final distancia = parking['distancia']?.toString() ?? '';
          final tipo = parking['tipo']?.toString() ?? '';

          final ocupacion = plazasTotales > 0 ? plazasDisponibles / plazasTotales : 0.0;
          final color = ocupacion > 0.3 ? Colors.green : (ocupacion > 0.1 ? Colors.orange : Colors.red);

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: InkWell(
              onTap: () => _verDetalleParking(context, parking),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: color.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Icon(Icons.local_parking, size: 32, color: color),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                nombre,
                                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                              ),
                              if (tipo.isNotEmpty)
                                Chip(
                                  label: Text(tipo),
                                  visualDensity: VisualDensity.compact,
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        const Icon(Icons.place, size: 16),
                        const SizedBox(width: 4),
                        Expanded(child: Text(direccion)),
                        if (distancia.isNotEmpty)
                          Text('$distancia km', style: TextStyle(color: Colors.blue[700])),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(Icons.local_parking, size: 16, color: color),
                        const SizedBox(width: 4),
                        Text('$plazasDisponibles/$plazasTotales ${i18n.parkingsAvailable}'),
                      ],
                    ),
                    const SizedBox(height: 8),
                    LinearProgressIndicator(
                      value: ocupacion,
                      backgroundColor: Colors.grey[300],
                      color: color,
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${i18n.parkingsPerHour}: $tarifaHora€', style: Theme.of(context).textTheme.bodySmall),
                            Text('${i18n.parkingsPerDay}: $tarifaDia€', style: Theme.of(context).textTheme.bodySmall),
                          ],
                        ),
                        FilledButton(
                          onPressed: plazasDisponibles > 0 ? () => _reservarParking(context, parking) : null,
                          child: Text(i18n.parkingsReserve),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildReservasTab(BuildContext context, List<Map<String, dynamic>> reservas, AppLocalizations i18n) {
    if (reservas.isEmpty) {
      return Center(child: Text(i18n.parkingsNoReservations));
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: reservas.length,
        itemBuilder: (context, index) {
          final reserva = reservas[index];
          final id = (reserva['id'] as num?)?.toInt() ?? 0;
          final parkingNombre = reserva['parking_nombre']?.toString() ?? '';
          final fechaEntrada = reserva['fecha_entrada']?.toString() ?? '';
          final fechaSalida = reserva['fecha_salida']?.toString() ?? '';
          final plaza = reserva['plaza']?.toString() ?? '';
          final estado = reserva['estado']?.toString() ?? 'pendiente';
          final coste = reserva['coste']?.toString() ?? '0';

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: _getEstadoColor(estado),
                child: Icon(_getEstadoIcon(estado), color: Colors.white, size: 20),
              ),
              title: Text(parkingNombre),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (plaza.isNotEmpty) Text('${i18n.parkingsSpace}: $plaza'),
                  Row(
                    children: [
                      const Icon(Icons.arrow_forward, size: 14),
                      const SizedBox(width: 4),
                      Text(fechaEntrada),
                    ],
                  ),
                  Row(
                    children: [
                      const Icon(Icons.arrow_back, size: 14),
                      const SizedBox(width: 4),
                      Text(fechaSalida),
                    ],
                  ),
                  Text('$coste €', style: TextStyle(color: Colors.green[700], fontWeight: FontWeight.bold)),
                  Chip(
                    label: Text(_getEstadoLabel(estado, i18n)),
                    visualDensity: VisualDensity.compact,
                    backgroundColor: _getEstadoColor(estado).withOpacity(0.2),
                  ),
                ],
              ),
              trailing: estado == 'activa'
                  ? PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'extend') {
                          _extenderReserva(context, id);
                        } else if (value == 'cancel') {
                          _cancelarReserva(context, id);
                        }
                      },
                      itemBuilder: (context) => [
                        PopupMenuItem(value: 'extend', child: Text(i18n.parkingsExtend)),
                        PopupMenuItem(value: 'cancel', child: Text(i18n.parkingsCancel)),
                      ],
                    )
                  : null,
            ),
          );
        },
      ),
    );
  }

  Color _getEstadoColor(String estado) {
    switch (estado) {
      case 'activa':
        return Colors.green;
      case 'pendiente':
        return Colors.orange;
      case 'completada':
        return Colors.blue;
      case 'cancelada':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  IconData _getEstadoIcon(String estado) {
    switch (estado) {
      case 'activa':
        return Icons.check_circle;
      case 'pendiente':
        return Icons.hourglass_empty;
      case 'completada':
        return Icons.done_all;
      case 'cancelada':
        return Icons.cancel;
      default:
        return Icons.info;
    }
  }

  String _getEstadoLabel(String estado, AppLocalizations i18n) {
    switch (estado) {
      case 'activa':
        return i18n.parkingsStatusActive;
      case 'pendiente':
        return i18n.parkingsStatusPending;
      case 'completada':
        return i18n.parkingsStatusCompleted;
      case 'cancelada':
        return i18n.parkingsStatusCancelled;
      default:
        return estado;
    }
  }

  void _verDetalleParking(BuildContext context, Map<String, dynamic> parking) {
    final i18n = AppLocalizations.of(context)!;
    showModalBottomSheet(
      context: context,
      builder: (context) {
        final nombre = parking['nombre']?.toString() ?? '';
        final descripcion = parking['descripcion']?.toString() ?? '';
        final horario = parking['horario']?.toString() ?? '';
        final servicios = parking['servicios']?.toString() ?? '';

        return Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(nombre, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              if (descripcion.isNotEmpty) ...[
                Text(i18n.parkingsDescription, style: Theme.of(context).textTheme.titleMedium),
                Text(descripcion),
                const SizedBox(height: 12),
              ],
              if (horario.isNotEmpty) ...[
                Row(
                  children: [
                    const Icon(Icons.access_time, size: 20),
                    const SizedBox(width: 8),
                    Text(horario),
                  ],
                ),
                const SizedBox(height: 12),
              ],
              if (servicios.isNotEmpty) ...[
                Text(i18n.parkingsServices, style: Theme.of(context).textTheme.titleMedium),
                Wrap(
                  spacing: 8,
                  children: servicios.split(',').map((s) => Chip(label: Text(s.trim()))).toList(),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  Future<void> _reservarParking(BuildContext context, Map<String, dynamic> parking) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);
    final parkingId = (parking['id'] as num?)?.toInt() ?? 0;

    DateTime fechaEntrada = DateTime.now();
    DateTime fechaSalida = DateTime.now().add(const Duration(hours: 2));

    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(i18n.parkingsReserve),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  ListTile(
                    leading: const Icon(Icons.calendar_today),
                    title: Text(i18n.parkingsEntryDate),
                    subtitle: Text(DateFormat('dd/MM/yyyy HH:mm').format(fechaEntrada)),
                    onTap: () async {
                      final date = await showDatePicker(
                        context: context,
                        initialDate: fechaEntrada,
                        firstDate: DateTime.now(),
                        lastDate: DateTime.now().add(const Duration(days: 30)),
                      );
                      if (date != null) {
                        final time = await showTimePicker(context: context, initialTime: TimeOfDay.fromDateTime(fechaEntrada));
                        if (time != null) {
                          setDialogState(() {
                            fechaEntrada = DateTime(date.year, date.month, date.day, time.hour, time.minute);
                          });
                        }
                      }
                    },
                  ),
                  ListTile(
                    leading: const Icon(Icons.calendar_today),
                    title: Text(i18n.parkingsExitDate),
                    subtitle: Text(DateFormat('dd/MM/yyyy HH:mm').format(fechaSalida)),
                    onTap: () async {
                      final date = await showDatePicker(
                        context: context,
                        initialDate: fechaSalida,
                        firstDate: fechaEntrada,
                        lastDate: fechaEntrada.add(const Duration(days: 30)),
                      );
                      if (date != null) {
                        final time = await showTimePicker(context: context, initialTime: TimeOfDay.fromDateTime(fechaSalida));
                        if (time != null) {
                          setDialogState(() {
                            fechaSalida = DateTime(date.year, date.month, date.day, time.hour, time.minute);
                          });
                        }
                      }
                    },
                  ),
                ],
              ),
              actions: [
                TextButton(onPressed: () => Navigator.pop(context, false), child: Text(i18n.commonCancel)),
                FilledButton(onPressed: () => Navigator.pop(context, true), child: Text(i18n.commonConfirm)),
              ],
            );
          },
        );
      },
    );

    if (result == true && context.mounted) {
      final response = await api.reservarParking(
        parkingId: parkingId,
        fechaEntrada: DateFormat('yyyy-MM-dd HH:mm').format(fechaEntrada),
        fechaSalida: DateFormat('yyyy-MM-dd HH:mm').format(fechaSalida),
      );
      if (context.mounted) {
        final msg = response.success ? i18n.parkingsReserveSuccess : (response.error ?? i18n.parkingsReserveError);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
        if (response.success) _refresh();
      }
    }
  }

  Future<void> _extenderReserva(BuildContext context, int reservaId) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final response = await api.extenderReservaParking(reservaId, 2);
    if (context.mounted) {
      final msg = response.success ? i18n.parkingsExtendSuccess : (response.error ?? i18n.parkingsExtendError);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      if (response.success) _refresh();
    }
  }

  Future<void> _cancelarReserva(BuildContext context, int reservaId) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.parkingsCancel),
        content: Text(i18n.parkingsCancelConfirm),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text(i18n.commonCancel)),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: Text(i18n.commonConfirm)),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.cancelarReservaParking(reservaId);
      if (context.mounted) {
        final msg = response.success ? i18n.parkingsCancelSuccess : (response.error ?? i18n.parkingsCancelError);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
        if (response.success) _refresh();
      }
    }
  }
}
