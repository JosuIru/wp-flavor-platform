import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

class FacturasScreen extends ConsumerStatefulWidget {
  const FacturasScreen({super.key});

  @override
  ConsumerState<FacturasScreen> createState() => _FacturasScreenState();
}

class _FacturasScreenState extends ConsumerState<FacturasScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getFacturas();
  }

  Future<void> _refresh() async {
    setState(() {
      _future = ref.read(apiClientProvider).getFacturas();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Facturas')),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return FlavorErrorState(
              message: res.error ?? 'No se pudieron cargar facturas',
              onRetry: _refresh,
              icon: Icons.receipt_long_outlined,
            );
          }
          final facturas = (res.data!['facturas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          if (facturas.isEmpty) {
            return const FlavorEmptyState(
              icon: Icons.receipt_long_outlined,
              title: 'No hay facturas',
            );
          }
          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: facturas.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final f = facturas[index];
                final id = (f['id'] as num?)?.toInt() ?? 0;
                final numero = f['numero_factura']?.toString() ?? '#$id';
                final cliente = f['cliente_nombre']?.toString() ?? '';
                final total = f['total']?.toString() ?? '';
                final estado = f['estado']?.toString() ?? '';
                return Card(
                  elevation: 1,
                  child: ListTile(
                    leading: const Icon(Icons.receipt),
                    title: Text(numero),
                    subtitle: Text('$cliente · $estado'),
                    trailing: Text(total),
                    onTap: () async {
                      final result = await Navigator.of(context).push<bool>(
                        MaterialPageRoute(
                          builder: (_) => FacturaDetailScreen(facturaId: id),
                        ),
                      );
                      if (result == true && mounted) {
                        _refresh();
                      }
                    },
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class FacturaDetailScreen extends ConsumerStatefulWidget {
  final int facturaId;
  const FacturaDetailScreen({super.key, required this.facturaId});

  @override
  ConsumerState<FacturaDetailScreen> createState() => _FacturaDetailScreenState();
}

class _FacturaDetailScreenState extends ConsumerState<FacturaDetailScreen> {
  late Future<ApiResponse<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(apiClientProvider).getFactura(widget.facturaId);
  }

  Future<void> _openPdf() async {
    final url = await ref.read(apiClientProvider).getFacturaPdfUrl(widget.facturaId);
    if (!mounted) return;
    await FlavorUrlLauncher.openExternal(
      context,
      url,
      emptyMessage: 'La factura no tiene PDF disponible.',
      errorMessage: 'No se pudo abrir el PDF',
    );
  }

  Future<void> _refresh() async {
    setState(() {
      _future = ref.read(apiClientProvider).getFactura(widget.facturaId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detalle de factura')),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const FlavorLoadingState();
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return FlavorErrorState(
              message: res.error ?? 'No se pudo cargar la factura',
              onRetry: _refresh,
              icon: Icons.receipt_outlined,
            );
          }
          final f = res.data!;
          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                ListTile(
                  title: Text(f['numero_factura']?.toString() ?? 'Factura'),
                  subtitle: Text(f['cliente_nombre']?.toString() ?? ''),
                  trailing: Text(f['estado']?.toString() ?? ''),
                ),
                ListTile(
                  title: const Text('Total'),
                  trailing: Text(f['total']?.toString() ?? ''),
                ),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: _openPdf,
                  child: const Text('Ver PDF'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
