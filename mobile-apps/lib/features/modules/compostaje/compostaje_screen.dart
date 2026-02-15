import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class CompostajeScreen extends ConsumerStatefulWidget {
  const CompostajeScreen({super.key});

  @override
  ConsumerState<CompostajeScreen> createState() => _CompostajeScreenState();
}

class _CompostajeScreenState extends ConsumerState<CompostajeScreen> {
  Map<String, dynamic> _datosDashboard = {};
  List<dynamic> _puntosCompostaje = [];
  bool _cargandoDatos = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarDashboard();
  }

  Future<void> _cargarDashboard() async {
    setState(() {
      _cargandoDatos = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/compostaje/dashboard');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosDashboard = respuesta.data!;
          _puntosCompostaje = respuesta.data!['puntos'] ??
              respuesta.data!['composteras'] ??
              respuesta.data!['items'] ??
              respuesta.data!['data'] ??
              [];
          _cargandoDatos = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar datos';
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
        title: const Text('Compostaje Comunitario'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDashboard,
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
                      const Icon(Icons.compost, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_mensajeError!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarDashboard,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _cargarDashboard,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _construirSeccionEstadisticas(),
                        const SizedBox(height: 24),
                        _construirSeccionAccionesRapidas(),
                        const SizedBox(height: 24),
                        _construirSeccionPuntosCompostaje(),
                      ],
                    ),
                  ),
                ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          // TODO: Registrar aportacion
        },
        icon: const Icon(Icons.add),
        label: const Text('Registrar aportacion'),
        backgroundColor: Colors.green,
      ),
    );
  }

  Widget _construirSeccionEstadisticas() {
    final totalKilosCompostados =
        _datosDashboard['total_kilos'] ?? _datosDashboard['kilos_totales'] ?? 0;
    final aportacionesMes =
        _datosDashboard['aportaciones_mes'] ?? _datosDashboard['este_mes'] ?? 0;
    final co2Evitado =
        _datosDashboard['co2_evitado'] ?? _datosDashboard['kg_co2'] ?? 0;
    final puntosActivos =
        _datosDashboard['puntos_activos'] ?? _puntosCompostaje.length;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Resumen',
          style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 12),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.5,
          children: [
            _construirTarjetaEstadistica(
              titulo: 'Total compostado',
              valor: '$totalKilosCompostados kg',
              icono: Icons.compost,
              colorFondo: Colors.green.shade100,
              colorIcono: Colors.green,
            ),
            _construirTarjetaEstadistica(
              titulo: 'Este mes',
              valor: '$aportacionesMes kg',
              icono: Icons.calendar_today,
              colorFondo: Colors.blue.shade100,
              colorIcono: Colors.blue,
            ),
            _construirTarjetaEstadistica(
              titulo: 'CO2 evitado',
              valor: '$co2Evitado kg',
              icono: Icons.eco,
              colorFondo: Colors.teal.shade100,
              colorIcono: Colors.teal,
            ),
            _construirTarjetaEstadistica(
              titulo: 'Puntos activos',
              valor: '$puntosActivos',
              icono: Icons.location_on,
              colorFondo: Colors.orange.shade100,
              colorIcono: Colors.orange,
            ),
          ],
        ),
      ],
    );
  }

  Widget _construirTarjetaEstadistica({
    required String titulo,
    required String valor,
    required IconData icono,
    required Color colorFondo,
    required Color colorIcono,
  }) {
    return Card(
      elevation: 2,
      child: Container(
        padding: const EdgeInsets.all(12),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: colorFondo,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icono, color: colorIcono, size: 20),
                ),
                const SizedBox(width: 8),
                Text(
                  valor,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              titulo,
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey.shade600,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _construirSeccionAccionesRapidas() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Acciones rapidas',
          style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _construirBotonAccion(
                titulo: 'Ver mapa',
                icono: Icons.map,
                color: Colors.indigo,
                onPressed: () {
                  // TODO: Navegar al mapa de puntos de compostaje
                },
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _construirBotonAccion(
                titulo: 'Historial',
                icono: Icons.history,
                color: Colors.purple,
                onPressed: () {
                  // TODO: Ver historial de aportaciones
                },
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _construirBotonAccion(
                titulo: 'Guia',
                icono: Icons.help_outline,
                color: Colors.teal,
                onPressed: () {
                  // TODO: Mostrar guia de compostaje
                },
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _construirBotonAccion({
    required String titulo,
    required IconData icono,
    required Color color,
    required VoidCallback onPressed,
  }) {
    return Material(
      color: color.withOpacity(0.1),
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Column(
            children: [
              Icon(icono, color: color, size: 28),
              const SizedBox(height: 4),
              Text(
                titulo,
                style: TextStyle(
                  color: color,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _construirSeccionPuntosCompostaje() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'Puntos de compostaje',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            TextButton(
              onPressed: () {
                // TODO: Ver todos los puntos
              },
              child: const Text('Ver todos'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (_puntosCompostaje.isEmpty)
          Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Center(
                child: Column(
                  children: [
                    Icon(Icons.location_off,
                        size: 48, color: Colors.grey.shade400),
                    const SizedBox(height: 8),
                    const Text('No hay puntos de compostaje cercanos'),
                  ],
                ),
              ),
            ),
          )
        else
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _puntosCompostaje.length > 5 ? 5 : _puntosCompostaje.length,
            itemBuilder: (context, indice) =>
                _construirTarjetaPuntoCompostaje(_puntosCompostaje[indice]),
          ),
      ],
    );
  }

  Widget _construirTarjetaPuntoCompostaje(dynamic punto) {
    final datosPunto = punto as Map<String, dynamic>;
    final nombrePunto = datosPunto['nombre'] ??
        datosPunto['name'] ??
        datosPunto['titulo'] ??
        'Punto de compostaje';
    final direccionPunto = datosPunto['direccion'] ??
        datosPunto['address'] ??
        datosPunto['ubicacion'] ??
        '';
    final estadoPunto =
        datosPunto['estado'] ?? datosPunto['status'] ?? 'activo';
    final capacidadPunto = datosPunto['capacidad'] ??
        datosPunto['capacity'] ??
        datosPunto['nivel'] ??
        '';
    final distanciaPunto = datosPunto['distancia'] ?? datosPunto['distance'] ?? '';

    Color colorEstado;
    IconData iconoEstado;
    switch (estadoPunto.toString().toLowerCase()) {
      case 'activo':
      case 'disponible':
      case 'active':
        colorEstado = Colors.green;
        iconoEstado = Icons.check_circle;
        break;
      case 'lleno':
      case 'full':
        colorEstado = Colors.red;
        iconoEstado = Icons.error;
        break;
      case 'mantenimiento':
      case 'maintenance':
        colorEstado = Colors.orange;
        iconoEstado = Icons.build;
        break;
      default:
        colorEstado = Colors.grey;
        iconoEstado = Icons.info;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.green.shade100,
          child: const Icon(Icons.compost, color: Colors.green),
        ),
        title: Text(nombrePunto),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (direccionPunto.isNotEmpty)
              Row(
                children: [
                  const Icon(Icons.location_on, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      direccionPunto,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                ],
              ),
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(iconoEstado, size: 14, color: colorEstado),
                const SizedBox(width: 4),
                Text(
                  estadoPunto,
                  style: TextStyle(fontSize: 12, color: colorEstado),
                ),
                if (capacidadPunto.toString().isNotEmpty) ...[
                  const SizedBox(width: 12),
                  const Icon(Icons.straighten, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(
                    'Capacidad: $capacidadPunto%',
                    style: const TextStyle(fontSize: 12),
                  ),
                ],
              ],
            ),
          ],
        ),
        trailing: distanciaPunto.toString().isNotEmpty
            ? Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.directions_walk,
                      size: 16, color: Colors.grey),
                  Text(
                    distanciaPunto.toString(),
                    style: const TextStyle(fontSize: 11),
                  ),
                ],
              )
            : const Icon(Icons.chevron_right),
        isThreeLine: true,
        onTap: () {
          // TODO: Ver detalle del punto de compostaje
        },
      ),
    );
  }
}
