part of 'grupos_consumo_screen.dart';

extension _GruposConsumoScreenStateParts on _GruposConsumoScreenState {
  Widget _buildPerfilTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futurePerfil,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.gruposConsumoProfileError,
            onRetry: _refresh,
            icon: Icons.verified_user_outlined,
          );
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
                            if (value == 'pause') {
                              if (!mounted) return;
                              await FlavorMutation.runApiResponse(
                                context,
                                request: () => ref.read(apiClientProvider).pauseGruposConsumoSuscripcion(subId),
                                successMessage: i18n.gruposConsumoSubPauseSuccess,
                                fallbackErrorMessage: i18n.gruposConsumoSubPauseError,
                                onSuccess: _refresh,
                              );
                            } else if (value == 'cancel') {
                              if (!mounted) return;
                              await FlavorMutation.runApiResponse(
                                context,
                                request: () => ref.read(apiClientProvider).cancelGruposConsumoSuscripcion(subId),
                                successMessage: i18n.gruposConsumoSubCancelSuccess,
                                fallbackErrorMessage: i18n.gruposConsumoSubCancelError,
                                onSuccess: _refresh,
                              );
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(message: i18n.gruposConsumoHistoryError);
        }
        final pedidos = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (pedidos.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.history,
            title: i18n.gruposConsumoHistoryEmpty,
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(message: i18n.gruposConsumoCatalogError);
        }
        final productos = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (productos.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.eco_outlined,
            title: i18n.gruposConsumoCatalogEmpty,
          );
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
                        await FlavorMutation.runApiResponse(
                          context,
                          request: () => ref.read(apiClientProvider).addGruposConsumoListaCompra(
                                productoId: id,
                                cantidad: 1,
                              ),
                          successMessage: i18n.gruposConsumoShoppingAddSuccess,
                          fallbackErrorMessage: i18n.gruposConsumoShoppingAddError,
                          onSuccess: _refresh,
                        );
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(message: i18n.gruposConsumoShoppingListError);
        }
        final items = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (items.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.shopping_basket_outlined,
            title: i18n.gruposConsumoShoppingListEmpty,
          );
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
                    await FlavorMutation.runApiResponse(
                      context,
                      request: () => ref.read(apiClientProvider).removeGruposConsumoListaCompra(id),
                      successMessage: i18n.gruposConsumoShoppingRemoveSuccess,
                      fallbackErrorMessage: i18n.gruposConsumoShoppingRemoveError,
                      onSuccess: _refresh,
                    );
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
                        return const Padding(
                          padding: EdgeInsets.symmetric(vertical: 24),
                          child: FlavorLoadingState(),
                        );
                      }
                      final response = snapshot.data!;
                      final productos = (response.data?['data'] as List<dynamic>? ?? [])
                          .whereType<Map<String, dynamic>>()
                          .toList();
                      if (productos.isEmpty) {
                        return FlavorEmptyState(
                          icon: Icons.inventory_2_outlined,
                          title: i18n.gruposConsumoSubNoProducts,
                        );
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
      if (!mounted) return;
      await FlavorMutation.runApiResponse(
        this.context,
        request: () => ref.read(apiClientProvider).createGruposConsumoSuscripcion(
              productoId: productoId ?? 0,
              cantidad: cantidad,
              frecuencia: frecuencia,
            ),
        successMessage: i18n.gruposConsumoSubCreateSuccess,
        fallbackErrorMessage: i18n.gruposConsumoSubCreateError,
        onSuccess: _refresh,
      );
    }

    cantidadController.dispose();
  }

  Widget _buildCyclesTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureCiclos,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(message: i18n.gruposConsumoCyclesError);
        }
        final ciclosRaw = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (ciclosRaw.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.event_busy_outlined,
            title: i18n.gruposConsumoCyclesEmpty,
          );
        }

        final ciclos = ciclosRaw.where(_matchesCycleFilters).toList();
        if (ciclos.isEmpty) {
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _buildCyclesFilters(i18n),
              const SizedBox(height: 12),
              FlavorEmptyState(
                icon: Icons.filter_alt_off_outlined,
                title: i18n.gruposConsumoCyclesEmpty,
              ),
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(message: i18n.gruposConsumoProducersError);
        }
        final productores = (response.data!['data'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();
        if (productores.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.agriculture_outlined,
            title: i18n.gruposConsumoProducersEmpty,
          );
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
      color: Theme.of(context)
          .colorScheme
          .surfaceContainerHighest
          .withOpacity(0.4),
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
                      // ignore: invalid_use_of_protected_member
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
                      // ignore: invalid_use_of_protected_member
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
                // ignore: invalid_use_of_protected_member
                setState(() => _cyclesTurn = value);
              },
            ),
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: () {
                  // ignore: invalid_use_of_protected_member
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
        FlavorSnackbar.showError(context, i18n.gruposConsumoRoutesMapNoData);
      }
      return;
    }
    final productores = (producersResponse.data!['data'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .toList();

    final deliveries = ciclos.map(_extractDeliveryNode).whereType<Map<String, dynamic>>().toList();
    if (productores.isEmpty || deliveries.isEmpty) {
      if (context.mounted) {
        FlavorSnackbar.showError(context, i18n.gruposConsumoRoutesMapNoData);
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
              final dLat = _toDouble(delivery['lat']);
              final dLng = _toDouble(delivery['lng']);
              if (dLat == null || dLng == null) continue;
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
    final a = (math.sin(dLat / 2) * math.sin(dLat / 2)) +
        math.cos(_deg2rad(lat1)) * math.cos(_deg2rad(lat2)) * math.sin(dLng / 2) * math.sin(dLng / 2);
    final c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a));
    return radius * c;
  }

  double _deg2rad(double deg) => deg * (math.pi / 180.0);

  Widget _buildProducersFilters(AppLocalizations i18n) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SwitchListTile(
          value: _producersUseNearby,
          onChanged: (value) {
            // ignore: invalid_use_of_protected_member
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
                  // ignore: invalid_use_of_protected_member
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
                  // ignore: invalid_use_of_protected_member
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
                    final id = (producto['id'] as num?)?.toInt() ?? 0;
                    final added = await FlavorMutation.runApiResponse(
                      context,
                      request: () => ref.read(apiClientProvider).addGruposConsumoListaCompra(
                            productoId: id,
                            cantidad: 1,
                          ),
                      successMessage: i18n.gruposConsumoShoppingAddSuccess,
                      fallbackErrorMessage: i18n.gruposConsumoShoppingAddError,
                      onSuccess: _refresh,
                    );

                    if (added && context.mounted) {
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
    final i18n = AppLocalizations.of(context);
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
