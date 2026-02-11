import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart' show apiClientProvider;

class ModuleClientDashboardScreen extends ConsumerWidget {
  const ModuleClientDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final i18n = AppLocalizations.of(context)!;
    final api = ref.read(apiClientProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.clientDashboardTitle),
      ),
      body: FutureBuilder<List<_DashboardMetric>>(
        future: _loadMetrics(api),
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final metrics = snapshot.data!;
          if (metrics.isEmpty) {
            return Center(child: Text(i18n.clientDashboardEmpty));
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: metrics.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final metric = metrics[index];
              return Card(
                elevation: 1,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: ListTile(
                  leading: Icon(metric.icon),
                  title: Text(metric.title),
                  subtitle: Text(metric.subtitle),
                  trailing: Text(metric.value),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Future<List<_DashboardMetric>> _loadMetrics(ApiClient api) async {
    final results = <_DashboardMetric>[];

    final pedidos = await api.getGruposConsumoMisPedidos();
    if (pedidos.success && pedidos.data != null) {
      final list = (pedidos.data!['data'] as List<dynamic>? ?? []);
      results.add(_DashboardMetric(
        title: 'Mis pedidos',
        subtitle: 'Grupos de Consumo',
        value: list.length.toString(),
        icon: Icons.shopping_basket_outlined,
      ));
    }

    final servicios = await api.getBancoTiempoMisServicios();
    if (servicios.success && servicios.data != null) {
      final list = (servicios.data!['servicios'] as List<dynamic>? ?? []);
      results.add(_DashboardMetric(
        title: 'Mis servicios',
        subtitle: 'Banco de Tiempo',
        value: list.length.toString(),
        icon: Icons.volunteer_activism,
      ));
    }

    final anuncios = await api.getMarketplaceMisAnuncios();
    if (anuncios.success && anuncios.data != null) {
      final list = (anuncios.data!['anuncios'] as List<dynamic>? ?? []);
      results.add(_DashboardMetric(
        title: 'Mis anuncios',
        subtitle: 'Marketplace',
        value: list.length.toString(),
        icon: Icons.storefront_outlined,
      ));
    }

    return results;
  }
}

class _DashboardMetric {
  final String title;
  final String subtitle;
  final String value;
  final IconData icon;

  const _DashboardMetric({
    required this.title,
    required this.subtitle,
    required this.value,
    required this.icon,
  });
}
