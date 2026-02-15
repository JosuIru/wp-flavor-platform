import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class ComunidadesScreen extends ConsumerStatefulWidget {
  const ComunidadesScreen({super.key});

  @override
  ConsumerState<ComunidadesScreen> createState() => _ComunidadesScreenState();
}

class _ComunidadesScreenState extends ConsumerState<ComunidadesScreen> {
  List<dynamic> _listaComunidades = [];
  bool _cargando = true;
  String? _mensajeError;

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
      final respuesta = await clienteApi.get('/comunidades');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaComunidades = respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Comunidades'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _cargarDatos),
        ],
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.location_city, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_mensajeError!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarDatos,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _listaComunidades.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.location_city, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay comunidades disponibles'),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listaComunidades.length,
                        itemBuilder: (context, indice) => _construirTarjetaComunidad(_listaComunidades[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaComunidad(dynamic elemento) {
    final mapaDatos = elemento as Map<String, dynamic>;
    final nombreComunidad = mapaDatos['nombre'] ?? mapaDatos['titulo'] ?? mapaDatos['title'] ?? 'Sin nombre';
    final descripcionComunidad = mapaDatos['descripcion'] ?? mapaDatos['description'] ?? '';
    final cantidadMiembros = mapaDatos['miembros'] ?? mapaDatos['members'] ?? 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Theme.of(context).colorScheme.primaryContainer,
          child: const Icon(Icons.location_city),
        ),
        title: Text(nombreComunidad),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (descripcionComunidad.isNotEmpty)
              Text(
                descripcionComunidad,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            if (cantidadMiembros > 0)
              Text(
                '$cantidadMiembros miembros',
                style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
              ),
          ],
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          final idComunidad = mapaDatos['id'];
          if (idComunidad != null) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => ComunidadDetalleScreen(comunidadId: idComunidad),
              ),
            );
          }
        },
      ),
    );
  }
}

class ComunidadDetalleScreen extends ConsumerStatefulWidget {
  final dynamic comunidadId;
  const ComunidadDetalleScreen({super.key, required this.comunidadId});

  @override
  ConsumerState<ComunidadDetalleScreen> createState() => _ComunidadDetalleScreenState();
}

class _ComunidadDetalleScreenState extends ConsumerState<ComunidadDetalleScreen> {
  Map<String, dynamic>? _datosComunidad;
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/comunidades/${widget.comunidadId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosComunidad = respuesta.data!['data'] ?? respuesta.data!;
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
      appBar: AppBar(title: const Text('Detalle de Comunidad')),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(child: Text(_mensajeError!))
              : _datosComunidad == null
                  ? const Center(child: Text('No se encontraron datos'))
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        Text(
                          _datosComunidad!['nombre'] ?? _datosComunidad!['titulo'] ?? 'Comunidad',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 16),
                        if (_datosComunidad!['descripcion'] != null)
                          Text(_datosComunidad!['descripcion']),
                        const SizedBox(height: 24),
                        FilledButton.icon(
                          onPressed: () {},
                          icon: const Icon(Icons.group_add),
                          label: const Text('Unirse a la comunidad'),
                        ),
                      ],
                    ),
    );
  }
}
