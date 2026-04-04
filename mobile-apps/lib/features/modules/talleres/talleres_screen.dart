import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'talleres_screen_parts.dart';

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
    final i18n = AppLocalizations.of(context);

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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.talleresError,
            onRetry: _refresh,
            icon: Icons.school_outlined,
          );
        }

        final talleres = (response.data!['talleres'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (talleres.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.school_outlined,
            title: i18n.talleresEmpty,
          );
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
                                  '$plazasDisponibles/$plazasTotales',
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  precio == '0' ? i18n.talleresFree : '$precio€',
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
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.talleresMineError,
            onRetry: _refresh,
            icon: Icons.school_outlined,
          );
        }

        final talleres = (response.data!['talleres'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (talleres.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.school_outlined,
            title: i18n.talleresMineEmpty,
          );
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
    final i18n = AppLocalizations.of(context);
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
  }
}
