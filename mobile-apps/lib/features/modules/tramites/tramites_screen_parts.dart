part of 'tramites_screen.dart';

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
    final i18n = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.tramitesDetail),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return FlavorErrorState(
              message: i18n.tramitesError,
              onRetry: () => setState(() {
                final api = ref.read(apiClientProvider);
                _future = api.getTramite(widget.tramiteId);
              }),
              icon: Icons.description_outlined,
            );
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
    final i18n = AppLocalizations.of(context);
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
    final i18n = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.tramitesRequestDetail),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return FlavorErrorState(
              message: i18n.tramitesError,
              onRetry: () => setState(() {
                final api = ref.read(apiClientProvider);
                _future = api.getSolicitudTramite(widget.solicitudId);
              }),
              icon: Icons.assignment_outlined,
            );
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
