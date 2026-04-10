part of 'documentacion_legal_screen.dart';

// =============================================================================
// TARJETA DE DOCUMENTO
// =============================================================================

class _DocumentoCard extends StatelessWidget {
  final Map<String, dynamic> documento;
  final bool esFavorito;
  final VoidCallback onTap;

  const _DocumentoCard({
    required this.documento,
    this.esFavorito = false,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final titulo = documento['titulo'] ?? 'Sin título';
    final tipo = documento['tipo'] ?? 'otro';
    final categoria = documento['categoria'] ?? '';
    final ambito = documento['ambito'] ?? 'estatal';
    final fechaPublicacion = documento['fecha_publicacion'];
    final descargas = documento['descargas'] ?? 0;
    final verificado = documento['verificado'] == 1 || documento['verificado'] == true;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header: icono + badges
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Icono según tipo
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: _getColorTipo(tipo).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      _getIconoTipo(tipo),
                      color: _getColorTipo(tipo),
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Badges
                        Wrap(
                          spacing: 6,
                          runSpacing: 4,
                          children: [
                            _TipoBadge(tipo: tipo),
                            _AmbitoBadge(ambito: ambito),
                            if (verificado)
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: Colors.green.shade100,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(Icons.verified,
                                        size: 12, color: Colors.green.shade700),
                                    const SizedBox(width: 2),
                                    Text(
                                      'Verificado',
                                      style: TextStyle(
                                        fontSize: 10,
                                        color: Colors.green.shade700,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        // Título
                        Text(
                          titulo,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 15,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  if (esFavorito)
                    const Icon(Icons.star, color: Colors.amber, size: 20),
                ],
              ),
              const SizedBox(height: 12),

              // Descripción (si existe)
              if (documento['descripcion'] != null &&
                  documento['descripcion'].toString().isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Text(
                    documento['descripcion'],
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontSize: 13,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),

              // Footer: categoría, fecha, descargas
              Row(
                children: [
                  if (categoria.isNotEmpty) ...[
                    Icon(Icons.folder, size: 14, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text(
                        categoria,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade600,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 12),
                  ],
                  if (fechaPublicacion != null) ...[
                    Icon(Icons.calendar_today,
                        size: 14, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Text(
                      _formatearFecha(fechaPublicacion),
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                      ),
                    ),
                    const SizedBox(width: 12),
                  ],
                  if (descargas > 0) ...[
                    Icon(Icons.download, size: 14, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Text(
                      '$descargas',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ],
                  const Spacer(),
                  Icon(Icons.arrow_forward_ios,
                      size: 14, color: Colors.grey.shade400),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatearFecha(String? fecha) {
    if (fecha == null) return '';
    try {
      final partes = fecha.split('-');
      if (partes.length >= 3) {
        return '${partes[2]}/${partes[1]}/${partes[0]}';
      }
    } catch (_) {}
    return fecha;
  }

  IconData _getIconoTipo(String tipo) {
    switch (tipo) {
      case 'ley':
        return Icons.gavel;
      case 'decreto':
        return Icons.description;
      case 'ordenanza':
        return Icons.location_city;
      case 'sentencia':
        return Icons.balance;
      case 'modelo_denuncia':
        return Icons.report_problem;
      case 'modelo_recurso':
        return Icons.assignment;
      case 'guia':
        return Icons.menu_book;
      case 'informe':
        return Icons.analytics;
      default:
        return Icons.article;
    }
  }

  Color _getColorTipo(String tipo) {
    switch (tipo) {
      case 'ley':
        return Colors.purple.shade700;
      case 'decreto':
        return Colors.blue.shade700;
      case 'ordenanza':
        return Colors.teal.shade700;
      case 'sentencia':
        return Colors.red.shade700;
      case 'modelo_denuncia':
        return Colors.orange.shade700;
      case 'modelo_recurso':
        return Colors.amber.shade700;
      case 'guia':
        return Colors.green.shade700;
      case 'informe':
        return Colors.indigo.shade700;
      default:
        return Colors.grey.shade700;
    }
  }
}

// =============================================================================
// BADGE DE TIPO
// =============================================================================

class _TipoBadge extends StatelessWidget {
  final String tipo;

  const _TipoBadge({required this.tipo});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: _getColor().withOpacity(0.1),
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: _getColor().withOpacity(0.3)),
      ),
      child: Text(
        _getLabel(),
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: _getColor(),
        ),
      ),
    );
  }

  String _getLabel() {
    switch (tipo) {
      case 'ley':
        return 'LEY';
      case 'decreto':
        return 'DECRETO';
      case 'ordenanza':
        return 'ORDENANZA';
      case 'sentencia':
        return 'SENTENCIA';
      case 'modelo_denuncia':
        return 'MOD. DENUNCIA';
      case 'modelo_recurso':
        return 'MOD. RECURSO';
      case 'guia':
        return 'GUÍA';
      case 'informe':
        return 'INFORME';
      default:
        return tipo.toUpperCase();
    }
  }

  Color _getColor() {
    switch (tipo) {
      case 'ley':
        return Colors.purple.shade700;
      case 'decreto':
        return Colors.blue.shade700;
      case 'ordenanza':
        return Colors.teal.shade700;
      case 'sentencia':
        return Colors.red.shade700;
      case 'modelo_denuncia':
        return Colors.orange.shade700;
      case 'modelo_recurso':
        return Colors.amber.shade700;
      case 'guia':
        return Colors.green.shade700;
      case 'informe':
        return Colors.indigo.shade700;
      default:
        return Colors.grey.shade700;
    }
  }
}

// =============================================================================
// BADGE DE ÁMBITO
// =============================================================================

class _AmbitoBadge extends StatelessWidget {
  final String ambito;

  const _AmbitoBadge({required this.ambito});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: Colors.grey.shade100,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        _getLabel(),
        style: TextStyle(
          fontSize: 10,
          color: Colors.grey.shade700,
        ),
      ),
    );
  }

  String _getLabel() {
    switch (ambito) {
      case 'europeo':
        return '🇪🇺 Europeo';
      case 'estatal':
        return '🇪🇸 Estatal';
      case 'autonomico':
        return 'Autonómico';
      case 'provincial':
        return 'Provincial';
      case 'municipal':
        return 'Municipal';
      default:
        return ambito;
    }
  }
}

// =============================================================================
// TARJETA DE CATEGORÍA
// =============================================================================

class _CategoriaCard extends StatelessWidget {
  final Map<String, dynamic> categoria;
  final VoidCallback onTap;

  const _CategoriaCard({
    required this.categoria,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final nombre = categoria['nombre'] ?? 'Sin nombre';
    final descripcion = categoria['descripcion'] ?? '';
    final colorHex = categoria['color'] ?? '#6366f1';
    final color = _hexToColor(colorHex);

    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                color.withOpacity(0.1),
                color.withOpacity(0.05),
              ],
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  _getIconoCategoria(categoria['slug'] ?? ''),
                  color: color,
                  size: 24,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                nombre,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                  color: color,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              if (descripcion.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  descripcion,
                  style: TextStyle(
                    fontSize: 11,
                    color: Colors.grey.shade600,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Color _hexToColor(String hex) {
    try {
      hex = hex.replaceFirst('#', '');
      if (hex.length == 6) {
        return Color(int.parse('FF$hex', radix: 16));
      }
    } catch (_) {}
    return Colors.indigo;
  }

  IconData _getIconoCategoria(String slug) {
    switch (slug) {
      case 'medio-ambiente':
        return Icons.eco;
      case 'urbanismo':
        return Icons.location_city;
      case 'aguas':
        return Icons.water_drop;
      case 'montes':
        return Icons.park;
      case 'patrimonio':
        return Icons.account_balance;
      case 'participacion':
        return Icons.groups;
      case 'transparencia':
        return Icons.visibility;
      case 'derechos':
        return Icons.shield;
      case 'energia':
        return Icons.bolt;
      case 'agricultura':
        return Icons.grass;
      default:
        return Icons.folder;
    }
  }
}

// =============================================================================
// PANTALLA DE DETALLE
// =============================================================================

class _DocumentoDetalleScreen extends ConsumerStatefulWidget {
  final int documentoId;

  const _DocumentoDetalleScreen({required this.documentoId});

  @override
  ConsumerState<_DocumentoDetalleScreen> createState() =>
      _DocumentoDetalleScreenState();
}

class _DocumentoDetalleScreenState
    extends ConsumerState<_DocumentoDetalleScreen> {
  Map<String, dynamic>? _documento;
  bool _cargando = true;
  bool _esFavorito = false;
  final TextEditingController _comentarioController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _cargarDocumento();
  }

  @override
  void dispose() {
    _comentarioController.dispose();
    super.dispose();
  }

  Future<void> _cargarDocumento() async {
    setState(() => _cargando = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta =
          await api.get('/documentacion-legal/${widget.documentoId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _documento = respuesta.data!['documento'];
        });
      }
    } finally {
      setState(() => _cargando = false);
    }
  }

  Future<void> _toggleFavorito() async {
    final api = ref.read(apiClientProvider);
    final respuesta =
        await api.post('/documentacion-legal/${widget.documentoId}/favorito');
    if (respuesta.success && respuesta.data != null) {
      setState(() {
        _esFavorito = respuesta.data!['es_favorito'] ?? false;
      });
      if (mounted) {
        FlavorSnackbar.show(
          context,
          respuesta.data!['mensaje'] ?? 'Acción completada',
          type: SnackbarType.success,
        );
      }
    }
  }

  Future<void> _registrarDescarga() async {
    final api = ref.read(apiClientProvider);
    await api.post('/documentacion-legal/${widget.documentoId}/descargar');
  }

  Future<void> _enviarComentario() async {
    final texto = _comentarioController.text.trim();
    if (texto.isEmpty) return;

    final api = ref.read(apiClientProvider);
    final respuesta = await api.post(
      '/documentacion-legal/${widget.documentoId}/comentarios',
      data: {'comentario': texto, 'tipo': 'nota'},
    );

    if (respuesta.success) {
      _comentarioController.clear();
      _cargarDocumento();
      if (mounted) {
        FlavorSnackbar.show(
          context,
          'Comentario añadido',
          type: SnackbarType.success,
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_cargando) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Documento'),
          backgroundColor: Colors.indigo.shade700,
          foregroundColor: Colors.white,
        ),
        body: const FlavorLoadingState(),
      );
    }

    if (_documento == null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Documento'),
          backgroundColor: Colors.indigo.shade700,
          foregroundColor: Colors.white,
        ),
        body: const FlavorEmptyState(
          icon: Icons.error_outline,
          title: 'Documento no encontrado',
        ),
      );
    }

    final titulo = _documento!['titulo'] ?? 'Sin título';
    final descripcion = _documento!['descripcion'] ?? '';
    final contenido = _documento!['contenido'] ?? '';
    final tipo = _documento!['tipo'] ?? 'otro';
    final categoria = _documento!['categoria'] ?? '';
    final ambito = _documento!['ambito'] ?? 'estatal';
    final fechaPublicacion = _documento!['fecha_publicacion'];
    final numeroReferencia = _documento!['numero_referencia'] ?? '';
    final urlOficial = _documento!['url_oficial'] ?? '';
    final archivoAdjunto = _documento!['archivo_adjunto'] ?? '';
    final verificado =
        _documento!['verificado'] == 1 || _documento!['verificado'] == true;
    final descargas = _documento!['descargas'] ?? 0;
    final visitas = _documento!['visitas'] ?? 0;
    final comentarios = _documento!['comentarios'] as List<dynamic>? ?? [];
    final relacionados = _documento!['relacionados'] as List<dynamic>? ?? [];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Documento'),
        backgroundColor: Colors.indigo.shade700,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: Icon(_esFavorito ? Icons.star : Icons.star_border),
            onPressed: _toggleFavorito,
          ),
          IconButton(
            icon: const Icon(Icons.share),
            onPressed: () {
              // Implementar compartir
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _cargarDocumento,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Header con tipo y ámbito
            Row(
              children: [
                _TipoBadge(tipo: tipo),
                const SizedBox(width: 8),
                _AmbitoBadge(ambito: ambito),
                if (verificado) ...[
                  const SizedBox(width: 8),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.green.shade100,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.verified,
                            size: 14, color: Colors.green.shade700),
                        const SizedBox(width: 4),
                        Text(
                          'Verificado',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.green.shade700,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 16),

            // Título
            Text(
              titulo,
              style: const TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),

            // Metadatos
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    if (categoria.isNotEmpty)
                      _MetadataRow(
                        icon: Icons.folder,
                        label: 'Categoría',
                        value: categoria,
                      ),
                    if (fechaPublicacion != null)
                      _MetadataRow(
                        icon: Icons.calendar_today,
                        label: 'Fecha publicación',
                        value: _formatearFecha(fechaPublicacion),
                      ),
                    if (numeroReferencia.isNotEmpty)
                      _MetadataRow(
                        icon: Icons.tag,
                        label: 'Referencia',
                        value: numeroReferencia,
                      ),
                    _MetadataRow(
                      icon: Icons.download,
                      label: 'Descargas',
                      value: '$descargas',
                    ),
                    _MetadataRow(
                      icon: Icons.visibility,
                      label: 'Visitas',
                      value: '$visitas',
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Descripción
            if (descripcion.isNotEmpty) ...[
              const Text(
                'Descripción',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 8),
              Text(descripcion),
              const SizedBox(height: 16),
            ],

            // Contenido
            if (contenido.isNotEmpty) ...[
              const Text(
                'Contenido',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: Text(
                  contenido,
                  style: const TextStyle(fontSize: 14, height: 1.6),
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Archivo adjunto
            if (archivoAdjunto.isNotEmpty) ...[
              Card(
                color: Colors.indigo.shade50,
                child: ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.indigo.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child:
                        Icon(Icons.file_present, color: Colors.indigo.shade700),
                  ),
                  title: const Text('Documento adjunto'),
                  subtitle: Text(
                    archivoAdjunto.split('/').last,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: IconButton(
                    icon: const Icon(Icons.download),
                    onPressed: () {
                      _registrarDescarga();
                      // Aquí se abriría el archivo
                    },
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],

            // URL oficial
            if (urlOficial.isNotEmpty) ...[
              Card(
                color: Colors.blue.shade50,
                child: ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(Icons.link, color: Colors.blue.shade700),
                  ),
                  title: const Text('Enlace oficial'),
                  subtitle: Text(
                    urlOficial,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: const Icon(Icons.open_in_new),
                  onTap: () {
                    // Abrir URL
                  },
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Comentarios
            const Text(
              'Comentarios',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            const SizedBox(height: 8),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _comentarioController,
                        decoration: const InputDecoration(
                          hintText: 'Añadir comentario...',
                          border: InputBorder.none,
                          isDense: true,
                        ),
                        maxLines: 2,
                        minLines: 1,
                      ),
                    ),
                    IconButton(
                      icon: Icon(Icons.send, color: Colors.indigo.shade700),
                      onPressed: _enviarComentario,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 8),
            if (comentarios.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: Text(
                  'No hay comentarios aún',
                  style: TextStyle(color: Colors.grey.shade500),
                  textAlign: TextAlign.center,
                ),
              )
            else
              ...comentarios.map((comentario) => _ComentarioTile(
                    comentario: comentario as Map<String, dynamic>,
                  )),
            const SizedBox(height: 24),

            // Documentos relacionados
            if (relacionados.isNotEmpty) ...[
              const Text(
                'Documentos relacionados',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 8),
              ...relacionados.map((doc) => ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: CircleAvatar(
                      backgroundColor: Colors.indigo.shade100,
                      child:
                          Icon(Icons.article, color: Colors.indigo.shade700),
                    ),
                    title: Text(
                      doc['titulo'] ?? 'Sin título',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    subtitle: Text(doc['tipo'] ?? ''),
                    trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                    onTap: () {
                      Navigator.of(context).pushReplacement(
                        MaterialPageRoute(
                          builder: (_) =>
                              _DocumentoDetalleScreen(documentoId: doc['id']),
                        ),
                      );
                    },
                  )),
            ],
          ],
        ),
      ),
    );
  }

  String _formatearFecha(String? fecha) {
    if (fecha == null) return '';
    try {
      final partes = fecha.split('-');
      if (partes.length >= 3) {
        return '${partes[2]}/${partes[1]}/${partes[0]}';
      }
    } catch (_) {}
    return fecha;
  }
}

// =============================================================================
// FILA DE METADATOS
// =============================================================================

class _MetadataRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _MetadataRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Icon(icon, size: 18, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Text(
            label,
            style: TextStyle(color: Colors.grey.shade600),
          ),
          const Spacer(),
          Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}

// =============================================================================
// TILE DE COMENTARIO
// =============================================================================

class _ComentarioTile extends StatelessWidget {
  final Map<String, dynamic> comentario;

  const _ComentarioTile({required this.comentario});

  @override
  Widget build(BuildContext context) {
    final texto = comentario['comentario'] ?? '';
    final autor = comentario['autor_nombre'] ?? 'Anónimo';
    final tipo = comentario['tipo'] ?? 'nota';
    final fecha = comentario['created_at'];

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 14,
                  backgroundColor: _getColorTipo(tipo),
                  child: Icon(_getIconoTipo(tipo), size: 14, color: Colors.white),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    autor,
                    style: const TextStyle(fontWeight: FontWeight.w500),
                  ),
                ),
                if (fecha != null)
                  Text(
                    _formatearFechaHora(fecha),
                    style: TextStyle(
                      fontSize: 11,
                      color: Colors.grey.shade500,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(texto),
          ],
        ),
      ),
    );
  }

  IconData _getIconoTipo(String tipo) {
    switch (tipo) {
      case 'pregunta':
        return Icons.help;
      case 'aclaracion':
        return Icons.lightbulb;
      case 'correccion':
        return Icons.edit;
      default:
        return Icons.comment;
    }
  }

  Color _getColorTipo(String tipo) {
    switch (tipo) {
      case 'pregunta':
        return Colors.blue;
      case 'aclaracion':
        return Colors.amber;
      case 'correccion':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatearFechaHora(String fecha) {
    try {
      final dt = DateTime.parse(fecha);
      return '${dt.day}/${dt.month}/${dt.year} ${dt.hour}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return fecha;
    }
  }
}

// =============================================================================
// PANTALLA SUBIR DOCUMENTO
// =============================================================================

class _SubirDocumentoScreen extends ConsumerStatefulWidget {
  final List<dynamic> categorias;

  const _SubirDocumentoScreen({required this.categorias});

  @override
  ConsumerState<_SubirDocumentoScreen> createState() =>
      _SubirDocumentoScreenState();
}

class _SubirDocumentoScreenState extends ConsumerState<_SubirDocumentoScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tituloController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _contenidoController = TextEditingController();
  final _referenciaController = TextEditingController();
  final _urlController = TextEditingController();
  final _palabrasClaveController = TextEditingController();

  String _tipo = 'otro';
  String _categoria = '';
  String _ambito = 'estatal';
  bool _enviando = false;

  final List<Map<String, String>> _tipos = [
    {'id': 'ley', 'label': 'Ley'},
    {'id': 'decreto', 'label': 'Decreto'},
    {'id': 'ordenanza', 'label': 'Ordenanza'},
    {'id': 'sentencia', 'label': 'Sentencia'},
    {'id': 'modelo_denuncia', 'label': 'Modelo de denuncia'},
    {'id': 'modelo_recurso', 'label': 'Modelo de recurso'},
    {'id': 'guia', 'label': 'Guía'},
    {'id': 'informe', 'label': 'Informe'},
    {'id': 'otro', 'label': 'Otro'},
  ];

  final List<Map<String, String>> _ambitos = [
    {'id': 'europeo', 'label': 'Europeo'},
    {'id': 'estatal', 'label': 'Estatal'},
    {'id': 'autonomico', 'label': 'Autonómico'},
    {'id': 'provincial', 'label': 'Provincial'},
    {'id': 'municipal', 'label': 'Municipal'},
  ];

  @override
  void dispose() {
    _tituloController.dispose();
    _descripcionController.dispose();
    _contenidoController.dispose();
    _referenciaController.dispose();
    _urlController.dispose();
    _palabrasClaveController.dispose();
    super.dispose();
  }

  Future<void> _enviar() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _enviando = true);

    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.post('/documentacion-legal', data: {
        'titulo': _tituloController.text.trim(),
        'descripcion': _descripcionController.text.trim(),
        'contenido': _contenidoController.text.trim(),
        'tipo': _tipo,
        'categoria': _categoria,
        'ambito': _ambito,
        'numero_referencia': _referenciaController.text.trim(),
        'url_oficial': _urlController.text.trim(),
        'palabras_clave': _palabrasClaveController.text.trim(),
      });

      if (respuesta.success && mounted) {
        Navigator.of(context).pop(true);
      } else if (mounted) {
        FlavorSnackbar.show(
          context,
          respuesta.errorMessage ?? 'Error al subir documento',
          type: SnackbarType.error,
        );
      }
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Subir Documento'),
        backgroundColor: Colors.indigo.shade700,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Info
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.amber.shade50,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.amber.shade200),
              ),
              child: Row(
                children: [
                  Icon(Icons.info, color: Colors.amber.shade700),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Los documentos subidos serán revisados antes de publicarse.',
                      style: TextStyle(color: Colors.amber.shade900),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Título
            TextFormField(
              controller: _tituloController,
              decoration: const InputDecoration(
                labelText: 'Título *',
                hintText: 'Ej: Ley 21/2013 de evaluación ambiental',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.title),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El título es obligatorio';
                }
                if (value.trim().length < 10) {
                  return 'El título debe tener al menos 10 caracteres';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Tipo y Ámbito
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _tipo,
                    decoration: const InputDecoration(
                      labelText: 'Tipo',
                      border: OutlineInputBorder(),
                    ),
                    items: _tipos
                        .map((t) => DropdownMenuItem(
                              value: t['id'],
                              child: Text(t['label']!),
                            ))
                        .toList(),
                    onChanged: (value) => setState(() => _tipo = value ?? 'otro'),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _ambito,
                    decoration: const InputDecoration(
                      labelText: 'Ámbito',
                      border: OutlineInputBorder(),
                    ),
                    items: _ambitos
                        .map((a) => DropdownMenuItem(
                              value: a['id'],
                              child: Text(a['label']!),
                            ))
                        .toList(),
                    onChanged: (value) =>
                        setState(() => _ambito = value ?? 'estatal'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Categoría
            DropdownButtonFormField<String>(
              value: _categoria.isEmpty ? null : _categoria,
              decoration: const InputDecoration(
                labelText: 'Categoría',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.category),
              ),
              hint: const Text('Seleccionar categoría'),
              items: widget.categorias
                  .map((c) => DropdownMenuItem(
                        value: c['slug'] as String?,
                        child: Text(c['nombre'] ?? ''),
                      ))
                  .toList(),
              onChanged: (value) => setState(() => _categoria = value ?? ''),
            ),
            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción',
                hintText: 'Breve descripción del documento',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.description),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 16),

            // Contenido
            TextFormField(
              controller: _contenidoController,
              decoration: const InputDecoration(
                labelText: 'Contenido / Texto completo',
                hintText: 'Pegar aquí el texto del documento',
                border: OutlineInputBorder(),
              ),
              maxLines: 8,
            ),
            const SizedBox(height: 16),

            // Referencia
            TextFormField(
              controller: _referenciaController,
              decoration: const InputDecoration(
                labelText: 'Número de referencia',
                hintText: 'Ej: BOE-A-2013-12913',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.tag),
              ),
            ),
            const SizedBox(height: 16),

            // URL oficial
            TextFormField(
              controller: _urlController,
              decoration: const InputDecoration(
                labelText: 'URL oficial',
                hintText: 'https://...',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.link),
              ),
              keyboardType: TextInputType.url,
            ),
            const SizedBox(height: 16),

            // Palabras clave
            TextFormField(
              controller: _palabrasClaveController,
              decoration: const InputDecoration(
                labelText: 'Palabras clave',
                hintText: 'Separadas por comas',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.label),
              ),
            ),
            const SizedBox(height: 24),

            // Botón enviar
            ElevatedButton.icon(
              onPressed: _enviando ? null : _enviar,
              icon: _enviando
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.upload),
              label: Text(_enviando ? 'Enviando...' : 'Subir documento'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.indigo.shade700,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// =============================================================================
// DELEGADO DE BÚSQUEDA
// =============================================================================

class _DocumentoBusquedaDelegate extends SearchDelegate<String> {
  final Function(String) onBuscar;

  _DocumentoBusquedaDelegate({required this.onBuscar});

  @override
  String get searchFieldLabel => 'Buscar documentos...';

  @override
  List<Widget> buildActions(BuildContext context) {
    return [
      IconButton(
        icon: const Icon(Icons.clear),
        onPressed: () {
          query = '';
        },
      ),
    ];
  }

  @override
  Widget buildLeading(BuildContext context) {
    return IconButton(
      icon: const Icon(Icons.arrow_back),
      onPressed: () {
        close(context, '');
      },
    );
  }

  @override
  Widget buildResults(BuildContext context) {
    if (query.isNotEmpty) {
      onBuscar(query);
      close(context, query);
    }
    return const SizedBox.shrink();
  }

  @override
  Widget buildSuggestions(BuildContext context) {
    final sugerencias = [
      'Ley de medio ambiente',
      'Ordenanza municipal',
      'Sentencia tribunal',
      'Modelo denuncia urbanismo',
      'Recurso administrativo',
    ];

    return ListView.builder(
      itemCount: sugerencias.length,
      itemBuilder: (context, index) {
        return ListTile(
          leading: const Icon(Icons.search),
          title: Text(sugerencias[index]),
          onTap: () {
            query = sugerencias[index];
            showResults(context);
          },
        );
      },
    );
  }
}
