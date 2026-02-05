import 'package:flutter/material.dart';
import '../core/models/business.dart';
import '../core/models/business_filter.dart';
import '../core/services/business_directory_service.dart';
import '../core/services/business_switcher_service.dart';

/// Pantalla para cambiar entre diferentes negocios/comunidades
class SwitchBusinessScreen extends StatefulWidget {
  const SwitchBusinessScreen({Key? key}) : super(key: key);

  @override
  State<SwitchBusinessScreen> createState() => _SwitchBusinessScreenState();
}

class _SwitchBusinessScreenState extends State<SwitchBusinessScreen> {
  final _directoryService = BusinessDirectoryService();
  final _switcherService = BusinessSwitcherService();
  final _searchController = TextEditingController();

  List<Business> _businesses = [];
  Map<String, String> _regions = {};
  Map<String, String> _categories = {};
  BusinessFilter _filter = BusinessFilter.empty();
  bool _isLoading = false;
  String? _error;

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

  /// Carga los datos iniciales
  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      // Cargar regiones y categorías
      final regions = await _directoryService.getAvailableRegions();
      final categories = await _directoryService.getAvailableCategories();

      // Cargar negocios
      final businesses = await _directoryService.getBusinesses(filter: _filter);

      setState(() {
        _regions = regions;
        _categories = categories;
        _businesses = businesses;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = 'Error al cargar negocios: $e';
        _isLoading = false;
      });
    }
  }

  /// Aplica los filtros
  Future<void> _applyFilter() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final businesses = await _directoryService.getBusinesses(filter: _filter);

      setState(() {
        _businesses = businesses;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = 'Error al filtrar: $e';
        _isLoading = false;
      });
    }
  }

  /// Cambia al negocio seleccionado
  Future<void> _switchToBusiness(Business business) async {
    // Mostrar loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => WillPopScope(
        onWillPop: () async => false,
        child: Center(
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const CircularProgressIndicator(),
                  const SizedBox(height: 16),
                  Text('Conectando con ${business.name}...'),
                ],
              ),
            ),
          ),
        ),
      ),
    );

    // Intentar cambiar
    final success = await _switcherService.switchToBusiness(business);

    // Cerrar loading
    if (mounted) {
      Navigator.of(context).pop();
    }

    if (success) {
      // Mostrar mensaje de éxito
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Conectado a ${business.name}'),
            backgroundColor: Colors.green,
          ),
        );

        // Volver a la pantalla anterior y recargar la app
        Navigator.of(context).pop(true); // true indica que se cambió de negocio
      }
    } else {
      // Mostrar error
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Error al conectar con el negocio'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Cambiar de Negocio'),
      ),
      body: Column(
        children: [
          // Barra de búsqueda y filtros
          Container(
            padding: const EdgeInsets.all(16.0),
            color: Colors.grey[100],
            child: Column(
              children: [
                // Campo de búsqueda
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Buscar por nombre...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _searchController.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              _filter = _filter.copyWith(search: '');
                              _applyFilter();
                            },
                          )
                        : null,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8.0),
                    ),
                    filled: true,
                    fillColor: Colors.white,
                  ),
                  onSubmitted: (value) {
                    _filter = _filter.copyWith(search: value);
                    _applyFilter();
                  },
                ),
                const SizedBox(height: 12),

                // Filtros de región y categoría
                Row(
                  children: [
                    // Filtro de región
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        value: _filter.region,
                        decoration: InputDecoration(
                          labelText: 'Región',
                          prefixIcon: const Icon(Icons.location_on),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(8.0),
                          ),
                          filled: true,
                          fillColor: Colors.white,
                        ),
                        items: [
                          const DropdownMenuItem(
                            value: null,
                            child: Text('Todas'),
                          ),
                          ..._regions.entries.map(
                            (e) => DropdownMenuItem(
                              value: e.key,
                              child: Text(e.value),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          _filter = _filter.copyWith(region: value ?? '');
                          _applyFilter();
                        },
                      ),
                    ),
                    const SizedBox(width: 12),

                    // Filtro de categoría
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        value: _filter.category,
                        decoration: InputDecoration(
                          labelText: 'Categoría',
                          prefixIcon: const Icon(Icons.category),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(8.0),
                          ),
                          filled: true,
                          fillColor: Colors.white,
                        ),
                        items: [
                          const DropdownMenuItem(
                            value: null,
                            child: Text('Todas'),
                          ),
                          ..._categories.entries.map(
                            (e) => DropdownMenuItem(
                              value: e.key,
                              child: Text(e.value),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          _filter = _filter.copyWith(category: value ?? '');
                          _applyFilter();
                        },
                      ),
                    ),
                  ],
                ),

                // Botón para limpiar filtros
                if (_filter.hasFilters) ...[
                  const SizedBox(height: 8),
                  TextButton.icon(
                    onPressed: () {
                      setState(() {
                        _filter = BusinessFilter.empty();
                        _searchController.clear();
                      });
                      _applyFilter();
                    },
                    icon: const Icon(Icons.clear_all),
                    label: const Text('Limpiar filtros'),
                  ),
                ],
              ],
            ),
          ),

          // Lista de negocios
          Expanded(
            child: _buildBusinessList(),
          ),
        ],
      ),
    );
  }

  /// Construye la lista de negocios
  Widget _buildBusinessList() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 64, color: Colors.red),
            const SizedBox(height: 16),
            Text(_error!, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loadData,
              child: const Text('Reintentar'),
            ),
          ],
        ),
      );
    }

    if (_businesses.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.store_outlined, size: 64, color: Colors.grey),
            const SizedBox(height: 16),
            const Text(
              'No se encontraron negocios',
              style: TextStyle(fontSize: 18),
            ),
            const SizedBox(height: 8),
            const Text(
              'Intenta cambiar los filtros',
              style: TextStyle(color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView.builder(
        padding: const EdgeInsets.all(16.0),
        itemCount: _businesses.length,
        itemBuilder: (context, index) {
          final business = _businesses[index];
          final isCurrent = _switcherService.currentBusiness?.url == business.url;

          return Card(
            margin: const EdgeInsets.only(bottom: 16.0),
            child: InkWell(
              onTap: isCurrent ? null : () => _switchToBusiness(business),
              borderRadius: BorderRadius.circular(8.0),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        // Logo
                        if (business.logoUrl != null)
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8.0),
                            child: Image.network(
                              business.logoUrl!,
                              width: 48,
                              height: 48,
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) =>
                                  const Icon(Icons.store, size: 48),
                            ),
                          )
                        else
                          const Icon(Icons.store, size: 48),

                        const SizedBox(width: 12),

                        // Nombre y descripción
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      business.name,
                                      style: const TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                                  if (isCurrent)
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 8,
                                        vertical: 4,
                                      ),
                                      decoration: BoxDecoration(
                                        color: Colors.green,
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: const Text(
                                        'ACTUAL',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontSize: 10,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                              if (business.description.isNotEmpty)
                                Text(
                                  business.description,
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Colors.grey[600],
                                  ),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Región y categoría
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        _buildChip(
                          Icons.location_on,
                          business.regionName,
                          Colors.blue,
                        ),
                        _buildChip(
                          Icons.category,
                          business.categoryName,
                          Colors.purple,
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),

                    // Módulos disponibles
                    Text(
                      business.modulesDescription,
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  /// Construye un chip con icono y texto
  Widget _buildChip(IconData icon, String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(fontSize: 12, color: color),
          ),
        ],
      ),
    );
  }
}
