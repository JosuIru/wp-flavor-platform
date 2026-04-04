import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../../core/config/server_config.dart';
import '../../../core/api/api_client.dart';
import '../../../core/widgets/flavor_snackbar.dart';

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
  Future<List<SavedBusiness>>? _businessesFuture;
  AppLocalizations get i18n => AppLocalizations.of(context);

  @override
  void initState() {
    super.initState();
    _loadCurrentConfig();
    _businessesFuture = _loadBusinesses();
  }

  Future<void> _loadCurrentConfig() async {
    final serverUrl = await ServerConfig.getServerUrl();
    final namespace = await ServerConfig.getApiNamespace();
    setState(() {
      _urlController.text = serverUrl;
      _namespaceController.text = namespace;
    });
  }

  Future<List<SavedBusiness>> _loadBusinesses() async {
    final businesses = await ServerConfig.getBusinesses();
    businesses.sort((a, b) {
      final aTime = DateTime.tryParse(a.lastUsedAt ?? '') ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bTime = DateTime.tryParse(b.lastUsedAt ?? '') ?? DateTime.fromMillisecondsSinceEpoch(0);
      return bTime.compareTo(aTime);
    });
    return businesses;
  }

  void _refreshBusinesses() {
    setState(() {
      _businessesFuture = _loadBusinesses();
    });
  }

  Future<void> _useBusiness(SavedBusiness business) async {
    setState(() {
      _isLoading = true;
      _testResult = null;
      _testSuccess = null;
    });

    try {
      final fullUrl = '${business.serverUrl}${business.apiNamespace}';
      final testClient = ApiClient(baseUrl: fullUrl);
      final response = await testClient.getBusinessInfo();

      if (!response.success) {
        setState(() {
          _isLoading = false;
          _testSuccess = false;
          _testResult = response.error ?? i18n.serverConfigConnectionError;
        });
        return;
      }

      final siteName = response.data?['name']?.toString() ??
          response.data?['business']?['name']?.toString();
      final updatedBusiness = business.name.isNotEmpty || siteName == null
          ? business
          : business.copyWith(name: siteName);

      await ref.read(serverConfigProvider.notifier).setCurrentBusiness(updatedBusiness);
      await _loadCurrentConfig();
      _refreshBusinesses();

      if (mounted) {
        FlavorSnackbar.showSuccess(
          context,
          i18n.serverConfigConnectedTo(
            business.name.isNotEmpty ? business.name : business.serverUrl,
          ),
        );
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _testSuccess = false;
        _testResult = i18n.serverConfigError(e.toString());
      });
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
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
          _testResult = i18n.serverConfigConnectionSuccess;
        } else {
          _testSuccess = false;
          _testResult = response.error ?? i18n.serverConfigConnectionError;
        }
      });
    } catch (e) {
      setState(() {
        _isTesting = false;
        _testSuccess = false;
        _testResult = i18n.serverConfigError(e.toString());
      });
    }
  }

  Future<void> _saveConfig() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final url = _urlController.text.trim();
      final namespace = _namespaceController.text.trim();

      await ref.read(serverConfigProvider.notifier).setCurrentBusiness(
            SavedBusiness(
              id: SavedBusiness.makeId(url, namespace, 'admin'),
              name: '',
              serverUrl: url,
              apiNamespace: namespace,
              type: 'admin',
            ),
          );
      await _loadCurrentConfig();
      _refreshBusinesses();

      if (mounted) {
        FlavorSnackbar.showSuccess(
          context,
          i18n.serverConfigSavedRestartNotice,
        );
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(
          context,
          i18n.serverConfigSaveError(e.toString()),
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
            final urlMatch = RegExp(r'"server_url"\s*:\s*"([^"]+)"').firstMatch(jsonStr) ??
                RegExp(r'"url"\s*:\s*"([^"]+)"').firstMatch(jsonStr) ??
                RegExp(r'"api_url"\s*:\s*"([^"]+)"').firstMatch(jsonStr);
            if (urlMatch != null) {
              serverUrl = urlMatch.group(1)!;
            }
          }
        } catch (e) {
          // Si falla el parse, usar el resultado como URL
        }
      }

      // Limpiar la URL
      serverUrl = serverUrl.trim().replaceAll(r'\/', '/');
      if (serverUrl.endsWith('/')) {
        serverUrl = serverUrl.substring(0, serverUrl.length - 1);
      }

      // Si viene con /wp-json/... dejar solo el dominio base
      final wpJsonIndex = serverUrl.indexOf('/wp-json');
      if (wpJsonIndex > 0) {
        serverUrl = serverUrl.substring(0, wpJsonIndex);
      }

      // Asegurar esquema
      if (!serverUrl.startsWith('http://') && !serverUrl.startsWith('https://')) {
        if (serverUrl.contains('.') && !serverUrl.contains(' ')) {
          serverUrl = 'https://$serverUrl';
        }
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
        FlavorSnackbar.showError(
          context,
          i18n.serverConfigInvalidQr(serverUrl),
        );
      }
    }
  }

  Future<void> _resetToDefaults() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.serverConfigResetTitle),
        content: Text(i18n.serverConfigResetDescription),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.serverConfigResetAction),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await ServerConfig.resetToDefaults();
      await _loadCurrentConfig();
      ref.read(serverConfigProvider.notifier).resetToDefaults();

      if (mounted) {
        FlavorSnackbar.showInfo(
          context,
          i18n.serverConfigResetDone,
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.serverConfigTitle),
        actions: [
          IconButton(
            icon: const Icon(Icons.restore),
            tooltip: i18n.serverConfigResetTooltip,
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
                                i18n.serverConfigScanQrTitle,
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                  color: colorScheme.onPrimaryContainer,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                i18n.serverConfigScanQrDescription,
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

              FutureBuilder<List<SavedBusiness>>(
                future: _businessesFuture,
                builder: (context, snapshot) {
                  final businesses = snapshot.data ?? [];
                  if (businesses.isEmpty) {
                    return const SizedBox.shrink();
                  }

                  final current = ref.watch(serverConfigProvider);

                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        i18n.serverConfigSavedBusinesses,
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 8),
                      ...businesses.map((business) {
                        final isCurrent = business.serverUrl == current.serverUrl &&
                            business.apiNamespace == current.apiNamespace;
                        return Card(
                          margin: const EdgeInsets.symmetric(vertical: 6),
                          child: ListTile(
                            title: Text(
                              business.name.isNotEmpty ? business.name : business.serverUrl,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            subtitle: Text(business.serverUrl),
                            trailing: isCurrent
                                ? Text(i18n.commonCurrent)
                                : TextButton(
                                    onPressed: _isLoading ? null : () => _useBusiness(business),
                                    child: Text(i18n.commonUse),
                                  ),
                          ),
                        );
                      }),
                      const SizedBox(height: 16),
                    ],
                  );
                },
              ),

              // Divider con texto
              Row(
                children: [
                  const Expanded(child: Divider()),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      i18n.serverConfigManualSeparator,
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
                i18n.serverConfigServerUrlTitle,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _urlController,
                decoration: InputDecoration(
                  labelText: i18n.serverConfigServerUrlLabel,
                  hintText: i18n.serverConfigServerUrlHint,
                  prefixIcon: const Icon(Icons.language),
                  helperText: i18n.serverConfigServerUrlHelper,
                ),
                keyboardType: TextInputType.url,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return i18n.serverConfigServerUrlRequired;
                  }
                  if (!ServerConfig.isValidUrl(value)) {
                    return i18n.serverConfigServerUrlInvalid;
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),

              // Namespace de la API
              Text(
                i18n.serverConfigNamespaceTitle,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _namespaceController,
                decoration: InputDecoration(
                  labelText: i18n.serverConfigNamespaceLabel,
                  hintText: i18n.serverConfigNamespaceHint,
                  prefixIcon: const Icon(Icons.api),
                  helperText: i18n.serverConfigNamespaceHelper,
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return i18n.serverConfigNamespaceRequired;
                  }
                  if (!value.startsWith('/')) {
                    return i18n.serverConfigNamespaceMustStartSlash;
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
                        i18n.serverConfigApiUrlPreviewTitle,
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
                  label: Text(
                    _isTesting
                        ? i18n.serverConfigTestingLabel
                        : i18n.serverConfigTestButton,
                  ),
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
                  label: Text(
                    _isLoading
                        ? i18n.serverConfigSavingLabel
                        : i18n.serverConfigSaveButton,
                  ),
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
                          i18n.serverConfigRestartNote,
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
    final i18n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.serverConfigQrScanTitle),
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
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.qr_code,
                    size: 48,
                    color: Colors.white,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    i18n.serverConfigQrScanInstruction,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    i18n.serverConfigQrScanHelp,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),
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
