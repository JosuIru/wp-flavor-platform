import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'presupuestos_participativos_screen_parts.dart';

class PresupuestosParticipativosScreen extends ConsumerStatefulWidget {
  const PresupuestosParticipativosScreen({super.key});

  @override
  ConsumerState<PresupuestosParticipativosScreen> createState() =>
      _PresupuestosParticipativosScreenState();
}

class _PresupuestosParticipativosScreenState
    extends ConsumerState<PresupuestosParticipativosScreen> {
  List<dynamic> _listaPropuestas = [];
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
      final respuesta = await clienteApi.get('/presupuestos/propuestas');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _listaPropuestas =
              respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar las propuestas';
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

  void _abrirDetallePropuesta(Map<String, dynamic> propuesta) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PropuestaDetalleScreen(
          datosPropuesta: propuesta,
          onActualizado: _cargarDatos,
        ),
      ),
    );
  }

  void _crearNuevaPropuesta() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => _FormularioNuevaPropuesta(
        onCreada: () {
          Navigator.pop(context);
          _cargarDatos();
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Presupuestos Participativos'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDatos,
          ),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDatos,
                  icon: Icons.account_balance_outlined,
                )
              : _listaPropuestas.isEmpty
                  ? FlavorEmptyState(
                      icon: Icons.account_balance_outlined,
                      title: 'No hay propuestas disponibles',
                      action: FilledButton.icon(
                        onPressed: _crearNuevaPropuesta,
                        icon: const Icon(Icons.add),
                        label: const Text('Crear primera propuesta'),
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _listaPropuestas.length,
                        itemBuilder: (context, indice) =>
                            _construirTarjetaPropuesta(_listaPropuestas[indice]),
                      ),
                    ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _crearNuevaPropuesta,
        icon: const Icon(Icons.add),
        label: const Text('Nueva Propuesta'),
      ),
    );
  }

  Widget _construirTarjetaPropuesta(dynamic elemento) {
    final mapa = elemento as Map<String, dynamic>;
    final tituloPropuesta =
        mapa['titulo'] ?? mapa['nombre'] ?? mapa['title'] ?? 'Sin titulo';
    final descripcionPropuesta =
        mapa['descripcion'] ?? mapa['description'] ?? '';
    final presupuestoSolicitado =
        mapa['presupuesto'] ?? mapa['budget'] ?? mapa['importe'] ?? 0;
    final estadoPropuesta = mapa['estado'] ?? mapa['status'] ?? 'pendiente';
    final cantidadApoyos = mapa['apoyos'] ?? mapa['votos'] ?? mapa['votes'] ?? 0;
    final categoriaPropuesta =
        mapa['categoria'] ?? mapa['category'] ?? mapa['area'] ?? '';
    final autorPropuesta = mapa['autor'] ?? mapa['author'] ?? '';

    Color colorEstado;
    String textoEstado;
    IconData iconoEstado;
    switch (estadoPropuesta.toString().toLowerCase()) {
      case 'aprobado':
      case 'approved':
      case 'aceptado':
        colorEstado = Colors.green;
        textoEstado = 'Aprobada';
        iconoEstado = Icons.check_circle;
        break;
      case 'rechazado':
      case 'rejected':
      case 'denegado':
        colorEstado = Colors.red;
        textoEstado = 'Rechazada';
        iconoEstado = Icons.cancel;
        break;
      case 'evaluacion':
      case 'review':
      case 'revision':
        colorEstado = Colors.orange;
        textoEstado = 'En Evaluacion';
        iconoEstado = Icons.pending;
        break;
      case 'ejecucion':
      case 'executing':
        colorEstado = Colors.blue;
        textoEstado = 'En Ejecucion';
        iconoEstado = Icons.build;
        break;
      default:
        colorEstado = Colors.grey;
        textoEstado = 'Pendiente';
        iconoEstado = Icons.hourglass_empty;
    }

    final presupuestoFormateado = presupuestoSolicitado is num
        ? '${presupuestoSolicitado.toStringAsFixed(0)} EUR'
        : presupuestoSolicitado.toString();

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: InkWell(
        onTap: () => _abrirDetallePropuesta(mapa),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    backgroundColor: Colors.teal.shade100,
                    child:
                        Icon(Icons.account_balance, color: Colors.teal.shade700),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tituloPropuesta,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        if (categoriaPropuesta.isNotEmpty)
                          Text(
                            categoriaPropuesta,
                            style: TextStyle(
                              color: Colors.teal.shade700,
                              fontSize: 12,
                            ),
                          ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: colorEstado.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(iconoEstado, size: 14, color: colorEstado),
                        const SizedBox(width: 4),
                        Text(
                          textoEstado,
                          style: TextStyle(
                            color: colorEstado,
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              if (descripcionPropuesta.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  descripcionPropuesta,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: Colors.grey.shade600),
                ),
              ],
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.teal.shade50,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    Column(
                      children: [
                        Icon(Icons.euro, color: Colors.teal.shade700, size: 20),
                        const SizedBox(height: 4),
                        Text(
                          presupuestoFormateado,
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Colors.teal.shade700,
                          ),
                        ),
                        Text(
                          'Presupuesto',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                    Container(
                      width: 1,
                      height: 40,
                      color: Colors.teal.shade200,
                    ),
                    Column(
                      children: [
                        Icon(Icons.thumb_up,
                            color: Colors.teal.shade700, size: 20),
                        const SizedBox(height: 4),
                        Text(
                          '$cantidadApoyos',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Colors.teal.shade700,
                          ),
                        ),
                        Text(
                          'Apoyos',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              if (autorPropuesta.isNotEmpty) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    const Icon(Icons.person, size: 14, color: Colors.grey),
                    const SizedBox(width: 4),
                    Text(
                      'Propuesto por: $autorPropuesta',
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
