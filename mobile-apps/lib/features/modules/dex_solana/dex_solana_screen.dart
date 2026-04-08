import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart' show apiClientProvider;
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'dex_solana_screen_parts.dart';

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

  // Constantes de validación
  static const double _cantidadMinima = 0.000001;
  static const double _cantidadMaxima = 1000000.0;

  /// Valida el formato de una dirección Solana (Base58, 32-44 caracteres)
  bool _validarDireccionSolana(String direccion) {
    if (direccion.isEmpty) return false;
    // Direcciones Solana: 32-44 caracteres, solo Base58 (sin 0, O, I, l)
    final patronBase58 = RegExp(r'^[1-9A-HJ-NP-Za-km-z]{32,44}$');
    return patronBase58.hasMatch(direccion);
  }

  /// Valida que la cantidad sea un número válido dentro de límites
  String? _validarCantidad(String cantidadTexto) {
    if (cantidadTexto.isEmpty) return 'La cantidad es requerida';
    final cantidad = double.tryParse(cantidadTexto);
    if (cantidad == null) return 'Cantidad inválida';
    if (cantidad < _cantidadMinima) return 'Cantidad mínima: $_cantidadMinima';
    if (cantidad > _cantidadMaxima) return 'Cantidad máxima: $_cantidadMaxima';
    return null;
  }

  /// Muestra diálogo de confirmación para operaciones críticas
  Future<bool> _confirmarOperacion({
    required String titulo,
    required String mensaje,
    required String textoConfirmar,
  }) async {
    final resultado = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            const Icon(Icons.warning_amber_rounded, color: Colors.orange),
            const SizedBox(width: 12),
            Expanded(child: Text(titulo)),
          ],
        ),
        content: Text(mensaje),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(textoConfirmar),
          ),
        ],
      ),
    );
    return resultado ?? false;
  }

  /// Maneja errores de forma segura sin exponer detalles técnicos
  String _obtenerMensajeError(dynamic error) {
    // No exponer stack traces ni detalles técnicos al usuario
    if (error is Exception) {
      final mensaje = error.toString();
      if (mensaje.contains('SocketException') || mensaje.contains('Connection')) {
        return 'Error de conexión. Verifica tu internet.';
      }
      if (mensaje.contains('timeout') || mensaje.contains('Timeout')) {
        return 'La operación tardó demasiado. Intenta de nuevo.';
      }
    }
    return 'Ha ocurrido un error. Intenta de nuevo.';
  }

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
        _mensajeError = _obtenerMensajeError(excepcion);
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
                              FlavorSnackbar.showError(context, 'Completa todos los campos');
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
    // Validar cantidad
    final errorCantidad = _validarCantidad(cantidad);
    if (errorCantidad != null) {
      if (mounted) FlavorSnackbar.showError(context, errorCantidad);
      return;
    }

    // Validar que no sean el mismo token
    if (tokenOrigen == tokenDestino) {
      if (mounted) FlavorSnackbar.showError(context, 'Selecciona tokens diferentes');
      return;
    }

    // Confirmar operación
    final confirmarSwap = await _confirmarOperacion(
      titulo: 'Confirmar Swap',
      mensaje: '¿Deseas intercambiar $cantidad $tokenOrigen por $tokenDestino?\n\nEsta operación no se puede deshacer.',
      textoConfirmar: 'Confirmar Swap',
    );

    if (!confirmarSwap) return;

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/dex-solana/swap', data: {
        'token_origen': tokenOrigen,
        'token_destino': tokenDestino,
        'cantidad': cantidad,
      });
      if (mounted) {
        if (respuesta.success) {
          FlavorSnackbar.showSuccess(context, 'Swap ejecutado correctamente');
          _cargarDatos();
        } else {
          FlavorSnackbar.showError(context, respuesta.error ?? 'Error al ejecutar swap');
        }
      }
    } catch (excepcion) {
      if (mounted) {
        FlavorSnackbar.showError(context, _obtenerMensajeError(excepcion));
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
                              FlavorSnackbar.showError(context, 'Completa todos los campos');
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
    // Validar dirección Solana
    if (!_validarDireccionSolana(direccionDestino)) {
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          'Dirección Solana inválida. Debe tener 32-44 caracteres Base58.',
        );
      }
      return;
    }

    // Validar cantidad
    final errorCantidad = _validarCantidad(cantidad);
    if (errorCantidad != null) {
      if (mounted) FlavorSnackbar.showError(context, errorCantidad);
      return;
    }

    // Confirmar operación (envío es irreversible)
    final confirmarEnvio = await _confirmarOperacion(
      titulo: 'Confirmar Envío',
      mensaje: '¿Deseas enviar $cantidad $token a:\n\n${direccionDestino.substring(0, 8)}...${direccionDestino.substring(direccionDestino.length - 8)}\n\n⚠️ Esta operación es IRREVERSIBLE.',
      textoConfirmar: 'Confirmar Envío',
    );

    if (!confirmarEnvio) return;

    try {
      final clienteApi = ref.read(apiClientProvider);
      final respuesta = await clienteApi.post('/dex-solana/send', data: {
        'token': token,
        'cantidad': cantidad,
        'direccion_destino': direccionDestino,
      });
      if (mounted) {
        if (respuesta.success) {
          FlavorSnackbar.showSuccess(context, 'Tokens enviados correctamente');
          _cargarDatos();
        } else {
          FlavorSnackbar.showError(context, respuesta.error ?? 'Error al enviar');
        }
      }
    } catch (excepcion) {
      if (mounted) {
        FlavorSnackbar.showError(context, _obtenerMensajeError(excepcion));
      }
    }
  }
}
