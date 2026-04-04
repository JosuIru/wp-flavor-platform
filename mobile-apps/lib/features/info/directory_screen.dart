import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_map_marker_cluster/flutter_map_marker_cluster.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/api/api_client.dart';
import '../../core/config/server_config.dart';
import '../../core/models/directory_models.dart';
import '../../core/services/business_directory_service.dart';
import '../../core/providers/providers.dart';
import '../../core/providers/sync_provider.dart';
import '../../core/utils/map_launch_helper.dart';
import '../../core/widgets/flavor_snackbar.dart';
import '../../core/widgets/google_marker_map.dart';
import '../../core/widgets/flavor_state_widgets.dart';

class DirectoryScreen extends ConsumerStatefulWidget {
  final VoidCallback? onConnected;
  final bool closeOnConnect;
  final bool embedded;

  const DirectoryScreen({
    super.key,
    this.onConnected,
    this.closeOnConnect = true,
    this.embedded = false,
  });

  @override
  ConsumerState<DirectoryScreen> createState() => _DirectoryScreenState();
}

class _DirectoryScreenState extends ConsumerState<DirectoryScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context);
  final _searchController = TextEditingController();
  bool _isLoading = false;
  String? _error;
  DirectoryResponse? _data;
  String _selectedTipo = '';
  String _selectedNivel = '';
  String _selectedSector = '';
  List<DirectoryNode> _mapNodes = [];
  List<DirectoryNode> _nearbyNodes = [];

  Future<void> _openExternalUrl(String url) async {
    if (url.isEmpty) return;
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  Future<void> _openNodeInMap(DirectoryNode node) async {
    if (node.latitud == null || node.longitud == null) return;
    final uri = MapLaunchHelper.buildConfiguredMapUri(
      node.latitud!,
      node.longitud!,
      query: node.nombre.isNotEmpty ? node.nombre : node.siteUrl,
    );
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  LatLng? _userLocation;
  bool _showMap = false;
  double _nearbyRadiusKm = 50;
  String _cityFilter = '';
  bool _showFavoritesOnly = false;
  bool _compactList = false;
  Set<String> _favoriteKeys = {};
  final Distance _distance = const Distance();

  @override
  void initState() {
    super.initState();
    _loadDirectory();
    _loadFavorites();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadFavorites() async {
    final keys = await ServerConfig.getFavoriteNodeKeys();
    if (mounted) {
      setState(() => _favoriteKeys = keys);
    }
  }

  String _favoriteKeyFor(DirectoryNode node) {
    return '${node.siteUrl.toLowerCase()}::${node.id}';
  }

  Future<void> _toggleFavorite(DirectoryNode node) async {
    if (node.siteUrl.isEmpty) return;
    await ServerConfig.toggleFavoriteNode(
        siteUrl: node.siteUrl, nodeId: node.id);
    await _loadFavorites();
  }

  bool _isFavorite(DirectoryNode node) {
    if (node.siteUrl.isEmpty) return false;
    return _favoriteKeys.contains(_favoriteKeyFor(node));
  }

  Future<void> _loadDirectory() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final serverUrl = await ServerConfig.getServerUrl();
      final service = BusinessDirectoryService(serverUrl: serverUrl);
      final data = await service.getDirectory(
        search: _searchController.text,
        tipo: _selectedTipo.isEmpty ? null : _selectedTipo,
        nivel: _selectedNivel.isEmpty ? null : _selectedNivel,
        sector: _selectedSector.isEmpty ? null : _selectedSector,
        ciudad: _cityFilter.isEmpty ? null : _cityFilter,
      );

      final mapNodes = await service.getMapData(
        tipo: _selectedTipo.isEmpty ? null : _selectedTipo,
        nivel: _selectedNivel.isEmpty ? null : _selectedNivel,
        sector: _selectedSector.isEmpty ? null : _selectedSector,
      );

      setState(() {
        _data = data;
        _mapNodes = mapNodes;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Error al cargar el directorio';
      });
    }
  }

  Future<void> _connectToBusiness(DirectoryNode business) async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final serverUrl = business.siteUrl;
      if (serverUrl.isEmpty) {
        throw Exception('URL del negocio no disponible');
      }

      final discoveryResponse = await ApiClient.discoverSiteAt(serverUrl);
      if (!discoveryResponse.success || discoveryResponse.data == null) {
        throw Exception(
          discoveryResponse.error ?? 'No se pudo descubrir la API del sitio',
        );
      }
      final apiNamespace = await ApiClient.detectPreferredApiNamespace(
        serverUrl,
        discoveryData: discoveryResponse.data,
      );

      final saved = SavedBusiness(
        id: SavedBusiness.makeId(
          serverUrl,
          apiNamespace,
          'client',
        ),
        name: business.nombre,
        serverUrl: serverUrl,
        apiNamespace: apiNamespace,
        type: 'client',
      );

      await ref.read(serverConfigProvider.notifier).setCurrentBusiness(saved);
      ref.read(apiClientProvider).updateBaseUrl(
            '$serverUrl$apiNamespace',
          );
      final syncResult =
          await ref.read(syncProvider.notifier).syncWithSite(serverUrl);
      if (!syncResult.success) {
        throw Exception(syncResult.error ?? 'No se pudo sincronizar el sitio');
      }

      if (mounted) {
        FlavorSnackbar.showSuccess(context, i18n.commonConnectedTo(business.nombre));
        widget.onConnected?.call();
        if (widget.closeOnConnect) {
          Navigator.pop(context, true);
        }
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'No se pudo conectar al negocio';
      });
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _loadNearby() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        final requested = await Geolocator.requestPermission();
        if (requested == LocationPermission.denied ||
            requested == LocationPermission.deniedForever) {
          throw Exception('Permiso de ubicación denegado');
        }
      }

      final position = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high);
      _userLocation = LatLng(position.latitude, position.longitude);

      final serverUrl = await ServerConfig.getServerUrl();
      final service = BusinessDirectoryService(serverUrl: serverUrl);
      final nearby = await service.getNearby(
        lat: position.latitude,
        lng: position.longitude,
        radioKm: _nearbyRadiusKm,
        limit: 50,
      );

      setState(() {
        _nearbyNodes = nearby.nodos;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'No se pudo obtener la ubicación';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context);
    final data = _data;
    final nodes = List<DirectoryNode>.from(data?.nodos ?? []);
    final filteredNodes =
        _showFavoritesOnly ? nodes.where(_isFavorite).toList() : nodes;

    if (_userLocation != null) {
      filteredNodes.sort((a, b) {
        final da = _distanceKm(a) ?? double.infinity;
        final db = _distanceKm(b) ?? double.infinity;
        return da.compareTo(db);
      });
    }

    final content = SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextField(
            controller: _searchController,
            decoration: InputDecoration(
              labelText: i18n.buscar113f74,
              prefixIcon: const Icon(Icons.search),
              border: const OutlineInputBorder(),
            ),
            onSubmitted: (_) => _loadDirectory(),
          ),
          if (widget.embedded) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                IconButton(
                  icon: Icon(_showMap ? Icons.list : Icons.map),
                  onPressed: () => setState(() => _showMap = !_showMap),
                ),
                const SizedBox(width: 4),
                IconButton(
                  icon: const Icon(Icons.refresh),
                  onPressed: _isLoading ? null : _loadDirectory,
                ),
              ],
            ),
          ],
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _selectedTipo,
                  decoration: InputDecoration(
                    labelText: i18n.tipo4f427c,
                    border: const OutlineInputBorder(),
                  ),
                  items: [
                    DropdownMenuItem(value: '', child: Text(i18n.todos32630c)),
                    DropdownMenuItem(
                        value: 'comunidad', child: Text(i18n.comunidad6b02af)),
                    DropdownMenuItem(
                        value: 'cooperativa',
                        child: Text(i18n.cooperativa657922)),
                    DropdownMenuItem(
                        value: 'asociacion', child: Text(i18n.asociaciN59d9a3)),
                    DropdownMenuItem(
                        value: 'empresa', child: Text(i18n.empresa9bc720)),
                    DropdownMenuItem(
                        value: 'colectivo', child: Text(i18n.colectivo9c6f6f)),
                    DropdownMenuItem(
                        value: 'fundacion', child: Text(i18n.fundaciN0bcfd2)),
                    DropdownMenuItem(
                        value: 'particular',
                        child: Text(i18n.particular1d75c6)),
                    DropdownMenuItem(
                        value: 'administracion',
                        child: Text(i18n.administraciNPBlica86e1c4)),
                    DropdownMenuItem(
                        value: 'educacion',
                        child: Text(i18n.centroEducativo96e832)),
                    DropdownMenuItem(
                        value: 'otro', child: Text(i18n.otroC22952)),
                  ],
                  onChanged: (value) {
                    setState(() => _selectedTipo = value ?? '');
                  },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _selectedNivel,
                  decoration: InputDecoration(
                    labelText: i18n.nivel48281f,
                    border: const OutlineInputBorder(),
                  ),
                  items: [
                    DropdownMenuItem(value: '', child: Text(i18n.todos32630c)),
                    DropdownMenuItem(
                        value: 'basico', child: Text(i18n.bSico597ae7)),
                    DropdownMenuItem(
                        value: 'transicion',
                        child: Text(i18n.enTransiciN3c5b08)),
                    DropdownMenuItem(
                        value: 'consciente',
                        child: Text(i18n.conscienteDf134a)),
                    DropdownMenuItem(
                        value: 'referente', child: Text(i18n.referenteD876b0)),
                  ],
                  onChanged: (value) {
                    setState(() => _selectedNivel = value ?? '');
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextField(
            decoration: InputDecoration(
              labelText: i18n.sectorOpcional94b201,
              border: const OutlineInputBorder(),
            ),
            onChanged: (value) => _selectedSector = value,
          ),
          const SizedBox(height: 12),
          TextField(
            decoration: InputDecoration(
              labelText: i18n.ciudadOpcionalE747b0,
              border: const OutlineInputBorder(),
            ),
            onChanged: (value) => _cityFilter = value,
          ),
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: _isLoading ? null : _loadDirectory,
            icon: const Icon(Icons.search),
            label: Text(i18n.buscar113f74),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: _isLoading ? null : _loadNearby,
            icon: const Icon(Icons.my_location),
            label: Text(i18n.cercaDeMC17429),
          ),
          const SizedBox(height: 8),
          SwitchListTile.adaptive(
            value: _showFavoritesOnly,
            onChanged: (value) => setState(() => _showFavoritesOnly = value),
            title: Text(i18n.soloFavoritos66eeaf),
            contentPadding: EdgeInsets.zero,
          ),
          const SizedBox(height: 4),
          SwitchListTile.adaptive(
            value: _compactList,
            onChanged: (value) => setState(() => _compactList = value),
            title: Text(i18n.vistaCompactaCe3da5),
            contentPadding: EdgeInsets.zero,
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Text(i18n.radioC210a7),
              const SizedBox(width: 8),
              Expanded(
                child: Slider(
                  value: _nearbyRadiusKm,
                  min: 5,
                  max: 200,
                  divisions: 39,
                  label: '${_nearbyRadiusKm.toStringAsFixed(0)} km',
                  onChanged: _isLoading
                      ? null
                      : (value) => setState(() => _nearbyRadiusKm = value),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          if (_error != null)
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.red.shade50,
                borderRadius: BorderRadius.circular(8),
              ),
              child:
                  Text(_error!, style: TextStyle(color: Colors.red.shade700)),
            ),
          if (_isLoading) ...[
            const SizedBox(height: 24),
            const FlavorLoadingState(),
          ] else if (_showMap) ...[
            const SizedBox(height: 8),
            _buildMap(),
            if (_nearbyNodes.isNotEmpty) ...[
              const SizedBox(height: 12),
              Text(
                'Cerca de mí',
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              ..._nearbyNodes.map((n) => _buildBusinessCard(n)),
            ],
          ] else if (filteredNodes.isEmpty) ...[
            const SizedBox(height: 24),
            Center(child: Text(i18n.noHayNegociosEnElDirectorio7c8399)),
          ] else ...[
            const SizedBox(height: 8),
            if (_compactList)
              _buildCompactTable(filteredNodes)
            else
              ...filteredNodes.map((b) => _buildBusinessCard(b)),
          ],
        ],
      ),
    );

    if (widget.embedded) {
      return content;
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.directorioDf7b92),
        actions: [
          IconButton(
            icon: Icon(_showMap ? Icons.list : Icons.map),
            onPressed: () => setState(() => _showMap = !_showMap),
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _isLoading ? null : _loadDirectory,
          ),
        ],
      ),
      body: content,
    );
  }

  Widget _buildCompactTable(List<DirectoryNode> nodes) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        children: [
          ListTile(
            dense: true,
            title: Text(i18n.negocios2d41e3),
            subtitle: Text(i18n.toqueParaVerDetallesBa76e9),
          ),
          const Divider(height: 1),
          ...nodes.map((node) {
            final distance = _distanceKm(node) ?? node.distanciaKm;
            final isFav = _isFavorite(node);
            return InkWell(
              onTap: () => _showNodePreview(node),
              child: Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                child: Row(
                  children: [
                    SizedBox(
                      width: 34,
                      height: 34,
                      child: node.logoUrl.isNotEmpty
                          ? CircleAvatar(
                              backgroundImage: NetworkImage(node.logoUrl))
                          : const CircleAvatar(
                              child: Icon(Icons.store, size: 18)),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            node.nombre.isNotEmpty ? node.nombre : node.siteUrl,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            node.tipoEntidad.isNotEmpty
                                ? node.tipoEntidad
                                : node.siteUrl,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                    if (distance != null)
                      Text(
                        '${distance.toStringAsFixed(1)} km',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    const SizedBox(width: 8),
                    IconButton(
                      icon: Icon(isFav ? Icons.star : Icons.star_border,
                          size: 20),
                      onPressed:
                          _isLoading ? null : () => _toggleFavorite(node),
                    ),
                  ],
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildBusinessCard(DirectoryNode business) {
    final badges = <Widget>[];
    if (business.tipoEntidad.isNotEmpty) {
      badges.add(_buildBadge(business.tipoEntidad,
          color: _colorForType(business.tipoEntidad)));
    }
    if (business.nivelConsciencia.isNotEmpty) {
      badges.add(_buildBadge(business.nivelConsciencia,
          color: _colorForLevel(business.nivelConsciencia)));
    }
    if (business.verificado) {
      badges.add(_buildBadge('verificado', isSuccess: true));
    }
    final distancia = _distanceKm(business) ?? business.distanciaKm;
    if (distancia != null) {
      badges.add(_buildBadge('${distancia.toStringAsFixed(1)} km'));
    }

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 6),
      child: ListTile(
        onTap: () => _showNodePreview(business),
        leading: business.logoUrl.isNotEmpty
            ? CircleAvatar(backgroundImage: NetworkImage(business.logoUrl))
            : const CircleAvatar(child: Icon(Icons.store)),
        title: Text(
          business.nombre.isNotEmpty ? business.nombre : business.siteUrl,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              business.descripcionCorta.isNotEmpty
                  ? business.descripcionCorta
                  : business.siteUrl,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            if (badges.isNotEmpty) ...[
              const SizedBox(height: 6),
              Wrap(children: badges),
            ],
          ],
        ),
        trailing: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            IconButton(
              icon:
                  Icon(_isFavorite(business) ? Icons.star : Icons.star_border),
              onPressed: _isLoading ? null : () => _toggleFavorite(business),
            ),
            TextButton(
              onPressed: _isLoading ? null : () => _connectToBusiness(business),
              child: Text(i18n.conectar4b2e15),
            ),
          ],
        ),
        isThreeLine: badges.isNotEmpty,
        subtitleTextStyle: Theme.of(context).textTheme.bodySmall,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      ),
    );
  }

  Widget _buildBadge(String text, {bool isSuccess = false, Color? color}) {
    final bg =
        color ?? (isSuccess ? Colors.green.shade100 : Colors.blueGrey.shade100);
    final textColor =
        isSuccess ? Colors.green.shade800 : Colors.blueGrey.shade800;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      margin: const EdgeInsets.only(right: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(text, style: TextStyle(fontSize: 11, color: textColor)),
    );
  }

  Widget _buildMap() {
    if (MapLaunchHelper.usesEmbeddedGoogle) {
      return _buildGoogleMap();
    }

    final markers = _mapNodes
        .where((n) => n.latitud != null && n.longitud != null)
        .map((n) => Marker(
              point: LatLng(n.latitud!, n.longitud!),
              width: 40,
              height: 40,
              child: GestureDetector(
                onTap: () => _showNodePreview(n),
                child: Icon(Icons.location_on,
                    color: n.verificado ? Colors.green : Colors.red, size: 36),
              ),
            ))
        .toList();

    final center = _userLocation ??
        (markers.isNotEmpty
            ? markers.first.point
            : const LatLng(40.4168, -3.7038));

    return SizedBox(
      height: 320,
      child: FlutterMap(
        options: MapOptions(
          initialCenter: center,
          initialZoom: 6,
        ),
        children: [
          TileLayer(
            urlTemplate: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            subdomains: const ['a', 'b', 'c'],
            userAgentPackageName: 'flavor-chat-ia',
          ),
          if (_userLocation != null)
            MarkerLayer(
              markers: [
                Marker(
                  point: _userLocation!,
                  width: 30,
                  height: 30,
                  child: const Icon(Icons.my_location,
                      color: Colors.blue, size: 24),
                ),
              ],
            ),
          MarkerClusterLayerWidget(
            options: MarkerClusterLayerOptions(
              maxClusterRadius: 55,
              size: const Size(44, 44),
              markers: markers,
              builder: (context, clusterMarkers) {
                return Container(
                  decoration: BoxDecoration(
                    color: Colors.indigo.shade600,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                  ),
                  alignment: Alignment.center,
                  child: Text(
                    clusterMarkers.length.toString(),
                    style: const TextStyle(
                        color: Colors.white, fontWeight: FontWeight.bold),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildGoogleMap() {
    final nodes = _mapNodes
        .where((n) => n.latitud != null && n.longitud != null)
        .toList();
    final center = _userLocation ??
        (nodes.isNotEmpty
            ? LatLng(nodes.first.latitud!, nodes.first.longitud!)
            : const LatLng(40.4168, -3.7038));

    return SizedBox(
      height: 320,
      child: GoogleMarkerMap(
        apiKey: MapLaunchHelper.googleMapsApiKey,
        centerLat: center.latitude,
        centerLng: center.longitude,
        zoom: 6,
        userLat: _userLocation?.latitude,
        userLng: _userLocation?.longitude,
        markers: nodes
            .map(
              (node) => GoogleMarkerMapItem(
                id: _favoriteKeyFor(node),
                title: node.nombre.isNotEmpty ? node.nombre : node.siteUrl,
                subtitle: node.descripcionCorta.isNotEmpty
                    ? node.descripcionCorta
                    : node.siteUrl,
                latitude: node.latitud!,
                longitude: node.longitud!,
                colorHex: node.verificado ? '#16A34A' : '#DC2626',
              ),
            )
            .toList(),
        onMarkerTap: (markerId) {
          for (final node in nodes) {
            if (_favoriteKeyFor(node) == markerId) {
              _showNodePreview(node);
              break;
            }
          }
        },
      ),
    );
  }

  void _showNodePreview(DirectoryNode node) {
    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      builder: (context) {
        final profileUrl = _buildProfileUrl(node);
        final distancia = _distanceKm(node) ?? node.distanciaKm;

        return Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  node.logoUrl.isNotEmpty
                      ? CircleAvatar(
                          backgroundImage: NetworkImage(node.logoUrl))
                      : const CircleAvatar(child: Icon(Icons.store)),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      node.nombre.isNotEmpty ? node.nombre : node.siteUrl,
                      style: Theme.of(context)
                          .textTheme
                          .titleMedium
                          ?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ),
                  IconButton(
                    icon: Icon(
                        _isFavorite(node) ? Icons.star : Icons.star_border),
                    onPressed: () => _toggleFavorite(node),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(node.descripcionCorta.isNotEmpty
                  ? node.descripcionCorta
                  : node.siteUrl),
              const SizedBox(height: 12),
              Wrap(
                children: [
                  if (node.tipoEntidad.isNotEmpty)
                    _buildBadge(node.tipoEntidad,
                        color: _colorForType(node.tipoEntidad)),
                  if (node.nivelConsciencia.isNotEmpty)
                    _buildBadge(node.nivelConsciencia,
                        color: _colorForLevel(node.nivelConsciencia)),
                  if (node.verificado)
                    _buildBadge('verificado', isSuccess: true),
                  if (distancia != null)
                    _buildBadge('${distancia.toStringAsFixed(1)} km'),
                ],
              ),
              const SizedBox(height: 16),
              OutlinedButton.icon(
                onPressed: profileUrl == null
                    ? null
                    : () {
                        Navigator.pop(context);
                        _openExternalUrl(profileUrl);
                      },
                icon: const Icon(Icons.open_in_browser),
                label: Text(i18n.verPerfilD615f4),
              ),
              if (node.latitud != null && node.longitud != null) ...[
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _openNodeInMap(node);
                  },
                  icon: const Icon(Icons.map_outlined),
                  label: Text(MapLaunchHelper.providerLabel),
                ),
              ],
              const SizedBox(height: 8),
              FilledButton.icon(
                onPressed: () {
                  Navigator.pop(context);
                  _connectToBusiness(node);
                },
                icon: const Icon(Icons.link),
                label: Text(i18n.conectar4b2e15),
              ),
            ],
          ),
        );
      },
    );
  }

  double? _distanceKm(DirectoryNode node) {
    if (_userLocation == null) return null;
    if (node.latitud == null || node.longitud == null) return null;
    return _distance.as(LengthUnit.Kilometer, _userLocation!,
        LatLng(node.latitud!, node.longitud!));
  }

  String? _buildProfileUrl(DirectoryNode node) {
    if (node.siteUrl.isEmpty || node.slug.isEmpty) return null;
    final base = node.siteUrl.endsWith('/')
        ? node.siteUrl.substring(0, node.siteUrl.length - 1)
        : node.siteUrl;
    return '$base/?nodo=${node.slug}';
  }

  Color _colorForType(String type) {
    switch (type) {
      case 'cooperativa':
        return const Color(0xFFDCEAFE);
      case 'asociacion':
        return const Color(0xFFEDE9FE);
      case 'empresa':
        return const Color(0xFFFEE2E2);
      case 'comunidad':
        return const Color(0xFFDCFCE7);
      default:
        return Colors.blueGrey.shade100;
    }
  }

  Color _colorForLevel(String level) {
    switch (level) {
      case 'basico':
        return const Color(0xFFF3F4F6);
      case 'transicion':
        return const Color(0xFFFEF3C7);
      case 'consciente':
        return const Color(0xFFD1FAE5);
      case 'referente':
        return const Color(0xFFDBEAFE);
      default:
        return Colors.blueGrey.shade100;
    }
  }
}
