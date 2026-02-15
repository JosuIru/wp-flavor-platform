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
  bool _loading = true;
  String? _error;

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
      final response = await apiClient.get('/reservas');
      if (response.success && response.data != null) {
        setState(() {
          _reservas = response.data!['reservas'] ??
              response.data!['items'] ??
              response.data!['data'] ??
              [];
          _loading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar reservas';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sistema de Reservas'),
        actions: [
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
              : _reservas.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.event_seat,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No tienes reservas'),
                          const SizedBox(height: 8),
                          const Text(
                            'Crea una nueva reserva para comenzar',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadData,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _reservas.length,
                        itemBuilder: (context, index) =>
                            _buildReservaCard(_reservas[index]),
                      ),
                    ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          // TODO: Implementar nueva reserva
        },
        icon: const Icon(Icons.add),
        label: const Text('Nueva Reserva'),
      ),
    );
  }

  Widget _buildReservaCard(dynamic item) {
    final reservaMap = item as Map<String, dynamic>;
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

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: estadoColor.withOpacity(0.1),
          child: Icon(Icons.event_seat, color: estadoColor),
        ),
        title: Text(
          recurso.toString(),
          style: const TextStyle(fontWeight: FontWeight.w500),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (fecha.toString().isNotEmpty)
              Row(
                children: [
                  const Icon(Icons.calendar_today, size: 14),
                  const SizedBox(width: 4),
                  Text(fecha.toString()),
                ],
              ),
            if (horaInicio.toString().isNotEmpty ||
                horaFin.toString().isNotEmpty)
              Row(
                children: [
                  const Icon(Icons.access_time, size: 14),
                  const SizedBox(width: 4),
                  Text('$horaInicio - $horaFin'),
                ],
              ),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(estadoIcon, color: estadoColor, size: 20),
            Text(
              estado.toString(),
              style: TextStyle(fontSize: 10, color: estadoColor),
            ),
          ],
        ),
        onTap: () {
          // TODO: Ver detalle de reserva
        },
      ),
    );
  }
}
