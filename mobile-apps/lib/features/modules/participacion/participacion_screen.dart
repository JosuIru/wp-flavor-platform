import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';

class ParticipacionScreen extends ConsumerStatefulWidget {
  const ParticipacionScreen({super.key});

  @override
  ConsumerState<ParticipacionScreen> createState() =>
      _ParticipacionScreenState();
}

class _ParticipacionScreenState extends ConsumerState<ParticipacionScreen> {
  List<dynamic> _procesosVotacion = [];
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
      final respuesta = await clienteApi.get('/participacion/procesos');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _procesosVotacion =
              respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError =
              respuesta.error ?? 'Error al cargar los procesos de votación';
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
        title: const Text('Votaciones'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _cargarDatos,
          ),
        ],
      ),
      body: _cargando
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.how_to_vote, size: 64, color: Colors.grey),
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
              : _procesosVotacion.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.how_to_vote,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay votaciones activas'),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _cargarDatos,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _procesosVotacion.length,
                        itemBuilder: (context, indice) =>
                            _construirTarjetaVotacion(
                                _procesosVotacion[indice]),
                      ),
                    ),
    );
  }

  Widget _construirTarjetaVotacion(dynamic elemento) {
    final mapa = elemento as Map<String, dynamic>;
    final titulo =
        mapa['titulo'] ?? mapa['nombre'] ?? mapa['title'] ?? 'Sin título';
    final descripcion = mapa['descripcion'] ?? mapa['description'] ?? '';
    final estadoVotacion = mapa['estado'] ?? mapa['status'] ?? 'activo';
    final fechaLimite =
        mapa['fecha_limite'] ?? mapa['deadline'] ?? mapa['fecha_fin'] ?? '';
    final totalVotos = mapa['total_votos'] ?? mapa['votes'] ?? 0;
    final yaVotado = mapa['votado'] ?? mapa['voted'] ?? false;

    Color colorEstado;
    String textoEstado;
    switch (estadoVotacion.toString().toLowerCase()) {
      case 'activo':
      case 'abierto':
      case 'active':
        colorEstado = Colors.green;
        textoEstado = 'Activa';
        break;
      case 'cerrado':
      case 'closed':
      case 'finalizado':
        colorEstado = Colors.red;
        textoEstado = 'Cerrada';
        break;
      case 'pendiente':
      case 'pending':
        colorEstado = Colors.orange;
        textoEstado = 'Pendiente';
        break;
      default:
        colorEstado = Colors.grey;
        textoEstado = estadoVotacion.toString();
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.blue.shade100,
                  child: Icon(Icons.how_to_vote, color: Colors.blue.shade700),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        titulo,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: colorEstado.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              textoEstado,
                              style: TextStyle(
                                color: colorEstado,
                                fontSize: 12,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                          if (yaVotado) ...[
                            const SizedBox(width: 8),
                            Icon(Icons.check_circle,
                                size: 16, color: Colors.green),
                            const SizedBox(width: 4),
                            Text(
                              'Votado',
                              style: TextStyle(
                                color: Colors.green,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right),
              ],
            ),
            if (descripcion.isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(
                descripcion,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(color: Colors.grey.shade600),
              ),
            ],
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(Icons.people, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text(
                  '$totalVotos votos',
                  style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                ),
                if (fechaLimite.isNotEmpty) ...[
                  const SizedBox(width: 16),
                  Icon(Icons.schedule, size: 16, color: Colors.grey),
                  const SizedBox(width: 4),
                  Text(
                    'Hasta: $fechaLimite',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}
