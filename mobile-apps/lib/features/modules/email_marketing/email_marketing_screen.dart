import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class EmailMarketingScreen extends ConsumerStatefulWidget {
  const EmailMarketingScreen({super.key});

  @override
  ConsumerState<EmailMarketingScreen> createState() => _EmailMarketingScreenState();
}

class _EmailMarketingScreenState extends ConsumerState<EmailMarketingScreen> {
  List<dynamic> _listaCampanas = [];
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
      final respuesta = await clienteApi.get('/email-marketing/campanas');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaCampanas = respuesta.data!['items'] ?? respuesta.data!['data'] ?? respuesta.data!['campanas'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar campanas';
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
        title: const Text('Email Marketing'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _cargarDatos),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {},
        icon: const Icon(Icons.add),
        label: const Text('Nueva campana'),
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.email, size: 64, color: Colors.grey),
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
              : _listaCampanas.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.email, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay campanas disponibles'),
                          const SizedBox(height: 8),
                          const Text(
                            'Crea tu primera campana de email marketing',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listaCampanas.length,
                        itemBuilder: (context, indice) => _construirTarjetaCampana(_listaCampanas[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaCampana(dynamic elemento) {
    final mapaDatos = elemento as Map<String, dynamic>;
    final nombreCampana = mapaDatos['nombre'] ?? mapaDatos['titulo'] ?? mapaDatos['title'] ?? 'Sin nombre';
    final asuntoCampana = mapaDatos['asunto'] ?? mapaDatos['subject'] ?? '';
    final estadoCampana = mapaDatos['estado'] ?? mapaDatos['status'] ?? 'borrador';
    final fechaEnvio = mapaDatos['fecha_envio'] ?? mapaDatos['sent_date'] ?? '';
    final totalEnviados = mapaDatos['enviados'] ?? mapaDatos['sent'] ?? 0;
    final totalAbiertos = mapaDatos['abiertos'] ?? mapaDatos['opened'] ?? 0;
    final totalClics = mapaDatos['clics'] ?? mapaDatos['clicks'] ?? 0;

    Color colorEstado;
    IconData iconoEstado;
    switch (estadoCampana.toString().toLowerCase()) {
      case 'enviado':
      case 'sent':
        colorEstado = Colors.green;
        iconoEstado = Icons.check_circle;
        break;
      case 'programado':
      case 'scheduled':
        colorEstado = Colors.blue;
        iconoEstado = Icons.schedule;
        break;
      case 'enviando':
      case 'sending':
        colorEstado = Colors.orange;
        iconoEstado = Icons.send;
        break;
      default:
        colorEstado = Colors.grey;
        iconoEstado = Icons.drafts;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () {
          final idCampana = mapaDatos['id'];
          if (idCampana != null) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => CampanaDetalleScreen(campanaId: idCampana),
              ),
            );
          }
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: colorEstado.withOpacity(0.2),
                  child: Icon(iconoEstado, color: colorEstado),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        nombreCampana,
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      if (asuntoCampana.isNotEmpty)
                        Text(
                          asuntoCampana,
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: colorEstado.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    estadoCampana.toString().toUpperCase(),
                    style: TextStyle(color: colorEstado, fontSize: 11, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            if (estadoCampana.toString().toLowerCase() == 'enviado' || estadoCampana.toString().toLowerCase() == 'sent') ...[
              const Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _construirEstadistica('Enviados', totalEnviados.toString(), Icons.send),
                  _construirEstadistica('Abiertos', totalAbiertos.toString(), Icons.visibility),
                  _construirEstadistica('Clics', totalClics.toString(), Icons.touch_app),
                ],
              ),
            ],
            if (fechaEnvio.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                'Enviado: $fechaEnvio',
                style: TextStyle(color: Colors.grey.shade500, fontSize: 12),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _construirEstadistica(String etiqueta, String valor, IconData icono) {
    return Column(
      children: [
        Icon(icono, size: 20, color: Colors.grey.shade600),
        const SizedBox(height: 4),
        Text(valor, style: const TextStyle(fontWeight: FontWeight.bold)),
        Text(etiqueta, style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
      ],
    );
  }
}

class CampanaDetalleScreen extends ConsumerStatefulWidget {
  final dynamic campanaId;
  const CampanaDetalleScreen({super.key, required this.campanaId});

  @override
  ConsumerState<CampanaDetalleScreen> createState() => _CampanaDetalleScreenState();
}

class _CampanaDetalleScreenState extends ConsumerState<CampanaDetalleScreen> {
  Map<String, dynamic>? _datosCampana;
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
      final respuesta = await clienteApi.get('/email-marketing/campanas/${widget.campanaId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosCampana = respuesta.data!['data'] ?? respuesta.data!;
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar campana';
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
        title: const Text('Detalle de Campana'),
        actions: [
          IconButton(icon: const Icon(Icons.edit), onPressed: () {}),
        ],
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(child: Text(_mensajeError!))
              : _datosCampana == null
                  ? const Center(child: Text('No se encontraron datos'))
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        Text(
                          _datosCampana!['nombre'] ?? _datosCampana!['titulo'] ?? 'Campana',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 8),
                        if (_datosCampana!['asunto'] != null)
                          Text(
                            'Asunto: ${_datosCampana!['asunto']}',
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        const SizedBox(height: 24),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            children: [
                              const Text('Estadisticas', style: TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(height: 16),
                              _construirFilaEstadistica('Total enviados', _datosCampana!['enviados']?.toString() ?? '0'),
                              _construirFilaEstadistica('Abiertos', _datosCampana!['abiertos']?.toString() ?? '0'),
                              _construirFilaEstadistica('Clics', _datosCampana!['clics']?.toString() ?? '0'),
                              _construirFilaEstadistica('Rebotes', _datosCampana!['rebotes']?.toString() ?? '0'),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        FilledButton.icon(
                          onPressed: () {},
                          icon: const Icon(Icons.send),
                          label: const Text('Enviar ahora'),
                        ),
                      ],
                    ),
    );
  }

  Widget _construirFilaEstadistica(String etiqueta, String valor) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(etiqueta),
          Text(valor, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}
