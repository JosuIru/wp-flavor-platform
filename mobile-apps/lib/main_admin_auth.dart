part of 'main_admin.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;

  @override
  void initState() {
    super.initState();
    Future.delayed(const Duration(seconds: 15), () {
      if (mounted) {
        final authState = ref.read(authStateProvider);
        if (authState.isLoading) {
          ref.read(authStateProvider.notifier).forceStopLoading();
        }
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.admin_panel_settings,
              size: 80,
              color: Theme.of(context).colorScheme.primary,
            ),
            const SizedBox(height: 24),
            const CircularProgressIndicator(),
            const SizedBox(height: 16),
            Text(
              i18n.loadingConnecting,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Theme.of(context)
                        .colorScheme
                        .onSurface
                        .withOpacity(0.6),
                  ),
            ),
          ],
        ),
      ),
    );
  }
}

class AdminSetupScreen extends ConsumerStatefulWidget {
  const AdminSetupScreen({super.key});

  @override
  ConsumerState<AdminSetupScreen> createState() => _AdminSetupScreenState();
}

class _AdminSetupScreenState extends ConsumerState<AdminSetupScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  final _urlController = TextEditingController();
  bool _isLoading = false;
  String? _error;

  @override
  void dispose() {
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _scanQR() async {
    final result = await Navigator.push<String>(
      context,
      MaterialPageRoute(
        builder: (context) => const _QRScannerSetupScreen(),
      ),
    );

    if (result != null && result.isNotEmpty && mounted) {
      final shouldProceed = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(i18n.adminSetupQrScannedTitle),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  i18n.adminSetupQrContentLabel,
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade200,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: SelectableText(
                    result,
                    style:
                        const TextStyle(fontFamily: 'monospace', fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(i18n.commonCancel),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(i18n.adminSetupConnect),
            ),
          ],
        ),
      );

      if (shouldProceed == true) {
        await _processServerUrl(result);
      }
    }
  }

  Future<void> _processServerUrl(String url) async {
    String serverUrl = url.trim();
    String? adminToken;

    Logger.d('QR escaneado: $serverUrl', tag: 'AdminSetup');

    if (serverUrl.contains('{') && serverUrl.contains('}')) {
      try {
        final jsonData = json.decode(serverUrl) as Map<String, dynamic>;
        final qrType = jsonData['type'] as String? ?? 'client';
        adminToken = jsonData['token'] as String?;

        if (qrType != 'admin') {
          setState(() {
            _error = i18n.adminSetupQrClientOnly;
          });
          return;
        }

        if (adminToken == null || adminToken.isEmpty) {
          setState(() {
            _error = i18n.adminSetupQrInvalid;
          });
          return;
        }

        serverUrl = (jsonData['url'] as String? ?? '').replaceAll(r'\/', '/');
        Logger.d(
            'URL extraída del JSON: $serverUrl, token presente: ${adminToken.isNotEmpty}',
            tag: 'AdminSetup');
      } catch (e) {
        Logger.e('Error parseando JSON: $e', tag: 'AdminSetup', error: e);
        final urlMatch = RegExp(r'"url"\s*:\s*"([^"]+)"').firstMatch(serverUrl);
        if (urlMatch != null) {
          serverUrl = urlMatch.group(1)!.replaceAll(r'\/', '/');
        }
      }
    } else {
      setState(() {
        _error = i18n.adminSetupRequiresSecureQr;
      });
      return;
    }

    serverUrl = serverUrl.trim();
    if (serverUrl.endsWith('/')) {
      serverUrl = serverUrl.substring(0, serverUrl.length - 1);
    }

    final wpJsonIndex = serverUrl.indexOf('/wp-json');
    if (wpJsonIndex > 0) {
      serverUrl = serverUrl.substring(0, wpJsonIndex);
    }

    if (!serverUrl.startsWith('http://') && !serverUrl.startsWith('https://')) {
      if (serverUrl.contains('.') && !serverUrl.contains(' ')) {
        serverUrl = 'https://$serverUrl';
      } else {
        setState(() {
          _error = i18n.adminSetupQrNotRecognized(url);
        });
        return;
      }
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final discoveryResponse = await ApiClient.discoverSiteAt(serverUrl);
      if (!discoveryResponse.success || discoveryResponse.data == null) {
        setState(() {
          _error = i18n.adminSetupServerError(
            discoveryResponse.error ?? i18n.commonInvalidResponse,
            '$serverUrl/wp-json/app-discovery/v1/info',
          );
        });
        return;
      }

      final apiNamespace = await ApiClient.detectPreferredApiNamespace(
        serverUrl,
        discoveryData: discoveryResponse.data,
      );
      final fullUrl = '$serverUrl$apiNamespace';
      Logger.d('Conectando a: $fullUrl', tag: 'AdminSetup');

      final testClient = ApiClient(baseUrl: fullUrl);
      final response = await testClient.getBusinessInfo().timeout(
            const Duration(seconds: 15),
            onTimeout: () => ApiResponse<Map<String, dynamic>>.error(
              i18n.adminSetupTimeoutServer,
            ),
          );

      Logger.d(
          'Respuesta: success=${response.success}, error=${response.error}',
          tag: 'AdminSetup');

      if (response.success) {
        if (adminToken != null && adminToken.isNotEmpty) {
          Logger.d('Validando token de admin...', tag: 'AdminSetup');
          final tokenResponse =
              await testClient.validateAdminSiteToken(adminToken).timeout(
                    const Duration(seconds: 10),
                    onTimeout: () => ApiResponse<Map<String, dynamic>>.error(
                      i18n.adminSetupTimeoutToken,
                    ),
                  );

          if (!tokenResponse.success) {
            setState(() {
              _isLoading = false;
              _error = i18n.adminSetupInvalidToken;
            });
            return;
          }
          Logger.i('Token de admin validado correctamente', tag: 'AdminSetup');
        }

        await ServerConfig.setServerUrl(serverUrl);
        await ServerConfig.setApiNamespace(apiNamespace);

        ref.read(apiClientProvider).updateBaseUrl(fullUrl);
        Logger.d('ApiClient actualizado con URL: $fullUrl', tag: 'AdminSetup');

        Logger.i('Sincronizando layouts y tema con servidor: $serverUrl',
            tag: 'AdminApp');
        final syncResult =
            await ref.read(syncProvider.notifier).syncWithSite(serverUrl);
        if (!syncResult.success) {
          Logger.w(
            'Sincronizacion inicial incompleta: ${syncResult.error}',
            tag: 'AdminApp',
          );
        }

        ref.invalidate(serverConfiguredProvider);
        ref.invalidate(authStateProvider);

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(i18n.adminSetupServerConfiguredSuccess),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        setState(() {
          _error = i18n.adminSetupServerError(
            response.error ?? i18n.commonInvalidResponse,
            fullUrl,
          );
        });
      }
    } catch (e, stack) {
      Logger.e('Error de conexión: $e', tag: 'AdminSetup', error: e);
      Logger.e('Stack: $stack', tag: 'AdminSetup');
      setState(() {
        _error = i18n.adminSetupConnectionError(e.toString(), serverUrl);
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primaryContainer,
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.admin_panel_settings,
                  size: 64,
                  color: Theme.of(context).colorScheme.primary,
                ),
              ),
              const SizedBox(height: 32),
              Text(
                i18n.adminSetupTitle,
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),
              Text(
                i18n.adminSetupSubtitle,
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: Theme.of(context).colorScheme.outline,
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 48),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _isLoading ? null : _scanQR,
                  icon: const Icon(Icons.qr_code_scanner, size: 28),
                  label: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      i18n.adminSetupScanQrButton,
                      style: const TextStyle(fontSize: 16),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                i18n.adminSetupFindQrHint,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.outline,
                    ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),
              Row(
                children: [
                  const Expanded(child: Divider()),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      i18n.adminSetupOrEnterUrl,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.outline,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  const Expanded(child: Divider()),
                ],
              ),
              const SizedBox(height: 24),
              TextField(
                controller: _urlController,
                decoration: InputDecoration(
                  labelText: i18n.adminSetupServerUrlLabel,
                  hintText: i18n.adminSetupServerUrlHint,
                  prefixIcon: const Icon(Icons.language),
                  errorText: _error,
                  suffixIcon: _isLoading
                      ? const Padding(
                          padding: EdgeInsets.all(12),
                          child: SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                        )
                      : IconButton(
                          icon: const Icon(Icons.arrow_forward),
                          onPressed: () {
                            if (_urlController.text.isNotEmpty) {
                              _processServerUrl(_urlController.text);
                            }
                          },
                        ),
                ),
                keyboardType: TextInputType.url,
                onSubmitted: (value) {
                  if (value.isNotEmpty) {
                    _processServerUrl(value);
                  }
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QRScannerSetupScreen extends StatefulWidget {
  const _QRScannerSetupScreen();

  @override
  State<_QRScannerSetupScreen> createState() => _QRScannerSetupScreenState();
}

class _QRScannerSetupScreenState extends State<_QRScannerSetupScreen> {
  final MobileScannerController _controller = MobileScannerController();
  bool _hasScanned = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_hasScanned) return;

    for (final barcode in capture.barcodes) {
      final code = barcode.rawValue;
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
        title: Text(AppLocalizations.of(context)!.adminSetupScanQrTitle),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
      ),
      body: Stack(
        children: [
          MobileScanner(
            controller: _controller,
            onDetect: _onDetect,
          ),
          Center(
            child: Container(
              width: 280,
              height: 280,
              decoration: BoxDecoration(
                border: Border.all(
                  color: Colors.white.withOpacity(0.5),
                  width: 3,
                ),
                borderRadius: BorderRadius.circular(20),
              ),
            ),
          ),
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.transparent,
                    Colors.black.withOpacity(0.9),
                  ],
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    AppLocalizations.of(context)!.adminSetupScanQrInstruction,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    AppLocalizations.of(context)!.adminSetupScanQrSubtitle,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class AdminLoginScreen extends ConsumerStatefulWidget {
  const AdminLoginScreen({super.key});

  @override
  ConsumerState<AdminLoginScreen> createState() => _AdminLoginScreenState();
}

class _AdminLoginScreenState extends ConsumerState<AdminLoginScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  final _storage = const FlutterSecureStorage();
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _rememberCredentials = true;

  static const _usernameKey = 'saved_username';
  static const _passwordKey = 'saved_password';
  static const _rememberKey = 'remember_credentials';

  @override
  void initState() {
    super.initState();
    _loadSavedCredentials();
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _loadSavedCredentials() async {
    final remember = await _storage.read(key: _rememberKey);
    if (remember == 'true') {
      final username = await _storage.read(key: _usernameKey);
      final password = await _storage.read(key: _passwordKey);
      if (username != null && password != null) {
        setState(() {
          _usernameController.text = username;
          _passwordController.text = password;
          _rememberCredentials = true;
        });
      }
    }
  }

  Future<void> _saveCredentials() async {
    if (_rememberCredentials) {
      await _storage.write(
          key: _usernameKey, value: _usernameController.text.trim());
      await _storage.write(key: _passwordKey, value: _passwordController.text);
      await _storage.write(key: _rememberKey, value: 'true');
    } else {
      await _storage.delete(key: _usernameKey);
      await _storage.delete(key: _passwordKey);
      await _storage.write(key: _rememberKey, value: 'false');
    }
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final success = await ref.read(authStateProvider.notifier).login(
          _usernameController.text.trim(),
          _passwordController.text,
        );

    if (success) {
      await _saveCredentials();
    }

    if (mounted) {
      setState(() => _isLoading = false);
    }

    if (!success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content:
              Text(ref.read(authStateProvider).error ?? i18n.adminLoginError),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Widget _buildLogo() {
    final siteInfo = ref.watch(siteInfoProvider);

    return siteInfo.when(
      data: (info) {
        final logoUrl = info?['logo_url'] as String?;
        if (logoUrl != null && logoUrl.isNotEmpty) {
          return ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: Image.network(
              logoUrl,
              height: 80,
              width: 80,
              fit: BoxFit.contain,
              errorBuilder: (context, error, stackTrace) => Icon(
                Icons.admin_panel_settings,
                size: 80,
                color: Theme.of(context).colorScheme.primary,
              ),
              loadingBuilder: (context, child, loadingProgress) {
                if (loadingProgress == null) return child;
                return SizedBox(
                  height: 80,
                  width: 80,
                  child: Center(
                    child: CircularProgressIndicator(
                      value: loadingProgress.expectedTotalBytes != null
                          ? loadingProgress.cumulativeBytesLoaded /
                              loadingProgress.expectedTotalBytes!
                          : null,
                      strokeWidth: 2,
                    ),
                  ),
                );
              },
            ),
          );
        }
        return Icon(
          Icons.admin_panel_settings,
          size: 80,
          color: Theme.of(context).colorScheme.primary,
        );
      },
      loading: () => SizedBox(
        height: 80,
        width: 80,
        child: Center(
          child: CircularProgressIndicator(
            strokeWidth: 2,
            color: Theme.of(context).colorScheme.primary,
          ),
        ),
      ),
      error: (_, __) => Icon(
        Icons.admin_panel_settings,
        size: 80,
        color: Theme.of(context).colorScheme.primary,
      ),
    );
  }

  Widget _buildSiteName() {
    final siteInfo = ref.watch(siteInfoProvider);
    final i18n = AppLocalizations.of(context)!;

    return siteInfo.when(
      data: (info) {
        final siteName = info?['name'] as String?;
        return Text(
          siteName ?? i18n.adminAppTitle,
          style: Theme.of(context).textTheme.headlineMedium,
          textAlign: TextAlign.center,
        );
      },
      loading: () => Text(
        i18n.adminAppTitle,
        style: Theme.of(context).textTheme.headlineMedium,
        textAlign: TextAlign.center,
      ),
      error: (_, __) => Text(
        i18n.adminAppTitle,
        style: Theme.of(context).textTheme.headlineMedium,
        textAlign: TextAlign.center,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.dns),
            tooltip: i18n.adminLoginConfigServerTooltip,
            onPressed: () async {
              await Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const ServerConfigScreen()),
              );
              if (mounted) {
                ref.invalidate(siteInfoProvider);
              }
            },
          ),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _buildLogo(),
                  const SizedBox(height: 16),
                  _buildSiteName(),
                  const SizedBox(height: 8),
                  Text(
                    i18n.adminLoginSubtitle,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Theme.of(context)
                              .colorScheme
                              .onSurface
                              .withOpacity(0.7),
                        ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  FutureBuilder<String>(
                    future: ServerConfig.getServerUrl(),
                    builder: (context, snapshot) {
                      final serverUrl = snapshot.data ?? '';
                      final isConfigured = serverUrl.isNotEmpty;
                      return Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 12),
                        decoration: BoxDecoration(
                          color: isConfigured
                              ? Colors.green.withOpacity(0.1)
                              : Colors.orange.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: isConfigured
                                ? Colors.green.withOpacity(0.3)
                                : Colors.orange.withOpacity(0.3),
                          ),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              isConfigured ? Icons.check_circle : Icons.warning,
                              size: 20,
                              color:
                                  isConfigured ? Colors.green : Colors.orange,
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    isConfigured
                                        ? i18n.adminServerConfiguredStatus
                                        : i18n.adminServerNotConfiguredStatus,
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 12,
                                      color: isConfigured
                                          ? Colors.green
                                          : Colors.orange,
                                    ),
                                  ),
                                  if (isConfigured)
                                    Text(
                                      serverUrl,
                                      style: const TextStyle(fontSize: 11),
                                      overflow: TextOverflow.ellipsis,
                                    )
                                  else
                                    Text(
                                      i18n.adminServerConfigureHint,
                                      style: const TextStyle(fontSize: 11),
                                    ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                  const SizedBox(height: 32),
                  AutofillGroup(
                    child: Column(
                      children: [
                        TextFormField(
                          controller: _usernameController,
                          decoration: InputDecoration(
                            labelText: i18n.adminLoginUsernameLabel,
                            prefixIcon: const Icon(Icons.person),
                          ),
                          autofillHints: const [AutofillHints.username],
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return i18n.adminLoginUsernameRequired;
                            }
                            return null;
                          },
                          textInputAction: TextInputAction.next,
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _passwordController,
                          decoration: InputDecoration(
                            labelText: i18n.adminLoginPasswordLabel,
                            prefixIcon: const Icon(Icons.lock),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                              ),
                              onPressed: () {
                                setState(
                                  () => _obscurePassword = !_obscurePassword,
                                );
                              },
                            ),
                          ),
                          autofillHints: const [AutofillHints.password],
                          obscureText: _obscurePassword,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return i18n.adminLoginPasswordRequired;
                            }
                            return null;
                          },
                          onFieldSubmitted: (_) => _handleLogin(),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Checkbox(
                        value: _rememberCredentials,
                        onChanged: (value) {
                          setState(() => _rememberCredentials = value ?? false);
                        },
                      ),
                      GestureDetector(
                        onTap: () {
                          setState(() =>
                              _rememberCredentials = !_rememberCredentials);
                        },
                        child: Text(i18n.adminLoginRememberCredentials),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: _isLoading ? null : _handleLogin,
                    child: _isLoading
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : Text(i18n.adminLoginButton),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
