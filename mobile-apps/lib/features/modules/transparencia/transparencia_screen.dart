import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../../../core/providers/providers.dart';

class TransparenciaScreen extends ConsumerStatefulWidget {
  const TransparenciaScreen({super.key});

  @override
  ConsumerState<TransparenciaScreen> createState() =>
      _TransparenciaScreenState();
}

class _TransparenciaScreenState extends ConsumerState<TransparenciaScreen> {
  List<dynamic> _documentos = [];
  bool _loading = true;
  String? _error;

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
        setState(() {
          _documentos = response.data!['documentos'] ??
              response.data!['documents'] ??
              response.data!['items'] ??
              response.data!['data'] ??
              [];
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Portal de Transparencia'),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: () {
              // TODO: Implementar busqueda
            },
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.visibility,
                          size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_error!, textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadData,
                        child: const Text('Reintentar'),
                      ),
                    ],
                  ),
                )
              : _documentos.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.visibility,
                              size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text('No hay documentos disponibles'),
                          const SizedBox(height: 8),
                          const Text(
                            'Los documentos de transparencia apareceran aqui',
                            style: TextStyle(color: Colors.grey),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadData,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _documentos.length,
                        itemBuilder: (context, index) =>
                            _buildDocumentoCard(_documentos[index]),
                      ),
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
      default:
        formatoIcon = Icons.insert_drive_file;
        formatoColor = Colors.grey;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {
          // TODO: Ver/descargar documento
        },
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
                            fecha.toString(),
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
              const Icon(Icons.chevron_right, color: Colors.grey),
            ],
          ),
        ),
      ),
    );
  }
}
