/// Filtros para buscar negocios en el directorio
class BusinessFilter {
  final String? region;
  final String? category;
  final String? search;
  final int? limit;

  BusinessFilter({
    this.region,
    this.category,
    this.search,
    this.limit,
  });

  /// Crear filtro vacío
  factory BusinessFilter.empty() {
    return BusinessFilter();
  }

  /// Copiar con cambios
  BusinessFilter copyWith({
    String? region,
    String? category,
    String? search,
    int? limit,
  }) {
    return BusinessFilter(
      region: region ?? this.region,
      category: category ?? this.category,
      search: search ?? this.search,
      limit: limit ?? this.limit,
    );
  }

  /// Verifica si hay filtros activos
  bool get hasFilters {
    return (region != null && region!.isNotEmpty) ||
        (category != null && category!.isNotEmpty) ||
        (search != null && search!.isNotEmpty);
  }

  /// Limpia todos los filtros
  BusinessFilter clear() {
    return BusinessFilter.empty();
  }

  @override
  String toString() {
    return 'BusinessFilter{region: $region, category: $category, search: $search, limit: $limit}';
  }
}
