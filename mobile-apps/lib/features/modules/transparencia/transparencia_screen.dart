import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/providers/providers.dart';
import '../../../core/utils/flavor_url_launcher.dart';
import '../../../core/widgets/flavor_snackbar.dart';
import '../../../core/widgets/flavor_state_widgets.dart';

part 'transparencia_screen_parts.dart';

class TransparenciaScreen extends ConsumerStatefulWidget {
  const TransparenciaScreen({super.key});

  @override
  ConsumerState<TransparenciaScreen> createState() =>
      _TransparenciaScreenState();
}

class _TransparenciaScreenState extends ConsumerState<TransparenciaScreen> {
  List<dynamic> _documentos = [];
  List<dynamic> _documentosFiltrados = [];
  bool _loading = true;
  String? _error;
  String _busqueda = '';
  String? _categoriaSeleccionada;
  List<String> _categorias = [];
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/transparencia');
      if (response.success && response.data != null) {
        final docs = response.data!['documentos'] ??
            response.data!['documents'] ??
            response.data!['items'] ??
            response.data!['data'] ??
            [];

        // Extraer categorías únicas
        final categoriasSet = <String>{};
        for (final doc in docs) {
          final cat = (doc as Map<String, dynamic>)['categoria'] ??
              doc['category'] ??
              doc['tipo'];
          if (cat != null && cat.toString().isNotEmpty) {
            categoriasSet.add(cat.toString());
          }
        }

        setState(() {
          _documentos = docs;
          _documentosFiltrados = docs;
          _categorias = categoriasSet.toList()..sort();
          _loading = false;
        });
      } else {
        setState(() {
          _error = response.error ?? 'Error al cargar documentos';
          _loading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  void _filtrarDocumentos() {
    setState(() {
      _documentosFiltrados = _documentos.where((doc) {
        final documentoMap = doc as Map<String, dynamic>;
        final titulo = (documentoMap['titulo'] ??
            documentoMap['title'] ??
            documentoMap['nombre'] ??
            '').toString().toLowerCase();
        final descripcion = (documentoMap['descripcion'] ??
            documentoMap['description'] ??
            '').toString().toLowerCase();
        final categoria = (documentoMap['categoria'] ??
            documentoMap['category'] ??
            documentoMap['tipo'] ??
            '').toString();

        final matchBusqueda = _busqueda.isEmpty ||
            titulo.contains(_busqueda.toLowerCase()) ||
            descripcion.contains(_busqueda.toLowerCase());

        final matchCategoria = _categoriaSeleccionada == null ||
            categoria == _categoriaSeleccionada;

        return matchBusqueda && matchCategoria;
      }).toList();
    });
  }

  void _mostrarBusqueda(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
          left: 16,
          right: 16,
          top: 16,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Buscar documentos',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _searchController,
              autofocus: true,
              decoration: InputDecoration(
                hintText: 'Buscar por título o descripción...',
                prefixIcon: const Icon(Icons.search),
                border: const OutlineInputBorder(),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          setState(() {
                            _busqueda = '';
                            _filtrarDocumentos();
                          });
                        },
                      )
                    : null,
              ),
              onChanged: (value) {
                setState(() {
                  _busqueda = value;
                  _filtrarDocumentos();
                });
              },
            ),
            const SizedBox(height: 16),
            if (_categorias.isNotEmpty) ...[
              Text(
                'Filtrar por categoría',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  FilterChip(
                    label: const Text('Todas'),
                    selected: _categoriaSeleccionada == null,
                    onSelected: (selected) {
                      setState(() {
                        _categoriaSeleccionada = null;
                        _filtrarDocumentos();
                      });
                      Navigator.pop(context);
                    },
                  ),
                  ..._categorias.map((cat) => FilterChip(
                    label: Text(cat),
                    selected: _categoriaSeleccionada == cat,
                    onSelected: (selected) {
                      setState(() {
                        _categoriaSeleccionada = selected ? cat : null;
                        _filtrarDocumentos();
                      });
                      Navigator.pop(context);
                    },
                  )),
                ],
              ),
            ],
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Aplicar filtros'),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final hayFiltrosActivos = _busqueda.isNotEmpty || _categoriaSeleccionada != null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Portal de Transparencia'),
        actions: [
          Stack(
            alignment: Alignment.center,
            children: [
              IconButton(
                icon: const Icon(Icons.search),
                onPressed: () => _mostrarBusqueda(context),
              ),
              if (hayFiltrosActivos)
                Positioned(
                  top: 8,
                  right: 8,
                  child: Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
            ],
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _loading
          ? const FlavorLoadingState()
          : _error != null
              ? FlavorErrorState(
                  message: _error!,
                  onRetry: _loadData,
                  icon: Icons.visibility,
                )
              : Column(
                  children: [
                    // Mostrar filtros activos
                    if (hayFiltrosActivos)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        color: Theme.of(context).colorScheme.primaryContainer.withOpacity(0.3),
                        child: Wrap(
                          spacing: 8,
                          children: [
                            if (_busqueda.isNotEmpty)
                              Chip(
                                label: Text('Búsqueda: "$_busqueda"'),
                                deleteIcon: const Icon(Icons.close, size: 18),
                                onDeleted: () {
                                  _searchController.clear();
                                  setState(() {
                                    _busqueda = '';
                                    _filtrarDocumentos();
                                  });
                                },
                              ),
                            if (_categoriaSeleccionada != null)
                              Chip(
                                label: Text('Categoría: $_categoriaSeleccionada'),
                                deleteIcon: const Icon(Icons.close, size: 18),
                                onDeleted: () {
                                  setState(() {
                                    _categoriaSeleccionada = null;
                                    _filtrarDocumentos();
                                  });
                                },
                              ),
                          ],
                        ),
                      ),

                    // Lista de documentos
                    Expanded(
                      child: _documentosFiltrados.isEmpty
                          ? FlavorEmptyState(
                              icon: Icons.visibility_outlined,
                              title: hayFiltrosActivos
                                  ? 'No hay documentos que coincidan con los filtros'
                                  : 'No hay documentos disponibles',
                              action: hayFiltrosActivos
                                  ? TextButton(
                                      onPressed: () {
                                        _searchController.clear();
                                        setState(() {
                                          _busqueda = '';
                                          _categoriaSeleccionada = null;
                                          _filtrarDocumentos();
                                        });
                                      },
                                      child: const Text('Limpiar filtros'),
                                    )
                                  : null,
                            )
                          : RefreshIndicator(
                              onRefresh: _loadData,
                              child: ListView.builder(
                                padding: const EdgeInsets.all(16),
                                itemCount: _documentosFiltrados.length,
                                itemBuilder: (context, index) =>
                                    _buildDocumentoCard(_documentosFiltrados[index]),
                              ),
                            ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildDocumentoCard(dynamic item) {
    final documentoMap = item as Map<String, dynamic>;
    final titulo = documentoMap['titulo'] ??
        documentoMap['title'] ??
        documentoMap['nombre'] ??
        'Documento';
    final descripcion = documentoMap['descripcion'] ??
        documentoMap['description'] ??
        '';
    final categoria = documentoMap['categoria'] ??
        documentoMap['category'] ??
        documentoMap['tipo'] ??
        'General';
    final fecha = documentoMap['fecha'] ??
        documentoMap['date'] ??
        documentoMap['fecha_publicacion'] ??
        '';
    final formato = documentoMap['formato'] ??
        documentoMap['format'] ??
        documentoMap['extension'] ??
        'pdf';
    final descargas = documentoMap['descargas'] ??
        documentoMap['downloads'] ??
        0;
    final urlDescarga = documentoMap['url'] ??
        documentoMap['url_descarga'] ??
        documentoMap['download_url'] ??
        '';
    final tamano = documentoMap['tamano'] ??
        documentoMap['size'] ??
        documentoMap['file_size'] ??
        '';

    IconData formatoIcon;
    Color formatoColor;
    switch (formato.toString().toLowerCase()) {
      case 'pdf':
        formatoIcon = Icons.picture_as_pdf;
        formatoColor = Colors.red;
        break;
      case 'xlsx':
      case 'xls':
      case 'excel':
        formatoIcon = Icons.table_chart;
        formatoColor = Colors.green;
        break;
      case 'docx':
      case 'doc':
      case 'word':
        formatoIcon = Icons.article;
        formatoColor = Colors.blue;
        break;
      case 'csv':
        formatoIcon = Icons.grid_on;
        formatoColor = Colors.teal;
        break;
      case 'zip':
      case 'rar':
        formatoIcon = Icons.folder_zip;
        formatoColor = Colors.orange;
        break;
      case 'jpg':
      case 'jpeg':
      case 'png':
      case 'gif':
        formatoIcon = Icons.image;
        formatoColor = Colors.purple;
        break;
      default:
        formatoIcon = Icons.insert_drive_file;
        formatoColor = Colors.grey;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () => _verDetalleDocumento(context, documentoMap),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: formatoColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(formatoIcon, color: formatoColor, size: 28),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      titulo.toString(),
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.blue.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            categoria.toString(),
                            style: const TextStyle(
                              fontSize: 11,
                              color: Colors.blue,
                            ),
                          ),
                        ),
                        if (fecha.toString().isNotEmpty) ...[
                          const SizedBox(width: 8),
                          Icon(Icons.calendar_today,
                              size: 12, color: Colors.grey[600]),
                          const SizedBox(width: 4),
                          Text(
                            _formatDate(fecha.toString()),
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ],
                    ),
                    if (descripcion.toString().isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        descripcion.toString(),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(Icons.download, size: 14, color: Colors.grey[500]),
                        const SizedBox(width: 4),
                        Text(
                          '$descargas descargas',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey[500],
                          ),
                        ),
                        if (tamano.toString().isNotEmpty) ...[
                          const SizedBox(width: 12),
                          Icon(Icons.storage, size: 14, color: Colors.grey[500]),
                          const SizedBox(width: 4),
                          Text(
                            tamano.toString(),
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[500],
                            ),
                          ),
                        ],
                        const Spacer(),
                        Text(
                          formato.toString().toUpperCase(),
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: formatoColor,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Column(
                children: [
                  const Icon(Icons.chevron_right, color: Colors.grey),
                  const SizedBox(height: 8),
                  if (urlDescarga.toString().isNotEmpty)
                    IconButton(
                      icon: Icon(Icons.download, color: formatoColor),
                      onPressed: () => _descargarDocumento(urlDescarga.toString(), titulo.toString()),
                      tooltip: 'Descargar',
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _descargarDocumento(String url, String titulo) async {
    final launched = await FlavorUrlLauncher.openExternal(
      context,
      url,
      emptyMessage: 'URL de descarga no disponible',
      errorMessage: 'No se puede abrir el enlace',
    );
    if (!mounted) return;
    if (launched) {
      FlavorSnackbar.showSuccess(context, 'Descargando: $titulo');
    }
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
    } catch (_) {
      return dateStr;
    }
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }
}
