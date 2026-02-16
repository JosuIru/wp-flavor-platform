import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_map_marker_cluster/flutter_map_marker_cluster.dart';
import 'package:latlong2/latlong.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/utils/haptics.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

/// Modelo para punto de interes en el mapa
class MapPointOfInterest {
  final String id;
  final String title;
  final String? description;
  final double latitude;
  final double longitude;
  final String moduleType;
  final String? iconName;
  final String? colorHex;
  final String? address;
  final Map<String, dynamic>? metadata;

  const MapPointOfInterest({
    required this.id,
    required this.title,
    this.description,
    required this.latitude,
    required this.longitude,
    required this.moduleType,
    this.iconName,
    this.colorHex,
    this.address,
    this.metadata,
  });

  LatLng get latLng => LatLng(latitude, longitude);

  factory MapPointOfInterest.fromJson(Map<String, dynamic> json) {
    return MapPointOfInterest(
      id: json['id']?.toString() ?? '',
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      latitude: (json['latitude'] as num?)?.toDouble() ?? 0,
      longitude: (json['longitude'] as num?)?.toDouble() ?? 0,
      moduleType: json['module_type'] as String? ?? 'general',
      iconName: json['icon'] as String?,
      colorHex: json['color'] as String?,
      address: json['address'] as String?,
      metadata: json['metadata'] as Map<String, dynamic>?,
    );
  }
}

/// Provider para obtener puntos de interes del API
final mapPointsOfInterestProvider = FutureProvider<List<MapPointOfInterest>>((ref) async {
  final api = ref.read(apiClientProvider);

  try {
    final response = await api.get('/client/map/points');
    if (response.success && response.data != null) {
      final pointsJson = response.data!['points'] as List? ?? [];
      return pointsJson
          .map((point) => MapPointOfInterest.fromJson(point as Map<String, dynamic>))
          .toList();
    }
  } catch (error) {
    debugPrint('[MapPointsOfInterest] Error: $error');
  }

  // Datos de ejemplo si no hay endpoint disponible
  return _getExamplePoints();
});

/// Widget de mapa interactivo para el dashboard del cliente
class DashboardMapWidget extends ConsumerStatefulWidget {
  final Function(MapPointOfInterest)? onPointTap;
  final List<String>? moduleFilters;
  final double height;
  final bool showControls;
  final bool enableClustering;

  const DashboardMapWidget({
    super.key,
    this.onPointTap,
    this.moduleFilters,
    this.height = 250,
    this.showControls = true,
    this.enableClustering = true,
  });

  @override
  ConsumerState<DashboardMapWidget> createState() => _DashboardMapWidgetState();
}

class _DashboardMapWidgetState extends ConsumerState<DashboardMapWidget>
    with TickerProviderStateMixin {
  final MapController _mapController = MapController();
  late AnimationController _animationController;
  MapPointOfInterest? _selectedPoint;
  bool _isBottomSheetVisible = false;

  // Centro por defecto (Espana)
  static const LatLng _defaultCenter = LatLng(40.4168, -3.7038);
  static const double _defaultZoom = 6.0;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 300),
    );
  }

  @override
  void dispose() {
    _mapController.dispose();
    _animationController.dispose();
    super.dispose();
  }

  Color _getColorFromHex(String? hexColor) {
    if (hexColor == null) return Colors.blue;
    final buffer = StringBuffer();
    if (hexColor.length == 6 || hexColor.length == 7) buffer.write('ff');
    buffer.write(hexColor.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  IconData _getIconForModule(String moduleType, String? iconName) {
    if (iconName != null) {
      final iconMap = {
        'shopping_basket': Icons.shopping_basket,
        'volunteer_activism': Icons.volunteer_activism,
        'storefront': Icons.storefront,
        'event': Icons.event,
        'local_library': Icons.local_library,
        'park': Icons.park,
        'recycling': Icons.recycling,
        'directions_bike': Icons.directions_bike,
        'local_parking': Icons.local_parking,
        'restaurant': Icons.restaurant,
        'local_cafe': Icons.local_cafe,
        'meeting_room': Icons.meeting_room,
        'fitness_center': Icons.fitness_center,
        'school': Icons.school,
        'location_on': Icons.location_on,
      };
      if (iconMap.containsKey(iconName)) {
        return iconMap[iconName]!;
      }
    }

    // Fallback por tipo de modulo
    final moduleIconMap = {
      'grupos_consumo': Icons.shopping_basket,
      'banco_tiempo': Icons.volunteer_activism,
      'marketplace': Icons.storefront,
      'eventos': Icons.event,
      'biblioteca': Icons.local_library,
      'huertos_urbanos': Icons.park,
      'reciclaje': Icons.recycling,
      'bicicletas': Icons.directions_bike,
      'parkings': Icons.local_parking,
      'bares': Icons.restaurant,
      'espacios_comunes': Icons.meeting_room,
      'talleres': Icons.school,
    };

    return moduleIconMap[moduleType] ?? Icons.location_on;
  }

  Color _getColorForModule(String moduleType, String? colorHex) {
    if (colorHex != null) {
      return _getColorFromHex(colorHex);
    }

    final moduleColorMap = {
      'grupos_consumo': Colors.green,
      'banco_tiempo': Colors.teal,
      'marketplace': Colors.orange,
      'eventos': Colors.pink,
      'biblioteca': Colors.brown,
      'huertos_urbanos': Colors.lightGreen,
      'reciclaje': Colors.green.shade700,
      'bicicletas': Colors.blue,
      'parkings': Colors.indigo,
      'bares': Colors.red,
      'espacios_comunes': Colors.purple,
      'talleres': Colors.amber,
    };

    return moduleColorMap[moduleType] ?? Colors.blue;
  }

  void _handleMarkerTap(MapPointOfInterest point) {
    Haptics.selection();
    setState(() {
      _selectedPoint = point;
      _isBottomSheetVisible = true;
    });
    _animationController.forward();

    // Centrar mapa en el punto seleccionado
    _mapController.move(point.latLng, _mapController.camera.zoom);
  }

  void _hideBottomSheet() {
    _animationController.reverse().then((_) {
      if (mounted) {
        setState(() {
          _isBottomSheetVisible = false;
          _selectedPoint = null;
        });
      }
    });
  }

  List<Marker> _buildMarkers(List<MapPointOfInterest> points) {
    final filteredPoints = widget.moduleFilters != null
        ? points.where((point) => widget.moduleFilters!.contains(point.moduleType)).toList()
        : points;

    return filteredPoints.map((point) {
      final color = _getColorForModule(point.moduleType, point.colorHex);
      final icon = _getIconForModule(point.moduleType, point.iconName);
      final isSelected = _selectedPoint?.id == point.id;

      return Marker(
        point: point.latLng,
        width: isSelected ? 50 : 40,
        height: isSelected ? 50 : 40,
        child: GestureDetector(
          onTap: () => _handleMarkerTap(point),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            decoration: BoxDecoration(
              color: isSelected ? color : color.withOpacity(0.9),
              shape: BoxShape.circle,
              border: Border.all(
                color: Colors.white,
                width: isSelected ? 3 : 2,
              ),
              boxShadow: [
                BoxShadow(
                  color: color.withOpacity(0.4),
                  blurRadius: isSelected ? 8 : 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Icon(
              icon,
              color: Colors.white,
              size: isSelected ? 24 : 20,
            ),
          ),
        ),
      );
    }).toList();
  }

  LatLngBounds? _calculateBounds(List<MapPointOfInterest> points) {
    if (points.isEmpty) return null;

    double minLat = points.first.latitude;
    double maxLat = points.first.latitude;
    double minLng = points.first.longitude;
    double maxLng = points.first.longitude;

    for (final point in points) {
      if (point.latitude < minLat) minLat = point.latitude;
      if (point.latitude > maxLat) maxLat = point.latitude;
      if (point.longitude < minLng) minLng = point.longitude;
      if (point.longitude > maxLng) maxLng = point.longitude;
    }

    return LatLngBounds(
      LatLng(minLat - 0.01, minLng - 0.01),
      LatLng(maxLat + 0.01, maxLng + 0.01),
    );
  }

  @override
  Widget build(BuildContext context) {
    final pointsAsync = ref.watch(mapPointsOfInterestProvider);
    final colorScheme = Theme.of(context).colorScheme;

    return Semantics(
      label: 'Mapa interactivo de puntos de interes',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Titulo de seccion
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Semantics(
                header: true,
                child: Text(
                  'Cerca de ti',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
              ),
              if (widget.showControls)
                IconButton(
                  onPressed: () {
                    Haptics.light();
                    // Expandir mapa a pantalla completa
                    _showFullScreenMap(context);
                  },
                  icon: const Icon(Icons.fullscreen),
                  tooltip: 'Ver mapa completo',
                ),
            ],
          ),
          const SizedBox(height: 12),

          // Contenedor del mapa
          ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: SizedBox(
              height: widget.height,
              child: Stack(
                children: [
                  // Mapa
                  pointsAsync.when(
                    data: (points) => _buildMap(points, colorScheme),
                    loading: () => _buildLoadingState(colorScheme),
                    error: (_, __) => _buildErrorState(colorScheme),
                  ),

                  // Bottom sheet con detalles del punto seleccionado
                  if (_isBottomSheetVisible && _selectedPoint != null)
                    _buildPointDetailsSheet(colorScheme),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMap(List<MapPointOfInterest> points, ColorScheme colorScheme) {
    final markers = _buildMarkers(points);
    final bounds = _calculateBounds(points);

    return FlutterMap(
      mapController: _mapController,
      options: MapOptions(
        initialCenter: bounds != null ? bounds.center : _defaultCenter,
        initialZoom: _defaultZoom,
        onTap: (_, __) => _hideBottomSheet(),
        interactionOptions: const InteractionOptions(
          flags: InteractiveFlag.all & ~InteractiveFlag.rotate,
        ),
      ),
      children: [
        // Capa de tiles (OpenStreetMap)
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'com.flavor.chatia',
          maxZoom: 19,
        ),

        // Marcadores con clustering
        if (widget.enableClustering && markers.length > 5)
          MarkerClusterLayerWidget(
            options: MarkerClusterLayerOptions(
              maxClusterRadius: 80,
              size: const Size(40, 40),
              markers: markers,
              builder: (context, clusterMarkers) {
                return Container(
                  decoration: BoxDecoration(
                    color: colorScheme.primary,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                    boxShadow: [
                      BoxShadow(
                        color: colorScheme.primary.withOpacity(0.4),
                        blurRadius: 6,
                      ),
                    ],
                  ),
                  child: Center(
                    child: Text(
                      '${clusterMarkers.length}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ),
                );
              },
            ),
          )
        else
          MarkerLayer(markers: markers),

        // Controles del mapa
        if (widget.showControls)
          Positioned(
            right: 8,
            bottom: 8,
            child: Column(
              children: [
                _buildMapControlButton(
                  icon: Icons.add,
                  onTap: () {
                    Haptics.light();
                    _mapController.move(
                      _mapController.camera.center,
                      _mapController.camera.zoom + 1,
                    );
                  },
                  tooltip: 'Acercar',
                  colorScheme: colorScheme,
                ),
                const SizedBox(height: 4),
                _buildMapControlButton(
                  icon: Icons.remove,
                  onTap: () {
                    Haptics.light();
                    _mapController.move(
                      _mapController.camera.center,
                      _mapController.camera.zoom - 1,
                    );
                  },
                  tooltip: 'Alejar',
                  colorScheme: colorScheme,
                ),
                const SizedBox(height: 4),
                _buildMapControlButton(
                  icon: Icons.my_location,
                  onTap: () {
                    Haptics.light();
                    // Centrar en ubicacion del usuario (requiere permisos)
                    _centerOnUserLocation();
                  },
                  tooltip: 'Mi ubicacion',
                  colorScheme: colorScheme,
                ),
              ],
            ),
          ),
      ],
    );
  }

  Widget _buildMapControlButton({
    required IconData icon,
    required VoidCallback onTap,
    required String tooltip,
    required ColorScheme colorScheme,
  }) {
    return Semantics(
      button: true,
      label: tooltip,
      child: Material(
        color: colorScheme.surface,
        elevation: 2,
        borderRadius: BorderRadius.circular(8),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(8),
          child: Container(
            padding: const EdgeInsets.all(8),
            child: Icon(
              icon,
              size: 20,
              color: colorScheme.onSurface,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildPointDetailsSheet(ColorScheme colorScheme) {
    final point = _selectedPoint!;
    final color = _getColorForModule(point.moduleType, point.colorHex);
    final icon = _getIconForModule(point.moduleType, point.iconName);

    return Positioned(
      left: 0,
      right: 0,
      bottom: 0,
      child: AnimatedBuilder(
        animation: _animationController,
        builder: (context, child) {
          return Transform.translate(
            offset: Offset(0, (1 - _animationController.value) * 150),
            child: Opacity(
              opacity: _animationController.value,
              child: child,
            ),
          );
        },
        child: Semantics(
          label: 'Detalles de ${point.title}',
          child: Container(
            margin: const EdgeInsets.all(8),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: colorScheme.surface,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 8,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    // Icono del modulo
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: color.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Icon(icon, color: color, size: 24),
                    ),
                    const SizedBox(width: 12),
                    // Titulo y tipo
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            point.title,
                            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          if (point.address != null)
                            Text(
                              point.address!,
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                    color: colorScheme.onSurfaceVariant,
                                  ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                        ],
                      ),
                    ),
                    // Boton cerrar
                    IconButton(
                      onPressed: _hideBottomSheet,
                      icon: const Icon(Icons.close),
                      iconSize: 20,
                      visualDensity: VisualDensity.compact,
                    ),
                  ],
                ),
                if (point.description != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    point.description!,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
                const SizedBox(height: 8),
                // Boton de accion
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.tonal(
                    onPressed: () {
                      Haptics.medium();
                      _hideBottomSheet();
                      widget.onPointTap?.call(point);
                    },
                    child: const Text('Ver detalles'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLoadingState(ColorScheme colorScheme) {
    return Container(
      color: colorScheme.surfaceContainerLow,
      child: const Center(
        child: CircularProgressIndicator(),
      ),
    );
  }

  Widget _buildErrorState(ColorScheme colorScheme) {
    return Container(
      color: colorScheme.surfaceContainerLow,
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.map_outlined,
              size: 48,
              color: colorScheme.onSurfaceVariant.withOpacity(0.5),
            ),
            const SizedBox(height: 8),
            Text(
              'No se pudo cargar el mapa',
              style: TextStyle(color: colorScheme.onSurfaceVariant),
            ),
          ],
        ),
      ),
    );
  }

  void _showFullScreenMap(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => _FullScreenMapScreen(
          moduleFilters: widget.moduleFilters,
          onPointTap: widget.onPointTap,
        ),
      ),
    );
  }

  Future<void> _centerOnUserLocation() async {
    // En una implementacion real, aqui se obtendria la ubicacion del usuario
    // Por ahora, simplemente hacemos feedback haptico
    Haptics.light();
    // Ejemplo: mover a ubicacion por defecto
    _mapController.move(_defaultCenter, 12);
  }
}

/// Pantalla de mapa a pantalla completa
class _FullScreenMapScreen extends ConsumerWidget {
  final List<String>? moduleFilters;
  final Function(MapPointOfInterest)? onPointTap;

  const _FullScreenMapScreen({
    this.moduleFilters,
    this.onPointTap,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mapa'),
        actions: [
          IconButton(
            onPressed: () {
              Haptics.light();
              _showFilterDialog(context);
            },
            icon: const Icon(Icons.filter_list),
            tooltip: 'Filtrar',
          ),
        ],
      ),
      body: DashboardMapWidget(
        moduleFilters: moduleFilters,
        onPointTap: (point) {
          Navigator.pop(context);
          onPointTap?.call(point);
        },
        height: MediaQuery.of(context).size.height,
        showControls: true,
        enableClustering: true,
      ),
    );
  }

  void _showFilterDialog(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Filtrar por tipo',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                FilterChip(label: const Text('Grupos Consumo'), selected: true, onSelected: (_) {}),
                FilterChip(label: const Text('Banco Tiempo'), selected: true, onSelected: (_) {}),
                FilterChip(label: const Text('Eventos'), selected: true, onSelected: (_) {}),
                FilterChip(label: const Text('Marketplace'), selected: true, onSelected: (_) {}),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Genera puntos de ejemplo para cuando no hay endpoint disponible
List<MapPointOfInterest> _getExamplePoints() {
  return [
    const MapPointOfInterest(
      id: '1',
      title: 'Grupo de Consumo Ecologico',
      description: 'Punto de recogida semanal de productos ecologicos',
      latitude: 40.4200,
      longitude: -3.7100,
      moduleType: 'grupos_consumo',
      iconName: 'shopping_basket',
      colorHex: '#4CAF50',
      address: 'Calle Mayor 15, Madrid',
    ),
    const MapPointOfInterest(
      id: '2',
      title: 'Banco de Tiempo Vecinal',
      description: 'Centro de intercambio de servicios comunitarios',
      latitude: 40.4150,
      longitude: -3.7050,
      moduleType: 'banco_tiempo',
      iconName: 'volunteer_activism',
      colorHex: '#009688',
      address: 'Plaza del Barrio 3, Madrid',
    ),
    const MapPointOfInterest(
      id: '3',
      title: 'Mercadillo Local',
      description: 'Mercado de productos artesanales los sabados',
      latitude: 40.4180,
      longitude: -3.7150,
      moduleType: 'marketplace',
      iconName: 'storefront',
      colorHex: '#FF9800',
      address: 'Avenida Principal 42, Madrid',
    ),
    const MapPointOfInterest(
      id: '4',
      title: 'Huerto Urbano Comunitario',
      description: 'Parcelas disponibles para el cultivo',
      latitude: 40.4100,
      longitude: -3.7000,
      moduleType: 'huertos_urbanos',
      iconName: 'park',
      colorHex: '#8BC34A',
      address: 'Parque Norte, Madrid',
    ),
    const MapPointOfInterest(
      id: '5',
      title: 'Punto de Reciclaje',
      description: 'Contenedores especiales y punto limpio',
      latitude: 40.4220,
      longitude: -3.7200,
      moduleType: 'reciclaje',
      iconName: 'recycling',
      colorHex: '#4CAF50',
      address: 'Calle Verde 8, Madrid',
    ),
  ];
}
