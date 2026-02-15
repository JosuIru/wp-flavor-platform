import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;

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
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.currency_bitcoin, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_mensajeError!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _cargarDatos,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
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
                        Center(
                          child: Column(
                            children: [
                              Icon(Icons.currency_bitcoin, size: 48, color: Colors.grey.shade400),
                              const SizedBox(height: 8),
                              const Text('No hay tokens disponibles'),
                            ],
                          ),
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
                  onPressed: () {},
                  icon: const Icon(Icons.swap_horiz),
                  label: const Text('Swap'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.send),
                  label: const Text('Enviar'),
                ),
              ),
            ],
          ),
        ],
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
          ? const Center(child: CircularProgressIndicator())
          : _mensajeError != null
              ? Center(child: Text(_mensajeError!))
              : _datosToken == null
                  ? const Center(child: Text('No se encontraron datos'))
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
                                onPressed: () {},
                                icon: const Icon(Icons.shopping_cart),
                                label: const Text('Comprar'),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: () {},
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
}
