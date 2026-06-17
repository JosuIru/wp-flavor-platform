import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'circulos_cuidados_screen_parts.dart';

class CirculosCuidadosScreen extends ConsumerStatefulWidget {
  const CirculosCuidadosScreen({super.key});

  @override
  ConsumerState<CirculosCuidadosScreen> createState() => _CirculosCuidadosScreenState();
}

class _CirculosCuidadosScreenState extends ConsumerState<CirculosCuidadosScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _circulos = [];
  List<Map<String, dynamic>> _misCirculos = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/circulos-cuidados/lista');
      if (response.success && response.data != null) {
        setState(() {
          _circulos = (response.data!['circulos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _misCirculos = (response.data!['mis_circulos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: Text(i18n.circulosCuidadosTitle),
          bottom: TabBar(
            tabs: [
              Tab(icon: const Icon(Icons.people), text: i18n.circulosCuidadosTabMine),
              Tab(icon: const Icon(Icons.explore), text: i18n.circulosCuidadosTabExplore),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildMisCirculos(),
            _buildExplorarCirculos(),
          ],
        ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _crearCirculo,
          icon: const Icon(Icons.add),
          label: Text(i18n.circulosCuidadosCreate),
        ),
      ),
    );
  }

  Widget _buildMisCirculos() {
    final i18n = AppLocalizations.of(context);
    if (_isLoading) return const FlavorLoadingState();

    if (_misCirculos.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.favorite_border,
        title: i18n.circulosCuidadosNoneMine,
        action: TextButton(
          onPressed: () => DefaultTabController.of(context).animateTo(1),
          child: Text(i18n.circulosCuidadosExploreAction),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _misCirculos.length,
        itemBuilder: (context, index) => _buildCirculoCard(_misCirculos[index], esMiembro: true),
      ),
    );
  }

  Widget _buildExplorarCirculos() {
    final i18n = AppLocalizations.of(context);
    if (_isLoading) return const FlavorLoadingState();

    if (_circulos.isEmpty) {
      return FlavorEmptyState(
        icon: Icons.search_off,
        title: i18n.circulosCuidadosNoneAvailable,
      );
    }

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _circulos.length,
        itemBuilder: (context, index) => _buildCirculoCard(_circulos[index]),
      ),
    );
  }

  Widget _buildCirculoCard(Map<String, dynamic> circulo, {bool esMiembro = false}) {
    final i18n = AppLocalizations.of(context);
    final nombre = circulo['nombre'] ?? i18n.circulosCuidadosUnnamed;
    final descripcion = circulo['descripcion'] ?? '';
    final miembros = circulo['total_miembros'] ?? 0;
    final tipo = circulo['tipo'] ?? '';
    final proximaReunion = circulo['proxima_reunion'] ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.pink.shade100,
                  child: Icon(Icons.favorite, color: Colors.pink.shade600),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(nombre, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      if (tipo.isNotEmpty)
                        Text(tipo, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                    ],
                  ),
                ),
                if (esMiembro)
                  Chip(
                    label: Text(i18n.circulosCuidadosMemberChip),
                    backgroundColor: Colors.green.shade100,
                    labelStyle: TextStyle(color: Colors.green.shade700, fontSize: 12),
                  ),
              ],
            ),
            if (descripcion.isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(descripcion, maxLines: 2, overflow: TextOverflow.ellipsis),
            ],
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(Icons.group, size: 16, color: Colors.grey.shade500),
                const SizedBox(width: 4),
                Text(
                    i18n.circulosCuidadosMembersCount(
                        int.tryParse(miembros.toString()) ?? 0),
                    style: TextStyle(color: Colors.grey.shade600)),
                if (proximaReunion.isNotEmpty) ...[
                  const SizedBox(width: 16),
                  Icon(Icons.event, size: 16, color: Colors.grey.shade500),
                  const SizedBox(width: 4),
                  Text(proximaReunion, style: TextStyle(color: Colors.grey.shade600)),
                ],
                const Spacer(),
                if (!esMiembro)
                  TextButton(
                    onPressed: () => _unirseCirculo(circulo),
                    child: Text(i18n.circulosCuidadosJoin),
                  )
                else
                  TextButton(
                    onPressed: () => _verCirculo(circulo),
                    child: Text(i18n.circulosCuidadosView),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatColumn({
    required IconData icon,
    required String value,
    required String label,
  }) {
    return Column(
      children: [
        Icon(icon, color: Colors.pink.shade400),
        const SizedBox(height: 4),
        Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
      ],
    );
  }
}
