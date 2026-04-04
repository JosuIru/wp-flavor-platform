import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/config/app_config.dart';
import '../../../core/widgets/flavor_snackbar.dart';

/// Pantalla de soporte y contacto con desarrolladores
class SupportScreen extends StatelessWidget {
  const SupportScreen({super.key});

  Future<void> _launchEmail(BuildContext context, {String? subject, String? body}) async {
    final i18n = AppLocalizations.of(context);
    final uri = Uri(
      scheme: 'mailto',
      path: AppConfig.developerEmail,
      queryParameters: {
        if (subject != null) 'subject': subject,
        if (body != null) 'body': body,
      },
    );

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      // Copiar email al portapapeles
      await Clipboard.setData(const ClipboardData(text: AppConfig.developerEmail));
      if (context.mounted) {
        FlavorSnackbar.showInfo(
          context,
          i18n.emailCopiadoAlPortapapelesDf197f,
        );
      }
    }
  }

  Future<void> _launchPhone(BuildContext context) async {
    final i18n = AppLocalizations.of(context);
    final uri = Uri(scheme: 'tel', path: AppConfig.developerPhone);

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      await Clipboard.setData(const ClipboardData(text: AppConfig.developerPhone));
      if (context.mounted) {
        FlavorSnackbar.showInfo(
          context,
          i18n.telefonoCopiadoAlPortapapeles5300e7,
        );
      }
    }
  }

  Future<void> _launchWhatsApp(BuildContext context) async {
    final i18n = AppLocalizations.of(context);
    // Limpiar el numero de telefono para WhatsApp
    final phone = AppConfig.developerPhone.replaceAll(RegExp(r'[^\d]'), '');
    final uri = Uri.parse('https://wa.me/$phone');

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (context.mounted) {
        FlavorSnackbar.showError(
          context,
          i18n.noSePudoAbrirWhatsapp0e9a90,
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
        title: Text(i18n.soporte54532b),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Card(
              color: colorScheme.primaryContainer,
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
                        Icons.support_agent,
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
                            AppConfig.developerName,
                            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 4),
                          Text(i18n.equipoDeDesarrollo,
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                  color: colorScheme.onPrimaryContainer.withOpacity(0.8),
                                ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 24),

            // Contacto rapido
            Text(i18n.contactoRapido,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _ContactButton(
                    icon: Icons.email,
                    label: i18n.supportContactEmailLabel,
                    color: Colors.red,
                    onTap: () => _launchEmail(context),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _ContactButton(
                    icon: Icons.phone,
                    label: i18n.supportContactCallLabel,
                    color: Colors.blue,
                    onTap: () => _launchPhone(context),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _ContactButton(
                    icon: Icons.chat,
                    label: i18n.supportContactWhatsAppLabel,
                    color: Colors.green,
                    onTap: () => _launchWhatsApp(context),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 24),

            // Opciones de soporte
            Text(i18n.necesitoAyudaCon,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            _SupportOption(
              icon: Icons.bug_report,
              title: i18n.supportReportProblemTitle,
              description: i18n.supportReportProblemDesc,
              onTap: () => _launchEmail(
                context,
                subject: i18n.supportReportProblemSubject,
                body: i18n.supportReportProblemBody(AppConfig.appVersion),
              ),
            ),
            _SupportOption(
              icon: Icons.lightbulb,
              title: i18n.supportSuggestImprovementTitle,
              description: i18n.supportSuggestImprovementDesc,
              onTap: () => _launchEmail(
                context,
                subject: i18n.supportSuggestImprovementSubject,
                body: i18n.supportSuggestImprovementBody(AppConfig.appVersion),
              ),
            ),
            _SupportOption(
              icon: Icons.help_outline,
              title: i18n.supportGeneralQuestionTitle,
              description: i18n.supportGeneralQuestionDesc,
              onTap: () => _launchEmail(
                context,
                subject: i18n.supportGeneralQuestionSubject,
                body: i18n.supportGeneralQuestionBody(AppConfig.appVersion),
              ),
            ),
            _SupportOption(
              icon: Icons.add_circle_outline,
              title: i18n.supportRequestFeatureTitle,
              description: i18n.supportRequestFeatureDesc,
              onTap: () => _launchEmail(
                context,
                subject: i18n.supportRequestFeatureSubject,
                body: i18n.supportRequestFeatureBody(AppConfig.appVersion),
              ),
            ),
            _SupportOption(
              icon: Icons.school,
              title: i18n.supportRequestTrainingTitle,
              description: i18n.supportRequestTrainingDesc,
              onTap: () => _launchEmail(
                context,
                subject: i18n.supportRequestTrainingSubject,
                body: i18n.supportRequestTrainingBody(AppConfig.appVersion),
              ),
            ),

            const SizedBox(height: 24),

            // Info de contacto
            Text(i18n.datosDeContacto,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            Card(
              child: Column(
                children: [
                  ListTile(
                    leading: const Icon(Icons.email),
                    title: Text(i18n.emailCe8ae9),
                    subtitle: const Text(AppConfig.developerEmail),
                    trailing: IconButton(
                      icon: const Icon(Icons.copy),
                      onPressed: () async {
                        await Clipboard.setData(
                          const ClipboardData(text: AppConfig.developerEmail),
                        );
                        if (context.mounted) {
                          FlavorSnackbar.showInfo(
                            context,
                            i18n.emailCopiadoFa2686,
                          );
                        }
                      },
                    ),
                    onTap: () => _launchEmail(context),
                  ),
                  const Divider(height: 1),
                  ListTile(
                    leading: const Icon(Icons.phone),
                    title: Text(i18n.telefonoBdf77d),
                    subtitle: const Text(AppConfig.developerPhone),
                    trailing: IconButton(
                      icon: const Icon(Icons.copy),
                      onPressed: () async {
                        await Clipboard.setData(
                          const ClipboardData(text: AppConfig.developerPhone),
                        );
                        if (context.mounted) {
                          FlavorSnackbar.showInfo(
                            context,
                            i18n.telefonoCopiado6cb976,
                          );
                        }
                      },
                    ),
                    onTap: () => _launchPhone(context),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            // Horario
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Icon(
                      Icons.access_time,
                      color: colorScheme.primary,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(i18n.horarioDeAtencion,
                            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 4),
                          Text(i18n.lunesAViernes9001800,
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }
}

class _ContactButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;

  const _ContactButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: color),
              ),
              const SizedBox(height: 8),
              Text(
                label,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SupportOption extends StatelessWidget {
  final IconData icon;
  final String title;
  final String description;
  final VoidCallback onTap;

  const _SupportOption({
    required this.icon,
    required this.title,
    required this.description,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Icon(icon, color: Theme.of(context).colorScheme.primary),
        title: Text(title),
        subtitle: Text(description),
        trailing: const Icon(Icons.chevron_right),
        onTap: onTap,
      ),
    );
  }
}
