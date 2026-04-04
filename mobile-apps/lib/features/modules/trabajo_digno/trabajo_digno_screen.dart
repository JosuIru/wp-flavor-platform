import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'trabajo_digno_screen_parts.dart';

class TrabajoDignoScreen extends ConsumerStatefulWidget {
  const TrabajoDignoScreen({super.key});

  @override
  ConsumerState<TrabajoDignoScreen> createState() => _TrabajoDignoScreenState();
}

class _TrabajoDignoScreenState extends ConsumerState<TrabajoDignoScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _ofertas = [];
  List<Map<String, dynamic>> _empresas = [];
  Map<String, dynamic>? _miPerfil;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/trabajo-digno/ofertas');
      if (response.success && response.data != null) {
        setState(() {
          _ofertas = (response.data!['ofertas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _empresas = (response.data!['empresas_comprometidas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _miPerfil = response.data!['mi_perfil'];
        });
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Trabajo Digno'),
        actions: [
          IconButton(icon: const Icon(Icons.person), onPressed: _verMiPerfil),
        ],
      ),
      body: _isLoading
          ? const FlavorLoadingState()
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildBanner(),
                    const SizedBox(height: 24),
                    _buildOfertasSection(),
                    const SizedBox(height: 24),
                    _buildEmpresasSection(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildBanner() {
    return Card(
      color: Colors.blue.shade50,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(Icons.work, size: 48, color: Colors.blue.shade400),
            const SizedBox(width: 16),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Empleos con condiciones justas', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  Text('Ofertas de empresas comprometidas con el trabajo digno'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOfertasSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Ofertas de empleo', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            TextButton.icon(
              onPressed: _mostrarFiltros,
              icon: const Icon(Icons.filter_list, size: 18),
              label: const Text('Filtrar'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (_ofertas.isEmpty)
          const FlavorEmptyState(
            icon: Icons.work_off,
            title: 'No hay ofertas disponibles',
          )
        else
          ..._ofertas.map((o) => _buildOfertaCard(o)),
      ],
    );
  }

  Widget _buildOfertaCard(Map<String, dynamic> oferta) {
    final badges = <Widget>[];
    if (oferta['teletrabajo'] == true) {
      badges.add(_buildBadge('Remoto', Colors.purple));
    }
    if (oferta['conciliacion'] == true) {
      badges.add(_buildBadge('Conciliacion', Colors.teal));
    }
    if (oferta['salario_justo'] == true) {
      badges.add(_buildBadge('Salario justo', Colors.green));
    }

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
                  backgroundColor: Colors.blue.shade100,
                  child: Icon(Icons.business, color: Colors.blue.shade600),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(oferta['titulo'] ?? '', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(oferta['empresa'] ?? '', style: TextStyle(color: Colors.grey.shade600)),
                    ],
                  ),
                ),
              ],
            ),
            if (badges.isNotEmpty) ...[
              const SizedBox(height: 12),
              Wrap(spacing: 8, runSpacing: 4, children: badges),
            ],
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(Icons.location_on, size: 16, color: Colors.grey.shade500),
                const SizedBox(width: 4),
                Text(oferta['ubicacion'] ?? '', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                const SizedBox(width: 16),
                Icon(Icons.euro, size: 16, color: Colors.grey.shade500),
                const SizedBox(width: 4),
                Text(oferta['salario'] ?? '', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                OutlinedButton(onPressed: () => _verDetalle(oferta), child: const Text('Ver mas')),
                const SizedBox(width: 8),
                FilledButton(onPressed: () => _aplicar(oferta), child: const Text('Aplicar')),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBadge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.2),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(text, style: TextStyle(fontSize: 11, color: color, fontWeight: FontWeight.w500)),
    );
  }

  Widget _buildEmpresasSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Empresas comprometidas', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        if (_empresas.isEmpty)
          const FlavorEmptyState(
            icon: Icons.business_outlined,
            title: 'No hay empresas registradas',
          )
        else
          SizedBox(
            height: 100,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: _empresas.length,
              itemBuilder: (context, index) {
                final empresa = _empresas[index];
                return Card(
                  margin: const EdgeInsets.only(right: 12),
                  child: Container(
                    width: 120,
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.verified, color: Colors.blue.shade400),
                        const SizedBox(height: 8),
                        Text(
                          empresa['nombre'] ?? '',
                          style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 12),
                          textAlign: TextAlign.center,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
      ],
    );
  }

}
