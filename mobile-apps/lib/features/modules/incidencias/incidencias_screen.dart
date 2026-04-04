import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'incidencias_screen_parts.dart';

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
    final i18n = AppLocalizations.of(context);
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: 'Error al cargar incidencias',
            onRetry: _refresh,
            icon: Icons.report_problem_outlined,
          );
        }

        final incidencias = (response.data!['incidencias'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (incidencias.isEmpty) {
          return const FlavorEmptyState(
            icon: Icons.report_problem_outlined,
            title: 'No hay incidencias reportadas',
          );
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
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: 'Error al cargar tus incidencias',
            onRetry: _refresh,
            icon: Icons.assignment_outlined,
          );
        }

        final incidencias = (response.data!['incidencias'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (incidencias.isEmpty) {
          return const FlavorEmptyState(
            icon: Icons.check_circle_outline,
            title: 'No tienes incidencias reportadas',
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
