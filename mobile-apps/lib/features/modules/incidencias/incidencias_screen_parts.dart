part of 'incidencias_screen.dart';

extension _IncidenciasScreenParts on _IncidenciasScreenState {
  Future<void> _showCreateIncidencia(BuildContext context, ApiClient api) async {
    final i18n = AppLocalizations.of(context);
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
          FlavorSnackbar.showError(context, 'El título y la descripción son obligatorios');
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
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
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
}

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

  String? _currentEstado;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de incidencia'),
        actions: [
          PopupMenuButton<String>(
            icon: const Icon(Icons.more_vert),
            tooltip: 'Acciones',
            onSelected: (value) {
              if (value == 'cambiar_estado') {
                _showChangeEstadoDialog();
              }
            },
            itemBuilder: (context) => [
              const PopupMenuItem(
                value: 'cambiar_estado',
                child: ListTile(
                  leading: Icon(Icons.edit),
                  title: Text('Cambiar estado'),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
            ],
          ),
        ],
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return FlavorErrorState(
              message: res.error ?? 'No se pudo cargar la incidencia',
              onRetry: _refresh,
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

          // Guardar estado actual para el diálogo de cambio
          _currentEstado = estado;

          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
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
                              backgroundColor: _getDetailColorForPriority(prioridad).withOpacity(0.1),
                            ),
                            _buildDetailEstadoChip(estado),
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
                  }),
                  const SizedBox(height: 16),
                ],
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

  Future<void> _showChangeEstadoDialog() async {
    final estados = ['abierto', 'en_progreso', 'resuelto', 'cerrado'];
    final estadosLabels = {
      'abierto': 'Abierto',
      'en_progreso': 'En progreso',
      'resuelto': 'Resuelto',
      'cerrado': 'Cerrado',
    };

    final nuevoEstado = await showDialog<String>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Cambiar estado'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: estados.map((estado) {
              return ListTile(
                leading: Icon(
                  _getIconForEstado(estado),
                  color: _getColorForEstado(estado),
                ),
                title: Text(estadosLabels[estado] ?? estado),
                selected: estado == _currentEstado,
                onTap: () => Navigator.pop(context, estado),
              );
            }).toList(),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancelar'),
            ),
          ],
        );
      },
    );

    if (nuevoEstado != null && nuevoEstado != _currentEstado) {
      await _updateEstado(nuevoEstado);
    }
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

  Color _getColorForEstado(String estado) {
    switch (estado.toLowerCase()) {
      case 'abierto':
        return Colors.orange;
      case 'en_progreso':
        return Colors.blue;
      case 'resuelto':
        return Colors.green;
      case 'cerrado':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  Future<void> _updateEstado(String nuevoEstado) async {
    final api = ref.read(apiClientProvider);
    final res = await api.updateIncidenciaEstado(
      incidenciaId: widget.incidenciaId,
      estado: nuevoEstado,
    );

    if (mounted) {
      if (res.success) {
        FlavorSnackbar.showSuccess(context, 'Estado actualizado a: $nuevoEstado');
        _refresh();
      } else {
        FlavorSnackbar.showError(context, res.error ?? 'Error al actualizar el estado');
      }
    }
  }

  Future<void> _addComment() async {
    if (_commentController.text.trim().isEmpty) {
      FlavorSnackbar.showError(context, 'El comentario no puede estar vacío');
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
      if (res.success) {
        FlavorSnackbar.showSuccess(context, msg);
      } else {
        FlavorSnackbar.showError(context, msg);
      }
      if (res.success) {
        _commentController.clear();
        _refresh();
      }
    }
  }

  Widget _buildDetailEstadoChip(String estado) {
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

  Color _getDetailColorForPriority(String prioridad) {
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
