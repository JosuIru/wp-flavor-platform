part of 'mapa_actores_screen.dart';

// =============================================================================
// TARJETA DE ACTOR
// =============================================================================

class _ActorCard extends StatelessWidget {
  final Map<String, dynamic> actor;
  final VoidCallback onTap;

  const _ActorCard({
    required this.actor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final nombre = actor['nombre'] ?? 'Sin nombre';
    final tipo = actor['tipo'] ?? 'otro';
    final posicion = actor['posicion_general'] ?? 'desconocido';
    final nivelInfluencia = actor['nivel_influencia'] ?? 'medio';
    final municipio = actor['municipio'] ?? '';
    final ambito = actor['ambito'] ?? 'local';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Icono tipo
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: _getColorTipo(tipo).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  _getIconoTipo(tipo),
                  color: _getColorTipo(tipo),
                  size: 28,
                ),
              ),
              const SizedBox(width: 16),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      nombre,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    // Badges
                    Wrap(
                      spacing: 6,
                      runSpacing: 4,
                      children: [
                        _PosicionBadge(posicion: posicion),
                        _InfluenciaBadge(nivel: nivelInfluencia),
                      ],
                    ),
                    const SizedBox(height: 6),
                    // Info adicional
                    Row(
                      children: [
                        Icon(Icons.category,
                            size: 12, color: Colors.grey.shade500),
                        const SizedBox(width: 4),
                        Text(
                          _formatearTipo(tipo),
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade600,
                          ),
                        ),
                        if (municipio.isNotEmpty) ...[
                          const SizedBox(width: 12),
                          Icon(Icons.location_on,
                              size: 12, color: Colors.grey.shade500),
                          const SizedBox(width: 4),
                          Flexible(
                            child: Text(
                              municipio,
                              style: TextStyle(
                                fontSize: 11,
                                color: Colors.grey.shade600,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              Icon(Icons.arrow_forward_ios,
                  size: 14, color: Colors.grey.shade400),
            ],
          ),
        ),
      ),
    );
  }

  String _formatearTipo(String tipo) {
    final map = {
      'administracion_publica': 'Adm. Pública',
      'empresa': 'Empresa',
      'institucion': 'Institución',
      'medio_comunicacion': 'Medio',
      'partido_politico': 'Partido',
      'sindicato': 'Sindicato',
      'ong': 'ONG',
      'colectivo': 'Colectivo',
      'persona': 'Persona',
      'otro': 'Otro',
    };
    return map[tipo] ?? tipo;
  }

  Color _getColorTipo(String tipo) {
    switch (tipo) {
      case 'administracion_publica':
        return Colors.blue.shade700;
      case 'empresa':
        return Colors.green.shade700;
      case 'institucion':
        return Colors.purple.shade700;
      case 'medio_comunicacion':
        return Colors.orange.shade700;
      case 'partido_politico':
        return Colors.red.shade700;
      case 'sindicato':
        return Colors.amber.shade700;
      case 'ong':
        return Colors.teal.shade700;
      case 'colectivo':
        return Colors.indigo.shade700;
      case 'persona':
        return Colors.brown.shade700;
      default:
        return Colors.grey.shade700;
    }
  }

  IconData _getIconoTipo(String tipo) {
    switch (tipo) {
      case 'administracion_publica':
        return Icons.account_balance;
      case 'empresa':
        return Icons.business;
      case 'institucion':
        return Icons.school;
      case 'medio_comunicacion':
        return Icons.newspaper;
      case 'partido_politico':
        return Icons.how_to_vote;
      case 'sindicato':
        return Icons.groups;
      case 'ong':
        return Icons.volunteer_activism;
      case 'colectivo':
        return Icons.diversity_3;
      case 'persona':
        return Icons.person;
      default:
        return Icons.category;
    }
  }
}

// =============================================================================
// BADGE DE POSICIÓN
// =============================================================================

class _PosicionBadge extends StatelessWidget {
  final String posicion;

  const _PosicionBadge({required this.posicion});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: _getColor().withOpacity(0.1),
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: _getColor().withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(_getIcono(), size: 12, color: _getColor()),
          const SizedBox(width: 4),
          Text(
            _getLabel(),
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: _getColor(),
            ),
          ),
        ],
      ),
    );
  }

  String _getLabel() {
    switch (posicion) {
      case 'aliado':
        return 'Aliado';
      case 'neutro':
        return 'Neutro';
      case 'opositor':
        return 'Opositor';
      default:
        return 'Desconocido';
    }
  }

  Color _getColor() {
    switch (posicion) {
      case 'aliado':
        return Colors.blue;
      case 'neutro':
        return Colors.grey;
      case 'opositor':
        return Colors.red;
      default:
        return Colors.grey.shade500;
    }
  }

  IconData _getIcono() {
    switch (posicion) {
      case 'aliado':
        return Icons.handshake;
      case 'neutro':
        return Icons.balance;
      case 'opositor':
        return Icons.gpp_bad;
      default:
        return Icons.help_outline;
    }
  }
}

// =============================================================================
// BADGE DE INFLUENCIA
// =============================================================================

class _InfluenciaBadge extends StatelessWidget {
  final String nivel;

  const _InfluenciaBadge({required this.nivel});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: Colors.amber.shade50,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          ...List.generate(
            _getNivelNumero(),
            (i) => Icon(Icons.star, size: 10, color: Colors.amber.shade700),
          ),
          ...List.generate(
            4 - _getNivelNumero(),
            (i) => Icon(Icons.star_border, size: 10, color: Colors.grey.shade400),
          ),
        ],
      ),
    );
  }

  int _getNivelNumero() {
    switch (nivel) {
      case 'muy_alto':
        return 4;
      case 'alto':
        return 3;
      case 'medio':
        return 2;
      case 'bajo':
        return 1;
      default:
        return 2;
    }
  }
}

// =============================================================================
// TARJETA DE TIPO
// =============================================================================

class _TipoCard extends StatelessWidget {
  final String tipoId;
  final String nombre;
  final Color color;
  final IconData icono;
  final VoidCallback onTap;

  const _TipoCard({
    required this.tipoId,
    required this.nombre,
    required this.color,
    required this.icono,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
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
              Icon(icono, color: color, size: 32),
              const SizedBox(height: 8),
              Text(
                nombre,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 13,
                  color: color,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// =============================================================================
// PANTALLA DE DETALLE
// =============================================================================

class _ActorDetalleScreen extends ConsumerStatefulWidget {
  final int actorId;

  const _ActorDetalleScreen({required this.actorId});

  @override
  ConsumerState<_ActorDetalleScreen> createState() =>
      _ActorDetalleScreenState();
}

class _ActorDetalleScreenState extends ConsumerState<_ActorDetalleScreen> {
  Map<String, dynamic>? _actor;
  List<dynamic> _relaciones = [];
  List<dynamic> _interacciones = [];
  bool _cargando = true;

  @override
  void initState() {
    super.initState();
    _cargarActor();
  }

  Future<void> _cargarActor() async {
    setState(() => _cargando = true);
    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.get('/actores/${widget.actorId}');
      if (respuesta.success && respuesta.data != null) {
        setState(() {
          _actor = respuesta.data!['actor'];
          _relaciones = _actor?['relaciones_salientes'] ?? [];
          _interacciones = _actor?['interacciones'] ?? [];
        });
      }

      // Cargar relaciones adicionales
      final respRelaciones =
          await api.get('/actores/${widget.actorId}/relaciones');
      if (respRelaciones.success && respRelaciones.data != null) {
        setState(() {
          final comoOrigen =
              respRelaciones.data!['como_origen'] as List<dynamic>? ?? [];
          final comoDestino =
              respRelaciones.data!['como_destino'] as List<dynamic>? ?? [];
          _relaciones = [...comoOrigen, ...comoDestino];
        });
      }
    } finally {
      setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_cargando) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Actor'),
          backgroundColor: Colors.teal.shade700,
          foregroundColor: Colors.white,
        ),
        body: const FlavorLoadingState(),
      );
    }

    if (_actor == null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Actor'),
          backgroundColor: Colors.teal.shade700,
          foregroundColor: Colors.white,
        ),
        body: const FlavorEmptyState(
          icon: Icons.error_outline,
          title: 'Actor no encontrado',
        ),
      );
    }

    final nombre = _actor!['nombre'] ?? 'Sin nombre';
    final descripcion = _actor!['descripcion'] ?? '';
    final tipo = _actor!['tipo'] ?? 'otro';
    final posicion = _actor!['posicion_general'] ?? 'desconocido';
    final nivelInfluencia = _actor!['nivel_influencia'] ?? 'medio';
    final ambito = _actor!['ambito'] ?? 'local';
    final municipio = _actor!['municipio'] ?? '';
    final direccion = _actor!['direccion'] ?? '';
    final telefono = _actor!['telefono'] ?? '';
    final email = _actor!['email'] ?? '';
    final web = _actor!['web'] ?? '';
    final competencias = _actor!['competencias'] ?? '';
    final personas = _actor!['personas'] as List<dynamic>? ?? [];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Actor'),
        backgroundColor: Colors.teal.shade700,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.share),
            onPressed: () {},
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _cargarActor,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Header
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: _getColorTipo(tipo).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Icon(
                    _getIconoTipo(tipo),
                    color: _getColorTipo(tipo),
                    size: 40,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        nombre,
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 4,
                        children: [
                          _PosicionBadge(posicion: posicion),
                          _InfluenciaBadge(nivel: nivelInfluencia),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Info básica
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _InfoRow(
                      icono: Icons.category,
                      label: 'Tipo',
                      valor: _formatearTipo(tipo),
                    ),
                    _InfoRow(
                      icono: Icons.public,
                      label: 'Ámbito',
                      valor: _formatearAmbito(ambito),
                    ),
                    if (municipio.isNotEmpty)
                      _InfoRow(
                        icono: Icons.location_on,
                        label: 'Municipio',
                        valor: municipio,
                      ),
                    if (direccion.isNotEmpty)
                      _InfoRow(
                        icono: Icons.place,
                        label: 'Dirección',
                        valor: direccion,
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Contacto
            if (telefono.isNotEmpty || email.isNotEmpty || web.isNotEmpty)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Contacto',
                        style: TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 12),
                      if (telefono.isNotEmpty)
                        ListTile(
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          leading: Icon(Icons.phone, color: Colors.teal),
                          title: Text(telefono),
                          onTap: () {},
                        ),
                      if (email.isNotEmpty)
                        ListTile(
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          leading: Icon(Icons.email, color: Colors.teal),
                          title: Text(email),
                          onTap: () {},
                        ),
                      if (web.isNotEmpty)
                        ListTile(
                          dense: true,
                          contentPadding: EdgeInsets.zero,
                          leading: Icon(Icons.language, color: Colors.teal),
                          title: Text(web),
                          trailing: const Icon(Icons.open_in_new, size: 16),
                          onTap: () {},
                        ),
                    ],
                  ),
                ),
              ),

            // Descripción
            if (descripcion.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Descripción',
                        style: TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 8),
                      Text(descripcion),
                    ],
                  ),
                ),
              ),
            ],

            // Competencias
            if (competencias.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Competencias',
                        style: TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 8),
                      Text(competencias),
                    ],
                  ),
                ),
              ),
            ],

            // Personas clave
            if (personas.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Personas Clave',
                        style: TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 12),
                      ...personas.map((p) => ListTile(
                            dense: true,
                            contentPadding: EdgeInsets.zero,
                            leading: CircleAvatar(
                              backgroundColor: Colors.teal.shade100,
                              child: Icon(Icons.person, color: Colors.teal),
                            ),
                            title: Text(p['nombre'] ?? ''),
                            subtitle: Text(p['cargo'] ?? ''),
                          )),
                    ],
                  ),
                ),
              ),
            ],

            // Relaciones
            if (_relaciones.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.link, size: 20),
                          const SizedBox(width: 8),
                          const Text(
                            'Relaciones',
                            style: TextStyle(
                                fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                          const Spacer(),
                          Text(
                            '${_relaciones.length}',
                            style: TextStyle(color: Colors.grey.shade600),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      ..._relaciones.take(5).map((r) => ListTile(
                            dense: true,
                            contentPadding: EdgeInsets.zero,
                            leading: Icon(
                              _getIconoRelacion(r['tipo_relacion'] ?? ''),
                              color: Colors.purple,
                            ),
                            title: Text(r['actor_nombre'] ?? 'Actor'),
                            subtitle:
                                Text(_formatearRelacion(r['tipo_relacion'])),
                          )),
                    ],
                  ),
                ),
              ),
            ],

            // Interacciones recientes
            if (_interacciones.isNotEmpty) ...[
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.history, size: 20),
                          const SizedBox(width: 8),
                          const Text(
                            'Interacciones Recientes',
                            style: TextStyle(
                                fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      ..._interacciones.take(5).map((i) => ListTile(
                            dense: true,
                            contentPadding: EdgeInsets.zero,
                            leading: CircleAvatar(
                              backgroundColor:
                                  _getColorResultado(i['resultado'] ?? ''),
                              radius: 16,
                              child: Icon(
                                _getIconoInteraccion(i['tipo'] ?? ''),
                                size: 16,
                                color: Colors.white,
                              ),
                            ),
                            title: Text(i['titulo'] ?? ''),
                            subtitle: Text(i['fecha'] ?? ''),
                          )),
                    ],
                  ),
                ),
              ),
            ],
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  String _formatearTipo(String tipo) {
    final map = {
      'administracion_publica': 'Administración Pública',
      'empresa': 'Empresa',
      'institucion': 'Institución',
      'medio_comunicacion': 'Medio de Comunicación',
      'partido_politico': 'Partido Político',
      'sindicato': 'Sindicato',
      'ong': 'ONG',
      'colectivo': 'Colectivo',
      'persona': 'Persona',
      'otro': 'Otro',
    };
    return map[tipo] ?? tipo;
  }

  String _formatearAmbito(String ambito) {
    final map = {
      'local': 'Local',
      'comarcal': 'Comarcal',
      'provincial': 'Provincial',
      'autonomico': 'Autonómico',
      'estatal': 'Estatal',
      'internacional': 'Internacional',
    };
    return map[ambito] ?? ambito;
  }

  String _formatearRelacion(String? tipo) {
    final map = {
      'pertenece_a': 'Pertenece a',
      'controla': 'Controla',
      'financia': 'Financia',
      'colabora': 'Colabora con',
      'compite': 'Compite con',
      'influye': 'Influye en',
      'depende': 'Depende de',
      'otro': 'Relacionado',
    };
    return map[tipo] ?? tipo ?? 'Relacionado';
  }

  Color _getColorTipo(String tipo) {
    switch (tipo) {
      case 'administracion_publica':
        return Colors.blue.shade700;
      case 'empresa':
        return Colors.green.shade700;
      case 'institucion':
        return Colors.purple.shade700;
      case 'medio_comunicacion':
        return Colors.orange.shade700;
      case 'partido_politico':
        return Colors.red.shade700;
      case 'sindicato':
        return Colors.amber.shade700;
      case 'ong':
        return Colors.teal.shade700;
      case 'colectivo':
        return Colors.indigo.shade700;
      case 'persona':
        return Colors.brown.shade700;
      default:
        return Colors.grey.shade700;
    }
  }

  IconData _getIconoTipo(String tipo) {
    switch (tipo) {
      case 'administracion_publica':
        return Icons.account_balance;
      case 'empresa':
        return Icons.business;
      case 'institucion':
        return Icons.school;
      case 'medio_comunicacion':
        return Icons.newspaper;
      case 'partido_politico':
        return Icons.how_to_vote;
      case 'sindicato':
        return Icons.groups;
      case 'ong':
        return Icons.volunteer_activism;
      case 'colectivo':
        return Icons.diversity_3;
      case 'persona':
        return Icons.person;
      default:
        return Icons.category;
    }
  }

  IconData _getIconoRelacion(String tipo) {
    switch (tipo) {
      case 'pertenece_a':
        return Icons.subdirectory_arrow_right;
      case 'controla':
        return Icons.admin_panel_settings;
      case 'financia':
        return Icons.attach_money;
      case 'colabora':
        return Icons.handshake;
      case 'compite':
        return Icons.compare_arrows;
      case 'influye':
        return Icons.trending_up;
      case 'depende':
        return Icons.link;
      default:
        return Icons.hub;
    }
  }

  IconData _getIconoInteraccion(String tipo) {
    switch (tipo) {
      case 'reunion':
        return Icons.groups;
      case 'comunicacion':
        return Icons.mail;
      case 'denuncia':
        return Icons.report;
      case 'colaboracion':
        return Icons.handshake;
      case 'conflicto':
        return Icons.gpp_bad;
      case 'declaracion':
        return Icons.campaign;
      default:
        return Icons.event;
    }
  }

  Color _getColorResultado(String resultado) {
    switch (resultado) {
      case 'positivo':
        return Colors.green;
      case 'negativo':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}

// =============================================================================
// FILA DE INFO
// =============================================================================

class _InfoRow extends StatelessWidget {
  final IconData icono;
  final String label;
  final String valor;

  const _InfoRow({
    required this.icono,
    required this.label,
    required this.valor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Icon(icono, size: 18, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Text(
            label,
            style: TextStyle(color: Colors.grey.shade600),
          ),
          const Spacer(),
          Text(
            valor,
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}

// =============================================================================
// PANTALLA CREAR ACTOR
// =============================================================================

class _CrearActorScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic>? tipos;

  const _CrearActorScreen({this.tipos});

  @override
  ConsumerState<_CrearActorScreen> createState() => _CrearActorScreenState();
}

class _CrearActorScreenState extends ConsumerState<_CrearActorScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nombreController = TextEditingController();
  final _descripcionController = TextEditingController();
  final _municipioController = TextEditingController();
  final _telefonoController = TextEditingController();
  final _emailController = TextEditingController();
  final _webController = TextEditingController();
  final _competenciasController = TextEditingController();

  String _tipo = 'otro';
  String _ambito = 'local';
  String _posicion = 'desconocido';
  String _nivelInfluencia = 'medio';
  bool _enviando = false;

  @override
  void dispose() {
    _nombreController.dispose();
    _descripcionController.dispose();
    _municipioController.dispose();
    _telefonoController.dispose();
    _emailController.dispose();
    _webController.dispose();
    _competenciasController.dispose();
    super.dispose();
  }

  Future<void> _enviar() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _enviando = true);

    try {
      final api = ref.read(apiClientProvider);
      final respuesta = await api.post('/actores', data: {
        'nombre': _nombreController.text.trim(),
        'descripcion': _descripcionController.text.trim(),
        'tipo': _tipo,
        'ambito': _ambito,
        'posicion_general': _posicion,
        'nivel_influencia': _nivelInfluencia,
        'municipio': _municipioController.text.trim(),
        'telefono': _telefonoController.text.trim(),
        'email': _emailController.text.trim(),
        'web': _webController.text.trim(),
        'competencias': _competenciasController.text.trim(),
      });

      if (respuesta.success && mounted) {
        Navigator.of(context).pop(true);
      } else if (mounted) {
        FlavorSnackbar.show(
          context,
          respuesta.errorMessage ?? 'Error al crear actor',
          type: SnackbarType.error,
        );
      }
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final tipos = widget.tipos?['tipos'] as Map<String, dynamic>? ?? {};
    final ambitos = widget.tipos?['ambitos'] as Map<String, dynamic>? ?? {};
    final posiciones =
        widget.tipos?['posiciones'] as Map<String, dynamic>? ?? {};
    final nivelesInfluencia =
        widget.tipos?['niveles_influencia'] as Map<String, dynamic>? ?? {};

    return Scaffold(
      appBar: AppBar(
        title: const Text('Nuevo Actor'),
        backgroundColor: Colors.teal.shade700,
        foregroundColor: Colors.white,
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Nombre
            TextFormField(
              controller: _nombreController,
              decoration: const InputDecoration(
                labelText: 'Nombre *',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.badge),
              ),
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'El nombre es obligatorio';
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
                    items: tipos.entries
                        .map((e) => DropdownMenuItem(
                              value: e.key,
                              child: Text(e.value, overflow: TextOverflow.ellipsis),
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
                    items: ambitos.entries
                        .map((e) => DropdownMenuItem(
                              value: e.key,
                              child: Text(e.value),
                            ))
                        .toList(),
                    onChanged: (value) =>
                        setState(() => _ambito = value ?? 'local'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Posición e Influencia
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _posicion,
                    decoration: const InputDecoration(
                      labelText: 'Posición',
                      border: OutlineInputBorder(),
                    ),
                    items: posiciones.entries
                        .map((e) => DropdownMenuItem(
                              value: e.key,
                              child: Text(e.value),
                            ))
                        .toList(),
                    onChanged: (value) =>
                        setState(() => _posicion = value ?? 'desconocido'),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: _nivelInfluencia,
                    decoration: const InputDecoration(
                      labelText: 'Influencia',
                      border: OutlineInputBorder(),
                    ),
                    items: nivelesInfluencia.entries
                        .map((e) => DropdownMenuItem(
                              value: e.key,
                              child: Text(e.value),
                            ))
                        .toList(),
                    onChanged: (value) =>
                        setState(() => _nivelInfluencia = value ?? 'medio'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Municipio
            TextFormField(
              controller: _municipioController,
              decoration: const InputDecoration(
                labelText: 'Municipio',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.location_city),
              ),
            ),
            const SizedBox(height: 16),

            // Descripción
            TextFormField(
              controller: _descripcionController,
              decoration: const InputDecoration(
                labelText: 'Descripción',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 16),

            // Contacto
            TextFormField(
              controller: _telefonoController,
              decoration: const InputDecoration(
                labelText: 'Teléfono',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.phone),
              ),
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _emailController,
              decoration: const InputDecoration(
                labelText: 'Email',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.email),
              ),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _webController,
              decoration: const InputDecoration(
                labelText: 'Web',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.language),
              ),
              keyboardType: TextInputType.url,
            ),
            const SizedBox(height: 16),

            // Competencias
            TextFormField(
              controller: _competenciasController,
              decoration: const InputDecoration(
                labelText: 'Competencias',
                hintText: 'Áreas de responsabilidad o competencia',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
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
                  : const Icon(Icons.save),
              label: Text(_enviando ? 'Guardando...' : 'Guardar actor'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.teal.shade700,
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

class _ActorBusquedaDelegate extends SearchDelegate<String> {
  final Function(String) onBuscar;

  _ActorBusquedaDelegate({required this.onBuscar});

  @override
  String get searchFieldLabel => 'Buscar actores...';

  @override
  List<Widget> buildActions(BuildContext context) {
    return [
      IconButton(
        icon: const Icon(Icons.clear),
        onPressed: () => query = '',
      ),
    ];
  }

  @override
  Widget buildLeading(BuildContext context) {
    return IconButton(
      icon: const Icon(Icons.arrow_back),
      onPressed: () => close(context, ''),
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
      'Ayuntamiento',
      'Diputación',
      'Gobierno',
      'Empresa',
      'ONG',
      'Colectivo',
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
