import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../core/providers/providers.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/config/app_config.dart';
import '../../core/config/dynamic_config.dart';
import '../../core/config/server_config.dart';
import '../../core/api/api_client.dart';
import '../../main_client.dart' show siteInfoProvider, clientAppConfigProvider;
import 'contact_webview_screen.dart';

/// Provider para contenido inteligente del sitio
final siteContentProvider = FutureProvider<Map<String, dynamic>?>((ref) async {
  final api = ref.read(apiClientProvider);
  final response = await api.getSiteContent();
  if (response.success && response.data != null) {
    return response.data;
  }
  return null;
});

/// Pantalla de información del negocio con autoconfiguración
class InfoScreen extends ConsumerStatefulWidget {
  final Function(int)? onNavigateToTab;

  const InfoScreen({super.key, this.onNavigateToTab});

  @override
  ConsumerState<InfoScreen> createState() => _InfoScreenState();
}

class _InfoScreenState extends ConsumerState<InfoScreen> {
  @override
  Widget build(BuildContext context) {
    final siteContentAsync = ref.watch(siteContentProvider);
    final siteInfo = ref.watch(siteInfoProvider);

    final siteName = siteInfo.valueOrNull?['name'] as String? ?? AppConfig.businessName;
    final logoUrl = siteInfo.valueOrNull?['logo_url'] as String?;

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(siteContentProvider);
          ref.invalidate(siteInfoProvider);
        },
        child: siteContentAsync.when(
          data: (content) => _buildContent(context, content, siteName, logoUrl),
          loading: () => const LoadingScreen(message: 'Cargando información...'),
          error: (error, _) => _buildFallbackContent(context, siteName, logoUrl),
        ),
      ),
    );
  }

  Widget _buildContent(BuildContext context, Map<String, dynamic>? content, String siteName, String? logoUrl) {
    if (content == null) {
      return _buildFallbackContent(context, siteName, logoUrl);
    }

    // Obtener configuración dinámica para filtrar secciones
    final dynamicConfig = DynamicConfig();

    final site = content['site'] as Map<String, dynamic>? ?? {};
    final sections = (content['sections'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final quickLinks = (content['quick_links'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final socialLinks = (content['social_links'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final contact = content['contact'] as Map<String, dynamic>? ?? {};
    final gallery = (content['gallery'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final news = (content['news'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final services = (content['services'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final schedule = content['schedule'] as Map<String, dynamic>? ?? {};
    final location = content['location'] as Map<String, dynamic>? ?? {};

    final displayName = site['name'] as String? ?? siteName;
    final tagline = site['tagline'] as String? ?? '';
    final siteLogoUrl = site['logo'] as String? ?? logoUrl;

    // Obtener logo dinámico según brillo del tema
    final brightness = Theme.of(context).brightness;
    final dynamicLogoUrl = dynamicConfig.isLoaded
        ? dynamicConfig.getLogoForBrightness(brightness)
        : null;
    final finalLogoUrl = dynamicLogoUrl ?? siteLogoUrl ?? logoUrl;

    return CustomScrollView(
      slivers: [
        // Header con logo
        SliverAppBar(
          expandedHeight: 220,
          pinned: true,
          flexibleSpace: FlexibleSpaceBar(
            title: Text(
              displayName,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                shadows: [Shadow(color: Colors.black54, blurRadius: 4)],
              ),
            ),
            background: _buildHeader(context, finalLogoUrl, tagline),
          ),
        ),

        // Contenido
        SliverToBoxAdapter(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Acciones principales de la app
              _buildAppActions(context),

              // Enlaces rápidos (siempre visibles si hay datos)
              if (quickLinks.isNotEmpty) _buildQuickLinks(context, quickLinks),

              // Servicios/Experiencias - respeta configuración
              if (services.isNotEmpty && dynamicConfig.showServices)
                _buildServicesSection(context, services),

              // Secciones de contenido (páginas importantes) - respeta business_info
              if (sections.isNotEmpty && dynamicConfig.showBusinessInfo)
                _buildSections(context, sections),

              // Galería - respeta configuración
              if (gallery.isNotEmpty && dynamicConfig.showGallery)
                _buildGallery(context, gallery),

              // Noticias/Blog - respeta configuración
              if (news.isNotEmpty && dynamicConfig.showLatestPosts)
                _buildNews(context, news),

              // Horarios - respeta configuración
              if (schedule['text'] != null &&
                  schedule['text'].toString().isNotEmpty &&
                  dynamicConfig.showSchedule)
                _buildSchedule(context, schedule),

              // Ubicación - respeta configuración
              if ((location['address'] != null || location['coordinates'] != null) &&
                  dynamicConfig.showLocation)
                _buildLocation(context, location),

              // Contacto - respeta configuración
              if (contact.values.any((v) => v != null && v.toString().isNotEmpty) &&
                  dynamicConfig.showContact)
                _buildContact(context, contact),

              // Redes sociales - respeta configuración
              if (socialLinks.isNotEmpty && dynamicConfig.showSocialLinks)
                _buildSocialLinks(context, socialLinks),

              // Sección de ajustes
              _buildSettingsSection(context),

              const SizedBox(height: 32),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildSettingsSection(BuildContext context) {
    return Container(
      margin: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surfaceContainerHighest.withOpacity(0.5),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          ListTile(
            leading: Icon(Icons.swap_horiz, color: Theme.of(context).colorScheme.primary),
            title: const Text('Cambiar negocio'),
            subtitle: const Text('Conectar a otro sitio'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _showChangeBusinessDialog(context),
          ),
        ],
      ),
    );
  }

  Future<void> _showChangeBusinessDialog(BuildContext context) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cambiar de negocio'),
        content: const Text(
          '¿Quieres conectar esta app a otro negocio?\n\n'
          'Podrás escanear el código QR de configuración del nuevo sitio.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Cambiar'),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      // Navegar a pantalla de setup
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => _ChangeBusinessScreen(
            onComplete: () {
              Navigator.pop(context);
              // Recargar la app
              ref.invalidate(clientAppConfigProvider);
            },
          ),
        ),
      );
    }
  }

  Widget _buildHeader(BuildContext context, String? logoUrl, String tagline) {
    return Stack(
      fit: StackFit.expand,
      children: [
        Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Theme.of(context).colorScheme.primary,
                Theme.of(context).colorScheme.primaryContainer,
              ],
            ),
          ),
          child: logoUrl != null && logoUrl.isNotEmpty
              ? Padding(
                  padding: const EdgeInsets.all(50),
                  child: CachedNetworkImage(
                    imageUrl: logoUrl,
                    fit: BoxFit.contain,
                    color: Colors.white.withOpacity(0.3),
                    colorBlendMode: BlendMode.modulate,
                    errorWidget: (_, __, ___) => _buildDefaultIcon(),
                  ),
                )
              : _buildDefaultIcon(),
        ),
        Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Colors.transparent, Colors.black.withOpacity(0.6)],
            ),
          ),
        ),
        // Tagline deshabilitado - se mostraba debajo del título
        // if (tagline.isNotEmpty)
        //   Positioned(
        //     bottom: 50,
        //     left: 16,
        //     right: 16,
        //     child: Text(
        //       tagline,
        //       style: TextStyle(
        //         color: Colors.white.withOpacity(0.9),
        //         fontSize: 14,
        //       ),
        //       textAlign: TextAlign.center,
        //     ),
        //   ),
      ],
    );
  }

  Widget _buildDefaultIcon() {
    return Icon(Icons.landscape, size: 100, color: Colors.white.withOpacity(0.3));
  }

  Widget _buildAppActions(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Expanded(
            child: _ActionCard(
              icon: Icons.chat_bubble,
              title: 'Chat',
              subtitle: 'Habla con nosotros',
              color: Theme.of(context).colorScheme.primary,
              onTap: () => widget.onNavigateToTab?.call(0),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _ActionCard(
              icon: Icons.calendar_today,
              title: 'Reservar',
              subtitle: 'Ver disponibilidad',
              color: Theme.of(context).colorScheme.secondary,
              onTap: () => widget.onNavigateToTab?.call(1),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickLinks(BuildContext context, List<Map<String, dynamic>> links) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Accesos rápidos',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: links.map((link) {
              return ActionChip(
                avatar: const Icon(Icons.link, size: 18),
                label: Text(link['title'] ?? ''),
                onPressed: () => _openUrl(link['url']),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildServicesSection(BuildContext context, List<Map<String, dynamic>> services) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.star, color: Theme.of(context).colorScheme.primary),
              const SizedBox(width: 8),
              Text(
                'Nuestras experiencias',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
            ],
          ),
          if (services.isEmpty)
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Text(
                'No hay experiencias disponibles en los próximos 2 meses',
                style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.6)),
              ),
            )
          else
            const SizedBox(height: 12),
          ...services.map((service) {
            final nextDates = (service['next_dates'] as List?)?.cast<String>() ?? [];
            final totalDays = service['total_available_days'] as int? ?? 0;

            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ExpansionTile(
                leading: service['image'] != null && service['image'].toString().isNotEmpty
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: CachedNetworkImage(
                          imageUrl: service['image'],
                          width: 60,
                          height: 60,
                          fit: BoxFit.cover,
                          errorWidget: (_, __, ___) => Container(
                            width: 60,
                            height: 60,
                            color: Theme.of(context).colorScheme.primaryContainer,
                            child: Icon(Icons.hiking, color: Theme.of(context).colorScheme.onPrimaryContainer),
                          ),
                        ),
                      )
                    : Container(
                        width: 60,
                        height: 60,
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.primaryContainer,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Icon(Icons.hiking, color: Theme.of(context).colorScheme.onPrimaryContainer),
                      ),
                title: Text(
                  service['name'] ?? '',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (service['price'] != null && service['price'] > 0)
                      Text(
                        '${service['price']}€',
                        style: TextStyle(
                          color: Theme.of(context).colorScheme.primary,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    if (service['duration'] != null && service['duration'].toString().isNotEmpty)
                      Text(
                        service['duration'],
                        style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.6)),
                      ),
                  ],
                ),
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (service['description'] != null && service['description'].toString().isNotEmpty) ...[
                          Text(
                            service['description'],
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                          const SizedBox(height: 16),
                        ],
                        if (nextDates.isNotEmpty) ...[
                          Text(
                            'Próximas fechas disponibles:',
                            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 8),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: nextDates.map((date) {
                              return Chip(
                                avatar: const Icon(Icons.calendar_today, size: 16),
                                label: Text(
                                  _formatDate(date),
                                  style: const TextStyle(fontSize: 12),
                                ),
                                backgroundColor: Theme.of(context).colorScheme.secondaryContainer,
                              );
                            }).toList(),
                          ),
                          if (totalDays > nextDates.length)
                            Padding(
                              padding: const EdgeInsets.only(top: 8),
                              child: Text(
                                '+ ${totalDays - nextDates.length} fechas más disponibles',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Theme.of(context).colorScheme.onSurface.withOpacity(0.6),
                                ),
                              ),
                            ),
                          const SizedBox(height: 12),
                        ],
                        FilledButton.icon(
                          onPressed: () {
                            // Navegar a pantalla de reservas con este ticket pre-seleccionado
                            widget.onNavigateToTab?.call(1); // Tab de reservas
                          },
                          icon: const Icon(Icons.calendar_month),
                          label: const Text('Reservar'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      final months = [
        'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
        'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
      ];
      return '${date.day} ${months[date.month - 1]}';
    } catch (e) {
      return dateStr;
    }
  }

  Widget _buildSections(BuildContext context, List<Map<String, dynamic>> sections) {
    final iconMap = {
      'info': Icons.info_outline,
      'star': Icons.star_outline,
      'phone': Icons.phone_outlined,
      'schedule': Icons.schedule,
      'euro': Icons.euro,
      'location_on': Icons.location_on_outlined,
      'help': Icons.help_outline,
      'photo_library': Icons.photo_library_outlined,
      'article': Icons.article_outlined,
    };

    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Información',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          ...sections.map((section) {
            final iconName = section['icon'] as String? ?? 'article';
            final icon = iconMap[iconName] ?? Icons.article_outlined;

            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ExpansionTile(
                leading: Icon(icon, color: Theme.of(context).colorScheme.primary),
                title: Text(section['title'] ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text(
                  section['summary'] ?? '',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7)),
                ),
                children: [
                  if (section['image'] != null && section['image'].toString().isNotEmpty)
                    CachedNetworkImage(
                      imageUrl: section['image'],
                      height: 150,
                      width: double.infinity,
                      fit: BoxFit.cover,
                    ),
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(section['summary'] ?? ''),
                        const SizedBox(height: 12),
                        OutlinedButton.icon(
                          onPressed: () => _openUrl(section['url']),
                          icon: const Icon(Icons.open_in_browser),
                          label: const Text('Ver más en la web'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildGallery(BuildContext context, List<Map<String, dynamic>> gallery) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.photo_library, color: Theme.of(context).colorScheme.primary),
              const SizedBox(width: 8),
              Text(
                'Galería',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 120,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: gallery.length,
              itemBuilder: (context, index) {
                final image = gallery[index];
                return GestureDetector(
                  onTap: () => _showImageDialog(context, image['url'], image['title']),
                  child: Container(
                    width: 160,
                    margin: const EdgeInsets.only(right: 8),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: CachedNetworkImage(
                        imageUrl: image['url'] ?? '',
                        fit: BoxFit.cover,
                        placeholder: (_, __) => Container(
                          color: Theme.of(context).colorScheme.surfaceContainerHighest,
                          child: const Center(child: CircularProgressIndicator(strokeWidth: 2)),
                        ),
                        errorWidget: (_, __, ___) => Container(
                          color: Theme.of(context).colorScheme.surfaceContainerHighest,
                          child: const Icon(Icons.broken_image),
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNews(BuildContext context, List<Map<String, dynamic>> news) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.newspaper, color: Theme.of(context).colorScheme.primary),
              const SizedBox(width: 8),
              Text(
                'Noticias',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ...news.take(3).map((post) {
            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                leading: post['image'] != null && post['image'].toString().isNotEmpty
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: CachedNetworkImage(
                          imageUrl: post['image'],
                          width: 60,
                          height: 60,
                          fit: BoxFit.cover,
                        ),
                      )
                    : Container(
                        width: 60,
                        height: 60,
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.primaryContainer,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Icon(Icons.article, color: Theme.of(context).colorScheme.primary),
                      ),
                title: Text(post['title'] ?? '', maxLines: 2, overflow: TextOverflow.ellipsis),
                subtitle: Text(post['date'] ?? '', style: const TextStyle(fontSize: 12)),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => _openUrl(post['url']),
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildSchedule(BuildContext context, Map<String, dynamic> schedule) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primaryContainer,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(Icons.schedule, color: Theme.of(context).colorScheme.primary),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Horarios', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(schedule['text'] ?? '', style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.8))),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLocation(BuildContext context, Map<String, dynamic> location) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.primaryContainer,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(Icons.location_on, color: Theme.of(context).colorScheme.primary),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Ubicación', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold)),
                        if (location['address'] != null) ...[
                          const SizedBox(height: 4),
                          Text(location['address'], style: TextStyle(color: Theme.of(context).colorScheme.onSurface.withOpacity(0.8))),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
              if (location['directions_url'] != null) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _openUrl(location['map_url']),
                        icon: const Icon(Icons.map),
                        label: const Text('Ver mapa'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () => _openUrl(location['directions_url']),
                        icon: const Icon(Icons.directions),
                        label: const Text('Cómo llegar'),
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildContact(BuildContext context, Map<String, dynamic> contact) {
    final serverConfig = ref.read(serverConfigProvider);
    final serverUrl = serverConfig.serverUrl;

    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Contacto',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              if (contact['phone'] != null && contact['phone'].toString().isNotEmpty)
                FilledButton.icon(
                  onPressed: () => _openUrl('tel:${contact['phone']}'),
                  icon: const Icon(Icons.phone),
                  label: Text(contact['phone']),
                ),
              if (contact['whatsapp'] != null && contact['whatsapp'].toString().isNotEmpty)
                FilledButton.icon(
                  onPressed: () => _openUrl('https://wa.me/${contact['whatsapp'].toString().replaceAll(RegExp(r'[^\d]'), '')}'),
                  icon: const Icon(Icons.chat),
                  label: const Text('WhatsApp'),
                  style: FilledButton.styleFrom(backgroundColor: const Color(0xFF25D366)),
                ),
              if (contact['email'] != null && contact['email'].toString().isNotEmpty)
                OutlinedButton.icon(
                  onPressed: () => _openUrl('mailto:${contact['email']}'),
                  icon: const Icon(Icons.email),
                  label: const Text('Email'),
                ),
              // Botón para abrir formulario de contacto web
              OutlinedButton.icon(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => ContactWebViewScreen(
                        url: '$serverUrl/contacto',
                        title: 'Formulario de Contacto',
                      ),
                    ),
                  );
                },
                icon: const Icon(Icons.contact_mail),
                label: const Text('Formulario Web'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSocialLinks(BuildContext context, List<Map<String, dynamic>> links) {
    final iconMap = {
      'facebook': Icons.facebook,
      'instagram': Icons.camera_alt,
      'twitter': Icons.alternate_email,
      'youtube': Icons.play_circle,
      'tiktok': Icons.music_note,
      'linkedin': Icons.work,
      'whatsapp': Icons.chat,
      'telegram': Icons.send,
    };

    final colorMap = {
      'facebook': const Color(0xFF1877F2),
      'instagram': const Color(0xFFE4405F),
      'twitter': const Color(0xFF1DA1F2),
      'youtube': const Color(0xFFFF0000),
      'tiktok': const Color(0xFF000000),
      'linkedin': const Color(0xFF0A66C2),
      'whatsapp': const Color(0xFF25D366),
      'telegram': const Color(0xFF0088CC),
    };

    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Síguenos',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            children: links.map((link) {
              final network = link['network'] as String? ?? '';
              final icon = iconMap[network] ?? Icons.link;
              final color = colorMap[network] ?? Theme.of(context).colorScheme.primary;

              return IconButton(
                onPressed: () => _openUrl(link['url']),
                icon: Icon(icon, color: color),
                tooltip: network.toUpperCase(),
                style: IconButton.styleFrom(
                  backgroundColor: color.withOpacity(0.1),
                  padding: const EdgeInsets.all(12),
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildFallbackContent(BuildContext context, String siteName, String? logoUrl) {
    // Fallback con providers originales
    final businessInfoAsync = ref.watch(businessInfoProvider);

    return CustomScrollView(
      slivers: [
        SliverAppBar(
          expandedHeight: 200,
          pinned: true,
          flexibleSpace: FlexibleSpaceBar(
            title: Text(siteName, style: const TextStyle(fontWeight: FontWeight.bold)),
            background: _buildHeader(context, logoUrl, ''),
          ),
        ),
        SliverToBoxAdapter(
          child: Column(
            children: [
              // Acciones principales de la app
              _buildAppActions(context),
              businessInfoAsync.when(
                data: (info) => Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (info.description.isNotEmpty) ...[
                        Text(info.description, style: Theme.of(context).textTheme.bodyLarge),
                        const SizedBox(height: 24),
                      ],
                      if (info.phone.isNotEmpty)
                        ListTile(
                          leading: const Icon(Icons.phone),
                          title: Text(info.phone),
                          onTap: () => _openUrl('tel:${info.phone}'),
                        ),
                      if (info.email.isNotEmpty)
                        ListTile(
                          leading: const Icon(Icons.email),
                          title: Text(info.email),
                          onTap: () => _openUrl('mailto:${info.email}'),
                        ),
                      if (info.address.isNotEmpty)
                        ListTile(
                          leading: const Icon(Icons.location_on),
                          title: Text(info.address),
                        ),
                    ],
                  ),
                ),
                loading: () => const Padding(
                  padding: EdgeInsets.all(32),
                  child: Center(child: CircularProgressIndicator()),
                ),
                error: (_, __) => const Padding(
                  padding: EdgeInsets.all(32),
                  child: Center(child: Text('Error al cargar información')),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  void _showImageDialog(BuildContext context, String? url, String? title) {
    if (url == null) return;
    showDialog(
      context: context,
      builder: (context) => Dialog(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CachedNetworkImage(imageUrl: url, fit: BoxFit.contain),
            if (title != null && title.isNotEmpty)
              Padding(
                padding: const EdgeInsets.all(8),
                child: Text(title, textAlign: TextAlign.center),
              ),
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cerrar'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openUrl(String? url) async {
    if (url == null || url.isEmpty) return;
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }
}

/// Widget de tarjeta de acción para navegación interna
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
                      color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
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
class _ChangeBusinessScreen extends ConsumerStatefulWidget {
  final VoidCallback onComplete;

  const _ChangeBusinessScreen({required this.onComplete});

  @override
  ConsumerState<_ChangeBusinessScreen> createState() => _ChangeBusinessScreenState();
}

class _ChangeBusinessScreenState extends ConsumerState<_ChangeBusinessScreen> {
  bool _isScanning = false;
  bool _isLoading = false;
  String? _error;

  Future<void> _processQR(String code) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      // Parsear JSON del QR
      final jsonData = json.decode(code) as Map<String, dynamic>;
      String serverUrl = (jsonData['url'] as String? ?? '').replaceAll(r'\/', '/');

      if (serverUrl.isEmpty) {
        setState(() {
          _isLoading = false;
          _error = 'QR no válido: no contiene URL';
        });
        return;
      }

      // Limpiar URL
      if (serverUrl.endsWith('/')) {
        serverUrl = serverUrl.substring(0, serverUrl.length - 1);
      }

      // Probar conexión
      final fullUrl = '$serverUrl${ServerConfig.defaultApiNamespace}';
      final testClient = ApiClient(baseUrl: fullUrl);
      final response = await testClient.getSiteInfo().timeout(
        const Duration(seconds: 15),
      );

      if (response.success) {
        // Guardar nueva configuración
        await ServerConfig.setServerUrl(serverUrl);

        // Actualizar API client
        ref.read(apiClientProvider).updateBaseUrl(fullUrl);

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✓ Conectado a ${jsonData['name'] ?? serverUrl}'),
              backgroundColor: Colors.green,
            ),
          );
          widget.onComplete();
        }
      } else {
        setState(() {
          _isLoading = false;
          _error = 'No se pudo conectar al servidor';
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Error: $e';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isScanning) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Escanear QR'),
          backgroundColor: Colors.black,
          foregroundColor: Colors.white,
        ),
        body: Stack(
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
                  border: Border.all(color: Colors.white.withOpacity(0.5), width: 3),
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
                child: const Text(
                  'Escanea el código QR del nuevo negocio',
                  style: TextStyle(color: Colors.white70, fontSize: 14),
                  textAlign: TextAlign.center,
                ),
              ),
            ),
          ],
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Cambiar negocio')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.qr_code_scanner,
              size: 80,
              color: Theme.of(context).colorScheme.primary,
            ),
            const SizedBox(height: 24),
            Text(
              'Conectar a otro negocio',
              style: Theme.of(context).textTheme.headlineSmall,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              'Escanea el código QR de configuración del nuevo sitio web.',
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
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
                child: Text(_error!, style: TextStyle(color: Colors.red.shade700)),
              ),
              const SizedBox(height: 16),
            ],
            if (_isLoading)
              const CircularProgressIndicator()
            else
              FilledButton.icon(
                onPressed: () => setState(() => _isScanning = true),
                icon: const Icon(Icons.qr_code_scanner),
                label: const Text('Escanear QR'),
              ),
          ],
        ),
      ),
    );
  }
}
