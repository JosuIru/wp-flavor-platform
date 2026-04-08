part of 'dex_solana_screen.dart';

class TokenDetalleScreen extends ConsumerStatefulWidget {
  final String tokenId;

  const TokenDetalleScreen({super.key, required this.tokenId});

  @override
  ConsumerState<TokenDetalleScreen> createState() => _TokenDetalleScreenState();
}

class _TokenDetalleScreenState extends ConsumerState<TokenDetalleScreen> {
  Map<String, dynamic>? _datosToken;
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarDetalle();
  }

  Future<void> _cargarDetalle() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/dex-solana/token/${widget.tokenId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosToken = respuesta.data!['data'] ?? respuesta.data!;
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar token';
          _cargando = false;
        });
      }
    } catch (excepcion) {
      setState(() {
        _mensajeError = excepcion.toString();
        _cargando = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Detalle del Token')),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(message: _mensajeError!, onRetry: _cargarDetalle)
              : _datosToken == null
                  ? const FlavorEmptyState(
                      icon: Icons.token_outlined,
                      title: 'No se encontraron datos',
                    )
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: [
                        Text(
                          _datosToken!['name'] ?? _datosToken!['nombre'] ?? 'Token',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          _datosToken!['symbol'] ?? _datosToken!['simbolo'] ?? '',
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                        const SizedBox(height: 24),
                        Row(
                          children: [
                            Expanded(
                              child: FilledButton.icon(
                                onPressed: () => _mostrarComprarModal(context),
                                icon: const Icon(Icons.shopping_cart),
                                label: const Text('Comprar'),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: () => _mostrarVenderModal(context),
                                icon: const Icon(Icons.sell),
                                label: const Text('Vender'),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
    );
  }

  void _mostrarComprarModal(BuildContext context) {
    final cantidadController = TextEditingController();
    bool comprando = false;
    final simboloToken = _datosToken?['symbol'] ?? _datosToken?['simbolo'] ?? 'TOKEN';
    final precioToken = _datosToken?['price'] ?? _datosToken?['precio'] ?? '0.00';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
            left: 20,
            right: 20,
            top: 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.shopping_cart, color: Colors.green),
                    const SizedBox(width: 12),
                    Text(
                      'Comprar $simboloToken',
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Card(
                  color: Colors.grey.shade100,
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Precio actual:'),
                        Text(
                          '\$$precioToken',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: cantidadController,
                  decoration: InputDecoration(
                    labelText: 'Cantidad de $simboloToken',
                    prefixIcon: const Icon(Icons.numbers),
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: comprando
                        ? null
                        : () async {
                            if (cantidadController.text.isEmpty) {
                              FlavorSnackbar.showError(context, 'Ingresa una cantidad');
                              return;
                            }
                            setModalState(() => comprando = true);
                            await _ejecutarCompra(cantidadController.text);
                            if (context.mounted) Navigator.pop(context);
                          },
                    icon: comprando
                        ? const FlavorInlineSpinner()
                        : const Icon(Icons.shopping_cart),
                    label: Text(comprando ? 'Comprando...' : 'Comprar'),
                    style: FilledButton.styleFrom(backgroundColor: Colors.green),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _ejecutarCompra(String cantidad) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/dex-solana/buy', data: {
        'token_id': widget.tokenId,
        'cantidad': cantidad,
      });
      if (mounted) {
        if (respuesta.success) {
          FlavorSnackbar.showSuccess(context, 'Compra realizada correctamente');
          _cargarDetalle();
        } else {
          FlavorSnackbar.showError(context, respuesta.error ?? 'Error en la compra');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  void _mostrarVenderModal(BuildContext context) {
    final cantidadController = TextEditingController();
    bool vendiendo = false;
    final simboloToken = _datosToken?['symbol'] ?? _datosToken?['simbolo'] ?? 'TOKEN';
    final precioToken = _datosToken?['price'] ?? _datosToken?['precio'] ?? '0.00';
    final balanceToken = _datosToken?['balance'] ?? _datosToken?['cantidad'] ?? '0';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
            left: 20,
            right: 20,
            top: 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.sell, color: Colors.red),
                    const SizedBox(width: 12),
                    Text(
                      'Vender $simboloToken',
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Card(
                  color: Colors.grey.shade100,
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Precio actual:'),
                            Text(
                              '\$$precioToken',
                              style: const TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Tu balance:'),
                            Text(
                              '$balanceToken $simboloToken',
                              style: const TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: cantidadController,
                  decoration: InputDecoration(
                    labelText: 'Cantidad de $simboloToken a vender',
                    prefixIcon: const Icon(Icons.numbers),
                    border: const OutlineInputBorder(),
                    suffixIcon: TextButton(
                      onPressed: () {
                        cantidadController.text = balanceToken.toString();
                      },
                      child: const Text('MAX'),
                    ),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: vendiendo
                        ? null
                        : () async {
                            if (cantidadController.text.isEmpty) {
                              FlavorSnackbar.showError(context, 'Ingresa una cantidad');
                              return;
                            }
                            setModalState(() => vendiendo = true);
                            await _ejecutarVenta(cantidadController.text);
                            if (context.mounted) Navigator.pop(context);
                          },
                    icon: vendiendo
                        ? const FlavorInlineSpinner()
                        : const Icon(Icons.sell),
                    label: Text(vendiendo ? 'Vendiendo...' : 'Vender'),
                    style: FilledButton.styleFrom(backgroundColor: Colors.red),
                  ),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _ejecutarVenta(String cantidad) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/dex-solana/sell', data: {
        'token_id': widget.tokenId,
        'cantidad': cantidad,
      });
      if (mounted) {
        if (respuesta.success) {
          FlavorSnackbar.showSuccess(context, 'Venta realizada correctamente');
          _cargarDetalle();
        } else {
          FlavorSnackbar.showError(context, respuesta.error ?? 'Error en la venta');
        }
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }
}
