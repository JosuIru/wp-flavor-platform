import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import 'package:intl/intl.dart';

part 'espacios_comunes_screen_parts.dart';

class EspaciosComunesScreen extends ConsumerStatefulWidget {
  const EspaciosComunesScreen({super.key});

  @override
  ConsumerState<EspaciosComunesScreen> createState() => _EspaciosComunesScreenState();
}

class _EspaciosComunesScreenState extends ConsumerState<EspaciosComunesScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _futureEspacios;
  late Future<ApiResponse<Map<String, dynamic>>> _futureMisReservas;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _futureEspacios = api.getEspaciosComunes();
    _futureMisReservas = api.getMisReservasEspacios();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _futureEspacios = api.getEspaciosComunes();
      _futureMisReservas = api.getMisReservasEspacios();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Espacios Comunes'),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.espaciosComunesTabSpaces, icon: const Icon(Icons.meeting_room)),
              Tab(text: i18n.espaciosComunesTabReservations, icon: const Icon(Icons.event)),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildEspaciosTab(i18n),
            _buildMisReservasTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildEspaciosTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureEspacios,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.espaciosComunesError,
            onRetry: _refresh,
            icon: Icons.meeting_room_outlined,
          );
        }

        final espacios = (response.data!['espacios'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (espacios.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.meeting_room_outlined,
            title: i18n.espaciosComunesEmpty,
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: espacios.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final espacio = espacios[index];
              final id = (espacio['id'] as num?)?.toInt() ?? 0;
              final nombre = espacio['nombre']?.toString() ?? '';
              final descripcion = espacio['descripcion']?.toString() ?? '';
              final capacidad = (espacio['capacidad'] as num?)?.toInt() ?? 0;
              final equipamiento = espacio['equipamiento']?.toString() ?? '';
              final imagen = espacio['imagen']?.toString() ?? '';
              final disponible = espacio['disponible'] == true || espacio['disponible'] == 1;

              return Card(
                elevation: 2,
                clipBehavior: Clip.antiAlias,
                child: InkWell(
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => EspacioDetailScreen(espacioId: id),
                      ),
                    ).then((_) => _refresh());
                  },
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (imagen.isNotEmpty)
                        Stack(
                          children: [
                            Image.network(
                              imagen,
                              width: double.infinity,
                              height: 180,
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) => _buildPlaceholderImage(),
                            ),
                            if (!disponible)
                              Positioned(
                                top: 8,
                                right: 8,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                  decoration: BoxDecoration(
                                    color: Colors.red,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Text(
                                    i18n.espaciosComunesOccupied,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ),
                          ],
                        )
                      else
                        _buildPlaceholderImage(),
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              nombre,
                              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                    fontWeight: FontWeight.bold,
                                  ),
                            ),
                            if (descripcion.isNotEmpty) ...[
                              const SizedBox(height: 8),
                              Text(
                                descripcion,
                                style: Theme.of(context).textTheme.bodyMedium,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Icon(Icons.people, size: 18, color: Colors.grey[600]),
                                const SizedBox(width: 4),
                                Text(
                                  '${i18n.espaciosComunesCapacity}: $capacidad personas',
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                              ],
                            ),
                            if (equipamiento.isNotEmpty) ...[
                              const SizedBox(height: 8),
                              Wrap(
                                spacing: 6,
                                runSpacing: 6,
                                children: equipamiento.split(',').take(3).map((equip) {
                                  return Chip(
                                    label: Text(equip.trim()),
                                    visualDensity: VisualDensity.compact,
                                  );
                                }).toList(),
                              ),
                            ],
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Icon(
                                  disponible ? Icons.check_circle : Icons.cancel,
                                  size: 18,
                                  color: disponible ? Colors.green : Colors.red,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  disponible
                                      ? i18n.espaciosComunesAvailable
                                      : i18n.espaciosComunesNotAvailable,
                                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                        color: disponible ? Colors.green : Colors.red,
                                        fontWeight: FontWeight.bold,
                                      ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }

  Widget _buildPlaceholderImage() {
    return Container(
      width: double.infinity,
      height: 180,
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: const Icon(Icons.meeting_room, size: 80),
    );
  }

  Widget _buildMisReservasTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMisReservas,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.espaciosComunesReservationsError,
            onRetry: _refresh,
            icon: Icons.event_outlined,
          );
        }

        final reservas = (response.data!['reservas'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (reservas.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.event_outlined,
            title: i18n.espaciosComunesReservationsEmpty,
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: reservas.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final reserva = reservas[index];
              final id = (reserva['id'] as num?)?.toInt() ?? 0;
              final espacioNombre = reserva['espacio_nombre']?.toString() ?? '';
              final fecha = reserva['fecha']?.toString() ?? '';
              final horaInicio = reserva['hora_inicio']?.toString() ?? '';
              final horaFin = reserva['hora_fin']?.toString() ?? '';
              final estado = reserva['estado']?.toString() ?? 'pendiente';
              final motivo = reserva['motivo']?.toString() ?? '';

              return Card(
                elevation: 1,
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: _getEstadoColor(estado),
                    child: const Icon(Icons.event, color: Colors.white),
                  ),
                  title: Text(espacioNombre),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today, size: 14),
                          const SizedBox(width: 4),
                          Text(fecha),
                        ],
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          const Icon(Icons.access_time, size: 14),
                          const SizedBox(width: 4),
                          Text('$horaInicio - $horaFin'),
                        ],
                      ),
                      if (motivo.isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Text(
                          motivo,
                          style: Theme.of(context).textTheme.bodySmall,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                      const SizedBox(height: 4),
                      Chip(
                        label: Text(_getEstadoLabel(estado, i18n)),
                        visualDensity: VisualDensity.compact,
                        backgroundColor: _getEstadoColor(estado).withOpacity(0.2),
                      ),
                    ],
                  ),
                  trailing: estado == 'pendiente' || estado == 'confirmada'
                      ? IconButton(
                          icon: const Icon(Icons.cancel_outlined),
                          onPressed: () => _cancelarReserva(context, id),
                          tooltip: i18n.espaciosComunesCancelReservation,
                        )
                      : null,
                ),
              );
            },
          ),
        );
      },
    );
  }

  Color _getEstadoColor(String estado) {
    switch (estado) {
      case 'pendiente':
        return Colors.orange;
      case 'confirmada':
        return Colors.green;
      case 'cancelada':
        return Colors.red;
      case 'completada':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }

  String _getEstadoLabel(String estado, AppLocalizations i18n) {
    switch (estado) {
      case 'pendiente':
        return i18n.espaciosComunesStatusPending;
      case 'confirmada':
        return i18n.espaciosComunesStatusConfirmed;
      case 'cancelada':
        return i18n.espaciosComunesStatusCancelled;
      case 'completada':
        return i18n.espaciosComunesStatusCompleted;
      default:
        return estado;
    }
  }

  Future<void> _cancelarReserva(BuildContext context, int reservaId) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.espaciosComunesCancelReservation),
        content: Text(i18n.espaciosComunesCancelConfirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: Text(i18n.commonConfirm),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.cancelarReservaEspacio(reservaId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.espaciosComunesCancelSuccess
            : (response.error ?? i18n.espaciosComunesCancelError);
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) {
          _refresh();
        }
      }
    }
  }
}
