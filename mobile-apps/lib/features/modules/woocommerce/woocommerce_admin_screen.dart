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

class _WooCommerceAdminScreenState extends ConsumerState<WooCommerceAdminScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoadingStats = true;
  bool _isLoadingPedidos = true;
  Map<String, dynamic>? _stats;
  List<Map<String, dynamic>> _pedidos = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    await Future.wait([
      _loadStats(),
      _loadPedidos(),
    ]);
  }

  Future<void> _loadStats() async {
    setState(() => _isLoadingStats = true);

    final api = ref.read(apiClientProvider);
    final response = await api.getWooStats();

    if (response.success && response.data != null) {
      setState(() {
        _stats = response.data;
        _isLoadingStats = false;
      });
    } else {
      setState(() => _isLoadingStats = false);
    }
  }

  Future<void> _loadPedidos() async {
    setState(() => _isLoadingPedidos = true);

    final api = ref.read(apiClientProvider);
    final response = await api.getWooPedidos(limite: 50);

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
    await _loadData();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('WooCommerce'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(icon: Icon(Icons.dashboard), text: 'Dashboard'),
            Tab(icon: Icon(Icons.shopping_cart), text: 'Pedidos'),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refresh,
          ),
        ],
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildDashboardTab(),
          _buildPedidosTab(),
        ],
      ),
    );
  }

  Widget _buildDashboardTab() {
    if (_isLoadingStats) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_stats == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
            const SizedBox(height: 16),
            const Text('Error al cargar estadísticas'),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _loadStats,
              icon: const Icon(Icons.refresh),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      );
    }

    final ventasHoy = _stats!['ventas_hoy'] ?? 0;
    final ventasMes = _stats!['ventas_mes'] ?? 0;
    final pedidosPendientes = _stats!['pedidos_pendientes'] ?? 0;
    final productosActivos = _stats!['productos_activos'] ?? 0;

    return RefreshIndicator(
      onRefresh: _refresh,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Resumen',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 1.3,
              children: [
                _buildStatCard(
                  'Ventas Hoy',
                  _formatCurrency(ventasHoy),
                  Icons.today,
                  Colors.green,
                ),
                _buildStatCard(
                  'Ventas del Mes',
                  _formatCurrency(ventasMes),
                  Icons.calendar_month,
                  Colors.blue,
                ),
                _buildStatCard(
                  'Pedidos Pendientes',
                  pedidosPendientes.toString(),
                  Icons.pending_actions,
                  Colors.orange,
                ),
                _buildStatCard(
                  'Productos Activos',
                  productosActivos.toString(),
                  Icons.inventory_2,
                  Colors.purple,
                ),
              ],
            ),
            const SizedBox(height: 24),
            const Text(
              'Últimos Pedidos',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            _isLoadingPedidos
                ? const Center(child: CircularProgressIndicator())
                : _pedidos.isEmpty
                    ? const Center(child: Text('No hay pedidos'))
                    : Column(
                        children: _pedidos.take(5).map((pedido) => _buildPedidoCard(pedido)).toList(),
                      ),
          ],
        ),
      ),
    );
  }

  Widget _buildPedidosTab() {
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

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 32, color: color),
            const SizedBox(height: 8),
            Text(
              value,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: color,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 4),
            Text(
              title,
              style: const TextStyle(fontSize: 12),
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
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
