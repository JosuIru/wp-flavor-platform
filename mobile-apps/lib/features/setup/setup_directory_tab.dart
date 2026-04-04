import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';
import '../../core/config/server_config.dart';
import '../../core/models/directory_models.dart';
import '../../core/services/business_directory_service.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart' show apiClientProvider;
import '../../core/utils/haptics.dart';
import '../../core/widgets/flavor_snackbar.dart';
import '../../core/widgets/flavor_state_widgets.dart';

/// Tab de directorio para la pantalla de setup inicial.
/// Permite descubrir y conectarse a comunidades sin tener servidor configurado.
class SetupDirectoryTab extends ConsumerStatefulWidget {
  final VoidCallback onSetupComplete;

  const SetupDirectoryTab({
    super.key,
    required this.onSetupComplete,
  });

  @override
  ConsumerState<SetupDirectoryTab> createState() => _SetupDirectoryTabState();
}

class _SetupDirectoryTabState extends ConsumerState<SetupDirectoryTab> {
  AppLocalizations get i18n => AppLocalizations.of(context);
  final _searchController = TextEditingController();
  final Distance _distanceCalculator = const Distance();

  bool _isLoading = false;
  bool _isConnecting = false;
  String? _error;
  DirectoryResponse? _directoryData;
  String _selectedTipo = '';
  LatLng? _userLocation;
  bool _sortByDistance = false;

  @override
  void initState() {
    super.initState();
    _loadDirectory();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadDirectory() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final bootstrapService = BusinessDirectoryService(
        serverUrl: ServerConfig.bootstrapServerUrl,
      );

      final directoryData = await bootstrapService.getDirectory(
        search: _searchController.text,
        tipo: _selectedTipo.isEmpty ? null : _selectedTipo,
      );

      if (!mounted) return;
      setState(() {
        _directoryData = directoryData;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = 'Error al cargar el directorio';
      });
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
          throw Exception('Permiso de ubicacion denegado');
        }
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      _userLocation = LatLng(position.latitude, position.longitude);

      final bootstrapService = BusinessDirectoryService(
        serverUrl: ServerConfig.bootstrapServerUrl,
      );

      final nearbyData = await bootstrapService.getNearby(
        lat: position.latitude,
        lng: position.longitude,
        radioKm: 100,
        limit: 50,
      );

      if (!mounted) return;
      setState(() {
        _directoryData = DirectoryResponse(
          nodos: nearbyData.nodos,
          total: nearbyData.nodos.length,
          pagina: 1,
          paginas: 1,
        );
        _sortByDistance = true;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isLoading = false;
        _error = 'No se pudo obtener la ubicacion';
      });
    }
  }

  Future<void> _connectToNode(DirectoryNode node) async {
    if (node.siteUrl.isEmpty) {
      FlavorSnackbar.showError(context, 'Este nodo no tiene URL disponible');
      return;
    }

    setState(() {
      _isConnecting = true;
      _error = null;
    });

    try {
      final serverUrl = node.siteUrl;
      final discoveryResponse = await ApiClient.discoverSiteAt(serverUrl);
      if (!discoveryResponse.success || discoveryResponse.data == null) {
        throw Exception(
            discoveryResponse.error ?? 'No se pudo leer app-discovery');
      }

      final apiNamespace = await ApiClient.detectPreferredApiNamespace(
        serverUrl,
        discoveryData: discoveryResponse.data,
      );
      final apiUrl = '$serverUrl$apiNamespace';
      final apiClient = ApiClient(baseUrl: apiUrl);
      final siteInfoResponse = await apiClient.getSiteInfo();

      if (!siteInfoResponse.success) {
        throw Exception('No se pudo conectar al servidor');
      }

      // Guardar servidor en ServerConfig
      await ServerConfig.setServerUrl(serverUrl);
      await ServerConfig.setApiNamespace(apiNamespace);

      // Actualizar apiClientProvider
      ref.read(apiClientProvider).updateBaseUrl(apiUrl);

      Haptics.success();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(i18n.commonConnectedTo(node.nombre)),
            backgroundColor: Colors.green,
          ),
        );

        // Llamar onSetupComplete
        widget.onSetupComplete();
      }
    } catch (e) {
      Haptics.error();
      if (!mounted) return;
      setState(() {
        _isConnecting = false;
        _error = 'No se pudo conectar: ${e.toString()}';
      });
    }
  }

  double? _calculateDistance(DirectoryNode node) {
    if (_userLocation == null) return null;
    if (node.latitud == null || node.longitud == null) return null;
    return _distanceCalculator.as(
      LengthUnit.Kilometer,
      _userLocation!,
      LatLng(node.latitud!, node.longitud!),
    );
  }

  @override
  Widget build(BuildContext context) {
    final nodes = List<DirectoryNode>.from(_directoryData?.nodos ?? []);

    // Ordenar por distancia si tenemos ubicacion
    if (_sortByDistance && _userLocation != null) {
      nodes.sort((a, b) {
        final distanceA = _calculateDistance(a) ?? double.infinity;
        final distanceB = _calculateDistance(b) ?? double.infinity;
        return distanceA.compareTo(distanceB);
      });
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Barra de busqueda
          TextField(
            controller: _searchController,
            decoration: InputDecoration(
              labelText: i18n.buscar113f74,
              prefixIcon: const Icon(Icons.search),
              border: const OutlineInputBorder(),
              suffixIcon: IconButton(
                icon: const Icon(Icons.clear),
                onPressed: () {
                  _searchController.clear();
                  _loadDirectory();
                },
              ),
            ),
            onSubmitted: (_) => _loadDirectory(),
          ),
          const SizedBox(height: 12),

          // Filtros en fila
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<String>(
                  value: _selectedTipo,
                  decoration: InputDecoration(
                    labelText: i18n.tipo4f427c,
                    border: const OutlineInputBorder(),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 8,
                    ),
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
                    _loadDirectory();
                  },
                ),
              ),
              const SizedBox(width: 8),
              // Boton "Cerca de mi"
              IconButton.filled(
                onPressed: _isLoading ? null : _loadNearby,
                icon: const Icon(Icons.my_location),
                tooltip: i18n.cercaDeMC17429,
              ),
              const SizedBox(width: 4),
              // Boton refrescar
              IconButton(
                onPressed: _isLoading ? null : _loadDirectory,
                icon: const Icon(Icons.refresh),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Mensaje de error
          if (_error != null)
            Container(
              padding: const EdgeInsets.all(12),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.errorContainer,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.error_outline,
                    color: Theme.of(context).colorScheme.error,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _error!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ),
                ],
              ),
            ),

          // Indicador de carga
          if (_isLoading)
            const FlavorLoadingState()
          else if (nodes.isEmpty)
            Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Column(
                  children: [
                    Icon(
                      Icons.search_off,
                      size: 48,
                      color: Theme.of(context).colorScheme.outline,
                    ),
                    const SizedBox(height: 16),
                    Text(
                      i18n.noHayNegociosEnElDirectorio7c8399,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.outline,
                      ),
                    ),
                  ],
                ),
              ),
            )
          else
            // Lista de nodos
            ...nodes.map((node) => _buildNodeCard(node)),
        ],
      ),
    );
  }

  Widget _buildNodeCard(DirectoryNode node) {
    final distancia = _calculateDistance(node) ?? node.distanciaKm;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        onTap: () => _showNodeDetails(node),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              // Avatar
              node.logoUrl.isNotEmpty
                  ? CircleAvatar(
                      backgroundImage: NetworkImage(node.logoUrl),
                      radius: 24,
                    )
                  : const CircleAvatar(
                      radius: 24,
                      child: Icon(Icons.store),
                    ),
              const SizedBox(width: 12),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      node.nombre.isNotEmpty ? node.nombre : node.siteUrl,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        if (node.tipoEntidad.isNotEmpty) ...[
                          _buildBadge(
                            node.tipoEntidad,
                            color: _colorForType(node.tipoEntidad),
                          ),
                          const SizedBox(width: 6),
                        ],
                        if (distancia != null)
                          _buildBadge('${distancia.toStringAsFixed(1)} km'),
                        if (node.verificado) ...[
                          const SizedBox(width: 6),
                          const Icon(
                            Icons.verified,
                            size: 16,
                            color: Colors.green,
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              // Boton conectar
              FilledButton(
                onPressed: _isConnecting ? null : () => _connectToNode(node),
                child: _isConnecting
                    ? const FlavorInlineSpinner(size: 16)
                    : Text(i18n.conectar4b2e15),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showNodeDetails(DirectoryNode node) {
    final distancia = _calculateDistance(node) ?? node.distanciaKm;

    showModalBottomSheet(
      context: context,
      showDragHandle: true,
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Cabecera
              Row(
                children: [
                  node.logoUrl.isNotEmpty
                      ? CircleAvatar(
                          backgroundImage: NetworkImage(node.logoUrl),
                          radius: 28,
                        )
                      : const CircleAvatar(
                          radius: 28,
                          child: Icon(Icons.store),
                        ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          node.nombre.isNotEmpty ? node.nombre : node.siteUrl,
                          style: Theme.of(context)
                              .textTheme
                              .titleMedium
                              ?.copyWith(fontWeight: FontWeight.bold),
                        ),
                        if (node.tipoEntidad.isNotEmpty)
                          Text(
                            node.tipoEntidad,
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                      ],
                    ),
                  ),
                  if (node.verificado)
                    const Icon(Icons.verified, color: Colors.green),
                ],
              ),
              const SizedBox(height: 16),

              // Descripcion
              if (node.descripcionCorta.isNotEmpty) Text(node.descripcionCorta),
              const SizedBox(height: 12),

              // Badges
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  if (node.nivelConsciencia.isNotEmpty)
                    _buildBadge(
                      node.nivelConsciencia,
                      color: _colorForLevel(node.nivelConsciencia),
                    ),
                  if (distancia != null)
                    _buildBadge('${distancia.toStringAsFixed(1)} km'),
                  if (node.ciudad.isNotEmpty) _buildBadge(node.ciudad),
                ],
              ),
              const SizedBox(height: 20),

              // Boton conectar
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _connectToNode(node);
                  },
                  icon: const Icon(Icons.link),
                  label: Text(i18n.conectar4b2e15),
                ),
              ),
              const SizedBox(height: 8),
            ],
          ),
        );
      },
    );
  }

  Widget _buildBadge(String text, {Color? color}) {
    final backgroundColor = color ?? Colors.blueGrey.shade100;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 11,
          color: Colors.blueGrey.shade800,
        ),
      ),
    );
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
