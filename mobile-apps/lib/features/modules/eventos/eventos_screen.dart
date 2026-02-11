import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class EventosScreen extends ConsumerStatefulWidget {
  const EventosScreen({super.key});

  @override
  ConsumerState<EventosScreen> createState() => _EventosScreenState();
}

class _EventosScreenState extends ConsumerState<EventosScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getEventos(limite: 50);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = ref.read(apiClientProvider).getEventos(limite: 50);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Eventos')),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return Center(child: Text(res.error ?? 'Error al cargar eventos'));
          }
          final items = (res.data!['data'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          if (items.isEmpty) {
            return const Center(child: Text('No hay eventos disponibles'));
          }
          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final evento = items[index];
                final id = (evento['id'] as num?)?.toInt() ?? 0;
                final titulo = evento['titulo']?.toString() ?? 'Evento';
                final fecha = evento['fecha_inicio']?.toString() ?? '';
                final ubicacion = evento['ubicacion']?.toString() ?? '';
                final precio = evento['precio']?.toString() ?? '0';
                return Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  child: ListTile(
                    leading: const Icon(Icons.event),
                    title: Text(titulo),
                    subtitle: Text('$fecha${ubicacion.isNotEmpty ? ' · $ubicacion' : ''}'),
                    trailing: Text(precio == '0' ? 'Gratis' : '${precio}€'),
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => EventoDetailScreen(eventoId: id),
                        ),
                      );
                    },
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class EventoDetailScreen extends ConsumerStatefulWidget {
  final int eventoId;
  const EventoDetailScreen({super.key, required this.eventoId});

  @override
  ConsumerState<EventoDetailScreen> createState() => _EventoDetailScreenState();
}

class _EventoDetailScreenState extends ConsumerState<EventoDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  int _plazas = 1;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getEvento(widget.eventoId);
  }

  Future<void> _inscribirse() async {
    final res = await ref.read(apiClientProvider).inscribirseEvento(
          eventoId: widget.eventoId,
          numPlazas: _plazas,
        );
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(res.success ? 'Inscripcion enviada' : (res.error ?? 'Error')),
        backgroundColor: res.success ? Colors.green : Colors.red,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detalle del evento')),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return Center(child: Text(res.error ?? 'No se pudo cargar el evento'));
          }
          final data = res.data!;
          final evento = (data['data'] as Map?) ?? data;
          final titulo = evento['titulo']?.toString() ?? 'Evento';
          final descripcion = evento['descripcion']?.toString() ?? '';
          final fecha = evento['fecha_inicio']?.toString() ?? '';
          final ubicacion = evento['ubicacion']?.toString() ?? '';
          final plazas = evento['plazas_disponibles']?.toString() ?? '';
          final precio = evento['precio']?.toString() ?? '0';

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(titulo, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              Text('$fecha${ubicacion.isNotEmpty ? ' · $ubicacion' : ''}'),
              const SizedBox(height: 8),
              Text(precio == '0' ? 'Gratis' : '${precio}€'),
              if (plazas.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text('Plazas disponibles: $plazas'),
              ],
              const SizedBox(height: 16),
              if (descripcion.isNotEmpty) Text(descripcion),
              const SizedBox(height: 24),
              Row(
                children: [
                  const Text('Plazas:'),
                  const SizedBox(width: 8),
                  DropdownButton<int>(
                    value: _plazas,
                    items: [1, 2, 3, 4, 5]
                        .map((n) => DropdownMenuItem(value: n, child: Text('$n')))
                        .toList(),
                    onChanged: (value) => setState(() => _plazas = value ?? 1),
                  ),
                  const Spacer(),
                  FilledButton(
                    onPressed: _inscribirse,
                    child: const Text('Inscribirse'),
                  ),
                ],
              ),
            ],
          );
        },
      ),
    );
  }
}
