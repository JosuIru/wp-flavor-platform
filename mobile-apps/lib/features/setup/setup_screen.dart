import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../core/config/server_config.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart' show apiClientProvider;
import '../../core/widgets/common_widgets.dart';
import '../../core/utils/haptics.dart';
import 'dart:convert';

/// Pantalla de configuración inicial del servidor
/// Se muestra la primera vez que se abre la app o cuando no hay servidor configurado
class SetupScreen extends ConsumerStatefulWidget {
  final VoidCallback onSetupComplete;
  final bool isFromSettings; // Si viene de ajustes, mostrar botón cancelar
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

class _SetupScreenState extends ConsumerState<SetupScreen> {
  final _urlController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;
  bool _showScanner = false;
  String? _error;
  String? _successMessage;
  bool _hasUnsavedChanges = false;
  String _initialUrl = '';
  AppLocalizations get i18n => AppLocalizations.of(context)!;

  @override
  void initState() {
    super.initState();
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

    // Añadir https:// si no tiene protocolo
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://$url';
    }

    // Quitar / final
    if (url.endsWith('/')) {
      url = url.substring(0, url.length - 1);
    }

    try {
      // Verificar que el servidor responde
      final apiClient = ApiClient(baseUrl: '$url${ServerConfig.defaultApiNamespace}');
      final response = await apiClient.getSiteInfo();

      if (response.success) {
        // Guardar configuración
        await ServerConfig.setServerUrl(url);

        // IMPORTANTE: Actualizar la URL del apiClient existente
        final fullApiUrl = '$url${ServerConfig.defaultApiNamespace}';
        ref.read(apiClientProvider).updateBaseUrl(fullApiUrl);
        debugPrint('ApiClient actualizado con URL: $fullApiUrl');

        setState(() {
          _isLoading = false;
          _successMessage = i18n.setupConnectedSuccess;
        });

        // Feedback haptico de exito
        Haptics.success();

        // Esperar un momento para mostrar el mensaje
        await Future.delayed(const Duration(milliseconds: 800));

        widget.onSetupComplete();
      } else {
        Haptics.error();
        setState(() {
          _isLoading = false;
          _error = i18n.setupConnectionFailed;
        });
      }
    } catch (e) {
      Haptics.error();
      setState(() {
        _isLoading = false;
        _error = i18n.setupConnectionError(e.toString());
      });
    }
  }

  void _onQRScanned(String? code) async {
    if (code == null) return;

    setState(() {
      _showScanner = false;
    });

    debugPrint('QR escaneado: $code');

    try {
      // Intentar parsear como JSON (formato completo de configuración)
      final Map<String, dynamic> config = json.decode(code);

      if (config.containsKey('url')) {
        // Desescapar caracteres JSON (\/ -> /)
        String url = config['url'].toString().replaceAll(r'\/', '/');
        debugPrint('URL extraída del QR: $url');

        final qrType = config['type'] as String? ?? 'client';
        final token = config['token'] as String?;
        final siteName = config['name'] ?? i18n.setupConfiguredSiteNameDefault;

        // Validación para app Admin
        if (widget.isAdminApp) {
          if (qrType != 'admin') {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(i18n.setupQrClientAppWarning),
                backgroundColor: Colors.orange,
                duration: Duration(seconds: 4),
              ),
            );
            return;
          }

          if (token == null || token.isEmpty) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(i18n.setupAdminQrMissingToken),
                backgroundColor: Colors.red,
              ),
            );
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
            final validateResponse = await apiClient.validateAdminSiteToken(token);

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

        // Mostrar información adicional si existe
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.setupConfigDetected(siteName)),
            backgroundColor: Colors.green,
          ),
        );

        // Auto-validar si el QR tiene formato completo
        _validateAndSave();
      }
    } catch (e) {
      // Si no es JSON, intentar como URL directa
      if (code.contains('.') || code.startsWith('http')) {
        // Para admin app, no permitir URL directa sin token
        if (widget.isAdminApp) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(i18n.setupAdminRequiresSecureQr),
              backgroundColor: Colors.orange,
              duration: Duration(seconds: 4),
            ),
          );
          return;
        }

        _urlController.text = code;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.setupUrlDetected),
            backgroundColor: Colors.green,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.setupInvalidQr),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_showScanner) {
      return _buildScanner(context);
    }

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
        body: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 40),

                  // Logo/Icono
                  Icon(
                    Icons.settings_applications,
                    size: 80,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  const SizedBox(height: 24),

                  // Titulo
                  Text(
                    i18n.setupTitle,
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),

                  // Subtitulo
                  Text(
                    i18n.setupSubtitle,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 40),

                  // Campo de URL
                  TextFormField(
                    controller: _urlController,
                    decoration: InputDecoration(
                      labelText: i18n.siteUrlLabel,
                      hintText: i18n.siteUrlExampleHint,
                      prefixIcon: const Icon(Icons.language),
                      prefixText: _urlController.text.isEmpty ? 'https://' : null,
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
                  const SizedBox(height: 16),

                  // Mensaje de error
                  if (_error != null)
                    Container(
                      padding: const EdgeInsets.all(12),
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

                  const SizedBox(height: 24),

                  // Boton conectar
                  FilledButton.icon(
                    onPressed: _isLoading ? null : _validateAndSave,
                    icon: _isLoading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.check),
                    label: Text(_isLoading ? i18n.setupConnecting : i18n.setupConnect),
                    style: FilledButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Boton escanear QR
                  OutlinedButton.icon(
                    onPressed: _isLoading ? null : () {
                      setState(() => _showScanner = true);
                    },
                    icon: const Icon(Icons.qr_code_scanner),
                    label: Text(i18n.setupScanQr),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                  ),

                  // Boton cancelar (solo si viene de ajustes)
                  if (widget.isFromSettings) ...[
                    const SizedBox(height: 12),
                    TextButton(
                      onPressed: () => Navigator.of(context).pop(),
                      child: Text(i18n.commonCancel),
                    ),
                  ],

                  const SizedBox(height: 40),

                  // Ayuda
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(
                                Icons.help_outline,
                                color: Theme.of(context).colorScheme.primary,
                              ),
                              const SizedBox(width: 8),
                              Text(
                                i18n.setupHowToGetQr,
                                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(
                            i18n.setupQrSteps,
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildScanner(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.setupQrScanTitle),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => setState(() => _showScanner = false),
        ),
      ),
      body: Stack(
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
        ],
      ),
    );
  }
}

/// Widget para verificar si hay configuración guardada
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
          return const Scaffold(
            body: Center(child: CircularProgressIndicator()),
          );
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
      // Si tiene la URL por defecto, consideramos que no está configurado
      // para otros usuarios que no sean Basabere
      return serverUrl != ServerConfig.defaultServerUrl ||
             serverUrl == 'https://basabere.com'; // Para Basabere sí está configurado
    } catch (e) {
      return false;
    }
  }
}
