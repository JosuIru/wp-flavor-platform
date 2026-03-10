import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
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
        onPressed: _mostrarFormularioAportacion,
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
                onPressed: _mostrarMapaPuntos,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _construirBotonAccion(
                titulo: 'Historial',
                icono: Icons.history,
                color: Colors.purple,
                onPressed: _mostrarHistorial,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _construirBotonAccion(
                titulo: 'Guia',
                icono: Icons.help_outline,
                color: Colors.teal,
                onPressed: _mostrarGuiaCompostaje,
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
              onPressed: _verTodosPuntos,
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
    final puntoId = datosPunto['id']?.toString() ?? '';
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
        onTap: () => _mostrarDetallePunto(datosPunto),
      ),
    );
  }

  // ========== Acciones ==========

  void _mostrarFormularioAportacion() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => RegistrarAportacionScreen(
          puntos: _puntosCompostaje,
          onAportacionRegistrada: _cargarDashboard,
        ),
      ),
    );
  }

  void _mostrarMapaPuntos() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => MapaPuntosScreen(puntos: _puntosCompostaje),
      ),
    );
  }

  void _mostrarHistorial() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => const HistorialAportacionesScreen(),
      ),
    );
  }

  void _mostrarGuiaCompostaje() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 20),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const Text(
                'Guia de Compostaje',
                style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 20),
              _buildGuiaSeccion(
                titulo: 'Que SI compostar',
                icono: Icons.check_circle,
                color: Colors.green,
                items: [
                  'Restos de frutas y verduras',
                  'Cascaras de huevo',
                  'Posos de cafe y bolsitas de te',
                  'Hojas secas y restos de jardin',
                  'Papel y carton sin tintas',
                  'Servilletas de papel usadas',
                ],
              ),
              const SizedBox(height: 16),
              _buildGuiaSeccion(
                titulo: 'Que NO compostar',
                icono: Icons.cancel,
                color: Colors.red,
                items: [
                  'Carnes y pescados',
                  'Productos lacteos',
                  'Aceites y grasas',
                  'Plantas enfermas',
                  'Heces de mascotas',
                  'Materiales sinteticos',
                ],
              ),
              const SizedBox(height: 16),
              _buildGuiaSeccion(
                titulo: 'Consejos',
                icono: Icons.lightbulb,
                color: Colors.amber,
                items: [
                  'Trocea los residuos para acelerar el proceso',
                  'Alterna capas verdes (humedad) y marrones (secos)',
                  'Mantén la humedad sin que quede encharcado',
                  'Voltea periodicamente para airear',
                ],
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Entendido'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildGuiaSeccion({
    required String titulo,
    required IconData icono,
    required Color color,
    required List<String> items,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icono, color: color),
                const SizedBox(width: 8),
                Text(
                  titulo,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ...items.map((item) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.circle, size: 8, color: Colors.grey.shade400),
                  const SizedBox(width: 8),
                  Expanded(child: Text(item)),
                ],
              ),
            )),
          ],
        ),
      ),
    );
  }

  void _verTodosPuntos() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ListaPuntosScreen(puntos: _puntosCompostaje),
      ),
    );
  }

  void _mostrarDetallePunto(Map<String, dynamic> punto) {
    final nombrePunto = punto['nombre'] ?? punto['name'] ?? 'Punto de compostaje';
    final direccionPunto = punto['direccion'] ?? punto['address'] ?? '';
    final estadoPunto = punto['estado'] ?? punto['status'] ?? 'activo';
    final capacidadPunto = punto['capacidad'] ?? punto['capacity'] ?? '';
    final horarioPunto = punto['horario'] ?? punto['schedule'] ?? '';
    final descripcionPunto = punto['descripcion'] ?? punto['description'] ?? '';
    final responsablePunto = punto['responsable'] ?? punto['encargado'] ?? '';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.6,
        minChildSize: 0.4,
        maxChildSize: 0.9,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 20),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              Row(
                children: [
                  CircleAvatar(
                    radius: 30,
                    backgroundColor: Colors.green.shade100,
                    child: const Icon(Icons.compost, color: Colors.green, size: 32),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          nombrePunto,
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        _buildEstadoBadge(estadoPunto),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              if (direccionPunto.isNotEmpty) ...[
                _buildInfoRow(Icons.location_on, 'Direccion', direccionPunto),
                const SizedBox(height: 12),
              ],
              if (horarioPunto.isNotEmpty) ...[
                _buildInfoRow(Icons.schedule, 'Horario', horarioPunto),
                const SizedBox(height: 12),
              ],
              if (capacidadPunto.toString().isNotEmpty) ...[
                _buildInfoRow(Icons.straighten, 'Capacidad actual', '$capacidadPunto%'),
                const SizedBox(height: 8),
                LinearProgressIndicator(
                  value: (double.tryParse(capacidadPunto.toString()) ?? 0) / 100,
                  backgroundColor: Colors.grey.shade200,
                  valueColor: AlwaysStoppedAnimation<Color>(
                    _getCapacidadColor(capacidadPunto),
                  ),
                ),
                const SizedBox(height: 12),
              ],
              if (responsablePunto.isNotEmpty) ...[
                _buildInfoRow(Icons.person, 'Responsable', responsablePunto),
                const SizedBox(height: 12),
              ],
              if (descripcionPunto.isNotEmpty) ...[
                const Text(
                  'Descripcion',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Text(descripcionPunto),
                const SizedBox(height: 16),
              ],
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _mostrarFormularioAportacion();
                  },
                  icon: const Icon(Icons.add),
                  label: const Text('Registrar aportacion aqui'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEstadoBadge(String estado) {
    Color color;
    switch (estado.toString().toLowerCase()) {
      case 'activo':
      case 'disponible':
      case 'active':
        color = Colors.green;
        break;
      case 'lleno':
      case 'full':
        color = Colors.red;
        break;
      case 'mantenimiento':
      case 'maintenance':
        color = Colors.orange;
        break;
      default:
        color = Colors.grey;
    }
    return Container(
      margin: const EdgeInsets.only(top: 4),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.2),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        estado,
        style: TextStyle(color: color, fontWeight: FontWeight.w500, fontSize: 12),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 20, color: Colors.grey),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
              Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
            ],
          ),
        ),
      ],
    );
  }

  Color _getCapacidadColor(dynamic capacidad) {
    final valor = double.tryParse(capacidad.toString()) ?? 0;
    if (valor < 50) return Colors.green;
    if (valor < 80) return Colors.orange;
    return Colors.red;
  }
}

/// Pantalla para registrar una nueva aportacion
class RegistrarAportacionScreen extends ConsumerStatefulWidget {
  final List<dynamic> puntos;
  final VoidCallback onAportacionRegistrada;

  const RegistrarAportacionScreen({
    super.key,
    required this.puntos,
    required this.onAportacionRegistrada,
  });

  @override
  ConsumerState<RegistrarAportacionScreen> createState() => _RegistrarAportacionScreenState();
}

class _RegistrarAportacionScreenState extends ConsumerState<RegistrarAportacionScreen> {
  final _formKey = GlobalKey<FormState>();
  final _kilosController = TextEditingController();
  final _notasController = TextEditingController();
  String? _puntoSeleccionado;
  String _tipoResiduo = 'organico';
  bool _guardando = false;

  final List<Map<String, String>> _tiposResiduos = [
    {'value': 'organico', 'label': 'Organico (frutas, verduras)'},
    {'value': 'jardin', 'label': 'Jardin (hojas, ramas)'},
    {'value': 'cafe', 'label': 'Posos de cafe/te'},
    {'value': 'papel', 'label': 'Papel/carton sin tintas'},
    {'value': 'otros', 'label': 'Otros'},
  ];

  @override
  void dispose() {
    _kilosController.dispose();
    _notasController.dispose();
    super.dispose();
  }

  Future<void> _guardarAportacion() async {
    if (!_formKey.currentState!.validate()) return;
    if (_puntoSeleccionado == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Selecciona un punto de compostaje')),
      );
      return;
    }

    setState(() => _guardando = true);

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/compostaje/aportaciones', data: {
        'punto_id': _puntoSeleccionado,
        'kilos': double.tryParse(_kilosController.text) ?? 0,
        'tipo_residuo': _tipoResiduo,
        'notas': _notasController.text.trim(),
        'fecha': DateTime.now().toIso8601String(),
      });

      if (mounted) {
        setState(() => _guardando = false);

        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Aportacion registrada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          widget.onAportacionRegistrada();
          Navigator.pop(context);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al registrar'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _guardando = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Registrar aportacion'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Punto de compostaje
            DropdownButtonFormField<String>(
              value: _puntoSeleccionado,
              decoration: const InputDecoration(
                labelText: 'Punto de compostaje *',
                prefixIcon: Icon(Icons.location_on),
                border: OutlineInputBorder(),
              ),
              items: widget.puntos.map((punto) {
                final p = punto as Map<String, dynamic>;
                return DropdownMenuItem(
                  value: p['id']?.toString(),
                  child: Text(p['nombre'] ?? p['name'] ?? 'Punto ${p['id']}'),
                );
              }).toList(),
              onChanged: (value) {
                setState(() => _puntoSeleccionado = value);
              },
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Selecciona un punto';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Tipo de residuo
            DropdownButtonFormField<String>(
              value: _tipoResiduo,
              decoration: const InputDecoration(
                labelText: 'Tipo de residuo *',
                prefixIcon: Icon(Icons.category),
                border: OutlineInputBorder(),
              ),
              items: _tiposResiduos.map((tipo) {
                return DropdownMenuItem(
                  value: tipo['value'],
                  child: Text(tipo['label']!),
                );
              }).toList(),
              onChanged: (value) {
                if (value != null) {
                  setState(() => _tipoResiduo = value);
                }
              },
            ),
            const SizedBox(height: 16),

            // Kilos
            TextFormField(
              controller: _kilosController,
              decoration: const InputDecoration(
                labelText: 'Cantidad (kg) *',
                hintText: 'Ej: 2.5',
                prefixIcon: Icon(Icons.scale),
                suffixText: 'kg',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.numberWithOptions(decimal: true),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'La cantidad es obligatoria';
                }
                final num = double.tryParse(value);
                if (num == null || num <= 0) {
                  return 'Ingresa una cantidad valida';
                }
                if (num > 50) {
                  return 'Maximo 50 kg por aportacion';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Notas
            TextFormField(
              controller: _notasController,
              decoration: const InputDecoration(
                labelText: 'Notas (opcional)',
                hintText: 'Observaciones adicionales...',
                prefixIcon: Icon(Icons.notes),
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 24),

            // Boton guardar
            FilledButton.icon(
              onPressed: _guardando ? null : _guardarAportacion,
              icon: _guardando
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.check),
              label: Text(_guardando ? 'Guardando...' : 'Registrar aportacion'),
              style: FilledButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Pantalla de historial de aportaciones
class HistorialAportacionesScreen extends ConsumerStatefulWidget {
  const HistorialAportacionesScreen({super.key});

  @override
  ConsumerState<HistorialAportacionesScreen> createState() => _HistorialAportacionesScreenState();
}

class _HistorialAportacionesScreenState extends ConsumerState<HistorialAportacionesScreen> {
  List<dynamic> _aportaciones = [];
  bool _cargando = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _cargarHistorial();
  }

  Future<void> _cargarHistorial() async {
    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/compostaje/mis-aportaciones');

      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _aportaciones = respuesta.data!['aportaciones'] ??
              respuesta.data!['items'] ??
              respuesta.data!['data'] ??
              [];
          _cargando = false;
        });
      } else {
        setState(() {
          _error = respuesta.error ?? 'Error al cargar historial';
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mis aportaciones'),
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_error!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarHistorial,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _aportaciones.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.history, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No tienes aportaciones registradas'),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarHistorial,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _aportaciones.length,
                        itemBuilder: (context, index) {
                          final aportacion = _aportaciones[index] as Map<String, dynamic>;
                          final kilos = aportacion['kilos'] ?? aportacion['cantidad'] ?? 0;
                          final fecha = aportacion['fecha'] ?? aportacion['date'] ?? '';
                          final punto = aportacion['punto_nombre'] ?? aportacion['punto'] ?? '';
                          final tipo = aportacion['tipo_residuo'] ?? aportacion['tipo'] ?? '';

                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            child: ListTile(
                              leading: CircleAvatar(
                                backgroundColor: Colors.green.shade100,
                                child: const Icon(Icons.compost, color: Colors.green),
                              ),
                              title: Text('$kilos kg'),
                              subtitle: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  if (punto.isNotEmpty) Text(punto),
                                  Row(
                                    children: [
                                      if (tipo.isNotEmpty) ...[
                                        Text(tipo, style: TextStyle(color: Colors.grey.shade600)),
                                        const Text(' - '),
                                      ],
                                      Text(fecha, style: TextStyle(color: Colors.grey.shade600)),
                                    ],
                                  ),
                                ],
                              ),
                              isThreeLine: punto.isNotEmpty,
                            ),
                          );
                        },
                      ),
                    ),
    );
  }
}

/// Pantalla de mapa de puntos (simplificada)
class MapaPuntosScreen extends StatelessWidget {
  final List<dynamic> puntos;

  const MapaPuntosScreen({super.key, required this.puntos});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mapa de puntos'),
      ),
      body: puntos.isEmpty
          ? const Center(child: Text('No hay puntos de compostaje'))
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: puntos.length,
              itemBuilder: (context, index) {
                final punto = puntos[index] as Map<String, dynamic>;
                final nombre = punto['nombre'] ?? punto['name'] ?? 'Punto ${index + 1}';
                final direccion = punto['direccion'] ?? punto['address'] ?? '';
                final lat = punto['latitud'] ?? punto['lat'];
                final lng = punto['longitud'] ?? punto['lng'];

                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    leading: const CircleAvatar(
                      backgroundColor: Colors.green,
                      child: Icon(Icons.location_on, color: Colors.white),
                    ),
                    title: Text(nombre),
                    subtitle: direccion.isNotEmpty ? Text(direccion) : null,
                    trailing: (lat != null && lng != null)
                        ? IconButton(
                            icon: const Icon(Icons.directions),
                            onPressed: () async {
                              final latitud = double.tryParse(lat.toString()) ?? 0;
                              final longitud = double.tryParse(lng.toString()) ?? 0;
                              final url = Uri.parse(
                                'https://www.openstreetmap.org/?mlat=$latitud&mlon=$longitud#map=18/$latitud/$longitud',
                              );
                              if (await canLaunchUrl(url)) {
                                await launchUrl(url, mode: LaunchMode.externalApplication);
                              } else {
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('No se puede abrir el mapa')),
                                  );
                                }
                              }
                            },
                          )
                        : null,
                  ),
                );
              },
            ),
    );
  }
}

/// Pantalla de lista de todos los puntos
class ListaPuntosScreen extends StatelessWidget {
  final List<dynamic> puntos;

  const ListaPuntosScreen({super.key, required this.puntos});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Todos los puntos'),
      ),
      body: puntos.isEmpty
          ? const Center(child: Text('No hay puntos de compostaje'))
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: puntos.length,
              itemBuilder: (context, index) {
                final punto = puntos[index] as Map<String, dynamic>;
                final nombre = punto['nombre'] ?? punto['name'] ?? 'Punto ${index + 1}';
                final direccion = punto['direccion'] ?? punto['address'] ?? '';
                final estado = punto['estado'] ?? punto['status'] ?? 'activo';
                final capacidad = punto['capacidad'] ?? punto['capacity'] ?? '';

                Color estadoColor;
                switch (estado.toString().toLowerCase()) {
                  case 'activo':
                  case 'disponible':
                    estadoColor = Colors.green;
                    break;
                  case 'lleno':
                    estadoColor = Colors.red;
                    break;
                  case 'mantenimiento':
                    estadoColor = Colors.orange;
                    break;
                  default:
                    estadoColor = Colors.grey;
                }

                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: Colors.green.shade100,
                      child: const Icon(Icons.compost, color: Colors.green),
                    ),
                    title: Text(nombre),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (direccion.isNotEmpty) Text(direccion),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: estadoColor.withOpacity(0.2),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                estado,
                                style: TextStyle(color: estadoColor, fontSize: 11),
                              ),
                            ),
                            if (capacidad.toString().isNotEmpty) ...[
                              const SizedBox(width: 8),
                              Text('$capacidad%', style: const TextStyle(fontSize: 12)),
                            ],
                          ],
                        ),
                      ],
                    ),
                    isThreeLine: direccion.isNotEmpty,
                  ),
                );
              },
            ),
    );
  }
}
