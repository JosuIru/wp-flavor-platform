import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import 'package:intl/intl.dart';

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
    final i18n = AppLocalizations.of(context)!;

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
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.espaciosComunesError));
        }

        final espacios = (response.data!['espacios'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (espacios.isEmpty) {
          return Center(child: Text(i18n.espaciosComunesEmpty));
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
      color: Theme.of(context).colorScheme.surfaceVariant,
      child: const Icon(Icons.meeting_room, size: 80),
    );
  }

  Widget _buildMisReservasTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMisReservas,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.espaciosComunesReservationsError));
        }

        final reservas = (response.data!['reservas'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (reservas.isEmpty) {
          return Center(child: Text(i18n.espaciosComunesReservationsEmpty));
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
    final i18n = AppLocalizations.of(context)!;
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          _refresh();
        }
      }
    }
  }
}

/// Pantalla de detalle de un espacio común
class EspacioDetailScreen extends ConsumerStatefulWidget {
  final int espacioId;

  const EspacioDetailScreen({
    super.key,
    required this.espacioId,
  });

  @override
  ConsumerState<EspacioDetailScreen> createState() => _EspacioDetailScreenState();
}

class _EspacioDetailScreenState extends ConsumerState<EspacioDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  DateTime _selectedDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getEspacioComun(widget.espacioId);
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return Scaffold(
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.espaciosComunesError));
          }

          final espacio = response.data!['espacio'] as Map<String, dynamic>? ?? {};
          final nombre = espacio['nombre']?.toString() ?? '';
          final descripcion = espacio['descripcion']?.toString() ?? '';
          final capacidad = (espacio['capacidad'] as num?)?.toInt() ?? 0;
          final equipamiento = espacio['equipamiento']?.toString() ?? '';
          final normas = espacio['normas']?.toString() ?? '';
          final imagen = espacio['imagen']?.toString() ?? '';
          final ubicacion = espacio['ubicacion']?.toString() ?? '';
          final horarioInicio = espacio['horario_inicio']?.toString() ?? '09:00';
          final horarioFin = espacio['horario_fin']?.toString() ?? '21:00';

          return CustomScrollView(
            slivers: [
              SliverAppBar(
                expandedHeight: 250,
                pinned: true,
                flexibleSpace: FlexibleSpaceBar(
                  title: Text(
                    nombre,
                    style: const TextStyle(
                      color: Colors.white,
                      shadows: [
                        Shadow(
                          offset: Offset(0, 1),
                          blurRadius: 3,
                          color: Colors.black54,
                        ),
                      ],
                    ),
                  ),
                  background: imagen.isNotEmpty
                      ? Stack(
                          fit: StackFit.expand,
                          children: [
                            Image.network(
                              imagen,
                              fit: BoxFit.cover,
                            ),
                            Container(
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  begin: Alignment.topCenter,
                                  end: Alignment.bottomCenter,
                                  colors: [
                                    Colors.transparent,
                                    Colors.black.withOpacity(0.7),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        )
                      : Container(
                          color: Theme.of(context).colorScheme.surfaceVariant,
                          child: const Icon(Icons.meeting_room, size: 100),
                        ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Información básica
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (descripcion.isNotEmpty) ...[
                                Text(
                                  descripcion,
                                  style: Theme.of(context).textTheme.bodyLarge,
                                ),
                                const SizedBox(height: 16),
                              ],
                              Row(
                                children: [
                                  const Icon(Icons.people, size: 20),
                                  const SizedBox(width: 8),
                                  Text(
                                    '${i18n.espaciosComunesCapacity}: $capacidad personas',
                                    style: Theme.of(context).textTheme.bodyMedium,
                                  ),
                                ],
                              ),
                              if (ubicacion.isNotEmpty) ...[
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    const Icon(Icons.place, size: 20),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(
                                        ubicacion,
                                        style: Theme.of(context).textTheme.bodyMedium,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  const Icon(Icons.access_time, size: 20),
                                  const SizedBox(width: 8),
                                  Text(
                                    '${i18n.espaciosComunesSchedule}: $horarioInicio - $horarioFin',
                                    style: Theme.of(context).textTheme.bodyMedium,
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Equipamiento
                      if (equipamiento.isNotEmpty) ...[
                        Text(
                          i18n.espaciosComunesEquipment,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: equipamiento.split(',').map((equip) {
                                return Chip(
                                  label: Text(equip.trim()),
                                  avatar: const Icon(Icons.check_circle, size: 16),
                                );
                              }).toList(),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // Normas
                      if (normas.isNotEmpty) ...[
                        Text(
                          i18n.espaciosComunesRules,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          color: Colors.blue.shade50,
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: normas.split('\n').map((norma) {
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Icon(Icons.info_outline, size: 18),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          norma.trim(),
                                          style: Theme.of(context).textTheme.bodyMedium,
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              }).toList(),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // Botón de reserva
                      FilledButton.icon(
                        onPressed: () => _showReservaDialog(
                          context,
                          nombre,
                          horarioInicio,
                          horarioFin,
                        ),
                        icon: const Icon(Icons.event_available),
                        label: Text(i18n.espaciosComunesReserve),
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _showReservaDialog(
    BuildContext context,
    String espacioNombre,
    String horarioInicio,
    String horarioFin,
  ) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    DateTime selectedDate = DateTime.now();
    String selectedHoraInicio = horarioInicio;
    String selectedHoraFin = horarioFin;
    final motivoController = TextEditingController();

    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(i18n.espaciosComunesReserve),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      espacioNombre,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 16),

                    // Selector de fecha
                    ListTile(
                      leading: const Icon(Icons.calendar_today),
                      title: Text(i18n.espaciosComunesDate),
                      subtitle: Text(DateFormat('dd/MM/yyyy').format(selectedDate)),
                      onTap: () async {
                        final date = await showDatePicker(
                          context: context,
                          initialDate: selectedDate,
                          firstDate: DateTime.now(),
                          lastDate: DateTime.now().add(const Duration(days: 90)),
                        );
                        if (date != null) {
                          setDialogState(() {
                            selectedDate = date;
                          });
                        }
                      },
                    ),

                    // Hora inicio
                    ListTile(
                      leading: const Icon(Icons.access_time),
                      title: Text(i18n.espaciosComunesStartTime),
                      subtitle: Text(selectedHoraInicio),
                      onTap: () async {
                        final time = await showTimePicker(
                          context: context,
                          initialTime: TimeOfDay(
                            hour: int.parse(selectedHoraInicio.split(':')[0]),
                            minute: int.parse(selectedHoraInicio.split(':')[1]),
                          ),
                        );
                        if (time != null) {
                          setDialogState(() {
                            selectedHoraInicio = '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
                          });
                        }
                      },
                    ),

                    // Hora fin
                    ListTile(
                      leading: const Icon(Icons.access_time),
                      title: Text(i18n.espaciosComunesEndTime),
                      subtitle: Text(selectedHoraFin),
                      onTap: () async {
                        final time = await showTimePicker(
                          context: context,
                          initialTime: TimeOfDay(
                            hour: int.parse(selectedHoraFin.split(':')[0]),
                            minute: int.parse(selectedHoraFin.split(':')[1]),
                          ),
                        );
                        if (time != null) {
                          setDialogState(() {
                            selectedHoraFin = '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
                          });
                        }
                      },
                    ),

                    const SizedBox(height: 16),
                    TextField(
                      controller: motivoController,
                      decoration: InputDecoration(
                        labelText: i18n.espaciosComunesPurpose,
                        border: const OutlineInputBorder(),
                      ),
                      maxLines: 3,
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: Text(i18n.commonCancel),
                ),
                FilledButton(
                  onPressed: () => Navigator.pop(context, true),
                  child: Text(i18n.commonConfirm),
                ),
              ],
            );
          },
        );
      },
    );

    if (result == true && context.mounted) {
      final response = await api.crearReservaEspacio(
        espacioId: widget.espacioId,
        fecha: DateFormat('yyyy-MM-dd').format(selectedDate),
        horaInicio: selectedHoraInicio,
        horaFin: selectedHoraFin,
        motivo: motivoController.text.trim(),
      );

      if (context.mounted) {
        final msg = response.success
            ? i18n.espaciosComunesReserveSuccess
            : (response.error ?? i18n.espaciosComunesReserveError);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }

    motivoController.dispose();
  }
}
