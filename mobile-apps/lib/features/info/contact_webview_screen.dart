import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import '../../core/widgets/flavor_state_widgets.dart';
import '../../core/widgets/flavor_webview_page.dart';

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
  AppLocalizations get i18n => AppLocalizations.of(context);

  @override
  Widget build(BuildContext context) {
    return FlavorWebViewPage(
      title: widget.title,
      url: widget.url,
      showLoadingOverlay: true,
      actionsBuilder: (context, controller) => [
        IconButton(
          icon: const Icon(Icons.refresh),
          onPressed: controller.reload,
          tooltip: i18n.recargar3a9786,
        ),
      ],
      loadingBuilder: (context) => Container(
        color: Theme.of(context).colorScheme.surface,
        child: const FlavorLoadingState(),
      ),
      errorBuilder: (context, errorMessage, retry) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
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
                'Error al cargar la pagina: $errorMessage',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyLarge,
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                onPressed: retry,
                icon: const Icon(Icons.refresh),
                label: Text(i18n.reintentar179654),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
