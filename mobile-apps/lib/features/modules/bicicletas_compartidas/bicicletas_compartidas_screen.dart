import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/utils/map_launch_helper.dart';
import '../../../core/widgets/flavor_confirm_dialog.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class BicicletasCompartidasScreen extends ConsumerStatefulWidget {
  const BicicletasCompartidasScreen({super.key});

  @override
  ConsumerState<BicicletasCompartidasScreen> createState() => _BicicletasCompartidasScreenState();
}

class _BicicletasCompartidasScreenState extends ConsumerState<BicicletasCompartidasScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getBicicletasCompartidas();
  }

  Future<void> _refresh() async {
    setState(() => _future = ref.read(apiClientProvider).getBicicletasCompartidas());
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);

    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Bicicletas Compartidas'),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.bicicletasTabStations, icon: const Icon(Icons.place)),
              Tab(text: i18n.bicicletasTabActive, icon: const Icon(Icons.pedal_bike)),
              Tab(text: i18n.bicicletasTabHistory, icon: const Icon(Icons.history)),
            ],
          ),
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
                message: i18n.bicicletasError,
                onRetry: _refresh,
                icon: Icons.pedal_bike_outlined,
              );
            }

            final data = response.data!;
            final estaciones = (data['estaciones'] as List?)?.cast<Map<String, dynamic>>() ?? [];
            final alquilerActivo = data['alquiler_activo'] as Map<String, dynamic>?;
            final historial = (data['historial'] as List?)?.cast<Map<String, dynamic>>() ?? [];

            return TabBarView(
              children: [
                _buildEstacionesTab(context, estaciones, i18n),
                _buildActivoTab(context, alquilerActivo, i18n),
                _buildHistorialTab(context, historial, i18n),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _buildEstacionesTab(BuildContext context, List<Map<String, dynamic>> estaciones, AppLocalizations i18n) {
    if (estaciones.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.place_outlined,
        title: i18n.bicicletasNoStations,
      );
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: estaciones.length,
        itemBuilder: (context, index) {
          final estacion = estaciones[index];
          final nombre = estacion['nombre']?.toString() ?? '';
          final ubicacion = estacion['ubicacion']?.toString() ?? '';
          final disponibles = (estacion['bicicletas_disponibles'] as num?)?.toInt() ?? 0;
          final total = (estacion['total_bicicletas'] as num?)?.toInt() ?? 0;
          final espacios = (estacion['espacios_libres'] as num?)?.toInt() ?? 0;

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: disponibles > 0 ? Colors.green : Colors.red,
                child: Text('$disponibles', style: const TextStyle(color: Colors.white)),
              ),
              title: Text(nombre),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.place, size: 14),
                      const SizedBox(width: 4),
                      Expanded(child: Text(ubicacion)),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Icon(Icons.pedal_bike, size: 16, color: Colors.blue[700]),
                      const SizedBox(width: 4),
                      Text('$disponibles/$total ${i18n.bicicletasAvailable}'),
                      const SizedBox(width: 16),
                      Icon(Icons.lock_open, size: 16, color: Colors.green[700]),
                      const SizedBox(width: 4),
                      Text('$espacios ${i18n.bicicletasSpaces}'),
                    ],
                  ),
                ],
              ),
              trailing: IconButton(
                icon: const Icon(Icons.directions),
                onPressed: () => _verMapa(context, estacion),
              ),
              onTap: disponibles > 0 ? () => _alquilarBicicleta(context, estacion) : null,
            ),
          );
        },
      ),
    );
  }

  Widget _buildActivoTab(BuildContext context, Map<String, dynamic>? alquiler, AppLocalizations i18n) {
    if (alquiler == null || alquiler.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.pedal_bike,
        title: i18n.bicicletasNoActiveRental,
        message: i18n.bicicletasRentFromStations,
      );
    }

    final bicicletaId = alquiler['bicicleta_id']?.toString() ?? '';
    final estacionOrigen = alquiler['estacion_origen']?.toString() ?? '';
    final horaInicio = alquiler['hora_inicio']?.toString() ?? '';
    final duracion = alquiler['duracion_minutos']?.toString() ?? '0';
    final coste = alquiler['coste_estimado']?.toString() ?? '0';

    return RefreshIndicator(
      onRefresh: _refresh,
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Card(
              elevation: 3,
              color: Colors.blue.shade50,
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  children: [
                    const Icon(Icons.pedal_bike, size: 80, color: Colors.blue),
                    const SizedBox(height: 16),
                    Text(
                      i18n.bicicletasActiveRental,
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 24),
                    _buildInfoRow(Icons.qr_code, i18n.bicicletasBikeID, bicicletaId, context),
                    _buildInfoRow(Icons.place, i18n.bicicletasOrigin, estacionOrigen, context),
                    _buildInfoRow(Icons.access_time, i18n.bicicletasStartTime, horaInicio, context),
                    _buildInfoRow(Icons.timer, i18n.bicicletasDuration, '$duracion min', context),
                    _buildInfoRow(Icons.euro, i18n.bicicletasEstimatedCost, '$coste €', context),
                    const SizedBox(height: 24),
                    FilledButton.icon(
                      onPressed: () => _finalizarAlquiler(context),
                      icon: const Icon(Icons.stop_circle),
                      label: Text(i18n.bicicletasEndRental),
                      style: FilledButton.styleFrom(
                        minimumSize: const Size.fromHeight(48),
                        backgroundColor: Colors.red,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHistorialTab(BuildContext context, List<Map<String, dynamic>> historial, AppLocalizations i18n) {
    if (historial.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.history,
        title: i18n.bicicletasNoHistory,
      );
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: historial.length,
        itemBuilder: (context, index) {
          final viaje = historial[index];
          final fecha = viaje['fecha']?.toString() ?? '';
          final origen = viaje['estacion_origen']?.toString() ?? '';
          final destino = viaje['estacion_destino']?.toString() ?? '';
          final duracion = viaje['duracion']?.toString() ?? '';
          final coste = viaje['coste']?.toString() ?? '';

          return Card(
            margin: const EdgeInsets.only(bottom: 8),
            child: ListTile(
              leading: const CircleAvatar(child: Icon(Icons.check_circle)),
              title: Text(fecha),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.arrow_upward, size: 14, color: Colors.green),
                      const SizedBox(width: 4),
                      Expanded(child: Text(origen)),
                    ],
                  ),
                  Row(
                    children: [
                      const Icon(Icons.arrow_downward, size: 14, color: Colors.red),
                      const SizedBox(width: 4),
                      Expanded(child: Text(destino)),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text('$duracion min - $coste €', style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value, BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(icon, size: 20),
          const SizedBox(width: 12),
          Text('$label: ', style: Theme.of(context).textTheme.labelMedium),
          Expanded(child: Text(value, style: Theme.of(context).textTheme.bodyMedium)),
        ],
      ),
    );
  }

  Future<void> _verMapa(BuildContext context, Map<String, dynamic> estacion) async {
    final latitud = estacion['latitud'] ?? estacion['lat'];
    final longitud = estacion['longitud'] ?? estacion['lng'];
    final nombre = estacion['nombre']?.toString() ?? 'Estación';

    if (latitud != null && longitud != null) {
      final lat = double.tryParse(latitud.toString()) ?? 0;
      final lng = double.tryParse(longitud.toString()) ?? 0;
      final url = MapLaunchHelper.buildConfiguredMapUri(
        lat,
        lng,
        query: nombre,
      );
      if (!context.mounted) return;
      await FlavorUrlLauncher.openExternalUri(
        context,
        url,
        errorMessage: 'No se puede abrir el mapa',
      );
    } else {
      if (context.mounted) {
        FlavorSnackbar.showInfo(
          context,
          'Ubicación no disponible para "$nombre"',
        );
      }
    }
  }

  Future<void> _alquilarBicicleta(BuildContext context, Map<String, dynamic> estacion) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);
    final estacionId = (estacion['id'] as num?)?.toInt() ?? 0;

    final confirm = await FlavorConfirmDialog.show(
      context,
      title: i18n.bicicletasRentBike,
      message: '${i18n.bicicletasRentConfirm} ${estacion['nombre']}?',
      cancelLabel: i18n.commonCancel,
      confirmLabel: i18n.commonConfirm,
    );

    if (confirm == true && context.mounted) {
      final response = await api.alquilarBicicleta(estacionId);
      if (context.mounted) {
        final msg = response.success ? i18n.bicicletasRentSuccess : (response.error ?? i18n.bicicletasRentError);
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) _refresh();
      }
    }
  }

  Future<void> _finalizarAlquiler(BuildContext context) async {
    final i18n = AppLocalizations.of(context);
    final api = ref.read(apiClientProvider);

    final confirm = await FlavorConfirmDialog.show(
      context,
      title: i18n.bicicletasEndRental,
      message: i18n.bicicletasEndConfirm,
      cancelLabel: i18n.commonCancel,
      confirmLabel: i18n.commonConfirm,
    );

    if (confirm == true && context.mounted) {
      final response = await api.finalizarAlquilerBicicleta();
      if (context.mounted) {
        final msg = response.success ? i18n.bicicletasEndSuccess : (response.error ?? i18n.bicicletasEndError);
        if (response.success) {
          FlavorSnackbar.showSuccess(context, msg);
        } else {
          FlavorSnackbar.showError(context, msg);
        }
        if (response.success) _refresh();
      }
    }
  }
}
