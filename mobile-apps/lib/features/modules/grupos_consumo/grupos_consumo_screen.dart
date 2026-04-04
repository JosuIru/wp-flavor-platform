import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_mutation.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import 'grupos_consumo_map_screen.dart';

import '../../../core/providers/sync_provider.dart';

part 'grupos_consumo_screen_parts.dart';

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
    final i18n = AppLocalizations.of(context);
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.gruposConsumoError,
            onRetry: _refresh,
            icon: Icons.shopping_basket_outlined,
          );
        }

        final pedidos = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (pedidos.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.shopping_basket_outlined,
            title: i18n.gruposConsumoEmpty,
          );
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
                                color: Theme.of(context).colorScheme.surfaceContainerHighest,
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
                            if (!mounted) return;
                            await FlavorMutation.runApiResponse(
                              this.context,
                              request: () => api.joinGruposConsumoPedido(
                                pedidoId: id,
                                cantidad: cantidad,
                              ),
                              successMessage: i18n.gruposConsumoJoinSuccess,
                              fallbackErrorMessage: i18n.gruposConsumoJoinError,
                            );
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.gruposConsumoMineError,
            onRetry: _refresh,
            icon: Icons.shopping_basket_outlined,
          );
        }
        final pedidos = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (pedidos.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.shopping_basket_outlined,
            title: i18n.gruposConsumoMineEmpty,
          );
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

}
