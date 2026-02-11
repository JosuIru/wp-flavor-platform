import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class WooCommerceAdminScreen extends ConsumerStatefulWidget {
  const WooCommerceAdminScreen({super.key});

  @override
  ConsumerState<WooCommerceAdminScreen> createState() => _WooCommerceAdminScreenState();
}

class _WooCommerceAdminScreenState extends ConsumerState<WooCommerceAdminScreen> {
  bool _isLoadingPedidos = true;
  List<Map<String, dynamic>> _pedidos = [];

  @override
  void initState() {
    super.initState();
    _loadPedidos();
  }

  Future<void> _loadPedidos() async {
    setState(() => _isLoadingPedidos = true);

    final api = ref.read(apiClientProvider);
    final response = await api.getWooPedidos(limite: 100);

    if (response.success && response.data != null) {
      final pedidos = (response.data!['pedidos'] as List<dynamic>? ?? [])
          .whereType<Map<String, dynamic>>()
          .toList();

      setState(() {
        _pedidos = pedidos;
        _isLoadingPedidos = false;
      });
    } else {
      setState(() => _isLoadingPedidos = false);
    }
  }

  Future<void> _refresh() async {
    await _loadPedidos();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('WooCommerce - Pedidos'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refresh,
          ),
        ],
      ),
      body: _buildPedidosView(),
    );
  }

  Widget _buildPedidosView() {
    if (_isLoadingPedidos) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_pedidos.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.shopping_cart_outlined, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            const Text('No hay pedidos'),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _pedidos.length,
        itemBuilder: (context, index) {
          return _buildPedidoCard(_pedidos[index]);
        },
      ),
    );
  }

  Widget _buildPedidoCard(Map<String, dynamic> pedido) {
    final id = pedido['id'] ?? '';
    final numero = pedido['numero'] ?? pedido['number'] ?? id;
    final fecha = pedido['fecha'] ?? pedido['date'] ?? '';
    final estado = pedido['estado'] ?? pedido['status'] ?? '';
    final total = pedido['total'] ?? 0;
    final cliente = pedido['cliente'] ?? pedido['customer'] ?? '';

    final estadoColor = _getEstadoColor(estado);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: estadoColor.withOpacity(0.2),
          child: Icon(Icons.shopping_bag, color: estadoColor),
        ),
        title: Row(
          children: [
            Text(
              '#$numero',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            const Spacer(),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: estadoColor.withOpacity(0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                _getEstadoLabel(estado),
                style: TextStyle(
                  fontSize: 12,
                  color: estadoColor,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            if (cliente.isNotEmpty) Text('Cliente: $cliente'),
            if (fecha.isNotEmpty) Text('Fecha: $fecha'),
            Text(
              _formatCurrency(total),
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
          ],
        ),
        isThreeLine: true,
      ),
    );
  }

  Color _getEstadoColor(String estado) {
    switch (estado.toLowerCase()) {
      case 'pending':
      case 'pendiente':
        return Colors.orange;
      case 'processing':
      case 'procesando':
        return Colors.blue;
      case 'completed':
      case 'completado':
        return Colors.green;
      case 'cancelled':
      case 'cancelado':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _getEstadoLabel(String estado) {
    switch (estado.toLowerCase()) {
      case 'pending':
        return 'Pendiente';
      case 'processing':
        return 'Procesando';
      case 'completed':
        return 'Completado';
      case 'cancelled':
        return 'Cancelado';
      default:
        return estado;
    }
  }

  String _formatCurrency(dynamic value) {
    final number = double.tryParse(value.toString()) ?? 0;
    return NumberFormat.currency(locale: 'es_ES', symbol: '€').format(number);
  }
}
