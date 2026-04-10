import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';

part 'kulturaka_screen_parts.dart';

/// Pantalla principal del módulo Kulturaka
/// Red cultural descentralizada que conecta artistas, espacios y comunidades
class KulturakaScreen extends ConsumerStatefulWidget {
  const KulturakaScreen({super.key});

  @override
  ConsumerState<KulturakaScreen> createState() => _KulturakaScreenState();
}

class _KulturakaScreenState extends ConsumerState<KulturakaScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Datos
  List<_Espacio> _espacios = [];
  List<_Artista> _artistas = [];
  List<_Evento> _eventos = [];
  Map<String, dynamic>? _comunidad;
  Map<String, dynamic>? _metricas;

  // Estados de carga
  bool _cargandoEspacios = true;
  bool _cargandoArtistas = true;
  bool _cargandoEventos = true;
  bool _cargandoComunidad = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _tabController.addListener(_onTabChanged);
    _cargarDatosIniciales();
  }

  @override
  void dispose() {
    _tabController.removeListener(_onTabChanged);
    _tabController.dispose();
    super.dispose();
  }

  void _onTabChanged() {
    if (!_tabController.indexIsChanging) {
      _cargarTabActual();
    }
  }

  Future<void> _cargarDatosIniciales() async {
    await Future.wait([
      _cargarEspacios(),
      _cargarMetricas(),
    ]);
  }

  Future<void> _cargarTabActual() async {
    switch (_tabController.index) {
      case 0:
        if (_espacios.isEmpty) await _cargarEspacios();
        break;
      case 1:
        if (_artistas.isEmpty) await _cargarArtistas();
        break;
      case 2:
        if (_eventos.isEmpty) await _cargarEventos();
        break;
      case 3:
        if (_comunidad == null) await _cargarComunidad();
        break;
    }
  }

  Future<void> _cargarEspacios() async {
    setState(() => _cargandoEspacios = true);
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/kulturaka/espacios');

      if (response.success && response.data != null) {
        final items = response.data!['espacios'] as List<dynamic>? ?? [];
        setState(() {
          _espacios = items.map((json) => _Espacio.fromJson(json)).toList();
          _cargandoEspacios = false;
        });
      } else {
        setState(() => _cargandoEspacios = false);
      }
    } catch (e) {
      setState(() => _cargandoEspacios = false);
    }
  }

  Future<void> _cargarArtistas() async {
    setState(() => _cargandoArtistas = true);
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/kulturaka/artistas');

      if (response.success && response.data != null) {
        final items = response.data!['artistas'] as List<dynamic>? ?? [];
        setState(() {
          _artistas = items.map((json) => _Artista.fromJson(json)).toList();
          _cargandoArtistas = false;
        });
      } else {
        setState(() => _cargandoArtistas = false);
      }
    } catch (e) {
      setState(() => _cargandoArtistas = false);
    }
  }

  Future<void> _cargarEventos() async {
    setState(() => _cargandoEventos = true);
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/kulturaka/eventos');

      if (response.success && response.data != null) {
        final items = response.data!['eventos'] as List<dynamic>? ?? [];
        setState(() {
          _eventos = items.map((json) => _Evento.fromJson(json)).toList();
          _cargandoEventos = false;
        });
      } else {
        setState(() => _cargandoEventos = false);
      }
    } catch (e) {
      setState(() => _cargandoEventos = false);
    }
  }

  Future<void> _cargarComunidad() async {
    setState(() => _cargandoComunidad = true);
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/kulturaka/comunidad');

      if (response.success && response.data != null) {
        setState(() {
          _comunidad = response.data!;
          _cargandoComunidad = false;
        });
      } else {
        setState(() => _cargandoComunidad = false);
      }
    } catch (e) {
      setState(() => _cargandoComunidad = false);
    }
  }

  Future<void> _cargarMetricas() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/kulturaka/metricas');

      if (response.success && response.data != null) {
        setState(() => _metricas = response.data!);
      }
    } catch (_) {
      // Métricas son opcionales, ignorar errores
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Kulturaka'),
        backgroundColor: Colors.pink.shade700,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(icon: Icon(Icons.location_city), text: 'Espacios'),
            Tab(icon: Icon(Icons.music_note), text: 'Artistas'),
            Tab(icon: Icon(Icons.event), text: 'Eventos'),
            Tab(icon: Icon(Icons.people), text: 'Comunidad'),
          ],
        ),
        actions: [
          if (_metricas != null)
            IconButton(
              icon: const Icon(Icons.analytics),
              onPressed: () => _mostrarMetricas(context),
              tooltip: 'Métricas de la red',
            ),
        ],
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildEspaciosTab(),
          _buildArtistasTab(),
          _buildEventosTab(),
          _buildComunidadTab(),
        ],
      ),
    );
  }

  Widget _buildEspaciosTab() {
    if (_cargandoEspacios) {
      return const FlavorLoadingState();
    }

    if (_espacios.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.location_city_outlined,
        title: 'Sin espacios culturales',
        message: 'No hay espacios registrados en la red',
        action: TextButton.icon(
          onPressed: _cargarEspacios,
          icon: const Icon(Icons.refresh),
          label: const Text('Actualizar'),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarEspacios,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _espacios.length,
        itemBuilder: (context, index) {
          return _EspacioCard(
            espacio: _espacios[index],
            onTap: () => _abrirEspacio(_espacios[index]),
          );
        },
      ),
    );
  }

  Widget _buildArtistasTab() {
    if (_cargandoArtistas) {
      return const FlavorLoadingState();
    }

    if (_artistas.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.music_note_outlined,
        title: 'Sin artistas',
        message: 'No hay artistas registrados en la red',
        action: TextButton.icon(
          onPressed: _cargarArtistas,
          icon: const Icon(Icons.refresh),
          label: const Text('Actualizar'),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarArtistas,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _artistas.length,
        itemBuilder: (context, index) {
          return _ArtistaCard(
            artista: _artistas[index],
            onTap: () => _abrirArtista(_artistas[index]),
          );
        },
      ),
    );
  }

  Widget _buildEventosTab() {
    if (_cargandoEventos) {
      return const FlavorLoadingState();
    }

    if (_eventos.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.event_outlined,
        title: 'Sin eventos próximos',
        message: 'No hay eventos culturales programados',
        action: TextButton.icon(
          onPressed: _cargarEventos,
          icon: const Icon(Icons.refresh),
          label: const Text('Actualizar'),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarEventos,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _eventos.length,
        itemBuilder: (context, index) {
          return _EventoCard(
            evento: _eventos[index],
            onTap: () => _abrirEvento(_eventos[index]),
          );
        },
      ),
    );
  }

  Widget _buildComunidadTab() {
    if (_cargandoComunidad) {
      return const FlavorLoadingState();
    }

    if (_comunidad == null) {
      return FlavorEmptyState(
        icon: Icons.people_outlined,
        title: 'Error al cargar',
        message: 'No se pudo cargar la información de la comunidad',
        action: TextButton.icon(
          onPressed: _cargarComunidad,
          icon: const Icon(Icons.refresh),
          label: const Text('Reintentar'),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarComunidad,
      child: _ComunidadView(
        data: _comunidad!,
        onEventoTap: _abrirEventoById,
      ),
    );
  }

  void _abrirEspacio(_Espacio espacio) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _EspacioDetalleScreen(espacio: espacio),
      ),
    );
  }

  void _abrirArtista(_Artista artista) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => _ArtistaDetalleScreen(artista: artista),
      ),
    );
  }

  void _abrirEvento(_Evento evento) {
    // Por ahora mostrar un snackbar, se puede expandir a una pantalla de detalle
    FlavorSnackbar.showInfo(context, 'Evento: ${evento.titulo}');
  }

  void _abrirEventoById(int eventoId) {
    FlavorSnackbar.showInfo(context, 'Abriendo evento #$eventoId');
  }

  void _mostrarMetricas(BuildContext context) {
    if (_metricas == null) return;

    showModalBottomSheet(
      context: context,
      builder: (context) => _MetricasSheet(metricas: _metricas!),
    );
  }
}
