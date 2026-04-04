import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_state_widgets.dart';
part 'empresarial_screen_parts.dart';


class EmpresarialScreen extends ConsumerStatefulWidget {
  const EmpresarialScreen({super.key});

  @override
  ConsumerState<EmpresarialScreen> createState() => _EmpresarialScreenState();
}

class _EmpresarialScreenState extends ConsumerState<EmpresarialScreen> {
  List<dynamic> _listaComponentes = [];
  Map<String, dynamic>? _datosDashboard;
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
      final respuesta = await clienteApi.get('/empresarial');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosDashboard = respuesta.data!;
          _listaComponentes = respuesta.data!['componentes'] ?? respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar datos empresariales';
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
        title: const Text('Panel Empresarial'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _cargarDatos),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDatos,
                  icon: Icons.business,
                )
              : RefreshIndicator(
                  onRefresh: _cargarDatos,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _construirResumenEmpresarial(),
                      const SizedBox(height: 24),
                      _construirSeccionAccesosRapidos(),
                      const SizedBox(height: 24),
                      if (_listaComponentes.isNotEmpty) ...[
                        const Text(
                          'Componentes',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 12),
                        ..._listaComponentes.map((componente) => _construirTarjetaComponente(componente)),
                      ] else
                        const FlavorEmptyState(
                          icon: Icons.business_outlined,
                          title: 'No hay componentes disponibles',
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _construirResumenEmpresarial() {
    final totalClientes = _datosDashboard?['total_clientes'] ?? _datosDashboard?['clientes'] ?? 0;
    final totalProyectos = _datosDashboard?['total_proyectos'] ?? _datosDashboard?['proyectos'] ?? 0;
    final facturacionMes = _datosDashboard?['facturacion_mes'] ?? _datosDashboard?['revenue'] ?? '0';
    final tareasPendientes = _datosDashboard?['tareas_pendientes'] ?? _datosDashboard?['pending_tasks'] ?? 0;

    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.indigo.shade100,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(Icons.business_center, color: Colors.indigo.shade700, size: 32),
              ),
              const SizedBox(width: 16),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Panel Empresarial', style: TextStyle(color: Colors.grey)),
                    Text(
                      'Resumen General',
                      style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(child: _construirIndicador('Clientes', totalClientes.toString(), Icons.people)),
              Expanded(child: _construirIndicador('Proyectos', totalProyectos.toString(), Icons.work)),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _construirIndicador('Facturacion', '\$$facturacionMes', Icons.euro)),
              Expanded(child: _construirIndicador('Pendientes', tareasPendientes.toString(), Icons.assignment_late)),
            ],
          ),
        ],
        ),
      ),
    );
  }

  Widget _construirIndicador(String etiqueta, String valor, IconData icono) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(8),
      ),
      margin: const EdgeInsets.all(4),
      child: Column(
        children: [
          Icon(icono, size: 24, color: Colors.indigo),
          const SizedBox(height: 8),
          Text(valor, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          Text(etiqueta, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
        ],
      ),
    );
  }

  Widget _construirSeccionAccesosRapidos() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Accesos Rapidos',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(child: _construirBotonAccesoRapido('Facturas', Icons.receipt_long, Colors.green)),
            const SizedBox(width: 12),
            Expanded(child: _construirBotonAccesoRapido('Clientes', Icons.people, Colors.blue)),
            const SizedBox(width: 12),
            Expanded(child: _construirBotonAccesoRapido('Informes', Icons.analytics, Colors.orange)),
          ],
        ),
      ],
    );
  }

  Widget _construirBotonAccesoRapido(String etiqueta, IconData icono, Color color) {
    return InkWell(
      onTap: () => _navegarASeccion(etiqueta),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Column(
          children: [
            Icon(icono, color: color, size: 28),
            const SizedBox(height: 8),
            Text(etiqueta, style: TextStyle(color: color, fontWeight: FontWeight.w500)),
          ],
        ),
      ),
    );
  }

  Widget _construirTarjetaComponente(dynamic elemento) {
    final mapaDatos = elemento as Map<String, dynamic>;
    final nombreComponente = mapaDatos['nombre'] ?? mapaDatos['titulo'] ?? mapaDatos['title'] ?? 'Componente';
    final descripcionComponente = mapaDatos['descripcion'] ?? mapaDatos['description'] ?? '';
    final tipoComponente = mapaDatos['tipo'] ?? mapaDatos['type'] ?? 'general';
    final estadoComponente = mapaDatos['estado'] ?? mapaDatos['status'] ?? 'activo';

    IconData iconoTipo;
    switch (tipoComponente.toString().toLowerCase()) {
      case 'crm':
        iconoTipo = Icons.people_alt;
        break;
      case 'facturacion':
        iconoTipo = Icons.receipt;
        break;
      case 'proyecto':
        iconoTipo = Icons.work;
        break;
      case 'rrhh':
        iconoTipo = Icons.badge;
        break;
      default:
        iconoTipo = Icons.business;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.indigo.shade100,
          child: Icon(iconoTipo, color: Colors.indigo.shade700),
        ),
        title: Text(nombreComponente),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (descripcionComponente.isNotEmpty)
              Text(
                descripcionComponente,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: estadoComponente == 'activo' ? Colors.green.withOpacity(0.1) : Colors.grey.withOpacity(0.1),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                estadoComponente.toString().toUpperCase(),
                style: TextStyle(
                  fontSize: 10,
                  color: estadoComponente == 'activo' ? Colors.green : Colors.grey,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          final idComponente = mapaDatos['id'];
          if (idComponente != null) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => ComponenteDetalleScreen(componenteId: idComponente),
              ),
            );
          }
        },
      ),
    );
  }

  void _navegarASeccion(String seccion) {
    switch (seccion.toLowerCase()) {
      case 'facturas':
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => const FacturasEmpresarialScreen(),
          ),
        );
        break;
      case 'clientes':
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => const ClientesEmpresarialScreen(),
          ),
        );
        break;
      case 'informes':
        _mostrarInformes();
        break;
      default:
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Seccion: $seccion')),
        );
    }
  }

  void _mostrarInformes() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.6,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.analytics, color: Colors.orange),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Informes',
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
              ListTile(
                leading: const Icon(Icons.trending_up, color: Colors.green),
                title: const Text('Informe de ventas'),
                subtitle: const Text('Resumen mensual de ventas'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.pop(context);
                  _generarInforme('ventas');
                },
              ),
              ListTile(
                leading: const Icon(Icons.people, color: Colors.blue),
                title: const Text('Informe de clientes'),
                subtitle: const Text('Analisis de clientes'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.pop(context);
                  _generarInforme('clientes');
                },
              ),
              ListTile(
                leading: const Icon(Icons.receipt, color: Colors.purple),
                title: const Text('Informe de facturas'),
                subtitle: const Text('Facturas pendientes y pagadas'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.pop(context);
                  _generarInforme('facturas');
                },
              ),
              ListTile(
                leading: const Icon(Icons.work, color: Colors.indigo),
                title: const Text('Informe de proyectos'),
                subtitle: const Text('Estado de proyectos'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.pop(context);
                  _generarInforme('proyectos');
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _generarInforme(String tipo) async {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Generando informe de $tipo...')),
    );

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/empresarial/informes/$tipo');

      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Informe de $tipo generado'),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al generar informe'),
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

