import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../../core/providers/providers.dart';
import '../../core/widgets/common_widgets.dart';
import '../../core/config/dynamic_config.dart';
import '../../core/config/server_config.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/sync_provider.dart';
import '../../core/widgets/flavor_state_widgets.dart';
import '../../main_client.dart' show siteInfoProvider, clientAppConfigProvider;
import 'directory_screen.dart';

part 'info_screen_parts.dart';

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
  final VoidCallback? onBusinessChanged;

  const InfoScreen({super.key, this.onNavigateToTab, this.onBusinessChanged});

  @override
  ConsumerState<InfoScreen> createState() => _InfoScreenState();
}

class _InfoScreenState extends ConsumerState<InfoScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context);
  @override
  Widget build(BuildContext context) {
    final siteContentAsync = ref.watch(siteContentProvider);
    final siteInfo = ref.watch(siteInfoProvider);

    final siteName = siteInfo.valueOrNull?['name'] as String? ??
        AppLocalizations.of(context).defaultBusinessName;
    final logoUrl = siteInfo.valueOrNull?['logo_url'] as String?;

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(siteContentProvider);
          ref.invalidate(siteInfoProvider);
          ref.invalidate(clientAppConfigProvider);
        },
        child: siteContentAsync.when(
          data: (content) => _buildContent(context, content, siteName, logoUrl),
          loading: () => LoadingScreen(message: i18n.loadingInfo),
          error: (error, _) =>
              _buildFallbackContent(context, siteName, logoUrl),
        ),
      ),
    );
  }

  // ignore: unused_element
  Widget _buildContentWithSections(
    BuildContext context,
    Map<String, dynamic>? content,
    String siteName,
    String? logoUrl,
    List<Map<String, dynamic>> configuredSections,
  ) {
    if (content == null) {
      return _buildFallbackContent(context, siteName, logoUrl);
    }

    // Ordenar secciones por orden configurado
    final sortedSections = List<Map<String, dynamic>>.from(configuredSections);
    sortedSections.sort((a, b) {
      final orderA = a['order'] as int? ?? 0;
      final orderB = b['order'] as int? ?? 0;
      return orderA.compareTo(orderB);
    });

    final site = content['site'] as Map<String, dynamic>? ?? {};
    final sections =
        (content['sections'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final quickLinks =
        (content['quick_links'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final socialLinks =
        (content['social_links'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final contact = content['contact'] as Map<String, dynamic>? ?? {};
    final gallery =
        (content['gallery'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final news = (content['news'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final services =
        (content['services'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final schedule = content['schedule'] as Map<String, dynamic>? ?? {};
    final location = content['location'] as Map<String, dynamic>? ?? {};

    final displayName = site['name'] as String? ?? siteName;
    final tagline = site['tagline'] as String? ?? '';
    final siteLogoUrl = site['logo'] as String? ?? logoUrl;

    // Obtener logo dinámico según brillo del tema
    final brightness = Theme.of(context).brightness;
    final dynamicConfig = DynamicConfig();
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

        // Contenido dinámico basado en secciones configuradas
        SliverToBoxAdapter(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Acciones principales de la app (siempre visible)
              _buildAppActions(context),

              // Renderizar secciones configuradas en orden
              ...sortedSections.map((sectionConfig) {
                final sectionId = sectionConfig['id'] as String? ?? '';
                return _buildConfiguredSection(
                  context,
                  sectionId,
                  sectionConfig,
                  content: content,
                  quickLinks: quickLinks,
                  services: services,
                  sections: sections,
                  gallery: gallery,
                  news: news,
                  schedule: schedule,
                  location: location,
                  contact: contact,
                  socialLinks: socialLinks,
                );
              }).whereType<Widget>(),

              // Sección de ajustes (siempre visible)
              _buildSettingsSection(context),

              const SizedBox(height: 32),
            ],
          ),
        ),
      ],
    );
  }

  Widget? _buildConfiguredSection(
    BuildContext context,
    String sectionId,
    Map<String, dynamic> sectionConfig, {
    required Map<String, dynamic> content,
    required List<Map<String, dynamic>> quickLinks,
    required List<Map<String, dynamic>> services,
    required List<Map<String, dynamic>> sections,
    required List<Map<String, dynamic>> gallery,
    required List<Map<String, dynamic>> news,
    required Map<String, dynamic> schedule,
    required Map<String, dynamic> location,
    required Map<String, dynamic> contact,
    required List<Map<String, dynamic>> socialLinks,
  }) {
    switch (sectionId) {
      case 'header':
        // Header ya se muestra en el SliverAppBar
        return null;

      case 'quick_links':
        if (quickLinks.isNotEmpty) {
          return _buildQuickLinks(context, quickLinks);
        }
        return null;

      case 'about':
      case 'services':
        if (services.isNotEmpty) {
          return _buildServicesSection(context, services);
        }
        return null;

      case 'content':
        if (sections.isNotEmpty) {
          return _buildSections(context, sections);
        }
        return null;

      case 'gallery':
        if (gallery.isNotEmpty) {
          return _buildGallery(context, gallery);
        }
        return null;

      case 'news':
        if (news.isNotEmpty) {
          return _buildNews(context, news);
        }
        return null;

      case 'hours':
      case 'schedule':
        if (schedule['text'] != null &&
            schedule['text'].toString().isNotEmpty) {
          return _buildSchedule(context, schedule);
        }
        return null;

      case 'location':
        if (location['address'] != null || location['coordinates'] != null) {
          return _buildLocation(context, location);
        }
        return null;

      case 'contact':
        if (contact.values.any((v) => v != null && v.toString().isNotEmpty)) {
          return _buildContact(context, contact);
        }
        return null;

      case 'social':
        if (socialLinks.isNotEmpty) {
          return _buildSocialLinks(context, socialLinks);
        }
        return null;

      case 'team':
      case 'faq':
      default:
        // Secciones custom o no implementadas
        return _buildCustomSection(context, sectionConfig);
    }
  }

  Widget _buildCustomSection(
      BuildContext context, Map<String, dynamic> sectionConfig) {
    final label = sectionConfig['label'] as String? ?? 'Sección';
    final iconName = sectionConfig['icon'] as String? ?? 'article';

    // Map simple de iconos
    final iconMap = {
      'info': Icons.info_outline,
      'article': Icons.article_outlined,
      'help': Icons.help_outline,
      'people': Icons.people_outline,
      'groups': Icons.groups_outlined,
    };

    final icon = iconMap[iconName] ?? Icons.article_outlined;

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
                  Icon(icon, color: Theme.of(context).colorScheme.primary),
                  const SizedBox(width: 8),
                  Text(
                    label,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                'Contenido de $label próximamente disponible',
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
      ),
    );
  }

  Widget _buildContent(BuildContext context, Map<String, dynamic>? content,
      String siteName, String? logoUrl) {
    if (content == null) {
      return _buildFallbackContent(context, siteName, logoUrl);
    }

    // Obtener configuración dinámica para filtrar secciones
    final dynamicConfig = DynamicConfig();

    final site = content['site'] as Map<String, dynamic>? ?? {};
    final sections =
        (content['sections'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final quickLinks =
        (content['quick_links'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final socialLinks =
        (content['social_links'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final contact = content['contact'] as Map<String, dynamic>? ?? {};
    final gallery =
        (content['gallery'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final news = (content['news'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final services =
        (content['services'] as List?)?.cast<Map<String, dynamic>>() ?? [];
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
              if ((location['address'] != null ||
                      location['coordinates'] != null) &&
                  dynamicConfig.showLocation)
                _buildLocation(context, location),

              // Contacto - respeta configuración
              if (contact.values
                      .any((v) => v != null && v.toString().isNotEmpty) &&
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
        color: Theme.of(context)
            .colorScheme
            .surfaceContainerHighest
            .withOpacity(0.5),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          ListTile(
            leading: Icon(Icons.swap_horiz,
                color: Theme.of(context).colorScheme.primary),
            title: Text(i18n.infoChangeBusinessTitle),
            subtitle: Text(i18n.infoConnectOtherSiteSubtitle),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _showChangeBusinessDialog(context),
          ),
          ListTile(
            leading:
                Icon(Icons.sync, color: Theme.of(context).colorScheme.primary),
            title: Text(i18n.commonResyncTitle),
            subtitle: Text(i18n.commonResyncSubtitle),
            trailing: const Icon(Icons.chevron_right),
            onTap: () async {
              final messenger = ScaffoldMessenger.of(context);
              messenger.showSnackBar(
                SnackBar(
                  content: Text(i18n.commonResyncInProgress),
                  behavior: SnackBarBehavior.floating,
                ),
              );
              final result = await ref.read(syncProvider.notifier).refresh();
              if (!mounted) return;
              if (result.success) {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(i18n.commonResyncSuccess),
                    backgroundColor: Colors.green,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              } else {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(i18n.commonResyncError),
                    backgroundColor: Colors.red,
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              }
            },
          ),
        ],
      ),
    );
  }

  Future<void> _showChangeBusinessDialog(BuildContext context) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.infoChangeBusinessDialogTitle),
        content: Text(i18n.infoChangeBusinessDialogBody),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(i18n.commonCancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text(i18n.commonChange),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      // Navegar a pantalla de setup
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => ChangeBusinessScreen(
            onComplete: () {
              Navigator.pop(context);
              // Recargar la app
              ref.invalidate(clientAppConfigProvider);
              widget.onBusinessChanged?.call();
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
    return Icon(Icons.landscape,
        size: 100, color: Colors.white.withOpacity(0.3));
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

  Widget _buildQuickLinks(
      BuildContext context, List<Map<String, dynamic>> links) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            i18n.infoQuickLinks,
            style: Theme.of(context)
                .textTheme
                .titleMedium
                ?.copyWith(fontWeight: FontWeight.bold),
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

  Widget _buildServicesSection(
      BuildContext context, List<Map<String, dynamic>> services) {
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
                i18n.infoOurExperiences,
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.bold),
              ),
            ],
          ),
          if (services.isEmpty)
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Text(
                i18n.infoNoExperiencesNextMonths,
                style: TextStyle(
                    color: Theme.of(context)
                        .colorScheme
                        .onSurface
                        .withOpacity(0.6)),
              ),
            )
          else
            const SizedBox(height: 12),
          ...services.map((service) {
            final nextDates =
                (service['next_dates'] as List?)?.cast<String>() ?? [];
            final totalDays = service['total_available_days'] as int? ?? 0;

            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ExpansionTile(
                leading: service['image'] != null &&
                        service['image'].toString().isNotEmpty
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
                            color:
                                Theme.of(context).colorScheme.primaryContainer,
                            child: Icon(Icons.hiking,
                                color: Theme.of(context)
                                    .colorScheme
                                    .onPrimaryContainer),
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
                        child: Icon(Icons.hiking,
                            color: Theme.of(context)
                                .colorScheme
                                .onPrimaryContainer),
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
                    if (service['duration'] != null &&
                        service['duration'].toString().isNotEmpty)
                      Text(
                        service['duration'],
                        style: TextStyle(
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withOpacity(0.6)),
                      ),
                  ],
                ),
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (service['description'] != null &&
                            service['description'].toString().isNotEmpty) ...[
                          Text(
                            service['description'],
                            style: Theme.of(context).textTheme.bodyMedium,
                          ),
                          const SizedBox(height: 16),
                        ],
                        if (nextDates.isNotEmpty) ...[
                          Text(
                            i18n.infoUpcomingDates,
                            style: Theme.of(context)
                                .textTheme
                                .titleSmall
                                ?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const SizedBox(height: 8),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: nextDates.map((date) {
                              return Chip(
                                avatar:
                                    const Icon(Icons.calendar_today, size: 16),
                                label: Text(
                                  _formatDate(date),
                                  style: const TextStyle(fontSize: 12),
                                ),
                                backgroundColor: Theme.of(context)
                                    .colorScheme
                                    .secondaryContainer,
                              );
                            }).toList(),
                          ),
                          if (totalDays > nextDates.length)
                            Padding(
                              padding: const EdgeInsets.only(top: 8),
                              child: Text(
                                i18n.infoMoreDatesCount(
                                    totalDays - nextDates.length),
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Theme.of(context)
                                      .colorScheme
                                      .onSurface
                                      .withOpacity(0.6),
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
                          label: Text(i18n.reserveCta),
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
        'Ene',
        'Feb',
        'Mar',
        'Abr',
        'May',
        'Jun',
        'Jul',
        'Ago',
        'Sep',
        'Oct',
        'Nov',
        'Dic'
      ];
      return '${date.day} ${months[date.month - 1]}';
    } catch (e) {
      return dateStr;
    }
  }

  Widget _buildSections(
      BuildContext context, List<Map<String, dynamic>> sections) {
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
            i18n.infoSectionTitle,
            style: Theme.of(context)
                .textTheme
                .titleMedium
                ?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          ...sections.map((section) {
            final iconName = section['icon'] as String? ?? 'article';
            final icon = iconMap[iconName] ?? Icons.article_outlined;

            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ExpansionTile(
                leading:
                    Icon(icon, color: Theme.of(context).colorScheme.primary),
                title: Text(section['title'] ?? '',
                    style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text(
                  section['summary'] ?? '',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                      color: Theme.of(context)
                          .colorScheme
                          .onSurface
                          .withOpacity(0.7)),
                ),
                children: [
                  if (section['image'] != null &&
                      section['image'].toString().isNotEmpty)
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
                          label: Text(i18n.infoSeeMoreOnWeb),
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

  Widget _buildGallery(
      BuildContext context, List<Map<String, dynamic>> gallery) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.photo_library,
                  color: Theme.of(context).colorScheme.primary),
              const SizedBox(width: 8),
              Text(
                i18n.infoGalleryTitle,
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.bold),
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
                  onTap: () =>
                      _showImageDialog(context, image['url'], image['title']),
                  child: Container(
                    width: 160,
                    margin: const EdgeInsets.only(right: 8),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: CachedNetworkImage(
                        imageUrl: image['url'] ?? '',
                        fit: BoxFit.cover,
                        placeholder: (_, __) => Container(
                          color: Theme.of(context)
                              .colorScheme
                              .surfaceContainerHighest,
                          child: const FlavorLoadingState(),
                        ),
                        errorWidget: (_, __, ___) => Container(
                          color: Theme.of(context)
                              .colorScheme
                              .surfaceContainerHighest,
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
              Icon(Icons.newspaper,
                  color: Theme.of(context).colorScheme.primary),
              const SizedBox(width: 8),
              Text(
                i18n.infoNewsTitle,
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.bold),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ...news.take(3).map((post) {
            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                leading: post['image'] != null &&
                        post['image'].toString().isNotEmpty
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
                        child: Icon(Icons.article,
                            color: Theme.of(context).colorScheme.primary),
                      ),
                title: Text(post['title'] ?? '',
                    maxLines: 2, overflow: TextOverflow.ellipsis),
                subtitle: Text(post['date'] ?? '',
                    style: const TextStyle(fontSize: 12)),
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
                child: Icon(Icons.schedule,
                    color: Theme.of(context).colorScheme.primary),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(i18n.infoSchedulesLabel,
                        style: Theme.of(context)
                            .textTheme
                            .titleSmall
                            ?.copyWith(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(schedule['text'] ?? '',
                        style: TextStyle(
                            color: Theme.of(context)
                                .colorScheme
                                .onSurface
                                .withOpacity(0.8))),
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
                    child: Icon(Icons.location_on,
                        color: Theme.of(context).colorScheme.primary),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(i18n.infoLocationLabel,
                            style: Theme.of(context)
                                .textTheme
                                .titleSmall
                                ?.copyWith(fontWeight: FontWeight.bold)),
                        if (location['address'] != null) ...[
                          const SizedBox(height: 4),
                          Text(location['address'],
                              style: TextStyle(
                                  color: Theme.of(context)
                                      .colorScheme
                                      .onSurface
                                      .withOpacity(0.8))),
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
                        label: Text(i18n.infoViewMap),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () => _openUrl(location['directions_url']),
                        icon: const Icon(Icons.directions),
                        label: Text(i18n.infoHowToGetThere),
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
            i18n.infoContactTitle,
            style: Theme.of(context)
                .textTheme
                .titleMedium
                ?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              if (contact['phone'] != null &&
                  contact['phone'].toString().isNotEmpty)
                FilledButton.icon(
                  onPressed: () => _openUrl('tel:${contact['phone']}'),
                  icon: const Icon(Icons.phone),
                  label: Text(contact['phone']),
                ),
              if (contact['whatsapp'] != null &&
                  contact['whatsapp'].toString().isNotEmpty)
                FilledButton.icon(
                  onPressed: () => _openUrl(
                      'https://wa.me/${contact['whatsapp'].toString().replaceAll(RegExp(r'[^\d]'), '')}'),
                  icon: const Icon(Icons.chat),
                  label: Text(i18n.infoWhatsAppLabel),
                  style: FilledButton.styleFrom(
                      backgroundColor: const Color(0xFF25D366)),
                ),
              if (contact['email'] != null &&
                  contact['email'].toString().isNotEmpty)
                OutlinedButton.icon(
                  onPressed: () => _openUrl('mailto:${contact['email']}'),
                  icon: const Icon(Icons.email),
                  label: Text(i18n.infoEmailLabel),
                ),
              // Botón para abrir formulario de contacto web
              OutlinedButton.icon(
                onPressed: () => _openUrl('$serverUrl/contacto'),
                icon: const Icon(Icons.contact_mail),
                label: Text(i18n.infoWebFormLabel),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSocialLinks(
      BuildContext context, List<Map<String, dynamic>> links) {
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
            i18n.infoFollowUsTitle,
            style: Theme.of(context)
                .textTheme
                .titleMedium
                ?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            children: links.map((link) {
              final network = link['network'] as String? ?? '';
              final icon = iconMap[network] ?? Icons.link;
              final color =
                  colorMap[network] ?? Theme.of(context).colorScheme.primary;

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

  Widget _buildFallbackContent(
      BuildContext context, String siteName, String? logoUrl) {
    // Fallback con providers originales
    final businessInfoAsync = ref.watch(businessInfoProvider);

    return CustomScrollView(
      slivers: [
        SliverAppBar(
          expandedHeight: 200,
          pinned: true,
          flexibleSpace: FlexibleSpaceBar(
            title: Text(siteName,
                style: const TextStyle(fontWeight: FontWeight.bold)),
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
                        Text(info.description,
                            style: Theme.of(context).textTheme.bodyLarge),
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
                  child: FlavorLoadingState(),
                ),
                error: (_, __) => Padding(
                  padding: const EdgeInsets.all(32),
                  child: Center(child: Text(i18n.infoLoadError)),
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
              child: Text(i18n.commonClose),
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
