import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class ClientesScreen extends ConsumerStatefulWidget {
  const ClientesScreen({super.key});

  @override
  ConsumerState<ClientesScreen> createState() => _ClientesScreenState();
}

class _ClientesScreenState extends ConsumerState<ClientesScreen> {
  List<dynamic> _listaClientes = [];
  bool _cargandoDatos = true;
  String? _mensajeError;
  String _terminoBusqueda = '';

  @override
  void initState() {
    super.initState();
    _cargarClientes();
  }

  Future<void> _cargarClientes() async {
    setState(() {
      _cargandoDatos = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/clientes');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaClientes = respuesta.data!['clientes'] ??
              respuesta.data!['items'] ??
              respuesta.data!['data'] ??
              [];
          _cargandoDatos = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar clientes';
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

  List<dynamic> get _clientesFiltrados {
    if (_terminoBusqueda.isEmpty) return _listaClientes;
    return _listaClientes.where((cliente) {
      final datosCliente = cliente as Map<String, dynamic>;
      final nombreCliente = (datosCliente['nombre'] ??
              datosCliente['name'] ??
              datosCliente['razon_social'] ??
              '')
          .toString()
          .toLowerCase();
      final emailCliente =
          (datosCliente['email'] ?? '').toString().toLowerCase();
      final telefonoCliente =
          (datosCliente['telefono'] ?? datosCliente['phone'] ?? '').toString();
      final busquedaMinusculas = _terminoBusqueda.toLowerCase();
      return nombreCliente.contains(busquedaMinusculas) ||
          emailCliente.contains(busquedaMinusculas) ||
          telefonoCliente.contains(busquedaMinusculas);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('CRM - Clientes'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarClientes,
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Buscar cliente...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                filled: true,
                fillColor: Colors.grey.shade100,
              ),
              onChanged: (valor) {
                setState(() {
                  _terminoBusqueda = valor;
                });
              },
            ),
          ),
          Expanded(
            child: _cargandoDatos
                ? const Center(child: CircularProgressIndicator())
                : _mensajeError != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.people,
                                size: 64, color: Colors.grey),
                            const SizedBox(height: 16),
                            Text(_mensajeError!),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _cargarClientes,
                              child: const Text('Reintentar'),
                            ),
                          ],
                        ),
                      )
                    : _clientesFiltrados.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.people,
                                    size: 64, color: Colors.grey.shade400),
                                const SizedBox(height: 16),
                                Text(_terminoBusqueda.isEmpty
                                    ? 'No hay clientes registrados'
                                    : 'No se encontraron resultados'),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _cargarClientes,
                            child: ListView.builder(
                              padding:
                                  const EdgeInsets.symmetric(horizontal: 16),
                              itemCount: _clientesFiltrados.length,
                              itemBuilder: (context, indice) =>
                                  _construirTarjetaCliente(
                                      _clientesFiltrados[indice]),
                            ),
                          ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _mostrarFormularioCliente(context),
        child: const Icon(Icons.person_add),
      ),
    );
  }

  Widget _construirTarjetaCliente(dynamic cliente) {
    final datosCliente = cliente as Map<String, dynamic>;
    final idCliente = datosCliente['id'] ?? datosCliente['ID'] ?? 0;
    final nombreCliente = datosCliente['nombre'] ??
        datosCliente['name'] ??
        datosCliente['razon_social'] ??
        'Sin nombre';
    final emailCliente = datosCliente['email'] ?? '';
    final telefonoCliente =
        datosCliente['telefono'] ?? datosCliente['phone'] ?? '';
    final tipoCliente =
        datosCliente['tipo'] ?? datosCliente['type'] ?? datosCliente['categoria'] ?? '';
    final estadoCliente =
        datosCliente['estado'] ?? datosCliente['status'] ?? 'activo';
    final inicialNombre = nombreCliente.isNotEmpty ? nombreCliente[0].toUpperCase() : '?';

    Color colorEstado;
    switch (estadoCliente.toString().toLowerCase()) {
      case 'activo':
      case 'active':
        colorEstado = Colors.green;
        break;
      case 'inactivo':
      case 'inactive':
        colorEstado = Colors.grey;
        break;
      case 'pendiente':
      case 'pending':
        colorEstado = Colors.orange;
        break;
      default:
        colorEstado = Colors.blue;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.blue.shade100,
          child: Text(
            inicialNombre,
            style: TextStyle(
              color: Colors.blue.shade700,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        title: Row(
          children: [
            Expanded(
              child: Text(
                nombreCliente,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            Container(
              width: 10,
              height: 10,
              decoration: BoxDecoration(
                color: colorEstado,
                shape: BoxShape.circle,
              ),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (emailCliente.isNotEmpty)
              Row(
                children: [
                  const Icon(Icons.email, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      emailCliente,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                ],
              ),
            if (telefonoCliente.isNotEmpty)
              Row(
                children: [
                  const Icon(Icons.phone, size: 14, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(
                    telefonoCliente,
                    style: const TextStyle(fontSize: 12),
                  ),
                ],
              ),
            if (tipoCliente.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Chip(
                  label: Text(
                    tipoCliente,
                    style: const TextStyle(fontSize: 10),
                  ),
                  padding: EdgeInsets.zero,
                  materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
              ),
          ],
        ),
        isThreeLine: true,
        trailing: PopupMenuButton<String>(
          onSelected: (valor) => _manejarAccionMenu(valor, datosCliente),
          itemBuilder: (context) => [
            const PopupMenuItem(
              value: 'ver',
              child: Row(
                children: [
                  Icon(Icons.visibility, size: 20),
                  SizedBox(width: 8),
                  Text('Ver detalle'),
                ],
              ),
            ),
            const PopupMenuItem(
              value: 'editar',
              child: Row(
                children: [
                  Icon(Icons.edit, size: 20),
                  SizedBox(width: 8),
                  Text('Editar'),
                ],
              ),
            ),
            if (telefonoCliente.isNotEmpty)
              const PopupMenuItem(
                value: 'llamar',
                child: Row(
                  children: [
                    Icon(Icons.phone, size: 20),
                    SizedBox(width: 8),
                    Text('Llamar'),
                  ],
                ),
              ),
            if (emailCliente.isNotEmpty)
              const PopupMenuItem(
                value: 'email',
                child: Row(
                  children: [
                    Icon(Icons.email, size: 20),
                    SizedBox(width: 8),
                    Text('Enviar email'),
                  ],
                ),
              ),
            const PopupMenuItem(
              value: 'eliminar',
              child: Row(
                children: [
                  Icon(Icons.delete, size: 20, color: Colors.red),
                  SizedBox(width: 8),
                  Text('Eliminar', style: TextStyle(color: Colors.red)),
                ],
              ),
            ),
          ],
        ),
        onTap: () => _verDetalleCliente(context, datosCliente),
      ),
    );
  }

  void _manejarAccionMenu(String accion, Map<String, dynamic> cliente) {
    final nombreCliente = cliente['nombre'] ?? cliente['name'] ?? 'Cliente';
    final emailCliente = cliente['email'] ?? '';
    final telefonoCliente = cliente['telefono'] ?? cliente['phone'] ?? '';
    final idCliente = cliente['id'] ?? cliente['ID'] ?? 0;

    switch (accion) {
      case 'ver':
        _verDetalleCliente(context, cliente);
        break;
      case 'editar':
        _mostrarFormularioCliente(context, cliente: cliente);
        break;
      case 'llamar':
        if (telefonoCliente.isNotEmpty) {
          _realizarLlamada(telefonoCliente);
        }
        break;
      case 'email':
        if (emailCliente.isNotEmpty) {
          _enviarEmail(emailCliente, nombreCliente);
        }
        break;
      case 'eliminar':
        _confirmarEliminarCliente(idCliente, nombreCliente);
        break;
    }
  }

  Future<void> _realizarLlamada(String telefono) async {
    final uri = Uri.parse('tel:$telefono');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No se puede realizar la llamada')),
        );
      }
    }
  }

  Future<void> _enviarEmail(String email, String nombre) async {
    final uri = Uri.parse('mailto:$email?subject=Contacto desde CRM&body=Hola $nombre,\n\n');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No se puede abrir el email')),
        );
      }
    }
  }

  void _verDetalleCliente(BuildContext context, Map<String, dynamic> cliente) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => ClienteDetalleScreen(cliente: cliente),
      ),
    );
  }

  Future<void> _mostrarFormularioCliente(BuildContext context, {Map<String, dynamic>? cliente}) async {
    final resultado = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (context) => ClienteFormScreen(cliente: cliente),
      ),
    );

    if (resultado == true) {
      _cargarClientes();
    }
  }

  Future<void> _confirmarEliminarCliente(dynamic id, String nombre) async {
    final confirmado = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar cliente'),
        content: Text('¿Seguro que deseas eliminar a "$nombre"? Esta acción no se puede deshacer.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );

    if (confirmado != true) return;

    final api = ref.read(apiClientProvider);
    final response = await api.delete('/clientes/$id');

    if (mounted) {
      if (response.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Cliente eliminado correctamente'),
            backgroundColor: Colors.green,
          ),
        );
        _cargarClientes();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al eliminar'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }
}

/// Pantalla de detalle del cliente
class ClienteDetalleScreen extends StatelessWidget {
  final Map<String, dynamic> cliente;

  const ClienteDetalleScreen({super.key, required this.cliente});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final nombreCliente = cliente['nombre'] ?? cliente['name'] ?? 'Sin nombre';
    final emailCliente = cliente['email'] ?? '';
    final telefonoCliente = cliente['telefono'] ?? cliente['phone'] ?? '';
    final direccionCliente = cliente['direccion'] ?? cliente['address'] ?? '';
    final tipoCliente = cliente['tipo'] ?? cliente['type'] ?? '';
    final estadoCliente = cliente['estado'] ?? cliente['status'] ?? 'activo';
    final notasCliente = cliente['notas'] ?? cliente['notes'] ?? '';
    final fechaCreacion = cliente['fecha_creacion'] ?? cliente['created_at'] ?? '';
    final empresaCliente = cliente['empresa'] ?? cliente['company'] ?? '';
    final cifCliente = cliente['cif'] ?? cliente['nif'] ?? cliente['tax_id'] ?? '';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle del Cliente'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit),
            onPressed: () {
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(
                  builder: (context) => ClienteFormScreen(cliente: cliente),
                ),
              );
            },
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Avatar y nombre
          Center(
            child: Column(
              children: [
                CircleAvatar(
                  radius: 50,
                  backgroundColor: Colors.blue.shade100,
                  child: Text(
                    nombreCliente.isNotEmpty ? nombreCliente[0].toUpperCase() : '?',
                    style: TextStyle(
                      fontSize: 36,
                      color: Colors.blue.shade700,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  nombreCliente,
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                if (empresaCliente.isNotEmpty)
                  Text(
                    empresaCliente,
                    style: theme.textTheme.bodyLarge?.copyWith(color: Colors.grey),
                  ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    if (tipoCliente.isNotEmpty)
                      Chip(
                        label: Text(tipoCliente),
                        backgroundColor: theme.colorScheme.primaryContainer,
                      ),
                    const SizedBox(width: 8),
                    Chip(
                      avatar: Icon(
                        estadoCliente.toString().toLowerCase() == 'activo'
                            ? Icons.check_circle
                            : Icons.pause_circle,
                        size: 16,
                        color: estadoCliente.toString().toLowerCase() == 'activo'
                            ? Colors.green
                            : Colors.grey,
                      ),
                      label: Text(estadoCliente.toString()),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Acciones rápidas
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              if (telefonoCliente.isNotEmpty)
                _buildAccionRapida(
                  context,
                  icon: Icons.phone,
                  label: 'Llamar',
                  color: Colors.green,
                  onTap: () => _realizarLlamada(context, telefonoCliente),
                ),
              if (emailCliente.isNotEmpty)
                _buildAccionRapida(
                  context,
                  icon: Icons.email,
                  label: 'Email',
                  color: Colors.blue,
                  onTap: () => _enviarEmail(context, emailCliente, nombreCliente),
                ),
              if (telefonoCliente.isNotEmpty)
                _buildAccionRapida(
                  context,
                  icon: Icons.message,
                  label: 'Mensaje',
                  color: Colors.orange,
                  onTap: () => _enviarSMS(context, telefonoCliente),
                ),
            ],
          ),
          const SizedBox(height: 24),

          // Información de contacto
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Información de contacto',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const Divider(),
                  if (emailCliente.isNotEmpty)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.email),
                      title: const Text('Email'),
                      subtitle: Text(emailCliente),
                    ),
                  if (telefonoCliente.isNotEmpty)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.phone),
                      title: const Text('Teléfono'),
                      subtitle: Text(telefonoCliente),
                    ),
                  if (direccionCliente.isNotEmpty)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.location_on),
                      title: const Text('Dirección'),
                      subtitle: Text(direccionCliente),
                    ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Información fiscal
          if (cifCliente.isNotEmpty || empresaCliente.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Información fiscal',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Divider(),
                    if (empresaCliente.isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.business),
                        title: const Text('Empresa'),
                        subtitle: Text(empresaCliente),
                      ),
                    if (cifCliente.isNotEmpty)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.badge),
                        title: const Text('CIF/NIF'),
                        subtitle: Text(cifCliente),
                      ),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 16),

          // Notas
          if (notasCliente.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Notas',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Divider(),
                    Text(notasCliente),
                  ],
                ),
              ),
            ),

          // Fecha de creación
          if (fechaCreacion.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 16),
              child: Text(
                'Cliente desde: ${_formatDate(fechaCreacion)}',
                style: theme.textTheme.bodySmall?.copyWith(color: Colors.grey),
                textAlign: TextAlign.center,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildAccionRapida(
    BuildContext context, {
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 28),
            const SizedBox(height: 4),
            Text(label, style: TextStyle(color: color, fontSize: 12)),
          ],
        ),
      ),
    );
  }

  Future<void> _realizarLlamada(BuildContext context, String telefono) async {
    final uri = Uri.parse('tel:$telefono');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _enviarEmail(BuildContext context, String email, String nombre) async {
    final uri = Uri.parse('mailto:$email?subject=Contacto desde CRM&body=Hola $nombre,\n\n');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _enviarSMS(BuildContext context, String telefono) async {
    final uri = Uri.parse('sms:$telefono');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
    } catch (_) {
      return dateStr;
    }
  }
}

/// Formulario para crear/editar cliente
class ClienteFormScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? cliente;

  const ClienteFormScreen({super.key, this.cliente});

  @override
  ConsumerState<ClienteFormScreen> createState() => _ClienteFormScreenState();
}

class _ClienteFormScreenState extends ConsumerState<ClienteFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nombreController = TextEditingController();
  final _emailController = TextEditingController();
  final _telefonoController = TextEditingController();
  final _direccionController = TextEditingController();
  final _empresaController = TextEditingController();
  final _cifController = TextEditingController();
  final _notasController = TextEditingController();
  String _tipoSeleccionado = '';
  bool _guardando = false;

  bool get _esEdicion => widget.cliente != null;

  final List<String> _tiposCliente = [
    'Particular',
    'Empresa',
    'Autónomo',
    'Administración',
    'Otro',
  ];

  @override
  void initState() {
    super.initState();
    if (_esEdicion) {
      _nombreController.text = widget.cliente!['nombre'] ?? widget.cliente!['name'] ?? '';
      _emailController.text = widget.cliente!['email'] ?? '';
      _telefonoController.text = widget.cliente!['telefono'] ?? widget.cliente!['phone'] ?? '';
      _direccionController.text = widget.cliente!['direccion'] ?? widget.cliente!['address'] ?? '';
      _empresaController.text = widget.cliente!['empresa'] ?? widget.cliente!['company'] ?? '';
      _cifController.text = widget.cliente!['cif'] ?? widget.cliente!['nif'] ?? '';
      _notasController.text = widget.cliente!['notas'] ?? widget.cliente!['notes'] ?? '';
      _tipoSeleccionado = widget.cliente!['tipo'] ?? widget.cliente!['type'] ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_esEdicion ? 'Editar Cliente' : 'Nuevo Cliente'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(
              controller: _nombreController,
              decoration: const InputDecoration(
                labelText: 'Nombre *',
                prefixIcon: Icon(Icons.person),
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El nombre es obligatorio';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _emailController,
              decoration: const InputDecoration(
                labelText: 'Email',
                prefixIcon: Icon(Icons.email),
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _telefonoController,
              decoration: const InputDecoration(
                labelText: 'Teléfono',
                prefixIcon: Icon(Icons.phone),
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 16),

            DropdownButtonFormField<String>(
              value: _tipoSeleccionado.isEmpty ? null : _tipoSeleccionado,
              decoration: const InputDecoration(
                labelText: 'Tipo de cliente',
                prefixIcon: Icon(Icons.category),
                border: OutlineInputBorder(),
              ),
              items: _tiposCliente.map((tipo) => DropdownMenuItem(
                value: tipo,
                child: Text(tipo),
              )).toList(),
              onChanged: (value) {
                setState(() => _tipoSeleccionado = value ?? '');
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _empresaController,
              decoration: const InputDecoration(
                labelText: 'Empresa',
                prefixIcon: Icon(Icons.business),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _cifController,
              decoration: const InputDecoration(
                labelText: 'CIF/NIF',
                prefixIcon: Icon(Icons.badge),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _direccionController,
              decoration: const InputDecoration(
                labelText: 'Dirección',
                prefixIcon: Icon(Icons.location_on),
                border: OutlineInputBorder(),
              ),
              maxLines: 2,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _notasController,
              decoration: const InputDecoration(
                labelText: 'Notas',
                prefixIcon: Icon(Icons.note),
                border: OutlineInputBorder(),
                alignLabelWithHint: true,
              ),
              maxLines: 4,
            ),
            const SizedBox(height: 24),

            FilledButton.icon(
              onPressed: _guardando ? null : _guardarCliente,
              icon: _guardando
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.check),
              label: Text(_guardando
                  ? 'Guardando...'
                  : (_esEdicion ? 'Guardar Cambios' : 'Crear Cliente')),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _guardarCliente() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _guardando = true);

    try {
      final api = ref.read(apiClientProvider);
      final datos = {
        'nombre': _nombreController.text.trim(),
        'email': _emailController.text.trim(),
        'telefono': _telefonoController.text.trim(),
        'tipo': _tipoSeleccionado,
        'empresa': _empresaController.text.trim(),
        'cif': _cifController.text.trim(),
        'direccion': _direccionController.text.trim(),
        'notas': _notasController.text.trim(),
      };

      final ApiResponse<Map<String, dynamic>> response;
      if (_esEdicion) {
        final idCliente = widget.cliente!['id'] ?? widget.cliente!['ID'];
        response = await api.put('/clientes/$idCliente', data: datos);
      } else {
        response = await api.post('/clientes', data: datos);
      }

      if (mounted) {
        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_esEdicion
                  ? 'Cliente actualizado correctamente'
                  : 'Cliente creado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          Navigator.of(context).pop(true);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al guardar'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } finally {
      if (mounted) {
        setState(() => _guardando = false);
      }
    }
  }

  @override
  void dispose() {
    _nombreController.dispose();
    _emailController.dispose();
    _telefonoController.dispose();
    _direccionController.dispose();
    _empresaController.dispose();
    _cifController.dispose();
    _notasController.dispose();
    super.dispose();
  }
}
