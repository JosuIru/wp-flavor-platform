import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_state_widgets.dart';

class DexSolanaScreen extends ConsumerStatefulWidget {
  const DexSolanaScreen({super.key});

  @override
  ConsumerState<DexSolanaScreen> createState() => _DexSolanaScreenState();
}

class _DexSolanaScreenState extends ConsumerState<DexSolanaScreen> {
  List<dynamic> _listaTokens = [];
  Map<String, dynamic>? _datosDashboard;
  bool _cargando = true;
  String? _mensajeError;

  @override
  void initState() {
    super.initState();
    _cargarDatos();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _cargando = true;
      _mensajeError = null;
    });
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.get('/dex-solana/dashboard');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _datosDashboard = respuesta.data!;
          _listaTokens = respuesta.data!['tokens'] ?? respuesta.data!['items'] ?? respuesta.data!['data'] ?? [];
          _cargando = false;
        });
      } else {
        setState(() {
          _mensajeError = respuesta.error ?? 'Error al cargar datos DEX';
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
      appBar: AppBar(
        title: const Text('DEX Solana'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _cargarDatos),
        ],
      ),
      body: _cargando
          ? const FlavorLoadingState()
          : _mensajeError != null
              ? FlavorErrorState(
                  message: _mensajeError!,
                  onRetry: _cargarDatos,
                  icon: Icons.currency_bitcoin,
                )
              : RefreshIndicator(
                  onRefresh: _cargarDatos,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _construirResumenBalance(),
                      const SizedBox(height: 24),
                      const Text(
                        'Tokens',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 12),
                      if (_listaTokens.isEmpty)
                        const FlavorEmptyState(
                          icon: Icons.currency_bitcoin,
                          title: 'No hay tokens disponibles',
                        )
                      else
                        ..._listaTokens.map((token) => _construirTarjetaToken(token)),
                    ],
                  ),
                ),
    );
  }

  Widget _construirResumenBalance() {
    final balanceTotal = _datosDashboard?['balance_total'] ?? _datosDashboard?['total_balance'] ?? '0.00';
    final variacion24h = _datosDashboard?['variacion_24h'] ?? _datosDashboard?['change_24h'] ?? 0.0;
    final esPositivo = (variacion24h is num) ? variacion24h >= 0 : true;

    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.orange.shade100,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(Icons.account_balance_wallet, color: Colors.orange.shade700, size: 32),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Balance Total', style: TextStyle(color: Colors.grey)),
                    Text(
                      '\$$balanceTotal',
                      style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold),
                    ),
                    Row(
                      children: [
                        Icon(
                          esPositivo ? Icons.trending_up : Icons.trending_down,
                          color: esPositivo ? Colors.green : Colors.red,
                          size: 16,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${esPositivo ? '+' : ''}$variacion24h% (24h)',
                          style: TextStyle(color: esPositivo ? Colors.green : Colors.red),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: FilledButton.icon(
                  onPressed: () => _mostrarSwapModal(context),
                  icon: const Icon(Icons.swap_horiz),
                  label: const Text('Swap'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _mostrarEnviarModal(context),
                  icon: const Icon(Icons.send),
                  label: const Text('Enviar'),
                ),
              ),
            ],
          ),
        ],
        ),
      ),
    );
  }

  Widget _construirTarjetaToken(dynamic elemento) {
    final mapaDatos = elemento as Map<String, dynamic>;
    final simboloToken = mapaDatos['symbol'] ?? mapaDatos['simbolo'] ?? 'TOKEN';
    final nombreToken = mapaDatos['name'] ?? mapaDatos['nombre'] ?? simboloToken;
    final cantidadToken = mapaDatos['balance'] ?? mapaDatos['cantidad'] ?? '0';
    final precioToken = mapaDatos['price'] ?? mapaDatos['precio'] ?? '0.00';
    final cambio24h = mapaDatos['change_24h'] ?? mapaDatos['cambio_24h'] ?? 0.0;
    final esCambioPositivo = (cambio24h is num) ? cambio24h >= 0 : true;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.orange.shade100,
          child: Text(
            simboloToken.toString().substring(0, simboloToken.toString().length > 2 ? 2 : simboloToken.toString().length),
            style: TextStyle(color: Colors.orange.shade700, fontWeight: FontWeight.bold),
          ),
        ),
        title: Text(nombreToken),
        subtitle: Text('$cantidadToken $simboloToken'),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text('\$$precioToken', style: const TextStyle(fontWeight: FontWeight.bold)),
            Text(
              '${esCambioPositivo ? '+' : ''}$cambio24h%',
              style: TextStyle(
                color: esCambioPositivo ? Colors.green : Colors.red,
                fontSize: 12,
              ),
            ),
          ],
        ),
        onTap: () {
          final idToken = mapaDatos['id'] ?? mapaDatos['address'];
          if (idToken != null) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => TokenDetalleScreen(tokenId: idToken.toString()),
              ),
            );
          }
        },
      ),
    );
  }

  void _mostrarSwapModal(BuildContext context) {
    final cantidadController = TextEditingController();
    String? tokenOrigenSeleccionado;
    String? tokenDestinoSeleccionado;
    bool ejecutandoSwap = false;

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
                    const Icon(Icons.swap_horiz, color: Colors.orange),
                    const SizedBox(width: 12),
                    const Text(
                      'Swap de Tokens',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                // Token origen
                DropdownButtonFormField<String>(
                  value: tokenOrigenSeleccionado,
                  decoration: const InputDecoration(
                    labelText: 'Token de origen',
                    prefixIcon: Icon(Icons.currency_bitcoin),
                    border: OutlineInputBorder(),
                  ),
                  items: _listaTokens.map((token) {
                    final mapa = token as Map<String, dynamic>;
                    final simbolo = mapa['symbol'] ?? mapa['simbolo'] ?? 'TOKEN';
                    return DropdownMenuItem<String>(
                      value: simbolo.toString(),
                      child: Text(simbolo.toString()),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setModalState(() => tokenOrigenSeleccionado = value);
                  },
                ),
                const SizedBox(height: 16),
                // Cantidad
                TextFormField(
                  controller: cantidadController,
                  decoration: const InputDecoration(
                    labelText: 'Cantidad',
                    prefixIcon: Icon(Icons.numbers),
                    border: OutlineInputBorder(),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                ),
                const SizedBox(height: 16),
                // Icono de intercambio
                const Center(
                  child: Icon(Icons.swap_vert, size: 32, color: Colors.orange),
                ),
                const SizedBox(height: 16),
                // Token destino
                DropdownButtonFormField<String>(
                  value: tokenDestinoSeleccionado,
                  decoration: const InputDecoration(
                    labelText: 'Token de destino',
                    prefixIcon: Icon(Icons.currency_bitcoin),
                    border: OutlineInputBorder(),
                  ),
                  items: _listaTokens.map((token) {
                    final mapa = token as Map<String, dynamic>;
                    final simbolo = mapa['symbol'] ?? mapa['simbolo'] ?? 'TOKEN';
                    return DropdownMenuItem<String>(
                      value: simbolo.toString(),
                      child: Text(simbolo.toString()),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setModalState(() => tokenDestinoSeleccionado = value);
                  },
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: ejecutandoSwap
                        ? null
                        : () async {
                            if (tokenOrigenSeleccionado == null ||
                                tokenDestinoSeleccionado == null ||
                                cantidadController.text.isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Completa todos los campos')),
                              );
                              return;
                            }
                            setModalState(() => ejecutandoSwap = true);
                            await _ejecutarSwap(
                              tokenOrigenSeleccionado!,
                              tokenDestinoSeleccionado!,
                              cantidadController.text,
                            );
                            if (context.mounted) Navigator.pop(context);
                          },
                    icon: ejecutandoSwap
                        ? const FlavorInlineSpinner()
                        : const Icon(Icons.swap_horiz),
                    label: Text(ejecutandoSwap ? 'Ejecutando...' : 'Ejecutar Swap'),
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

  Future<void> _ejecutarSwap(String tokenOrigen, String tokenDestino, String cantidad) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/dex-solana/swap', data: {
        'token_origen': tokenOrigen,
        'token_destino': tokenDestino,
        'cantidad': cantidad,
      });
      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Swap ejecutado correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDatos();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al ejecutar swap'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  void _mostrarEnviarModal(BuildContext context) {
    final direccionDestinoController = TextEditingController();
    final cantidadController = TextEditingController();
    String? tokenSeleccionado;
    bool enviando = false;

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
                    const Icon(Icons.send, color: Colors.orange),
                    const SizedBox(width: 12),
                    const Text(
                      'Enviar Tokens',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                // Seleccionar token
                DropdownButtonFormField<String>(
                  value: tokenSeleccionado,
                  decoration: const InputDecoration(
                    labelText: 'Token a enviar',
                    prefixIcon: Icon(Icons.currency_bitcoin),
                    border: OutlineInputBorder(),
                  ),
                  items: _listaTokens.map((token) {
                    final mapa = token as Map<String, dynamic>;
                    final simbolo = mapa['symbol'] ?? mapa['simbolo'] ?? 'TOKEN';
                    final balance = mapa['balance'] ?? mapa['cantidad'] ?? '0';
                    return DropdownMenuItem<String>(
                      value: simbolo.toString(),
                      child: Text('$simbolo ($balance)'),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setModalState(() => tokenSeleccionado = value);
                  },
                ),
                const SizedBox(height: 16),
                // Cantidad
                TextFormField(
                  controller: cantidadController,
                  decoration: const InputDecoration(
                    labelText: 'Cantidad',
                    prefixIcon: Icon(Icons.numbers),
                    border: OutlineInputBorder(),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                ),
                const SizedBox(height: 16),
                // Dirección destino
                TextFormField(
                  controller: direccionDestinoController,
                  decoration: const InputDecoration(
                    labelText: 'Dirección de destino',
                    prefixIcon: Icon(Icons.account_balance_wallet),
                    border: OutlineInputBorder(),
                    hintText: 'Ej: 7xK9p...',
                  ),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: enviando
                        ? null
                        : () async {
                            if (tokenSeleccionado == null ||
                                cantidadController.text.isEmpty ||
                                direccionDestinoController.text.isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Completa todos los campos')),
                              );
                              return;
                            }
                            setModalState(() => enviando = true);
                            await _ejecutarEnvio(
                              tokenSeleccionado!,
                              cantidadController.text,
                              direccionDestinoController.text,
                            );
                            if (context.mounted) Navigator.pop(context);
                          },
                    icon: enviando
                        ? const FlavorInlineSpinner()
                        : const Icon(Icons.send),
                    label: Text(enviando ? 'Enviando...' : 'Enviar'),
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

  Future<void> _ejecutarEnvio(String token, String cantidad, String direccionDestino) async {
    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/dex-solana/send', data: {
        'token': token,
        'cantidad': cantidad,
        'direccion_destino': direccionDestino,
      });
      if (mounted) {
        if (respuesta.success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Tokens enviados correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDatos();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error al enviar'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}

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
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Ingresa una cantidad')),
                              );
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
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Compra realizada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDetalle();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error en la compra'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
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
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Ingresa una cantidad')),
                              );
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
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Venta realizada correctamente'),
              backgroundColor: Colors.green,
            ),
          );
          _cargarDetalle();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(respuesta.error ?? 'Error en la venta'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }
}
