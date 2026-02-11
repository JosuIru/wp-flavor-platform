import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class TalleresScreen extends ConsumerStatefulWidget {
  const TalleresScreen({super.key});

  @override
  ConsumerState<TalleresScreen> createState() => _TalleresScreenState();
}

class _TalleresScreenState extends ConsumerState<TalleresScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _futureCatalogo;
  late Future<ApiResponse<Map<String, dynamic>>> _futureMisTalleres;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _futureCatalogo = api.getTalleres();
    _futureMisTalleres = api.getMisTalleres();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _futureCatalogo = api.getTalleres();
      _futureMisTalleres = api.getMisTalleres();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Talleres'),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.talleresTabCatalog, icon: const Icon(Icons.grid_view)),
              Tab(text: i18n.talleresTabMine, icon: const Icon(Icons.school)),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildCatalogoTab(i18n),
            _buildMisTalleresTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildCatalogoTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureCatalogo,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.talleresError));
        }

        final talleres = (response.data!['talleres'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (talleres.isEmpty) {
          return Center(child: Text(i18n.talleresEmpty));
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: talleres.length,
            separatorBuilder: (_, __) => const SizedBox(height: 16),
            itemBuilder: (context, index) {
              final taller = talleres[index];
              final id = (taller['id'] as num?)?.toInt() ?? 0;
              final titulo = taller['titulo']?.toString() ?? '';
              final descripcion = taller['descripcion']?.toString() ?? '';
              final instructor = taller['instructor']?.toString() ?? '';
              final categoria = taller['categoria']?.toString() ?? '';
              final nivel = taller['nivel']?.toString() ?? '';
              final duracion = taller['duracion']?.toString() ?? '';
              final fechaInicio = taller['fecha_inicio']?.toString() ?? '';
              final imagen = taller['imagen']?.toString() ?? '';
              final plazasDisponibles = (taller['plazas_disponibles'] as num?)?.toInt() ?? 0;
              final plazasTotales = (taller['plazas_totales'] as num?)?.toInt() ?? 0;
              final precio = taller['precio']?.toString() ?? '0';

              return Card(
                elevation: 2,
                clipBehavior: Clip.antiAlias,
                child: InkWell(
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => TallerDetailScreen(tallerId: id),
                      ),
                    ).then((_) => _refresh());
                  },
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (imagen.isNotEmpty)
                        Stack(
                          children: [
                            Image.network(
                              imagen,
                              width: double.infinity,
                              height: 180,
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) => _buildPlaceholderImage(),
                            ),
                            if (plazasDisponibles == 0)
                              Positioned(
                                top: 8,
                                right: 8,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                  decoration: BoxDecoration(
                                    color: Colors.red,
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Text(
                                    i18n.talleresFull,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ),
                          ],
                        )
                      else
                        _buildPlaceholderImage(),
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              titulo,
                              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                    fontWeight: FontWeight.bold,
                                  ),
                            ),
                            const SizedBox(height: 8),
                            if (descripcion.isNotEmpty)
                              Text(
                                descripcion,
                                style: Theme.of(context).textTheme.bodyMedium,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            const SizedBox(height: 12),
                            Wrap(
                              spacing: 6,
                              runSpacing: 6,
                              children: [
                                if (categoria.isNotEmpty)
                                  Chip(
                                    label: Text(categoria),
                                    visualDensity: VisualDensity.compact,
                                  ),
                                if (nivel.isNotEmpty)
                                  Chip(
                                    label: Text(nivel),
                                    backgroundColor: _getNivelColor(nivel),
                                    visualDensity: VisualDensity.compact,
                                  ),
                                if (duracion.isNotEmpty)
                                  Chip(
                                    label: Text(duracion),
                                    avatar: const Icon(Icons.schedule, size: 16),
                                    visualDensity: VisualDensity.compact,
                                  ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Icon(Icons.person, size: 18, color: Colors.grey[600]),
                                const SizedBox(width: 4),
                                Expanded(
                                  child: Text(
                                    instructor,
                                    style: Theme.of(context).textTheme.bodySmall,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                            if (fechaInicio.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  Icon(Icons.calendar_today, size: 18, color: Colors.grey[600]),
                                  const SizedBox(width: 4),
                                  Text(
                                    '${i18n.talleresStartDate}: $fechaInicio',
                                    style: Theme.of(context).textTheme.bodySmall,
                                  ),
                                ],
                              ),
                            ],
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Expanded(
                                  child: LinearProgressIndicator(
                                    value: plazasTotales > 0 ? plazasDisponibles / plazasTotales : 0,
                                    backgroundColor: Colors.grey[300],
                                    color: plazasDisponibles > 5 ? Colors.green : Colors.orange,
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  '$plazasDisponibles/${plazasTotales}',
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  precio == '0' ? i18n.talleresFree : '${precio}€',
                                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                        fontWeight: FontWeight.bold,
                                        color: Theme.of(context).colorScheme.primary,
                                      ),
                                ),
                                FilledButton(
                                  onPressed: plazasDisponibles > 0
                                      ? () {
                                          Navigator.push(
                                            context,
                                            MaterialPageRoute(
                                              builder: (_) => TallerDetailScreen(tallerId: id),
                                            ),
                                          ).then((_) => _refresh());
                                        }
                                      : null,
                                  child: Text(i18n.talleresViewDetails),
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

  Widget _buildPlaceholderImage() {
    return Container(
      width: double.infinity,
      height: 180,
      color: Theme.of(context).colorScheme.surfaceVariant,
      child: const Icon(Icons.school, size: 80),
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

  Widget _buildMisTalleresTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureMisTalleres,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.talleresMineError));
        }

        final talleres = (response.data!['talleres'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (talleres.isEmpty) {
          return Center(child: Text(i18n.talleresMineEmpty));
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: talleres.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final taller = talleres[index];
              final id = (taller['id'] as num?)?.toInt() ?? 0;
              final inscripcionId = (taller['inscripcion_id'] as num?)?.toInt() ?? 0;
              final titulo = taller['titulo']?.toString() ?? '';
              final instructor = taller['instructor']?.toString() ?? '';
              final fechaInicio = taller['fecha_inicio']?.toString() ?? '';
              final progreso = ((taller['progreso'] as num?)?.toDouble() ?? 0.0).clamp(0.0, 100.0);
              final estado = taller['estado']?.toString() ?? 'activo';

              return Card(
                elevation: 1,
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: _getEstadoColor(estado),
                    child: Text(
                      '${progreso.toInt()}%',
                      style: const TextStyle(fontSize: 12, color: Colors.white),
                    ),
                  ),
                  title: Text(titulo),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 4),
                      Text(instructor),
                      const SizedBox(height: 4),
                      LinearProgressIndicator(
                        value: progreso / 100,
                        backgroundColor: Colors.grey[300],
                      ),
                      const SizedBox(height: 4),
                      if (fechaInicio.isNotEmpty)
                        Text(
                          '${i18n.talleresStartDate}: $fechaInicio',
                          style: Theme.of(context).textTheme.bodySmall,
                        ),
                    ],
                  ),
                  trailing: estado == 'activo'
                      ? PopupMenuButton<String>(
                          onSelected: (value) async {
                            if (value == 'view') {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => TallerDetailScreen(tallerId: id),
                                ),
                              ).then((_) => _refresh());
                            } else if (value == 'cancel') {
                              await _cancelarInscripcion(context, inscripcionId);
                            }
                          },
                          itemBuilder: (context) => [
                            PopupMenuItem(
                              value: 'view',
                              child: Row(
                                children: [
                                  const Icon(Icons.visibility, size: 20),
                                  const SizedBox(width: 8),
                                  Text(i18n.commonView),
                                ],
                              ),
                            ),
                            PopupMenuItem(
                              value: 'cancel',
                              child: Row(
                                children: [
                                  const Icon(Icons.cancel_outlined, size: 20),
                                  const SizedBox(width: 8),
                                  Text(i18n.talleresCancelEnrollment),
                                ],
                              ),
                            ),
                          ],
                        )
                      : null,
                ),
              );
            },
          ),
        );
      },
    );
  }

  Color _getEstadoColor(String estado) {
    switch (estado) {
      case 'activo':
        return Colors.green;
      case 'completado':
        return Colors.blue;
      case 'cancelado':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Future<void> _cancelarInscripcion(BuildContext context, int inscripcionId) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.talleresCancelEnrollment),
        content: Text(i18n.talleresCancelConfirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: Text(i18n.commonConfirm),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.cancelarInscripcionTaller(inscripcionId);
      if (context.mounted) {
        final msg = response.success
            ? i18n.talleresCancelSuccess
            : (response.error ?? i18n.talleresCancelError);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          _refresh();
        }
      }
    }
  }
}

/// Pantalla de detalle de un taller
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
    final i18n = AppLocalizations.of(context)!;

    return Scaffold(
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.talleresError));
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
                          color: Theme.of(context).colorScheme.surfaceVariant,
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
                      // Chips de información
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

                      // Descripción
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

                      // Información del instructor
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

                      // Detalles prácticos
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

                      // Materiales
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

                      // Sesiones
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

                      // Plazas disponibles
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

                      // Botón de inscripción
                      if (!inscrito && plazasDisponibles > 0)
                        FilledButton.icon(
                          onPressed: () => _inscribirse(context, precio),
                          icon: const Icon(Icons.person_add),
                          label: Text(
                            precio == '0'
                                ? i18n.talleresEnrollFree
                                : '${i18n.talleresEnroll} (${precio}€)',
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
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.talleresEnroll),
        content: Text(
          precio == '0'
              ? i18n.talleresEnrollConfirmFree
              : '${i18n.talleresEnrollConfirm} ${precio}€?',
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }
  }
}
