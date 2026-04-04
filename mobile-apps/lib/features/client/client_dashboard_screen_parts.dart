part of 'client_dashboard_screen.dart';

class _UserProfileHeader extends ConsumerWidget {
  final VoidCallback? onNotificationsTap;

  const _UserProfileHeader({this.onNotificationsTap});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profileAsync = ref.watch(userProfileProvider);
    final notificationsAsync = ref.watch(userNotificationsProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return profileAsync.when(
      data: (profile) {
        if (profile == null) return const SizedBox.shrink();

        final unreadCount = notificationsAsync.whenOrNull(
              data: (notifications) => notifications
                  .where((notification) => !notification.isRead)
                  .length,
            ) ??
            0;

        return Semantics(
          label:
              'Perfil de ${profile.displayName}, nivel ${profile.level}, ${profile.points} puntos',
          child: Row(
            children: [
              // Avatar con indicador de nivel
              _buildAvatar(context, profile, colorScheme),

              const SizedBox(width: 12),

              // Nombre y nivel
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ExcludeSemantics(
                      child: Text(
                        profile.displayName,
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(height: 4),
                    _buildLevelBadge(context, profile, colorScheme),
                  ],
                ),
              ),

              // Boton de notificaciones
              Semantics(
                button: true,
                label:
                    'Notificaciones${unreadCount > 0 ? ', $unreadCount sin leer' : ''}',
                child: Badge(
                  isLabelVisible: unreadCount > 0,
                  label: Text(
                    unreadCount > 99 ? '99+' : unreadCount.toString(),
                    style: const TextStyle(fontSize: 10),
                  ),
                  child: IconButton(
                    onPressed: onNotificationsTap,
                    icon: const Icon(Icons.notifications_outlined),
                    style: IconButton.styleFrom(
                      backgroundColor: colorScheme.surfaceContainerHighest,
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
      loading: () => _buildSkeletonHeader(colorScheme),
      error: (_, __) => const SizedBox.shrink(),
    );
  }

  Widget _buildAvatar(
      BuildContext context, UserProfile profile, ColorScheme colorScheme) {
    return Stack(
      children: [
        ExcludeSemantics(
          child: CircleAvatar(
            radius: 28,
            backgroundColor: colorScheme.primaryContainer,
            backgroundImage: profile.avatarUrl != null
                ? NetworkImage(profile.avatarUrl!)
                : null,
            child: profile.avatarUrl == null
                ? Text(
                    profile.displayName.isNotEmpty
                        ? profile.displayName[0].toUpperCase()
                        : 'U',
                    style: TextStyle(
                      color: colorScheme.onPrimaryContainer,
                      fontWeight: FontWeight.bold,
                      fontSize: 24,
                    ),
                  )
                : null,
          ),
        ),
        // Indicador de nivel
        Positioned(
          right: 0,
          bottom: 0,
          child: ExcludeSemantics(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: colorScheme.primary,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: colorScheme.surface,
                  width: 2,
                ),
              ),
              child: Text(
                '${profile.level}',
                style: TextStyle(
                  color: colorScheme.onPrimary,
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildLevelBadge(
      BuildContext context, UserProfile profile, ColorScheme colorScheme) {
    return ExcludeSemantics(
      child: Row(
        children: [
          Icon(
            Icons.star,
            size: 14,
            color: Colors.amber[700],
          ),
          const SizedBox(width: 4),
          Text(
            '${profile.points} pts',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: colorScheme.onSurfaceVariant,
                  fontWeight: FontWeight.w500,
                ),
          ),
          const SizedBox(width: 8),
          // Barra de progreso mini
          SizedBox(
            width: 60,
            height: 4,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(2),
              child: LinearProgressIndicator(
                value: profile.levelProgress,
                backgroundColor: colorScheme.surfaceContainerHighest,
                color: Colors.amber[700],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSkeletonHeader(ColorScheme colorScheme) {
    return Row(
      children: [
        Container(
          width: 56,
          height: 56,
          decoration: BoxDecoration(
            color: colorScheme.surfaceContainerHighest,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 120,
                height: 18,
                decoration: BoxDecoration(
                  color: colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const SizedBox(height: 8),
              Container(
                width: 80,
                height: 14,
                decoration: BoxDecoration(
                  color: colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

/// Seccion de saludo personalizado
class _GreetingSection extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final colorScheme = Theme.of(context).colorScheme;
    final hour = DateTime.now().hour;

    String greeting;
    IconData greetingIcon;

    if (hour < 12) {
      greeting = 'Buenos dias';
      greetingIcon = Icons.wb_sunny_outlined;
    } else if (hour < 19) {
      greeting = 'Buenas tardes';
      greetingIcon = Icons.wb_twilight_outlined;
    } else {
      greeting = 'Buenas noches';
      greetingIcon = Icons.nightlight_outlined;
    }

    return Semantics(
      label: '$greeting, ${_formatDate(DateTime.now(), context)}',
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [
              colorScheme.primaryContainer,
              colorScheme.secondaryContainer,
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                ExcludeSemantics(
                  child: Icon(
                    greetingIcon,
                    color: colorScheme.onPrimaryContainer,
                    size: 28,
                  ),
                ),
                const SizedBox(width: 12),
                ExcludeSemantics(
                  child: Text(
                    greeting,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: colorScheme.onPrimaryContainer,
                        ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            ExcludeSemantics(
              child: Text(
                _formatDate(DateTime.now(), context),
                style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                      color: colorScheme.onPrimaryContainer.withOpacity(0.8),
                    ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime date, BuildContext context) {
    final locale = Localizations.localeOf(context).toLanguageTag();
    return DateFormat('EEEE, d \'de\' MMMM', locale).format(date);
  }
}

/// Seccion de metricas mejorada
class _EnhancedMetricsSection extends ConsumerWidget {
  final Function(String)? onNavigateToModule;

  const _EnhancedMetricsSection({this.onNavigateToModule});

  /// Mapea el ID de la estadística al módulo correspondiente
  String? _getModuleFromStatisticId(String statisticId) {
    final mapping = {
      'my_orders': 'woocommerce',
      'gc_pedidos': 'grupos_consumo',
      'bt_servicios': 'banco_tiempo',
      'marketplace_anuncios': 'marketplace',
      'eventos_proximos': 'eventos',
      'socios_total': 'socios',
      'foros_posts': 'foros',
      'red_social': 'red_social',
      'reservas': 'reservas',
      'carpooling': 'carpooling',
      'comunidades': 'comunidades',
    };
    return mapping[statisticId];
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final metricsAsync = ref.watch(enhancedUserMetricsProvider);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Semantics(
          header: true,
          child: Text(
            'Mi Resumen',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 12),
        metricsAsync.when(
          data: (statistics) => ClientStatsGrid(
            statistics: statistics,
            onStatisticTap: (statistic) {
              Haptics.light();
              final moduleId = _getModuleFromStatisticId(statistic.id);
              if (moduleId != null && onNavigateToModule != null) {
                onNavigateToModule!(moduleId);
              }
            },
          ),
          loading: () => const ClientStatsGrid(statistics: [], isLoading: true),
          error: (error, stack) => const Card(
            child: Padding(
              padding: EdgeInsets.all(16),
              child: Text('Error al cargar metricas'),
            ),
          ),
        ),
      ],
    );
  }
}

/// Seccion de accesos rapidos
class _QuickActionsSection extends ConsumerWidget {
  final Function(String)? onNavigateToModule;

  const _QuickActionsSection({this.onNavigateToModule});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeConfigAsync = ref.watch(clientHomeConfigProvider);
    final modulesAsync = ref.watch(adminModulesProvider);

    return homeConfigAsync.when(
      data: (homeConfig) {
        final configuredActions = _buildQuickActionsFromConfig(homeConfig);
        if (configuredActions.isNotEmpty) {
          return QuickActionsSection(
            title: 'Accesos rapidos',
            actions: configuredActions,
            onActionTap: (action) {
              Haptics.selection();
              if (action.route != null) {
                onNavigateToModule?.call(action.route!);
              }
            },
            gridColumns: 4,
          );
        }

        return modulesAsync.when(
          data: (modules) {
            final actions = _buildQuickActions(modules);
            return QuickActionsSection(
              title: 'Accesos rapidos',
              actions: actions,
              onActionTap: (action) {
                Haptics.selection();
                if (action.route != null) {
                  onNavigateToModule?.call(action.route!);
                }
              },
              gridColumns: 4,
            );
          },
          loading: () => const SizedBox(
            height: 100,
            child: FlavorLoadingState(),
          ),
          error: (_, __) => const SizedBox.shrink(),
        );
      },
      loading: () => modulesAsync.when(
        data: (modules) {
          final actions = _buildQuickActions(modules);
          return QuickActionsSection(
            title: 'Accesos rapidos',
            actions: actions,
            onActionTap: (action) {
              Haptics.selection();
              if (action.route != null) {
                onNavigateToModule?.call(action.route!);
              }
            },
            gridColumns: 4,
          );
        },
        loading: () => const SizedBox(
          height: 100,
          child: FlavorLoadingState(),
        ),
        error: (_, __) => const SizedBox.shrink(),
      ),
      error: (_, __) => modulesAsync.when(
        data: (modules) {
          final actions = _buildQuickActions(modules);
          return QuickActionsSection(
            title: 'Accesos rapidos',
            actions: actions,
            onActionTap: (action) {
              Haptics.selection();
              if (action.route != null) {
                onNavigateToModule?.call(action.route!);
              }
            },
            gridColumns: 4,
          );
        },
        loading: () => const SizedBox(
          height: 100,
          child: FlavorLoadingState(),
        ),
        error: (_, __) => const SizedBox.shrink(),
      ),
    );
  }

  List<QuickAction> _buildQuickActionsFromConfig(Map<String, dynamic> config) {
    final raw = config['quick_actions'];
    if (raw is! List) {
      return const <QuickAction>[];
    }

    final actions = <QuickAction>[];
    for (final item in raw) {
      if (item is! Map) {
        continue;
      }
      final map = Map<String, dynamic>.from(item);
      final moduleId = (map['module_id'] as String? ?? '').trim();
      if (moduleId.isEmpty) {
        continue;
      }

      actions.add(QuickAction(
        id: moduleId,
        label: (map['label'] as String? ?? moduleId).trim(),
        iconName: (map['icon'] as String? ?? 'star').trim(),
        colorHex: _colorForModule(moduleId),
        route: moduleId,
      ));
    }

    return actions;
  }

  String _colorForModule(String moduleId) {
    switch (moduleId) {
      case 'eventos':
        return '#E91E63';
      case 'marketplace':
        return '#FF9800';
      case 'grupos_consumo':
        return '#4CAF50';
      case 'banco_tiempo':
        return '#009688';
      case 'socios':
        return '#3F51B5';
      case 'reservas':
        return '#2196F3';
      case 'foros':
        return '#673AB7';
      case 'incidencias':
        return '#F44336';
      case 'tramites':
        return '#607D8B';
      default:
        return '#795548';
    }
  }

  List<QuickAction> _buildQuickActions(List<String> modules) {
    final actions = <QuickAction>[];

    // Reservas (siempre disponible)
    actions.add(const QuickAction(
      id: 'reservations',
      label: 'Reservar',
      iconName: 'calendar_today',
      colorHex: '#2196F3',
      route: 'reservations',
    ));

    // Chat IA
    actions.add(const QuickAction(
      id: 'chat',
      label: 'Chat IA',
      iconName: 'smart_toy',
      colorHex: '#9C27B0',
      route: 'chat',
    ));

    // Grupos de Consumo
    if (modules.contains('grupos_consumo') ||
        modules.contains('grupos-consumo')) {
      actions.add(const QuickAction(
        id: 'grupos_consumo',
        label: 'Grupos',
        iconName: 'shopping_basket',
        colorHex: '#4CAF50',
        route: 'grupos_consumo',
        badgeCount: 2,
      ));
    }

    // Banco de Tiempo
    if (modules.contains('banco_tiempo') || modules.contains('banco-tiempo')) {
      actions.add(const QuickAction(
        id: 'banco_tiempo',
        label: 'Tiempo',
        iconName: 'volunteer_activism',
        colorHex: '#009688',
        route: 'banco_tiempo',
      ));
    }

    // Marketplace
    if (modules.contains('marketplace')) {
      actions.add(const QuickAction(
        id: 'marketplace',
        label: 'Mercado',
        iconName: 'storefront',
        colorHex: '#FF9800',
        route: 'marketplace',
      ));
    }

    // Eventos
    if (modules.contains('eventos')) {
      actions.add(const QuickAction(
        id: 'eventos',
        label: 'Eventos',
        iconName: 'event',
        colorHex: '#E91E63',
        route: 'eventos',
        badgeCount: 3,
      ));
    }

    // Biblioteca
    if (modules.contains('biblioteca')) {
      actions.add(const QuickAction(
        id: 'biblioteca',
        label: 'Biblioteca',
        iconName: 'article',
        colorHex: '#795548',
        route: 'biblioteca',
      ));
    }

    // Incidencias
    if (modules.contains('incidencias')) {
      actions.add(const QuickAction(
        id: 'incidencias',
        label: 'Incidencias',
        iconName: 'notifications',
        colorHex: '#F44336',
        route: 'incidencias',
      ));
    }

    return actions;
  }
}

/// Seccion de actividad reciente mejorada
class _EnhancedActivitySection extends ConsumerWidget {
  final Function(UserActivity)? onActivityTap;

  const _EnhancedActivitySection({this.onActivityTap});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final activityAsync = ref.watch(userActivityProvider);

    return activityAsync.when(
      data: (activities) => ActivitySection(
        title: 'Actividad reciente',
        activities: activities,
        onActivityTap: onActivityTap,
        maxItems: 5,
        onViewAll: () {
          Haptics.light();
          // Navegar a pantalla de actividad completa
        },
      ),
      loading: () => const ActivitySection(
        activities: [],
        isLoading: true,
      ),
      error: (_, __) => const SizedBox.shrink(),
    );
  }
}

/// Seccion de graficos mejorada con animaciones y graficos interactivos
class _EnhancedChartsSection extends StatelessWidget {
  final Function(String)? onNavigateToModule;

  const _EnhancedChartsSection({this.onNavigateToModule});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Semantics(
          header: true,
          child: Text(
            'Mi Actividad',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 12),

        // Grafico de actividad semanal animado
        AnimatedWeeklyActivityChart(
          title: 'Actividad de los ultimos 7 dias',
          onBarTap: (activityData) {
            Haptics.selection();
            debugPrint('Tap on day: ${activityData.dayLabel}');
          },
        ),

        const SizedBox(height: 16),

        // Grafico de distribucion circular animado
        AnimatedDistributionPieChart(
          title: 'Distribucion por actividad',
          onSectionTap: (distributionData) {
            Haptics.selection();
            debugPrint('Tap on section: ${distributionData.label}');
            // Navegar al modulo correspondiente si es posible
            final moduleRoutes = {
              'Reservas': 'reservations',
              'Pedidos': 'grupos_consumo',
              'Intercambios': 'banco_tiempo',
              'Eventos': 'eventos',
            };
            final route = moduleRoutes[distributionData.label];
            if (route != null) {
              onNavigateToModule?.call(route);
            }
          },
        ),
      ],
    );
  }
}

/// Seccion de widgets de modulos (proximas reservas, mensajes, etc.)
class _ModuleWidgetsSection extends ConsumerWidget {
  final Function(String)? onNavigateToModule;

  const _ModuleWidgetsSection({this.onNavigateToModule});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeConfigAsync = ref.watch(clientHomeConfigProvider);

    return homeConfigAsync.when(
      data: (config) {
        final rawWidgets = config['home_widgets'];
        final widgets = rawWidgets is List
            ? rawWidgets
                .whereType<Map>()
                .map((e) => Map<String, dynamic>.from(e))
                .toList()
            : const <Map<String, dynamic>>[];

        if (widgets.isNotEmpty) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Semantics(
                header: true,
                child: Text(
                  'Widgets de inicio',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ),
              const SizedBox(height: 12),
              ...widgets.map((widget) {
                final moduleId = (widget['module_id'] as String? ?? '').trim();
                final title = (widget['title'] as String? ?? moduleId).trim();
                final icon = (widget['icon'] as String? ?? 'widgets').trim();
                return Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  child: ListTile(
                    leading: Icon(_iconFromName(icon)),
                    title: Text(title),
                    subtitle: Text(moduleId.replaceAll('_', ' ')),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: moduleId.isEmpty
                        ? null
                        : () {
                            Haptics.light();
                            onNavigateToModule?.call(moduleId);
                          },
                  ),
                );
              }),
            ],
          );
        }

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Semantics(
              header: true,
              child: Text(
                'Proximas citas',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
            ),
            const SizedBox(height: 12),
            _UpcomingReservationsWidget(
              onViewAll: () {
                Haptics.light();
                onNavigateToModule?.call('my_tickets');
              },
            ),
          ],
        );
      },
      loading: () => const SizedBox.shrink(),
      error: (_, __) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Semantics(
            header: true,
            child: Text(
              'Proximas citas',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
          ),
          const SizedBox(height: 12),
          _UpcomingReservationsWidget(
            onViewAll: () {
              Haptics.light();
              onNavigateToModule?.call('my_tickets');
            },
          ),
        ],
      ),
    );
  }

  IconData _iconFromName(String iconName) {
    switch (iconName) {
      case 'event':
        return Icons.event;
      case 'store':
      case 'storefront':
        return Icons.storefront;
      case 'shopping_basket':
        return Icons.shopping_basket;
      case 'handyman':
        return Icons.handyman;
      case 'card_membership':
        return Icons.card_membership;
      case 'event_available':
        return Icons.event_available;
      case 'people':
        return Icons.people;
      case 'forum':
        return Icons.forum;
      case 'report_problem':
        return Icons.report_problem;
      case 'description':
        return Icons.description;
      default:
        return Icons.widgets_outlined;
    }
  }
}

/// Widget de proximas reservas
class _UpcomingReservationsWidget extends StatelessWidget {
  final VoidCallback? onViewAll;

  const _UpcomingReservationsWidget({this.onViewAll});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    // Datos de ejemplo - en produccion vendrian del API
    final reservas = [
      _ReservationPreview(
        title: 'Reserva de espacio',
        date: DateTime.now().add(const Duration(days: 2)),
        location: 'Sala de reuniones A',
        color: Colors.blue,
      ),
      _ReservationPreview(
        title: 'Clase de yoga',
        date: DateTime.now().add(const Duration(days: 5)),
        location: 'Gimnasio comunitario',
        color: Colors.green,
      ),
    ];

    if (reservas.isEmpty) {
      return Semantics(
        label: 'No tienes reservas proximas',
        child: Card(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                Icon(
                  Icons.calendar_today_outlined,
                  size: 48,
                  color: colorScheme.onSurfaceVariant.withOpacity(0.5),
                ),
                const SizedBox(height: 12),
                Text(
                  'Sin reservas proximas',
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        color: colorScheme.onSurfaceVariant,
                      ),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(
          color: colorScheme.outlineVariant,
          width: 1,
        ),
      ),
      child: Column(
        children: [
          ...reservas
              .map((reserva) => _ReservationPreviewTile(reserva: reserva)),
          if (onViewAll != null)
            ListTile(
              title: Text(
                'Ver todas mis reservas',
                style: TextStyle(
                  color: colorScheme.primary,
                  fontWeight: FontWeight.w500,
                ),
                textAlign: TextAlign.center,
              ),
              onTap: onViewAll,
            ),
        ],
      ),
    );
  }
}

class _ReservationPreview {
  final String title;
  final DateTime date;
  final String location;
  final Color color;

  const _ReservationPreview({
    required this.title,
    required this.date,
    required this.location,
    required this.color,
  });
}

class _ReservationPreviewTile extends StatelessWidget {
  final _ReservationPreview reserva;

  const _ReservationPreviewTile({required this.reserva});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final timeFormat = DateFormat('HH:mm');

    return ListTile(
      leading: Container(
        width: 48,
        height: 48,
        decoration: BoxDecoration(
          color: reserva.color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              reserva.date.day.toString(),
              style: TextStyle(
                color: reserva.color,
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            Text(
              DateFormat('MMM', 'es').format(reserva.date).toUpperCase(),
              style: TextStyle(
                color: reserva.color,
                fontSize: 10,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
      title: Text(
        reserva.title,
        style: const TextStyle(fontWeight: FontWeight.w600),
      ),
      subtitle: Row(
        children: [
          Icon(
            Icons.location_on_outlined,
            size: 14,
            color: colorScheme.onSurfaceVariant,
          ),
          const SizedBox(width: 4),
          Expanded(
            child: Text(
              reserva.location,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
      trailing: Text(
        timeFormat.format(reserva.date),
        style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: colorScheme.onSurfaceVariant,
            ),
      ),
    );
  }
}

/// Sheet de notificaciones
class _NotificationsSheet extends ConsumerWidget {
  const _NotificationsSheet();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notificationsAsync = ref.watch(userNotificationsProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      minChildSize: 0.4,
      maxChildSize: 0.9,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: colorScheme.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            children: [
              // Handle
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: colorScheme.onSurfaceVariant.withOpacity(0.3),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              // Titulo
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Text(
                      'Notificaciones',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const Spacer(),
                    TextButton(
                      onPressed: () {
                        Haptics.light();
                        // Marcar todas como leidas
                      },
                      child: const Text('Marcar leidas'),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              // Lista de notificaciones
              Expanded(
                child: notificationsAsync.when(
                  data: (notifications) {
                    if (notifications.isEmpty) {
                      return Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.notifications_none,
                              size: 64,
                              color:
                                  colorScheme.onSurfaceVariant.withOpacity(0.5),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              'Sin notificaciones',
                              style: Theme.of(context)
                                  .textTheme
                                  .bodyLarge
                                  ?.copyWith(
                                    color: colorScheme.onSurfaceVariant,
                                  ),
                            ),
                          ],
                        ),
                      );
                    }

                    return ListView.separated(
                      controller: scrollController,
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      itemCount: notifications.length,
                      separatorBuilder: (_, __) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final notification = notifications[index];
                        return ListTile(
                          leading: CircleAvatar(
                            backgroundColor: notification.isRead
                                ? colorScheme.surfaceContainerHighest
                                : colorScheme.primaryContainer,
                            child: Icon(
                              _getIconForNotificationType(notification.type),
                              color: notification.isRead
                                  ? colorScheme.onSurfaceVariant
                                  : colorScheme.primary,
                              size: 20,
                            ),
                          ),
                          title: Text(
                            notification.title,
                            style: TextStyle(
                              fontWeight: notification.isRead
                                  ? FontWeight.normal
                                  : FontWeight.bold,
                            ),
                          ),
                          subtitle: Text(
                            notification.body,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          trailing: Text(
                            _formatNotificationTime(notification.createdAt),
                            style:
                                Theme.of(context).textTheme.bodySmall?.copyWith(
                                      color: colorScheme.onSurfaceVariant,
                                    ),
                          ),
                          onTap: () {
                            Haptics.light();
                            // Navegar al detalle
                          },
                        );
                      },
                    );
                  },
                  loading: () => const FlavorLoadingState(),
                  error: (_, __) => const Center(
                      child: Text('Error al cargar notificaciones')),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  IconData _getIconForNotificationType(String type) {
    final typeIcons = {
      'reservation': Icons.calendar_today,
      'message': Icons.message,
      'payment': Icons.payment,
      'promotion': Icons.local_offer,
      'system': Icons.info,
    };
    return typeIcons[type] ?? Icons.notifications;
  }

  String _formatNotificationTime(DateTime time) {
    final now = DateTime.now();
    final difference = now.difference(time);

    if (difference.inMinutes < 60) {
      return '${difference.inMinutes}m';
    } else if (difference.inHours < 24) {
      return '${difference.inHours}h';
    } else if (difference.inDays < 7) {
      return '${difference.inDays}d';
    }
    return DateFormat('d/M').format(time);
  }
}
