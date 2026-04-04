import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';
import '../../core/config/server_config.dart';
import '../../core/widgets/flavor_snackbar.dart';
import '../../core/widgets/flavor_state_widgets.dart';

/// Pantalla para añadir una comunidad manualmente introduciendo la URL
class AddCommunityScreen extends ConsumerStatefulWidget {
  const AddCommunityScreen({super.key});

  @override
  ConsumerState<AddCommunityScreen> createState() => _AddCommunityScreenState();
}

class _AddCommunityScreenState extends ConsumerState<AddCommunityScreen> {
  final _formKey = GlobalKey<FormState>();
  final _urlController = TextEditingController();
  final _nombreController = TextEditingController();

  bool _isValidating = false;
  bool _isValid = false;
  String? _error;
  Map<String, dynamic>? _siteInfo;

  @override
  void dispose() {
    _urlController.dispose();
    _nombreController.dispose();
    super.dispose();
  }

  Future<void> _validarUrl() async {
    final url = _urlController.text.trim();
    if (url.isEmpty) return;

    setState(() {
      _isValidating = true;
      _isValid = false;
      _error = null;
      _siteInfo = null;
    });

    try {
      // Crear cliente temporal para validar
      final tempApi = ApiClient(baseUrl: _normalizeUrl(url));

      // Intentar obtener info del sitio
      final response = await tempApi.getSiteInfo();

      if (response.success && response.data != null) {
        final nombre = response.data!['name']?.toString() ??
                       response.data!['site_name']?.toString() ?? '';

        setState(() {
          _isValid = true;
          _isValidating = false;
          _siteInfo = response.data;
          if (nombre.isNotEmpty && _nombreController.text.isEmpty) {
            _nombreController.text = nombre;
          }
        });
      } else {
        setState(() {
          _isValidating = false;
          _error = 'No se pudo conectar con el servidor';
        });
      }
    } catch (e) {
      setState(() {
        _isValidating = false;
        _error = 'Error de conexión: verificar URL';
      });
    }
  }

  String _normalizeUrl(String url) {
    String cleanUrl = url.trim();

    if (!cleanUrl.startsWith('http://') && !cleanUrl.startsWith('https://')) {
      cleanUrl = 'https://$cleanUrl';
    }

    if (cleanUrl.endsWith('/')) {
      cleanUrl = cleanUrl.substring(0, cleanUrl.length - 1);
    }

    // Remover /wp-json si está incluido
    final wpJsonIndex = cleanUrl.indexOf('/wp-json');
    if (wpJsonIndex > 0) {
      cleanUrl = cleanUrl.substring(0, wpJsonIndex);
    }

    return cleanUrl;
  }

  Future<void> _guardarComunidad() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_isValid) {
      await _validarUrl();
      if (!_isValid) return;
    }

    final url = _normalizeUrl(_urlController.text);
    final nombre = _nombreController.text.trim();

    try {
      await ServerConfig.setCurrentBusiness(
        serverUrl: url,
        name: nombre.isNotEmpty ? nombre : 'Comunidad',
        type: 'community',
      );

      if (mounted) {
        FlavorSnackbar.showSuccess(context, 'Comunidad añadida correctamente');
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        FlavorSnackbar.showError(context, 'Error: $e');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Añadir Comunidad'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Icono
              Icon(
                Icons.add_home_work_outlined,
                size: 64,
                color: theme.colorScheme.primary,
              ),
              const SizedBox(height: 24),

              // Descripción
              Text(
                'Introduce la URL de la comunidad a la que quieres unirte',
                style: theme.textTheme.bodyLarge,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),

              // Campo URL
              TextFormField(
                controller: _urlController,
                decoration: InputDecoration(
                  labelText: 'URL de la comunidad',
                  hintText: 'ejemplo: micomunidad.org',
                  prefixIcon: const Icon(Icons.link),
                  border: const OutlineInputBorder(),
                  suffixIcon: _isValidating
                      ? const Padding(
                          padding: EdgeInsets.all(12),
                          child: FlavorInlineSpinner(),
                        )
                      : _isValid
                          ? const Icon(Icons.check_circle, color: Colors.green)
                          : _error != null
                              ? const Icon(Icons.error, color: Colors.red)
                              : null,
                  errorText: _error,
                ),
                keyboardType: TextInputType.url,
                textInputAction: TextInputAction.next,
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Introduce una URL';
                  }
                  return null;
                },
                onChanged: (_) {
                  if (_isValid || _error != null) {
                    setState(() {
                      _isValid = false;
                      _error = null;
                      _siteInfo = null;
                    });
                  }
                },
                onFieldSubmitted: (_) => _validarUrl(),
              ),
              const SizedBox(height: 8),

              // Botón validar
              Align(
                alignment: Alignment.centerRight,
                child: TextButton.icon(
                  onPressed: _isValidating ? null : _validarUrl,
                  icon: const Icon(Icons.verified_outlined, size: 18),
                  label: const Text('Verificar conexión'),
                ),
              ),
              const SizedBox(height: 16),

              // Campo nombre (opcional)
              TextFormField(
                controller: _nombreController,
                decoration: const InputDecoration(
                  labelText: 'Nombre (opcional)',
                  hintText: 'Nombre para identificar la comunidad',
                  prefixIcon: Icon(Icons.badge_outlined),
                  border: OutlineInputBorder(),
                ),
                textInputAction: TextInputAction.done,
              ),
              const SizedBox(height: 24),

              // Info del sitio si está validado
              if (_isValid && _siteInfo != null) ...[
                Card(
                  color: theme.colorScheme.primaryContainer.withOpacity(0.3),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Icon(
                          Icons.check_circle,
                          color: theme.colorScheme.primary,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Conexión verificada',
                                style: TextStyle(fontWeight: FontWeight.w600),
                              ),
                              if (_siteInfo!['description'] != null)
                                Text(
                                  _siteInfo!['description'].toString(),
                                  style: TextStyle(
                                    fontSize: 13,
                                    color: theme.colorScheme.onSurfaceVariant,
                                  ),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 24),
              ],

              // Botón guardar
              FilledButton.icon(
                onPressed: _isValidating ? null : _guardarComunidad,
                icon: const Icon(Icons.add),
                label: const Text('Añadir Comunidad'),
              ),
              const SizedBox(height: 16),

              // Botón explorar
              OutlinedButton.icon(
                onPressed: () {
                  Navigator.pushReplacementNamed(context, '/network/discover');
                },
                icon: const Icon(Icons.explore),
                label: const Text('Explorar comunidades conocidas'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
