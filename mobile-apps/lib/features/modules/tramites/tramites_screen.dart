import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'tramites_screen_parts.dart';

class TramitesScreen extends ConsumerStatefulWidget {
  const TramitesScreen({super.key});

  @override
  ConsumerState<TramitesScreen> createState() => _TramitesScreenState();
}

class _TramitesScreenState extends ConsumerState<TramitesScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _futureTramites;
  late Future<ApiResponse<Map<String, dynamic>>> _futureSolicitudes;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _futureTramites = api.getTramites();
    _futureSolicitudes = api.getMisSolicitudesTramites();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _futureTramites = api.getTramites();
      _futureSolicitudes = api.getMisSolicitudesTramites();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Trámites'),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.tramitesTabCatalog, icon: const Icon(Icons.folder_outlined)),
              Tab(text: i18n.tramitesTabRequests, icon: const Icon(Icons.description)),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildTramitesTab(i18n),
            _buildSolicitudesTab(i18n),
          ],
        ),
      ),
    );
  }

  Widget _buildTramitesTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureTramites,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.tramitesError,
            onRetry: _refresh,
            icon: Icons.description_outlined,
          );
        }

        final tramites = (response.data!['tramites'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (tramites.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.description_outlined,
            title: i18n.tramitesEmpty,
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: tramites.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final tramite = tramites[index];
              final id = (tramite['id'] as num?)?.toInt() ?? 0;
              final nombre = tramite['nombre']?.toString() ?? '';
              final descripcion = tramite['descripcion']?.toString() ?? '';
              final categoria = tramite['categoria']?.toString() ?? '';
              final plazoResolucion = tramite['plazo_resolucion']?.toString() ?? '';
              final online = tramite['online'] == true || tramite['online'] == 1;

              return Card(
                elevation: 1,
                child: InkWell(
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => TramiteDetailScreen(tramiteId: id),
                      ),
                    ).then((_) => _refresh());
                  },
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Theme.of(context).colorScheme.primaryContainer,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Icon(
                                _getCategoriaIcon(categoria),
                                size: 28,
                                color: Theme.of(context).colorScheme.primary,
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    nombre,
                                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                          fontWeight: FontWeight.bold,
                                        ),
                                  ),
                                  if (categoria.isNotEmpty) ...[
                                    const SizedBox(height: 4),
                                    Text(
                                      categoria,
                                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                            color: Theme.of(context).colorScheme.primary,
                                          ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                            if (online)
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: Colors.green,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  i18n.tramitesOnline,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                          ],
                        ),
                        if (descripcion.isNotEmpty) ...[
                          const SizedBox(height: 12),
                          Text(
                            descripcion,
                            style: Theme.of(context).textTheme.bodyMedium,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                        if (plazoResolucion.isNotEmpty) ...[
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Icon(Icons.schedule, size: 16, color: Colors.grey[600]),
                              const SizedBox(width: 4),
                              Text(
                                '${i18n.tramitesResolutionTime}: $plazoResolucion',
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                            ],
                          ),
                        ],
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

  IconData _getCategoriaIcon(String categoria) {
    switch (categoria.toLowerCase()) {
      case 'certificados':
        return Icons.verified_outlined;
      case 'licencias':
        return Icons.card_membership;
      case 'ayudas':
        return Icons.volunteer_activism;
      case 'urbanismo':
        return Icons.apartment;
      case 'medio ambiente':
        return Icons.eco;
      case 'cultura':
        return Icons.museum;
      case 'deportes':
        return Icons.sports;
      case 'tributos':
        return Icons.payment;
      case 'registro':
        return Icons.how_to_reg;
      default:
        return Icons.description;
    }
  }

  Widget _buildSolicitudesTab(AppLocalizations i18n) {
    return FutureBuilder<ApiResponse<Map<String, dynamic>>>(
      future: _futureSolicitudes,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const FlavorLoadingState();
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return FlavorErrorState(
            message: i18n.tramitesRequestsError,
            onRetry: _refresh,
            icon: Icons.assignment_outlined,
          );
        }

        final solicitudes = (response.data!['solicitudes'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (solicitudes.isEmpty) {
          return FlavorEmptyState(
            icon: Icons.assignment_outlined,
            title: i18n.tramitesRequestsEmpty,
          );
        }

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: solicitudes.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final solicitud = solicitudes[index];
              final id = (solicitud['id'] as num?)?.toInt() ?? 0;
              final tramiteNombre = solicitud['tramite_nombre']?.toString() ?? '';
              final numeroExpediente = solicitud['numero_expediente']?.toString() ?? '';
              final fechaSolicitud = solicitud['fecha_solicitud']?.toString() ?? '';
              final estado = solicitud['estado']?.toString() ?? 'pendiente';
              final ultimaActualizacion = solicitud['ultima_actualizacion']?.toString() ?? '';

              return Card(
                elevation: 1,
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: _getEstadoColor(estado),
                    child: Icon(
                      _getEstadoIcon(estado),
                      color: Colors.white,
                      size: 20,
                    ),
                  ),
                  title: Text(tramiteNombre),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 4),
                      if (numeroExpediente.isNotEmpty)
                        Text('${i18n.tramitesFileNumber}: $numeroExpediente'),
                      Text('${i18n.tramitesRequestDate}: $fechaSolicitud'),
                      if (ultimaActualizacion.isNotEmpty)
                        Text('${i18n.tramitesLastUpdate}: $ultimaActualizacion'),
                      const SizedBox(height: 4),
                      Chip(
                        label: Text(_getEstadoLabel(estado, i18n)),
                        visualDensity: VisualDensity.compact,
                        backgroundColor: _getEstadoColor(estado).withOpacity(0.2),
                      ),
                    ],
                  ),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => SolicitudDetailScreen(solicitudId: id),
                      ),
                    ).then((_) => _refresh());
                  },
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
      case 'pendiente':
        return Colors.orange;
      case 'en_revision':
        return Colors.blue;
      case 'aprobada':
        return Colors.green;
      case 'rechazada':
        return Colors.red;
      case 'completada':
        return Colors.teal;
      default:
        return Colors.grey;
    }
  }

  IconData _getEstadoIcon(String estado) {
    switch (estado) {
      case 'pendiente':
        return Icons.hourglass_empty;
      case 'en_revision':
        return Icons.search;
      case 'aprobada':
        return Icons.check_circle;
      case 'rechazada':
        return Icons.cancel;
      case 'completada':
        return Icons.done_all;
      default:
        return Icons.info;
    }
  }

  String _getEstadoLabel(String estado, AppLocalizations i18n) {
    switch (estado) {
      case 'pendiente':
        return i18n.tramitesStatusPending;
      case 'en_revision':
        return i18n.tramitesStatusInReview;
      case 'aprobada':
        return i18n.tramitesStatusApproved;
      case 'rechazada':
        return i18n.tramitesStatusRejected;
      case 'completada':
        return i18n.tramitesStatusCompleted;
      default:
        return estado;
    }
  }
}

