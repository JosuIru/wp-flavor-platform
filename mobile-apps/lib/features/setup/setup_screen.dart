import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../core/config/server_config.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart' show apiClientProvider;
import '../../core/widgets/common_widgets.dart';
import '../../core/widgets/flavor_snackbar.dart';
import '../../core/widgets/flavor_state_widgets.dart';
import '../../core/utils/haptics.dart';
import 'setup_directory_tab.dart';
import 'dart:convert';

/// Pantalla de configuracion inicial del servidor
/// Se muestra la primera vez que se abre la app o cuando no hay servidor configurado
class SetupScreen extends ConsumerStatefulWidget {
  final VoidCallback onSetupComplete;
  final bool isFromSettings; // Si viene de ajustes, mostrar boton cancelar
  final bool isAdminApp; // Si es la app de admin, requiere token de seguridad

  const SetupScreen({
    super.key,
    required this.onSetupComplete,
    this.isFromSettings = false,
    this.isAdminApp = false,
  });

  @override
  ConsumerState<SetupScreen> createState() => _SetupScreenState();
}

class _SetupScreenState extends ConsumerState<SetupScreen>
    with SingleTickerProviderStateMixin {
  TabController? _tabController;
  final _urlController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  bool _showManualUrl = false;
  String? _error;
  String? _successMessage;
  bool _hasUnsavedChanges = false;
  final String _initialUrl = '';
  AppLocalizations get i18n => AppLocalizations.of(context);

  /// Si hay URL preconfigurada, mostramos tabs (Directorio + QR)
  bool get _showDirectoryTab => ServerConfig.bootstrapServerUrl.isNotEmpty;

  @override
  void initState() {
    super.initState();
    if (_showDirectoryTab) {
      _tabController = TabController(length: 2, vsync: this);
    }
    _urlController.addListener(_onUrlChanged);
  }

  void _onUrlChanged() {
    final hasChanges = _urlController.text.trim() != _initialUrl;
    if (hasChanges != _hasUnsavedChanges) {
      setState(() {
        _hasUnsavedChanges = hasChanges;
      });
    }
  }

  @override
  void dispose() {
    _tabController?.dispose();
    _urlController.removeListener(_onUrlChanged);
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _validateAndSave() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _error = null;
    });

    String url = _urlController.text.trim();

    // Anadir https:// si no tiene protocolo
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://$url';
    }

    // Quitar / final
    if (url.endsWith('/')) {
      url = url.substring(0, url.length - 1);
    }

    try {
      // Descubrir sistema/API del sitio antes de fijar namespace
      final discoveryResponse = await ApiClient.discoverSiteAt(url);
      if (!discoveryResponse.success || discoveryResponse.data == null) {
        Haptics.error();
        if (!mounted) return;
        setState(() {
          _isLoading = false;
          _error = discoveryResponse.error ?? i18n.setupConnectionFailed;
        });
        return;
      }

      final apiNamespace = await ApiClient.detectPreferredApiNamespace(
        url,
        discoveryData: discoveryResponse.data,
      );
      final apiClient = ApiClient(baseUrl: '$url$apiNamespace');
      final response = await apiClient.getSiteInfo();

      if (response.success) {
        // Guardar configuracion
        await ServerConfig.setServerUrl(url);
        await ServerConfig.setApiNamespace(apiNamespace);

        // IMPORTANTE: Actualizar la URL del apiClient existente
        final fullApiUrl = '$url$apiNamespace';
        ref.read(apiClientProvider).updateBaseUrl(fullApiUrl);
        debugPrint('ApiClient actualizado con URL: $fullApiUrl');

        if (!mounted) return;
        setState(() {
          _isLoading = false;
          _successMessage = i18n.setupConnectedSuccess;
        });

        // Feedback haptico de exito
        Haptics.success();

        // Esperar un momento para mostrar el mensaje
        await Future.delayed(const Duration(milliseconds: 800));

        if (!mounted) return;
        widget.onSetupComplete();
      } else {
        Haptics.error();
        if (!mounted) return;
        setState(() {
          _isLoading = false;
          _error = i18n.setupConnectionFailed;
        });
      }
    } catch (e) {
      Haptics.error();
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = i18n.setupConnectionError(e.toString());
      });
    }
  }

  void _onQRScanned(String? code) async {
    if (code == null) return;

    debugPrint('QR escaneado: $code');

    try {
      // Intentar parsear como JSON (formato completo de configuracion)
      final Map<String, dynamic> config = json.decode(code);

      if (config.containsKey('url')) {
        // Desescapar caracteres JSON (\/ -> /)
        String url = config['url'].toString().replaceAll(r'\/', '/');
        debugPrint('URL extraida del QR: $url');

        final qrType = config['type'] as String? ?? 'client';
        final token = config['token'] as String?;
        final siteName = config['name'] ?? i18n.setupConfiguredSiteNameDefault;

        // Validacion para app Admin
        if (widget.isAdminApp) {
          if (qrType != 'admin') {
            FlavorSnackbar.showInfo(context, i18n.setupQrClientAppWarning);
            return;
          }

          if (token == null || token.isEmpty) {
            FlavorSnackbar.showError(context, i18n.setupAdminQrMissingToken);
            return;
          }

          // Validar token con el servidor
          setState(() {
            _isLoading = true;
            _error = null;
          });

          final apiUrl = '$url${ServerConfig.defaultApiNamespace}';
          final apiClient = ApiClient(baseUrl: apiUrl);

          try {
            final validateResponse =
                await apiClient.validateAdminSiteToken(token);

            if (!validateResponse.success) {
              setState(() {
                _isLoading = false;
                _error = i18n.setupAdminTokenInvalid;
              });
              return;
            }

            debugPrint('Token de admin validado correctamente');
          } catch (e) {
            setState(() {
              _isLoading = false;
              _error = i18n.setupAdminTokenValidateError(e.toString());
            });
            return;
          }

          setState(() {
            _isLoading = false;
          });
        }

        _urlController.text = url;

        // Mostrar informacion adicional si existe
        if (!mounted) return;
        FlavorSnackbar.showSuccess(context, i18n.setupConfigDetected(siteName));

        // Mostrar URL manual y auto-validar
        setState(() => _showManualUrl = true);
        _validateAndSave();
      }
    } catch (e) {
      // Si no es JSON, intentar como URL directa
      if (code.contains('.') || code.startsWith('http')) {
        // Para admin app, no permitir URL directa sin token
        if (widget.isAdminApp) {
          if (!mounted) return;
          FlavorSnackbar.showInfo(context, i18n.setupAdminRequiresSecureQr);
          return;
        }

        _urlController.text = code;
        if (!mounted) return;
        FlavorSnackbar.showSuccess(context, i18n.setupUrlDetected);

        // Mostrar URL manual y auto-validar
        setState(() => _showManualUrl = true);
        _validateAndSave();
      } else {
        if (!mounted) return;
        FlavorSnackbar.showError(context, i18n.setupInvalidQr);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: !_hasUnsavedChanges,
      onPopInvokedWithResult: (didPop, result) async {
        if (didPop) return;
        final shouldDiscard = await showDiscardChangesDialog(context);
        if (shouldDiscard && context.mounted) {
          Navigator.of(context).pop();
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(i18n.setupTitle),
          leading: widget.isFromSettings
              ? IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.of(context).pop(),
                )
              : null,
          bottom: _showDirectoryTab
              ? TabBar(
                  controller: _tabController,
                  tabs: const [
                    Tab(
                      icon: Icon(Icons.explore),
                      text: 'Directorio',
                    ),
                    Tab(
                      icon: Icon(Icons.qr_code_scanner),
                      text: 'Escanear QR',
                    ),
                  ],
                )
              : null,
        ),
        body: _showDirectoryTab ? _buildWithTabs() : _buildWithoutTabs(),
      ),
    );
  }

  /// Layout con tabs (cuando hay URL preconfigurada)
  Widget _buildWithTabs() {
    return Column(
      children: [
        Expanded(
          child: TabBarView(
            controller: _tabController,
            children: [
              // Tab 0: Directorio
              SetupDirectoryTab(
                onSetupComplete: widget.onSetupComplete,
              ),
              // Tab 1: Scanner QR
              _buildQrScannerTab(),
            ],
          ),
        ),
        // Enlace inferior para URL manual
        if (!_showManualUrl)
          SafeArea(
            child: TextButton(
              onPressed: () => setState(() => _showManualUrl = true),
              child: Text(
                '¿Tienes la URL? Toca aqui',
                style: TextStyle(
                  color: Theme.of(context).colorScheme.primary,
                ),
              ),
            ),
          ),
        // Formulario URL manual (colapsado por defecto)
        if (_showManualUrl) _buildManualUrlSection(),
      ],
    );
  }

  /// Layout sin tabs (app generica sin URL preconfigurada)
  Widget _buildWithoutTabs() {
    return Column(
      children: [
        Expanded(child: _buildQrScannerTab()),
        // Enlace inferior para URL manual
        if (!_showManualUrl)
          SafeArea(
            child: TextButton(
              onPressed: () => setState(() => _showManualUrl = true),
              child: Text(
                '¿Tienes la URL? Toca aqui',
                style: TextStyle(
                  color: Theme.of(context).colorScheme.primary,
                ),
              ),
            ),
          ),
        // Formulario URL manual (colapsado por defecto)
        if (_showManualUrl) _buildManualUrlSection(),
      ],
    );
  }

  Widget _buildQrScannerTab() {
    return Stack(
      children: [
        MobileScanner(
          onDetect: (capture) {
            final List<Barcode> barcodes = capture.barcodes;
            for (final barcode in barcodes) {
              if (barcode.rawValue != null) {
                _onQRScanned(barcode.rawValue);
                break;
              }
            }
          },
        ),
        // Overlay con instrucciones
        Positioned(
          bottom: 100,
          left: 20,
          right: 20,
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Text(
                i18n.setupQrScanHint,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ),
          ),
        ),
        // Card de ayuda en la parte superior
        Positioned(
          top: 20,
          left: 20,
          right: 20,
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  Icon(
                    Icons.help_outline,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      i18n.setupHowToGetQr,
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildManualUrlSection() {
    return SafeArea(
      child: Container(
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
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Introducir URL manualmente',
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => setState(() => _showManualUrl = false),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              TextFormField(
                controller: _urlController,
                decoration: InputDecoration(
                  labelText: i18n.siteUrlLabel,
                  hintText: i18n.siteUrlExampleHint,
                  prefixIcon: const Icon(Icons.language),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                keyboardType: TextInputType.url,
                textInputAction: TextInputAction.done,
                autocorrect: false,
                onFieldSubmitted: (_) => _validateAndSave(),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return i18n.setupUrlRequired;
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),

              // Mensaje de error
              if (_error != null)
                Container(
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 12),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.errorContainer,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        Icons.error_outline,
                        color: Theme.of(context).colorScheme.error,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _error!,
                          style: TextStyle(
                            color: Theme.of(context).colorScheme.error,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

              // Mensaje de exito
              if (_successMessage != null)
                Container(
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 12),
                  decoration: BoxDecoration(
                    color: Colors.green.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.check_circle, color: Colors.green),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _successMessage!,
                          style: const TextStyle(color: Colors.green),
                        ),
                      ),
                    ],
                  ),
                ),

              // Boton conectar
              FilledButton.icon(
                onPressed: _isLoading ? null : _validateAndSave,
                icon: _isLoading
                    ? const FlavorInlineSpinner()
                    : const Icon(Icons.check),
                label:
                    Text(_isLoading ? i18n.setupConnecting : i18n.setupConnect),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Widget para verificar si hay configuracion guardada
class SetupChecker extends ConsumerWidget {
  final Widget child;
  final Widget Function(VoidCallback onComplete) setupBuilder;

  const SetupChecker({
    super.key,
    required this.child,
    required this.setupBuilder,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return FutureBuilder<bool>(
      future: _checkIfConfigured(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Scaffold(body: FlavorLoadingState());
        }

        final isConfigured = snapshot.data ?? false;

        if (!isConfigured) {
          return setupBuilder(() {
            // Forzar rebuild
            (context as Element).markNeedsBuild();
          });
        }

        return child;
      },
    );
  }

  Future<bool> _checkIfConfigured() async {
    try {
      final serverUrl = await ServerConfig.getServerUrl();
      // Si tiene la URL por defecto, consideramos que no esta configurado
      // para otros usuarios que no sean Basabere
      return serverUrl != ServerConfig.defaultServerUrl ||
          serverUrl ==
              'https://basabere.com'; // Para Basabere si esta configurado
    } catch (e) {
      return false;
    }
  }
}
