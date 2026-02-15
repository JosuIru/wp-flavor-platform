import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class AdvertisingScreen extends ConsumerStatefulWidget {
  const AdvertisingScreen({super.key});

  @override
  ConsumerState<AdvertisingScreen> createState() => _AdvertisingScreenState();
}

class _AdvertisingScreenState extends ConsumerState<AdvertisingScreen> {
  List<dynamic> _anunciosEticos = [];
  bool _cargandoDatos = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarAnuncios();
  }

  Future<void> _cargarAnuncios() async {
    setState(() {
      _cargandoDatos = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/advertising');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _anunciosEticos = respuesta.data!['anuncios'] ??
              respuesta.data!['items'] ??
              respuesta.data!['data'] ??
              [];
          _cargandoDatos = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar publicidad';
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
        title: const Text('Publicidad Ética'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarAnuncios,
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
                      const Icon(Icons.campaign, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_mensajeError!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarAnuncios,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _anunciosEticos.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.campaign,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay anuncios disponibles'),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarAnuncios,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _anunciosEticos.length,
                        itemBuilder: (context, indice) =>
                            _construirTarjetaAnuncio(_anunciosEticos[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaAnuncio(dynamic anuncio) {
    final datosAnuncio = anuncio as Map<String, dynamic>;
    final tituloAnuncio = datosAnuncio['titulo'] ??
        datosAnuncio['nombre'] ??
        datosAnuncio['title'] ??
        'Sin título';
    final descripcionAnuncio = datosAnuncio['descripcion'] ??
        datosAnuncio['description'] ??
        '';
    final tipoAnuncio = datosAnuncio['tipo'] ?? datosAnuncio['type'] ?? '';
    final estadoAnuncio = datosAnuncio['estado'] ?? datosAnuncio['status'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.amber.shade100,
          child: const Icon(Icons.campaign, color: Colors.amber),
        ),
        title: Text(tituloAnuncio),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (descripcionAnuncio.isNotEmpty)
              Text(
                descripcionAnuncio,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            if (tipoAnuncio.isNotEmpty || estadoAnuncio.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Row(
                  children: [
                    if (tipoAnuncio.isNotEmpty)
                      Chip(
                        label: Text(tipoAnuncio, style: const TextStyle(fontSize: 10)),
                        padding: EdgeInsets.zero,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                    if (estadoAnuncio.isNotEmpty) ...[
                      const SizedBox(width: 4),
                      Chip(
                        label: Text(estadoAnuncio, style: const TextStyle(fontSize: 10)),
                        padding: EdgeInsets.zero,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                    ],
                  ],
                ),
              ),
          ],
        ),
        isThreeLine: descripcionAnuncio.isNotEmpty,
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          // TODO: Navegar al detalle del anuncio
        },
      ),
    );
  }
}
