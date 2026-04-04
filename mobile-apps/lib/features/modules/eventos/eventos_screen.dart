import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_state_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';

part 'eventos_screen_parts.dart';

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
            return const FlavorLoadingState();
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return FlavorErrorState(
              message: res.error ?? 'Error al cargar eventos',
              onRetry: _refresh,
              icon: Icons.event_busy,
            );
          }
          final items = (res.data!['data'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          if (items.isEmpty) {
            return const FlavorEmptyState(
              icon: Icons.event_available,
              title: 'No hay eventos disponibles',
            );
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
                    trailing: Text(precio == '0' ? 'Gratis' : '$precio€'),
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

