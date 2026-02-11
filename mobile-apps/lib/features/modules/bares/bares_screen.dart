import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class BaresScreen extends ConsumerStatefulWidget {
  const BaresScreen({super.key});

  @override
  ConsumerState<BaresScreen> createState() => _BaresScreenState();
}

class _BaresScreenState extends ConsumerState<BaresScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getBares(limite: 50);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = ref.read(apiClientProvider).getBares(limite: 50);
    });
  }

  Future<void> _abrirMapa(double lat, double lng, String nombre) async {
    final url = Uri.parse('https://www.openstreetmap.org/?mlat=$lat&mlon=$lng#map=18/$lat/$lng');
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _llamarTelefono(String telefono) async {
    final url = Uri.parse('tel:$telefono');
    if (await canLaunchUrl(url)) {
      await launchUrl(url);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Bares'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refresh,
          ),
        ],
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (!snapshot.hasData) {
            return const Center(child: Text('Error al cargar bares'));
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
                  const SizedBox(height: 16),
                  Text(response.error ?? 'Error al cargar bares'),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _refresh,
                    icon: const Icon(Icons.refresh),
                    label: const Text('Reintentar'),
                  ),
                ],
              ),
            );
          }

          final bares = (response.data!['bares'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          if (bares.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.local_bar, size: 64, color: Colors.grey.shade400),
                  const SizedBox(height: 16),
                  const Text('No hay bares disponibles'),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: bares.length,
              itemBuilder: (context, index) {
                final bar = bares[index];
                return _buildBarCard(bar);
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildBarCard(Map<String, dynamic> bar) {
    final nombre = bar['nombre'] ?? bar['title'] ?? 'Bar sin nombre';
    final descripcion = bar['descripcion'] ?? bar['description'] ?? '';
    final direccion = bar['direccion'] ?? bar['address'] ?? '';
    final telefono = bar['telefono'] ?? bar['phone'] ?? '';
    final horario = bar['horario'] ?? bar['schedule'] ?? '';
    final imagen = bar['imagen'] ?? bar['image'] ?? '';
    final latitud = bar['latitud'] ?? bar['lat'];
    final longitud = bar['longitud'] ?? bar['lng'];

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (imagen.isNotEmpty)
            Image.network(
              imagen,
              height: 200,
              width: double.infinity,
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                height: 200,
                color: Colors.grey.shade300,
                child: const Icon(Icons.local_bar, size: 64, color: Colors.grey),
              ),
            )
          else
            Container(
              height: 200,
              color: Colors.grey.shade300,
              child: const Center(
                child: Icon(Icons.local_bar, size: 64, color: Colors.grey),
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  nombre,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                if (descripcion.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    descripcion,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey.shade700,
                    ),
                  ),
                ],
                if (direccion.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Icon(Icons.location_on, size: 18, color: Colors.red.shade400),
                      const SizedBox(width: 8),
                      Expanded(child: Text(direccion)),
                    ],
                  ),
                ],
                if (telefono.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Icon(Icons.phone, size: 18, color: Colors.blue.shade400),
                      const SizedBox(width: 8),
                      Expanded(child: Text(telefono)),
                    ],
                  ),
                ],
                if (horario.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Icon(Icons.access_time, size: 18, color: Colors.orange.shade400),
                      const SizedBox(width: 8),
                      Expanded(child: Text(horario)),
                    ],
                  ),
                ],
                const SizedBox(height: 16),
                Row(
                  children: [
                    if (latitud != null && longitud != null)
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _abrirMapa(
                            double.tryParse(latitud.toString()) ?? 0,
                            double.tryParse(longitud.toString()) ?? 0,
                            nombre,
                          ),
                          icon: const Icon(Icons.map),
                          label: const Text('Ver mapa'),
                        ),
                      ),
                    if (latitud != null && longitud != null && telefono.isNotEmpty)
                      const SizedBox(width: 12),
                    if (telefono.isNotEmpty)
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: () => _llamarTelefono(telefono),
                          icon: const Icon(Icons.phone),
                          label: const Text('Llamar'),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
