import 'dart:math' as Math;
import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import 'grupos_consumo_map_screen.dart';
import '../../layouts/layout_config.dart';
import '../../../core/providers/sync_provider.dart';

class GruposConsumoScreen extends ConsumerStatefulWidget {
  const GruposConsumoScreen({super.key});

  @override
  ConsumerState<GruposConsumoScreen> createState() => _GruposConsumoScreenState();
}

class _GruposConsumoScreenState extends ConsumerState<GruposConsumoScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  late Future<ApiResponse<Map<String, dynamic>>> _futureMis;
  late Future<ApiResponse<Map<String, dynamic>>> _futurePerfil;
  late Future<ApiResponse<Map<String, dynamic>>> _futureSuscripciones;
  late Future<ApiResponse<Map<String, dynamic>>> _futureHistorial;
  late Future<ApiResponse<Map<String, dynamic>>> _futureProductos;
  late Future<ApiResponse<Map<String, dynamic>>> _futureLista;
  late Future<ApiResponse<Map<String, dynamic>>> _futureCiclos;
  late Future<ApiResponse<Map<String, dynamic>>> _futureProductores;
  late Future<ApiResponse<Map<String, dynamic>>> _futureProductoresCercanos;

  String _catalogSearch = '';
  String _catalogCategory = '';
  int _catalogProducerId = 0;

  bool _producersOnlyDelivery = false;
  bool _producersOnlyEco = false;
  bool _producersUseNearby = true;

  DateTime? _cyclesFrom;
  DateTime? _cyclesTo;
  String _cyclesTurn = 'any';

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getGruposConsumoPedidos();
    _futureMis = api.getGruposConsumoMisPedidos();
    _futurePerfil = api.getGruposConsumoPerfil();
    _futureSuscripciones = api.getGruposConsumoSuscripciones();
    _futureHistorial = api.getGruposConsumoHistorial();
    _futureProductos = api.getGruposConsumoProductos();
    _futureLista = api.getGruposConsumoListaCompra();
    _futureCiclos = api.getGruposConsumoCiclos();
    _futureProductores = api.getGruposConsumoProductores();
    _futureProductoresCercanos = api.getGruposConsumoProductoresCercanos();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _future = api.getGruposConsumoPedidos();
      _futureMis = api.getGruposConsumoMisPedidos();
      _futurePerfil = api.getGruposConsumoPerfil();
      _futureSuscripciones = api.getGruposConsumoSuscripciones();
      _futureHistorial = api.getGruposConsumoHistorial();
      _futureProductos = api.getGruposConsumoProductos();
      _futureLista = api.getGruposConsumoListaCompra();
      _futureCiclos = api.getGruposConsumoCiclos();
      _futureProductores = api.getGruposConsumoProductores(
        soloEco: _producersOnlyEco,
        conEntrega: _producersOnlyDelivery,
      );
      _futureProductoresCercanos = api.getGruposConsumoProductoresCercanos();
    });
  }

  void _reloadProducers() {
    setState(() {
      final api = ref.read(apiClientProvider);
      _futureProductores = api.getGruposConsumoProductores(
        soloEco: _producersOnlyEco,
        conEntrega: _producersOnlyDelivery,
      );
      _futureProductoresCercanos = api.getGruposConsumoProductoresCercanos();
    });
  }

  void _reloadCatalog() {
    setState(() {
      _futureProductos = ref.read(apiClientProvider).getGruposConsumoProductos(
            categoria: _catalogCategory.isNotEmpty ? _catalogCategory : null,
            productorId: _catalogProducerId > 0 ? _catalogProducerId : null,
            busqueda: _catalogSearch.isNotEmpty ? _catalogSearch : null,
          );
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    return DefaultTabController(
      length: 8,
      child: Scaffold(
        appBar: AppBar(
          title: Text(i18n.gruposConsumoTitle),
          bottom: TabBar(
            isScrollable: true,
            tabs: [
              Tab(text: i18n.gruposConsumoTabAll),
              Tab(text: i18n.gruposConsumoTabMine),
              Tab(text: i18n.gruposConsumoTabCatalog),
              Tab(text: i18n.gruposConsumoTabShoppingList),
              Tab(text: i18n.gruposConsumoTabCycles),
              Tab(text: i18n.gruposConsumoTabProducers),
              Tab(text: i18n.gruposConsumoTabProfile),
              Tab(text: i18n.gruposConsumoTabHistory),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildPedidosTab(context, api, _future, i18n),
            _buildMisPedidosTab(context, _futureMis, i18n),
            _buildCatalogTab(i18n),
            _buildShoppingListTab(i18n),
            _buildCyclesTab(i18n),
            _buildProducersTab(i18n),
            _buildPerfilTab(i18n),
            _buildHistorialTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildPedidosTab(
    BuildContext context,
    ApiClient api,
    Future<ApiResponse<Map<String, dynamic>>> future,
    AppLocalizations i18n,
  ) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: future,
      builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.gruposConsumoError));
          }

          final pedidos = (response.data!['data'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          if (pedidos.isEmpty) {
            return Center(child: Text(i18n.gruposConsumoEmpty));
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: pedidos.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final pedido = pedidos[index];
                final id = (pedido['id'] as num?)?.toInt() ?? 0;
                final title = pedido['titulo']?.toString() ?? '';
                final productor = pedido['productor']?.toString() ?? '';
                final estado = pedido['estado']?.toString() ?? '';
                final progreso = (pedido['progreso'] as num?)?.toDouble() ?? 0;
                final precio = (pedido['precio_final'] as num?)?.toDouble() ?? 0;
                final unidad = pedido['unidad']?.toString() ?? '';
                final imagen = pedido['imagen']?.toString() ?? '';

                return Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        Row(
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
                                child: const Icon(Icons.shopping_basket_outlined),
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
                                  if (productor.isNotEmpty)
                                    Text(
                                      productor,
                                      style: Theme.of(context).textTheme.bodySmall,
                                    ),
                                  const SizedBox(height: 6),
                                  LinearProgressIndicator(
                                    value: (progreso / 100).clamp(0, 1),
                                    minHeight: 6,
                                  ),
                                  const SizedBox(height: 6),
                                  Row(
                                    children: [
                                      Text(
                                        '${precio.toStringAsFixed(2)} $unidad',
                                        style: Theme.of(context).textTheme.bodySmall,
                                      ),
                                      const Spacer(),
                                      Text(
                                        estado,
                                        style: Theme.of(context).textTheme.labelSmall,
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Align(
                          alignment: Alignment.centerRight,
                          child: TextButton.icon(
                            onPressed: () async {
                              final cantidad = await _promptCantidad(context);
                              if (cantidad == null || cantidad <= 0) return;
                              final response = await api.joinGruposConsumoPedido(
                                pedidoId: id,
                                cantidad: cantidad,
                              );
                              if (context.mounted) {
                                final message = response.success
                                    ? i18n.gruposConsumoJoinSuccess
                                    : (response.error ?? i18n.gruposConsumoJoinError);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(message)),
                                );
                              }
                            },
                            icon: const Icon(Icons.group_add),
                            label: Text(i18n.gruposConsumoJoin),
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

  Widget _buildMisPedidosTab(
    BuildContext context,
    Future<ApiResponse<Map<String, dynamic>>> future,
    AppLocalizations i18n,
  ) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: future,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.gruposConsumoMineError));
        }
        final pedidos = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (pedidos.isEmpty) {
          return Center(child: Text(i18n.gruposConsumoMineEmpty));
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: pedidos.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final pedido = pedidos[index];
            final title = pedido['titulo']?.toString() ?? '';
            final estado = pedido['estado']?.toString() ?? '';
            final cantidad = (pedido['cantidad'] as num?)?.toDouble();
            return ListTile(
              leading: const Icon(Icons.shopping_basket_outlined),
              title: Text(title),
              subtitle: Text(estado),
              trailing: cantidad != null ? Text(cantidad.toString()) : null,
            );
          },
        );
      },
    );
  }

  Widget _buildPerfilTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futurePerfil,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.gruposConsumoProfileError));
        }
        final data = response.data!['data'] as Map<String, dynamic>? ?? {};
        final consumidor = data['consumidor'] as Map<String, dynamic>?;
        final esMiembro = data['es_miembro'] == true;
        final saldo = consumidor?['saldo_pendiente']?.toString() ?? '0';
        final rol = consumidor?['rol']?.toString() ?? i18n.gruposConsumoProfileRoleUnknown;

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            ListTile(
              leading: const Icon(Icons.verified_user),
              title: Text(esMiembro ? i18n.gruposConsumoProfileMemberYes : i18n.gruposConsumoProfileMemberNo),
              subtitle: Text(rol),
            ),
            ListTile(
              leading: const Icon(Icons.account_balance_wallet),
              title: Text(i18n.gruposConsumoProfileBalance),
              trailing: Text(saldo),
            ),
            const Divider(),
            FutureBuilder<ApiResponse<Map<String, dynamic>>>(
              future: _futureSuscripciones,
              builder: (context, snap) {
                if (!snap.hasData) {
                  return const SizedBox.shrink();
                }
                final res = snap.data!;
                if (!res.success || res.data == null) {
                  return ListTile(title: Text(i18n.gruposConsumoSubscriptionsError));
                }
                final subs = (res.data!['data'] as List<dynamic>? ?? [])
                    .whereType<Map<String, dynamic>>()
                    .toList();
                if (subs.isEmpty) {
                  return ListTile(title: Text(i18n.gruposConsumoSubscriptionsEmpty));
                }
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(i18n.gruposConsumoSubscriptionsTitle, style: Theme.of(context).textTheme.titleMedium),
                    const SizedBox(height: 8),
                    ...subs.map((s) {
                      final subId = (s['id'] as num?)?.toInt() ?? 0;
                      final estado = s['estado']?.toString() ?? '';
                      return ListTile(
                        leading: const Icon(Icons.repeat),
                        title: Text(s['nombre']?.toString() ?? ''),
                        subtitle: Text(estado),
                        trailing: PopupMenuButton<String>(
                          onSelected: (value) async {
                            final api = ref.read(apiClientProvider);
                            if (value == 'pause') {
                              final r = await api.pauseGruposConsumoSuscripcion(subId);
                              _handleSubAction(context, r.success, r.error, i18n.gruposConsumoSubPauseSuccess, i18n.gruposConsumoSubPauseError);
                            } else if (value == 'cancel') {
                              final r = await api.cancelGruposConsumoSuscripcion(subId);
                              _handleSubAction(context, r.success, r.error, i18n.gruposConsumoSubCancelSuccess, i18n.gruposConsumoSubCancelError);
                            }
                          },
                          itemBuilder: (context) => [
                            PopupMenuItem(value: 'pause', child: Text(i18n.gruposConsumoSubPause)),
                            PopupMenuItem(value: 'cancel', child: Text(i18n.gruposConsumoSubCancel)),
                          ],
                        ),
                      );
                    }),
                  ],
                );
              },
            ),
            const SizedBox(height: 12),
            FilledButton.icon(
              onPressed: () => _showCreateSubscription(context, i18n),
              icon: const Icon(Icons.add),
              label: Text(i18n.gruposConsumoSubCreate),
            ),
          ],
        );
      },
    );
  }

  Widget _buildHistorialTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureHistorial,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.gruposConsumoHistoryError));
        }
        final pedidos = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (pedidos.isEmpty) {
          return Center(child: Text(i18n.gruposConsumoHistoryEmpty));
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: pedidos.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final pedido = pedidos[index];
            final title = pedido['titulo']?.toString() ?? '';
            final estado = pedido['estado']?.toString() ?? '';
            final fecha = pedido['fecha_entrega']?.toString() ?? '';
            return ListTile(
              leading: const Icon(Icons.history),
              title: Text(title),
              subtitle: Text('$estado • $fecha'),
            );
          },
        );
      },
    );
  }

  Widget _buildCatalogTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureProductos,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.gruposConsumoCatalogError));
        }
        final productos = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (productos.isEmpty) {
          return Center(child: Text(i18n.gruposConsumoCatalogEmpty));
        }

        final categorias = <String>{};
        for (final p in productos) {
          final cats = p['categorias'] as List<dynamic>? ?? [];
          for (final c in cats) {
            categorias.add(c.toString());
          }
        }

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _buildCatalogFilters(i18n, categorias.toList()),
            const SizedBox(height: 12),
            ...productos.map((producto) {
              final id = (producto['id'] as num?)?.toInt() ?? 0;
              final nombre = producto['nombre']?.toString() ?? '';
              final precio = producto['precio']?.toString() ?? '';
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: ListTile(
                    leading: const Icon(Icons.eco),
                    title: Text(nombre),
                    subtitle: Text(precio),
                    onTap: () => _showProductDetail(context, i18n, producto),
                    trailing: IconButton(
                      icon: const Icon(Icons.add_shopping_cart),
                      onPressed: () async {
                        final api = ref.read(apiClientProvider);
                        final res = await api.addGruposConsumoListaCompra(
                          productoId: id,
                          cantidad: 1,
                        );
                        if (context.mounted) {
                          final msg = res.success
                              ? i18n.gruposConsumoShoppingAddSuccess
                              : (res.error ?? i18n.gruposConsumoShoppingAddError);
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text(msg)),
                          );
                          if (res.success) _refresh();
                        }
                      },
                    ),
                  ),
                ),
              );
            }),
          ],
        );
      },
    );
  }

  Widget _buildCatalogFilters(AppLocalizations i18n, List<String> categorias) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextField(
          decoration: InputDecoration(
            labelText: i18n.gruposConsumoCatalogSearch,
            prefixIcon: const Icon(Icons.search),
          ),
          onSubmitted: (value) {
            _catalogSearch = value.trim();
            _reloadCatalog();
          },
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: DropdownButtonFormField<String>(
                value: _catalogCategory.isEmpty ? null : _catalogCategory,
                decoration: InputDecoration(labelText: i18n.gruposConsumoCatalogCategory),
                items: [
                  DropdownMenuItem(value: '', child: Text(i18n.gruposConsumoCatalogCategoryAll)),
                  ...categorias.map((c) => DropdownMenuItem(value: c, child: Text(c))),
                ],
                onChanged: (value) {
                  _catalogCategory = value ?? '';
                  _reloadCatalog();
                },
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
                future: _futureProductores,
                builder: (context, snapshot) {
                  final productores = (snapshot.data?.data?['data'] as List<dynamic>? ?? [])
                      .whereType<Map<String, dynamic>>()
                      .toList();
                  return DropdownButtonFormField<int>(
                    value: _catalogProducerId > 0 ? _catalogProducerId : null,
                    decoration: InputDecoration(labelText: i18n.gruposConsumoCatalogProducer),
                    items: [
                      DropdownMenuItem(value: 0, child: Text(i18n.gruposConsumoCatalogProducerAll)),
                      ...productores.map((p) {
                        final id = (p['id'] as num?)?.toInt() ?? 0;
                        final nombre = p['nombre']?.toString() ?? '';
                        return DropdownMenuItem(value: id, child: Text(nombre));
                      }),
                    ],
                    onChanged: (value) {
                      _catalogProducerId = value ?? 0;
                      _reloadCatalog();
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildShoppingListTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureLista,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.gruposConsumoShoppingListError));
        }
        final items = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (items.isEmpty) {
          return Center(child: Text(i18n.gruposConsumoShoppingListEmpty));
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: items.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final item = items[index];
            final id = (item['id'] as num?)?.toInt() ?? 0;
            final nombre = item['producto']?['nombre']?.toString() ?? '';
            final cantidad = item['cantidad']?.toString() ?? '';
            return Card(
              elevation: 1,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: ListTile(
                leading: const Icon(Icons.list_alt),
                title: Text(nombre),
                subtitle: Text('${i18n.gruposConsumoShoppingQuantity}: $cantidad'),
                trailing: IconButton(
                  icon: const Icon(Icons.delete_outline),
                  onPressed: () async {
                    final api = ref.read(apiClientProvider);
                    final res = await api.removeGruposConsumoListaCompra(id);
                    if (context.mounted) {
                      final msg = res.success
                          ? i18n.gruposConsumoShoppingRemoveSuccess
                          : (res.error ?? i18n.gruposConsumoShoppingRemoveError);
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(msg)),
                      );
                      if (res.success) _refresh();
                    }
                  },
                ),
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _showCreateSubscription(BuildContext context, AppLocalizations i18n) async {
    final cantidadController = TextEditingController(text: '1');
    String frecuencia = 'semanal';
    int? productoId;

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final bottom = MediaQuery.of(context).viewInsets.bottom;
        return StatefulBuilder(
          builder: (context, setState) {
            return Padding(
              padding: EdgeInsets.fromLTRB(16, 16, 16, bottom + 16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(i18n.gruposConsumoSubCreate, style: Theme.of(context).textTheme.titleLarge),
                  const SizedBox(height: 12),
                  FutureBuilder<ApiResponse<Map<String, dynamic>>>(
                    future: _futureProductos,
                    builder: (context, snapshot) {
                      if (!snapshot.hasData) {
                        return const Center(child: CircularProgressIndicator());
                      }
                      final response = snapshot.data!;
                      final productos = (response.data?['data'] as List<dynamic>? ?? [])
                          .whereType<Map<String, dynamic>>()
                          .toList();
                      if (productos.isEmpty) {
                        return Text(i18n.gruposConsumoSubNoProducts);
                      }
                      productoId ??= (productos.first['id'] as num?)?.toInt();
                      return DropdownButtonFormField<int>(
                        value: productoId,
                        decoration: InputDecoration(labelText: i18n.gruposConsumoSubProduct),
                        items: productos.map((p) {
                          final id = (p['id'] as num?)?.toInt() ?? 0;
                          final nombre = p['nombre']?.toString() ?? '';
                          return DropdownMenuItem<int>(
                            value: id,
                            child: Text(nombre),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setState(() => productoId = value);
                        },
                      );
                    },
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: cantidadController,
                    decoration: InputDecoration(labelText: i18n.gruposConsumoSubQuantity),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  ),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    value: frecuencia,
                    decoration: InputDecoration(labelText: i18n.gruposConsumoSubFrequency),
                    items: [
                      DropdownMenuItem(value: 'semanal', child: Text(i18n.gruposConsumoSubFrequencyWeekly)),
                      DropdownMenuItem(value: 'quincenal', child: Text(i18n.gruposConsumoSubFrequencyBiweekly)),
                      DropdownMenuItem(value: 'mensual', child: Text(i18n.gruposConsumoSubFrequencyMonthly)),
                    ],
                    onChanged: (value) {
                      if (value == null) return;
                      setState(() => frecuencia = value);
                    },
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
      },
    );

    if (result == true) {
      final cantidad = double.tryParse(cantidadController.text.replaceAll(',', '.')) ?? 1;
      final api = ref.read(apiClientProvider);
      final res = await api.createGruposConsumoSuscripcion(
        productoId: productoId ?? 0,
        cantidad: cantidad,
        frecuencia: frecuencia,
      );
      if (context.mounted) {
        _handleSubAction(
          context,
          res.success,
          res.error,
          i18n.gruposConsumoSubCreateSuccess,
          i18n.gruposConsumoSubCreateError,
        );
      }
    }

    cantidadController.dispose();
  }

  void _handleSubAction(
    BuildContext context,
    bool success,
    String? error,
    String successMsg,
    String errorMsg,
  ) {
    final msg = success ? successMsg : (error ?? errorMsg);
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    if (success) _refresh();
  }

  Widget _buildCyclesTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureCiclos,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.gruposConsumoCyclesError));
        }
        final ciclosRaw = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (ciclosRaw.isEmpty) {
          return Center(child: Text(i18n.gruposConsumoCyclesEmpty));
        }

        final ciclos = ciclosRaw.where(_matchesCycleFilters).toList();
        if (ciclos.isEmpty) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _buildCyclesFilters(i18n),
              const SizedBox(height: 12),
              Text(i18n.gruposConsumoCyclesEmpty),
            ],
          );
        }

        final deliveryMarkers = ciclos
            .where((c) => (c['lugar_entrega']?.toString() ?? '').isNotEmpty)
            .map((c) => {
                  'title': '${c['lugar_entrega'] ?? ''} - ${c['fecha_entrega'] ?? ''}',
                  'address': c['lugar_entrega'],
                })
            .toList();

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _buildCyclesFilters(i18n),
            const SizedBox(height: 8),
            if (deliveryMarkers.isNotEmpty)
              Align(
                alignment: Alignment.centerRight,
                child: OutlinedButton.icon(
                  onPressed: () {
                    final layoutConfig = ref.read(layoutConfigProvider);
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => GruposConsumoMapScreen(
                          title: i18n.gruposConsumoDeliveriesMapTitle,
                          markers: deliveryMarkers,
                          routes: const [],
                          provider: layoutConfig.mapProvider,
                          googleMapsApiKey: layoutConfig.googleMapsApiKey,
                          showRoutes: true,
                        ),
                      ),
                    );
                  },
                  icon: const Icon(Icons.map),
                  label: Text(i18n.gruposConsumoDeliveriesMapOpen),
                ),
              )
            else
              Text(i18n.gruposConsumoDeliveriesMapNoData),
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: OutlinedButton.icon(
                onPressed: () async {
                  await _openRoutesMap(context, i18n, ciclos);
                },
                icon: const Icon(Icons.alt_route),
                label: Text(i18n.gruposConsumoRoutesMapOpen),
              ),
            ),
            const SizedBox(height: 12),
            ...ciclos.map((ciclo) {
              final title = ciclo['titulo']?.toString() ?? '';
              final fecha = ciclo['fecha_entrega']?.toString() ?? '';
              final hora = ciclo['hora_entrega']?.toString() ?? '';
              final lugar = ciclo['lugar_entrega']?.toString() ?? '';
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: ListTile(
                    leading: const Icon(Icons.event),
                    title: Text(title),
                    subtitle: Text('$fecha $hora • $lugar'),
                  ),
                ),
              );
            }),
          ],
        );
      },
    );
  }

  Widget _buildProducersTab(AppLocalizations i18n) {
    final future = _producersUseNearby ? _futureProductoresCercanos : _futureProductores;
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: future,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.gruposConsumoProducersError));
        }
        final productores = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (productores.isEmpty) {
          return Center(child: Text(i18n.gruposConsumoProducersEmpty));
        }

        final markers = productores
            .where((p) => p['coordenadas'] != null)
            .map((p) {
              final coords = p['coordenadas'] as Map<String, dynamic>;
              return {
                'title': p['nombre']?.toString() ?? '',
                'lat': coords['lat'],
                'lng': coords['lng'],
              };
            })
            .toList();

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _buildProducersFilters(i18n),
            const SizedBox(height: 8),
            if (markers.isNotEmpty)
              Align(
                alignment: Alignment.centerRight,
                child: OutlinedButton.icon(
                  onPressed: () {
                    final layoutConfig = ref.read(layoutConfigProvider);
                    final provider = layoutConfig.mapProvider;
                    final apiKey = layoutConfig.googleMapsApiKey;
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => GruposConsumoMapScreen(
                          title: i18n.gruposConsumoMapTitle,
                          markers: markers,
                          routes: const [],
                          provider: provider,
                          googleMapsApiKey: apiKey,
                        ),
                      ),
                    );
                  },
                  icon: const Icon(Icons.map),
                  label: Text(i18n.gruposConsumoMapOpen),
                ),
              )
            else
              Text(i18n.gruposConsumoMapNoData),
            const SizedBox(height: 12),
            ...productores.map((productor) {
              final nombre = productor['nombre']?.toString() ?? '';
              final region = productor['region']?.toString() ?? productor['ubicacion']?.toString() ?? '';
              final radio = productor['radio_entrega_km']?.toString() ?? '';
              final tieneEntrega = productor['tiene_entrega_domicilio'] == true;
              final eco = productor['certificacion_eco'] == true;
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: ListTile(
                    leading: const Icon(Icons.agriculture),
                    title: Text(nombre),
                    subtitle: Text('$region • ${i18n.gruposConsumoProducersRadius}: $radio km'),
                    trailing: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (eco) const Icon(Icons.eco, size: 18),
                        const SizedBox(width: 6),
                        Icon(tieneEntrega ? Icons.local_shipping : Icons.store_mall_directory),
                      ],
                    ),
                  ),
                ),
              );
            }),
          ],
        );
      },
    );
  }

  Widget _buildCyclesFilters(AppLocalizations i18n) {
    return Card(
      elevation: 0,
      color: Theme.of(context).colorScheme.surfaceVariant.withOpacity(0.4),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(i18n.gruposConsumoCyclesFiltersTitle, style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () async {
                      final picked = await _pickDate(context, _cyclesFrom);
                      if (picked == null) return;
                      setState(() => _cyclesFrom = picked);
                    },
                    child: Text(_cyclesFrom == null
                        ? i18n.gruposConsumoCyclesFrom
                        : '${i18n.gruposConsumoCyclesFrom}: ${_formatDate(_cyclesFrom!)}'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton(
                    onPressed: () async {
                      final picked = await _pickDate(context, _cyclesTo);
                      if (picked == null) return;
                      setState(() => _cyclesTo = picked);
                    },
                    child: Text(_cyclesTo == null
                        ? i18n.gruposConsumoCyclesTo
                        : '${i18n.gruposConsumoCyclesTo}: ${_formatDate(_cyclesTo!)}'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            DropdownButtonFormField<String>(
              value: _cyclesTurn,
              decoration: InputDecoration(labelText: i18n.gruposConsumoCyclesTurn),
              items: [
                DropdownMenuItem(value: 'any', child: Text(i18n.gruposConsumoCyclesTurnAny)),
                DropdownMenuItem(value: 'morning', child: Text(i18n.gruposConsumoCyclesTurnMorning)),
                DropdownMenuItem(value: 'afternoon', child: Text(i18n.gruposConsumoCyclesTurnAfternoon)),
                DropdownMenuItem(value: 'evening', child: Text(i18n.gruposConsumoCyclesTurnEvening)),
              ],
              onChanged: (value) {
                if (value == null) return;
                setState(() => _cyclesTurn = value);
              },
            ),
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: () {
                  setState(() {
                    _cyclesFrom = null;
                    _cyclesTo = null;
                    _cyclesTurn = 'any';
                  });
                },
                child: Text(i18n.gruposConsumoCyclesFiltersClear),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openRoutesMap(
    BuildContext context,
    AppLocalizations i18n,
    List<Map<String, dynamic>> ciclos,
  ) async {
    final layoutConfig = ref.read(layoutConfigProvider);
    final useNearby = _producersUseNearby;
    final producersResponse = await (useNearby ? _futureProductoresCercanos : _futureProductores);
    if (!producersResponse.success || producersResponse.data == null) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(i18n.gruposConsumoRoutesMapNoData)));
      }
      return;
    }
    final productores = (producersResponse.data!['data'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .toList();

    final deliveries = ciclos.map(_extractDeliveryNode).whereType<Map<String, dynamic>>().toList();
    if (productores.isEmpty || deliveries.isEmpty) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(i18n.gruposConsumoRoutesMapNoData)));
      }
      return;
    }

    final routes = <Map<String, dynamic>>[];
    final markers = <Map<String, dynamic>>[];

    final routesByCiclo = _buildRoutesByCiclo(productores, ciclos, deliveries);
    if (routesByCiclo.isNotEmpty) {
      routes.addAll(routesByCiclo);
    } else {
      for (final producer in productores) {
        final fromNode = _extractProducerNode(producer);
        if (fromNode == null) continue;
        Map<String, dynamic> toNode = deliveries.first;
        final fromLat = _toDouble(fromNode['lat']);
        final fromLng = _toDouble(fromNode['lng']);
        if (fromLat != null && fromLng != null) {
          final deliveriesWithCoords = deliveries
              .where((d) => _toDouble(d['lat']) != null && _toDouble(d['lng']) != null)
              .toList();
          if (deliveriesWithCoords.isNotEmpty) {
            double bestDistance = double.infinity;
            for (final delivery in deliveriesWithCoords) {
              final dLat = _toDouble(delivery['lat'])!;
              final dLng = _toDouble(delivery['lng'])!;
              final dist = _haversine(fromLat, fromLng, dLat, dLng);
              if (dist < bestDistance) {
                bestDistance = dist;
                toNode = delivery;
              }
            }
          }
        }
        routes.add({'from': fromNode, 'to': toNode});
      }
    }

    final producerIdsAdded = <int>{};
    for (final producer in productores) {
      final node = _extractProducerNode(producer);
      if (node != null) {
        final id = _toInt(producer['id']);
        if (id == null || producerIdsAdded.add(id)) {
          markers.add(node);
        }
      }
    }
    for (final delivery in deliveries) {
      markers.add(delivery);
    }

    if (context.mounted) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => GruposConsumoMapScreen(
            title: i18n.gruposConsumoRoutesMapTitle,
            markers: markers,
            routes: routes,
            provider: layoutConfig.mapProvider,
            googleMapsApiKey: layoutConfig.googleMapsApiKey,
            showRoutes: false,
          ),
        ),
      );
    }
  }

  List<Map<String, dynamic>> _buildRoutesByCiclo(
    List<Map<String, dynamic>> productores,
    List<Map<String, dynamic>> ciclos,
    List<Map<String, dynamic>> deliveries,
  ) {
    final routes = <Map<String, dynamic>>[];
    final deliveriesByTitle = {
      for (final d in deliveries) (d['title']?.toString() ?? ''): d,
    };
    for (final ciclo in ciclos) {
      final ids = _extractProductorIds(ciclo['productor_ids']);
      if (ids.isEmpty) continue;
      final delivery = _extractDeliveryNode(ciclo);
      if (delivery == null) continue;
      final deliveryKey = delivery['title']?.toString() ?? '';
      deliveriesByTitle[deliveryKey] = delivery;
      for (final productor in productores) {
        final productorId = _toInt(productor['id']);
        if (productorId == null || !ids.contains(productorId)) continue;
        final fromNode = _extractProducerNode(productor);
        if (fromNode == null) continue;
        routes.add({'from': fromNode, 'to': delivery});
      }
    }
    return routes;
  }

  Set<int> _extractProductorIds(dynamic raw) {
    if (raw is List) {
      return raw.map(_toInt).whereType<int>().toSet();
    }
    if (raw is String && raw.isNotEmpty) {
      final ids = raw.split(',').map((v) => _toInt(v)).whereType<int>().toSet();
      return ids;
    }
    return {};
  }

  Map<String, dynamic>? _extractDeliveryNode(Map<String, dynamic> ciclo) {
    final address = ciclo['lugar_entrega']?.toString() ?? '';
    final coords = ciclo['coordenadas'] as Map<String, dynamic>?;
    final lat = _toDouble(coords?['lat'] ?? ciclo['lat'] ?? ciclo['latitude']);
    final lng = _toDouble(coords?['lng'] ?? ciclo['lng'] ?? ciclo['longitude']);
    if (lat == null && lng == null && address.isEmpty) return null;
    return {
      'title': '${ciclo['lugar_entrega'] ?? ''} - ${ciclo['fecha_entrega'] ?? ''}',
      'lat': lat,
      'lng': lng,
      'address': address.isNotEmpty ? address : null,
    };
  }

  Map<String, dynamic>? _extractProducerNode(Map<String, dynamic> productor) {
    final coords = productor['coordenadas'] as Map<String, dynamic>?;
    final lat = _toDouble(coords?['lat'] ?? productor['lat'] ?? productor['latitude']);
    final lng = _toDouble(coords?['lng'] ?? productor['lng'] ?? productor['longitude']);
    final address = productor['direccion']?.toString() ??
        productor['ubicacion']?.toString() ??
        productor['region']?.toString() ??
        '';
    if (lat == null && lng == null && address.isEmpty) return null;
    return {
      'title': productor['nombre']?.toString() ?? '',
      'lat': lat,
      'lng': lng,
      'address': address.isNotEmpty ? address : null,
    };
  }

  bool _matchesCycleFilters(Map<String, dynamic> ciclo) {
    final date = _parseDate(ciclo['fecha_entrega']?.toString() ?? '');
    if (_cyclesFrom != null && date != null) {
      final fromDate = DateTime(_cyclesFrom!.year, _cyclesFrom!.month, _cyclesFrom!.day);
      if (date.isBefore(fromDate)) return false;
    }
    if (_cyclesTo != null && date != null) {
      final toDate = DateTime(_cyclesTo!.year, _cyclesTo!.month, _cyclesTo!.day, 23, 59, 59);
      if (date.isAfter(toDate)) return false;
    }
    if (_cyclesTurn == 'any') return true;
    final turn = _extractTurn(ciclo);
    return turn == _cyclesTurn;
  }

  String _extractTurn(Map<String, dynamic> ciclo) {
    final raw = (ciclo['turno'] ??
            ciclo['turno_entrega'] ??
            ciclo['franja_horaria'] ??
            ciclo['hora_entrega'] ??
            '')
        .toString()
        .toLowerCase();
    if (raw.contains('mañana') || raw.contains('manana') || raw.contains('morning')) return 'morning';
    if (raw.contains('tarde') || raw.contains('afternoon')) return 'afternoon';
    if (raw.contains('noche') || raw.contains('evening') || raw.contains('night')) return 'evening';
    final hour = _parseHour(raw);
    if (hour != null) {
      if (hour < 12) return 'morning';
      if (hour < 18) return 'afternoon';
      return 'evening';
    }
    return 'any';
  }

  int? _parseHour(String raw) {
    final match = RegExp(r'(\\d{1,2})').firstMatch(raw);
    if (match == null) return null;
    final hour = int.tryParse(match.group(1)!);
    if (hour == null) return null;
    return hour.clamp(0, 23);
  }

  DateTime? _parseDate(String value) {
    final trimmed = value.trim();
    if (trimmed.isEmpty) return null;
    final direct = DateTime.tryParse(trimmed);
    if (direct != null) return DateTime(direct.year, direct.month, direct.day);
    final parts = trimmed.split(RegExp(r'[/\\-]'));
    if (parts.length == 3) {
      final p0 = int.tryParse(parts[0]);
      final p1 = int.tryParse(parts[1]);
      final p2 = int.tryParse(parts[2]);
      if (p0 != null && p1 != null && p2 != null) {
        if (p0 > 31) {
          return DateTime(p0, p1, p2);
        }
        return DateTime(p2, p1, p0);
      }
    }
    return null;
  }

  String _formatDate(DateTime date) {
    final d = date.day.toString().padLeft(2, '0');
    final m = date.month.toString().padLeft(2, '0');
    final y = date.year.toString();
    return '$d/$m/$y';
  }

  Future<DateTime?> _pickDate(BuildContext context, DateTime? initial) {
    final now = DateTime.now();
    final init = initial ?? now;
    return showDatePicker(
      context: context,
      initialDate: init,
      firstDate: DateTime(now.year - 5),
      lastDate: DateTime(now.year + 5),
    );
  }

  double? _toDouble(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    return double.tryParse(value.toString());
  }

  int? _toInt(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse(value.toString());
  }

  double _haversine(double lat1, double lng1, double lat2, double lng2) {
    const radius = 6371.0;
    final dLat = _deg2rad(lat2 - lat1);
    final dLng = _deg2rad(lng2 - lng1);
    final a = (Math.sin(dLat / 2) * Math.sin(dLat / 2)) +
        Math.cos(_deg2rad(lat1)) * Math.cos(_deg2rad(lat2)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    final c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return radius * c;
  }

  double _deg2rad(double deg) => deg * (Math.pi / 180.0);

  Widget _buildProducersFilters(AppLocalizations i18n) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SwitchListTile(
          value: _producersUseNearby,
          onChanged: (value) {
            setState(() => _producersUseNearby = value);
            _reloadProducers();
          },
          title: Text(i18n.gruposConsumoProducersNearby),
        ),
        Row(
          children: [
            Expanded(
              child: CheckboxListTile(
                value: _producersOnlyDelivery,
                onChanged: (value) {
                  setState(() => _producersOnlyDelivery = value ?? false);
                  _reloadProducers();
                },
                title: Text(i18n.gruposConsumoProducersDelivery),
                controlAffinity: ListTileControlAffinity.leading,
              ),
            ),
            Expanded(
              child: CheckboxListTile(
                value: _producersOnlyEco,
                onChanged: (value) {
                  setState(() => _producersOnlyEco = value ?? false);
                  _reloadProducers();
                },
                title: Text(i18n.gruposConsumoProducersEco),
                controlAffinity: ListTileControlAffinity.leading,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Future<void> _showProductDetail(
    BuildContext context,
    AppLocalizations i18n,
    Map<String, dynamic> producto,
  ) async {
    final nombre = producto['nombre']?.toString() ?? '';
    final descripcion = producto['descripcion']?.toString() ?? '';
    final precio = producto['precio']?.toString() ?? '';
    final unidad = producto['unidad']?.toString() ?? '';
    final stock = producto['stock']?.toString() ?? '';
    final temporada = producto['temporada']?.toString() ?? '';
    final origen = producto['origen']?.toString() ?? '';
    final imagen = producto['imagen']?.toString() ?? '';
    final categorias = (producto['categorias'] as List<dynamic>? ?? [])
        .map((c) => c.toString())
        .toList()
        .join(', ');

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final bottom = MediaQuery.of(context).viewInsets.bottom;
        return Padding(
          padding: EdgeInsets.fromLTRB(16, 16, 16, bottom + 16),
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(nombre, style: Theme.of(context).textTheme.titleLarge),
                const SizedBox(height: 8),
                if (imagen.isNotEmpty)
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.network(imagen, height: 180, width: double.infinity, fit: BoxFit.cover),
                  ),
                if (descripcion.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(descripcion),
                ],
                const SizedBox(height: 8),
                Text('${i18n.gruposConsumoProductPrice}: $precio $unidad'),
                if (stock.isNotEmpty) Text('${i18n.gruposConsumoProductStock}: $stock'),
                if (temporada.isNotEmpty) Text('${i18n.gruposConsumoProductSeason}: $temporada'),
                if (origen.isNotEmpty) Text('${i18n.gruposConsumoProductOrigin}: $origen'),
                if (categorias.isNotEmpty) Text('${i18n.gruposConsumoProductCategories}: $categorias'),
                const SizedBox(height: 12),
                FilledButton.icon(
                  onPressed: () async {
                    final api = ref.read(apiClientProvider);
                    final id = (producto['id'] as num?)?.toInt() ?? 0;
                    final res = await api.addGruposConsumoListaCompra(
                      productoId: id,
                      cantidad: 1,
                    );
                    if (context.mounted) {
                      final msg = res.success
                          ? i18n.gruposConsumoShoppingAddSuccess
                          : (res.error ?? i18n.gruposConsumoShoppingAddError);
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text(msg)),
                      );
                      if (res.success) _refresh();
                      Navigator.pop(context);
                    }
                  },
                  icon: const Icon(Icons.add_shopping_cart),
                  label: Text(i18n.gruposConsumoShoppingAdd),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<double?> _promptCantidad(BuildContext context) async {
    final controller = TextEditingController();
    final i18n = AppLocalizations.of(context)!;
    final result = await showDialog<double>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(i18n.gruposConsumoJoinTitle),
          content: TextField(
            controller: controller,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(
              labelText: i18n.gruposConsumoJoinQuantity,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(i18n.commonCancel),
            ),
            FilledButton(
              onPressed: () {
                final value = double.tryParse(controller.text.replaceAll(',', '.'));
                Navigator.pop(context, value);
              },
              child: Text(i18n.commonSave),
            ),
          ],
        );
      },
    );
    controller.dispose();
    return result;
  }
}
