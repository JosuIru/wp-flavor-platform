part of 'empresarial_screen.dart';

class ComponenteDetalleScreen extends ConsumerStatefulWidget {
  final dynamic componenteId;
  const ComponenteDetalleScreen({super.key, required this.componenteId});

  @override
  ConsumerState<ComponenteDetalleScreen> createState() => _ComponenteDetalleScreenState();
}

class _ComponenteDetalleScreenState extends ConsumerState<ComponenteDetalleScreen> {
  Map<String, dynamic>? _datosComponente;
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
      final respuesta = await clienteApi.get('/empresarial/componente/${widget.componenteId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosComponente = respuesta.data!['data'] ?? respuesta.data!;
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar componente';
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
      appBar: AppBar(title: const Text('Detalle del Componente')),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDetalle,
                  icon: Icons.widgets_outlined,
                )
              : _datosComponente == null
                  ? const FlavorEmptyState(
                      icon: Icons.widgets_outlined,
                      title: 'No se encontraron datos',
                    )
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        Text(
                          _datosComponente!['nombre'] ?? _datosComponente!['titulo'] ?? 'Componente',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 16),
                        if (_datosComponente!['descripcion'] != null)
                          Text(_datosComponente!['descripcion']),
                        const SizedBox(height: 24),
                        FilledButton.icon(
                          onPressed: () => _configurarComponente(context),
                          icon: const Icon(Icons.settings),
                          label: const Text('Configurar'),
                        ),
                      ],
                    ),
    );
  }

  void _configurarComponente(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.settings, color: Colors.indigo),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'Configurar Componente',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 20),
            SwitchListTile(
              title: const Text('Componente activo'),
              subtitle: const Text('Habilitar o deshabilitar'),
              value: _datosComponente?['estado'] == 'activo',
              onChanged: (value) async {
                Navigator.pop(context);
                await _cambiarEstadoComponente(value);
              },
            ),
            ListTile(
              leading: const Icon(Icons.notifications),
              title: const Text('Notificaciones'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () {
                Navigator.pop(context);
                _configurarNotificacionesComponente();
              },
            ),
            ListTile(
              leading: const Icon(Icons.security),
              title: const Text('Permisos'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () {
                Navigator.pop(context);
                _configurarPermisosComponente();
              },
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Future<void> _cambiarEstadoComponente(bool activo) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.put(
        '/empresarial/componente/${widget.componenteId}',
        data: {'estado': activo ? 'activo' : 'inactivo'},
      );

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Componente ${activo ? 'activado' : 'desactivado'}'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDetalle();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _configurarNotificacionesComponente() {
    bool notificacionesActivas = _datosComponente?['notificaciones_activas'] ?? true;
    bool notificarEmail = _datosComponente?['notificar_email'] ?? false;
    bool notificarPush = _datosComponente?['notificar_push'] ?? true;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.notifications, color: Colors.indigo),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Configurar Notificaciones',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              SwitchListTile(
                title: const Text('Notificaciones activas'),
                subtitle: const Text('Recibir notificaciones de este componente'),
                value: notificacionesActivas,
                onChanged: (value) {
                  setModalState(() => notificacionesActivas = value);
                },
              ),
              SwitchListTile(
                title: const Text('Notificar por email'),
                subtitle: const Text('Recibir notificaciones por correo'),
                value: notificarEmail,
                onChanged: notificacionesActivas
                    ? (value) {
                        setModalState(() => notificarEmail = value);
                      }
                    : null,
              ),
              SwitchListTile(
                title: const Text('Notificaciones push'),
                subtitle: const Text('Recibir notificaciones en el dispositivo'),
                value: notificarPush,
                onChanged: notificacionesActivas
                    ? (value) {
                        setModalState(() => notificarPush = value);
                      }
                    : null,
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: () async {
                    Navigator.pop(context);
                    await _guardarConfiguracionNotificaciones(
                      notificacionesActivas,
                      notificarEmail,
                      notificarPush,
                    );
                  },
                  child: const Text('Guardar'),
                ),
              ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _guardarConfiguracionNotificaciones(
    bool activas,
    bool email,
    bool push,
  ) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.put(
        '/empresarial/componente/${widget.componenteId}/notificaciones',
        data: {
          'notificaciones_activas': activas,
          'notificar_email': email,
          'notificar_push': push,
        },
      );

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Configuracion de notificaciones guardada'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDetalle();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al guardar'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _configurarPermisosComponente() {
    final permisosActuales = (_datosComponente?['permisos'] as List<dynamic>?) ?? [];
    final permisosDisponibles = [
      {'id': 'ver', 'nombre': 'Ver', 'descripcion': 'Ver informacion del componente'},
      {'id': 'editar', 'nombre': 'Editar', 'descripcion': 'Modificar datos'},
      {'id': 'eliminar', 'nombre': 'Eliminar', 'descripcion': 'Eliminar registros'},
      {'id': 'exportar', 'nombre': 'Exportar', 'descripcion': 'Exportar datos'},
      {'id': 'admin', 'nombre': 'Administrar', 'descripcion': 'Acceso total'},
    ];
    final permisosSeleccionados = Set<String>.from(
      permisosActuales.map((p) => p.toString()),
    );

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => DraggableScrollableSheet(
          initialChildSize: 0.6,
          expand: false,
          builder: (context, scrollController) => Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.security, color: Colors.indigo),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Text(
                        'Configurar Permisos',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                const Text(
                  'Selecciona los permisos para este componente',
                  style: TextStyle(color: Colors.grey),
                ),
                const SizedBox(height: 16),
                Expanded(
                  child: ListView.builder(
                    controller: scrollController,
                    itemCount: permisosDisponibles.length,
                    itemBuilder: (context, index) {
                      final permiso = permisosDisponibles[index];
                      final permisoId = permiso['id'] as String;
                      final seleccionado = permisosSeleccionados.contains(permisoId);

                      return CheckboxListTile(
                        title: Text(permiso['nombre'] as String),
                        subtitle: Text(permiso['descripcion'] as String),
                        value: seleccionado,
                        onChanged: (value) {
                          setModalState(() {
                            if (value == true) {
                              permisosSeleccionados.add(permisoId);
                            } else {
                              permisosSeleccionados.remove(permisoId);
                            }
                          });
                        },
                      );
                    },
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: () async {
                      Navigator.pop(context);
                      await _guardarPermisos(permisosSeleccionados.toList());
                    },
                    child: const Text('Guardar permisos'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _guardarPermisos(List<String> permisos) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.put(
        '/empresarial/componente/${widget.componenteId}/permisos',
        data: {'permisos': permisos},
      );

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Permisos actualizados correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDetalle();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al guardar permisos'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}

/// Pantalla de facturas empresariales
class FacturasEmpresarialScreen extends ConsumerStatefulWidget {
  const FacturasEmpresarialScreen({super.key});

  @override
  ConsumerState<FacturasEmpresarialScreen> createState() => _FacturasEmpresarialScreenState();
}

class _FacturasEmpresarialScreenState extends ConsumerState<FacturasEmpresarialScreen> {
  List<dynamic> _facturas = [];
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarFacturas();
  }

  Future<void> _cargarFacturas() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/empresarial/facturas');

      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _facturas = respuesta.data!['facturas'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar facturas';
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _mensajeError = e.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Facturas'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: _crearNuevaFactura,
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarFacturas,
                  icon: Icons.receipt_long,
                )
              : _facturas.isEmpty
                  ? FlavorEmptyState(
                      icon: Icons.receipt_long_outlined,
                      title: 'No hay facturas',
                      action: FilledButton.icon(
                        onPressed: _crearNuevaFactura,
                        icon: const Icon(Icons.add),
                        label: const Text('Crear factura'),
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarFacturas,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _facturas.length,
                        itemBuilder: (context, index) => _construirTarjetaFactura(_facturas[index]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaFactura(dynamic factura) {
    final datosFactura = factura as Map<String, dynamic>;
    final numero = datosFactura['numero'] ?? datosFactura['numero_factura'] ?? '';
    final cliente = datosFactura['cliente'] ?? datosFactura['cliente_nombre'] ?? '';
    final total = datosFactura['total'] ?? datosFactura['importe'] ?? 0;
    final estado = datosFactura['estado'] ?? 'pendiente';
    final fecha = datosFactura['fecha'] ?? datosFactura['fecha_emision'] ?? '';

    Color colorEstado;
    switch (estado.toString().toLowerCase()) {
      case 'pagada':
      case 'cobrada':
        colorEstado = Colors.green;
        break;
      case 'pendiente':
        colorEstado = Colors.orange;
        break;
      case 'vencida':
        colorEstado = Colors.red;
        break;
      default:
        colorEstado = Colors.grey;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: colorEstado.withOpacity(0.1),
          child: Icon(Icons.receipt, color: colorEstado),
        ),
        title: Text(numero.toString()),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(cliente.toString()),
            const SizedBox(height: 4),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: colorEstado.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    estado.toString().toUpperCase(),
                    style: TextStyle(fontSize: 10, color: colorEstado, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 8),
                Text(fecha.toString(), style: const TextStyle(fontSize: 12)),
              ],
            ),
          ],
        ),
        trailing: Text(
          '\$$total',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
        onTap: () => _verDetalleFactura(datosFactura),
      ),
    );
  }

  void _verDetalleFactura(Map<String, dynamic> factura) {
    final facturaId = factura['id'];
    if (facturaId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => FacturaEmpresarialDetalleScreen(facturaId: facturaId),
        ),
      ).then((_) => _cargarFacturas());
    }
  }

  void _crearNuevaFactura() {
    final clienteController = TextEditingController();
    final conceptoController = TextEditingController();
    final importeController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: MediaQuery.of(context).viewInsets.bottom + 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.receipt_long, color: Colors.green),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'Nueva Factura',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const SizedBox(height: 16),
            TextField(
              controller: clienteController,
              decoration: const InputDecoration(
                labelText: 'Cliente',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.person),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: conceptoController,
              decoration: const InputDecoration(
                labelText: 'Concepto',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.description),
              ),
              maxLines: 2,
            ),
            const SizedBox(height: 12),
            TextField(
              controller: importeController,
              decoration: const InputDecoration(
                labelText: 'Importe',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.euro),
              ),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: () async {
                  if (clienteController.text.isEmpty || importeController.text.isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Complete los campos requeridos')),
                    );
                    return;
                  }
                  Navigator.pop(context);
                  await _guardarNuevaFactura(
                    clienteController.text,
                    conceptoController.text,
                    importeController.text,
                  );
                },
                icon: const Icon(Icons.save),
                label: const Text('Crear factura'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _guardarNuevaFactura(String cliente, String concepto, String importe) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/empresarial/facturas', data: {
        'cliente': cliente,
        'concepto': concepto,
        'importe': double.tryParse(importe) ?? 0,
      });

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Factura creada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarFacturas();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al crear factura'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}

/// Pantalla de detalle de factura empresarial
class FacturaEmpresarialDetalleScreen extends ConsumerStatefulWidget {
  final dynamic facturaId;
  const FacturaEmpresarialDetalleScreen({super.key, required this.facturaId});

  @override
  ConsumerState<FacturaEmpresarialDetalleScreen> createState() => _FacturaEmpresarialDetalleScreenState();
}

class _FacturaEmpresarialDetalleScreenState extends ConsumerState<FacturaEmpresarialDetalleScreen> {
  Map<String, dynamic>? _datosFactura;
  bool _cargando = true;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() => _cargando = true);

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/empresarial/facturas/${widget.facturaId}');

      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosFactura = respuesta.data!['data'] ?? respuesta.data!;
          _cargando = false;
        });
      } else {
        setState(() => _cargando = false);
      }
    } catch (e) {
      setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detalle de Factura')),
      body: _cargando
          ? const FlavorLoadingState()
          : _datosFactura == null
              ? const FlavorEmptyState(
                  icon: Icons.receipt_long_outlined,
                  title: 'No se encontraron datos',
                )
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Factura ${_datosFactura!['numero'] ?? ''}',
                              style: Theme.of(context).textTheme.titleLarge,
                            ),
                            const SizedBox(height: 16),
                            _buildInfoRow('Cliente', _datosFactura!['cliente']?.toString() ?? ''),
                            _buildInfoRow('Concepto', _datosFactura!['concepto']?.toString() ?? ''),
                            _buildInfoRow('Importe', '\$${_datosFactura!['total'] ?? 0}'),
                            _buildInfoRow('Estado', _datosFactura!['estado']?.toString() ?? ''),
                            _buildInfoRow('Fecha', _datosFactura!['fecha']?.toString() ?? ''),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(label, style: const TextStyle(fontWeight: FontWeight.bold)),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}

/// Pantalla de clientes empresariales
class ClientesEmpresarialScreen extends ConsumerStatefulWidget {
  const ClientesEmpresarialScreen({super.key});

  @override
  ConsumerState<ClientesEmpresarialScreen> createState() => _ClientesEmpresarialScreenState();
}

class _ClientesEmpresarialScreenState extends ConsumerState<ClientesEmpresarialScreen> {
  List<dynamic> _clientes = [];
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarClientes();
  }

  Future<void> _cargarClientes() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/empresarial/clientes');

      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _clientes = respuesta.data!['clientes'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar clientes';
          _cargando = false;
        });
      }
    } catch (e) {
      setState(() {
        _mensajeError = e.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Clientes'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: _crearNuevoCliente,
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarClientes,
                  icon: Icons.people,
                )
              : _clientes.isEmpty
                  ? FlavorEmptyState(
                      icon: Icons.people_outline,
                      title: 'No hay clientes',
                      action: FilledButton.icon(
                        onPressed: _crearNuevoCliente,
                        icon: const Icon(Icons.add),
                        label: const Text('Agregar cliente'),
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarClientes,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _clientes.length,
                        itemBuilder: (context, index) => _construirTarjetaCliente(_clientes[index]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaCliente(dynamic cliente) {
    final datosCliente = cliente as Map<String, dynamic>;
    final nombre = datosCliente['nombre'] ?? datosCliente['name'] ?? '';
    final email = datosCliente['email'] ?? datosCliente['correo'] ?? '';
    final telefono = datosCliente['telefono'] ?? datosCliente['phone'] ?? '';
    final empresa = datosCliente['empresa'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.blue.shade100,
          child: Text(
            nombre.toString().isNotEmpty ? nombre.toString()[0].toUpperCase() : '?',
            style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue.shade700),
          ),
        ),
        title: Text(nombre.toString()),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (empresa.toString().isNotEmpty) Text(empresa.toString()),
            if (email.toString().isNotEmpty)
              Row(
                children: [
                  const Icon(Icons.email, size: 14),
                  const SizedBox(width: 4),
                  Expanded(child: Text(email.toString(), style: const TextStyle(fontSize: 12))),
                ],
              ),
            if (telefono.toString().isNotEmpty)
              Row(
                children: [
                  const Icon(Icons.phone, size: 14),
                  const SizedBox(width: 4),
                  Text(telefono.toString(), style: const TextStyle(fontSize: 12)),
                ],
              ),
          ],
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: () => _verDetalleCliente(datosCliente),
      ),
    );
  }

  void _verDetalleCliente(Map<String, dynamic> cliente) {
    final clienteId = cliente['id'];
    if (clienteId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ClienteEmpresarialDetalleScreen(clienteId: clienteId),
        ),
      ).then((_) => _cargarClientes());
    }
  }

  void _crearNuevoCliente() {
    final nombreController = TextEditingController();
    final emailController = TextEditingController();
    final telefonoController = TextEditingController();
    final empresaController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: MediaQuery.of(context).viewInsets.bottom + 20,
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.person_add, color: Colors.blue),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Nuevo Cliente',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              TextField(
                controller: nombreController,
                decoration: const InputDecoration(
                  labelText: 'Nombre *',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.person),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: empresaController,
                decoration: const InputDecoration(
                  labelText: 'Empresa',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.business),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: emailController,
                decoration: const InputDecoration(
                  labelText: 'Email',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.email),
                ),
                keyboardType: TextInputType.emailAddress,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: telefonoController,
                decoration: const InputDecoration(
                  labelText: 'Telefono',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.phone),
                ),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () async {
                    if (nombreController.text.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('El nombre es requerido')),
                      );
                      return;
                    }
                    Navigator.pop(context);
                    await _guardarNuevoCliente(
                      nombreController.text,
                      empresaController.text,
                      emailController.text,
                      telefonoController.text,
                    );
                  },
                  icon: const Icon(Icons.save),
                  label: const Text('Guardar cliente'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _guardarNuevoCliente(
    String nombre,
    String empresa,
    String email,
    String telefono,
  ) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/empresarial/clientes', data: {
        'nombre': nombre,
        'empresa': empresa,
        'email': email,
        'telefono': telefono,
      });

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Cliente creado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarClientes();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al crear cliente'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}

/// Pantalla de detalle de cliente empresarial
class ClienteEmpresarialDetalleScreen extends ConsumerStatefulWidget {
  final dynamic clienteId;
  const ClienteEmpresarialDetalleScreen({super.key, required this.clienteId});

  @override
  ConsumerState<ClienteEmpresarialDetalleScreen> createState() => _ClienteEmpresarialDetalleScreenState();
}

class _ClienteEmpresarialDetalleScreenState extends ConsumerState<ClienteEmpresarialDetalleScreen> {
  Map<String, dynamic>? _datosCliente;
  bool _cargando = true;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() => _cargando = true);

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/empresarial/clientes/${widget.clienteId}');

      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosCliente = respuesta.data!['data'] ?? respuesta.data!;
          _cargando = false;
        });
      } else {
        setState(() => _cargando = false);
      }
    } catch (e) {
      setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detalle del Cliente')),
      body: _cargando
          ? const FlavorLoadingState()
          : _datosCliente == null
              ? const FlavorEmptyState(
                  icon: Icons.people_outline,
                  title: 'No se encontraron datos',
                )
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                CircleAvatar(
                                  radius: 30,
                                  backgroundColor: Colors.blue.shade100,
                                  child: Text(
                                    (_datosCliente!['nombre']?.toString() ?? '?')[0].toUpperCase(),
                                    style: TextStyle(
                                      fontSize: 24,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.blue.shade700,
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 16),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        _datosCliente!['nombre']?.toString() ?? '',
                                        style: Theme.of(context).textTheme.titleLarge,
                                      ),
                                      if (_datosCliente!['empresa'] != null)
                                        Text(
                                          _datosCliente!['empresa'].toString(),
                                          style: TextStyle(color: Colors.grey.shade600),
                                        ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 24),
                            _buildInfoRow(Icons.email, 'Email', _datosCliente!['email']?.toString() ?? '-'),
                            _buildInfoRow(Icons.phone, 'Telefono', _datosCliente!['telefono']?.toString() ?? '-'),
                            _buildInfoRow(Icons.location_on, 'Direccion', _datosCliente!['direccion']?.toString() ?? '-'),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
              Text(value),
            ],
          ),
        ],
      ),
    );
  }
}
