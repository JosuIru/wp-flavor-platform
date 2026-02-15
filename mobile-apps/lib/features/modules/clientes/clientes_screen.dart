import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
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
        onPressed: () {
          // TODO: Agregar nuevo cliente
        },
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
          onSelected: (valor) {
            // TODO: Manejar acciones del menu
          },
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
          ],
        ),
        onTap: () {
          // TODO: Navegar al detalle del cliente
        },
      ),
    );
  }
}
