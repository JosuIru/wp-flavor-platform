import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'cursos_screen_parts.dart';

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
    final i18n = AppLocalizations.of(context);

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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: 'Error al cargar cursos',
            onRetry: _refresh,
            icon: Icons.school_outlined,
          );
        }

        final cursos = (response.data!['cursos'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (cursos.isEmpty) {
          return const FlavorEmptyState(
            icon: Icons.school_outlined,
            title: 'No hay cursos disponibles',
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
                                      precio == '0' ? 'Gratis' : '$precio€',
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: 'Error al cargar tus cursos',
            onRetry: _refresh,
            icon: Icons.school_outlined,
          );
        }

        final cursos = (response.data!['cursos'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (cursos.isEmpty) {
          return const FlavorEmptyState(
            icon: Icons.school_outlined,
            title: 'No estás inscrito en ningún curso',
            message: 'Explora el catálogo para inscribirte',
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
