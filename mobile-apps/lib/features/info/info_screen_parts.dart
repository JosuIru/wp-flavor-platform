part of 'info_screen.dart';

class _ActionCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback? onTap;

  const _ActionCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                color.withOpacity(0.1),
                color.withOpacity(0.05),
              ],
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: color, size: 24),
              ),
              const SizedBox(height: 12),
              Text(
                title,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                subtitle,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withOpacity(0.7),
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Pantalla para cambiar de negocio (escanear otro QR)
class ChangeBusinessScreen extends ConsumerStatefulWidget {
  final VoidCallback onComplete;

  const ChangeBusinessScreen({super.key, required this.onComplete});

  @override
  ConsumerState<ChangeBusinessScreen> createState() =>
      _ChangeBusinessScreenState();
}

class _ChangeBusinessScreenState extends ConsumerState<ChangeBusinessScreen> {
  bool _isScanning = false;
  bool _isLoading = false;
  String? _error;
  bool _isLoadingSaved = false;
  String _savedQuery = '';
  final TextEditingController _manualUrlController = TextEditingController();
  List<SavedBusiness> _savedBusinesses = [];
  AppLocalizations get i18n => AppLocalizations.of(context);

  @override
  void initState() {
    super.initState();
    _loadSavedBusinesses();
  }

  @override
  void dispose() {
    _manualUrlController.dispose();
    super.dispose();
  }

  Future<void> _loadSavedBusinesses() async {
    setState(() => _isLoadingSaved = true);
    final businesses = await ServerConfig.getBusinesses();
    if (mounted) {
      setState(() {
        _savedBusinesses = businesses;
        _isLoadingSaved = false;
      });
    }
  }

  Future<void> _connectSavedBusiness(SavedBusiness business) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      await ref
          .read(serverConfigProvider.notifier)
          .setCurrentBusiness(business);
      ref
          .read(apiClientProvider)
          .updateBaseUrl('${business.serverUrl}${business.apiNamespace}');
      final syncResult = await ref
          .read(syncProvider.notifier)
          .syncWithSite(business.serverUrl);
      if (!syncResult.success) {
        setState(() {
          _isLoading = false;
          _error = syncResult.error ?? 'No se pudo sincronizar el negocio';
        });
        return;
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.commonConnectedTo(
                business.name.isNotEmpty ? business.name : business.serverUrl)),
            backgroundColor: Colors.green,
          ),
        );
        widget.onComplete();
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _error = 'No se pudo conectar al negocio';
        });
      }
    }
  }

  Future<void> _connectToServerUrl(String inputUrl,
      {String? preferredName}) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      String serverUrl = inputUrl.trim().replaceAll(r'\/', '/');

      if (serverUrl.isEmpty) {
        setState(() {
          _isLoading = false;
          _error = i18n.infoInvalidQrMissingUrl;
        });
        return;
      }

      // Normalizar URL
      final wpJsonIndex = serverUrl.indexOf('/wp-json');
      if (wpJsonIndex > 0) {
        serverUrl = serverUrl.substring(0, wpJsonIndex);
      }
      if (!serverUrl.startsWith('http://') &&
          !serverUrl.startsWith('https://')) {
        serverUrl = 'https://$serverUrl';
      }
      if (serverUrl.endsWith('/')) {
        serverUrl = serverUrl.substring(0, serverUrl.length - 1);
      }

      // Detectar API/namespace del sitio para evitar fijar chat-ia-mobile siempre
      final discoveryResponse = await ApiClient.discoverSiteAt(serverUrl);
      if (!discoveryResponse.success || discoveryResponse.data == null) {
        setState(() {
          _isLoading = false;
          _error = discoveryResponse.error ?? i18n.setupConnectionFailed;
        });
        return;
      }

      final apiNamespace = await ApiClient.detectPreferredApiNamespace(
        serverUrl,
        discoveryData: discoveryResponse.data,
      );
      final fullUrl = '$serverUrl$apiNamespace';

      // Guardar negocio actual (URL + namespace) usando el nombre detectado en app-discovery
      final siteName = (discoveryResponse.data?['app_name'] ??
              discoveryResponse.data?['site_name'])
          ?.toString();
      await ServerConfig.setCurrentBusiness(
        serverUrl: serverUrl,
        apiNamespace: apiNamespace,
        name: siteName,
        type: 'client',
      );

      // Actualizar API client
      ref.read(apiClientProvider).updateBaseUrl(fullUrl);
      final syncResult =
          await ref.read(syncProvider.notifier).syncWithSite(serverUrl);
      if (!syncResult.success) {
        setState(() {
          _isLoading = false;
          _error = syncResult.error ?? 'No se pudo sincronizar el negocio';
        });
        return;
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.infoConnectedTo(
              siteName ?? preferredName ?? serverUrl,
            )),
            backgroundColor: Colors.green,
          ),
        );
        widget.onComplete();
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Error: $e';
      });
    }
  }

  Future<void> _processQR(String code) async {
    try {
      final jsonData = json.decode(code) as Map<String, dynamic>;
      final serverUrl =
          (jsonData['url'] as String? ?? '').replaceAll(r'\/', '/');
      final name = jsonData['name']?.toString();
      await _connectToServerUrl(serverUrl, preferredName: name);
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Error: $e';
      });
    }
  }

  Future<void> _connectManualUrl() async {
    await _connectToServerUrl(_manualUrlController.text);
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: Text(i18n.infoChangeBusinessTitle),
          bottom: TabBar(
            tabs: [
              Tab(text: i18n.infoScanQrTitle),
              const Tab(text: 'URL'),
              Tab(text: i18n.directorioDf7b92),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildScanTab(),
            _buildManualTab(),
            DirectoryScreen(
              embedded: true,
              closeOnConnect: false,
              onConnected: widget.onComplete,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildScanTab() {
    if (_isScanning) {
      return Stack(
        children: [
          MobileScanner(
            onDetect: (capture) {
              final code = capture.barcodes.firstOrNull?.rawValue;
              if (code != null && code.isNotEmpty) {
                setState(() => _isScanning = false);
                _processQR(code);
              }
            },
          ),
          Center(
            child: Container(
              width: 280,
              height: 280,
              decoration: BoxDecoration(
                border:
                    Border.all(color: Colors.white.withOpacity(0.5), width: 3),
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
                  colors: [Colors.transparent, Colors.black.withOpacity(0.9)],
                ),
              ),
              child: Text(
                i18n.infoScanNewBusinessHint,
                style: const TextStyle(color: Colors.white70, fontSize: 14),
                textAlign: TextAlign.center,
              ),
            ),
          ),
        ],
      );
    }

    final filteredBusinesses = _savedQuery.isEmpty
        ? _savedBusinesses
        : _savedBusinesses.where((b) {
            final query = _savedQuery.toLowerCase();
            return b.name.toLowerCase().contains(query) ||
                b.serverUrl.toLowerCase().contains(query);
          }).toList();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Icon(
            Icons.qr_code_scanner,
            size: 80,
            color: Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(height: 24),
          Text(
            i18n.infoConnectAnotherBusinessTitle,
            style: Theme.of(context).textTheme.headlineSmall,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 12),
          Text(
            i18n.infoScanConfigQrHint,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color:
                      Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 32),
          if (_error != null) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.red.shade50,
                borderRadius: BorderRadius.circular(8),
              ),
              child:
                  Text(_error!, style: TextStyle(color: Colors.red.shade700)),
            ),
            const SizedBox(height: 16),
          ],
          if (_isLoading)
            const FlavorLoadingState()
          else
            FilledButton.icon(
              onPressed: () => setState(() => _isScanning = true),
              icon: const Icon(Icons.qr_code_scanner),
              label: Text(i18n.infoScanQrButton),
            ),
          const SizedBox(height: 24),
          Text(
            i18n.sitiosGuardados13e63b,
            style: Theme.of(context)
                .textTheme
                .titleMedium
                ?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          TextField(
            decoration: InputDecoration(
              labelText: i18n.buscar113f74,
              prefixIcon: const Icon(Icons.search),
              border: const OutlineInputBorder(),
            ),
            onChanged: (value) => setState(() => _savedQuery = value),
          ),
          const SizedBox(height: 12),
          if (_isLoadingSaved)
            const FlavorLoadingState()
          else if (filteredBusinesses.isEmpty)
            Text(
              i18n.noHaySitiosGuardados5a9b5e,
              style: Theme.of(context).textTheme.bodyMedium,
              textAlign: TextAlign.center,
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: filteredBusinesses.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final business = filteredBusinesses[index];
                final title = business.name.isNotEmpty
                    ? business.name
                    : business.serverUrl;
                final subtitle = business.name.isNotEmpty
                    ? business.serverUrl
                    : business.apiNamespace;
                return Card(
                  child: ListTile(
                    leading: const Icon(Icons.public),
                    title: Text(title,
                        maxLines: 1, overflow: TextOverflow.ellipsis),
                    subtitle: Text(subtitle,
                        maxLines: 1, overflow: TextOverflow.ellipsis),
                    trailing: TextButton(
                      onPressed: _isLoading
                          ? null
                          : () => _connectSavedBusiness(business),
                      child: Text(i18n.conectar4b2e15),
                    ),
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  Widget _buildManualTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Icon(
            Icons.link,
            size: 72,
            color: Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(height: 16),
          Text(
            'Conectar por URL',
            style: Theme.of(context).textTheme.headlineSmall,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 10),
          Text(
            'Introduce la URL base del negocio, por ejemplo: https://tudominio.com',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color:
                      Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
                ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          TextField(
            controller: _manualUrlController,
            keyboardType: TextInputType.url,
            autocorrect: false,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _connectManualUrl(),
            decoration: const InputDecoration(
              labelText: 'URL del negocio',
              hintText: 'https://tu-sitio.com',
              prefixIcon: Icon(Icons.public),
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 12),
          if (_error != null) ...[
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.red.shade50,
                borderRadius: BorderRadius.circular(8),
              ),
              child:
                  Text(_error!, style: TextStyle(color: Colors.red.shade700)),
            ),
            const SizedBox(height: 12),
          ],
          if (_isLoading)
            const FlavorLoadingState()
          else
            FilledButton.icon(
              onPressed: _connectManualUrl,
              icon: const Icon(Icons.login),
              label: const Text('Conectar'),
            ),
        ],
      ),
    );
  }
}
