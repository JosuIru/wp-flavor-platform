import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class BancoTiempoScreen extends ConsumerStatefulWidget {
  const BancoTiempoScreen({super.key});

  @override
  ConsumerState<BancoTiempoScreen> createState() => _BancoTiempoScreenState();
}

class _BancoTiempoScreenState extends ConsumerState<BancoTiempoScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  late Future<ApiResponse<Map<String, dynamic>>> _futureMine;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getBancoTiempoServicios();
    _futureMine = api.getBancoTiempoMisServicios();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _future = api.getBancoTiempoServicios();
      _futureMine = api.getBancoTiempoMisServicios();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: Text(i18n.bancoTiempoTitle),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.bancoTiempoTabAll),
              Tab(text: i18n.bancoTiempoTabMine),
            ],
          ),
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () => _showCreateService(context, api),
          icon: const Icon(Icons.add),
          label: Text(i18n.bancoTiempoCreate),
        ),
        body: TabBarView(
          children: [
            _buildServiciosTab(i18n),
            _buildMisServiciosTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildServiciosTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _future,
      builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.bancoTiempoError));
          }

          final servicios = (response.data!['servicios'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          if (servicios.isEmpty) {
            return Center(child: Text(i18n.bancoTiempoEmpty));
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: servicios.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final servicio = servicios[index];
                final title = servicio['titulo']?.toString() ?? '';
                final categoria = servicio['categoria']?.toString() ?? '';
                final horas = (servicio['horas_estimadas'] as num?)?.toDouble() ?? 0;
                final usuario = servicio['usuario']?['nombre']?.toString() ?? '';
                final descripcion = servicio['descripcion']?.toString() ?? '';

                return Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.volunteer_activism, color: Theme.of(context).colorScheme.primary),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                title,
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                            ),
                          ],
                        ),
                        if (descripcion.isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            descripcion,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            if (categoria.isNotEmpty)
                              Chip(
                                label: Text(categoria),
                                visualDensity: VisualDensity.compact,
                              ),
                            const Spacer(),
                            Text(
                              '${horas.toStringAsFixed(1)} h',
                              style: Theme.of(context).textTheme.labelMedium,
                            ),
                          ],
                        ),
                        if (usuario.isNotEmpty)
                          Align(
                            alignment: Alignment.centerRight,
                            child: Text(
                              usuario,
                              style: Theme.of(context).textTheme.labelSmall,
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

  Widget _buildMisServiciosTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMine,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.bancoTiempoMineError));
        }

        final servicios = (response.data!['servicios'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (servicios.isEmpty) {
          return Center(child: Text(i18n.bancoTiempoMineEmpty));
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: servicios.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final servicio = servicios[index];
            final id = (servicio['id'] as num?)?.toInt() ?? 0;
            final title = servicio['titulo']?.toString() ?? '';
            final estado = servicio['estado']?.toString() ?? '';
            final descripcion = servicio['descripcion']?.toString() ?? '';
            final categoria = servicio['categoria']?.toString() ?? '';
            final horas = (servicio['horas_estimadas'] as num?)?.toDouble() ?? 0;

            return Card(
              elevation: 1,
              child: ListTile(
                leading: const Icon(Icons.volunteer_activism),
                title: Text(title),
                subtitle: Text(estado),
                trailing: PopupMenuButton<String>(
                  onSelected: (value) async {
                    if (value == 'edit') {
                      await _showEditService(
                        context,
                        id: id,
                        titulo: title,
                        descripcion: descripcion,
                        categoria: categoria,
                        horas: horas,
                      );
                    } else if (value == 'delete') {
                      final confirm = await showDialog<bool>(
                        context: context,
                        builder: (context) => AlertDialog(
                          title: const Text('Confirmar'),
                          content: Text('¿Eliminar el servicio "$title"?'),
                          actions: [
                            TextButton(
                              onPressed: () => Navigator.pop(context, false),
                              child: const Text('Cancelar'),
                            ),
                            FilledButton(
                              onPressed: () => Navigator.pop(context, true),
                              child: const Text('Eliminar'),
                            ),
                          ],
                        ),
                      );

                      if (confirm == true) {
                        final api = ref.read(apiClientProvider);
                        final res = await api.deleteBancoTiempoServicio(id);
                        if (context.mounted) {
                          final msg = res.success
                              ? i18n.bancoTiempoDeleteSuccess
                              : (res.error ?? i18n.bancoTiempoDeleteError);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(msg)),
                          );
                          if (res.success) _refresh();
                        }
                      }
                    }
                  },
                  itemBuilder: (context) => [
                    PopupMenuItem(
                      value: 'edit',
                      child: Row(
                        children: [
                          const Icon(Icons.edit, size: 20),
                          const SizedBox(width: 8),
                          Text(i18n.commonEdit),
                        ],
                      ),
                    ),
                    PopupMenuItem(
                      value: 'delete',
                      child: Row(
                        children: [
                          const Icon(Icons.delete, size: 20),
                          const SizedBox(width: 8),
                          Text(i18n.commonDelete),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _showCreateService(BuildContext context, ApiClient api) async {
    final i18n = AppLocalizations.of(context)!;
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
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
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
    final i18n = AppLocalizations.of(context)!;
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
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
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
