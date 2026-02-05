import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../core/config/app_config.dart';

/// WebView para el checkout de WooCommerce
class CheckoutWebView extends StatefulWidget {
  final String checkoutUrl;

  const CheckoutWebView({
    super.key,
    required this.checkoutUrl,
  });

  @override
  State<CheckoutWebView> createState() => _CheckoutWebViewState();
}

class _CheckoutWebViewState extends State<CheckoutWebView> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  void _initWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (progress) {
            setState(() {
              _progress = progress / 100;
            });
          },
          onPageStarted: (url) {
            setState(() => _isLoading = true);
          },
          onPageFinished: (url) {
            setState(() => _isLoading = false);
            _checkForCompletion(url);
          },
          onNavigationRequest: (request) {
            // Permitir navegación dentro del dominio
            return NavigationDecision.navigate;
          },
          onWebResourceError: (error) {
            if (mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text('Error: ${error.description}'),
                  backgroundColor: Colors.red,
                ),
              );
            }
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.checkoutUrl));
  }

  void _checkForCompletion(String url) {
    // Detectar URLs de confirmación de WooCommerce
    final completionPatterns = [
      '/order-received/',
      '/checkout/order-received/',
      '/thank-you',
      '/thankyou',
      'order-received',
      'key=wc_order_',
    ];

    final isCompleted = completionPatterns.any((pattern) =>
      url.toLowerCase().contains(pattern.toLowerCase())
    );

    if (isCompleted) {
      // Mostrar mensaje y cerrar después de un momento
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => AlertDialog(
          icon: const Icon(
            Icons.check_circle,
            color: Colors.green,
            size: 64,
          ),
          title: const Text('¡Reserva completada!'),
          content: const Text(
            'Tu reserva ha sido procesada con éxito. '
            'Recibirás un email de confirmación.',
          ),
          actions: [
            FilledButton(
              onPressed: () {
                Navigator.pop(context); // Cerrar diálogo
                Navigator.pop(context, true); // Volver con éxito
              },
              child: const Text('Aceptar'),
            ),
          ],
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Finalizar compra'),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => _showExitConfirmation(),
        ),
        bottom: _isLoading
            ? PreferredSize(
                preferredSize: const Size.fromHeight(4),
                child: LinearProgressIndicator(value: _progress),
              )
            : null,
      ),
      body: WebViewWidget(controller: _controller),
    );
  }

  void _showExitConfirmation() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('¿Cancelar compra?'),
        content: const Text(
          'Si sales ahora, perderás los cambios y tendrás que empezar de nuevo.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Continuar'),
          ),
          FilledButton(
            onPressed: () {
              Navigator.pop(context); // Cerrar diálogo
              Navigator.pop(context, false); // Volver sin éxito
            },
            style: FilledButton.styleFrom(
              backgroundColor: Theme.of(context).colorScheme.error,
            ),
            child: const Text('Salir'),
          ),
        ],
      ),
    );
  }
}
