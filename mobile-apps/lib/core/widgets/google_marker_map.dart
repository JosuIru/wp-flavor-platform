import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class GoogleMarkerMapItem {
  final String id;
  final String title;
  final String? subtitle;
  final double latitude;
  final double longitude;
  final String? colorHex;

  const GoogleMarkerMapItem({
    required this.id,
    required this.title,
    this.subtitle,
    required this.latitude,
    required this.longitude,
    this.colorHex,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'subtitle': subtitle,
        'latitude': latitude,
        'longitude': longitude,
        'colorHex': colorHex,
      };
}

class GoogleMarkerMap extends StatefulWidget {
  final String apiKey;
  final List<GoogleMarkerMapItem> markers;
  final double centerLat;
  final double centerLng;
  final double zoom;
  final double? userLat;
  final double? userLng;
  final ValueChanged<String>? onMarkerTap;

  const GoogleMarkerMap({
    super.key,
    required this.apiKey,
    required this.markers,
    required this.centerLat,
    required this.centerLng,
    this.zoom = 12,
    this.userLat,
    this.userLng,
    this.onMarkerTap,
  });

  @override
  State<GoogleMarkerMap> createState() => _GoogleMarkerMapState();
}

class _GoogleMarkerMapState extends State<GoogleMarkerMap> {
  late final WebViewController _controller;
  bool _isLoading = true;
  double _progress = 0;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..addJavaScriptChannel(
        'MarkerTap',
        onMessageReceived: (message) {
          widget.onMarkerTap?.call(message.message);
        },
      )
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
    final markersJson = jsonEncode(widget.markers.map((m) => m.toJson()).toList());
    final userMarker = (widget.userLat != null && widget.userLng != null)
        ? '{"lat": ${widget.userLat}, "lng": ${widget.userLng}}'
        : 'null';

    return '''
<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <style>
      html, body, #map { height: 100%; width: 100%; margin: 0; padding: 0; }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=${widget.apiKey}"></script>
  </head>
  <body>
    <div id="map"></div>
    <script>
      const center = { lat: ${widget.centerLat}, lng: ${widget.centerLng} };
      const markers = $markersJson;
      const userMarker = $userMarker;
      const map = new google.maps.Map(document.getElementById('map'), {
        center,
        zoom: ${widget.zoom},
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
      });

      markers.forEach((item) => {
        const marker = new google.maps.Marker({
          position: { lat: item.latitude, lng: item.longitude },
          map,
          title: item.title
        });

        const infoHtml =
          '<div style="min-width:160px">' +
          '<strong>' + item.title + '</strong>' +
          (item.subtitle
            ? '<div style="margin-top:4px;color:#555">' + item.subtitle + '</div>'
            : '') +
          '</div>';
        const infoWindow = new google.maps.InfoWindow({ content: infoHtml });
        marker.addListener('click', () => {
          infoWindow.open(map, marker);
          if (window.MarkerTap) {
            window.MarkerTap.postMessage(item.id);
          }
        });
      });

      if (userMarker) {
        new google.maps.Marker({
          position: userMarker,
          map,
          title: 'Tu ubicación',
          icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 8,
            fillColor: '#2563EB',
            fillOpacity: 1,
            strokeColor: '#FFFFFF',
            strokeWeight: 2
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
    return Stack(
      children: [
        WebViewWidget(controller: _controller),
        if (_isLoading)
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: LinearProgressIndicator(value: _progress),
          ),
      ],
    );
  }
}
