import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class AyudaVecinalScreen extends ConsumerStatefulWidget {
  const AyudaVecinalScreen({super.key});

  @override
  ConsumerState<AyudaVecinalScreen> createState() => _AyudaVecinalScreenState();
}

class _AyudaVecinalScreenState extends ConsumerState<AyudaVecinalScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getAyudaVecinal();
  }

  Future<void> _refresh() async {
    setState(() => _future = ref.read(apiClientProvider).getAyudaVecinal());
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Ayuda Vecinal'),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.ayudaTabRequests, icon: const Icon(Icons.help_outline)),
              Tab(text: i18n.ayudaTabVolunteers, icon: const Icon(Icons.volunteer_activism)),
              Tab(text: i18n.ayudaTabMine, icon: const Icon(Icons.person)),
            ],
          ),
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () => _solicitarAyuda(context),
          icon: const Icon(Icons.add),
          label: Text(i18n.ayudaRequestHelp),
        ),
        body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
          future: _future,
          builder: (context, snapshot) {
            if (!snapshot.hasData) {
              return const Center(child: CircularProgressIndicator());
            }

            final response = snapshot.data!;
            if (!response.success || response.data == null) {
              return Center(child: Text(i18n.ayudaError));
            }

            final data = response.data!;
            final solicitudes = (data['solicitudes'] as List?)?.cast<Map<String, dynamic>>() ?? [];
            final voluntarios = (data['voluntarios'] as List?)?.cast<Map<String, dynamic>>() ?? [];
            final misSolicitudes = (data['mis_solicitudes'] as List?)?.cast<Map<String, dynamic>>() ?? [];

            return TabBarView(
              children: [
                _buildSolicitudesTab(context, solicitudes, i18n),
                _buildVoluntariosTab(context, voluntarios, i18n),
                _buildMisSolicitudesTab(context, misSolicitudes, i18n),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildSolicitudesTab(BuildContext context, List<Map<String, dynamic>> solicitudes, AppLocalizations i18n) {
    if (solicitudes.isEmpty) {
      return Center(child: Text(i18n.ayudaNoRequests));
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: solicitudes.length,
        itemBuilder: (context, index) {
          final solicitud = solicitudes[index];
          final id = (solicitud['id'] as num?)?.toInt() ?? 0;
          final titulo = solicitud['titulo']?.toString() ?? '';
          final descripcion = solicitud['descripcion']?.toString() ?? '';
          final categoria = solicitud['categoria']?.toString() ?? '';
          final urgencia = solicitud['urgencia']?.toString() ?? 'normal';
          final usuario = solicitud['usuario']?.toString() ?? '';
          final fecha = solicitud['fecha']?.toString() ?? '';
          final zona = solicitud['zona']?.toString() ?? '';

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: InkWell(
              onTap: () => _verDetalleSolicitud(context, solicitud),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: _getCategoriaColor(categoria).withOpacity(0.2),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Icon(_getCategoriaIcon(categoria), color: _getCategoriaColor(categoria), size: 24),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                titulo,
                                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                              ),
                              Row(
                                children: [
                                  Chip(
                                    label: Text(categoria),
                                    visualDensity: VisualDensity.compact,
                                  ),
                                  if (urgencia == 'urgente') ...[
                                    const SizedBox(width: 4),
                                    Chip(
                                      label: Text(i18n.ayudaUrgent),
                                      visualDensity: VisualDensity.compact,
                                      backgroundColor: Colors.red.shade100,
                                    ),
                                  ],
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(descripcion, maxLines: 2, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(Icons.person, size: 14),
                        const SizedBox(width: 4),
                        Text(usuario, style: Theme.of(context).textTheme.bodySmall),
                        const SizedBox(width: 16),
                        const Icon(Icons.place, size: 14),
                        const SizedBox(width: 4),
                        Text(zona, style: Theme.of(context).textTheme.bodySmall),
                        const Spacer(),
                        Text(fecha, style: Theme.of(context).textTheme.bodySmall),
                      ],
                    ),
                    const SizedBox(height: 8),
                    FilledButton.icon(
                      onPressed: () => _ofrecerAyuda(context, id),
                      icon: const Icon(Icons.volunteer_activism),
                      label: Text(i18n.ayudaOfferHelp),
                      style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(36)),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildVoluntariosTab(BuildContext context, List<Map<String, dynamic>> voluntarios, AppLocalizations i18n) {
    if (voluntarios.isEmpty) {
      return Center(child: Text(i18n.ayudaNoVolunteers));
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: voluntarios.length,
        itemBuilder: (context, index) {
          final voluntario = voluntarios[index];
          final nombre = voluntario['nombre']?.toString() ?? '';
          final categorias = voluntario['categorias']?.toString() ?? '';
          final zona = voluntario['zona']?.toString() ?? '';
          final disponibilidad = voluntario['disponibilidad']?.toString() ?? '';
          final ayudasRealizadas = (voluntario['ayudas_realizadas'] as num?)?.toInt() ?? 0;

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: Colors.green,
                child: Text(
                  ayudasRealizadas.toString(),
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                ),
              ),
              title: Text(nombre),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 4),
                  Wrap(
                    spacing: 4,
                    runSpacing: 4,
                    children: categorias.split(',').map((cat) {
                      return Chip(
                        label: Text(cat.trim()),
                        visualDensity: VisualDensity.compact,
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.place, size: 14),
                      const SizedBox(width: 4),
                      Text(zona),
                      const SizedBox(width: 12),
                      const Icon(Icons.schedule, size: 14),
                      const SizedBox(width: 4),
                      Expanded(child: Text(disponibilidad)),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildMisSolicitudesTab(BuildContext context, List<Map<String, dynamic>> solicitudes, AppLocalizations i18n) {
    if (solicitudes.isEmpty) {
      return Center(child: Text(i18n.ayudaMyRequestsEmpty));
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: solicitudes.length,
        itemBuilder: (context, index) {
          final solicitud = solicitudes[index];
          final id = (solicitud['id'] as num?)?.toInt() ?? 0;
          final titulo = solicitud['titulo']?.toString() ?? '';
          final categoria = solicitud['categoria']?.toString() ?? '';
          final estado = solicitud['estado']?.toString() ?? 'pendiente';
          final fecha = solicitud['fecha']?.toString() ?? '';
          final voluntarios = (solicitud['num_voluntarios'] as num?)?.toInt() ?? 0;

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: _getEstadoColor(estado),
                child: Icon(_getEstadoIcon(estado), color: Colors.white, size: 20),
              ),
              title: Text(titulo),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Chip(label: Text(categoria), visualDensity: VisualDensity.compact),
                  Text(fecha, style: Theme.of(context).textTheme.bodySmall),
                  if (voluntarios > 0)
                    Text('$voluntarios ${i18n.ayudaVolunteers}', style: TextStyle(color: Colors.green[700])),
                  Chip(
                    label: Text(_getEstadoLabel(estado, i18n)),
                    visualDensity: VisualDensity.compact,
                    backgroundColor: _getEstadoColor(estado).withOpacity(0.2),
                  ),
                ],
              ),
              trailing: estado == 'pendiente'
                  ? IconButton(
                      icon: const Icon(Icons.cancel_outlined),
                      onPressed: () => _cancelarSolicitud(context, id),
                    )
                  : null,
            ),
          );
        },
      ),
    );
  }

  IconData _getCategoriaIcon(String categoria) {
    switch (categoria.toLowerCase()) {
      case 'compras':
        return Icons.shopping_cart;
      case 'transporte':
        return Icons.directions_car;
      case 'cuidados':
        return Icons.favorite;
      case 'tecnología':
        return Icons.computer;
      case 'reparaciones':
        return Icons.build;
      case 'tareas domésticas':
        return Icons.cleaning_services;
      case 'acompañamiento':
        return Icons.accessibility;
      default:
        return Icons.help_outline;
    }
  }

  Color _getCategoriaColor(String categoria) {
    switch (categoria.toLowerCase()) {
      case 'compras':
        return Colors.blue;
      case 'transporte':
        return Colors.green;
      case 'cuidados':
        return Colors.red;
      case 'tecnología':
        return Colors.purple;
      case 'reparaciones':
        return Colors.orange;
      case 'tareas domésticas':
        return Colors.teal;
      case 'acompañamiento':
        return Colors.pink;
      default:
        return Colors.grey;
    }
  }

  Color _getEstadoColor(String estado) {
    switch (estado) {
      case 'pendiente':
        return Colors.orange;
      case 'en_curso':
        return Colors.blue;
      case 'completada':
        return Colors.green;
      case 'cancelada':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  IconData _getEstadoIcon(String estado) {
    switch (estado) {
      case 'pendiente':
        return Icons.hourglass_empty;
      case 'en_curso':
        return Icons.play_arrow;
      case 'completada':
        return Icons.check_circle;
      case 'cancelada':
        return Icons.cancel;
      default:
        return Icons.info;
    }
  }

  String _getEstadoLabel(String estado, AppLocalizations i18n) {
    switch (estado) {
      case 'pendiente':
        return i18n.ayudaStatusPending;
      case 'en_curso':
        return i18n.ayudaStatusInProgress;
      case 'completada':
        return i18n.ayudaStatusCompleted;
      case 'cancelada':
        return i18n.ayudaStatusCancelled;
      default:
        return estado;
    }
  }

  void _verDetalleSolicitud(BuildContext context, Map<String, dynamic> solicitud) {
    final i18n = AppLocalizations.of(context)!;
    final titulo = solicitud['titulo']?.toString() ?? '';
    final descripcion = solicitud['descripcion']?.toString() ?? '';
    final categoria = solicitud['categoria']?.toString() ?? '';
    final usuario = solicitud['usuario']?.toString() ?? '';
    final zona = solicitud['zona']?.toString() ?? '';
    final telefono = solicitud['telefono']?.toString() ?? '';

    showModalBottomSheet(
      context: context,
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(titulo, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Chip(label: Text(categoria)),
              const SizedBox(height: 16),
              Text(descripcion, style: Theme.of(context).textTheme.bodyLarge),
              const SizedBox(height: 16),
              Row(
                children: [
                  const Icon(Icons.person, size: 20),
                  const SizedBox(width: 8),
                  Text(usuario),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.place, size: 20),
                  const SizedBox(width: 8),
                  Text(zona),
                ],
              ),
              if (telefono.isNotEmpty) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    const Icon(Icons.phone, size: 20),
                    const SizedBox(width: 8),
                    Text(telefono),
                  ],
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  Future<void> _solicitarAyuda(BuildContext context) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final tituloController = TextEditingController();
    final descripcionController = TextEditingController();
    String categoria = 'compras';
    String urgencia = 'normal';

    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(i18n.ayudaRequestHelp),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: tituloController,
                      decoration: InputDecoration(labelText: i18n.ayudaTitle),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: descripcionController,
                      decoration: InputDecoration(labelText: i18n.ayudaDescription),
                      maxLines: 3,
                    ),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<String>(
                      value: categoria,
                      decoration: InputDecoration(labelText: i18n.ayudaCategory),
                      items: ['compras', 'transporte', 'cuidados', 'tecnología', 'reparaciones', 'tareas domésticas', 'acompañamiento']
                          .map((cat) => DropdownMenuItem(value: cat, child: Text(cat)))
                          .toList(),
                      onChanged: (value) => setDialogState(() => categoria = value!),
                    ),
                    const SizedBox(height: 16),
                    SegmentedButton<String>(
                      segments: [
                        ButtonSegment(value: 'normal', label: Text(i18n.ayudaNormal)),
                        ButtonSegment(value: 'urgente', label: Text(i18n.ayudaUrgent)),
                      ],
                      selected: {urgencia},
                      onSelectionChanged: (Set<String> newSelection) {
                        setDialogState(() => urgencia = newSelection.first);
                      },
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(onPressed: () => Navigator.pop(context, false), child: Text(i18n.commonCancel)),
                FilledButton(onPressed: () => Navigator.pop(context, true), child: Text(i18n.commonSend)),
              ],
            );
          },
        );
      },
    );

    if (result == true && context.mounted) {
      final response = await api.crearSolicitudAyuda(
        titulo: tituloController.text.trim(),
        descripcion: descripcionController.text.trim(),
        categoria: categoria,
        urgencia: urgencia,
      );
      if (context.mounted) {
        final msg = response.success ? i18n.ayudaRequestSuccess : (response.error ?? i18n.ayudaRequestError);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
        if (response.success) _refresh();
      }
    }

    tituloController.dispose();
    descripcionController.dispose();
  }

  Future<void> _ofrecerAyuda(BuildContext context, int solicitudId) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.ayudaOfferHelp),
        content: Text(i18n.ayudaOfferConfirm),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text(i18n.commonCancel)),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: Text(i18n.commonConfirm)),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.ofrecerAyuda(solicitudId);
      if (context.mounted) {
        final msg = response.success ? i18n.ayudaOfferSuccess : (response.error ?? i18n.ayudaOfferError);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
        if (response.success) _refresh();
      }
    }
  }

  Future<void> _cancelarSolicitud(BuildContext context, int solicitudId) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.ayudaCancelRequest),
        content: Text(i18n.ayudaCancelConfirm),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text(i18n.commonCancel)),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: Text(i18n.commonConfirm)),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      final response = await api.cancelarSolicitudAyuda(solicitudId);
      if (context.mounted) {
        final msg = response.success ? i18n.ayudaCancelSuccess : (response.error ?? i18n.ayudaCancelError);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
        if (response.success) _refresh();
      }
    }
  }
}
