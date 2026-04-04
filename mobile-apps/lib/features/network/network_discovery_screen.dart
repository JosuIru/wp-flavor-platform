import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import '../../core/config/server_config.dart';
import '../../core/providers/providers.dart';
import '../../core/widgets/flavor_snackbar.dart';
import '../../core/widgets/flavor_state_widgets.dart';

/// Pantalla de descubrimiento de comunidades en la red federada
///
/// Permite explorar comunidades conocidas por el servidor actual
/// sin depender de un punto central. Modelo P2P.
class NetworkDiscoveryScreen extends ConsumerStatefulWidget {
  const NetworkDiscoveryScreen({super.key});

  @override
  ConsumerState<NetworkDiscoveryScreen> createState() => _NetworkDiscoveryScreenState();
}

class _NetworkDiscoveryScreenState extends ConsumerState<NetworkDiscoveryScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final _searchController = TextEditingController();

  List<Map<String, dynamic>> _comunidades = [];
  List<Map<String, dynamic>> _cercanas = [];
  bool _isLoading = true;
  bool _isLoadingNearby = false;
  String? _error;
  Position? _ubicacion;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _cargarDirectorio();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _cargarDirectorio({String? busqueda}) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getNetworkDirectory(
        busqueda: busqueda,
        limite: 50,
      );

      if (response.success && response.data != null) {
        final nodes = response.data!['nodes'] as List? ??
                      response.data!['directory'] as List? ?? [];
        setState(() {
          _comunidades = nodes.whereType<Map<String, dynamic>>().toList();
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar directorio';
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

  Future<void> _cargarCercanas() async {
    // Obtener ubicación
    if (_ubicacion == null) {
      setState(() => _isLoadingNearby = true);

      try {
        final permission = await Geolocator.checkPermission();
        if (permission == LocationPermission.denied) {
          final requested = await Geolocator.requestPermission();
          if (requested == LocationPermission.denied ||
              requested == LocationPermission.deniedForever) {
            setState(() {
              _isLoadingNearby = false;
              _error = 'Se necesita permiso de ubicación';
            });
            return;
          }
        }

        _ubicacion = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.medium,
        );
      } catch (e) {
        setState(() {
          _isLoadingNearby = false;
          _error = 'No se pudo obtener ubicación';
        });
        return;
      }
    }

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.getNearbyNodes(
        lat: _ubicacion!.latitude,
        lng: _ubicacion!.longitude,
        radioKm: 100,
        limite: 20,
      );

      if (response.success && response.data != null) {
        final nodes = response.data!['nodes'] as List? ?? [];
        setState(() {
          _cercanas = nodes.whereType<Map<String, dynamic>>().toList();
          _isLoadingNearby = false;
        });
      } else {
        setState(() {
          _isLoadingNearby = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoadingNearby = false;
      });
    }
  }

  Future<void> _agregarComunidad(Map<String, dynamic> comunidad) async {
    final url = comunidad['url']?.toString() ??
                comunidad['site_url']?.toString() ?? '';
    final nombre = comunidad['name']?.toString() ??
                   comunidad['nombre']?.toString() ?? 'Comunidad';

    if (url.isEmpty) {
      FlavorSnackbar.showError(context, 'Esta comunidad no tiene URL configurada');
      return;
    }

    try {
      await ServerConfig.setCurrentBusiness(
        serverUrl: url,
        name: nombre,
        type: 'community',
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Añadida: $nombre'),
            backgroundColor: Colors.green,
            action: SnackBarAction(
              label: 'Conectar',
              textColor: Colors.white,
              onPressed: () {
                // Reiniciar la app con el nuevo servidor
                Navigator.of(context).popUntil((route) => route.isFirst);
              },
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error al añadir: $e');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Explorar Comunidades'),
        bottom: TabBar(
          controller: _tabController,
          onTap: (index) {
            if (index == 1 && _cercanas.isEmpty && !_isLoadingNearby) {
              _cargarCercanas();
            }
          },
          tabs: const [
            Tab(icon: Icon(Icons.public), text: 'Directorio'),
            Tab(icon: Icon(Icons.near_me), text: 'Cercanas'),
            Tab(icon: Icon(Icons.history), text: 'Guardadas'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildDirectorioTab(theme),
          _buildCercanasTab(theme),
          _buildGuardadasTab(theme),
        ],
      ),
    );
  }

  Widget _buildDirectorioTab(ThemeData theme) {
    return Column(
      children: [
        // Barra de búsqueda
        Padding(
          padding: const EdgeInsets.all(16),
          child: TextField(
            controller: _searchController,
            decoration: InputDecoration(
              hintText: 'Buscar comunidades...',
              prefixIcon: const Icon(Icons.search),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              suffixIcon: _searchController.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () {
                        _searchController.clear();
                        _cargarDirectorio();
                      },
                    )
                  : null,
            ),
            onSubmitted: (value) => _cargarDirectorio(busqueda: value),
          ),
        ),

        // Lista de comunidades
        Expanded(
          child: _isLoading
              ? const FlavorLoadingState()
              : _error != null
                  ? _buildError(_error!)
                  : _comunidades.isEmpty
                      ? _buildEmpty('No hay comunidades en el directorio')
                      : RefreshIndicator(
                          onRefresh: () => _cargarDirectorio(),
                          child: ListView.builder(
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            itemCount: _comunidades.length,
                            itemBuilder: (context, index) {
                              return _buildComunidadCard(_comunidades[index]);
                            },
                          ),
                        ),
        ),
      ],
    );
  }

  Widget _buildCercanasTab(ThemeData theme) {
    if (_isLoadingNearby) {
      return const FlavorLoadingState();
    }

    if (_cercanas.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.location_searching, size: 64, color: theme.colorScheme.outline),
            const SizedBox(height: 16),
            const Text('No se encontraron comunidades cercanas'),
            const SizedBox(height: 24),
            FilledButton.icon(
              onPressed: _cargarCercanas,
              icon: const Icon(Icons.refresh),
              label: const Text('Buscar de nuevo'),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarCercanas,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _cercanas.length,
        itemBuilder: (context, index) {
          final comunidad = _cercanas[index];
          final distancia = comunidad['distancia'] ?? comunidad['distance'];
          return _buildComunidadCard(
            comunidad,
            subtitle: distancia != null ? '${distancia.toStringAsFixed(1)} km' : null,
          );
        },
      ),
    );
  }

  Widget _buildGuardadasTab(ThemeData theme) {
    return FutureBuilder<List<SavedBusiness>>(
      future: ServerConfig.getBusinesses(),
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }

        final guardadas = snapshot.data!;
        if (guardadas.isEmpty) {
          return _buildEmpty('No tienes comunidades guardadas');
        }

        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: guardadas.length,
          itemBuilder: (context, index) {
            final business = guardadas[index];
            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: ListTile(
                leading: CircleAvatar(
                  backgroundColor: theme.colorScheme.primaryContainer,
                  child: Icon(
                    Icons.groups,
                    color: theme.colorScheme.onPrimaryContainer,
                  ),
                ),
                title: Text(
                  business.name.isNotEmpty ? business.name : 'Comunidad',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                subtitle: Text(
                  business.serverUrl,
                  style: TextStyle(
                    fontSize: 12,
                    color: theme.colorScheme.outline,
                  ),
                ),
                trailing: IconButton(
                  icon: const Icon(Icons.login),
                  tooltip: 'Conectar',
                  onPressed: () async {
                    await ServerConfig.setCurrentBusiness(
                      serverUrl: business.serverUrl,
                      apiNamespace: business.apiNamespace,
                      name: business.name,
                      type: business.type,
                    );
                    if (!context.mounted) return;
                    Navigator.of(context).popUntil((route) => route.isFirst);
                  },
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildComunidadCard(Map<String, dynamic> comunidad, {String? subtitle}) {
    final theme = Theme.of(context);
    final nombre = comunidad['name']?.toString() ??
                   comunidad['nombre']?.toString() ?? 'Comunidad';
    final descripcion = comunidad['description']?.toString() ??
                        comunidad['descripcion']?.toString() ?? '';
    final url = comunidad['url']?.toString() ??
                comunidad['site_url']?.toString() ?? '';
    final logo = comunidad['logo']?.toString() ?? '';
    final miembros = comunidad['members_count'] ?? comunidad['miembros'];
    final categoria = comunidad['category']?.toString() ??
                      comunidad['categoria']?.toString();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => _mostrarDetalle(comunidad),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Logo o avatar
              CircleAvatar(
                radius: 28,
                backgroundColor: theme.colorScheme.primaryContainer,
                backgroundImage: logo.isNotEmpty ? NetworkImage(logo) : null,
                child: logo.isEmpty
                    ? Icon(Icons.groups, color: theme.colorScheme.onPrimaryContainer)
                    : null,
              ),
              const SizedBox(width: 16),

              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      nombre,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 16,
                      ),
                    ),
                    if (subtitle != null || categoria != null) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          if (categoria != null) ...[
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.secondaryContainer,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                categoria,
                                style: TextStyle(
                                  fontSize: 11,
                                  color: theme.colorScheme.onSecondaryContainer,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                          ],
                          if (subtitle != null)
                            Text(
                              subtitle,
                              style: TextStyle(
                                fontSize: 12,
                                color: theme.colorScheme.outline,
                              ),
                            ),
                        ],
                      ),
                    ],
                    if (descripcion.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        descripcion,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 13,
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                    if (miembros != null) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(
                            Icons.people_outline,
                            size: 14,
                            color: theme.colorScheme.outline,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '$miembros miembros',
                            style: TextStyle(
                              fontSize: 12,
                              color: theme.colorScheme.outline,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),

              // Botón añadir
              IconButton(
                icon: const Icon(Icons.add_circle_outline),
                tooltip: 'Añadir a mis comunidades',
                onPressed: url.isNotEmpty ? () => _agregarComunidad(comunidad) : null,
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _mostrarDetalle(Map<String, dynamic> comunidad) {
    final theme = Theme.of(context);
    final nombre = comunidad['name']?.toString() ?? 'Comunidad';
    final descripcion = comunidad['description']?.toString() ?? '';
    final url = comunidad['url']?.toString() ?? '';
    final miembros = comunidad['members_count'];
    final modulos = comunidad['modules'] as List? ?? [];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.6,
          minChildSize: 0.4,
          maxChildSize: 0.9,
          expand: false,
          builder: (context, scrollController) {
            return ListView(
              controller: scrollController,
              padding: const EdgeInsets.all(24),
              children: [
                // Handle
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: theme.colorScheme.outline.withOpacity(0.3),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Nombre
                Text(
                  nombre,
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),

                // URL
                if (url.isNotEmpty) ...[
                  Text(
                    url,
                    style: TextStyle(
                      color: theme.colorScheme.primary,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // Descripción
                if (descripcion.isNotEmpty) ...[
                  Text(descripcion),
                  const SizedBox(height: 16),
                ],

                // Stats
                if (miembros != null) ...[
                  Row(
                    children: [
                      _buildStat(Icons.people, '$miembros', 'Miembros'),
                      const SizedBox(width: 24),
                      _buildStat(Icons.extension, '${modulos.length}', 'Módulos'),
                    ],
                  ),
                  const SizedBox(height: 24),
                ],

                // Módulos
                if (modulos.isNotEmpty) ...[
                  Text(
                    'Módulos disponibles',
                    style: theme.textTheme.titleMedium,
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: modulos.map<Widget>((m) {
                      final moduleName = m is String ? m : (m['name'] ?? m['id'] ?? '');
                      return Chip(
                        label: Text(moduleName.toString()),
                        backgroundColor: theme.colorScheme.surfaceContainerHighest,
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 24),
                ],

                // Botón principal
                FilledButton.icon(
                  onPressed: url.isNotEmpty ? () {
                    Navigator.pop(context);
                    _agregarComunidad(comunidad);
                  } : null,
                  icon: const Icon(Icons.add),
                  label: const Text('Añadir a mis comunidades'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  Widget _buildStat(IconData icon, String value, String label) {
    final theme = Theme.of(context);
    return Column(
      children: [
        Icon(icon, color: theme.colorScheme.primary),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 18,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: theme.colorScheme.outline,
          ),
        ),
      ],
    );
  }

  Widget _buildError(String message) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.error_outline, size: 48, color: Theme.of(context).colorScheme.error),
          const SizedBox(height: 16),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 24),
          FilledButton.tonal(
            onPressed: () => _cargarDirectorio(),
            child: const Text('Reintentar'),
          ),
        ],
      ),
    );
  }

  Widget _buildEmpty(String message) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.inbox_outlined, size: 64, color: Theme.of(context).colorScheme.outline),
          const SizedBox(height: 16),
          Text(message),
        ],
      ),
    );
  }
}
