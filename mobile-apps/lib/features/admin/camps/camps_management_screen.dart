import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al cargar campamentos'),
            backgroundColor: Colors.red,
          ),
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(currentIsActive
                ? 'Campamento desactivado'
                : 'Campamento activado'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al cambiar estado'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _deleteCamp(int campId, String title) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar campamento'),
        content: Text(
          '¿Estás seguro de eliminar "$title"?\n\nEsta acción no se puede deshacer.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('Eliminar'),
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
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Campamento eliminado correctamente'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.error ?? 'Error al eliminar campamento'),
            backgroundColor: Colors.red,
          ),
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
          title: Text('Compartir: $title'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Enlace web:',
                style: TextStyle(fontWeight: FontWeight.bold),
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
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Enlace copiado al portapapeles'),
                          ),
                        );
                      },
                      icon: const Icon(Icons.copy),
                      label: const Text('Copiar'),
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
                      label: const Text('Compartir'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              const Divider(),
              const SizedBox(height: 8),
              const Text(
                'Enlace para app móvil:',
                style: TextStyle(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              SelectableText(appDeeplink),
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: () {
                  Clipboard.setData(ClipboardData(text: appDeeplink));
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Deeplink copiado al portapapeles'),
                    ),
                  );
                },
                icon: const Icon(Icons.copy),
                label: const Text('Copiar deeplink'),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cerrar'),
            ),
          ],
        ),
      );
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content:
                Text(response.error ?? 'Error al obtener enlace compartible'),
            backgroundColor: Colors.red,
          ),
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
  _convertToCampModel(Map<String, dynamic> data) {
    // Aquí deberíamos usar Camp.fromJson pero como solo tenemos
    // datos parciales del admin endpoint, creamos un objeto mínimo
    // En producción, sería mejor obtener el campamento completo
    return null; // TODO: implementar conversión completa
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Gestión de Campamentos'),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _createOrEditCamp(),
        icon: const Icon(Icons.add),
        label: const Text('Nuevo'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _camps.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.event_busy,
                        size: 64,
                        color: Colors.grey[400],
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'No hay campamentos',
                        style: TextStyle(
                          fontSize: 18,
                          color: Colors.grey[600],
                        ),
                      ),
                      const SizedBox(height: 8),
                      FilledButton.icon(
                        onPressed: () => _createOrEditCamp(),
                        icon: const Icon(Icons.add),
                        label: const Text('Crear primero'),
                      ),
                    ],
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
                Text('${priceTotal.toStringAsFixed(2)}€'),
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
                const Text('inscripciones', style: TextStyle(fontSize: 11)),
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
                  tooltip: 'Ver inscripciones',
                ),
                // Editar
                IconButton.outlined(
                  onPressed: onEdit,
                  icon: const Icon(Icons.edit),
                  tooltip: 'Editar',
                ),
                // Compartir
                IconButton.outlined(
                  onPressed: onShare,
                  icon: const Icon(Icons.share),
                  tooltip: 'Compartir',
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
                  tooltip: 'Eliminar',
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
