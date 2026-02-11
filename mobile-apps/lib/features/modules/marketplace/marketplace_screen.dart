import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class MarketplaceScreen extends ConsumerStatefulWidget {
  const MarketplaceScreen({super.key});

  @override
  ConsumerState<MarketplaceScreen> createState() => _MarketplaceScreenState();
}

class _MarketplaceScreenState extends ConsumerState<MarketplaceScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  late Future<ApiResponse<Map<String, dynamic>>> _futureMine;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getMarketplaceAnuncios();
    _futureMine = api.getMarketplaceMisAnuncios();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _future = api.getMarketplaceAnuncios();
      _futureMine = api.getMarketplaceMisAnuncios();
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
          title: Text(i18n.marketplaceTitle),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.marketplaceTabAll),
              Tab(text: i18n.marketplaceTabMine),
            ],
          ),
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () => _showCreateAnuncio(context, api),
          icon: const Icon(Icons.add),
          label: Text(i18n.marketplaceCreate),
        ),
        body: TabBarView(
          children: [
            _buildAnunciosTab(i18n),
            _buildMisAnunciosTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildAnunciosTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _future,
      builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.marketplaceError));
          }

          final anuncios = (response.data!['anuncios'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          if (anuncios.isEmpty) {
            return Center(child: Text(i18n.marketplaceEmpty));
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: anuncios.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final anuncio = anuncios[index];
                final title = anuncio['titulo']?.toString() ?? '';
                final precio = anuncio['precio'];
                final tipo = anuncio['tipo']?.toString() ?? '';
                final ubicacion = anuncio['ubicacion']?.toString() ?? '';
                final imagen = anuncio['imagen']?.toString() ?? '';

                return Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Row(
                      children: [
                        if (imagen.isNotEmpty)
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: Image.network(
                              imagen,
                              width: 72,
                              height: 72,
                              fit: BoxFit.cover,
                            ),
                          )
                        else
                          Container(
                            width: 72,
                            height: 72,
                            decoration: BoxDecoration(
                              color: Theme.of(context).colorScheme.surfaceVariant,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Icon(Icons.storefront_outlined),
                          ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                title,
                                style: Theme.of(context).textTheme.titleMedium,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 6),
                              if (tipo.isNotEmpty)
                                Chip(
                                  label: Text(tipo),
                                  visualDensity: VisualDensity.compact,
                                ),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  if (precio != null)
                                    Text(
                                      '${precio.toString()} €',
                                      style: Theme.of(context).textTheme.labelMedium,
                                    ),
                                  const Spacer(),
                                  if (ubicacion.isNotEmpty)
                                    Text(
                                      ubicacion,
                                      style: Theme.of(context).textTheme.labelSmall,
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

  Widget _buildMisAnunciosTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMine,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.marketplaceMineError));
        }

        final anuncios = (response.data!['anuncios'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (anuncios.isEmpty) {
          return Center(child: Text(i18n.marketplaceMineEmpty));
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: anuncios.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final anuncio = anuncios[index];
            final id = (anuncio['id'] as num?)?.toInt() ?? 0;
            final title = anuncio['titulo']?.toString() ?? '';
            final descripcion = anuncio['descripcion']?.toString() ?? '';
            final tipo = anuncio['tipo']?.toString() ?? '';
            final categoria = anuncio['categoria']?.toString() ?? '';
            final precio = (anuncio['precio'] as num?)?.toDouble();
            final ubicacion = anuncio['ubicacion']?.toString() ?? '';
            final estado = anuncio['vendido'] == true ? i18n.marketplaceStatusSold : i18n.marketplaceStatusActive;
            return ListTile(
              leading: const Icon(Icons.storefront_outlined),
              title: Text(title),
              subtitle: Text(estado),
              trailing: PopupMenuButton<String>(
                onSelected: (value) async {
                  final api = ref.read(apiClientProvider);
                  if (value == 'edit') {
                    await _showEditAnuncio(
                      context,
                      id: id,
                      titulo: title,
                      descripcion: descripcion,
                      tipo: tipo,
                      categoria: categoria,
                      precio: precio,
                      ubicacion: ubicacion,
                    );
                  } else if (value == 'sold') {
                    final res = await api.markMarketplaceSold(id);
                    if (context.mounted) {
                      final msg = res.success
                          ? i18n.marketplaceMarkSoldSuccess
                          : (res.error ?? i18n.marketplaceMarkSoldError);
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(msg)),
                      );
                      if (res.success) _refresh();
                    }
                  } else if (value == 'delete') {
                    final res = await api.deleteMarketplaceAnuncio(id);
                    if (context.mounted) {
                      final msg = res.success
                          ? i18n.marketplaceDeleteSuccess
                          : (res.error ?? i18n.marketplaceDeleteError);
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(msg)),
                      );
                      if (res.success) _refresh();
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
                  PopupMenuItem(value: 'sold', child: Text(i18n.marketplaceMarkSold)),
                  PopupMenuItem(value: 'delete', child: Text(i18n.marketplaceDelete)),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _showCreateAnuncio(BuildContext context, ApiClient api) async {
    final i18n = AppLocalizations.of(context)!;
    final titleController = TextEditingController();
    final descController = TextEditingController();
    final categoriaController = TextEditingController();
    final tipoController = TextEditingController(text: 'venta');
    final precioController = TextEditingController();
    final ubicacionController = TextEditingController();

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
              Text(i18n.marketplaceCreate, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 12),
              TextField(
                controller: titleController,
                decoration: InputDecoration(labelText: i18n.marketplaceFieldTitle),
              ),
              TextField(
                controller: descController,
                decoration: InputDecoration(labelText: i18n.marketplaceFieldDescription),
                maxLines: 3,
              ),
              TextField(
                controller: categoriaController,
                decoration: InputDecoration(labelText: i18n.marketplaceFieldCategory),
              ),
              TextField(
                controller: tipoController,
                decoration: InputDecoration(labelText: i18n.marketplaceFieldType),
              ),
              TextField(
                controller: precioController,
                decoration: InputDecoration(labelText: i18n.marketplaceFieldPrice),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
              ),
              TextField(
                controller: ubicacionController,
                decoration: InputDecoration(labelText: i18n.marketplaceFieldLocation),
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
      final precio = double.tryParse(precioController.text.replaceAll(',', '.'));
      final response = await api.createMarketplaceAnuncio(
        titulo: titleController.text.trim(),
        descripcion: descController.text.trim(),
        tipo: tipoController.text.trim().isEmpty ? 'venta' : tipoController.text.trim(),
        categoria: categoriaController.text.trim().isEmpty ? 'general' : categoriaController.text.trim(),
        precio: precio,
        ubicacion: ubicacionController.text.trim(),
      );

      if (context.mounted) {
        final msg = response.success ? i18n.marketplaceCreateSuccess : (response.error ?? i18n.marketplaceCreateError);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
        if (response.success) {
          _refresh();
        }
      }
    }

    titleController.dispose();
    descController.dispose();
    categoriaController.dispose();
    tipoController.dispose();
    precioController.dispose();
    ubicacionController.dispose();
  }

  Future<void> _showEditAnuncio(
    BuildContext context, {
    required int id,
    required String titulo,
    required String descripcion,
    required String tipo,
    required String categoria,
    double? precio,
    required String ubicacion,
  }) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final titleController = TextEditingController(text: titulo);
    final descController = TextEditingController(text: descripcion);
    final categoriaController = TextEditingController(text: categoria);
    final tipoController = TextEditingController(text: tipo);
    final precioController = TextEditingController(text: precio?.toString() ?? '');
    final ubicacionController = TextEditingController(text: ubicacion);

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final bottom = MediaQuery.of(context).viewInsets.bottom;
        return Padding(
          padding: EdgeInsets.fromLTRB(16, 16, 16, bottom + 16),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(i18n.marketplaceEdit, style: Theme.of(context).textTheme.titleLarge),
                const SizedBox(height: 12),
                TextField(
                  controller: titleController,
                  decoration: InputDecoration(labelText: i18n.marketplaceFieldTitle),
                ),
                TextField(
                  controller: descController,
                  decoration: InputDecoration(labelText: i18n.marketplaceFieldDescription),
                  maxLines: 3,
                ),
                TextField(
                  controller: categoriaController,
                  decoration: InputDecoration(labelText: i18n.marketplaceFieldCategory),
                ),
                TextField(
                  controller: tipoController,
                  decoration: InputDecoration(labelText: i18n.marketplaceFieldType),
                ),
                TextField(
                  controller: precioController,
                  decoration: InputDecoration(labelText: i18n.marketplaceFieldPrice),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                ),
                TextField(
                  controller: ubicacionController,
                  decoration: InputDecoration(labelText: i18n.marketplaceFieldLocation),
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
          ),
        );
      },
    );

    if (result == true) {
      final precioValue = double.tryParse(precioController.text.replaceAll(',', '.'));
      final response = await api.updateMarketplaceAnuncio(
        anuncioId: id,
        titulo: titleController.text.trim(),
        descripcion: descController.text.trim(),
        tipo: tipoController.text.trim().isEmpty ? 'venta' : tipoController.text.trim(),
        categoria: categoriaController.text.trim().isEmpty ? 'general' : categoriaController.text.trim(),
        precio: precioValue,
        ubicacion: ubicacionController.text.trim(),
      );

      if (context.mounted) {
        final msg = response.success ? i18n.marketplaceUpdateSuccess : (response.error ?? i18n.marketplaceUpdateError);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
        if (response.success) {
          _refresh();
        }
      }
    }

    titleController.dispose();
    descController.dispose();
    categoriaController.dispose();
    tipoController.dispose();
    precioController.dispose();
    ubicacionController.dispose();
  }
}
