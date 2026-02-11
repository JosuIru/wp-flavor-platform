import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class HuertosUrbanosScreen extends ConsumerStatefulWidget {
  const HuertosUrbanosScreen({super.key});

  @override
  ConsumerState<HuertosUrbanosScreen> createState() => _HuertosUrbanosScreenState();
}

class _HuertosUrbanosScreenState extends ConsumerState<HuertosUrbanosScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _futureDashboard;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _futureDashboard = api.getHuertosUrbanosDashboard();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _futureDashboard = api.getHuertosUrbanosDashboard();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Huertos Urbanos'),
        actions: [
          IconButton(
            icon: const Icon(Icons.info_outline),
            onPressed: () => _showInfo(context),
            tooltip: i18n.commonInfo,
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _solicitarParcela(context),
        icon: const Icon(Icons.add_location_alt),
        label: Text(i18n.huertosRequestPlot),
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _futureDashboard,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.huertosError));
          }

          final dashboard = response.data!;
          final misParcelas = (dashboard['mis_parcelas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          final parcelasDisponibles = (dashboard['parcelas_disponibles'] as num?)?.toInt() ?? 0;
          final tareasPendientes = (dashboard['tareas_pendientes'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          final proximosCultivos = (dashboard['proximos_cultivos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          final intercambios = (dashboard['intercambios'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();

          return RefreshIndicator(
            onRefresh: _refresh,
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Estadísticas generales
                  _buildStatsCard(context, dashboard),
                  const SizedBox(height: 16),

                  // Mis parcelas
                  if (misParcelas.isNotEmpty) ...[
                    _buildSectionHeader(
                      context,
                      i18n.huertosMyPlots,
                      Icons.grass,
                    ),
                    const SizedBox(height: 8),
                    ...misParcelas.map((parcela) => _buildParcelaCard(context, parcela, i18n)),
                    const SizedBox(height: 16),
                  ],

                  // Tareas pendientes
                  if (tareasPendientes.isNotEmpty) ...[
                    _buildSectionHeader(
                      context,
                      i18n.huertosPendingTasks,
                      Icons.task_alt,
                    ),
                    const SizedBox(height: 8),
                    ...tareasPendientes.map((tarea) => _buildTareaCard(context, tarea, i18n)),
                    const SizedBox(height: 16),
                  ],

                  // Calendario de cultivos
                  if (proximosCultivos.isNotEmpty) ...[
                    _buildSectionHeader(
                      context,
                      i18n.huertosSeasonalCalendar,
                      Icons.calendar_month,
                    ),
                    const SizedBox(height: 8),
                    ...proximosCultivos.map((cultivo) => _buildCultivoCard(context, cultivo, i18n)),
                    const SizedBox(height: 16),
                  ],

                  // Intercambios disponibles
                  if (intercambios.isNotEmpty) ...[
                    _buildSectionHeader(
                      context,
                      i18n.huertosExchanges,
                      Icons.swap_horiz,
                    ),
                    const SizedBox(height: 8),
                    ...intercambios.map((intercambio) => _buildIntercambioCard(context, intercambio, i18n)),
                    const SizedBox(height: 16),
                  ],

                  // Parcelas disponibles
                  if (parcelasDisponibles > 0 && misParcelas.isEmpty) ...[
                    Card(
                      color: Colors.green.shade50,
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            const Icon(Icons.eco, color: Colors.green, size: 40),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    i18n.huertosAvailablePlots,
                                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                          fontWeight: FontWeight.bold,
                                          color: Colors.green[900],
                                        ),
                                  ),
                                  Text(
                                    '$parcelasDisponibles ${i18n.huertosPlots}',
                                    style: Theme.of(context).textTheme.bodyLarge,
                                  ),
                                ],
                              ),
                            ),
                            const Icon(Icons.arrow_forward, color: Colors.green),
                          ],
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 80), // Espacio para FAB
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildStatsCard(BuildContext context, Map<String, dynamic> dashboard) {
    final i18n = AppLocalizations.of(context)!;
    final totalParcelas = (dashboard['total_parcelas'] as num?)?.toInt() ?? 0;
    final parcelasOcupadas = (dashboard['parcelas_ocupadas'] as num?)?.toInt() ?? 0;
    final participantes = (dashboard['participantes'] as num?)?.toInt() ?? 0;

    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: _buildStatItem(
                    context,
                    Icons.grid_on,
                    i18n.huertosPlots,
                    '$totalParcelas',
                    Colors.green,
                  ),
                ),
                Expanded(
                  child: _buildStatItem(
                    context,
                    Icons.people,
                    i18n.huertosParticipants,
                    '$participantes',
                    Colors.blue,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            LinearProgressIndicator(
              value: totalParcelas > 0 ? parcelasOcupadas / totalParcelas : 0,
              backgroundColor: Colors.grey[300],
              color: Colors.green,
            ),
            const SizedBox(height: 8),
            Text(
              '$parcelasOcupadas/${totalParcelas} ${i18n.huertosOccupied}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatItem(BuildContext context, IconData icon, String label, String value, Color color) {
    return Column(
      children: [
        Icon(icon, size: 32, color: color),
        const SizedBox(height: 4),
        Text(
          value,
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
                color: color,
              ),
        ),
        Text(
          label,
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ],
    );
  }

  Widget _buildSectionHeader(BuildContext context, String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, color: Theme.of(context).colorScheme.primary),
        const SizedBox(width: 8),
        Text(
          title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
      ],
    );
  }

  Widget _buildParcelaCard(BuildContext context, Map<String, dynamic> parcela, AppLocalizations i18n) {
    final numero = parcela['numero']?.toString() ?? '';
    final tamanio = parcela['tamanio']?.toString() ?? '';
    final cultivoActual = parcela['cultivo_actual']?.toString() ?? '';
    final fechaSiembra = parcela['fecha_siembra']?.toString() ?? '';
    final imagen = parcela['imagen']?.toString() ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: () {
          // Navegar a detalle de parcela
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              if (imagen.isNotEmpty)
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    imagen,
                    width: 80,
                    height: 80,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => _buildPlaceholderImage(),
                  ),
                )
              else
                _buildPlaceholderImage(),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${i18n.huertosPlot} $numero',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 4),
                    if (cultivoActual.isNotEmpty)
                      Row(
                        children: [
                          const Icon(Icons.eco, size: 16, color: Colors.green),
                          const SizedBox(width: 4),
                          Text(cultivoActual),
                        ],
                      ),
                    if (tamanio.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        '$tamanio m²',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                    if (fechaSiembra.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        '${i18n.huertosPlantedOn}: $fechaSiembra',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ],
                ),
              ),
              const Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPlaceholderImage() {
    return Container(
      width: 80,
      height: 80,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceVariant,
        borderRadius: BorderRadius.circular(8),
      ),
      child: const Icon(Icons.grass, size: 40),
    );
  }

  Widget _buildTareaCard(BuildContext context, Map<String, dynamic> tarea, AppLocalizations i18n) {
    final id = (tarea['id'] as num?)?.toInt() ?? 0;
    final descripcion = tarea['descripcion']?.toString() ?? '';
    final fecha = tarea['fecha']?.toString() ?? '';
    final prioridad = tarea['prioridad']?.toString() ?? 'media';
    final tipo = tarea['tipo']?.toString() ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: _getPrioridadColor(prioridad),
          child: Icon(_getTipoIcon(tipo), color: Colors.white, size: 20),
        ),
        title: Text(descripcion),
        subtitle: Text(fecha),
        trailing: IconButton(
          icon: const Icon(Icons.check_circle_outline),
          onPressed: () => _completarTarea(context, id),
          tooltip: i18n.huertosMarkComplete,
        ),
      ),
    );
  }

  Color _getPrioridadColor(String prioridad) {
    switch (prioridad) {
      case 'alta':
        return Colors.red;
      case 'media':
        return Colors.orange;
      case 'baja':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  IconData _getTipoIcon(String tipo) {
    switch (tipo.toLowerCase()) {
      case 'riego':
        return Icons.water_drop;
      case 'abono':
        return Icons.compost;
      case 'poda':
        return Icons.cut;
      case 'siembra':
        return Icons.eco;
      case 'cosecha':
        return Icons.agriculture;
      default:
        return Icons.task_alt;
    }
  }

  Widget _buildCultivoCard(BuildContext context, Map<String, dynamic> cultivo, AppLocalizations i18n) {
    final nombre = cultivo['nombre']?.toString() ?? '';
    final temporada = cultivo['temporada']?.toString() ?? '';
    final mes = cultivo['mes']?.toString() ?? '';
    final dificultad = cultivo['dificultad']?.toString() ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: const CircleAvatar(
          backgroundColor: Colors.green,
          child: Icon(Icons.calendar_today, color: Colors.white, size: 20),
        ),
        title: Text(nombre),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('$temporada - $mes'),
            if (dificultad.isNotEmpty)
              Chip(
                label: Text(dificultad),
                visualDensity: VisualDensity.compact,
                backgroundColor: _getDificultadColor(dificultad),
              ),
          ],
        ),
      ),
    );
  }

  Color _getDificultadColor(String dificultad) {
    switch (dificultad.toLowerCase()) {
      case 'fácil':
        return Colors.green.shade100;
      case 'media':
        return Colors.orange.shade100;
      case 'difícil':
        return Colors.red.shade100;
      default:
        return Colors.grey.shade100;
    }
  }

  Widget _buildIntercambioCard(BuildContext context, Map<String, dynamic> intercambio, AppLocalizations i18n) {
    final id = (intercambio['id'] as num?)?.toInt() ?? 0;
    final usuario = intercambio['usuario']?.toString() ?? '';
    final ofrece = intercambio['ofrece']?.toString() ?? '';
    final busca = intercambio['busca']?.toString() ?? '';
    final fecha = intercambio['fecha']?.toString() ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: const CircleAvatar(
          child: Icon(Icons.swap_horiz),
        ),
        title: Text(usuario),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.arrow_circle_up, size: 16, color: Colors.green),
                const SizedBox(width: 4),
                Expanded(child: Text('${i18n.huertosOffers}: $ofrece')),
              ],
            ),
            Row(
              children: [
                const Icon(Icons.arrow_circle_down, size: 16, color: Colors.orange),
                const SizedBox(width: 4),
                Expanded(child: Text('${i18n.huertosSeeks}: $busca')),
              ],
            ),
            Text(
              fecha,
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
        trailing: IconButton(
          icon: const Icon(Icons.message),
          onPressed: () => _contactarIntercambio(context, id),
          tooltip: i18n.commonContact,
        ),
      ),
    );
  }

  Future<void> _solicitarParcela(BuildContext context) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final observacionesController = TextEditingController();
    String tamanioPreferido = 'pequeño';

    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text(i18n.huertosRequestPlot),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(i18n.huertosPlotSize),
                    const SizedBox(height: 8),
                    SegmentedButton<String>(
                      segments: [
                        ButtonSegment(
                          value: 'pequeño',
                          label: Text(i18n.huertosSmall),
                          icon: const Icon(Icons.crop_square),
                        ),
                        ButtonSegment(
                          value: 'mediano',
                          label: Text(i18n.huertosMedium),
                          icon: const Icon(Icons.crop_landscape),
                        ),
                        ButtonSegment(
                          value: 'grande',
                          label: Text(i18n.huertosLarge),
                          icon: const Icon(Icons.crop_free),
                        ),
                      ],
                      selected: {tamanioPreferido},
                      onSelectionChanged: (Set<String> newSelection) {
                        setDialogState(() {
                          tamanioPreferido = newSelection.first;
                        });
                      },
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: observacionesController,
                      decoration: InputDecoration(
                        labelText: i18n.huertosComments,
                        border: const OutlineInputBorder(),
                        helperText: i18n.huertosCommentsOptional,
                      ),
                      maxLines: 3,
                    ),
                  ],
                ),
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
      },
    );

    if (result == true && context.mounted) {
      final response = await api.solicitarParcelaHuerto(
        tamanio: tamanioPreferido,
        observaciones: observacionesController.text.trim(),
      );

      if (context.mounted) {
        final msg = response.success
            ? i18n.huertosRequestSuccess
            : (response.error ?? i18n.huertosRequestError);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
        if (response.success) {
          _refresh();
        }
      }
    }

    observacionesController.dispose();
  }

  Future<void> _completarTarea(BuildContext context, int tareaId) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final response = await api.completarTareaHuerto(tareaId);
    if (context.mounted) {
      final msg = response.success
          ? i18n.huertosTaskCompleted
          : (response.error ?? i18n.huertosTaskError);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg)),
      );
      if (response.success) {
        _refresh();
      }
    }
  }

  Future<void> _contactarIntercambio(BuildContext context, int intercambioId) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    final mensajeController = TextEditingController();

    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(i18n.huertosContactExchange),
          content: TextField(
            controller: mensajeController,
            decoration: InputDecoration(
              labelText: i18n.commonMessage,
              border: const OutlineInputBorder(),
            ),
            maxLines: 3,
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
      final response = await api.contactarIntercambioHuerto(
        intercambioId: intercambioId,
        mensaje: mensajeController.text.trim(),
      );

      if (context.mounted) {
        final msg = response.success
            ? i18n.huertosMessageSent
            : (response.error ?? i18n.huertosMessageError);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg)),
        );
      }
    }

    mensajeController.dispose();
  }

  void _showInfo(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.eco, color: Colors.green),
            const SizedBox(width: 8),
            Text(i18n.huertosInfo),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                i18n.huertosInfoText,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 16),
              _buildInfoItem(
                context,
                Icons.grid_on,
                i18n.huertosBenefits1,
              ),
              _buildInfoItem(
                context,
                Icons.people,
                i18n.huertosBenefits2,
              ),
              _buildInfoItem(
                context,
                Icons.favorite,
                i18n.huertosBenefits3,
              ),
            ],
          ),
        ),
        actions: [
          FilledButton(
            onPressed: () => Navigator.pop(context),
            child: Text(i18n.commonClose),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoItem(BuildContext context, IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: Colors.green),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          ),
        ],
      ),
    );
  }
}
