part of 'cursos_screen.dart';

class CursoDetailScreen extends ConsumerStatefulWidget {
  final int cursoId;

  const CursoDetailScreen({super.key, required this.cursoId});

  @override
  ConsumerState<CursoDetailScreen> createState() => _CursoDetailScreenState();
}

class _CursoDetailScreenState extends ConsumerState<CursoDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getCurso(widget.cursoId);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = ref.read(apiClientProvider).getCurso(widget.cursoId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return FlavorErrorState(
              message: res.error ?? 'No se pudo cargar el curso',
              onRetry: () => setState(() {
                final api = ref.read(apiClientProvider);
                _future = api.getCurso(widget.cursoId);
              }),
              icon: Icons.school_outlined,
            );
          }

          final curso = (res.data!['curso'] as Map?) ?? {};
          final temario = (res.data!['temario'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          final inscrito = res.data!['inscrito'] == true;

          final titulo = curso['titulo']?.toString() ?? 'Sin título';
          final descripcion = curso['descripcion']?.toString() ?? '';
          final imagen = curso['imagen']?.toString() ?? '';
          final instructor = curso['instructor']?.toString() ?? '';
          final duracion = curso['duracion']?.toString() ?? '';
          final nivel = curso['nivel']?.toString() ?? '';
          final precio = curso['precio']?.toString() ?? '0';
          final plazas = (curso['plazas_disponibles'] as num?)?.toInt() ?? 0;

          return CustomScrollView(
            slivers: [
              // Header con imagen
              SliverAppBar(
                expandedHeight: 250,
                pinned: true,
                flexibleSpace: FlexibleSpaceBar(
                  title: Text(
                    titulo,
                    style: const TextStyle(
                      shadows: [Shadow(color: Colors.black54, blurRadius: 4)],
                    ),
                  ),
                  background: imagen.isNotEmpty
                      ? Image.network(
                          imagen,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            color: Colors.grey[300],
                            child: const Icon(Icons.school, size: 64),
                          ),
                        )
                      : Container(
                          color: Colors.grey[300],
                          child: const Icon(Icons.school, size: 64),
                        ),
                ),
              ),

              // Contenido
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Info básica
                      Row(
                        children: [
                          Expanded(
                            child: Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: [
                                if (nivel.isNotEmpty)
                                  Chip(
                                    avatar: const Icon(Icons.signal_cellular_alt, size: 16),
                                    label: Text(nivel),
                                    visualDensity: VisualDensity.compact,
                                  ),
                                if (duracion.isNotEmpty)
                                  Chip(
                                    avatar: const Icon(Icons.access_time, size: 16),
                                    label: Text(duracion),
                                    visualDensity: VisualDensity.compact,
                                  ),
                                if (plazas > 0)
                                  Chip(
                                    avatar: const Icon(Icons.people, size: 16),
                                    label: Text('$plazas plazas'),
                                    visualDensity: VisualDensity.compact,
                                  ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Descripción
                      if (descripcion.isNotEmpty) ...[
                        Text(
                          'Descripción',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 8),
                        Text(descripcion),
                        const SizedBox(height: 24),
                      ],

                      // Instructor
                      if (instructor.isNotEmpty) ...[
                        Card(
                          child: ListTile(
                            leading: const CircleAvatar(
                              child: Icon(Icons.person),
                            ),
                            title: const Text('Instructor'),
                            subtitle: Text(instructor),
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // Temario
                      if (temario.isNotEmpty) ...[
                        Text(
                          'Temario',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 8),
                        ...temario.asMap().entries.map((entry) {
                          final index = entry.key;
                          final modulo = entry.value;
                          final tituloModulo = modulo['titulo']?.toString() ?? '';
                          final duracionModulo = modulo['duracion']?.toString() ?? '';

                          return Card(
                            margin: const EdgeInsets.only(bottom: 8),
                            child: ListTile(
                              leading: CircleAvatar(
                                child: Text('${index + 1}'),
                              ),
                              title: Text(tituloModulo),
                              subtitle: duracionModulo.isNotEmpty
                                  ? Text(duracionModulo)
                                  : null,
                              trailing: inscrito
                                  ? const Icon(Icons.play_circle_outline)
                                  : const Icon(Icons.lock_outline),
                            ),
                          );
                        }),
                        const SizedBox(height: 24),
                      ],

                      // Botón de inscripción
                      if (!inscrito)
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: () => _inscribir(context, precio),
                            icon: const Icon(Icons.check_circle),
                            label: Text(
                              precio == '0'
                                  ? 'Inscribirse gratis'
                                  : 'Inscribirse por $precio€',
                            ),
                            style: FilledButton.styleFrom(
                              padding: const EdgeInsets.all(16),
                            ),
                          ),
                        )
                      else
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton.icon(
                            onPressed: () {
                              FlavorSnackbar.showInfo(context, 'Ya estás inscrito en este curso');
                            },
                            icon: const Icon(Icons.school),
                            label: const Text('Ya inscrito - Acceder al curso'),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.all(16),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _inscribir(BuildContext context, String precio) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirmar inscripción'),
        content: Text(
          precio == '0'
              ? '¿Deseas inscribirte en este curso gratuito?'
              : '¿Deseas inscribirte en este curso por $precio€?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Inscribirse'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      final api = ref.read(apiClientProvider);
      final res = await api.inscribirseCurso(cursoId: widget.cursoId);

      if (context.mounted) {
        final msg = res.success
            ? 'Inscripción realizada correctamente'
            : (res.error ?? 'Error al inscribirse');
        if (res.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (res.success) {
          _refresh();
        }
      }
    }
  }
}
