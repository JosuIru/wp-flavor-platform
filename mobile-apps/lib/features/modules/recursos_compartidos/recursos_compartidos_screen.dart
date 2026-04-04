import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_share_helper.dart';
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/widgets/flavor_detail_widgets.dart';
import '../../../core/widgets/flavor_meta_chip.dart';
import '../../../core/widgets/flavor_search_field.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_webview_page.dart';

part 'recursos_compartidos_screen_parts.dart';

class RecursosCompartidosScreen extends ConsumerStatefulWidget {
  const RecursosCompartidosScreen({super.key});

  @override
  ConsumerState<RecursosCompartidosScreen> createState() =>
      _RecursosCompartidosScreenState();
}

class _RecursosCompartidosScreenState
    extends ConsumerState<RecursosCompartidosScreen> {
  static const _favoritesKey = 'shared_resources_favorites';
  static const _recentKey = 'shared_resources_recent';

  late Future<ApiResponse<Map<String, dynamic>>> _future;
  String _selectedType = 'all';
  String _searchQuery = '';
  bool _showFavoritesOnly = false;
  bool _showRecentOnly = false;
  Set<String> _favoriteIds = <String>{};
  List<String> _recentIds = <String>[];

  @override
  void initState() {
    super.initState();
    _loadLocalState();
    _future = _loadResources();
  }

  Future<void> _loadLocalState() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _favoriteIds =
          prefs.getStringList(_favoritesKey)?.toSet() ?? <String>{};
      _recentIds = prefs.getStringList(_recentKey) ?? <String>[];
    });
  }

  Future<void> _persistLocalState() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_favoritesKey, _favoriteIds.toList()..sort());
    await prefs.setStringList(_recentKey, _recentIds);
  }

  Future<ApiResponse<Map<String, dynamic>>> _loadResources() {
    return ref.read(apiClientProvider).getClientSharedResources(
          type: _selectedType == 'all' ? null : _selectedType,
        );
  }

  Future<void> _refresh() async {
    setState(() {
      _future = _loadResources();
    });
    await _future;
  }

  Future<void> _changeType(String type) async {
    if (_selectedType == type) {
      return;
    }

    setState(() {
      _selectedType = type;
      _future = _loadResources();
    });
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Recursos compartidos'),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return FlavorErrorState(
              message: 'No se pudieron cargar los recursos compartidos.',
              onRetry: _refresh,
              icon: Icons.cloud_off_outlined,
            );
          }

          final data = response.data!;
          final items = (data['items'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          final filteredItems = _filterItems(items);
          final availableTypes = (data['available_types'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          return Column(
            children: [
              _FiltersBar(
                selectedType: _selectedType,
                availableTypes: availableTypes,
                onTypeSelected: _changeType,
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 4),
                child: Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    FilterChip(
                      label: const Text('Guardados'),
                      selected: _showFavoritesOnly,
                      onSelected: (value) {
                        setState(() {
                          _showFavoritesOnly = value;
                          if (value) _showRecentOnly = false;
                        });
                      },
                    ),
                    FilterChip(
                      label: const Text('Recientes'),
                      selected: _showRecentOnly,
                      onSelected: (value) {
                        setState(() {
                          _showRecentOnly = value;
                          if (value) _showFavoritesOnly = false;
                        });
                      },
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
                child: FlavorSearchField(
                  hintText: 'Buscar recurso, origen o tipo',
                  value: _searchQuery,
                  onChanged: (value) {
                    setState(() {
                      _searchQuery = value.trim();
                    });
                  },
                ),
              ),
              Expanded(
                child: filteredItems.isEmpty
                    ? RefreshIndicator(
                        onRefresh: _refresh,
                        child: ListView(
                          physics: const AlwaysScrollableScrollPhysics(),
                          children: const [
                            SizedBox(height: 120),
                            _EmptyState(),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _refresh,
                        child: ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                          itemCount: filteredItems.length,
                          separatorBuilder: (_, __) =>
                              const SizedBox(height: 12),
                          itemBuilder: (context, index) {
                            return _ResourceCard(
                              item: filteredItems[index],
                              onOpen: _openResourceDetail,
                              onPreview: _openResourceInApp,
                              onCopy: _copyResourceLink,
                              onToggleFavorite: _toggleFavorite,
                              isFavorite:
                                  _favoriteIds.contains(_itemId(filteredItems[index])),
                            );
                          },
                        ),
                      ),
              ),
            ],
          );
        },
      ),
    );
  }

  bool _canOpenInApp(Map<String, dynamic> item) {
    final rawUrl = item['url']?.toString() ?? '';
    final uri = Uri.tryParse(rawUrl);
    if (uri == null || !(uri.scheme == 'http' || uri.scheme == 'https')) {
      return false;
    }

    final path = uri.path.toLowerCase();
    return path.endsWith('.pdf') ||
        path.endsWith('.html') ||
        path.endsWith('.htm') ||
        path.endsWith('.txt') ||
        !path.contains('.');
  }

  List<Map<String, dynamic>> _filterItems(List<Map<String, dynamic>> items) {
    var result = List<Map<String, dynamic>>.from(items);

    if (_showFavoritesOnly) {
      result = result
          .where((item) => _favoriteIds.contains(_itemId(item)))
          .toList();
    } else if (_showRecentOnly) {
      final indexed = {
        for (final item in result) _itemId(item): item,
      };
      result = _recentIds
          .map((id) => indexed[id])
          .whereType<Map<String, dynamic>>()
          .toList();
    }

    if (_searchQuery.isEmpty) {
      return result;
    }

    final query = _searchQuery.toLowerCase();
    return result.where((item) {
      final haystack = [
        item['title'],
        item['summary'],
        item['origin'],
        item['type'],
        item['raw_type'],
        item['source'],
      ].whereType<Object>().map((e) => e.toString().toLowerCase()).join(' ');

      return haystack.contains(query);
    }).toList();
  }

  String _itemId(Map<String, dynamic> item) =>
      item['id']?.toString() ??
      item['resource_id']?.toString() ??
      item['url']?.toString() ??
      DateTime.now().microsecondsSinceEpoch.toString();
}

class _FiltersBar extends StatelessWidget {
  final String selectedType;
  final List<Map<String, dynamic>> availableTypes;
  final ValueChanged<String> onTypeSelected;

  const _FiltersBar({
    required this.selectedType,
    required this.availableTypes,
    required this.onTypeSelected,
  });

  @override
  Widget build(BuildContext context) {
    if (availableTypes.isEmpty) {
      return const SizedBox.shrink();
    }

    return SizedBox(
      height: 56,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 6),
        scrollDirection: Axis.horizontal,
        itemCount: availableTypes.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final type = availableTypes[index];
          final id = type['id']?.toString() ?? 'all';
          final label = type['label']?.toString() ?? id;
          final count = (type['count'] as num?)?.toInt() ?? 0;

          return ChoiceChip(
            label: Text('$label ($count)'),
            selected: selectedType == id,
            onSelected: (_) => onTypeSelected(id),
          );
        },
      ),
    );
  }
}

class _ResourceCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final ValueChanged<Map<String, dynamic>> onOpen;
  final ValueChanged<Map<String, dynamic>> onPreview;
  final ValueChanged<Map<String, dynamic>> onCopy;
  final ValueChanged<Map<String, dynamic>> onToggleFavorite;
  final bool isFavorite;

  const _ResourceCard({
    required this.item,
    required this.onOpen,
    required this.onPreview,
    required this.onCopy,
    required this.onToggleFavorite,
    required this.isFavorite,
  });

  @override
  Widget build(BuildContext context) {
    final title = item['title']?.toString() ?? 'Recurso';
    final summary = item['summary']?.toString() ?? '';
    final origin = item['origin']?.toString() ?? '';
    final type = item['type']?.toString() ?? 'general';
    final accent = _parseColor(item['accent']?.toString());
    final icon = _iconForType(item['icon']?.toString(), type);

    return Card(
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => onOpen(item),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: accent.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(icon, color: accent),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          style: Theme.of(context)
                              .textTheme
                              .titleMedium
                              ?.copyWith(fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 6),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            FlavorMetaChip(
                              icon: Icons.category_outlined,
                              label: _labelForType(type),
                            ),
                            if (origin.isNotEmpty)
                              FlavorMetaChip(
                                icon: Icons.hub_outlined,
                                label: origin,
                              ),
                            if ((item['date']?.toString() ?? '').isNotEmpty)
                              FlavorMetaChip(
                                icon: Icons.schedule_outlined,
                                label: _formatDate(item['date']?.toString()),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'copy') {
                        onCopy(item);
                        return;
                      }
                      if (value == 'favorite') {
                        onToggleFavorite(item);
                        return;
                      }
                      if (value == 'preview') {
                        onPreview(item);
                        return;
                      }
                      onOpen(item);
                    },
                    itemBuilder: (context) => [
                      const PopupMenuItem<String>(
                        value: 'open',
                        child: Text('Abrir detalle'),
                      ),
                      const PopupMenuItem<String>(
                        value: 'preview',
                        child: Text('Ver en app'),
                      ),
                      const PopupMenuItem<String>(
                        value: 'copy',
                        child: Text('Copiar enlace'),
                      ),
                      PopupMenuItem<String>(
                        value: 'favorite',
                        child: Text(
                          isFavorite ? 'Quitar de guardados' : 'Guardar',
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              if (summary.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  summary,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    OutlinedButton.icon(
                      onPressed: () => onPreview(item),
                      icon: const Icon(Icons.visibility_outlined),
                      label: const Text('Ver'),
                    ),
                    TextButton.icon(
                      onPressed: () => onCopy(item),
                      icon: const Icon(Icons.copy_outlined),
                      label: const Text('Copiar'),
                    ),
                    TextButton.icon(
                      onPressed: () => onToggleFavorite(item),
                      icon: Icon(
                        isFavorite ? Icons.bookmark : Icons.bookmark_border,
                      ),
                      label: Text(isFavorite ? 'Guardado' : 'Guardar'),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  static Color _parseColor(String? rawColor) {
    final normalized = rawColor?.replaceAll('#', '').trim() ?? '';
    if (normalized.length != 6) {
      return Colors.indigo;
    }
    return Color(int.parse('FF$normalized', radix: 16));
  }

  static IconData _iconForType(String? rawIcon, String type) {
    switch (rawIcon) {
      case 'event':
        return Icons.event_outlined;
      case 'local_offer':
        return Icons.local_offer_outlined;
      case 'handyman':
        return Icons.handyman_outlined;
      case 'inventory_2':
        return Icons.inventory_2_outlined;
      case 'hub':
        return Icons.hub_outlined;
    }

    switch (type) {
      case 'eventos':
        return Icons.event_outlined;
      case 'ofertas':
        return Icons.local_offer_outlined;
      case 'servicios':
        return Icons.handyman_outlined;
      case 'recursos':
        return Icons.inventory_2_outlined;
      default:
        return Icons.hub_outlined;
    }
  }

  static String _labelForType(String type) {
    switch (type) {
      case 'eventos':
        return 'Eventos';
      case 'ofertas':
        return 'Ofertas';
      case 'servicios':
        return 'Servicios';
      case 'recursos':
        return 'Recursos';
      default:
        return 'General';
    }
  }

  static String _formatDate(String? rawDate) {
    final parsed = rawDate == null ? null : DateTime.tryParse(rawDate);
    if (parsed == null) {
      return rawDate ?? '';
    }

    final day = parsed.day.toString().padLeft(2, '0');
    final month = parsed.month.toString().padLeft(2, '0');
    final year = parsed.year.toString();
    return '$day/$month/$year';
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return const FlavorEmptyState(
      icon: Icons.hub_outlined,
      title: 'No hay recursos compartidos disponibles.',
      message:
          'Cuando otras comunidades publiquen eventos, ofertas o recursos, aparecerán aquí.',
    );
  }
}
