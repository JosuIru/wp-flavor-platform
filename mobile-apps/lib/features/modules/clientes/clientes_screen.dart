import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_contact_launcher.dart';
import '../../../core/widgets/flavor_initials_avatar.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'clientes_screen_parts.dart';

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
                ? const FlavorLoadingState()
                : _mensajeError != null
                    ? FlavorErrorState(
                        message: _mensajeError!,
                        onRetry: _cargarClientes,
                        icon: Icons.people,
                      )
                    : _clientesFiltrados.isEmpty
                        ? FlavorEmptyState(
                            icon: Icons.people,
                            title: _terminoBusqueda.isEmpty
                                ? 'No hay clientes registrados'
                                : 'No se encontraron resultados',
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
        leading: FlavorInitialsAvatar(
          name: nombreCliente,
          backgroundColor: Colors.blue.shade100,
          textStyle: TextStyle(
            color: Colors.blue.shade700,
            fontWeight: FontWeight.bold,
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
    await FlavorContactLauncher.call(context, telefono);
  }

  Future<void> _enviarEmail(String email, String nombre) async {
    await FlavorContactLauncher.email(
      context,
      email,
      subject: 'Contacto desde CRM',
      body: 'Hola $nombre,\n\n',
      errorMessage: 'No se puede abrir el email',
    );
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
        FlavorSnackbar.showSuccess(context, 'Cliente eliminado correctamente');
        _cargarClientes();
      } else {
        FlavorSnackbar.showError(context, response.error ?? 'Error al eliminar');
      }
    }
  }
}
