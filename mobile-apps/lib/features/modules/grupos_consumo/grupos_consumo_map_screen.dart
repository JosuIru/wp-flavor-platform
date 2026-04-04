import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/utils/map_launch_helper.dart';

class GruposConsumoMapScreen extends StatefulWidget {
  final String title;
  final List<Map<String, dynamic>> markers;
  final List<Map<String, dynamic>> routes;
  final String provider;
  final String googleMapsApiKey;
  final bool showRoutes;

  const GruposConsumoMapScreen({
    super.key,
    required this.title,
    required this.markers,
    required this.provider,
    required this.googleMapsApiKey,
    this.routes = const [],
    this.showRoutes = false,
  });

  @override
  State<GruposConsumoMapScreen> createState() => _GruposConsumoMapScreenState();
}

class _GruposConsumoMapScreenState extends State<GruposConsumoMapScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;

  String get _effectiveProvider {
    final provider = widget.provider.trim().toLowerCase();
    return provider.isEmpty ? MapLaunchHelper.provider : provider;
  }

  String get _effectiveGoogleMapsApiKey {
    final key = widget.googleMapsApiKey.trim();
    return key.isEmpty ? MapLaunchHelper.googleMapsApiKey : key;
  }

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (progress) {
            setState(() {
              _progress = progress / 100;
            });
          },
          onPageStarted: (_) {
            setState(() {
              _isLoading = true;
            });
          },
          onPageFinished: (_) {
            setState(() {
              _isLoading = false;
            });
          },
          onWebResourceError: (_) {
            setState(() {
              _isLoading = false;
            });
          },
        ),
      )
      ..loadHtmlString(_buildHtml());
  }

  String _buildHtml() {
    final markers = widget.markers.map((m) {
      return {
        'title': m['title'],
        'lat': m['lat'],
        'lng': m['lng'],
        'address': m['address'],
      };
    }).toList();
    final routes = widget.routes.map((r) {
      return {
        'from': r['from'],
        'to': r['to'],
      };
    }).toList();
    final markersJson = jsonEncode(markers);
    final routesJson = jsonEncode(routes);
    final useGoogle =
        _effectiveProvider == 'google' && _effectiveGoogleMapsApiKey.isNotEmpty;
    final googleKey = _effectiveGoogleMapsApiKey;
    return '''
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  ${useGoogle ? '' : '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>'}
  <style>
    html, body, #map { height: 100%; margin: 0; padding: 0; }
  </style>
</head>
<body>
  <div id="map"></div>
  ${useGoogle ? '<script src="https://maps.googleapis.com/maps/api/js?key=$googleKey"></script>' : '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>'}
  <script>
    const markers = $markersJson;
    const routes = $routesJson;
    const useGoogle = ${useGoogle ? 'true' : 'false'};
    const showRoutes = ${widget.showRoutes ? 'true' : 'false'};
    const cacheKey = (address) => 'gc_geo_' + address.toLowerCase().trim();
    const getCachedGeocode = (address) => {
      try {
        const raw = localStorage.getItem(cacheKey(address));
        if (!raw) return null;
        const data = JSON.parse(raw);
        if (data && typeof data.lat === 'number' && typeof data.lng === 'number') return data;
      } catch (e) {
        console.warn('Error leyendo geocode cache:', e);
      }
      return null;
    };
    const setCachedGeocode = (address, lat, lng) => {
      try {
        localStorage.setItem(cacheKey(address), JSON.stringify({ lat, lng, ts: Date.now() }));
      } catch (e) {
        console.warn('Error guardando geocode cache:', e);
      }
    };

    if (useGoogle) {
      const map = new google.maps.Map(document.getElementById('map'), {
        zoom: 6,
        center: { lat: 40.4168, lng: -3.7038 },
      });
      const bounds = new google.maps.LatLngBounds();
      const geocoder = new google.maps.Geocoder();
      let pending = 0;
      const points = [];
      const routePairs = [];

      const resolvePoint = (node, cb) => {
        if (!node) return cb(null);
        if (node.lat && node.lng) return cb({ lat: node.lat, lng: node.lng });
        if (!node.address) return cb(null);
        const cached = getCachedGeocode(node.address);
        if (cached) return cb(cached);
        geocoder.geocode({ address: node.address }, (results, status) => {
          if (status === 'OK' && results[0]) {
            const position = results[0].geometry.location;
            const lat = position.lat();
            const lng = position.lng();
            setCachedGeocode(node.address, lat, lng);
            return cb({ lat, lng });
          }
          return cb(null);
        });
      };

      markers.forEach(m => {
        if (m.lat && m.lng) {
          const position = { lat: m.lat, lng: m.lng };
          const marker = new google.maps.Marker({ position, map, title: m.title || '' });
          bounds.extend(position);
          points.push(position);
        } else if (m.address) {
          pending++;
          resolvePoint(m, (position) => {
            if (position) {
              const marker = new google.maps.Marker({ position, map, title: m.title || m.address });
              bounds.extend(position);
              points.push(position);
            }
            pending--;
            if (pending === 0 && !bounds.isEmpty()) {
              map.fitBounds(bounds);
              if (showRoutes && points.length > 1) {
                const routePath = new google.maps.Polyline({
                  path: points,
                  geodesic: true,
                  strokeColor: '#2e7d32',
                  strokeOpacity: 0.9,
                  strokeWeight: 3,
                });
                routePath.setMap(map);
              }
              if (routePairs.length > 0) {
                routePairs.forEach(pair => {
                  const path = new google.maps.Polyline({
                    path: [pair.from, pair.to],
                    geodesic: true,
                    strokeColor: '#1565c0',
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                  });
                  path.setMap(map);
                });
              }
            }
          });
        }
      });

      routes.forEach(r => {
        if (!r.from || !r.to) return;
        pending++;
        resolvePoint(r.from, (fromPoint) => {
          resolvePoint(r.to, (toPoint) => {
            if (fromPoint && toPoint) {
              routePairs.push({ from: fromPoint, to: toPoint });
              bounds.extend(fromPoint);
              bounds.extend(toPoint);
            }
            pending--;
            if (pending === 0 && !bounds.isEmpty()) {
              map.fitBounds(bounds);
              if (showRoutes && points.length > 1) {
                const routePath = new google.maps.Polyline({
                  path: points,
                  geodesic: true,
                  strokeColor: '#2e7d32',
                  strokeOpacity: 0.9,
                  strokeWeight: 3,
                });
                routePath.setMap(map);
              }
              if (routePairs.length > 0) {
                routePairs.forEach(pair => {
                  const path = new google.maps.Polyline({
                    path: [pair.from, pair.to],
                    geodesic: true,
                    strokeColor: '#1565c0',
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                  });
                  path.setMap(map);
                });
              }
            }
          });
        });
      });

      if (!bounds.isEmpty()) {
        map.fitBounds(bounds);
        if (showRoutes && points.length > 1) {
          const routePath = new google.maps.Polyline({
            path: points,
            geodesic: true,
            strokeColor: '#2e7d32',
            strokeOpacity: 0.9,
            strokeWeight: 3,
          });
          routePath.setMap(map);
        }
        if (routePairs.length > 0) {
          routePairs.forEach(pair => {
            const path = new google.maps.Polyline({
              path: [pair.from, pair.to],
              geodesic: true,
              strokeColor: '#1565c0',
              strokeOpacity: 0.8,
              strokeWeight: 2,
            });
            path.setMap(map);
          });
        }
      }
    } else {
      const map = L.map('map');
      const bounds = [];
      const pending = [];
      const points = [];
      const routePairs = [];

      const resolvePoint = (node) => {
        if (!node) return Promise.resolve(null);
        if (node.lat && node.lng) return Promise.resolve([node.lat, node.lng]);
        if (!node.address) return Promise.resolve(null);
        const cached = getCachedGeocode(node.address);
        if (cached) return Promise.resolve([cached.lat, cached.lng]);
        return fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(node.address))
          .then(r => r.json())
          .then(results => {
            if (results && results.length > 0) {
              const lat = parseFloat(results[0].lat);
              const lng = parseFloat(results[0].lon);
              setCachedGeocode(node.address, lat, lng);
              return [lat, lng];
            }
            return null;
          })
          .catch(() => null);
      };

      markers.forEach(m => {
        if (m.lat && m.lng) {
          const marker = L.marker([m.lat, m.lng]).addTo(map);
          if (m.title) marker.bindPopup(m.title);
          bounds.push([m.lat, m.lng]);
          points.push([m.lat, m.lng]);
        } else if (m.address) {
          pending.push(
            resolvePoint(m).then(point => {
              if (point) {
                const marker = L.marker(point).addTo(map);
                if (m.title || m.address) marker.bindPopup(m.title || m.address);
                bounds.push(point);
                points.push(point);
              }
            })
          );
        }
      });

      routes.forEach(r => {
        if (!r.from || !r.to) return;
        pending.push(
          Promise.all([resolvePoint(r.from), resolvePoint(r.to)]).then(pointsPair => {
            const fromPoint = pointsPair[0];
            const toPoint = pointsPair[1];
            if (fromPoint && toPoint) {
              routePairs.push([fromPoint, toPoint]);
              bounds.push(fromPoint, toPoint);
            }
          })
        );
      });

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      Promise.all(pending).finally(() => {
        if (bounds.length > 0) {
          map.fitBounds(bounds, { padding: [24, 24] });
          if (showRoutes && points.length > 1) {
            L.polyline(points, { color: '#2e7d32', weight: 3 }).addTo(map);
          }
          if (routePairs.length > 0) {
            routePairs.forEach(pair => {
              L.polyline(pair, { color: '#1565c0', weight: 2, opacity: 0.8 }).addTo(map);
            });
          }
        } else {
          map.setView([40.4168, -3.7038], 5);
        }
      });
    }
  </script>
</body>
</html>
''';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
        actions: [
          IconButton(
            tooltip: MapLaunchHelper.providerLabel,
            onPressed: _openCurrentMapExternally,
            icon: const Icon(Icons.open_in_new),
          ),
        ],
        bottom: _isLoading
            ? PreferredSize(
                preferredSize: const Size.fromHeight(4),
                child: LinearProgressIndicator(value: _progress),
              )
            : null,
      ),
      body: WebViewWidget(controller: _controller),
    );
  }

  Future<void> _openCurrentMapExternally() async {
    Map<String, dynamic>? target;
    for (final marker in widget.markers) {
      if (marker['lat'] != null && marker['lng'] != null) {
        target = marker;
        break;
      }
    }
    target ??= widget.markers.isNotEmpty ? widget.markers.first : null;
    if (target == null) return;

    final lat = double.tryParse((target['lat'] ?? '').toString());
    final lng = double.tryParse((target['lng'] ?? '').toString());
    if (lat == null || lng == null) return;

    final uri = MapLaunchHelper.buildConfiguredMapUri(
      lat,
      lng,
      query: target['title']?.toString() ?? target['address']?.toString() ?? '',
    );
    if (!mounted) return;
    await FlavorUrlLauncher.openExternalUri(
      context,
      uri,
      errorMessage: 'No se puede abrir el mapa',
    );
  }
}
