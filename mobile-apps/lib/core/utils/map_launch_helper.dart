import '../../features/layouts/layout_config.dart';

class MapLaunchHelper {
  const MapLaunchHelper._();

  static String get provider {
    final value = LayoutService().config.mapProvider.trim().toLowerCase();
    return value.isEmpty ? 'osm' : value;
  }

  static String get googleMapsApiKey =>
      LayoutService().config.googleMapsApiKey.trim();

  static bool get usesEmbeddedGoogle =>
      provider.contains('google') && googleMapsApiKey.isNotEmpty;

  static Uri buildConfiguredMapUri(
    double lat,
    double lng, {
    String? query,
  }) {
    if (provider.contains('google')) {
      final effectiveQuery = (query ?? '').trim();
      if (effectiveQuery.isNotEmpty) {
        return Uri.parse(
          'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(effectiveQuery)}',
        );
      }
      return Uri.parse(
        'https://www.google.com/maps/search/?api=1&query=$lat,$lng',
      );
    }

    return Uri.parse(
      'https://www.openstreetmap.org/?mlat=$lat&mlon=$lng#map=18/$lat/$lng',
    );
  }

  static String get providerLabel =>
      provider.contains('google') ? 'Google Maps' : 'OpenStreetMap';

  static String buildEmbeddedMapUrl(
    double lat,
    double lng,
  ) {
    final googleApiKey = LayoutService().config.googleMapsApiKey.trim();

    if (provider.contains('google')) {
      if (googleApiKey.isNotEmpty) {
        return 'https://www.google.com/maps/embed/v1/place?key=${Uri.encodeComponent(googleApiKey)}&q=$lat,$lng&zoom=16';
      }
      return 'https://maps.google.com/maps?q=$lat,$lng&z=16&output=embed';
    }

    const latPadding = 0.01;
    const lngPadding = 0.01;
    final left = lng - lngPadding;
    final right = lng + lngPadding;
    final top = lat + latPadding;
    final bottom = lat - latPadding;
    return 'https://www.openstreetmap.org/export/embed.html?bbox=$left,$bottom,$right,$top&layer=mapnik&marker=$lat,$lng';
  }
}
