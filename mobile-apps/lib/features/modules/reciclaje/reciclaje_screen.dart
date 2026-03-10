import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

class ReciclajeScreen extends ConsumerStatefulWidget {
  const ReciclajeScreen({super.key});

  @override
  ConsumerState<ReciclajeScreen> createState() => _ReciclajeScreenState();
}

class _ReciclajeScreenState extends ConsumerState<ReciclajeScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _futureDashboard;

  @override
  void initState() {
    super.initState();
    final api = ref.read(apiClientProvider);
    _futureDashboard = api.getReciclajeDashboard();
  }

  Future<void> _refresh() async {
    setState(() {
      final api = ref.read(apiClientProvider);
      _futureDashboard = api.getReciclajeDashboard();
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Reciclaje'),
        actions: [
          IconButton(
            icon: const Icon(Icons.help_outline),
            onPressed: () => _showGuiaReciclaje(context),
            tooltip: i18n.reciclajeGuide,
          ),
        ],
      ),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _futureDashboard,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return Center(child: Text(i18n.reciclajeError));
          }

          final dashboard = response.data!;
          final estadisticas = dashboard['estadisticas'] as Map<String, dynamic>? ?? {};
          final puntosReciclaje = (dashboard['puntos_reciclaje'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          final calendario = (dashboard['calendario'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          final miEstadistica = dashboard['mi_estadistica'] as Map<String, dynamic>? ?? {};

          return RefreshIndicator(
            onRefresh: _refresh,
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Mis estadísticas personales
                  if (miEstadistica.isNotEmpty) ...[
                    _buildMiEstadisticaCard(context, miEstadistica, i18n),
                    const SizedBox(height: 16),
                  ],

                  // Estadísticas globales
                  _buildEstadisticasGlobales(context, estadisticas, i18n),
                  const SizedBox(height: 16),

                  // Calendario de recogidas
                  if (calendario.isNotEmpty) ...[
                    _buildSectionHeader(
                      context,
                      i18n.reciclajeCalendar,
                      Icons.calendar_month,
                    ),
                    const SizedBox(height: 8),
                    _buildCalendarioCard(context, calendario, i18n),
                    const SizedBox(height: 16),
                  ],

                  // Puntos de reciclaje
                  if (puntosReciclaje.isNotEmpty) ...[
                    _buildSectionHeader(
                      context,
                      i18n.reciclajePoints,
                      Icons.place,
                    ),
                    const SizedBox(height: 8),
                    ...puntosReciclaje.map((punto) => _buildPuntoReciclajeCard(context, punto, i18n)),
                    const SizedBox(height: 16),
                  ],

                  // Tipos de residuos
                  _buildSectionHeader(
                    context,
                    i18n.reciclajeWasteTypes,
                    Icons.delete_outline,
                  ),
                  const SizedBox(height: 8),
                  _buildTiposResiduos(context, i18n),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildMiEstadisticaCard(BuildContext context, Map<String, dynamic> stats, AppLocalizations i18n) {
    final kgReciclados = (stats['kg_reciclados'] as num?)?.toDouble() ?? 0.0;
    final co2Ahorrado = (stats['co2_ahorrado'] as num?)?.toDouble() ?? 0.0;
    final posicionRanking = (stats['posicion_ranking'] as num?)?.toInt() ?? 0;
    final puntos = (stats['puntos'] as num?)?.toInt() ?? 0;

    return Card(
      elevation: 3,
      color: Colors.green.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.eco, color: Colors.green, size: 32),
                const SizedBox(width: 12),
                Text(
                  i18n.reciclajeMyStats,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: Colors.green[900],
                      ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _buildStatChip(
                    context,
                    Icons.scale,
                    '${kgReciclados.toStringAsFixed(1)} kg',
                    i18n.reciclajeRecycled,
                    Colors.green,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildStatChip(
                    context,
                    Icons.cloud_outlined,
                    '${co2Ahorrado.toStringAsFixed(1)} kg',
                    i18n.reciclajeCO2Saved,
                    Colors.blue,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: _buildStatChip(
                    context,
                    Icons.emoji_events,
                    '#$posicionRanking',
                    i18n.reciclajeRanking,
                    Colors.orange,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildStatChip(
                    context,
                    Icons.star,
                    '$puntos',
                    i18n.reciclajePoints,
                    Colors.amber,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatChip(BuildContext context, IconData icon, String value, String label, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 4),
          Text(
            value,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: color,
                ),
          ),
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall,
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildEstadisticasGlobales(BuildContext context, Map<String, dynamic> stats, AppLocalizations i18n) {
    final totalKg = (stats['total_kg'] as num?)?.toDouble() ?? 0.0;
    final participantes = (stats['participantes'] as num?)?.toInt() ?? 0;
    final objetivo = (stats['objetivo_mensual'] as num?)?.toDouble() ?? 100.0;
    final progreso = objetivo > 0 ? (totalKg / objetivo).clamp(0.0, 1.0) : 0.0;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              i18n.reciclajeGlobalStats,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildGlobalStat(
                  context,
                  Icons.recycling,
                  '${totalKg.toStringAsFixed(0)} kg',
                  i18n.reciclajeThisMonth,
                  Colors.green,
                ),
                _buildGlobalStat(
                  context,
                  Icons.people,
                  '$participantes',
                  i18n.reciclajeParticipants,
                  Colors.blue,
                ),
              ],
            ),
            const SizedBox(height: 16),
            Text(
              i18n.reciclajeMonthlyGoal,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 8),
            LinearProgressIndicator(
              value: progreso,
              backgroundColor: Colors.grey[300],
              color: Colors.green,
              minHeight: 8,
            ),
            const SizedBox(height: 4),
            Text(
              '${totalKg.toStringAsFixed(0)} / ${objetivo.toStringAsFixed(0)} kg (${(progreso * 100).toStringAsFixed(0)}%)',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGlobalStat(BuildContext context, IconData icon, String value, String label, Color color) {
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
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  Widget _buildCalendarioCard(BuildContext context, List<Map<String, dynamic>> calendario, AppLocalizations i18n) {
    return Card(
      child: Column(
        children: calendario.map((recogida) {
          final tipo = recogida['tipo']?.toString() ?? '';
          final fecha = recogida['fecha']?.toString() ?? '';
          final hora = recogida['hora']?.toString() ?? '';
          final zona = recogida['zona']?.toString() ?? '';

          return ListTile(
            leading: CircleAvatar(
              backgroundColor: _getTipoColor(tipo),
              child: Icon(_getTipoIcon(tipo), color: Colors.white, size: 20),
            ),
            title: Text(tipo),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.calendar_today, size: 14),
                    const SizedBox(width: 4),
                    Text('$fecha - $hora'),
                  ],
                ),
                if (zona.isNotEmpty)
                  Row(
                    children: [
                      const Icon(Icons.place, size: 14),
                      const SizedBox(width: 4),
                      Text(zona),
                    ],
                  ),
              ],
            ),
            trailing: IconButton(
              icon: const Icon(Icons.alarm_add),
              onPressed: () => _addRecordatorio(context, recogida),
              tooltip: i18n.reciclajeAddReminder,
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildPuntoReciclajeCard(BuildContext context, Map<String, dynamic> punto, AppLocalizations i18n) {
    final nombre = punto['nombre']?.toString() ?? '';
    final direccion = punto['direccion']?.toString() ?? '';
    final tiposAcepta = punto['tipos_acepta']?.toString() ?? '';
    final horario = punto['horario']?.toString() ?? '';
    final distancia = punto['distancia']?.toString() ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: const CircleAvatar(
          backgroundColor: Colors.green,
          child: Icon(Icons.recycling, color: Colors.white),
        ),
        title: Text(nombre),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (direccion.isNotEmpty)
              Row(
                children: [
                  const Icon(Icons.place, size: 14),
                  const SizedBox(width: 4),
                  Expanded(child: Text(direccion)),
                ],
              ),
            if (distancia.isNotEmpty)
              Text(
                '$distancia km',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Colors.blue,
                    ),
              ),
            if (horario.isNotEmpty)
              Text(
                horario,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            if (tiposAcepta.isNotEmpty) ...[
              const SizedBox(height: 4),
              Wrap(
                spacing: 4,
                runSpacing: 4,
                children: tiposAcepta.split(',').take(3).map((tipo) {
                  return Chip(
                    label: Text(tipo.trim()),
                    visualDensity: VisualDensity.compact,
                    backgroundColor: _getTipoColor(tipo.trim()).withOpacity(0.2),
                  );
                }).toList(),
              ),
            ],
          ],
        ),
        trailing: IconButton(
          icon: const Icon(Icons.directions),
          onPressed: () => _abrirMapa(context, punto),
          tooltip: i18n.reciclajeDirections,
        ),
      ),
    );
  }

  Widget _buildTiposResiduos(BuildContext context, AppLocalizations i18n) {
    final tipos = [
      {'tipo': i18n.reciclajeOrganic, 'color': Colors.brown, 'icon': Icons.compost},
      {'tipo': i18n.reciclajePaper, 'color': Colors.blue, 'icon': Icons.description},
      {'tipo': i18n.reciclajePlastic, 'color': Colors.yellow, 'icon': Icons.local_drink},
      {'tipo': i18n.reciclajeGlass, 'color': Colors.green, 'icon': Icons.wine_bar},
      {'tipo': i18n.reciclajeMetal, 'color': Colors.grey, 'icon': Icons.build},
      {'tipo': i18n.reciclajeElectronic, 'color': Colors.red, 'icon': Icons.devices},
    ];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(8),
        child: GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 3,
            childAspectRatio: 1,
            crossAxisSpacing: 8,
            mainAxisSpacing: 8,
          ),
          itemCount: tipos.length,
          itemBuilder: (context, index) {
            final tipo = tipos[index];
            return InkWell(
              onTap: () => _verDetallesTipo(context, tipo['tipo'] as String),
              child: Container(
                decoration: BoxDecoration(
                  color: (tipo['color'] as Color).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(
                    color: tipo['color'] as Color,
                    width: 2,
                  ),
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      tipo['icon'] as IconData,
                      size: 32,
                      color: tipo['color'] as Color,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      tipo['tipo'] as String,
                      style: Theme.of(context).textTheme.bodySmall,
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
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

  Color _getTipoColor(String tipo) {
    switch (tipo.toLowerCase()) {
      case 'orgánico':
      case 'organico':
        return Colors.brown;
      case 'papel':
      case 'cartón':
        return Colors.blue;
      case 'plástico':
      case 'plastico':
      case 'envases':
        return Colors.yellow;
      case 'vidrio':
        return Colors.green;
      case 'metal':
        return Colors.grey;
      case 'electrónico':
      case 'electronico':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  IconData _getTipoIcon(String tipo) {
    switch (tipo.toLowerCase()) {
      case 'orgánico':
      case 'organico':
        return Icons.compost;
      case 'papel':
      case 'cartón':
        return Icons.description;
      case 'plástico':
      case 'plastico':
      case 'envases':
        return Icons.local_drink;
      case 'vidrio':
        return Icons.wine_bar;
      case 'metal':
        return Icons.build;
      case 'electrónico':
      case 'electronico':
        return Icons.devices;
      default:
        return Icons.delete;
    }
  }

  void _showGuiaReciclaje(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return DraggableScrollableSheet(
          initialChildSize: 0.7,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          expand: false,
          builder: (context, scrollController) {
            return Container(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.recycling, color: Colors.green, size: 32),
                      const SizedBox(width: 12),
                      Text(
                        i18n.reciclajeGuide,
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Expanded(
                    child: ListView(
                      controller: scrollController,
                      children: [
                        _buildGuiaItem(
                          context,
                          Colors.brown,
                          Icons.compost,
                          i18n.reciclajeOrganic,
                          i18n.reciclajeOrganicDesc,
                        ),
                        _buildGuiaItem(
                          context,
                          Colors.blue,
                          Icons.description,
                          i18n.reciclajePaper,
                          i18n.reciclajePaperDesc,
                        ),
                        _buildGuiaItem(
                          context,
                          Colors.yellow,
                          Icons.local_drink,
                          i18n.reciclajePlastic,
                          i18n.reciclajePlasticDesc,
                        ),
                        _buildGuiaItem(
                          context,
                          Colors.green,
                          Icons.wine_bar,
                          i18n.reciclajeGlass,
                          i18n.reciclajeGlassDesc,
                        ),
                        _buildGuiaItem(
                          context,
                          Colors.red,
                          Icons.devices,
                          i18n.reciclajeElectronic,
                          i18n.reciclajeElectronicDesc,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildGuiaItem(BuildContext context, Color color, IconData icon, String title, String description) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.2),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(icon, color: color, size: 32),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    description,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _addRecordatorio(BuildContext context, Map<String, dynamic> recogida) {
    final i18n = AppLocalizations.of(context)!;
    final tipo = recogida['tipo']?.toString() ?? 'Recogida';
    final fecha = recogida['fecha']?.toString() ?? '';
    final hora = recogida['hora']?.toString() ?? '';
    final zona = recogida['zona']?.toString() ?? '';

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.alarm_add, color: Colors.green),
            const SizedBox(width: 8),
            Text(i18n.reciclajeAddReminder),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Se configurará un recordatorio para:'),
            const SizedBox(height: 12),
            Card(
              color: _getTipoColor(tipo).withOpacity(0.1),
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(_getTipoIcon(tipo), color: _getTipoColor(tipo)),
                        const SizedBox(width: 8),
                        Text(
                          tipo,
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                    if (fecha.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today, size: 14),
                          const SizedBox(width: 4),
                          Text('$fecha${hora.isNotEmpty ? " - $hora" : ""}'),
                        ],
                      ),
                    ],
                    if (zona.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(Icons.place, size: 14),
                          const SizedBox(width: 4),
                          Text(zona),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            Text(
              'Recibirás una notificación el día anterior.',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () async {
              Navigator.pop(context);
              await _guardarRecordatorio(recogida);
            },
            child: const Text('Activar recordatorio'),
          ),
        ],
      ),
    );
  }

  Future<void> _guardarRecordatorio(Map<String, dynamic> recogida) async {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/reciclaje/recordatorios', data: {
        'tipo': recogida['tipo'],
        'fecha': recogida['fecha'],
        'hora': recogida['hora'],
        'zona': recogida['zona'],
      });

      if (mounted) {
        if (response.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(i18n.reciclajeReminderAdded),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response.error ?? 'Error al guardar recordatorio'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _abrirMapa(BuildContext context, Map<String, dynamic> punto) {
    final i18n = AppLocalizations.of(context)!;
    final nombre = punto['nombre']?.toString() ?? 'Punto de reciclaje';
    final direccion = punto['direccion']?.toString() ?? '';
    final latitud = punto['latitud'] ?? punto['lat'];
    final longitud = punto['longitud'] ?? punto['lng'] ?? punto['lon'];
    final tiposAcepta = punto['tipos_acepta']?.toString() ?? '';
    final horario = punto['horario']?.toString() ?? '';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.5,
        minChildSize: 0.3,
        maxChildSize: 0.7,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Handle
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              // Cabecera
              Row(
                children: [
                  const CircleAvatar(
                    backgroundColor: Colors.green,
                    child: Icon(Icons.recycling, color: Colors.white),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          nombre,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        if (direccion.isNotEmpty)
                          Text(
                            direccion,
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
              const Divider(height: 24),
              // Información
              if (horario.isNotEmpty)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.schedule, color: Colors.green),
                  title: const Text('Horario'),
                  subtitle: Text(horario),
                ),
              if (tiposAcepta.isNotEmpty) ...[
                const Text(
                  'Tipos de residuos aceptados:',
                  style: TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 6,
                  runSpacing: 6,
                  children: tiposAcepta.split(',').map((tipo) {
                    final tipoTrimmed = tipo.trim();
                    return Chip(
                      avatar: Icon(
                        _getTipoIcon(tipoTrimmed),
                        size: 16,
                        color: _getTipoColor(tipoTrimmed),
                      ),
                      label: Text(tipoTrimmed),
                      backgroundColor: _getTipoColor(tipoTrimmed).withOpacity(0.1),
                      visualDensity: VisualDensity.compact,
                    );
                  }).toList(),
                ),
              ],
              const SizedBox(height: 24),
              // Botones de acción
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _copiarDireccion(direccion.isNotEmpty ? direccion : nombre);
                      },
                      icon: const Icon(Icons.copy),
                      label: const Text('Copiar dirección'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _abrirEnMapasExternos(nombre, direccion, latitud, longitud);
                      },
                      icon: const Icon(Icons.directions),
                      label: Text(i18n.reciclajeDirections),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _copiarDireccion(String direccion) async {
    if (direccion.isEmpty) return;

    await Clipboard.setData(ClipboardData(text: direccion));

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Dirección copiada al portapapeles'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  void _abrirEnMapasExternos(String nombre, String direccion, dynamic latitud, dynamic longitud) {
    final i18n = AppLocalizations.of(context)!;

    // Mostrar opciones de mapas
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'Abrir en aplicación de mapas',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 16),
              ListTile(
                leading: const Icon(Icons.map, color: Colors.blue),
                title: const Text('Google Maps'),
                onTap: () {
                  Navigator.pop(context);
                  _lanzarMapa('google', nombre, direccion, latitud, longitud);
                },
              ),
              ListTile(
                leading: const Icon(Icons.map_outlined, color: Colors.grey),
                title: const Text('Apple Maps'),
                onTap: () {
                  Navigator.pop(context);
                  _lanzarMapa('apple', nombre, direccion, latitud, longitud);
                },
              ),
              ListTile(
                leading: const Icon(Icons.navigation, color: Colors.orange),
                title: const Text('Waze'),
                onTap: () {
                  Navigator.pop(context);
                  _lanzarMapa('waze', nombre, direccion, latitud, longitud);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _lanzarMapa(String app, String nombre, String direccion, dynamic lat, dynamic lng) async {
    Uri? uri;
    final latStr = lat?.toString();
    final lngStr = lng?.toString();
    final query = direccion.isNotEmpty ? direccion : nombre;

    switch (app) {
      case 'google':
        if (latStr != null && lngStr != null) {
          uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=$latStr,$lngStr');
        } else {
          uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(query)}');
        }
        break;
      case 'apple':
        if (latStr != null && lngStr != null) {
          uri = Uri.parse('https://maps.apple.com/?ll=$latStr,$lngStr&q=${Uri.encodeComponent(nombre)}');
        } else {
          uri = Uri.parse('https://maps.apple.com/?q=${Uri.encodeComponent(query)}');
        }
        break;
      case 'waze':
        if (latStr != null && lngStr != null) {
          uri = Uri.parse('https://waze.com/ul?ll=$latStr,$lngStr&navigate=yes');
        } else {
          uri = Uri.parse('https://waze.com/ul?q=${Uri.encodeComponent(query)}');
        }
        break;
    }

    if (uri != null) {
      try {
        if (await canLaunchUrl(uri)) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        } else {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('No se puede abrir ${app.toUpperCase()}')),
            );
          }
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error al abrir mapa: $e')),
          );
        }
      }
    }
  }

  void _verDetallesTipo(BuildContext context, String tipo) {
    final i18n = AppLocalizations.of(context)!;
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(tipo),
        content: Text('${i18n.reciclajeDetailsFor} $tipo'),
        actions: [
          FilledButton(
            onPressed: () => Navigator.pop(context),
            child: Text(i18n.commonClose),
          ),
        ],
      ),
    );
  }
}
