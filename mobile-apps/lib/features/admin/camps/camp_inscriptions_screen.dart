import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/providers/providers.dart';
import '../../../core/models/models.dart';
import '../../../core/widgets/flavor_snackbar.dart';

/// Pantalla para ver inscripciones de un campamento específico
class CampInscriptionsScreen extends ConsumerStatefulWidget {
  final int campId;
  final String campTitle;

  const CampInscriptionsScreen({
    super.key,
    required this.campId,
    required this.campTitle,
  });

  @override
  ConsumerState<CampInscriptionsScreen> createState() =>
      _CampInscriptionsScreenState();
}

class _CampInscriptionsScreenState
    extends ConsumerState<CampInscriptionsScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context);
  List<CampInscription> _inscriptions = [];
  Map<String, int>? _stats;
  bool _isLoading = false;
  String? _selectedPaymentStatus;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _loadInscriptions();
  }

  Future<void> _loadInscriptions() async {
    setState(() => _isLoading = true);

    final api = ref.read(apiClientProvider);
    final response = await api.getCampInscriptions(
      campId: widget.campId,
      search: _searchQuery.isNotEmpty ? _searchQuery : null,
      paymentStatus: _selectedPaymentStatus,
    );

    if (response.success && response.data != null) {
      final inscriptionsList = response.data!['inscriptions'] as List? ?? [];
      final statsData = response.data!['stats'] as Map? ?? {};

      setState(() {
        _inscriptions = inscriptionsList
            .map((i) => CampInscription.fromJson(i))
            .toList();
        _stats = {
          'total': statsData['total'] ?? 0,
          'paid': statsData['paid'] ?? 0,
          'pending': statsData['pending'] ?? 0,
        };
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          response.error ?? i18n.campInscriptionsLoadError,
        );
      }
    }
  }

  Future<void> _launchUrl(String urlString) async {
    final uri = Uri.parse(urlString);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          i18n.commonCannotOpenUrl(urlString),
        );
      }
    }
  }

  Future<void> _exportToExcel() async {
    final api = ref.read(apiClientProvider);
    final response = await api.exportCampInscriptionsExcel(widget.campId);

    if (response.success && response.data != null) {
      final downloadUrl = response.data!['download_url'];
      _launchUrl(downloadUrl);
      if (mounted) {
        FlavorSnackbar.showSuccess(
          context,
          i18n.exportandoAExcel583e30,
        );
      }
    } else {
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          response.error ?? i18n.campInscriptionsExportError,
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(i18n.inscripciones7f2754),
            Text(
              widget.campTitle,
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.download),
            onPressed: _exportToExcel,
            tooltip: i18n.exportarAExcel3ac529,
          ),
        ],
      ),
      body: Column(
        children: [
          // Estadísticas
          if (_stats != null)
            Container(
              padding: const EdgeInsets.all(16),
              color: Theme.of(context).colorScheme.surfaceContainerHighest,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  _StatCard(
                    label: i18n.campInscriptionsTotalLabel,
                    value: _stats!['total'].toString(),
                    icon: Icons.people,
                    color: Colors.blue,
                  ),
                  _StatCard(
                    label: i18n.campInscriptionsPaidLabel,
                    value: _stats!['paid'].toString(),
                    icon: Icons.check_circle,
                    color: Colors.green,
                  ),
                  _StatCard(
                    label: i18n.campInscriptionsPendingLabel,
                    value: _stats!['pending'].toString(),
                    icon: Icons.pending,
                    color: Colors.orange,
                  ),
                ],
              ),
            ),

          // Filtros
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    decoration: InputDecoration(
                      labelText: i18n.buscar113f74,
                      prefixIcon: const Icon(Icons.search),
                      border: const OutlineInputBorder(),
                    ),
                    onChanged: (value) {
                      setState(() => _searchQuery = value);
                      _loadInscriptions();
                    },
                  ),
                ),
                const SizedBox(width: 8),
                DropdownButton<String?>(
                  value: _selectedPaymentStatus,
                  hint: Text(i18n.estado3397e6),
                  items: [
                    DropdownMenuItem(value: null, child: Text(i18n.todos32630c)),
                    DropdownMenuItem(value: 'paid', child: Text(i18n.pagadas6be9ac)),
                    DropdownMenuItem(
                        value: 'pending', child: Text(i18n.pendientesB4188c)),
                  ],
                  onChanged: (value) {
                    setState(() => _selectedPaymentStatus = value);
                    _loadInscriptions();
                  },
                ),
              ],
            ),
          ),

          // Lista de inscripciones
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _inscriptions.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.person_off,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(i18n.noHayInscripciones,
                              style: TextStyle(
                                fontSize: 18,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadInscriptions,
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _inscriptions.length,
                          itemBuilder: (context, index) {
                            final inscription = _inscriptions[index];
                            return _InscriptionCard(
                              inscription: inscription,
                              onEmail: () => _launchUrl(
                                  'mailto:${inscription.guardianEmail}'),
                              onPhone: () => _launchUrl(
                                  'tel:${inscription.guardianPhone}'),
                              onWhatsApp: () {
                                final phone = inscription.guardianPhone
                                    .replaceAll(RegExp(r'[^\d+]'), '');
                                _launchUrl('https://wa.me/$phone');
                              },
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color color;

  const _StatCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, color: color, size: 32),
        const SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey[600],
          ),
        ),
      ],
    );
  }
}

class _InscriptionCard extends StatelessWidget {
  final CampInscription inscription;
  final VoidCallback onEmail;
  final VoidCallback onPhone;
  final VoidCallback onWhatsApp;

  const _InscriptionCard({
    required this.inscription,
    required this.onEmail,
    required this.onPhone,
    required this.onWhatsApp,
  });

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ExpansionTile(
        leading: CircleAvatar(
          backgroundColor: inscription.isPaid ? Colors.green : Colors.orange,
          child: Text(
            inscription.participantName.isNotEmpty
                ? inscription.participantName[0].toUpperCase()
                : '?',
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        title: Text(
          inscription.participantName,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(i18n.commonYearsOld(inscription.participantAge)),
            const SizedBox(height: 4),
            Chip(
              label: Text(
                inscription.isPaid ? i18n.campInscriptionsPaidLabel : i18n.campInscriptionsPendingLabel,
                style: const TextStyle(fontSize: 11),
              ),
              backgroundColor:
                  inscription.isPaid ? Colors.green[100] : Colors.orange[100],
              padding: EdgeInsets.zero,
              materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
          ],
        ),
        children: [
          const Divider(height: 1),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Información del responsable
                Text(i18n.responsable,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 8),
                ListTile(
                  leading: const Icon(Icons.person),
                  title: Text(inscription.guardianName),
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                ),
                ListTile(
                  leading: const Icon(Icons.email),
                  title: Text(inscription.guardianEmail),
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                ),
                ListTile(
                  leading: const Icon(Icons.phone),
                  title: Text(inscription.guardianPhone),
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                ),

                const SizedBox(height: 16),

                // Botones de contacto
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    // Email
                    Expanded(
                      child: FilledButton.tonalIcon(
                        onPressed: onEmail,
                        icon: const Icon(Icons.email, size: 20),
                        label: Text(i18n.emailCe8ae9),
                        style: FilledButton.styleFrom(
                          backgroundColor: Colors.blue[100],
                          foregroundColor: Colors.blue[900],
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    // Teléfono
                    Expanded(
                      child: FilledButton.tonalIcon(
                        onPressed: onPhone,
                        icon: const Icon(Icons.phone, size: 20),
                        label: Text(i18n.llamarC9c110),
                        style: FilledButton.styleFrom(
                          backgroundColor: Colors.green[100],
                          foregroundColor: Colors.green[900],
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    // WhatsApp
                    Expanded(
                      child: FilledButton.tonalIcon(
                        onPressed: onWhatsApp,
                        icon: const Icon(Icons.message, size: 20),
                        label: Text(i18n.whatsapp8b777e),
                        style: FilledButton.styleFrom(
                          backgroundColor: const Color(0xFF25D366),
                          foregroundColor: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 16),

                // Información adicional
                const Divider(),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(i18n.inscripcion,
                      style: TextStyle(color: Colors.grey[600]),
                    ),
                    Text(inscription.inscriptionDate),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(i18n.importe,
                      style: TextStyle(color: Colors.grey[600]),
                    ),
                    Text(
                      '${inscription.amount.toStringAsFixed(2)}€',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
