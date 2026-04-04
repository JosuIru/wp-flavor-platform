import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import '../../core/widgets/flavor_webview_page.dart';

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
  AppLocalizations get i18n => AppLocalizations.of(context);

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
          title: Text(i18n.reservationCompletedTitle),
        content: Text(i18n.checkoutSuccessMessage),
          actions: [
            FilledButton(
              onPressed: () {
                Navigator.pop(context); // Cerrar diálogo
                Navigator.pop(context, true); // Volver con éxito
              },
              child: Text(i18n.acceptButton),
            ),
          ],
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    return FlavorWebViewPage(
      title: i18n.completePurchaseTitle,
      url: widget.checkoutUrl,
      leading: IconButton(
        icon: const Icon(Icons.close),
        onPressed: _showExitConfirmation,
      ),
      onPageFinished: _checkForCompletion,
      onWebResourceError: (error) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.commonError(error.description)),
            backgroundColor: Colors.red,
          ),
        );
      },
    );
  }

  void _showExitConfirmation() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.cancelPurchaseConfirm),
        content: Text(i18n.checkoutExitWarning),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(i18n.continueButton),
          ),
          FilledButton(
            onPressed: () {
              Navigator.pop(context); // Cerrar diálogo
              Navigator.pop(context, false); // Volver sin éxito
            },
            style: FilledButton.styleFrom(
              backgroundColor: Theme.of(context).colorScheme.error,
            ),
            child: Text(i18n.exitButton),
          ),
        ],
      ),
    );
  }
}
