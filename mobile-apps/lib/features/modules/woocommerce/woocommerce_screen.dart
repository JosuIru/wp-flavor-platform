import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

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

  Future<void> _updateCartQuantity(String itemKey, int nuevaCantidad) async {
    if (nuevaCantidad < 1) return;

    final api = ref.read(apiClientProvider);
    final response = await api.post('/woocommerce/carrito/update', data: {
      'item_key': itemKey,
      'cantidad': nuevaCantidad,
    });

    if (response.success) {
      _loadCarrito();
    } else {
      if (mounted) {
        FlavorSnackbar.showError(context, response.error ?? 'Error al actualizar cantidad');
      }
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
              ? const FlavorLoadingState()
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
                        child: const FlavorLoadingState(),
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
      return const FlavorLoadingState();
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
                              onPressed: cantidad > 1
                                  ? () => _updateCartQuantity(itemKey, cantidad - 1)
                                  : null,
                              visualDensity: VisualDensity.compact,
                            ),
                            Text('$cantidad'),
                            IconButton(
                              icon: const Icon(Icons.add, size: 18),
                              onPressed: () => _updateCartQuantity(itemKey, cantidad + 1),
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
      return const FlavorLoadingState();
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
      child: InkWell(
        onTap: () => _showPedidoDetail(pedido),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              CircleAvatar(
                backgroundColor: estadoColor.withOpacity(0.2),
                child: Icon(Icons.receipt, color: estadoColor),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('#$numero', style: const TextStyle(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(fecha, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                    const SizedBox(height: 4),
                    Text(_formatCurrency(total), style: const TextStyle(fontWeight: FontWeight.w600)),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Container(
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
                  const SizedBox(height: 8),
                  Icon(Icons.chevron_right, color: Colors.grey.shade400),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showPedidoDetail(Map<String, dynamic> pedido) {
    final pedidoId = pedido['id']?.toString() ?? '';
    final numero = pedido['numero'] ?? pedido['number'] ?? pedido['id'];
    final fecha = pedido['fecha'] ?? pedido['date'] ?? '';
    final estado = pedido['estado'] ?? pedido['status'] ?? '';
    final total = pedido['total'] ?? 0;
    final items = (pedido['items'] as List<dynamic>? ?? [])
        .whereType<Map<String, dynamic>>()
        .toList();
    final direccion = pedido['direccion'] ?? pedido['shipping'] ?? {};
    final tracking = pedido['tracking'] ?? '';
    final notas = pedido['notas'] ?? pedido['customer_note'] ?? '';
    final estadoColor = _getEstadoColor(estado);
    final puedeCancelar = estado.toLowerCase() == 'pending' ||
                          estado.toLowerCase() == 'pendiente' ||
                          estado.toLowerCase() == 'processing' ||
                          estado.toLowerCase() == 'procesando';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            // Handle bar
            Container(
              margin: const EdgeInsets.symmetric(vertical: 12),
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            // Header
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Pedido #$numero',
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 4),
                        Text(fecha, style: TextStyle(color: Colors.grey.shade600)),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(
                      color: estadoColor.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      _getEstadoLabel(estado),
                      style: TextStyle(color: estadoColor, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 24),
            // Content
            Expanded(
              child: ListView(
                controller: scrollController,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                children: [
                  // Items del pedido
                  Text(
                    'Productos (${items.length})',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  const SizedBox(height: 12),
                  if (items.isEmpty)
                    Card(
                      child: ListTile(
                        leading: const Icon(Icons.shopping_bag_outlined),
                        title: const Text('Sin detalles de productos'),
                        subtitle: Text('Los items del pedido no están disponibles',
                            style: TextStyle(color: Colors.grey.shade600)),
                      ),
                    )
                  else
                    ...items.map((item) {
                      final itemNombre = item['nombre'] ?? item['name'] ?? 'Producto';
                      final itemPrecio = item['precio'] ?? item['price'] ?? item['subtotal'] ?? 0;
                      final itemCantidad = item['cantidad'] ?? item['quantity'] ?? 1;
                      final itemImagen = item['imagen'] ?? item['image'] ?? '';

                      return Card(
                        margin: const EdgeInsets.only(bottom: 8),
                        child: ListTile(
                          leading: itemImagen.isNotEmpty
                              ? ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.network(
                                    itemImagen,
                                    width: 50,
                                    height: 50,
                                    fit: BoxFit.cover,
                                    errorBuilder: (_, __, ___) => Container(
                                      width: 50,
                                      height: 50,
                                      color: Colors.grey.shade200,
                                      child: const Icon(Icons.shopping_bag, size: 24),
                                    ),
                                  ),
                                )
                              : Container(
                                  width: 50,
                                  height: 50,
                                  decoration: BoxDecoration(
                                    color: Colors.grey.shade200,
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Icon(Icons.shopping_bag, size: 24),
                                ),
                          title: Text(itemNombre, maxLines: 2, overflow: TextOverflow.ellipsis),
                          subtitle: Text('Cantidad: $itemCantidad'),
                          trailing: Text(
                            _formatCurrency(itemPrecio),
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                        ),
                      );
                    }),

                  const SizedBox(height: 16),

                  // Dirección de envío
                  if (direccion is Map && direccion.isNotEmpty) ...[
                    const Text(
                      'Dirección de envío',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 12),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            const Icon(Icons.location_on_outlined, color: Colors.grey),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                _formatDireccion(direccion),
                                style: const TextStyle(height: 1.4),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Tracking
                  if (tracking.toString().isNotEmpty) ...[
                    const Text(
                      'Seguimiento',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 12),
                    Card(
                      child: ListTile(
                        leading: const Icon(Icons.local_shipping_outlined, color: Colors.blue),
                        title: const Text('Número de seguimiento'),
                        subtitle: Text(tracking.toString()),
                        trailing: IconButton(
                          icon: const Icon(Icons.copy),
                          onPressed: () {
                            Clipboard.setData(ClipboardData(text: tracking.toString()));
                            FlavorSnackbar.showInfo(context, 'Copiado al portapapeles');
                          },
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Notas
                  if (notas.toString().isNotEmpty) ...[
                    const Text(
                      'Notas del pedido',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 12),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(Icons.note_outlined, color: Colors.grey),
                            const SizedBox(width: 12),
                            Expanded(child: Text(notas.toString())),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Total
                  Card(
                    color: Theme.of(context).colorScheme.primaryContainer.withOpacity(0.3),
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Total del pedido', style: TextStyle(fontSize: 16)),
                          Text(
                            _formatCurrency(total),
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).colorScheme.primary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 24),

                  // Botón cancelar
                  if (puedeCancelar)
                    OutlinedButton.icon(
                      onPressed: () => _cancelarPedido(context, pedidoId, numero.toString()),
                      icon: const Icon(Icons.cancel_outlined, color: Colors.red),
                      label: const Text('Cancelar pedido', style: TextStyle(color: Colors.red)),
                      style: OutlinedButton.styleFrom(
                        minimumSize: const Size.fromHeight(48),
                        side: const BorderSide(color: Colors.red),
                      ),
                    ),

                  const SizedBox(height: 32),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDireccion(Map<dynamic, dynamic> direccion) {
    final partes = <String>[];

    final calle = direccion['address_1'] ?? direccion['calle'] ?? '';
    final calle2 = direccion['address_2'] ?? '';
    final ciudad = direccion['city'] ?? direccion['ciudad'] ?? '';
    final provincia = direccion['state'] ?? direccion['provincia'] ?? '';
    final cp = direccion['postcode'] ?? direccion['codigo_postal'] ?? '';
    final pais = direccion['country'] ?? direccion['pais'] ?? '';

    if (calle.toString().isNotEmpty) partes.add(calle.toString());
    if (calle2.toString().isNotEmpty) partes.add(calle2.toString());
    if (ciudad.toString().isNotEmpty || cp.toString().isNotEmpty) {
      partes.add('${cp.toString()} ${ciudad.toString()}'.trim());
    }
    if (provincia.toString().isNotEmpty) partes.add(provincia.toString());
    if (pais.toString().isNotEmpty) partes.add(pais.toString());

    return partes.join('\n');
  }

  Future<void> _cancelarPedido(BuildContext context, String pedidoId, String numero) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancelar pedido'),
        content: Text('¿Estás seguro de que deseas cancelar el pedido #$numero?\n\nEsta acción no se puede deshacer.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('No, mantener'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Sí, cancelar'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/woocommerce/pedidos/$pedidoId/cancelar', data: {});

      if (mounted) {
        Navigator.pop(context); // Cerrar modal

        if (response.success) {
          FlavorSnackbar.showSuccess(context, 'Pedido #$numero cancelado');
          _loadMisPedidos();
        } else {
          FlavorSnackbar.showError(context, response.error ?? 'Error al cancelar pedido');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  Widget _buildEmptyState({
    required IconData icon,
    required String message,
    Widget? action,
  }) {
    return FlavorEmptyState(
      icon: icon,
      title: message,
      action: action,
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

  void _proceedToCheckout() async {
    final api = ref.read(apiClientProvider);

    // Mostrar dialogo de confirmacion
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) {
        final subtotal = _carrito.fold<double>(0, (sum, item) {
          final precio = double.tryParse(item['precio']?.toString() ?? '0') ?? 0;
          final cantidad = item['cantidad'] ?? 1;
          return sum + (precio * cantidad);
        });

        return AlertDialog(
          title: const Text('Confirmar pedido'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('${_carrito.length} producto(s) en tu carrito'),
              const SizedBox(height: 8),
              Text(
                'Total: ${_formatCurrency(subtotal)}',
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              Text(
                'Al confirmar, se creara tu pedido y recibiras instrucciones de pago.',
                style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Confirmar pedido'),
            ),
          ],
        );
      },
    );

    if (confirmar != true) return;

    // Crear pedido
    try {
      final response = await api.post('/woocommerce/checkout', data: {
        'items': _carrito,
      });

      if (response.success) {
        final numeroPedido = response.data?['order_number'] ?? '';
        if (mounted) {
          FlavorSnackbar.showSuccess(context, 'Pedido #$numeroPedido creado correctamente');
          // Limpiar carrito y cargar pedidos
          _carrito.clear();
          _loadCarrito();
          _loadMisPedidos();
          _tabController.animateTo(2); // Ir a pestaña de pedidos
        }
      } else {
        throw Exception(response.error ?? 'Error al crear pedido');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
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
