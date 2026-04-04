part of 'espacios_comunes_screen.dart';

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

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getEspacioComun(widget.espacioId);
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);

    return Scaffold(
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return FlavorErrorState(
              message: i18n.espaciosComunesError,
              onRetry: () => setState(() {
                final api = ref.read(apiClientProvider);
                _future = api.getEspacioComun(widget.espacioId);
              }),
              icon: Icons.meeting_room_outlined,
            );
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
                            Image.network(imagen, fit: BoxFit.cover),
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
                          color: Theme.of(context).colorScheme.surfaceContainerHighest,
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
    final i18n = AppLocalizations.of(context);
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
                            selectedHoraInicio =
                                '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
                          });
                        }
                      },
                    ),
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
                            selectedHoraFin =
                                '${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}';
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
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }

    motivoController.dispose();
  }
}
