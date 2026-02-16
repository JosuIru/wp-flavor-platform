import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/providers/providers.dart';

class WooCommerceScreen extends ConsumerStatefulWidget {
  const WooCommerceScreen({super.key});

  @override
  ConsumerState<WooCommerceScreen> createState() => _WooCommerceScreenState();
}

class _WooCommerceScreenState extends ConsumerState<WooCommerceScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoadingProductos = true;
  bool _isLoadingCarrito = false;
  bool _isLoadingPedidos = false;
  List<Map<String, dynamic>> _productos = [];
  List<Map<String, dynamic>> _carrito = [];
  List<Map<String, dynamic>> _misPedidos = [];
  String _categoriaSeleccionada = '';
  List<Map<String, dynamic>> _categorias = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadProductos();
    _loadCarrito();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadProductos() async {
    setState(() => _isLoadingProductos = true);
    final api = ref.read(apiClientProvider);

    try {
      final queryParams = <String, dynamic>{};
      if (_categoriaSeleccionada.isNotEmpty) {
        queryParams['categoria'] = _categoriaSeleccionada;
      }
      final response = await api.get('/woocommerce/productos', queryParameters: queryParams);

      if (response.success && response.data != null) {
        setState(() {
          _productos = (response.data!['productos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
          _categorias = (response.data!['categorias'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });
      }
    } finally {
      setState(() => _isLoadingProductos = false);
    }
  }

  Future<void> _loadCarrito() async {
    setState(() => _isLoadingCarrito = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/woocommerce/carrito');
      if (response.success && response.data != null) {
        setState(() {
          _carrito = (response.data!['items'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });
      }
    } finally {
      setState(() => _isLoadingCarrito = false);
    }
  }

  Future<void> _loadMisPedidos() async {
    setState(() => _isLoadingPedidos = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/woocommerce/pedidos');
      if (response.success && response.data != null) {
        setState(() {
          _misPedidos = (response.data!['pedidos'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });
      }
    } finally {
      setState(() => _isLoadingPedidos = false);
    }
  }

  Future<void> _addToCart(Map<String, dynamic> producto) async {
    final api = ref.read(apiClientProvider);
    final productoId = producto['id']?.toString() ?? '';

    if (productoId.isEmpty) return;

    final response = await api.post('/woocommerce/carrito/add', data: {
      'producto_id': productoId,
      'cantidad': 1,
    });

    if (response.success) {
      _loadCarrito();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('${producto['nombre'] ?? 'Producto'} agregado al carrito'),
            action: SnackBarAction(
              label: 'Ver carrito',
              onPressed: () => _tabController.animateTo(1),
            ),
          ),
        );
      }
    }
  }

  Future<void> _removeFromCart(String itemKey) async {
    final api = ref.read(apiClientProvider);
    final response = await api.post('/woocommerce/carrito/remove', data: {
      'item_key': itemKey,
    });

    if (response.success) {
      _loadCarrito();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Tienda'),
        bottom: TabBar(
          controller: _tabController,
          onTap: (index) {
            if (index == 2 && _misPedidos.isEmpty) {
              _loadMisPedidos();
            }
          },
          tabs: [
            const Tab(icon: Icon(Icons.storefront), text: 'Productos'),
            Tab(
              icon: Badge(
                isLabelVisible: _carrito.isNotEmpty,
                label: Text('${_carrito.length}'),
                child: const Icon(Icons.shopping_cart),
              ),
              text: 'Carrito',
            ),
            const Tab(icon: Icon(Icons.receipt_long), text: 'Pedidos'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildProductosTab(),
          _buildCarritoTab(),
          _buildPedidosTab(),
        ],
      ),
    );
  }

  Widget _buildProductosTab() {
    return Column(
      children: [
        if (_categorias.isNotEmpty) _buildCategoriaFilter(),
        Expanded(
          child: _isLoadingProductos
              ? const Center(child: CircularProgressIndicator())
              : _productos.isEmpty
                  ? _buildEmptyState(
                      icon: Icons.inventory_2_outlined,
                      message: 'No hay productos disponibles',
                    )
                  : RefreshIndicator(
                      onRefresh: _loadProductos,
                      child: GridView.builder(
                        padding: const EdgeInsets.all(12),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          childAspectRatio: 0.7,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                        ),
                        itemCount: _productos.length,
                        itemBuilder: (context, index) => _buildProductoCard(_productos[index]),
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildCategoriaFilter() {
    return Container(
      height: 50,
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        itemCount: _categorias.length + 1,
        itemBuilder: (context, index) {
          if (index == 0) {
            return Padding(
              padding: const EdgeInsets.only(right: 8),
              child: FilterChip(
                label: const Text('Todos'),
                selected: _categoriaSeleccionada.isEmpty,
                onSelected: (_) {
                  setState(() => _categoriaSeleccionada = '');
                  _loadProductos();
                },
              ),
            );
          }
          final categoria = _categorias[index - 1];
          final categoriaId = categoria['id']?.toString() ?? '';
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: FilterChip(
              label: Text(categoria['nombre'] ?? categoria['name'] ?? 'Sin nombre'),
              selected: _categoriaSeleccionada == categoriaId,
              onSelected: (_) {
                setState(() => _categoriaSeleccionada = categoriaId);
                _loadProductos();
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildProductoCard(Map<String, dynamic> producto) {
    final nombre = producto['nombre'] ?? producto['name'] ?? 'Producto';
    final precio = producto['precio'] ?? producto['price'] ?? 0;
    final imagen = producto['imagen'] ?? producto['image'] ?? '';
    final enStock = producto['en_stock'] ?? producto['in_stock'] ?? true;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => _showProductoDetail(producto),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              flex: 3,
              child: imagen.isNotEmpty
                  ? CachedNetworkImage(
                      imageUrl: imagen,
                      fit: BoxFit.cover,
                      width: double.infinity,
                      placeholder: (_, __) => Container(
                        color: Colors.grey.shade200,
                        child: const Center(child: CircularProgressIndicator()),
                      ),
                      errorWidget: (_, __, ___) => Container(
                        color: Colors.grey.shade200,
                        child: const Icon(Icons.image_not_supported, size: 48),
                      ),
                    )
                  : Container(
                      color: Colors.grey.shade200,
                      child: const Center(
                        child: Icon(Icons.shopping_bag, size: 48, color: Colors.grey),
                      ),
                    ),
            ),
            Expanded(
              flex: 2,
              child: Padding(
                padding: const EdgeInsets.all(8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      nombre,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const Spacer(),
                    Row(
                      children: [
                        Text(
                          _formatCurrency(precio),
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Theme.of(context).colorScheme.primary,
                          ),
                        ),
                        const Spacer(),
                        if (enStock)
                          IconButton(
                            icon: const Icon(Icons.add_shopping_cart, size: 20),
                            onPressed: () => _addToCart(producto),
                            visualDensity: VisualDensity.compact,
                          )
                        else
                          Text(
                            'Agotado',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.red.shade600,
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCarritoTab() {
    if (_isLoadingCarrito) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_carrito.isEmpty) {
      return _buildEmptyState(
        icon: Icons.shopping_cart_outlined,
        message: 'Tu carrito esta vacio',
        action: ElevatedButton.icon(
          onPressed: () => _tabController.animateTo(0),
          icon: const Icon(Icons.storefront),
          label: const Text('Ver productos'),
        ),
      );
    }

    final subtotal = _carrito.fold<double>(0, (sum, item) {
      final precio = double.tryParse(item['precio']?.toString() ?? '0') ?? 0;
      final cantidad = item['cantidad'] ?? 1;
      return sum + (precio * cantidad);
    });

    return Column(
      children: [
        Expanded(
          child: RefreshIndicator(
            onRefresh: _loadCarrito,
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _carrito.length,
              itemBuilder: (context, index) => _buildCarritoItem(_carrito[index]),
            ),
          ),
        ),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.1),
                blurRadius: 8,
                offset: const Offset(0, -2),
              ),
            ],
          ),
          child: SafeArea(
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Subtotal:', style: TextStyle(fontSize: 16)),
                    Text(
                      _formatCurrency(subtotal),
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _proceedToCheckout,
                    icon: const Icon(Icons.payment),
                    label: const Text('Finalizar compra'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCarritoItem(Map<String, dynamic> item) {
    final nombre = item['nombre'] ?? item['name'] ?? 'Producto';
    final precio = item['precio'] ?? item['price'] ?? 0;
    final cantidad = item['cantidad'] ?? item['quantity'] ?? 1;
    final imagen = item['imagen'] ?? item['image'] ?? '';
    final itemKey = item['key'] ?? item['id']?.toString() ?? '';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: imagen.isNotEmpty
                  ? CachedNetworkImage(
                      imageUrl: imagen,
                      width: 80,
                      height: 80,
                      fit: BoxFit.cover,
                    )
                  : Container(
                      width: 80,
                      height: 80,
                      color: Colors.grey.shade200,
                      child: const Icon(Icons.shopping_bag),
                    ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(nombre, style: const TextStyle(fontWeight: FontWeight.w600)),
                  const SizedBox(height: 4),
                  Text(_formatCurrency(precio)),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Container(
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey.shade300),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            IconButton(
                              icon: const Icon(Icons.remove, size: 18),
                              onPressed: cantidad > 1 ? () {} : null,
                              visualDensity: VisualDensity.compact,
                            ),
                            Text('$cantidad'),
                            IconButton(
                              icon: const Icon(Icons.add, size: 18),
                              onPressed: () {},
                              visualDensity: VisualDensity.compact,
                            ),
                          ],
                        ),
                      ),
                      const Spacer(),
                      IconButton(
                        icon: const Icon(Icons.delete_outline, color: Colors.red),
                        onPressed: () => _removeFromCart(itemKey),
                      ),
                    ],
                  ),
                ],
              ),
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

    if (_misPedidos.isEmpty) {
      return _buildEmptyState(
        icon: Icons.receipt_long_outlined,
        message: 'No tienes pedidos aun',
      );
    }

    return RefreshIndicator(
      onRefresh: _loadMisPedidos,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _misPedidos.length,
        itemBuilder: (context, index) => _buildPedidoCard(_misPedidos[index]),
      ),
    );
  }

  Widget _buildPedidoCard(Map<String, dynamic> pedido) {
    final numero = pedido['numero'] ?? pedido['number'] ?? pedido['id'];
    final fecha = pedido['fecha'] ?? pedido['date'] ?? '';
    final estado = pedido['estado'] ?? pedido['status'] ?? '';
    final total = pedido['total'] ?? 0;
    final estadoColor = _getEstadoColor(estado);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: estadoColor.withOpacity(0.2),
          child: Icon(Icons.receipt, color: estadoColor),
        ),
        title: Text('#$numero', style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(fecha),
            Text(_formatCurrency(total), style: const TextStyle(fontWeight: FontWeight.w600)),
          ],
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: estadoColor.withOpacity(0.2),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Text(
            _getEstadoLabel(estado),
            style: TextStyle(color: estadoColor, fontWeight: FontWeight.w600, fontSize: 12),
          ),
        ),
        isThreeLine: true,
      ),
    );
  }

  Widget _buildEmptyState({
    required IconData icon,
    required String message,
    Widget? action,
  }) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 64, color: Colors.grey.shade400),
          const SizedBox(height: 16),
          Text(message, style: TextStyle(color: Colors.grey.shade600)),
          if (action != null) ...[
            const SizedBox(height: 16),
            action,
          ],
        ],
      ),
    );
  }

  void _showProductoDetail(Map<String, dynamic> producto) {
    final nombre = producto['nombre'] ?? producto['name'] ?? 'Producto';
    final descripcion = producto['descripcion'] ?? producto['description'] ?? '';
    final precio = producto['precio'] ?? producto['price'] ?? 0;
    final imagen = producto['imagen'] ?? producto['image'] ?? '';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (imagen.isNotEmpty)
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: CachedNetworkImage(
                      imageUrl: imagen,
                      height: 250,
                      width: double.infinity,
                      fit: BoxFit.cover,
                    ),
                  ),
                const SizedBox(height: 16),
                Text(nombre, style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 8),
                Text(
                  _formatCurrency(precio),
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                ),
                if (descripcion.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(descripcion),
                ],
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: () {
                      _addToCart(producto);
                      Navigator.pop(context);
                    },
                    icon: const Icon(Icons.add_shopping_cart),
                    label: const Text('Agregar al carrito'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _proceedToCheckout() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Redirigiendo al checkout...')),
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
    return NumberFormat.currency(locale: 'es_ES', symbol: '\u20AC').format(number);
  }
}
