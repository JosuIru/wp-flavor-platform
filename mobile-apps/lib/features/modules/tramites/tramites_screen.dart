import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

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
    final i18n = AppLocalizations.of(context)!;

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
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.tramitesError));
        }

        final tramites = (response.data!['tramites'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (tramites.isEmpty) {
          return Center(child: Text(i18n.tramitesEmpty));
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
          return const Center(child: CircularProgressIndicator());
        }
        final response = snapshot.data!;
        if (!response.success || response.data == null) {
          return Center(child: Text(i18n.tramitesRequestsError));
        }

        final solicitudes = (response.data!['solicitudes'] as List<dynamic>? ?? [])
            .whereType<Map<String, dynamic>>()
            .toList();

        if (solicitudes.isEmpty) {
          return Center(child: Text(i18n.tramitesRequestsEmpty));
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

/// Pantalla de detalle de un trámite
class TramiteDetailScreen extends ConsumerStatefulWidget {
  final int tramiteId;

  const TramiteDetailScreen({
    super.key,
    required this.tramiteId,
  });

  @override
  ConsumerState<TramiteDetailScreen> createState() => _TramiteDetailScreenState();
}

class _TramiteDetailScreenState extends ConsumerState<TramiteDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getTramite(widget.tramiteId);
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.tramitesDetail),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.tramitesError));
          }

          final tramite = response.data!['tramite'] as Map<String, dynamic>? ?? {};
          final nombre = tramite['nombre']?.toString() ?? '';
          final descripcion = tramite['descripcion']?.toString() ?? '';
          final categoria = tramite['categoria']?.toString() ?? '';
          final requisitos = tramite['requisitos']?.toString() ?? '';
          final documentos = tramite['documentos']?.toString() ?? '';
          final pasos = tramite['pasos']?.toString() ?? '';
          final plazoResolucion = tramite['plazo_resolucion']?.toString() ?? '';
          final online = tramite['online'] == true || tramite['online'] == 1;
          final tasas = tramite['tasas']?.toString() ?? '';

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Título y categoría
                Card(
                  color: Theme.of(context).colorScheme.primaryContainer,
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          nombre,
                          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                                fontWeight: FontWeight.bold,
                                color: Theme.of(context).colorScheme.onPrimaryContainer,
                              ),
                        ),
                        if (categoria.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Chip(
                            label: Text(categoria),
                            avatar: const Icon(Icons.category, size: 16),
                          ),
                        ],
                        if (online) ...[
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              const Icon(Icons.cloud_done, color: Colors.green),
                              const SizedBox(width: 8),
                              Text(
                                i18n.tramitesCanRequestOnline,
                                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                      color: Colors.green,
                                      fontWeight: FontWeight.bold,
                                    ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Descripción
                if (descripcion.isNotEmpty) ...[
                  Text(
                    i18n.tramitesDescription,
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

                // Requisitos
                if (requisitos.isNotEmpty) ...[
                  Text(
                    i18n.tramitesRequirements,
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
                        children: requisitos.split('\n').map((req) {
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Icon(Icons.check_circle_outline, size: 18, color: Colors.green),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    req.trim(),
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

                // Documentos necesarios
                if (documentos.isNotEmpty) ...[
                  Text(
                    i18n.tramitesDocuments,
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
                        children: documentos.split('\n').map((doc) {
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Icon(Icons.attach_file, size: 18, color: Colors.blue),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    doc.trim(),
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

                // Pasos a seguir
                if (pasos.isNotEmpty) ...[
                  Text(
                    i18n.tramitesSteps,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 8),
                  ...pasos.split('\n').asMap().entries.map((entry) {
                    final index = entry.key;
                    final paso = entry.value;
                    return Card(
                      child: ListTile(
                        leading: CircleAvatar(
                          child: Text('${index + 1}'),
                        ),
                        title: Text(paso.trim()),
                      ),
                    );
                  }),
                  const SizedBox(height: 16),
                ],

                // Información adicional
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (plazoResolucion.isNotEmpty) ...[
                          Row(
                            children: [
                              const Icon(Icons.schedule, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                i18n.tramitesResolutionTime,
                                style: Theme.of(context).textTheme.labelMedium,
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            plazoResolucion,
                            style: Theme.of(context).textTheme.bodyLarge,
                          ),
                          const SizedBox(height: 12),
                        ],
                        if (tasas.isNotEmpty) ...[
                          Row(
                            children: [
                              const Icon(Icons.euro, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                i18n.tramitesFees,
                                style: Theme.of(context).textTheme.labelMedium,
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            tasas,
                            style: Theme.of(context).textTheme.bodyLarge,
                          ),
                        ],
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Botón de solicitud
                if (online)
                  FilledButton.icon(
                    onPressed: () => _iniciarSolicitud(context),
                    icon: const Icon(Icons.send),
                    label: Text(i18n.tramitesStartRequest),
                    style: FilledButton.styleFrom(
                      minimumSize: const Size.fromHeight(48),
                    ),
                  )
                else
                  Card(
                    color: Colors.blue.shade50,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          const Icon(Icons.info_outline, color: Colors.blue),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              i18n.tramitesRequiresInPerson,
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                const SizedBox(height: 16),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _iniciarSolicitud(BuildContext context) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final observacionesController = TextEditingController();

    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(i18n.tramitesStartRequest),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(i18n.tramitesStartConfirm),
              const SizedBox(height: 16),
              TextField(
                controller: observacionesController,
                decoration: InputDecoration(
                  labelText: i18n.tramitesComments,
                  border: const OutlineInputBorder(),
                  helperText: i18n.tramitesCommentsOptional,
                ),
                maxLines: 3,
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(i18n.commonCancel),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(i18n.commonSend),
            ),
          ],
        );
      },
    );

    if (result == true && context.mounted) {
      final response = await api.iniciarSolicitudTramite(
        tramiteId: widget.tramiteId,
        observaciones: observacionesController.text.trim(),
      );

      if (context.mounted) {
        final msg = response.success
            ? i18n.tramitesRequestSuccess
            : (response.error ?? i18n.tramitesRequestError);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          Navigator.pop(context);
        }
      }
    }

    observacionesController.dispose();
  }
}

/// Pantalla de detalle de una solicitud
class SolicitudDetailScreen extends ConsumerStatefulWidget {
  final int solicitudId;

  const SolicitudDetailScreen({
    super.key,
    required this.solicitudId,
  });

  @override
  ConsumerState<SolicitudDetailScreen> createState() => _SolicitudDetailScreenState();
}

class _SolicitudDetailScreenState extends ConsumerState<SolicitudDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _future = api.getSolicitudTramite(widget.solicitudId);
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.tramitesRequestDetail),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.tramitesError));
          }

          final solicitud = response.data!['solicitud'] as Map<String, dynamic>? ?? {};
          final tramiteNombre = solicitud['tramite_nombre']?.toString() ?? '';
          final numeroExpediente = solicitud['numero_expediente']?.toString() ?? '';
          final fechaSolicitud = solicitud['fecha_solicitud']?.toString() ?? '';
          final estado = solicitud['estado']?.toString() ?? 'pendiente';
          final observaciones = solicitud['observaciones']?.toString() ?? '';
          final ultimaActualizacion = solicitud['ultima_actualizacion']?.toString() ?? '';
          final notas = solicitud['notas']?.toString() ?? '';
          final historial = (solicitud['historial'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Información principal
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tramiteNombre,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 12),
                        _buildInfoRow(
                          Icons.confirmation_number,
                          i18n.tramitesFileNumber,
                          numeroExpediente,
                          context,
                        ),
                        const SizedBox(height: 8),
                        _buildInfoRow(
                          Icons.calendar_today,
                          i18n.tramitesRequestDate,
                          fechaSolicitud,
                          context,
                        ),
                        if (ultimaActualizacion.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          _buildInfoRow(
                            Icons.update,
                            i18n.tramitesLastUpdate,
                            ultimaActualizacion,
                            context,
                          ),
                        ],
                        const SizedBox(height: 12),
                        Chip(
                          label: Text(_getEstadoLabel(estado, i18n)),
                          avatar: Icon(_getEstadoIcon(estado), size: 18),
                          backgroundColor: _getEstadoColor(estado).withOpacity(0.2),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Observaciones
                if (observaciones.isNotEmpty) ...[
                  Text(
                    i18n.tramitesComments,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 8),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Text(
                        observaciones,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // Notas de la administración
                if (notas.isNotEmpty) ...[
                  Text(
                    i18n.tramitesAdminNotes,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 8),
                  Card(
                    color: Colors.blue.shade50,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Icon(Icons.info_outline, color: Colors.blue),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              notas,
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // Historial
                if (historial.isNotEmpty) ...[
                  Text(
                    i18n.tramitesHistory,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 8),
                  ...historial.map((evento) {
                    final fecha = evento['fecha']?.toString() ?? '';
                    final accion = evento['accion']?.toString() ?? '';
                    final descripcion = evento['descripcion']?.toString() ?? '';

                    return Card(
                      child: ListTile(
                        leading: const Icon(Icons.history),
                        title: Text(accion),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (descripcion.isNotEmpty) Text(descripcion),
                            Text(
                              fecha,
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                          ],
                        ),
                      ),
                    );
                  }),
                ],
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value, BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 18),
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
