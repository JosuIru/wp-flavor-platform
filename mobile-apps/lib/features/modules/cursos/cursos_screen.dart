import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class CursosScreen extends ConsumerStatefulWidget {
  const CursosScreen({super.key});

  @override
  ConsumerState<CursosScreen> createState() => _CursosScreenState();
}

class _CursosScreenState extends ConsumerState<CursosScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  late Future<ApiResponse<Map<String, dynamic>>> _futureMine;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getCursos();
    _futureMine = api.getMisCursos();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _future = api.getCursos();
      _futureMine = api.getMisCursos();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Cursos'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Catálogo', icon: Icon(Icons.library_books)),
              Tab(text: 'Mis cursos', icon: Icon(Icons.school)),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildCatalogTab(i18n),
            _buildMisCursosTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildCatalogTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _future,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return const Center(child: Text('Error al cargar cursos'));
        }

        final cursos = (response.data!['cursos'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (cursos.isEmpty) {
          return const Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.school_outlined, size: 64, color: Colors.grey),
                SizedBox(height: 16),
                Text('No hay cursos disponibles'),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: cursos.length,
            separatorBuilder: (_, __) => const SizedBox(height: 16),
            itemBuilder: (context, index) {
              final curso = cursos[index];
              final id = (curso['id'] as num?)?.toInt() ?? 0;
              final titulo = curso['titulo']?.toString() ?? 'Sin título';
              final descripcion = curso['descripcion']?.toString() ?? '';
              final categoria = curso['categoria']?.toString() ?? '';
              final duracion = curso['duracion']?.toString() ?? '';
              final nivel = curso['nivel']?.toString() ?? '';
              final plazas = (curso['plazas_disponibles'] as num?)?.toInt() ?? 0;
              final precio = curso['precio']?.toString() ?? '0';
              final imagen = curso['imagen']?.toString() ?? '';
              final instructor = curso['instructor']?.toString() ?? '';

              return Card(
                elevation: 2,
                clipBehavior: Clip.antiAlias,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                child: InkWell(
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => CursoDetailScreen(cursoId: id),
                      ),
                    );
                  },
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Imagen del curso
                      if (imagen.isNotEmpty)
                        Image.network(
                          imagen,
                          height: 160,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => _buildPlaceholderImage(),
                        )
                      else
                        _buildPlaceholderImage(),

                      // Contenido
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Título
                            Text(
                              titulo,
                              style: Theme.of(context).textTheme.titleLarge,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 8),

                            // Descripción
                            if (descripcion.isNotEmpty)
                              Text(
                                descripcion,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: Theme.of(context).textTheme.bodyMedium,
                              ),
                            const SizedBox(height: 12),

                            // Chips de info
                            Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: [
                                if (categoria.isNotEmpty)
                                  Chip(
                                    avatar: const Icon(Icons.category, size: 16),
                                    label: Text(categoria),
                                    visualDensity: VisualDensity.compact,
                                  ),
                                if (nivel.isNotEmpty)
                                  Chip(
                                    avatar: Icon(
                                      _getNivelIcon(nivel),
                                      size: 16,
                                    ),
                                    label: Text(nivel),
                                    visualDensity: VisualDensity.compact,
                                    backgroundColor: _getNivelColor(nivel).withOpacity(0.1),
                                  ),
                                if (duracion.isNotEmpty)
                                  Chip(
                                    avatar: const Icon(Icons.access_time, size: 16),
                                    label: Text(duracion),
                                    visualDensity: VisualDensity.compact,
                                  ),
                              ],
                            ),
                            const SizedBox(height: 12),

                            // Footer
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                // Instructor
                                if (instructor.isNotEmpty)
                                  Expanded(
                                    child: Row(
                                      children: [
                                        const Icon(Icons.person, size: 16),
                                        const SizedBox(width: 4),
                                        Expanded(
                                          child: Text(
                                            instructor,
                                            style: Theme.of(context).textTheme.bodySmall,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),

                                // Precio y plazas
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Text(
                                      precio == '0' ? 'Gratis' : '${precio}€',
                                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                        color: Theme.of(context).colorScheme.primary,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                    if (plazas > 0)
                                      Text(
                                        '$plazas plazas',
                                        style: Theme.of(context).textTheme.labelSmall,
                                      ),
                                  ],
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }

  Widget _buildMisCursosTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMine,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return const Center(child: Text('Error al cargar tus cursos'));
        }

        final cursos = (response.data!['cursos'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (cursos.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.school_outlined,
                  size: 64,
                  color: Colors.grey[400],
                ),
                const SizedBox(height: 16),
                Text(
                  'No estás inscrito en ningún curso',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey[600],
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Explora el catálogo para inscribirte',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[500],
                  ),
                ),
              ],
            ),
          );
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: cursos.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final curso = cursos[index];
            final id = (curso['id'] as num?)?.toInt() ?? 0;
            final titulo = curso['titulo']?.toString() ?? 'Sin título';
            final progreso = (curso['progreso'] as num?)?.toDouble() ?? 0;
            final ultimoAcceso = curso['ultimo_acceso']?.toString() ?? '';

            return Card(
              elevation: 1,
              child: ListTile(
                leading: CircleAvatar(
                  child: Text('${progreso.toInt()}%'),
                ),
                title: Text(titulo),
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: 4),
                    LinearProgressIndicator(
                      value: progreso / 100,
                      backgroundColor: Colors.grey[300],
                    ),
                    if (ultimoAcceso.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        'Último acceso: $ultimoAcceso',
                        style: Theme.of(context).textTheme.labelSmall,
                      ),
                    ],
                  ],
                ),
                trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => CursoDetailScreen(cursoId: id),
                    ),
                  );
                },
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildPlaceholderImage() {
    return Container(
      height: 160,
      width: double.infinity,
      color: Colors.grey[300],
      child: Icon(
        Icons.school,
        size: 64,
        color: Colors.grey[500],
      ),
    );
  }

  IconData _getNivelIcon(String nivel) {
    switch (nivel.toLowerCase()) {
      case 'principiante':
      case 'básico':
        return Icons.show_chart;
      case 'intermedio':
        return Icons.trending_up;
      case 'avanzado':
        return Icons.rocket_launch;
      default:
        return Icons.school;
    }
  }

  Color _getNivelColor(String nivel) {
    switch (nivel.toLowerCase()) {
      case 'principiante':
      case 'básico':
        return Colors.green;
      case 'intermedio':
        return Colors.orange;
      case 'avanzado':
        return Colors.red;
      default:
        return Colors.blue;
    }
  }
}

// Pantalla de detalle del curso
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
            return const Center(child: CircularProgressIndicator());
          }

          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return Center(
              child: Text(res.error ?? 'No se pudo cargar el curso'),
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
                        }).toList(),
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
                                  : 'Inscribirse por ${precio}€',
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
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Ya estás inscrito en este curso'),
                                ),
                              );
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
              : '¿Deseas inscribirte en este curso por ${precio}€?',
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(msg),
            backgroundColor: res.success ? Colors.green : Colors.red,
          ),
        );
        if (res.success) {
          _refresh();
        }
      }
    }
  }
}
