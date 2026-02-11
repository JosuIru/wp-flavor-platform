import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:webview_flutter/webview_flutter.dart';

/// Pantalla de contacto con WebView
class ContactWebViewScreen extends StatefulWidget {
  final String url;
  final String title;

  const ContactWebViewScreen({
    super.key,
    required this.url,
    this.title = 'Contacto',
  });

  @override
  State<ContactWebViewScreen> createState() => _ContactWebViewScreenState();
}

class _ContactWebViewScreenState extends State<ContactWebViewScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  late final WebViewController _controller;
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _initializeWebView();
  }

  void _initializeWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
              _errorMessage = null;
            });
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
          },
          onWebResourceError: (WebResourceError error) {
            setState(() {
              _isLoading = false;
              _errorMessage = 'Error al cargar la página: ${error.description}';
            });
          },
          onNavigationRequest: (NavigationRequest request) {
            // Permitir navegación dentro del dominio
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          // Botón para recargar
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              _controller.reload();
            },
            tooltip: i18n.recargar3a9786,
          ),
        ],
      ),
      body: Stack(
        children: [
          if (_errorMessage != null)
            // Mostrar error
            Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.error_outline,
                      size: 64,
                      color: Theme.of(context).colorScheme.error,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      _errorMessage!,
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                    const SizedBox(height: 24),
                    FilledButton.icon(
                      onPressed: () {
                        setState(() {
                          _errorMessage = null;
                        });
                        _initializeWebView();
                      },
                      icon: const Icon(Icons.refresh),
                      label: Text(i18n.reintentar179654),
                    ),
                  ],
                ),
              ),
            )
          else
            WebViewWidget(controller: _controller),

          // Indicador de carga
          if (_isLoading)
            Container(
              color: Theme.of(context).colorScheme.surface,
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    CircularProgressIndicator(
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      i18n.commonLoading,
                      style: Theme.of(context).textTheme.bodyMedium,
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
