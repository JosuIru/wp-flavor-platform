import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class ColectivosScreen extends ConsumerStatefulWidget {
  const ColectivosScreen({super.key});

  @override
  ConsumerState<ColectivosScreen> createState() => _ColectivosScreenState();
}

class _ColectivosScreenState extends ConsumerState<ColectivosScreen> {
  List<dynamic> _listaColectivos = [];
  bool _cargandoDatos = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarColectivos();
  }

  Future<void> _cargarColectivos() async {
    setState(() {
      _cargandoDatos = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/colectivos');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaColectivos = respuesta.data!['colectivos'] ??
              respuesta.data!['asociaciones'] ??
              respuesta.data!['items'] ??
              respuesta.data!['data'] ??
              [];
          _cargandoDatos = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar colectivos';
          _cargandoDatos = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargandoDatos = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Colectivos y Asociaciones'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarColectivos,
          ),
        ],
      ),
      body: _cargandoDatos
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.groups, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_mensajeError!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarColectivos,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _listaColectivos.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.groups,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay colectivos registrados'),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarColectivos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listaColectivos.length,
                        itemBuilder: (context, indice) =>
                            _construirTarjetaColectivo(
                                _listaColectivos[indice]),
                      ),
                    ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          // TODO: Crear nuevo colectivo
        },
        icon: const Icon(Icons.group_add),
        label: const Text('Nuevo colectivo'),
      ),
    );
  }

  Widget _construirTarjetaColectivo(dynamic colectivo) {
    final datosColectivo = colectivo as Map<String, dynamic>;
    final nombreColectivo = datosColectivo['nombre'] ??
        datosColectivo['name'] ??
        datosColectivo['titulo'] ??
        'Sin nombre';
    final descripcionColectivo = datosColectivo['descripcion'] ??
        datosColectivo['description'] ??
        '';
    final categoriaColectivo = datosColectivo['categoria'] ??
        datosColectivo['category'] ??
        datosColectivo['tipo'] ??
        '';
    final numeroMiembros = datosColectivo['miembros'] ??
        datosColectivo['num_miembros'] ??
        datosColectivo['members_count'] ??
        0;
    final imagenColectivo = datosColectivo['imagen'] ??
        datosColectivo['image'] ??
        datosColectivo['logo'] ??
        '';
    final ubicacionColectivo = datosColectivo['ubicacion'] ??
        datosColectivo['location'] ??
        datosColectivo['ciudad'] ??
        '';

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (imagenColectivo.isNotEmpty)
            Image.network(
              imagenColectivo,
              height: 150,
              width: double.infinity,
              fit: BoxFit.cover,
              errorBuilder: (context, error, stackTrace) => Container(
                height: 100,
                color: Colors.purple.shade50,
                child: const Center(
                  child: Icon(Icons.groups, size: 48, color: Colors.purple),
                ),
              ),
            )
          else
            Container(
              height: 100,
              width: double.infinity,
              color: Colors.purple.shade50,
              child: const Center(
                child: Icon(Icons.groups, size: 48, color: Colors.purple),
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        nombreColectivo,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    if (categoriaColectivo.isNotEmpty)
                      Chip(
                        label: Text(
                          categoriaColectivo,
                          style: const TextStyle(fontSize: 10),
                        ),
                        backgroundColor: Colors.purple.shade100,
                        padding: EdgeInsets.zero,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                  ],
                ),
                if (descripcionColectivo.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    descripcionColectivo,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(color: Colors.grey.shade700),
                  ),
                ],
                const SizedBox(height: 12),
                Row(
                  children: [
                    Icon(Icons.people, size: 18, color: Colors.grey.shade600),
                    const SizedBox(width: 4),
                    Text(
                      '$numeroMiembros miembros',
                      style: TextStyle(color: Colors.grey.shade600),
                    ),
                    if (ubicacionColectivo.isNotEmpty) ...[
                      const SizedBox(width: 16),
                      Icon(Icons.location_on,
                          size: 18, color: Colors.grey.shade600),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          ubicacionColectivo,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          // TODO: Ver detalles del colectivo
                        },
                        icon: const Icon(Icons.info_outline),
                        label: const Text('Ver detalles'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () {
                          // TODO: Unirse al colectivo
                        },
                        icon: const Icon(Icons.person_add),
                        label: const Text('Unirse'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
