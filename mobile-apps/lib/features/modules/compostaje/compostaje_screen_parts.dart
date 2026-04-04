part of 'compostaje_screen.dart';

extension _CompostajeScreenParts on _CompostajeScreenState {
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
            ...items.map(
              (item) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.circle, size: 8, color: Colors.grey.shade400),
                    const SizedBox(width: 8),
                    Expanded(child: Text(item)),
                  ],
                ),
              ),
            ),
          ],
        ),
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
}

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
      FlavorSnackbar.showError(context, 'Selecciona un punto de compostaje');
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
          FlavorSnackbar.showSuccess(context, 'Aportacion registrada correctamente');
          widget.onAportacionRegistrada();
          Navigator.pop(context);
        } else {
          FlavorSnackbar.showError(context, respuesta.error ?? 'Error al registrar');
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _guardando = false);
        FlavorSnackbar.showError(context, 'Error: $e');
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
            TextFormField(
              controller: _kilosController,
              decoration: const InputDecoration(
                labelText: 'Cantidad (kg) *',
                hintText: 'Ej: 2.5',
                prefixIcon: Icon(Icons.scale),
                suffixText: 'kg',
                border: OutlineInputBorder(),
              ),
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
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
            FilledButton.icon(
              onPressed: _guardando ? null : _guardarAportacion,
              icon: _guardando ? const FlavorInlineSpinner() : const Icon(Icons.check),
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
          ? const FlavorLoadingState()
          : _error != null
              ? FlavorErrorState(
                  message: _error!,
                  onRetry: _cargarHistorial,
                )
              : _aportaciones.isEmpty
                  ? const FlavorEmptyState(
                      icon: Icons.history,
                      title: 'No tienes aportaciones registradas',
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
          ? const FlavorEmptyState(
              icon: Icons.location_off,
              title: 'No hay puntos de compostaje',
            )
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
                              final url = MapLaunchHelper.buildConfiguredMapUri(
                                latitud,
                                longitud,
                                query: direccion.isNotEmpty ? direccion : nombre,
                              );
                              if (!context.mounted) return;
                              await FlavorUrlLauncher.openExternalUri(
                                context,
                                url,
                                errorMessage: 'No se puede abrir el mapa',
                              );
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
          ? const FlavorEmptyState(
              icon: Icons.location_off,
              title: 'No hay puntos de compostaje',
            )
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
