import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/utils/map_launch_helper.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'reciclaje_screen_parts.dart';

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
    final i18n = AppLocalizations.of(context);

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
            return const FlavorLoadingState();
          }

          final response = snapshot.data!;
          if (!response.success || response.data == null) {
            return FlavorErrorState(
              message: i18n.reciclajeError,
              onRetry: _refresh,
              icon: Icons.recycling_outlined,
            );
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

          final hasContent = estadisticas.isNotEmpty ||
              puntosReciclaje.isNotEmpty ||
              calendario.isNotEmpty ||
              miEstadistica.isNotEmpty;

          if (!hasContent) {
            return FlavorEmptyState(
              icon: Icons.recycling_outlined,
              title: i18n.reciclajeError,
            );
          }

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
}
