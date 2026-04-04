import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_search_field.dart';
import '../../../core/widgets/flavor_state_widgets.dart';
import 'busqueda_foros_sheet.dart';
import 'crear_foro_sheet.dart';
import 'foro_detalle_screen.dart';
import 'widgets/foro_card.dart';

class ForosScreen extends ConsumerStatefulWidget {
  const ForosScreen({super.key});

  @override
  ConsumerState<ForosScreen> createState() => _ForosScreenState();
}

class _ForosScreenState extends ConsumerState<ForosScreen> {
  List<dynamic> _listaForos = [];
  bool _cargando = true;
  String? _mensajeError;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _cargarDatos();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/foros');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaForos = respuesta.data!['items'] ?? respuesta.data!['data'] ?? respuesta.data!['foros'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar foros';
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
      appBar: AppBar(
        title: const Text('Foros'),
        actions: [
          IconButton(icon: const Icon(Icons.search), onPressed: _mostrarBusqueda),
          IconButton(icon: const Icon(Icons.refresh), onPressed: _cargarDatos),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _crearNuevoForo(context),
        child: const Icon(Icons.add),
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDatos,
                  icon: Icons.forum,
                )
              : _listaForos.isEmpty
                  ? const FlavorEmptyState(
                      icon: Icons.forum,
                      title: 'No hay foros disponibles',
                      message: 'Se el primero en iniciar una discusion',
                    )
                  : Column(
                      children: [
                        Padding(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
                          child: FlavorSearchField(
                            hintText: 'Buscar foros o temas',
                            value: _searchQuery,
                            onChanged: (value) {
                              setState(() {
                                _searchQuery = value.trim();
                              });
                            },
                          ),
                        ),
                        Expanded(
                          child: RefreshIndicator(
                            onRefresh: _cargarDatos,
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: _forosFiltrados.length,
                              itemBuilder: (context, indice) =>
                                  _construirTarjetaForo(_forosFiltrados[indice]),
                            ),
                          ),
                        ),
                      ],
                    ),
    );
  }

  List<dynamic> get _forosFiltrados {
    if (_searchQuery.isEmpty) {
      return _listaForos;
    }

    final query = _searchQuery.toLowerCase();
    return _listaForos.where((elemento) {
      final mapa = elemento as Map<String, dynamic>;
      final haystack = [
        mapa['titulo'],
        mapa['nombre'],
        mapa['title'],
        mapa['descripcion'],
        mapa['description'],
        mapa['autor'],
        mapa['author'],
        mapa['usuario'],
        mapa['categoria'],
        mapa['category'],
      ].whereType<Object>().map((e) => e.toString().toLowerCase()).join(' ');
      return haystack.contains(query);
    }).toList();
  }

  Widget _construirTarjetaForo(dynamic elemento) {
    final mapaDatos = elemento as Map<String, dynamic>;
    return ForoCard(
      item: mapaDatos,
      onTap: () {
        final idForo = mapaDatos['id'];
        if (idForo != null) {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => ForoDetalleScreen(foroId: idForo),
            ),
          );
        }
      },
    );
  }

  Future<void> _crearNuevoForo(BuildContext context) async {
    await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => CrearForoSheet(
        onCreated: _cargarDatos,
      ),
    );
  }

  void _mostrarBusqueda() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => BusquedaForosSheet(
        foros: _listaForos,
        onSelect: (foro) {
          Navigator.pop(context);
          final mapaForo = foro as Map<String, dynamic>;
          final idForo = mapaForo['id'];
          if (idForo != null) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => ForoDetalleScreen(foroId: idForo),
              ),
            );
          }
        },
      ),
    );
  }
}
