import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';
import '../../../core/providers/providers.dart';
import '../../../core/models/models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import 'camp_form_screen.dart';
import 'camp_inscriptions_screen.dart';

/// Pantalla de gestión de campamentos para administradores
class CampsManagementScreen extends ConsumerStatefulWidget {
  const CampsManagementScreen({super.key});

  @override
  ConsumerState<CampsManagementScreen> createState() =>
      _CampsManagementScreenState();
}

class _CampsManagementScreenState
    extends ConsumerState<CampsManagementScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context);
  List<Map<String, dynamic>> _camps = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadCamps();
  }

  Future<void> _loadCamps() async {
    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final response = await api.getAdminCamps();

    if (response.success && response.data != null) {
      setState(() {
        _camps = List<Map<String, dynamic>>.from(
          response.data!['camps'] ?? [],
        );
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          response.error ?? 'Error al cargar campamentos',
        );
      }
    }
  }

  Future<void> _toggleCampStatus(int campId, bool currentIsActive) async {
    final api = ref.read(apiClientProvider);
    final response = await api.toggleCampStatus(campId);

    if (response.success) {
      _loadCamps(); // Recargar lista
      if (mounted) {
        FlavorSnackbar.showSuccess(
          context,
          currentIsActive
              ? 'Campamento desactivado'
              : 'Campamento activado',
        );
      }
    } else {
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          response.error ?? 'Error al cambiar estado',
        );
      }
    }
  }

  Future<void> _deleteCamp(int campId, String title) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.eliminarCampamento01a566),
        content: Text(
          '¿Estás seguro de eliminar "$title"?\n\nEsta acción no se puede deshacer.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: Text(i18n.eliminar5b5c9f),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    final api = ref.read(apiClientProvider);
    final response = await api.deleteCamp(campId);

    if (response.success) {
      _loadCamps(); // Recargar lista
      if (mounted) {
        FlavorSnackbar.showSuccess(
          context,
          i18n.campamentoEliminadoCorrectamenteEe685d,
        );
      }
    } else {
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          response.error ?? 'Error al eliminar campamento',
        );
      }
    }
  }

  Future<void> _showShareableLink(int campId, String title) async {
    final api = ref.read(apiClientProvider);
    final response = await api.getCampShareableLink(campId);

    if (response.success && response.data != null) {
      final shareableUrl = response.data!['shareable_url'];
      final appDeeplink = response.data!['app_deeplink'];

      if (!mounted) return;

      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(i18n.campsShareTitle(title)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(i18n.enlaceWeb,
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              SelectableText(shareableUrl),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Clipboard.setData(ClipboardData(text: shareableUrl));
                        FlavorSnackbar.showInfo(
                          context,
                          i18n.enlaceCopiadoAlPortapapelesBdfefc,
                        );
                      },
                      icon: const Icon(Icons.copy),
                      label: Text(i18n.copiar0816bd),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: () {
                        Share.share(
                          'Mira este campamento: $title\n\n$shareableUrl',
                          subject: title,
                        );
                      },
                      icon: const Icon(Icons.share),
                      label: Text(i18n.compartirFba5ba),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              const Divider(),
              const SizedBox(height: 8),
              Text(i18n.enlaceParaAppMovil,
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              SelectableText(appDeeplink),
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: () {
                  Clipboard.setData(ClipboardData(text: appDeeplink));
                  FlavorSnackbar.showInfo(
                    context,
                    i18n.deeplinkCopiadoAlPortapapeles3bd450,
                  );
                },
                icon: const Icon(Icons.copy),
                label: Text(i18n.copiarDeeplinkE589f8),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(i18n.cerrar92eb39),
            ),
          ],
        ),
      );
    } else {
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          response.error ?? 'Error al obtener enlace compartible',
        );
      }
    }
  }

  Future<void> _createOrEditCamp([Map<String, dynamic>? camp]) async {
    // Por simplicidad, aquí no convertimos a Camp model
    // La pantalla de formulario aceptará null para crear nuevo
    final result = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (context) => CampFormScreen(
          camp: camp != null ? _convertToCampModel(camp) : null,
        ),
      ),
    );

    if (result == true) {
      _loadCamps(); // Recargar después de crear/editar
    }
  }

  // Conversión temporal de Map a Camp (necesario para el formulario)
  Camp _convertToCampModel(Map<String, dynamic> data) {
    // Usamos Camp.fromJson con datos parciales del endpoint admin.
    // Los campos faltantes se rellenan con defaults del modelo.
    final safe = Map<String, dynamic>.from(data);
    return Camp.fromJson({
      'id': safe['id'] ?? 0,
      'title': safe['title'] ?? '',
      'slug': safe['slug'] ?? '',
      'excerpt': safe['excerpt'] ?? '',
      'description': safe['description'],
      'featured_image': safe['featured_image'] ?? safe['featuredImage'] ?? '',
      'categories': safe['categories'] ?? [],
      'ages': safe['ages'] ?? [],
      'languages': safe['languages'] ?? [],
      'price': safe['price'] ?? 0,
      'price_total': safe['price_total'] ?? 0,
      'duration': safe['duration'] ?? '',
      'label': safe['label'],
      'inscription_open': safe['inscription_open'] ?? true,
      'inscription_count': safe['inscription_count'] ?? 0,
      'capacity': safe['capacity'],
      'dates': safe['dates'],
      'schedule': safe['schedule'],
      'location': safe['location'],
      'gallery': safe['gallery'],
      'category_color': safe['category_color'],
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.gestiNDeCampamentos247af6),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _createOrEditCamp(),
        icon: const Icon(Icons.add),
        label: Text(i18n.nuevo73fa04),
      ),
      body: _isLoading
          ? LoadingScreen(message: i18n.loadingCamps)
          : _camps.isEmpty
              ? EmptyScreen(
                  message: 'No hay campamentos',
                  icon: Icons.event_busy,
                  action: FilledButton.icon(
                    onPressed: () => _createOrEditCamp(),
                    icon: const Icon(Icons.add),
                    label: Text(i18n.crearPrimeroE98bb6),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadCamps,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _camps.length,
                    itemBuilder: (context, index) {
                      final camp = _camps[index];
                      return _CampManagementCard(
                        camp: camp,
                        onToggleStatus: () => _toggleCampStatus(
                          camp['id'],
                          camp['inscription_open'] == true,
                        ),
                        onDelete: () => _deleteCamp(
                          camp['id'],
                          camp['title'],
                        ),
                        onEdit: () => _createOrEditCamp(camp),
                        onShare: () => _showShareableLink(
                          camp['id'],
                          camp['title'],
                        ),
                        onViewInscriptions: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => CampInscriptionsScreen(
                                campId: camp['id'],
                                campTitle: camp['title'],
                              ),
                            ),
                          );
                        },
                      );
                    },
                  ),
                ),
    );
  }
}

class _CampManagementCard extends StatelessWidget {
  final Map<String, dynamic> camp;
  final VoidCallback onToggleStatus;
  final VoidCallback onDelete;
  final VoidCallback onEdit;
  final VoidCallback onShare;
  final VoidCallback onViewInscriptions;

  const _CampManagementCard({
    required this.camp,
    required this.onToggleStatus,
    required this.onDelete,
    required this.onEdit,
    required this.onShare,
    required this.onViewInscriptions,
  });

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    final inscriptionOpen = camp['inscription_open'] == true;
    final inscriptionCount = camp['inscription_count'] ?? 0;
    final priceTotal = camp['price_total'] ?? 0.0;
    final categories = camp['categories'] as List? ?? [];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Column(
        children: [
          ListTile(
            leading: CircleAvatar(
              backgroundColor: inscriptionOpen ? Colors.green : Colors.grey,
              child: Icon(
                inscriptionOpen ? Icons.check_circle : Icons.block,
                color: Colors.white,
              ),
            ),
            title: Text(
              camp['title'] ?? 'Sin título',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (categories.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Wrap(
                    spacing: 4,
                    runSpacing: 4,
                    children: categories.map((cat) {
                      return Chip(
                        label: Text(
                          cat['name'] ?? '',
                          style: const TextStyle(fontSize: 11),
                        ),
                        padding: EdgeInsets.zero,
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      );
                    }).toList(),
                  ),
                ],
                const SizedBox(height: 4),
                Text(i18n.commonPriceEur(priceTotal.toStringAsFixed(2))),
              ],
            ),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  '$inscriptionCount',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                Text(i18n.campsInscriptionsLabel, style: const TextStyle(fontSize: 11)),
              ],
            ),
          ),
          const Divider(height: 1),
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: Wrap(
              spacing: 8,
              runSpacing: 4,
              alignment: WrapAlignment.spaceEvenly,
              children: [
                // Ver inscripciones
                IconButton.outlined(
                  onPressed: onViewInscriptions,
                  icon: const Icon(Icons.people),
                  tooltip: i18n.verInscripcionesB46dd7,
                ),
                // Editar
                IconButton.outlined(
                  onPressed: onEdit,
                  icon: const Icon(Icons.edit),
                  tooltip: i18n.editarEf485e,
                ),
                // Compartir
                IconButton.outlined(
                  onPressed: onShare,
                  icon: const Icon(Icons.share),
                  tooltip: i18n.compartirFba5ba,
                ),
                // Toggle estado
                IconButton.outlined(
                  onPressed: onToggleStatus,
                  icon: Icon(
                    inscriptionOpen ? Icons.lock : Icons.lock_open,
                  ),
                  tooltip: inscriptionOpen
                      ? 'Cerrar inscripciones'
                      : 'Abrir inscripciones',
                  style: IconButton.styleFrom(
                    foregroundColor:
                        inscriptionOpen ? Colors.orange : Colors.green,
                  ),
                ),
                // Eliminar
                IconButton.outlined(
                  onPressed: onDelete,
                  icon: const Icon(Icons.delete),
                  tooltip: i18n.eliminar5b5c9f,
                  style: IconButton.styleFrom(
                    foregroundColor: Colors.red,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
