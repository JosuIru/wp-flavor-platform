part of 'trabajo_digno_screen.dart';

extension _TrabajoDignoScreenActions on _TrabajoDignoScreenState {
  void _verMiPerfil() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.75,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 32,
                    backgroundColor: Colors.blue.shade100,
                    child: Icon(Icons.person, size: 32, color: Colors.blue.shade600),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _miPerfil?['nombre'] ?? 'Tu perfil',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          _miPerfil?['profesion'] ?? 'Profesion no especificada',
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _buildPerfilRow(
                        Icons.email,
                        'Email',
                        _miPerfil?['email'] ?? 'No especificado',
                      ),
                      const Divider(),
                      _buildPerfilRow(
                        Icons.phone,
                        'Telefono',
                        _miPerfil?['telefono'] ?? 'No especificado',
                      ),
                      const Divider(),
                      _buildPerfilRow(
                        Icons.location_on,
                        'Ubicacion',
                        _miPerfil?['ubicacion'] ?? 'No especificada',
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              const Text(
                'Habilidades',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 12),
              if (_miPerfil?['habilidades'] != null &&
                  (_miPerfil!['habilidades'] as List).isNotEmpty)
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: (_miPerfil!['habilidades'] as List)
                      .map((h) => Chip(label: Text(h.toString())))
                      .toList(),
                )
              else
                Text(
                  'Anade tus habilidades para mejorar tu perfil',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _editarPerfil();
                  },
                  icon: const Icon(Icons.edit),
                  label: const Text('Editar perfil'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPerfilRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(color: Colors.grey.shade600)),
          const Spacer(),
          Flexible(child: Text(value, textAlign: TextAlign.end)),
        ],
      ),
    );
  }

  void _editarPerfil() {
    final nombreController = TextEditingController(text: _miPerfil?['nombre'] ?? '');
    final profesionController = TextEditingController(text: _miPerfil?['profesion'] ?? '');
    final telefonoController = TextEditingController(text: _miPerfil?['telefono'] ?? '');
    final ubicacionController = TextEditingController(text: _miPerfil?['ubicacion'] ?? '');
    final habilidadesController = TextEditingController(
      text: (_miPerfil?['habilidades'] as List?)?.join(', ') ?? '',
    );
    final experienciaController = TextEditingController(text: _miPerfil?['experiencia'] ?? '');

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.edit),
                  const SizedBox(width: 12),
                  const Text(
                    'Editar perfil',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                'Actualiza tu informacion para mejorar tus oportunidades.',
                style: TextStyle(color: Colors.grey.shade600),
              ),
              const SizedBox(height: 20),
              TextFormField(
                controller: nombreController,
                decoration: const InputDecoration(
                  labelText: 'Nombre completo',
                  prefixIcon: Icon(Icons.person),
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: profesionController,
                decoration: const InputDecoration(
                  labelText: 'Profesion',
                  prefixIcon: Icon(Icons.work),
                  border: OutlineInputBorder(),
                  hintText: 'Ej: Desarrollador/a, Disenador/a...',
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: telefonoController,
                decoration: const InputDecoration(
                  labelText: 'Telefono',
                  prefixIcon: Icon(Icons.phone),
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: ubicacionController,
                decoration: const InputDecoration(
                  labelText: 'Ubicacion',
                  prefixIcon: Icon(Icons.location_on),
                  border: OutlineInputBorder(),
                  hintText: 'Ciudad, Provincia',
                ),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: habilidadesController,
                decoration: const InputDecoration(
                  labelText: 'Habilidades',
                  prefixIcon: Icon(Icons.psychology),
                  border: OutlineInputBorder(),
                  hintText: 'Separadas por comas',
                  helperText: 'Ej: Python, React, Gestion de proyectos',
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: experienciaController,
                decoration: const InputDecoration(
                  labelText: 'Experiencia laboral',
                  prefixIcon: Icon(Icons.history),
                  border: OutlineInputBorder(),
                  hintText: 'Describe tu experiencia brevemente...',
                ),
                maxLines: 4,
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () async {
                    Navigator.pop(context);
                    await _guardarPerfil(
                      nombre: nombreController.text,
                      profesion: profesionController.text,
                      telefono: telefonoController.text,
                      ubicacion: ubicacionController.text,
                      habilidades: habilidadesController.text,
                      experiencia: experienciaController.text,
                    );
                  },
                  icon: const Icon(Icons.save),
                  label: const Text('Guardar cambios'),
                ),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _guardarPerfil({
    required String nombre,
    required String profesion,
    required String telefono,
    required String ubicacion,
    required String habilidades,
    required String experiencia,
  }) async {
    final api = ref.read(apiClientProvider);

    try {
      final habilidadesList = habilidades
          .split(',')
          .map((h) => h.trim())
          .where((h) => h.isNotEmpty)
          .toList();

      final response = await api.post('/trabajo-digno/perfil', data: {
        'nombre': nombre,
        'profesion': profesion,
        'telefono': telefono,
        'ubicacion': ubicacion,
        'habilidades': habilidadesList,
        'experiencia': experiencia,
      });

      if (response.success) {
        if (mounted) {
          FlavorSnackbar.showSuccess(context, 'Perfil actualizado correctamente');
          _loadData();
        }
      } else {
        throw Exception(response.error ?? 'Error al guardar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  void _mostrarFiltros() {
    String ubicacionFiltro = '';
    bool soloTeletrabajo = false;
    bool soloConciliacion = false;
    bool soloSalarioJusto = false;
    String tipoContrato = 'todos';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Icon(Icons.filter_list),
                  const SizedBox(width: 12),
                  const Text(
                    'Filtrar ofertas',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              TextFormField(
                initialValue: ubicacionFiltro,
                decoration: const InputDecoration(
                  labelText: 'Ubicacion',
                  prefixIcon: Icon(Icons.location_on),
                  border: OutlineInputBorder(),
                  hintText: 'Ej: Madrid, Barcelona...',
                ),
                onChanged: (value) => ubicacionFiltro = value,
              ),
              const SizedBox(height: 16),
              const Text('Tipo de contrato', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: tipoContrato,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.work),
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(value: 'todos', child: Text('Todos')),
                  DropdownMenuItem(value: 'indefinido', child: Text('Indefinido')),
                  DropdownMenuItem(value: 'temporal', child: Text('Temporal')),
                  DropdownMenuItem(value: 'practicas', child: Text('Practicas')),
                  DropdownMenuItem(value: 'autonomo', child: Text('Autonomo')),
                ],
                onChanged: (value) {
                  setModalState(() => tipoContrato = value ?? 'todos');
                },
              ),
              const SizedBox(height: 16),
              const Text('Caracteristicas', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              CheckboxListTile(
                value: soloTeletrabajo,
                onChanged: (value) => setModalState(() => soloTeletrabajo = value ?? false),
                title: const Text('Teletrabajo'),
                secondary: const Icon(Icons.home_work, color: Colors.purple),
                contentPadding: EdgeInsets.zero,
              ),
              CheckboxListTile(
                value: soloConciliacion,
                onChanged: (value) => setModalState(() => soloConciliacion = value ?? false),
                title: const Text('Conciliacion'),
                secondary: const Icon(Icons.family_restroom, color: Colors.teal),
                contentPadding: EdgeInsets.zero,
              ),
              CheckboxListTile(
                value: soloSalarioJusto,
                onChanged: (value) => setModalState(() => soloSalarioJusto = value ?? false),
                title: const Text('Salario justo'),
                secondary: const Icon(Icons.euro, color: Colors.green),
                contentPadding: EdgeInsets.zero,
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () {
                        Navigator.pop(context);
                        FlavorSnackbar.showInfo(context, 'Filtros limpiados');
                        _loadData();
                      },
                      child: const Text('Limpiar'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: () async {
                        Navigator.pop(context);
                        await _aplicarFiltros(
                          ubicacion: ubicacionFiltro,
                          tipoContrato: tipoContrato,
                          teletrabajo: soloTeletrabajo,
                          conciliacion: soloConciliacion,
                          salarioJusto: soloSalarioJusto,
                        );
                      },
                      icon: const Icon(Icons.check),
                      label: const Text('Aplicar'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _aplicarFiltros({
    required String ubicacion,
    required String tipoContrato,
    required bool teletrabajo,
    required bool conciliacion,
    required bool salarioJusto,
  }) async {
    // ignore: invalid_use_of_protected_member
    setState(() => _isLoading = true);
    final api = ref.read(apiClientProvider);

    try {
      final response = await api.get('/trabajo-digno/ofertas', queryParameters: {
        if (ubicacion.isNotEmpty) 'ubicacion': ubicacion,
        if (tipoContrato != 'todos') 'tipo_contrato': tipoContrato,
        if (teletrabajo) 'teletrabajo': '1',
        if (conciliacion) 'conciliacion': '1',
        if (salarioJusto) 'salario_justo': '1',
      });

      if (response.success && response.data != null) {
        // ignore: invalid_use_of_protected_member
        setState(() {
          _ofertas = (response.data!['ofertas'] as List<dynamic>? ?? [])
              .whereType<Map<String, dynamic>>()
              .toList();
        });

        if (mounted) {
          FlavorSnackbar.showInfo(context, '${_ofertas.length} ofertas encontradas');
        }
      }
    } finally {
      // ignore: invalid_use_of_protected_member
      setState(() => _isLoading = false);
    }
  }

  void _verDetalle(Map<String, dynamic> oferta) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.8,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: Colors.blue.shade100,
                    child: Icon(Icons.business, color: Colors.blue.shade600),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          oferta['titulo'] ?? '',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          oferta['empresa'] ?? '',
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              // Badges
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  if (oferta['teletrabajo'] == true)
                    _buildBadge('Remoto', Colors.purple),
                  if (oferta['conciliacion'] == true)
                    _buildBadge('Conciliacion', Colors.teal),
                  if (oferta['salario_justo'] == true)
                    _buildBadge('Salario justo', Colors.green),
                ],
              ),
              const SizedBox(height: 20),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _buildDetalleRow(Icons.location_on, 'Ubicacion', oferta['ubicacion'] ?? ''),
                      const Divider(),
                      _buildDetalleRow(Icons.euro, 'Salario', oferta['salario'] ?? ''),
                      const Divider(),
                      _buildDetalleRow(Icons.work, 'Tipo', oferta['tipo_contrato'] ?? 'No especificado'),
                      const Divider(),
                      _buildDetalleRow(Icons.schedule, 'Jornada', oferta['jornada'] ?? 'No especificada'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              if ((oferta['descripcion'] ?? '').isNotEmpty) ...[
                const Text(
                  'Descripcion',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(oferta['descripcion']),
                const SizedBox(height: 20),
              ],
              if ((oferta['requisitos'] ?? '').isNotEmpty) ...[
                const Text(
                  'Requisitos',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(oferta['requisitos']),
                const SizedBox(height: 20),
              ],
              if ((oferta['beneficios'] ?? '').isNotEmpty) ...[
                const Text(
                  'Beneficios',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                const SizedBox(height: 8),
                Text(oferta['beneficios']),
                const SizedBox(height: 20),
              ],
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: () {
                    Navigator.pop(context);
                    _aplicar(oferta);
                  },
                  icon: const Icon(Icons.send),
                  label: const Text('Aplicar a esta oferta'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDetalleRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey.shade600),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(color: Colors.grey.shade600)),
          const Spacer(),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  void _aplicar(Map<String, dynamic> oferta) async {
    final cartaPresentacionController = TextEditingController();

    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Aplicar a ${oferta['titulo']}'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Tu perfil sera enviado a ${oferta['empresa']}.',
              style: TextStyle(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: cartaPresentacionController,
              decoration: const InputDecoration(
                labelText: 'Carta de presentacion (opcional)',
                border: OutlineInputBorder(),
                hintText: 'Presentate brevemente...',
              ),
              maxLines: 3,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Enviar aplicacion'),
          ),
        ],
      ),
    );

    if (confirmar != true) return;

    final api = ref.read(apiClientProvider);

    try {
      final response = await api.post('/trabajo-digno/aplicar', data: {
        'oferta_id': oferta['id'],
        'carta_presentacion': cartaPresentacionController.text,
      });

      if (response.success) {
        if (mounted) {
          FlavorSnackbar.showSuccess(context, 'Aplicacion enviada a ${oferta['empresa']}');
        }
      } else {
        throw Exception(response.error ?? 'Error al aplicar');
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }
}
