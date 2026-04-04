part of 'talleres_screen.dart';

class TallerDetailScreen extends ConsumerStatefulWidget {
  final int tallerId;

  const TallerDetailScreen({
    super.key,
    required this.tallerId,
  });

  @override
  ConsumerState<TallerDetailScreen> createState() => _TallerDetailScreenState();
}

class _TallerDetailScreenState extends ConsumerState<TallerDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getTaller(widget.tallerId);
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);

    return Scaffold(
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return FlavorErrorState(
              message: i18n.talleresError,
              onRetry: () => setState(() {
                final api = ref.read(apiClientProvider);
                _future = api.getTaller(widget.tallerId);
              }),
              icon: Icons.school_outlined,
            );
          }

          final taller = response.data!['taller'] as Map<String, dynamic>? ?? {};
          final titulo = taller['titulo']?.toString() ?? '';
          final descripcion = taller['descripcion']?.toString() ?? '';
          final instructor = taller['instructor']?.toString() ?? '';
          final categoria = taller['categoria']?.toString() ?? '';
          final nivel = taller['nivel']?.toString() ?? '';
          final duracion = taller['duracion']?.toString() ?? '';
          final fechaInicio = taller['fecha_inicio']?.toString() ?? '';
          final horario = taller['horario']?.toString() ?? '';
          final ubicacion = taller['ubicacion']?.toString() ?? '';
          final imagen = taller['imagen']?.toString() ?? '';
          final plazasDisponibles = (taller['plazas_disponibles'] as num?)?.toInt() ?? 0;
          final plazasTotales = (taller['plazas_totales'] as num?)?.toInt() ?? 0;
          final precio = taller['precio']?.toString() ?? '0';
          final materiales = taller['materiales']?.toString() ?? '';
          final sesiones = (taller['sesiones'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          final inscrito = taller['inscrito'] == true || taller['inscrito'] == 1;

          return CustomScrollView(
            slivers: [
              SliverAppBar(
                expandedHeight: 250,
                pinned: true,
                flexibleSpace: FlexibleSpaceBar(
                  title: Text(
                    titulo,
                    style: const TextStyle(
                      color: Colors.white,
                      shadows: [
                        Shadow(
                          offset: Offset(0, 1),
                          blurRadius: 3,
                          color: Colors.black54,
                        ),
                      ],
                    ),
                  ),
                  background: imagen.isNotEmpty
                      ? Stack(
                          fit: StackFit.expand,
                          children: [
                            Image.network(
                              imagen,
                              fit: BoxFit.cover,
                            ),
                            Container(
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  begin: Alignment.topCenter,
                                  end: Alignment.bottomCenter,
                                  colors: [
                                    Colors.transparent,
                                    Colors.black.withOpacity(0.7),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        )
                      : Container(
                          color: Theme.of(context).colorScheme.surfaceContainerHighest,
                          child: const Icon(Icons.school, size: 100),
                        ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          if (categoria.isNotEmpty)
                            Chip(
                              label: Text(categoria),
                              avatar: const Icon(Icons.category, size: 16),
                            ),
                          if (nivel.isNotEmpty)
                            Chip(
                              label: Text(nivel),
                              backgroundColor: _getNivelColor(nivel),
                            ),
                          if (duracion.isNotEmpty)
                            Chip(
                              label: Text(duracion),
                              avatar: const Icon(Icons.schedule, size: 16),
                            ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      if (descripcion.isNotEmpty) ...[
                        Text(
                          i18n.talleresDescription,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Text(
                              descripcion,
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],
                      Card(
                        child: ListTile(
                          leading: const CircleAvatar(
                            child: Icon(Icons.person),
                          ),
                          title: Text(i18n.talleresInstructor),
                          subtitle: Text(instructor),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            children: [
                              if (fechaInicio.isNotEmpty)
                                _buildInfoRow(
                                  Icons.calendar_today,
                                  i18n.talleresStartDate,
                                  fechaInicio,
                                  context,
                                ),
                              if (horario.isNotEmpty) ...[
                                const SizedBox(height: 8),
                                _buildInfoRow(
                                  Icons.access_time,
                                  i18n.talleresSchedule,
                                  horario,
                                  context,
                                ),
                              ],
                              if (ubicacion.isNotEmpty) ...[
                                const SizedBox(height: 8),
                                _buildInfoRow(
                                  Icons.place,
                                  i18n.talleresLocation,
                                  ubicacion,
                                  context,
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      if (materiales.isNotEmpty) ...[
                        Text(
                          i18n.talleresMaterials,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: materiales.split('\n').map((material) {
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Icon(Icons.check_circle_outline, size: 18),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          material.trim(),
                                          style: Theme.of(context).textTheme.bodyMedium,
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              }).toList(),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],
                      if (sesiones.isNotEmpty) ...[
                        Text(
                          i18n.talleresSessions,
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 8),
                        ...sesiones.asMap().entries.map((entry) {
                          final index = entry.key;
                          final sesion = entry.value;
                          final tituloSesion = sesion['titulo']?.toString() ?? '';
                          final fecha = sesion['fecha']?.toString() ?? '';
                          final completada = sesion['completada'] == true;

                          return Card(
                            child: ListTile(
                              leading: CircleAvatar(
                                backgroundColor: completada ? Colors.green : Colors.grey,
                                child: Text(
                                  '${index + 1}',
                                  style: const TextStyle(color: Colors.white),
                                ),
                              ),
                              title: Text(tituloSesion),
                              subtitle: fecha.isNotEmpty ? Text(fecha) : null,
                              trailing: completada
                                  ? const Icon(Icons.check_circle, color: Colors.green)
                                  : (inscrito
                                      ? const Icon(Icons.lock_open)
                                      : const Icon(Icons.lock_outline)),
                            ),
                          );
                        }),
                        const SizedBox(height: 16),
                      ],
                      Card(
                        color: plazasDisponibles > 0
                            ? Colors.green.withOpacity(0.1)
                            : Colors.red.withOpacity(0.1),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  Icon(
                                    plazasDisponibles > 0 ? Icons.check_circle : Icons.cancel,
                                    color: plazasDisponibles > 0 ? Colors.green : Colors.red,
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      plazasDisponibles > 0
                                          ? '${i18n.talleresAvailablePlaces}: $plazasDisponibles/$plazasTotales'
                                          : i18n.talleresFull,
                                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                            color: plazasDisponibles > 0 ? Colors.green : Colors.red,
                                            fontWeight: FontWeight.bold,
                                          ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              LinearProgressIndicator(
                                value: plazasTotales > 0 ? plazasDisponibles / plazasTotales : 0,
                                backgroundColor: Colors.grey[300],
                                color: plazasDisponibles > 0 ? Colors.green : Colors.red,
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),
                      if (!inscrito && plazasDisponibles > 0)
                        FilledButton.icon(
                          onPressed: () => _inscribirse(context, precio),
                          icon: const Icon(Icons.person_add),
                          label: Text(
                            precio == '0'
                                ? i18n.talleresEnrollFree
                                : '${i18n.talleresEnroll} ($precio€)',
                          ),
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                          ),
                        )
                      else if (inscrito)
                        Card(
                          color: Colors.blue.withOpacity(0.1),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Row(
                              children: [
                                const Icon(Icons.check_circle, color: Colors.blue),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Text(
                                    i18n.talleresAlreadyEnrolled,
                                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                          color: Colors.blue,
                                          fontWeight: FontWeight.bold,
                                        ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      const SizedBox(height: 16),
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

  Widget _buildInfoRow(IconData icon, String label, String value, BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 20),
        const SizedBox(width: 8),
        Text(
          '$label: ',
          style: Theme.of(context).textTheme.labelMedium,
        ),
        Expanded(
          child: Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium,
          ),
        ),
      ],
    );
  }

  Color _getNivelColor(String nivel) {
    switch (nivel.toLowerCase()) {
      case 'principiante':
        return Colors.green.shade100;
      case 'intermedio':
        return Colors.orange.shade100;
      case 'avanzado':
        return Colors.red.shade100;
      default:
        return Colors.grey.shade100;
    }
  }

  Future<void> _inscribirse(BuildContext context, String precio) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.talleresEnroll),
        content: Text(
          precio == '0'
              ? i18n.talleresEnrollConfirmFree
              : '${i18n.talleresEnrollConfirm} $precio€?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.commonConfirm),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.inscribirseTaller(widget.tallerId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.talleresEnrollSuccess
            : (response.error ?? i18n.talleresEnrollError);
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }
  }
}
