import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/config/app_config.dart';

/// Pantalla de soporte y contacto con desarrolladores
class SupportScreen extends StatelessWidget {
  const SupportScreen({super.key});

  Future<void> _launchEmail(BuildContext context, {String? subject, String? body}) async {
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
      await Clipboard.setData(ClipboardData(text: AppConfig.developerEmail));
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Email copiado al portapapeles')),
        );
      }
    }
  }

  Future<void> _launchPhone(BuildContext context) async {
    final uri = Uri(scheme: 'tel', path: AppConfig.developerPhone);

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      await Clipboard.setData(ClipboardData(text: AppConfig.developerPhone));
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Telefono copiado al portapapeles')),
        );
      }
    }
  }

  Future<void> _launchWhatsApp(BuildContext context) async {
    // Limpiar el numero de telefono para WhatsApp
    final phone = AppConfig.developerPhone.replaceAll(RegExp(r'[^\d]'), '');
    final uri = Uri.parse('https://wa.me/$phone');

    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No se pudo abrir WhatsApp')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Soporte'),
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
                          Text(
                            'Equipo de desarrollo',
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
            Text(
              'Contacto rapido',
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
                    label: 'Email',
                    color: Colors.red,
                    onTap: () => _launchEmail(context),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _ContactButton(
                    icon: Icons.phone,
                    label: 'Llamar',
                    color: Colors.blue,
                    onTap: () => _launchPhone(context),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _ContactButton(
                    icon: Icons.chat,
                    label: 'WhatsApp',
                    color: Colors.green,
                    onTap: () => _launchWhatsApp(context),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 24),

            // Opciones de soporte
            Text(
              'Necesito ayuda con...',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),
            _SupportOption(
              icon: Icons.bug_report,
              title: 'Reportar un problema',
              description: 'Algo no funciona como esperaba',
              onTap: () => _launchEmail(
                context,
                subject: '[App Admin] Reporte de problema',
                body: 'Hola,\n\nHe encontrado un problema en la app:\n\nDescripcion del problema:\n\n\nPasos para reproducirlo:\n1. \n2. \n3. \n\nVersion de la app: ${AppConfig.appVersion}\n\nGracias.',
              ),
            ),
            _SupportOption(
              icon: Icons.lightbulb,
              title: 'Proponer una mejora',
              description: 'Tengo una idea para mejorar la app',
              onTap: () => _launchEmail(
                context,
                subject: '[App Admin] Propuesta de mejora',
                body: 'Hola,\n\nMe gustaria proponer la siguiente mejora:\n\nDescripcion de la mejora:\n\n\nPor que seria util:\n\n\nVersion de la app: ${AppConfig.appVersion}\n\nGracias.',
              ),
            ),
            _SupportOption(
              icon: Icons.help_outline,
              title: 'Pregunta general',
              description: 'Tengo una duda sobre el funcionamiento',
              onTap: () => _launchEmail(
                context,
                subject: '[App Admin] Consulta',
                body: 'Hola,\n\nTengo una pregunta:\n\n\n\nVersion de la app: ${AppConfig.appVersion}\n\nGracias.',
              ),
            ),
            _SupportOption(
              icon: Icons.add_circle_outline,
              title: 'Solicitar nueva funcionalidad',
              description: 'Necesito algo que la app no tiene',
              onTap: () => _launchEmail(
                context,
                subject: '[App Admin] Solicitud de funcionalidad',
                body: 'Hola,\n\nMe gustaria solicitar la siguiente funcionalidad:\n\nDescripcion:\n\n\nCaso de uso:\n\n\nPrioridad (alta/media/baja):\n\nVersion de la app: ${AppConfig.appVersion}\n\nGracias.',
              ),
            ),
            _SupportOption(
              icon: Icons.school,
              title: 'Solicitar formacion',
              description: 'Necesito ayuda para usar la app',
              onTap: () => _launchEmail(
                context,
                subject: '[App Admin] Solicitud de formacion',
                body: 'Hola,\n\nMe gustaria solicitar formacion sobre:\n\n\n\nDisponibilidad horaria:\n\n\nVersion de la app: ${AppConfig.appVersion}\n\nGracias.',
              ),
            ),

            const SizedBox(height: 24),

            // Info de contacto
            Text(
              'Datos de contacto',
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
                    title: const Text('Email'),
                    subtitle: Text(AppConfig.developerEmail),
                    trailing: IconButton(
                      icon: const Icon(Icons.copy),
                      onPressed: () async {
                        await Clipboard.setData(
                          ClipboardData(text: AppConfig.developerEmail),
                        );
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Email copiado')),
                          );
                        }
                      },
                    ),
                    onTap: () => _launchEmail(context),
                  ),
                  const Divider(height: 1),
                  ListTile(
                    leading: const Icon(Icons.phone),
                    title: const Text('Telefono'),
                    subtitle: Text(AppConfig.developerPhone),
                    trailing: IconButton(
                      icon: const Icon(Icons.copy),
                      onPressed: () async {
                        await Clipboard.setData(
                          ClipboardData(text: AppConfig.developerPhone),
                        );
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Telefono copiado')),
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
                          Text(
                            'Horario de atencion',
                            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Lunes a Viernes: 9:00 - 18:00',
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
