part of 'eventos_screen.dart';

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
    if (res.success) {
      FlavorSnackbar.showSuccess(context, 'Inscripcion enviada');
    } else {
      FlavorSnackbar.showError(context, res.error ?? 'Error');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detalle del evento')),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return FlavorErrorState(
              message: res.error ?? 'No se pudo cargar el evento',
              onRetry: () {
                setState(() {
                  _future = ref.read(apiClientProvider).getEvento(widget.eventoId);
                });
              },
              icon: Icons.event_note,
            );
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
              Text(precio == '0' ? 'Gratis' : '$precio€'),
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
