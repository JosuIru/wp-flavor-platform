import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../models/module_config.dart';
import '../widgets/dynamic_card.dart';
import '../widgets/dynamic_list_tile.dart';
import '../widgets/dynamic_grid_item.dart';
import '../widgets/dynamic_dashboard.dart';
import '../widgets/icon_helper.dart';
import 'dynamic_detail_screen.dart';
import 'dynamic_form_screen.dart';

/// Pantalla de lista dinámica que renderiza cualquier módulo
/// según su configuración.
class DynamicListScreen extends ConsumerStatefulWidget {
  final ModuleConfig config;

  const DynamicListScreen({super.key, required this.config});

  @override
  ConsumerState<DynamicListScreen> createState() => _DynamicListScreenState();
}

class _DynamicListScreenState extends ConsumerState<DynamicListScreen> {
  List<dynamic> _items = [];
  Map<String, dynamic>? _dashboardData;
  bool _loading = true;
  String? _error;
  Map<String, dynamic> _filtrosActivos = {};
  String _busqueda = '';
  final _searchController = TextEditingController();

  ModuleConfig get config => widget.config;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);

      // Construir parámetros de query
      final queryParams = <String, dynamic>{};
      _filtrosActivos.forEach((key, value) {
        if (value != null && value.toString().isNotEmpty) {
          queryParams[key] = value;
        }
      });
      if (_busqueda.isNotEmpty) {
        queryParams['busqueda'] = _busqueda;
      }

      final response = await api.get(config.endpoint, queryParams: queryParams);

      if (response.success && response.data != null) {
        setState(() {
          // Para dashboard, guardamos todos los datos
          if (config.layout == LayoutType.dashboard) {
            _dashboardData = response.data;
            _items = response.data!['items'] ??
                     response.data!['data'] ??
                     response.data!['lista'] ?? [];
          } else {
            // Intentar obtener la lista de items
            _items = _extractItems(response.data!);
          }
          _loading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar datos';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  List<dynamic> _extractItems(Map<String, dynamic> data) {
    // Intentar varias keys comunes
    final possibleKeys = [
      config.id, // El id del módulo como key
      '${config.id}s', // Plural
      'items',
      'data',
      'lista',
      'resultados',
      'results',
    ];

    for (final key in possibleKeys) {
      if (data[key] is List) {
        return data[key] as List;
      }
    }

    // Si el data mismo es una lista
    if (data['success'] == true) {
      // Buscar cualquier key que sea lista
      for (final entry in data.entries) {
        if (entry.value is List && entry.key != 'errors') {
          return entry.value as List;
        }
      }
    }

    return [];
  }

  void _onFilterChanged(String key, dynamic value) {
    setState(() {
      _filtrosActivos[key] = value;
    });
    _loadData();
  }

  void _onSearch(String value) {
    setState(() {
      _busqueda = value;
    });
    _loadData();
  }

  void _onItemTap(Map<String, dynamic> item) {
    if (config.detailEndpoint != null) {
      final itemId = item['id']?.toString() ?? '';
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => DynamicDetailScreen(
            config: config,
            itemId: itemId,
            initialData: item,
          ),
        ),
      );
    }
  }

  void _onFabPressed() {
    if (config.fab == null) return;

    if (config.fab!.accion == 'create') {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => DynamicFormScreen(
            config: config,
            mode: FormMode.create,
          ),
        ),
      ).then((result) {
        if (result == true) _loadData();
      });
    }
  }

  Future<void> _executeAction(ModuleAction action, Map<String, dynamic> item) async {
    switch (action.tipo) {
      case 'navigate':
        _onItemTap(item);
        break;
      case 'api_call':
        if (action.requiereConfirmacion) {
          final confirmed = await showDialog<bool>(
            context: context,
            builder: (ctx) => AlertDialog(
              title: Text(action.label),
              content: const Text('¿Estás seguro?'),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx, false),
                  child: const Text('Cancelar'),
                ),
                FilledButton(
                  onPressed: () => Navigator.pop(ctx, true),
                  child: const Text('Confirmar'),
                ),
              ],
            ),
          );
          if (confirmed != true) return;
        }

        if (action.endpoint != null) {
          final api = ref.read(apiClientProvider);
          final endpoint = action.endpoint!.replaceAll('{id}', item['id'].toString());
          await api.post(endpoint, {});
          _loadData();
        }
        break;
      case 'call':
        final telefono = item['telefono'] ?? item['phone'] ?? '';
        if (telefono.isNotEmpty) {
          // Usar url_launcher
        }
        break;
      case 'share':
        // Usar share_plus
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(config.titulo),
        actions: [
          if (config.filtros.any((f) => f.tipo == 'search'))
            IconButton(
              icon: const Icon(Icons.search),
              onPressed: () => _showSearchDialog(),
            ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtros
          if (config.filtros.isNotEmpty) _buildFilters(),

          // Contenido principal
          Expanded(child: _buildBody()),
        ],
      ),
      floatingActionButton: config.fab != null
          ? FloatingActionButton.extended(
              onPressed: _onFabPressed,
              icon: Icon(IconHelper.getIcon(config.fab!.icono)),
              label: Text(config.fab!.label),
            )
          : null,
    );
  }

  Widget _buildFilters() {
    final selectFilters = config.filtros.where((f) => f.tipo == 'select').toList();
    if (selectFilters.isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: selectFilters.map((filtro) {
            return Padding(
              padding: const EdgeInsets.only(right: 8),
              child: DropdownButton<String>(
                hint: Text(filtro.label),
                value: _filtrosActivos[filtro.paramName ?? filtro.id]?.toString(),
                items: [
                  DropdownMenuItem(value: '', child: Text('Todos')),
                  ...filtro.opciones.map((op) => DropdownMenuItem(
                    value: op.value,
                    child: Text(op.label),
                  )),
                ],
                onChanged: (value) => _onFilterChanged(
                  filtro.paramName ?? filtro.id,
                  value,
                ),
              ),
            );
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              IconHelper.getIcon(config.emptyIcon ?? config.icono),
              size: 64,
              color: Colors.red.shade300,
            ),
            const SizedBox(height: 16),
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _loadData,
              icon: const Icon(Icons.refresh),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      );
    }

    // Dashboard layout especial
    if (config.layout == LayoutType.dashboard && _dashboardData != null) {
      return DynamicDashboard(
        data: _dashboardData!,
        config: config,
        items: _items,
        onItemTap: (item) => _onItemTap(item as Map<String, dynamic>),
        onRefresh: _loadData,
      );
    }

    if (_items.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              IconHelper.getIcon(config.emptyIcon ?? config.icono),
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            Text(
              config.emptyMessage ?? 'No hay datos disponibles',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade600),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadData,
      child: _buildList(),
    );
  }

  Widget _buildList() {
    switch (config.layout) {
      case LayoutType.grid:
        return GridView.builder(
          padding: const EdgeInsets.all(16),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 0.85,
          ),
          itemCount: _items.length,
          itemBuilder: (context, index) {
            final item = _items[index] as Map<String, dynamic>;
            return DynamicGridItem(
              item: item,
              config: config,
              onTap: () => _onItemTap(item),
            );
          },
        );

      case LayoutType.list:
        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: _items.length,
          separatorBuilder: (_, __) => const Divider(),
          itemBuilder: (context, index) {
            final item = _items[index] as Map<String, dynamic>;
            return DynamicListTile(
              item: item,
              config: config,
              onTap: () => _onItemTap(item),
              acciones: config.acciones,
              onAction: (action) => _executeAction(action, item),
            );
          },
        );

      case LayoutType.card:
      default:
        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: _items.length,
          itemBuilder: (context, index) {
            final item = _items[index] as Map<String, dynamic>;
            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: DynamicCard(
                item: item,
                config: config,
                onTap: () => _onItemTap(item),
                acciones: config.acciones,
                onAction: (action) => _executeAction(action, item),
              ),
            );
          },
        );
    }
  }

  void _showSearchDialog() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Buscar'),
        content: TextField(
          controller: _searchController,
          decoration: const InputDecoration(
            hintText: 'Escribe para buscar...',
            prefixIcon: Icon(Icons.search),
          ),
          autofocus: true,
          onSubmitted: (value) {
            Navigator.pop(ctx);
            _onSearch(value);
          },
        ),
        actions: [
          TextButton(
            onPressed: () {
              _searchController.clear();
              Navigator.pop(ctx);
              _onSearch('');
            },
            child: const Text('Limpiar'),
          ),
          FilledButton(
            onPressed: () {
              Navigator.pop(ctx);
              _onSearch(_searchController.text);
            },
            child: const Text('Buscar'),
          ),
        ],
      ),
    );
  }
}
