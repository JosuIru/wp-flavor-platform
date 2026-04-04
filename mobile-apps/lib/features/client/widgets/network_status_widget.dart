import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/utils/haptics.dart';
import '../../../core/providers/providers.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

/// Estado de la conexion de red
enum NetworkConnectionStatus {
  connected,
  connecting,
  disconnected,
  error,
}

/// Modelo para informacion de una comunidad conectada
class ConnectedCommunity {
  final String id;
  final String name;
  final String? logoUrl;
  final int memberCount;
  final bool isActive;
  final String? description;

  const ConnectedCommunity({
    required this.id,
    required this.name,
    this.logoUrl,
    required this.memberCount,
    this.isActive = true,
    this.description,
  });

  factory ConnectedCommunity.fromJson(Map<String, dynamic> json) {
    return ConnectedCommunity(
      id: json['id']?.toString() ?? '',
      name: json['name'] as String? ?? '',
      logoUrl: json['logo_url'] as String?,
      memberCount: json['member_count'] as int? ?? 0,
      isActive: json['is_active'] as bool? ?? true,
      description: json['description'] as String?,
    );
  }
}

/// Modelo para recurso compartido
class SharedResource {
  final String id;
  final String name;
  final String type;
  final String? iconName;
  final int availableCount;
  final String? communityName;

  const SharedResource({
    required this.id,
    required this.name,
    required this.type,
    this.iconName,
    required this.availableCount,
    this.communityName,
  });

  factory SharedResource.fromJson(Map<String, dynamic> json) {
    return SharedResource(
      id: json['id']?.toString() ?? '',
      name: json['name'] as String? ?? '',
      type: json['type'] as String? ?? 'general',
      iconName: json['icon'] as String?,
      availableCount: json['available_count'] as int? ?? 0,
      communityName: json['community_name'] as String?,
    );
  }
}

/// Modelo para estado de la red
class NetworkStatus {
  final NetworkConnectionStatus status;
  final int totalCommunities;
  final int activeCommunities;
  final List<ConnectedCommunity> communities;
  final List<SharedResource> sharedResources;
  final int totalSharedResources;
  final DateTime? lastSync;

  const NetworkStatus({
    required this.status,
    this.totalCommunities = 0,
    this.activeCommunities = 0,
    this.communities = const [],
    this.sharedResources = const [],
    this.totalSharedResources = 0,
    this.lastSync,
  });

  factory NetworkStatus.fromJson(Map<String, dynamic> json) {
    final communitiesJson = json['communities'] as List? ?? [];
    final resourcesJson = json['shared_resources'] as List? ?? [];
    final statusStr = json['status'] as String? ?? 'disconnected';

    NetworkConnectionStatus connectionStatus;
    switch (statusStr) {
      case 'connected':
        connectionStatus = NetworkConnectionStatus.connected;
        break;
      case 'connecting':
        connectionStatus = NetworkConnectionStatus.connecting;
        break;
      case 'error':
        connectionStatus = NetworkConnectionStatus.error;
        break;
      default:
        connectionStatus = NetworkConnectionStatus.disconnected;
    }

    return NetworkStatus(
      status: connectionStatus,
      totalCommunities: json['total_communities'] as int? ?? 0,
      activeCommunities: json['active_communities'] as int? ?? 0,
      communities: communitiesJson
          .map((community) => ConnectedCommunity.fromJson(community as Map<String, dynamic>))
          .toList(),
      sharedResources: resourcesJson
          .map((resource) => SharedResource.fromJson(resource as Map<String, dynamic>))
          .toList(),
      totalSharedResources: json['total_shared_resources'] as int? ?? 0,
      lastSync: json['last_sync'] != null
          ? DateTime.tryParse(json['last_sync'] as String)
          : null,
    );
  }
}

/// Provider para obtener el estado de la red
final networkStatusProvider = FutureProvider<NetworkStatus>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.get('/client/network/status');
    if (response.success && response.data != null) {
      return NetworkStatus.fromJson(response.data!);
    }
  } catch (error) {
    debugPrint('[NetworkStatus] Error: $error');
  }

  // Estado de ejemplo si no hay endpoint disponible
  return _getExampleNetworkStatus();
});

/// Widget de estado de la red para el dashboard del cliente
class NetworkStatusWidget extends ConsumerWidget {
  final VoidCallback? onTap;
  final VoidCallback? onViewCommunities;
  final VoidCallback? onViewResources;
  final bool showDetails;

  const NetworkStatusWidget({
    super.key,
    this.onTap,
    this.onViewCommunities,
    this.onViewResources,
    this.showDetails = true,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final networkAsync = ref.watch(networkStatusProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Titulo de seccion
        Semantics(
          header: true,
          child: Text(
            'Mi Red',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
        ),
        const SizedBox(height: 12),

        // Card principal
        networkAsync.when(
          data: (status) => _buildNetworkCard(context, status, colorScheme),
          loading: () => _buildLoadingCard(colorScheme),
          error: (_, __) => _buildErrorCard(colorScheme),
        ),
      ],
    );
  }

  Widget _buildNetworkCard(
    BuildContext context,
    NetworkStatus status,
    ColorScheme colorScheme,
  ) {
    final statusColor = _getStatusColor(status.status, colorScheme);
    final statusIcon = _getStatusIcon(status.status);
    final statusText = _getStatusText(status.status);

    return Semantics(
      label: 'Estado de la red: $statusText. '
          '${status.activeCommunities} comunidades activas. '
          '${status.totalSharedResources} recursos compartidos disponibles',
      child: Card(
        elevation: 0,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(
            color: colorScheme.outlineVariant,
            width: 1,
          ),
        ),
        child: InkWell(
          onTap: onTap != null
              ? () {
                  Haptics.light();
                  onTap!();
                }
              : null,
          child: Column(
            children: [
              // Cabecera con estado
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      statusColor.withOpacity(0.1),
                      statusColor.withOpacity(0.05),
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                child: Row(
                  children: [
                    // Indicador de estado
                    _buildStatusIndicator(statusColor, statusIcon),
                    const SizedBox(width: 12),
                    // Texto de estado
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          ExcludeSemantics(
                            child: Text(
                              statusText,
                              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                                    fontWeight: FontWeight.bold,
                                    color: statusColor,
                                  ),
                            ),
                          ),
                          if (status.lastSync != null)
                            ExcludeSemantics(
                              child: Text(
                                'Ultima sincronizacion: ${_formatLastSync(status.lastSync!)}',
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                      color: colorScheme.onSurfaceVariant,
                                    ),
                              ),
                            ),
                        ],
                      ),
                    ),
                    // Icono de navegacion
                    if (onTap != null)
                      ExcludeSemantics(
                        child: Icon(
                          Icons.chevron_right,
                          color: colorScheme.onSurfaceVariant,
                        ),
                      ),
                  ],
                ),
              ),

              // Estadisticas rapidas
              if (showDetails) ...[
                const Divider(height: 1),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      // Comunidades conectadas
                      Expanded(
                        child: _buildStatItem(
                          context,
                          icon: Icons.people_alt_outlined,
                          value: '${status.activeCommunities}',
                          label: 'Comunidades',
                          color: Colors.blue,
                          onTap: onViewCommunities,
                        ),
                      ),
                      Container(
                        width: 1,
                        height: 40,
                        color: colorScheme.outlineVariant,
                      ),
                      // Recursos compartidos
                      Expanded(
                        child: _buildStatItem(
                          context,
                          icon: Icons.share_outlined,
                          value: '${status.totalSharedResources}',
                          label: 'Recursos',
                          color: Colors.green,
                          onTap: onViewResources,
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              // Lista de comunidades (preview)
              if (showDetails && status.communities.isNotEmpty) ...[
                const Divider(height: 1),
                _buildCommunitiesPreview(context, status.communities, colorScheme),
              ],

              // Lista de recursos compartidos (preview)
              if (showDetails && status.sharedResources.isNotEmpty) ...[
                const Divider(height: 1),
                _buildResourcesPreview(context, status.sharedResources, colorScheme),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusIndicator(Color color, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        shape: BoxShape.circle,
        border: Border.all(
          color: color.withOpacity(0.3),
          width: 2,
        ),
      ),
      child: Icon(
        icon,
        color: color,
        size: 24,
      ),
    );
  }

  Widget _buildStatItem(
    BuildContext context, {
    required IconData icon,
    required String value,
    required String label,
    required Color color,
    VoidCallback? onTap,
  }) {
    return Semantics(
      button: onTap != null,
      label: '$value $label',
      child: InkWell(
        onTap: onTap != null
            ? () {
                Haptics.light();
                onTap();
              }
            : null,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
          child: Column(
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(icon, color: color, size: 18),
                  const SizedBox(width: 6),
                  Text(
                    value,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                          color: color,
                        ),
                  ),
                ],
              ),
              const SizedBox(height: 2),
              Text(
                label,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCommunitiesPreview(
    BuildContext context,
    List<ConnectedCommunity> communities,
    ColorScheme colorScheme,
  ) {
    final previewCommunities = communities.take(3).toList();

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Comunidades conectadas',
                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
              ),
              if (communities.length > 3)
                TextButton(
                  onPressed: onViewCommunities != null
                      ? () {
                          Haptics.light();
                          onViewCommunities!();
                        }
                      : null,
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.zero,
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                  child: Text('Ver todas (${communities.length})'),
                ),
            ],
          ),
          const SizedBox(height: 12),
          // Stack de avatares de comunidades
          SizedBox(
            height: 44,
            child: Row(
              children: [
                // Avatares apilados
                SizedBox(
                  width: 44 + (previewCommunities.length - 1) * 24,
                  child: Stack(
                    children: previewCommunities.asMap().entries.map((entry) {
                      final index = entry.key;
                      final community = entry.value;
                      return Positioned(
                        left: index * 24.0,
                        child: _buildCommunityAvatar(community, colorScheme),
                      );
                    }).toList(),
                  ),
                ),
                const SizedBox(width: 12),
                // Nombres
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        previewCommunities.map((community) => community.name).join(', '),
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              fontWeight: FontWeight.w500,
                            ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        '${communities.fold<int>(0, (sum, community) => sum + community.memberCount)} miembros en total',
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: colorScheme.onSurfaceVariant,
                            ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCommunityAvatar(ConnectedCommunity community, ColorScheme colorScheme) {
    return Semantics(
      label: community.name,
      child: Container(
        width: 44,
        height: 44,
        decoration: BoxDecoration(
          color: colorScheme.primaryContainer,
          shape: BoxShape.circle,
          border: Border.all(
            color: colorScheme.surface,
            width: 2,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.1),
              blurRadius: 4,
            ),
          ],
        ),
        child: community.logoUrl != null
            ? ClipOval(
                child: Image.network(
                  community.logoUrl!,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => _buildCommunityInitial(community, colorScheme),
                ),
              )
            : _buildCommunityInitial(community, colorScheme),
      ),
    );
  }

  Widget _buildCommunityInitial(ConnectedCommunity community, ColorScheme colorScheme) {
    return Center(
      child: Text(
        community.name.isNotEmpty ? community.name[0].toUpperCase() : 'C',
        style: TextStyle(
          color: colorScheme.onPrimaryContainer,
          fontWeight: FontWeight.bold,
          fontSize: 18,
        ),
      ),
    );
  }

  Widget _buildResourcesPreview(
    BuildContext context,
    List<SharedResource> resources,
    ColorScheme colorScheme,
  ) {
    final previewResources = resources.take(4).toList();

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Recursos disponibles',
                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
              ),
              if (resources.length > 4)
                TextButton(
                  onPressed: onViewResources != null
                      ? () {
                          Haptics.light();
                          onViewResources!();
                        }
                      : null,
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.zero,
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                  child: Text('Ver todos (${resources.length})'),
                ),
            ],
          ),
          const SizedBox(height: 12),
          // Chips de recursos
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: previewResources.map((resource) {
              return _buildResourceChip(context, resource, colorScheme);
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _buildResourceChip(
    BuildContext context,
    SharedResource resource,
    ColorScheme colorScheme,
  ) {
    final icon = _getResourceIcon(resource.type, resource.iconName);

    return Semantics(
      label: '${resource.name}, ${resource.availableCount} disponibles',
      child: Chip(
        avatar: Icon(icon, size: 16),
        label: Text(
          '${resource.name} (${resource.availableCount})',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        visualDensity: VisualDensity.compact,
        side: BorderSide(color: colorScheme.outlineVariant),
      ),
    );
  }

  Widget _buildLoadingCard(ColorScheme colorScheme) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: colorScheme.outlineVariant,
          width: 1,
        ),
      ),
      child: const Padding(
        padding: EdgeInsets.all(32),
        child: FlavorLoadingState(),
      ),
    );
  }

  Widget _buildErrorCard(ColorScheme colorScheme) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: colorScheme.outlineVariant,
          width: 1,
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Icon(
              Icons.cloud_off,
              size: 48,
              color: colorScheme.onSurfaceVariant.withOpacity(0.5),
            ),
            const SizedBox(height: 8),
            Text(
              'No se pudo cargar el estado de la red',
              style: TextStyle(color: colorScheme.onSurfaceVariant),
            ),
          ],
        ),
      ),
    );
  }

  Color _getStatusColor(NetworkConnectionStatus status, ColorScheme colorScheme) {
    switch (status) {
      case NetworkConnectionStatus.connected:
        return Colors.green;
      case NetworkConnectionStatus.connecting:
        return Colors.orange;
      case NetworkConnectionStatus.disconnected:
        return colorScheme.onSurfaceVariant;
      case NetworkConnectionStatus.error:
        return colorScheme.error;
    }
  }

  IconData _getStatusIcon(NetworkConnectionStatus status) {
    switch (status) {
      case NetworkConnectionStatus.connected:
        return Icons.cloud_done;
      case NetworkConnectionStatus.connecting:
        return Icons.cloud_sync;
      case NetworkConnectionStatus.disconnected:
        return Icons.cloud_off;
      case NetworkConnectionStatus.error:
        return Icons.error_outline;
    }
  }

  String _getStatusText(NetworkConnectionStatus status) {
    switch (status) {
      case NetworkConnectionStatus.connected:
        return 'Conectado a la red';
      case NetworkConnectionStatus.connecting:
        return 'Conectando...';
      case NetworkConnectionStatus.disconnected:
        return 'Desconectado';
      case NetworkConnectionStatus.error:
        return 'Error de conexion';
    }
  }

  IconData _getResourceIcon(String type, String? iconName) {
    if (iconName != null) {
      final iconMap = {
        'shopping_basket': Icons.shopping_basket,
        'volunteer_activism': Icons.volunteer_activism,
        'event': Icons.event,
        'local_library': Icons.local_library,
        'directions_bike': Icons.directions_bike,
        'build': Icons.build,
        'eco': Icons.eco,
      };
      if (iconMap.containsKey(iconName)) {
        return iconMap[iconName]!;
      }
    }

    final typeIconMap = {
      'product': Icons.shopping_basket,
      'service': Icons.volunteer_activism,
      'event': Icons.event,
      'book': Icons.local_library,
      'vehicle': Icons.directions_bike,
      'tool': Icons.build,
    };

    return typeIconMap[type] ?? Icons.category;
  }

  String _formatLastSync(DateTime time) {
    final now = DateTime.now();
    final difference = now.difference(time);

    if (difference.inMinutes < 1) {
      return 'ahora';
    } else if (difference.inMinutes < 60) {
      return 'hace ${difference.inMinutes} min';
    } else if (difference.inHours < 24) {
      return 'hace ${difference.inHours} h';
    } else {
      return '${time.day}/${time.month}';
    }
  }
}

/// Genera estado de ejemplo para cuando no hay endpoint disponible
NetworkStatus _getExampleNetworkStatus() {
  return NetworkStatus(
    status: NetworkConnectionStatus.connected,
    totalCommunities: 5,
    activeCommunities: 3,
    communities: const [
      ConnectedCommunity(
        id: '1',
        name: 'Barrio Sostenible',
        memberCount: 234,
        isActive: true,
        description: 'Comunidad de vecinos comprometidos con la sostenibilidad',
      ),
      ConnectedCommunity(
        id: '2',
        name: 'EcoVecinos Madrid',
        memberCount: 156,
        isActive: true,
        description: 'Red de consumo responsable',
      ),
      ConnectedCommunity(
        id: '3',
        name: 'Huertos del Norte',
        memberCount: 89,
        isActive: true,
        description: 'Comunidad de huertos urbanos',
      ),
    ],
    sharedResources: const [
      SharedResource(
        id: '1',
        name: 'Cestas ecologicas',
        type: 'product',
        iconName: 'shopping_basket',
        availableCount: 12,
        communityName: 'Barrio Sostenible',
      ),
      SharedResource(
        id: '2',
        name: 'Clases de yoga',
        type: 'service',
        iconName: 'volunteer_activism',
        availableCount: 5,
        communityName: 'EcoVecinos Madrid',
      ),
      SharedResource(
        id: '3',
        name: 'Herramientas jardin',
        type: 'tool',
        iconName: 'build',
        availableCount: 8,
        communityName: 'Huertos del Norte',
      ),
      SharedResource(
        id: '4',
        name: 'Libros intercambio',
        type: 'book',
        iconName: 'local_library',
        availableCount: 45,
        communityName: 'Barrio Sostenible',
      ),
    ],
    totalSharedResources: 70,
    lastSync: DateTime.now().subtract(const Duration(minutes: 5)),
  );
}
