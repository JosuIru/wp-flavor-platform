import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class IncidenciasScreen extends ConsumerStatefulWidget {
  const IncidenciasScreen({super.key});

  @override
  ConsumerState<IncidenciasScreen> createState() => _IncidenciasScreenState();
}

class _IncidenciasScreenState extends ConsumerState<IncidenciasScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  late Future<ApiResponse<Map<String, dynamic>>> _futureMine;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getIncidencias();
    _futureMine = api.getMisIncidencias();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _future = api.getIncidencias();
      _futureMine = api.getMisIncidencias();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Incidencias'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Todas', icon: Icon(Icons.list)),
              Tab(text: 'Mis incidencias', icon: Icon(Icons.person)),
            ],
          ),
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () => _showCreateIncidencia(context, api),
          icon: const Icon(Icons.add),
          label: const Text('Reportar incidencia'),
        ),
        body: TabBarView(
          children: [
            _buildIncidenciasTab(i18n),
            _buildMisIncidenciasTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildIncidenciasTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _future,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return const Center(child: Text('Error al cargar incidencias'));
        }

        final incidencias = (response.data!['incidencias'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (incidencias.isEmpty) {
          return const Center(child: Text('No hay incidencias reportadas'));
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: incidencias.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final incidencia = incidencias[index];
              final id = (incidencia['id'] as num?)?.toInt() ?? 0;
              final titulo = incidencia['titulo']?.toString() ?? 'Sin título';
              final descripcion = incidencia['descripcion']?.toString() ?? '';
              final estado = incidencia['estado']?.toString() ?? 'abierto';
              final prioridad = incidencia['prioridad']?.toString() ?? 'media';
              final categoria = incidencia['categoria']?.toString() ?? '';
              final fecha = incidencia['fecha']?.toString() ?? '';
              final ubicacion = incidencia['ubicacion']?.toString() ?? '';

              return Card(
                elevation: 1,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                child: InkWell(
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => IncidenciaDetailScreen(incidenciaId: id),
                      ),
                    );
                  },
                  borderRadius: BorderRadius.circular(12),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(
                              _getIconForPriority(prioridad),
                              color: _getColorForPriority(prioridad, context),
                              size: 20,
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                titulo,
                                style: Theme.of(context).textTheme.titleMedium,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            _buildEstadoChip(estado, context),
                          ],
                        ),
                        if (descripcion.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Text(
                            descripcion,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                        ],
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 4,
                          children: [
                            if (categoria.isNotEmpty)
                              Chip(
                                avatar: const Icon(Icons.category, size: 16),
                                label: Text(categoria),
                                visualDensity: VisualDensity.compact,
                              ),
                            if (ubicacion.isNotEmpty)
                              Chip(
                                avatar: const Icon(Icons.location_on, size: 16),
                                label: Text(ubicacion),
                                visualDensity: VisualDensity.compact,
                              ),
                            if (fecha.isNotEmpty)
                              Chip(
                                avatar: const Icon(Icons.calendar_today, size: 16),
                                label: Text(fecha),
                                visualDensity: VisualDensity.compact,
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        );
      },
    );
  }

  Widget _buildMisIncidenciasTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMine,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return const Center(child: Text('Error al cargar tus incidencias'));
        }

        final incidencias = (response.data!['incidencias'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (incidencias.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.check_circle_outline,
                  size: 64,
                  color: Colors.grey[400],
                ),
                const SizedBox(height: 16),
                Text(
                  'No tienes incidencias reportadas',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          );
        }

        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: incidencias.length,
          separatorBuilder: (_, __) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final incidencia = incidencias[index];
            final id = (incidencia['id'] as num?)?.toInt() ?? 0;
            final titulo = incidencia['titulo']?.toString() ?? 'Sin título';
            final estado = incidencia['estado']?.toString() ?? 'abierto';
            final fecha = incidencia['fecha']?.toString() ?? '';

            return Card(
              elevation: 1,
              child: ListTile(
                leading: Icon(_getIconForEstado(estado)),
                title: Text(titulo),
                subtitle: Text('Estado: $estado${fecha.isNotEmpty ? ' • $fecha' : ''}'),
                trailing: const Icon(Icons.chevron_right),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => IncidenciaDetailScreen(incidenciaId: id),
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

  Future<void> _showCreateIncidencia(BuildContext context, ApiClient api) async {
    final i18n = AppLocalizations.of(context)!;
    final titleController = TextEditingController();
    final descController = TextEditingController();
    final categoriaController = TextEditingController();
    final ubicacionController = TextEditingController();
    String prioridad = 'media';

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final bottom = MediaQuery.of(context).viewInsets.bottom;
            return Padding(
              padding: EdgeInsets.fromLTRB(16, 16, 16, bottom + 16),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Reportar incidencia',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: titleController,
                      decoration: const InputDecoration(
                        labelText: 'Título *',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.title),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: descController,
                      decoration: const InputDecoration(
                        labelText: 'Descripción *',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.description),
                      ),
                      maxLines: 4,
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: categoriaController,
                      decoration: const InputDecoration(
                        labelText: 'Categoría',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.category),
                        hintText: 'ej: Limpieza, Iluminación, etc.',
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: ubicacionController,
                      decoration: const InputDecoration(
                        labelText: 'Ubicación',
                        border: OutlineInputBorder(),
                        prefixIcon: Icon(Icons.location_on),
                        hintText: 'ej: Portal 3, piso 2',
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Prioridad',
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                    const SizedBox(height: 8),
                    SegmentedButton<String>(
                      segments: const [
                        ButtonSegment(
                          value: 'baja',
                          label: Text('Baja'),
                          icon: Icon(Icons.arrow_downward),
                        ),
                        ButtonSegment(
                          value: 'media',
                          label: Text('Media'),
                          icon: Icon(Icons.remove),
                        ),
                        ButtonSegment(
                          value: 'alta',
                          label: Text('Alta'),
                          icon: Icon(Icons.arrow_upward),
                        ),
                      ],
                      selected: {prioridad},
                      onSelectionChanged: (Set<String> newSelection) {
                        setModalState(() {
                          prioridad = newSelection.first;
                        });
                      },
                    ),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        TextButton(
                          onPressed: () => Navigator.pop(context, false),
                          child: Text(i18n.commonCancel),
                        ),
                        const SizedBox(width: 12),
                        FilledButton.icon(
                          onPressed: () => Navigator.pop(context, true),
                          icon: const Icon(Icons.send),
                          label: const Text('Enviar'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );

    if (result == true) {
      if (titleController.text.trim().isEmpty || descController.text.trim().isEmpty) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('El título y la descripción son obligatorios')),
          );
        }
        return;
      }

      final response = await api.createIncidencia(
        titulo: titleController.text.trim(),
        descripcion: descController.text.trim(),
        categoria: categoriaController.text.trim().isEmpty ? 'general' : categoriaController.text.trim(),
        ubicacion: ubicacionController.text.trim(),
        prioridad: prioridad,
      );

      if (context.mounted) {
        final msg = response.success
            ? 'Incidencia reportada correctamente'
            : (response.error ?? 'Error al reportar la incidencia');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(msg),
            backgroundColor: response.success ? Colors.green : Colors.red,
          ),
        );
        if (response.success) {
          _refresh();
        }
      }
    }

    titleController.dispose();
    descController.dispose();
    categoriaController.dispose();
    ubicacionController.dispose();
  }

  Widget _buildEstadoChip(String estado, BuildContext context) {
    Color color;
    IconData icon;

    switch (estado.toLowerCase()) {
      case 'abierto':
        color = Colors.orange;
        icon = Icons.schedule;
        break;
      case 'en_progreso':
        color = Colors.blue;
        icon = Icons.engineering;
        break;
      case 'resuelto':
        color = Colors.green;
        icon = Icons.check_circle;
        break;
      case 'cerrado':
        color = Colors.grey;
        icon = Icons.done_all;
        break;
      default:
        color = Colors.grey;
        icon = Icons.help_outline;
    }

    return Chip(
      avatar: Icon(icon, size: 16, color: color),
      label: Text(estado),
      visualDensity: VisualDensity.compact,
      backgroundColor: color.withOpacity(0.1),
    );
  }

  IconData _getIconForEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'abierto':
        return Icons.schedule;
      case 'en_progreso':
        return Icons.engineering;
      case 'resuelto':
        return Icons.check_circle;
      case 'cerrado':
        return Icons.done_all;
      default:
        return Icons.help_outline;
    }
  }

  IconData _getIconForPriority(String prioridad) {
    switch (prioridad.toLowerCase()) {
      case 'alta':
        return Icons.priority_high;
      case 'baja':
        return Icons.low_priority;
      default:
        return Icons.remove;
    }
  }

  Color _getColorForPriority(String prioridad, BuildContext context) {
    switch (prioridad.toLowerCase()) {
      case 'alta':
        return Colors.red;
      case 'baja':
        return Colors.green;
      default:
        return Colors.orange;
    }
  }
}

// Pantalla de detalle de incidencia
class IncidenciaDetailScreen extends ConsumerStatefulWidget {
  final int incidenciaId;

  const IncidenciaDetailScreen({super.key, required this.incidenciaId});

  @override
  ConsumerState<IncidenciaDetailScreen> createState() => _IncidenciaDetailScreenState();
}

class _IncidenciaDetailScreenState extends ConsumerState<IncidenciaDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;
  final _commentController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getIncidencia(widget.incidenciaId);
  }

  Future<void> _refresh() async {
    setState(() {
      _future = ref.read(apiClientProvider).getIncidencia(widget.incidenciaId);
    });
  }

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de incidencia'),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return Center(
              child: Text(res.error ?? 'No se pudo cargar la incidencia'),
            );
          }

          final incidencia = (res.data!['incidencia'] as Map?) ?? {};
          final comentarios = (res.data!['comentarios'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          final titulo = incidencia['titulo']?.toString() ?? 'Sin título';
          final descripcion = incidencia['descripcion']?.toString() ?? '';
          final estado = incidencia['estado']?.toString() ?? 'abierto';
          final prioridad = incidencia['prioridad']?.toString() ?? 'media';
          final categoria = incidencia['categoria']?.toString() ?? '';
          final ubicacion = incidencia['ubicacion']?.toString() ?? '';
          final fecha = incidencia['fecha']?.toString() ?? '';
          final reportadoPor = incidencia['reportado_por']?.toString() ?? '';

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Header con título y estado
                Card(
                  elevation: 2,
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                titulo,
                                style: Theme.of(context).textTheme.titleLarge,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            Chip(
                              avatar: const Icon(Icons.priority_high, size: 16),
                              label: Text('Prioridad: $prioridad'),
                              backgroundColor: _getColorForPriority(prioridad).withOpacity(0.1),
                            ),
                            _buildEstadoChip(estado),
                            if (categoria.isNotEmpty)
                              Chip(
                                avatar: const Icon(Icons.category, size: 16),
                                label: Text(categoria),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Descripción
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.description, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              'Descripción',
                              style: Theme.of(context).textTheme.titleMedium,
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(descripcion),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Información adicional
                Card(
                  child: Column(
                    children: [
                      if (ubicacion.isNotEmpty)
                        ListTile(
                          leading: const Icon(Icons.location_on),
                          title: const Text('Ubicación'),
                          subtitle: Text(ubicacion),
                        ),
                      if (fecha.isNotEmpty) ...[
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.calendar_today),
                          title: const Text('Fecha'),
                          subtitle: Text(fecha),
                        ),
                      ],
                      if (reportadoPor.isNotEmpty) ...[
                        const Divider(height: 1),
                        ListTile(
                          leading: const Icon(Icons.person),
                          title: const Text('Reportado por'),
                          subtitle: Text(reportadoPor),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Comentarios
                if (comentarios.isNotEmpty) ...[
                  Text(
                    'Comentarios (${comentarios.length})',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 8),
                  ...comentarios.map((comentario) {
                    final texto = comentario['comentario']?.toString() ?? '';
                    final autor = comentario['autor']?.toString() ?? '';
                    final fechaComentario = comentario['fecha']?.toString() ?? '';

                    return Card(
                      margin: const EdgeInsets.only(bottom: 8),
                      child: ListTile(
                        leading: CircleAvatar(
                          child: Text(autor.isNotEmpty ? autor[0].toUpperCase() : '?'),
                        ),
                        title: Text(autor),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (fechaComentario.isNotEmpty) ...[
                              Text(
                                fechaComentario,
                                style: Theme.of(context).textTheme.labelSmall,
                              ),
                              const SizedBox(height: 4),
                            ],
                            Text(texto),
                          ],
                        ),
                      ),
                    );
                  }).toList(),
                  const SizedBox(height: 16),
                ],

                // Formulario para agregar comentario
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Agregar comentario',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _commentController,
                          decoration: const InputDecoration(
                            hintText: 'Escribe un comentario...',
                            border: OutlineInputBorder(),
                          ),
                          maxLines: 3,
                        ),
                        const SizedBox(height: 12),
                        Align(
                          alignment: Alignment.centerRight,
                          child: FilledButton.icon(
                            onPressed: _addComment,
                            icon: const Icon(Icons.send),
                            label: const Text('Enviar'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _addComment() async {
    if (_commentController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('El comentario no puede estar vacío')),
      );
      return;
    }

    final api = ref.read(apiClientProvider);
    final res = await api.addIncidenciaComment(
      incidenciaId: widget.incidenciaId,
      comentario: _commentController.text.trim(),
    );

    if (mounted) {
      final msg = res.success
          ? 'Comentario agregado'
          : (res.error ?? 'Error al agregar comentario');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg)),
      );
      if (res.success) {
        _commentController.clear();
        _refresh();
      }
    }
  }

  Widget _buildEstadoChip(String estado) {
    Color color;
    IconData icon;

    switch (estado.toLowerCase()) {
      case 'abierto':
        color = Colors.orange;
        icon = Icons.schedule;
        break;
      case 'en_progreso':
        color = Colors.blue;
        icon = Icons.engineering;
        break;
      case 'resuelto':
        color = Colors.green;
        icon = Icons.check_circle;
        break;
      case 'cerrado':
        color = Colors.grey;
        icon = Icons.done_all;
        break;
      default:
        color = Colors.grey;
        icon = Icons.help_outline;
    }

    return Chip(
      avatar: Icon(icon, size: 16, color: color),
      label: Text(estado),
      visualDensity: VisualDensity.compact,
      backgroundColor: color.withOpacity(0.1),
    );
  }

  Color _getColorForPriority(String prioridad) {
    switch (prioridad.toLowerCase()) {
      case 'alta':
        return Colors.red;
      case 'baja':
        return Colors.green;
      default:
        return Colors.orange;
    }
  }
}
