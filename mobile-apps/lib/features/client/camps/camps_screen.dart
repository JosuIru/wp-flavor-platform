import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/models/models.dart';
import 'camp_detail_screen.dart';

/// Pantalla de lista de campamentos para clientes
class CampsScreen extends ConsumerStatefulWidget {
  const CampsScreen({super.key});

  @override
  ConsumerState<CampsScreen> createState() => _CampsScreenState();
}

class _CampsScreenState extends ConsumerState<CampsScreen> {
  List<Camp> _camps = [];
  List<Camp> _filteredCamps = [];
  bool _isLoading = false;
  String? _error;

  String? _selectedCategory;
  String? _selectedAge;
  String? _selectedLanguage;
  String? _selectedStatus;
  String _searchQuery = '';

  List<CampTerm> _categories = [];
  List<CampTerm> _ages = [];
  List<CampTerm> _languages = [];

  @override
  void initState() {
    super.initState();
    _loadCamps();
  }

  Future<void> _loadCamps() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getCamps();

      if (response.success && response.data != null) {
        final camps = (response.data!['camps'] as List?)
                ?.map((c) => Camp.fromJson(c))
                .toList() ??
            [];

        // Extraer términos únicos para filtros
        _extractFilterTerms(camps);

        setState(() {
          _camps = camps;
          _filteredCamps = camps;
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar campamentos';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Error de conexión: $e';
        _isLoading = false;
      });
    }
  }

  void _extractFilterTerms(List<Camp> camps) {
    final categories = <String, CampTerm>{};
    final ages = <String, CampTerm>{};
    final languages = <String, CampTerm>{};

    for (final camp in camps) {
      for (final cat in camp.categories) {
        categories[cat.slug] = cat;
      }
      for (final age in camp.ages) {
        ages[age.slug] = age;
      }
      for (final lang in camp.languages) {
        languages[lang.slug] = lang;
      }
    }

    _categories = categories.values.toList();
    _ages = ages.values.toList();
    _languages = languages.values.toList();
  }

  void _applyFilters() {
    var filtered = _camps;

    // Filtro por categoría
    if (_selectedCategory != null) {
      filtered = filtered
          .where((c) => c.categories.any((cat) => cat.slug == _selectedCategory))
          .toList();
    }

    // Filtro por edad
    if (_selectedAge != null) {
      filtered = filtered
          .where((c) => c.ages.any((age) => age.slug == _selectedAge))
          .toList();
    }

    // Filtro por idioma
    if (_selectedLanguage != null) {
      filtered = filtered
          .where((c) => c.languages.any((lang) => lang.slug == _selectedLanguage))
          .toList();
    }

    // Filtro por estado
    if (_selectedStatus != null) {
      if (_selectedStatus == 'open') {
        filtered = filtered.where((c) => c.inscriptionOpen).toList();
      } else if (_selectedStatus == 'closed') {
        filtered = filtered.where((c) => !c.inscriptionOpen).toList();
      }
    }

    // Búsqueda por texto
    if (_searchQuery.isNotEmpty) {
      final query = _searchQuery.toLowerCase();
      filtered = filtered.where((c) {
        return c.title.toLowerCase().contains(query) ||
            c.excerpt.toLowerCase().contains(query) ||
            c.categoriesText.toLowerCase().contains(query);
      }).toList();
    }

    setState(() {
      _filteredCamps = filtered;
    });
  }

  void _clearFilters() {
    setState(() {
      _selectedCategory = null;
      _selectedAge = null;
      _selectedLanguage = null;
      _selectedStatus = null;
      _searchQuery = '';
      _filteredCamps = _camps;
    });
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final hasActiveFilters = _selectedCategory != null ||
        _selectedAge != null ||
        _selectedLanguage != null ||
        _selectedStatus != null ||
        _searchQuery.isNotEmpty;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Campamentos'),
        actions: [
          IconButton(
            onPressed: _loadCamps,
            icon: const Icon(Icons.refresh),
            tooltip: 'Actualizar',
          ),
          IconButton(
            onPressed: () => _showFiltersBottomSheet(context),
            icon: Badge(
              isLabelVisible: hasActiveFilters,
              child: const Icon(Icons.filter_list),
            ),
            tooltip: 'Filtros',
          ),
        ],
      ),
      body: Column(
        children: [
          // Buscador
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Buscar campamentos...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchQuery.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          setState(() {
                            _searchQuery = '';
                          });
                          _applyFilters();
                        },
                      )
                    : null,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
              ),
              onChanged: (value) {
                setState(() {
                  _searchQuery = value;
                });
                _applyFilters();
              },
            ),
          ),

          // Chips de filtros activos
          if (hasActiveFilters)
            Container(
              height: 50,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: ListView(
                scrollDirection: Axis.horizontal,
                children: [
                  if (_selectedCategory != null)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: Chip(
                        label: Text(_categories
                            .firstWhere((c) => c.slug == _selectedCategory)
                            .name),
                        onDeleted: () {
                          setState(() => _selectedCategory = null);
                          _applyFilters();
                        },
                      ),
                    ),
                  if (_selectedAge != null)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: Chip(
                        label: Text(
                            _ages.firstWhere((a) => a.slug == _selectedAge).name),
                        onDeleted: () {
                          setState(() => _selectedAge = null);
                          _applyFilters();
                        },
                      ),
                    ),
                  if (_selectedLanguage != null)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: Chip(
                        label: Text(_languages
                            .firstWhere((l) => l.slug == _selectedLanguage)
                            .name),
                        onDeleted: () {
                          setState(() => _selectedLanguage = null);
                          _applyFilters();
                        },
                      ),
                    ),
                  if (_selectedStatus != null)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: Chip(
                        label: Text(
                            _selectedStatus == 'open' ? 'Abierto' : 'Cerrado'),
                        onDeleted: () {
                          setState(() => _selectedStatus = null);
                          _applyFilters();
                        },
                      ),
                    ),
                  TextButton.icon(
                    onPressed: _clearFilters,
                    icon: const Icon(Icons.clear_all),
                    label: const Text('Limpiar'),
                  ),
                ],
              ),
            ),

          // Lista de campamentos
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.error_outline,
                              size: 48,
                              color: colorScheme.error,
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _error!,
                              style: TextStyle(color: colorScheme.error),
                              textAlign: TextAlign.center,
                            ),
                            const SizedBox(height: 16),
                            FilledButton.icon(
                              onPressed: _loadCamps,
                              icon: const Icon(Icons.refresh),
                              label: const Text('Reintentar'),
                            ),
                          ],
                        ),
                      )
                    : _filteredCamps.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  Icons.search_off,
                                  size: 48,
                                  color: colorScheme.outline,
                                ),
                                const SizedBox(height: 16),
                                Text(
                                  hasActiveFilters
                                      ? 'No se encontraron campamentos\ncon los filtros aplicados'
                                      : 'No hay campamentos disponibles',
                                  style: TextStyle(color: colorScheme.outline),
                                  textAlign: TextAlign.center,
                                ),
                                if (hasActiveFilters) ...[
                                  const SizedBox(height: 16),
                                  TextButton.icon(
                                    onPressed: _clearFilters,
                                    icon: const Icon(Icons.clear_all),
                                    label: const Text('Limpiar filtros'),
                                  ),
                                ],
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _loadCamps,
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: _filteredCamps.length,
                              itemBuilder: (context, index) {
                                final camp = _filteredCamps[index];
                                return _CampCard(
                                  camp: camp,
                                  onTap: () => _navigateToDetail(camp),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }

  void _showFiltersBottomSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (context) => _FiltersBottomSheet(
        categories: _categories,
        ages: _ages,
        languages: _languages,
        selectedCategory: _selectedCategory,
        selectedAge: _selectedAge,
        selectedLanguage: _selectedLanguage,
        selectedStatus: _selectedStatus,
        onApply: (category, age, language, status) {
          setState(() {
            _selectedCategory = category;
            _selectedAge = age;
            _selectedLanguage = language;
            _selectedStatus = status;
          });
          _applyFilters();
          Navigator.pop(context);
        },
      ),
    );
  }

  void _navigateToDetail(Camp camp) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CampDetailScreen(campId: camp.id),
      ),
    );
  }
}

/// Card de campamento
class _CampCard extends StatelessWidget {
  final Camp camp;
  final VoidCallback onTap;

  const _CampCard({
    required this.camp,
    required this.onTap,
  });

  Color _getStatusColor() {
    if (camp.isClosed) return Colors.red;
    if (camp.isFull) return Colors.orange;
    return Colors.green;
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Imagen
            if (camp.featuredImage.isNotEmpty)
              AspectRatio(
                aspectRatio: 16 / 9,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    Image.network(
                      camp.featuredImage,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) => Container(
                        color: colorScheme.surfaceContainerHighest,
                        child: const Icon(Icons.cabin, size: 48),
                      ),
                    ),
                    // Badge de estado
                    Positioned(
                      top: 12,
                      right: 12,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: _getStatusColor(),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          camp.statusText.toUpperCase(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                    // Label especial si existe
                    if (camp.label != null && camp.label!.isNotEmpty)
                      Positioned(
                        top: 12,
                        left: 12,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.purple,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            camp.label!.toUpperCase(),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),

            // Contenido
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Título
                  Text(
                    camp.title,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 8),

                  // Categorías
                  if (camp.categories.isNotEmpty)
                    Wrap(
                      spacing: 8,
                      children: camp.categories
                          .map((cat) => Chip(
                                label: Text(cat.name),
                                labelStyle: const TextStyle(fontSize: 12),
                                visualDensity: VisualDensity.compact,
                              ))
                          .toList(),
                    ),

                  const SizedBox(height: 12),

                  // Info
                  Row(
                    children: [
                      Icon(
                        Icons.child_care,
                        size: 16,
                        color: colorScheme.outline,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        camp.agesText,
                        style: TextStyle(
                          color: colorScheme.onSurfaceVariant,
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Icon(
                        Icons.access_time,
                        size: 16,
                        color: colorScheme.outline,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        camp.duration,
                        style: TextStyle(
                          color: colorScheme.onSurfaceVariant,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 8),

                  // Idiomas
                  if (camp.languages.isNotEmpty)
                    Row(
                      children: [
                        Icon(
                          Icons.language,
                          size: 16,
                          color: colorScheme.outline,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          camp.languagesText,
                          style: TextStyle(
                            color: colorScheme.onSurfaceVariant,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),

                  const SizedBox(height: 12),

                  // Precio
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Precio total',
                            style: TextStyle(
                              color: colorScheme.outline,
                              fontSize: 12,
                            ),
                          ),
                          Text(
                            camp.formattedPriceTotal,
                            style: Theme.of(context)
                                .textTheme
                                .headlineSmall
                                ?.copyWith(
                                  fontWeight: FontWeight.bold,
                                  color: colorScheme.primary,
                                ),
                          ),
                        ],
                      ),
                      FilledButton(
                        onPressed: camp.inscriptionOpen ? onTap : null,
                        child: Text(
                          camp.inscriptionOpen ? 'Ver más' : 'Cerrado',
                        ),
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
  }
}

/// Bottom sheet de filtros
class _FiltersBottomSheet extends StatefulWidget {
  final List<CampTerm> categories;
  final List<CampTerm> ages;
  final List<CampTerm> languages;
  final String? selectedCategory;
  final String? selectedAge;
  final String? selectedLanguage;
  final String? selectedStatus;
  final Function(String?, String?, String?, String?) onApply;

  const _FiltersBottomSheet({
    required this.categories,
    required this.ages,
    required this.languages,
    required this.selectedCategory,
    required this.selectedAge,
    required this.selectedLanguage,
    required this.selectedStatus,
    required this.onApply,
  });

  @override
  State<_FiltersBottomSheet> createState() => _FiltersBottomSheetState();
}

class _FiltersBottomSheetState extends State<_FiltersBottomSheet> {
  late String? _category;
  late String? _age;
  late String? _language;
  late String? _status;

  @override
  void initState() {
    super.initState();
    _category = widget.selectedCategory;
    _age = widget.selectedAge;
    _language = widget.selectedLanguage;
    _status = widget.selectedStatus;
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Filtros',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              TextButton(
                onPressed: () {
                  setState(() {
                    _category = null;
                    _age = null;
                    _language = null;
                    _status = null;
                  });
                },
                child: const Text('Limpiar'),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Categoría
          if (widget.categories.isNotEmpty) ...[
            Text(
              'Categoría',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            DropdownButtonFormField<String?>(
              value: _category,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              items: [
                const DropdownMenuItem<String?>(
                  value: null,
                  child: Text('Todas'),
                ),
                ...widget.categories.map((cat) => DropdownMenuItem<String?>(
                      value: cat.slug,
                      child: Text(cat.name),
                    )),
              ],
              onChanged: (value) => setState(() => _category = value),
            ),
            const SizedBox(height: 16),
          ],

          // Edad
          if (widget.ages.isNotEmpty) ...[
            Text(
              'Edad',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            DropdownButtonFormField<String?>(
              value: _age,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              items: [
                const DropdownMenuItem<String?>(
                  value: null,
                  child: Text('Todas'),
                ),
                ...widget.ages.map((age) => DropdownMenuItem<String?>(
                      value: age.slug,
                      child: Text(age.name),
                    )),
              ],
              onChanged: (value) => setState(() => _age = value),
            ),
            const SizedBox(height: 16),
          ],

          // Idioma
          if (widget.languages.isNotEmpty) ...[
            Text(
              'Idioma',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            DropdownButtonFormField<String?>(
              value: _language,
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              items: [
                const DropdownMenuItem<String?>(
                  value: null,
                  child: Text('Todos'),
                ),
                ...widget.languages.map((lang) => DropdownMenuItem<String?>(
                      value: lang.slug,
                      child: Text(lang.name),
                    )),
              ],
              onChanged: (value) => setState(() => _language = value),
            ),
            const SizedBox(height: 16),
          ],

          // Estado
          Text(
            'Estado',
            style: Theme.of(context).textTheme.titleSmall,
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<String?>(
            value: _status,
            decoration: const InputDecoration(
              border: OutlineInputBorder(),
              contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            ),
            items: const [
              DropdownMenuItem<String?>(
                value: null,
                child: Text('Todos'),
              ),
              DropdownMenuItem<String?>(
                value: 'open',
                child: Text('Abierto'),
              ),
              DropdownMenuItem<String?>(
                value: 'closed',
                child: Text('Cerrado'),
              ),
            ],
            onChanged: (value) => setState(() => _status = value),
          ),
          const SizedBox(height: 24),

          // Botón aplicar
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: () => widget.onApply(_category, _age, _language, _status),
              child: const Text('Aplicar filtros'),
            ),
          ),
        ],
      ),
    );
  }
}
