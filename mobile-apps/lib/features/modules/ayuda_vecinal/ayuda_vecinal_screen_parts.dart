part of 'ayuda_vecinal_screen.dart';

extension _AyudaVecinalScreenParts on _AyudaVecinalScreenState {
  void _verDetalleSolicitud(BuildContext context, Map<String, dynamic> solicitud) {
    final titulo = solicitud['titulo']?.toString() ?? '';
    final descripcion = solicitud['descripcion']?.toString() ?? '';
    final categoria = solicitud['categoria']?.toString() ?? '';
    final usuario = solicitud['usuario']?.toString() ?? '';
    final zona = solicitud['zona']?.toString() ?? '';
    final telefono = solicitud['telefono']?.toString() ?? '';

    showModalBottomSheet(
      context: context,
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                titulo,
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 8),
              Chip(label: Text(categoria)),
              const SizedBox(height: 16),
              Text(
                descripcion,
                style: Theme.of(context).textTheme.bodyLarge,
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  const Icon(Icons.person, size: 20),
                  const SizedBox(width: 8),
                  Text(usuario),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.place, size: 20),
                  const SizedBox(width: 8),
                  Text(zona),
                ],
              ),
              if (telefono.isNotEmpty) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.phone, size: 20),
                    const SizedBox(width: 8),
                    Text(telefono),
                  ],
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  Future<void> _solicitarAyuda(BuildContext context) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final tituloController = TextEditingController();
    final descripcionController = TextEditingController();
    String categoria = 'compras';
    String urgencia = 'normal';

    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(i18n.ayudaRequestHelp),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: tituloController,
                      decoration: InputDecoration(labelText: i18n.ayudaTitle),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: descripcionController,
                      decoration: InputDecoration(labelText: i18n.ayudaDescription),
                      maxLines: 3,
                    ),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<String>(
                      value: categoria,
                      decoration: InputDecoration(labelText: i18n.ayudaCategory),
                      items: ['compras', 'transporte', 'cuidados', 'tecnología', 'reparaciones', 'tareas domésticas', 'acompañamiento']
                          .map((cat) => DropdownMenuItem(value: cat, child: Text(cat)))
                          .toList(),
                      onChanged: (value) => setDialogState(() => categoria = value!),
                    ),
                    const SizedBox(height: 16),
                    SegmentedButton<String>(
                      segments: [
                        ButtonSegment(value: 'normal', label: Text(i18n.ayudaNormal)),
                        ButtonSegment(value: 'urgente', label: Text(i18n.ayudaUrgent)),
                      ],
                      selected: {urgencia},
                      onSelectionChanged: (Set<String> newSelection) {
                        setDialogState(() => urgencia = newSelection.first);
                      },
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
                  child: Text(i18n.commonSend),
                ),
              ],
            );
          },
        );
      },
    );

    if (result == true && context.mounted) {
      final response = await api.crearSolicitudAyuda(
        titulo: tituloController.text.trim(),
        descripcion: descripcionController.text.trim(),
        categoria: categoria,
        urgencia: urgencia,
      );
      if (context.mounted) {
        final msg = response.success
            ? i18n.ayudaRequestSuccess
            : (response.error ?? i18n.ayudaRequestError);
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) _refresh();
      }
    }

    tituloController.dispose();
    descripcionController.dispose();
  }

  Future<void> _ofrecerAyuda(BuildContext context, int solicitudId) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.ayudaOfferHelp),
        content: Text(i18n.ayudaOfferConfirm),
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
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.ofrecerAyuda(solicitudId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.ayudaOfferSuccess
            : (response.error ?? i18n.ayudaOfferError);
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) _refresh();
      }
    }
  }

  Future<void> _cancelarSolicitud(BuildContext context, int solicitudId) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.ayudaCancelRequest),
        content: Text(i18n.ayudaCancelConfirm),
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
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.cancelarSolicitudAyuda(solicitudId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.ayudaCancelSuccess
            : (response.error ?? i18n.ayudaCancelError);
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
