part of 'parkings_screen.dart';

extension _ParkingsScreenActions on _ParkingsScreenState {
  void _verDetalleParking(BuildContext context, Map<String, dynamic> parking) {
    final i18n = AppLocalizations.of(context);
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
    final i18n = AppLocalizations.of(context);
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
                        if (!context.mounted) return;
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
                        if (!context.mounted) return;
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
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) _refresh();
      }
    }
  }

  Future<void> _extenderReserva(BuildContext context, int reservaId) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final response = await api.extenderReservaParking(reservaId, 2);
    if (context.mounted) {
      final msg = response.success ? i18n.parkingsExtendSuccess : (response.error ?? i18n.parkingsExtendError);
      if (response.success) {
        FlavorSnackbar.showSuccess(context, msg);
      } else {
        FlavorSnackbar.showError(context, msg);
      }
      if (response.success) _refresh();
    }
  }

  Future<void> _cancelarReserva(BuildContext context, int reservaId) async {
    final i18n = AppLocalizations.of(context);
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
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) _refresh();
      }
    }
  }
}
