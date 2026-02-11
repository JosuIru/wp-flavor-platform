import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

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
            return const Center(child: CircularProgressIndicator());
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return Center(child: Text(res.error ?? 'No se pudieron cargar facturas'));
          }
          final facturas = (res.data!['facturas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          if (facturas.isEmpty) {
            return const Center(child: Text('No hay facturas'));
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
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => FacturaDetailScreen(facturaId: id),
                        ),
                      );
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
    final uri = Uri.parse(url);
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No se pudo abrir el PDF')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detalle de factura')),
      body: FutureBuilder<ApiResponse<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final res = snapshot.data!;
          if (!res.success || res.data == null) {
            return Center(child: Text(res.error ?? 'No se pudo cargar la factura'));
          }
          final f = res.data!;
          return ListView(
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
          );
        },
      ),
    );
  }
}
