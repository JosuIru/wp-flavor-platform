import 'package:flutter/services.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_mutation.dart';
import '../../../core/utils/map_launch_helper.dart';
import '../../../core/widgets/flavor_confirm_dialog.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
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
    _searchController.text = _busqueda;
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

      final response = await api.get(
        config.endpoint,
        queryParameters: _buildQueryParams(),
      );

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

  Map<String, dynamic> _buildQueryParams() {
    final queryParams = <String, dynamic>{};
    _filtrosActivos.forEach((key, value) {
      if (value != null && value.toString().isNotEmpty) {
        queryParams[key] = value;
      }
    });
    if (_busqueda.isNotEmpty) {
      queryParams['busqueda'] = _busqueda;
    }
    return queryParams;
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
      if (value == null || value.toString().isEmpty) {
        _filtrosActivos.remove(key);
      } else {
        _filtrosActivos[key] = value;
      }
    });
    _loadData();
  }

  void _onSearch(String value) {
    setState(() {
      _busqueda = value.trim();
    });
    _searchController.text = _busqueda;
    _loadData();
  }

  void _clearFilters() {
    setState(() {
      _filtrosActivos = {};
      _busqueda = '';
      _searchController.clear();
    });
    _loadData();
  }

  bool get _hasActiveFilters => _filtrosActivos.isNotEmpty || _busqueda.isNotEmpty;

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
          final confirmed = await FlavorConfirmDialog.show(
            context,
            title: action.label,
            message: '¿Estás seguro?',
          );
          if (confirmed != true) return;
        }

        if (action.endpoint != null) {
          if (!mounted) return;
          final endpoint = action.endpoint!.replaceAll('{id}', item['id'].toString());
          await FlavorMutation.runApiResponse(
            context,
            request: () => ref.read(apiClientProvider).post(endpoint, data: {}),
            successMessage: '${action.label} completado',
            fallbackErrorMessage: 'No se pudo completar la acción.',
            onSuccess: _loadData,
          );
        }
        break;
      case 'call':
        final telefono = item['telefono'] ?? item['phone'] ?? '';
        if (telefono.isNotEmpty) {
          await _launchUrlString(
            'tel:$telefono',
            errorMessage: 'No se pudo abrir el teléfono.',
          );
        } else if (mounted) {
          FlavorSnackbar.showInfo(context, 'Este elemento no tiene teléfono.');
        }
        break;
      case 'map':
        await _openMapForItem(item);
        break;
      case 'share':
        await _shareItem(item);
        break;
    }
  }

  Future<void> _shareItem(Map<String, dynamic> item) async {
    final text = _resolveShareText(item);
    if (text.isEmpty) {
      if (mounted) {
        FlavorSnackbar.showInfo(context, 'No hay contenido para compartir.');
      }
      return;
    }

    await Clipboard.setData(ClipboardData(text: text));
    if (mounted) {
      FlavorSnackbar.showSuccess(context, 'Contenido copiado al portapapeles.');
    }
  }

  String _resolveShareText(Map<String, dynamic> item) {
    final parts = <String>[];

    final title = _extractText(item, const ['titulo', 'nombre', 'title']);
    final description = _extractText(item, const ['descripcion', 'excerpt', 'resumen']);
    final url = _extractText(item, const ['url', 'link', 'permalink']);

    if (title != null) parts.add(title);
    if (description != null) parts.add(description);
    if (url != null) parts.add(url);

    return parts.join('\n');
  }

  Future<void> _openMapForItem(Map<String, dynamic> item) async {
    final lat = _extractCoordinate(item, const ['lat', 'latitude', 'latitude_value']);
    final lng = _extractCoordinate(item, const ['lng', 'lon', 'longitud', 'longitude']);
    final address = _extractText(item, const ['direccion', 'address', 'ubicacion']);

    if (lat != null && lng != null) {
      final uri = MapLaunchHelper.buildConfiguredMapUri(
        lat,
        lng,
        query: address,
      );
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication) && mounted) {
        FlavorSnackbar.showError(context, 'No se pudo abrir el mapa.');
      }
      return;
    }

    if (address != null) {
      final encoded = Uri.encodeComponent(address);
      final uri = MapLaunchHelper.provider.contains('google')
          ? Uri.parse('https://www.google.com/maps/search/?api=1&query=$encoded')
          : Uri.parse('https://www.openstreetmap.org/search?query=$encoded');
      if (!await launchUrl(uri, mode: LaunchMode.externalApplication) && mounted) {
        FlavorSnackbar.showError(context, 'No se pudo abrir el mapa.');
      }
      return;
    }

    if (mounted) {
      FlavorSnackbar.showInfo(context, 'Este elemento no tiene ubicación.');
    }
  }

  double? _extractCoordinate(Map<String, dynamic> item, List<String> keys) {
    for (final key in keys) {
      final value = item[key];
      if (value is num) return value.toDouble();
      if (value is String) {
        final parsed = double.tryParse(value.replaceAll(',', '.'));
        if (parsed != null) return parsed;
      }
    }
    return null;
  }

  String? _extractText(Map<String, dynamic> item, List<String> keys) {
    for (final key in keys) {
      final value = item[key]?.toString().trim();
      if (value != null && value.isNotEmpty) return value;
    }
    return null;
  }

  Future<void> _launchUrlString(
    String url, {
    required String errorMessage,
  }) async {
    final uri = Uri.tryParse(url);
    if (uri == null || !await launchUrl(uri)) {
      if (mounted) {
        FlavorSnackbar.showError(context, errorMessage);
      }
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
          if (_hasActiveFilters)
            IconButton(
              icon: const Icon(Icons.filter_alt_off_outlined),
              tooltip: 'Limpiar filtros',
              onPressed: _clearFilters,
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
          if (_hasActiveFilters) _buildActiveFiltersBar(),

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
                  const DropdownMenuItem(value: '', child: Text('Todos')),
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

  Widget _buildActiveFiltersBar() {
    final chips = <Widget>[];

    if (_busqueda.isNotEmpty) {
      chips.add(
        InputChip(
          label: Text('Buscar: $_busqueda'),
          onDeleted: () => _onSearch(''),
        ),
      );
    }

    for (final entry in _filtrosActivos.entries) {
      chips.add(
        InputChip(
          label: Text('${_formatFilterLabel(entry.key)}: ${entry.value}'),
          onDeleted: () => _onFilterChanged(entry.key, ''),
        ),
      );
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        children: chips,
      ),
    );
  }

  String _formatFilterLabel(String key) {
    for (final filter in config.filtros) {
      if ((filter.paramName ?? filter.id) == key) {
        return filter.label;
      }
    }
    return key;
  }

  Widget _buildBody() {
    if (_loading) {
      return const FlavorLoadingState();
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
