part of 'banco_tiempo_screen.dart';

extension _BancoTiempoScreenDialogs on _BancoTiempoScreenState {
  Future<void> _showCreateService(BuildContext context, ApiClient api) async {
    final i18n = AppLocalizations.of(context);
    final titleController = TextEditingController();
    final descController = TextEditingController();
    final categoriaController = TextEditingController();
    final horasController = TextEditingController(text: '1');

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final bottom = MediaQuery.of(context).viewInsets.bottom;
        return Padding(
          padding: EdgeInsets.fromLTRB(16, 16, 16, bottom + 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(i18n.bancoTiempoCreate, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 12),
              TextField(
                controller: titleController,
                decoration: InputDecoration(labelText: i18n.bancoTiempoFieldTitle),
              ),
              TextField(
                controller: descController,
                decoration: InputDecoration(labelText: i18n.bancoTiempoFieldDescription),
                maxLines: 3,
              ),
              TextField(
                controller: categoriaController,
                decoration: InputDecoration(labelText: i18n.bancoTiempoFieldCategory),
              ),
              TextField(
                controller: horasController,
                decoration: InputDecoration(labelText: i18n.bancoTiempoFieldHours),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context, false),
                    child: Text(i18n.commonCancel),
                  ),
                  const Spacer(),
                  FilledButton(
                    onPressed: () => Navigator.pop(context, true),
                    child: Text(i18n.commonSave),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );

    if (result == true) {
      final horas = double.tryParse(horasController.text.replaceAll(',', '.')) ?? 1;
      final response = await api.createBancoTiempoServicio(
        titulo: titleController.text.trim(),
        descripcion: descController.text.trim(),
        categoria: categoriaController.text.trim().isEmpty ? 'general' : categoriaController.text.trim(),
        horasEstimadas: horas,
      );

      if (context.mounted) {
        final msg = response.success ? i18n.bancoTiempoCreateSuccess : (response.error ?? i18n.bancoTiempoCreateError);
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

    titleController.dispose();
    descController.dispose();
    categoriaController.dispose();
    horasController.dispose();
  }

  Future<void> _showEditService(
    BuildContext context, {
    required int id,
    required String titulo,
    required String descripcion,
    required String categoria,
    required double horas,
  }) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final titleController = TextEditingController(text: titulo);
    final descController = TextEditingController(text: descripcion);
    final categoriaController = TextEditingController(text: categoria);
    final horasController = TextEditingController(text: horas.toString());

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final bottom = MediaQuery.of(context).viewInsets.bottom;
        return Padding(
          padding: EdgeInsets.fromLTRB(16, 16, 16, bottom + 16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(i18n.bancoTiempoEdit, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 12),
              TextField(
                controller: titleController,
                decoration: InputDecoration(labelText: i18n.bancoTiempoFieldTitle),
              ),
              TextField(
                controller: descController,
                decoration: InputDecoration(labelText: i18n.bancoTiempoFieldDescription),
                maxLines: 3,
              ),
              TextField(
                controller: categoriaController,
                decoration: InputDecoration(labelText: i18n.bancoTiempoFieldCategory),
              ),
              TextField(
                controller: horasController,
                decoration: InputDecoration(labelText: i18n.bancoTiempoFieldHours),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context, false),
                    child: Text(i18n.commonCancel),
                  ),
                  const Spacer(),
                  FilledButton(
                    onPressed: () => Navigator.pop(context, true),
                    child: Text(i18n.commonSave),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );

    if (result == true) {
      final horasValue = double.tryParse(horasController.text.replaceAll(',', '.')) ?? horas;
      final response = await api.updateBancoTiempoServicio(
        servicioId: id,
        titulo: titleController.text.trim(),
        descripcion: descController.text.trim(),
        categoria: categoriaController.text.trim().isEmpty ? 'general' : categoriaController.text.trim(),
        horasEstimadas: horasValue,
      );

      if (context.mounted) {
        final msg = response.success ? i18n.bancoTiempoUpdateSuccess : (response.error ?? i18n.bancoTiempoUpdateError);
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

    titleController.dispose();
    descController.dispose();
    categoriaController.dispose();
    horasController.dispose();
  }
}
