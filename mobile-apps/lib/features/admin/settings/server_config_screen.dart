import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../../core/config/server_config.dart';
import '../../../core/api/api_client.dart';

/// Pantalla de configuracion del servidor
class ServerConfigScreen extends ConsumerStatefulWidget {
  const ServerConfigScreen({super.key});

  @override
  ConsumerState<ServerConfigScreen> createState() => _ServerConfigScreenState();
}

class _ServerConfigScreenState extends ConsumerState<ServerConfigScreen> {
  final _formKey = GlobalKey<FormState>();
  final _urlController = TextEditingController();
  final _namespaceController = TextEditingController();
  bool _isLoading = false;
  bool _isTesting = false;
  String? _testResult;
  bool? _testSuccess;

  @override
  void initState() {
    super.initState();
    _loadCurrentConfig();
  }

  Future<void> _loadCurrentConfig() async {
    final serverUrl = await ServerConfig.getServerUrl();
    final namespace = await ServerConfig.getApiNamespace();
    setState(() {
      _urlController.text = serverUrl;
      _namespaceController.text = namespace;
    });
  }

  @override
  void dispose() {
    _urlController.dispose();
    _namespaceController.dispose();
    super.dispose();
  }

  Future<void> _testConnection() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isTesting = true;
      _testResult = null;
      _testSuccess = null;
    });

    try {
      final url = _urlController.text.trim();
      final namespace = _namespaceController.text.trim();
      final fullUrl = '$url$namespace';

      // Crear cliente temporal para probar
      final testClient = ApiClient(baseUrl: fullUrl);
      final response = await testClient.getBusinessInfo();

      setState(() {
        _isTesting = false;
        if (response.success) {
          _testSuccess = true;
          _testResult = 'Conexion exitosa con el servidor';
        } else {
          _testSuccess = false;
          _testResult = response.error ?? 'Error de conexion';
        }
      });
    } catch (e) {
      setState(() {
        _isTesting = false;
        _testSuccess = false;
        _testResult = 'Error: ${e.toString()}';
      });
    }
  }

  Future<void> _saveConfig() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final url = _urlController.text.trim();
      final namespace = _namespaceController.text.trim();

      await ServerConfig.setServerUrl(url);
      await ServerConfig.setApiNamespace(namespace);

      // Actualizar el provider
      ref.read(serverConfigProvider.notifier).updateServerUrl(url);
      ref.read(serverConfigProvider.notifier).updateApiNamespace(namespace);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Configuracion guardada. Reinicia la app para aplicar los cambios.'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 3),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error al guardar: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _scanQRCode() async {
    final result = await Navigator.push<String>(
      context,
      MaterialPageRoute(
        builder: (context) => const _QRScannerScreen(),
      ),
    );

    if (result != null && result.isNotEmpty && mounted) {
      // El QR puede contener solo la URL o un JSON con configuración
      String serverUrl = result;

      // Si es un JSON, extraer la URL
      if (result.startsWith('{')) {
        try {
          final jsonStart = result.indexOf('{');
          final jsonEnd = result.lastIndexOf('}');
          if (jsonStart >= 0 && jsonEnd > jsonStart) {
            final jsonStr = result.substring(jsonStart, jsonEnd + 1);
            // Parsear el JSON simple
            final urlMatch = RegExp(r'"server_url"\s*:\s*"([^"]+)"').firstMatch(jsonStr);
            if (urlMatch != null) {
              serverUrl = urlMatch.group(1)!;
            }
          }
        } catch (e) {
          // Si falla el parse, usar el resultado como URL
        }
      }

      // Limpiar la URL
      serverUrl = serverUrl.trim();
      if (serverUrl.endsWith('/')) {
        serverUrl = serverUrl.substring(0, serverUrl.length - 1);
      }

      // Validar que es una URL válida
      if (ServerConfig.isValidUrl(serverUrl)) {
        setState(() {
          _urlController.text = serverUrl;
        });

        // Probar conexión automáticamente
        await _testConnection();

        if (_testSuccess == true) {
          // Guardar automáticamente si la conexión es exitosa
          await _saveConfig();
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('QR no válido: $serverUrl'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _resetToDefaults() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Restablecer valores'),
        content: const Text('Se restablecera la URL del servidor a los valores por defecto. ¿Continuar?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Restablecer'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await ServerConfig.resetToDefaults();
      await _loadCurrentConfig();
      ref.read(serverConfigProvider.notifier).resetToDefaults();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Configuracion restablecida'),
            backgroundColor: Colors.blue,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Configuracion del servidor'),
        actions: [
          IconButton(
            icon: const Icon(Icons.restore),
            tooltip: 'Restablecer valores',
            onPressed: _resetToDefaults,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Opción de escanear QR
              Card(
                color: colorScheme.primaryContainer,
                child: InkWell(
                  onTap: _scanQRCode,
                  borderRadius: BorderRadius.circular(12),
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: colorScheme.primary,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(
                            Icons.qr_code_scanner,
                            color: colorScheme.onPrimary,
                            size: 32,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Escanear código QR',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                  color: colorScheme.onPrimaryContainer,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Escanea el QR desde el panel de WordPress para configurar automáticamente',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: colorScheme.onPrimaryContainer.withOpacity(0.8),
                                ),
                              ),
                            ],
                          ),
                        ),
                        Icon(
                          Icons.chevron_right,
                          color: colorScheme.onPrimaryContainer,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Divider con texto
              Row(
                children: [
                  const Expanded(child: Divider()),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      'o configura manualmente',
                      style: TextStyle(
                        color: colorScheme.outline,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  const Expanded(child: Divider()),
                ],
              ),
              const SizedBox(height: 16),

              // URL del servidor
              Text(
                'URL del servidor',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _urlController,
                decoration: const InputDecoration(
                  labelText: 'URL del servidor',
                  hintText: 'https://ejemplo.com',
                  prefixIcon: Icon(Icons.language),
                  helperText: 'Incluir https:// o http://',
                ),
                keyboardType: TextInputType.url,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'La URL es requerida';
                  }
                  if (!ServerConfig.isValidUrl(value)) {
                    return 'URL no valida. Debe comenzar con http:// o https://';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),

              // Namespace de la API
              Text(
                'Namespace de la API',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _namespaceController,
                decoration: const InputDecoration(
                  labelText: 'Namespace',
                  hintText: '/wp-json/chat-ia-mobile/v1',
                  prefixIcon: Icon(Icons.api),
                  helperText: 'Normalmente no es necesario cambiar esto',
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'El namespace es requerido';
                  }
                  if (!value.startsWith('/')) {
                    return 'Debe comenzar con /';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 24),

              // URL completa (preview)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'URL completa de la API:',
                        style: Theme.of(context).textTheme.labelMedium,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${_urlController.text}${_namespaceController.text}',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              fontFamily: 'monospace',
                              color: colorScheme.primary,
                            ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // Boton de prueba
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: _isTesting ? null : _testConnection,
                  icon: _isTesting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.wifi_find),
                  label: Text(_isTesting ? 'Probando...' : 'Probar conexion'),
                ),
              ),

              // Resultado del test
              if (_testResult != null) ...[
                const SizedBox(height: 16),
                Card(
                  color: _testSuccess == true
                      ? Colors.green.withOpacity(0.1)
                      : Colors.red.withOpacity(0.1),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Icon(
                          _testSuccess == true
                              ? Icons.check_circle
                              : Icons.error,
                          color: _testSuccess == true
                              ? Colors.green
                              : Colors.red,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _testResult!,
                            style: TextStyle(
                              color: _testSuccess == true
                                  ? Colors.green.shade700
                                  : Colors.red.shade700,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],

              const SizedBox(height: 32),

              // Boton guardar
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _isLoading ? null : _saveConfig,
                  icon: _isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.save),
                  label: Text(_isLoading ? 'Guardando...' : 'Guardar configuracion'),
                ),
              ),

              const SizedBox(height: 16),

              // Nota
              Card(
                color: colorScheme.surfaceContainerHighest,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Row(
                    children: [
                      Icon(
                        Icons.warning_amber,
                        color: colorScheme.onSurfaceVariant,
                        size: 20,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Despues de guardar, reinicia la aplicacion para que los cambios surtan efecto.',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: colorScheme.onSurfaceVariant,
                              ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Pantalla de escaneo QR
class _QRScannerScreen extends StatefulWidget {
  const _QRScannerScreen();

  @override
  State<_QRScannerScreen> createState() => _QRScannerScreenState();
}

class _QRScannerScreenState extends State<_QRScannerScreen> {
  final MobileScannerController _controller = MobileScannerController();
  bool _hasScanned = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_hasScanned) return;

    final List<Barcode> barcodes = capture.barcodes;
    for (final barcode in barcodes) {
      final String? code = barcode.rawValue;
      if (code != null && code.isNotEmpty) {
        _hasScanned = true;
        Navigator.pop(context, code);
        return;
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Escanear QR'),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
      ),
      body: Stack(
        children: [
          MobileScanner(
            controller: _controller,
            onDetect: _onDetect,
          ),
          // Overlay con instrucciones
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.transparent,
                    Colors.black.withOpacity(0.8),
                  ],
                ),
              ),
              child: const Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.qr_code,
                    size: 48,
                    color: Colors.white,
                  ),
                  SizedBox(height: 16),
                  Text(
                    'Escanea el código QR',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Encontrarás el QR en el panel de WordPress:\nChat IA → Configuración → Apps Móviles',
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: 24),
                ],
              ),
            ),
          ),
          // Marco de escaneo
          Center(
            child: Container(
              width: 250,
              height: 250,
              decoration: BoxDecoration(
                border: Border.all(
                  color: Colors.white.withOpacity(0.5),
                  width: 2,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
