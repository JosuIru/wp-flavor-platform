import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_state_widgets.dart';

part 'comunidades_screen_parts.dart';

/// Pantalla principal del módulo de comunidades
class ComunidadesScreen extends ConsumerStatefulWidget {
  const ComunidadesScreen({super.key});

  @override
  ConsumerState<ComunidadesScreen> createState() => _ComunidadesScreenState();
}

class _ComunidadesScreenState extends ConsumerState<ComunidadesScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<_Comunidad> _todasComunidades = [];
  List<_Comunidad> _misComunidades = [];
  bool _cargando = true;
  String? _mensajeError;
  String _busqueda = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _cargarDatos();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/comunidades');
      if (respuesta.success && respuesta.data != null) {
        final items = respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
        final comunidades = (items as List)
            .map((json) => _Comunidad.fromJson(json as Map<String, dynamic>))
            .toList();

        setState(() {
          _todasComunidades = comunidades;
          _misComunidades = comunidades.where((c) => c.esMiembro).toList();
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar comunidades';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  List<_Comunidad> get _comunidadesFiltradas {
    final lista = _tabController.index == 0 ? _misComunidades : _todasComunidades;
    if (_busqueda.isEmpty) return lista;
    final query = _busqueda.toLowerCase();
    return lista.where((c) {
      return c.nombre.toLowerCase().contains(query) ||
          c.descripcion.toLowerCase().contains(query) ||
          c.categorias.any((cat) => cat.toLowerCase().contains(query));
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Comunidades'),
        bottom: TabBar(
          controller: _tabController,
          onTap: (_) => setState(() {}),
          tabs: [
            Tab(
              icon: const Icon(Icons.star),
              text: 'Mis Comunidades (${_misComunidades.length})',
            ),
            Tab(
              icon: const Icon(Icons.explore),
              text: 'Explorar (${_todasComunidades.length})',
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: _mostrarBusqueda,
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDatos,
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDatos,
                  icon: Icons.groups,
                )
              : Column(
                  children: [
                    // Barra de búsqueda activa
                    if (_busqueda.isNotEmpty)
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 8,
                        ),
                        color: Theme.of(context).colorScheme.surfaceContainerHighest,
                        child: Row(
                          children: [
                            const Icon(Icons.search, size: 20),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Buscando: "$_busqueda"',
                                style: const TextStyle(fontStyle: FontStyle.italic),
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.close, size: 20),
                              onPressed: () => setState(() => _busqueda = ''),
                            ),
                          ],
                        ),
                      ),

                    // Lista de comunidades
                    Expanded(
                      child: _comunidadesFiltradas.isEmpty
                          ? FlavorEmptyState(
                              icon: Icons.groups_outlined,
                              title: _tabController.index == 0
                                  ? 'No perteneces a ninguna comunidad'
                                  : 'No hay comunidades disponibles',
                              message: _tabController.index == 0
                                  ? 'Explora y únete a comunidades'
                                  : null,
                              action: _tabController.index == 0
                                  ? TextButton.icon(
                                      onPressed: () {
                                        _tabController.animateTo(1);
                                        setState(() {});
                                      },
                                      icon: const Icon(Icons.explore),
                                      label: const Text('Explorar comunidades'),
                                    )
                                  : null,
                            )
                          : RefreshIndicator(
                              onRefresh: _cargarDatos,
                              child: ListView.builder(
                                padding: const EdgeInsets.all(16),
                                itemCount: _comunidadesFiltradas.length,
                                itemBuilder: (context, index) {
                                  final comunidad = _comunidadesFiltradas[index];
                                  return _ComunidadCard(
                                    comunidad: comunidad,
                                    onTap: () => _abrirDetalle(comunidad),
                                  );
                                },
                              ),
                            ),
                    ),
                  ],
                ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearComunidad,
        icon: const Icon(Icons.add),
        label: const Text('Nueva'),
      ),
    );
  }

  void _mostrarBusqueda() async {
    final resultado = await showSearch(
      context: context,
      delegate: _ComunidadSearchDelegate(_todasComunidades),
    );

    if (resultado != null && resultado.isNotEmpty) {
      setState(() => _busqueda = resultado);
    }
  }

  void _abrirDetalle(_Comunidad comunidad) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ComunidadDetalleScreen(
          comunidadId: comunidad.id,
          comunidad: comunidad,
        ),
      ),
    ).then((_) => _cargarDatos());
  }

  void _crearComunidad() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const _CrearComunidadScreen(),
      ),
    ).then((created) {
      if (created == true) {
        _cargarDatos();
      }
    });
  }
}

/// Search delegate para comunidades
class _ComunidadSearchDelegate extends SearchDelegate<String> {
  final List<_Comunidad> comunidades;

  _ComunidadSearchDelegate(this.comunidades);

  @override
  String get searchFieldLabel => 'Buscar comunidades...';

  @override
  List<Widget> buildActions(BuildContext context) {
    return [
      if (query.isNotEmpty)
        IconButton(
          icon: const Icon(Icons.clear),
          onPressed: () => query = '',
        ),
    ];
  }

  @override
  Widget buildLeading(BuildContext context) {
    return IconButton(
      icon: const Icon(Icons.arrow_back),
      onPressed: () => close(context, ''),
    );
  }

  @override
  Widget buildResults(BuildContext context) {
    close(context, query);
    return const SizedBox.shrink();
  }

  @override
  Widget buildSuggestions(BuildContext context) {
    final sugerencias = comunidades.where((c) {
      final queryLower = query.toLowerCase();
      return c.nombre.toLowerCase().contains(queryLower) ||
          c.descripcion.toLowerCase().contains(queryLower);
    }).toList();

    return ListView.builder(
      itemCount: sugerencias.length,
      itemBuilder: (context, index) {
        final comunidad = sugerencias[index];
        return ListTile(
          leading: const CircleAvatar(child: Icon(Icons.groups)),
          title: Text(comunidad.nombre),
          subtitle: Text(
            comunidad.descripcion,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          onTap: () => close(context, comunidad.nombre),
        );
      },
    );
  }
}

/// Pantalla de detalle de comunidad mejorada
class ComunidadDetalleScreen extends ConsumerStatefulWidget {
  final dynamic comunidadId;
  final _Comunidad? comunidad;

  const ComunidadDetalleScreen({
    super.key,
    required this.comunidadId,
    this.comunidad,
  });

  @override
  ConsumerState<ComunidadDetalleScreen> createState() =>
      _ComunidadDetalleScreenState();
}

class _ComunidadDetalleScreenState extends ConsumerState<ComunidadDetalleScreen> {
  _Comunidad? _comunidad;
  bool _cargando = true;
  String? _mensajeError;
  bool _uniendose = false;
  bool _abandonando = false;

  @override
  void initState() {
    super.initState();
    if (widget.comunidad != null) {
      _comunidad = widget.comunidad;
      _cargando = false;
    }
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    if (_comunidad == null) {
      setState(() {
        _cargando = true;
        _mensajeError = null;
      });
    }

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/comunidades/${widget.comunidadId}');
      if (respuesta.success && respuesta.data != null) {
        final data = respuesta.data!['data'] ?? respuesta.data!;
        setState(() {
          _comunidad = _Comunidad.fromJson(data);
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar detalle';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDetalle,
                )
              : _comunidad == null
                  ? const FlavorEmptyState(
                      icon: Icons.groups_outlined,
                      title: 'No se encontraron datos',
                    )
                  : CustomScrollView(
                      slivers: [
                        // AppBar con imagen
                        SliverAppBar(
                          expandedHeight: 200,
                          pinned: true,
                          flexibleSpace: FlexibleSpaceBar(
                            title: Text(
                              _comunidad!.nombre,
                              style: const TextStyle(
                                shadows: [
                                  Shadow(blurRadius: 4, color: Colors.black54)
                                ],
                              ),
                            ),
                            background: _comunidad!.imagenUrl != null
                                ? Image.network(
                                    _comunidad!.imagenUrl!,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) =>
                                        _buildPlaceholderHeader(context),
                                  )
                                : _buildPlaceholderHeader(context),
                          ),
                          actions: [
                            if (_comunidad!.esAdmin)
                              IconButton(
                                icon: const Icon(Icons.settings),
                                onPressed: _abrirConfiguracion,
                              ),
                          ],
                        ),

                        // Contenido
                        SliverToBoxAdapter(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Stats
                                _buildStatsRow(),

                                const SizedBox(height: 16),

                                // Descripción
                                if (_comunidad!.descripcion.isNotEmpty) ...[
                                  Text(
                                    'Acerca de',
                                    style: Theme.of(context).textTheme.titleMedium,
                                  ),
                                  const SizedBox(height: 8),
                                  Text(_comunidad!.descripcion),
                                  const SizedBox(height: 16),
                                ],

                                // Ubicación
                                if (_comunidad!.ubicacion != null) ...[
                                  Row(
                                    children: [
                                      const Icon(Icons.location_on, size: 20),
                                      const SizedBox(width: 8),
                                      Text(_comunidad!.ubicacion!),
                                    ],
                                  ),
                                  const SizedBox(height: 16),
                                ],

                                // Categorías
                                if (_comunidad!.categorias.isNotEmpty) ...[
                                  Wrap(
                                    spacing: 8,
                                    runSpacing: 8,
                                    children: _comunidad!.categorias.map((cat) {
                                      return Chip(label: Text(cat));
                                    }).toList(),
                                  ),
                                  const SizedBox(height: 16),
                                ],

                                // Botón de acción
                                _buildActionButton(),

                                const SizedBox(height: 24),

                                // Acciones rápidas
                                Text(
                                  'Accesos rápidos',
                                  style: Theme.of(context).textTheme.titleMedium,
                                ),
                                const SizedBox(height: 12),
                                _buildQuickActions(),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
    );
  }

  Widget _buildPlaceholderHeader(BuildContext context) {
    return Container(
      color: Theme.of(context).colorScheme.primaryContainer,
      child: Center(
        child: Icon(
          Icons.groups,
          size: 64,
          color: Theme.of(context).colorScheme.primary,
        ),
      ),
    );
  }

  Widget _buildStatsRow() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceAround,
      children: [
        _StatItem(
          icon: Icons.people,
          value: '${_comunidad!.cantidadMiembros}',
          label: 'Miembros',
        ),
        if (_comunidad!.fechaCreacion != null)
          _StatItem(
            icon: Icons.calendar_today,
            value: '${_comunidad!.fechaCreacion!.year}',
            label: 'Creada',
          ),
        _StatItem(
          icon: _comunidad!.esMiembro ? Icons.check_circle : Icons.group_add,
          value: _comunidad!.esMiembro ? 'Sí' : 'No',
          label: 'Miembro',
        ),
      ],
    );
  }

  Widget _buildActionButton() {
    if (_comunidad!.esMiembro) {
      return Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              onPressed: _abandonando ? null : _abandonarComunidad,
              icon: _abandonando
                  ? const FlavorInlineSpinner()
                  : const Icon(Icons.exit_to_app),
              label: Text(_abandonando ? 'Saliendo...' : 'Abandonar comunidad'),
              style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
            ),
          ),
        ],
      );
    }

    return Row(
      children: [
        Expanded(
          child: FilledButton.icon(
            onPressed: _uniendose ? null : _unirseAComunidad,
            icon: _uniendose
                ? const FlavorInlineSpinner(color: Colors.white)
                : const Icon(Icons.group_add),
            label: Text(_uniendose ? 'Uniendose...' : 'Unirse a la comunidad'),
          ),
        ),
      ],
    );
  }

  Widget _buildQuickActions() {
    return Wrap(
      spacing: 12,
      runSpacing: 12,
      children: [
        _QuickActionChip(
          icon: Icons.people,
          label: 'Miembros',
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => _MiembrosComunidadScreen(
                comunidadId: _comunidad!.id,
                nombreComunidad: _comunidad!.nombre,
                esAdmin: _comunidad!.esAdmin,
              ),
            ),
          ),
        ),
        _QuickActionChip(
          icon: Icons.event,
          label: 'Eventos',
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (_) => _EventosComunidadScreen(
                comunidadId: _comunidad!.id,
                nombreComunidad: _comunidad!.nombre,
              ),
            ),
          ),
        ),
        _QuickActionChip(
          icon: Icons.forum,
          label: 'Foro',
          onTap: () {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Foro no disponible aún')),
            );
          },
        ),
        _QuickActionChip(
          icon: Icons.share,
          label: 'Compartir',
          onTap: _compartir,
        ),
      ],
    );
  }

  Future<void> _unirseAComunidad() async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Unirse a la comunidad'),
        content: Text('¿Deseas unirte a "${_comunidad?.nombre}"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Unirme'),
          ),
        ],
      ),
    );

    if (confirmar != true || !mounted) return;

    setState(() => _uniendose = true);

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post(
        '/comunidades/${widget.comunidadId}/unirse',
        data: {},
      );

      if (!mounted) return;

      if (respuesta.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Te has unido a la comunidad'),
            backgroundColor: Colors.green,
          ),
        );
        _cargarDetalle();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(respuesta.error ?? 'Error al unirse'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _uniendose = false);
      }
    }
  }

  Future<void> _abandonarComunidad() async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Abandonar comunidad'),
        content: Text('¿Seguro que deseas abandonar "${_comunidad?.nombre}"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Abandonar'),
          ),
        ],
      ),
    );

    if (confirmar != true || !mounted) return;

    setState(() => _abandonando = true);

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post(
        '/comunidades/${widget.comunidadId}/abandonar',
        data: {},
      );

      if (!mounted) return;

      if (respuesta.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Has abandonado la comunidad')),
        );
        Navigator.pop(context);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(respuesta.error ?? 'Error'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _abandonando = false);
      }
    }
  }

  void _abrirConfiguracion() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Configuración no disponible aún')),
    );
  }

  void _compartir() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Compartir no disponible aún')),
    );
  }
}

/// Widget de estadística
class _StatItem extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;

  const _StatItem({
    required this.icon,
    required this.value,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, size: 28, color: Theme.of(context).colorScheme.primary),
        const SizedBox(height: 4),
        Text(
          value,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
        Text(
          label,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Colors.grey,
              ),
        ),
      ],
    );
  }
}

/// Chip de acción rápida
class _QuickActionChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _QuickActionChip({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      avatar: Icon(icon, size: 18),
      label: Text(label),
      onPressed: onTap,
    );
  }
}
