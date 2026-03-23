import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart' show apiClientProvider;
import '../../core/utils/logger.dart';
import '../../core/utils/haptics.dart';

/// Pantalla de login para usuarios de la app cliente
class LoginScreen extends ConsumerStatefulWidget {
  /// Callback cuando el login es exitoso
  final VoidCallback? onLoginSuccess;

  /// Si es true, se puede cerrar la pantalla sin hacer login
  final bool canDismiss;

  const LoginScreen({
    super.key,
    this.onLoginSuccess,
    this.canDismiss = true,
  });

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _isLoading = false;
  bool _obscurePassword = true;
  String? _errorMessage;

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    Haptics.light();

    final api = ref.read(apiClientProvider);

    try {
      final response = await api.login(
        username: _usernameController.text.trim(),
        password: _passwordController.text,
        appType: 'client',
      );

      if (!mounted) return;

      if (response.success && response.data?['success'] == true) {
        Logger.i('Login exitoso', tag: 'LoginScreen');
        Haptics.success();

        // Notificar éxito
        widget.onLoginSuccess?.call();

        // Cerrar pantalla si se puede
        if (widget.canDismiss && Navigator.of(context).canPop()) {
          Navigator.of(context).pop(true);
        }
      } else {
        setState(() {
          _errorMessage = response.error ?? 'Error al iniciar sesión';
        });
        Haptics.error();
      }
    } catch (e) {
      Logger.e('Error en login', tag: 'LoginScreen', error: e);
      setState(() {
        _errorMessage = 'Error de conexión. Verifica tu conexión a internet.';
      });
      Haptics.error();
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final i18n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: widget.canDismiss
          ? AppBar(
              title: Text(i18n.commonLogin),
              leading: IconButton(
                icon: const Icon(Icons.close),
                onPressed: () => Navigator.of(context).pop(false),
              ),
            )
          : null,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 32),

                // Icono de usuario
                Icon(
                  Icons.account_circle,
                  size: 80,
                  color: theme.colorScheme.primary,
                ),
                const SizedBox(height: 24),

                // Título
                Text(
                  i18n.commonLogin,
                  style: theme.textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),

                // Subtítulo
                Text(
                  i18n.loginSubtitle,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 32),

                // Campo de usuario/email
                TextFormField(
                  controller: _usernameController,
                  decoration: InputDecoration(
                    labelText: i18n.loginUsernameLabel,
                    hintText: i18n.loginUsernameHint,
                    prefixIcon: const Icon(Icons.person),
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.emailAddress,
                  textInputAction: TextInputAction.next,
                  autocorrect: false,
                  enabled: !_isLoading,
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return i18n.loginUsernameRequired;
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Campo de contraseña
                TextFormField(
                  controller: _passwordController,
                  decoration: InputDecoration(
                    labelText: i18n.loginPasswordLabel,
                    prefixIcon: const Icon(Icons.lock),
                    border: const OutlineInputBorder(),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword ? Icons.visibility : Icons.visibility_off,
                      ),
                      onPressed: () {
                        setState(() => _obscurePassword = !_obscurePassword);
                      },
                    ),
                  ),
                  obscureText: _obscurePassword,
                  textInputAction: TextInputAction.done,
                  enabled: !_isLoading,
                  onFieldSubmitted: (_) => _handleLogin(),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return i18n.loginPasswordRequired;
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 24),

                // Mensaje de error
                if (_errorMessage != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: theme.colorScheme.errorContainer,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.error_outline,
                          color: theme.colorScheme.error,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _errorMessage!,
                            style: TextStyle(
                              color: theme.colorScheme.onErrorContainer,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                // Botón de login
                FilledButton.icon(
                  onPressed: _isLoading ? null : _handleLogin,
                  icon: _isLoading
                      ? SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: theme.colorScheme.onPrimary,
                          ),
                        )
                      : const Icon(Icons.login),
                  label: Text(_isLoading ? i18n.commonLoading : i18n.commonLogin),
                  style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                ),
                const SizedBox(height: 16),

                // Enlace de ayuda
                TextButton(
                  onPressed: _isLoading ? null : () {
                    _showHelpDialog(context);
                  },
                  child: Text(i18n.loginNeedHelp),
                ),

                const SizedBox(height: 32),

                // Información adicional
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surfaceContainerHighest,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    children: [
                      Icon(
                        Icons.info_outline,
                        color: theme.colorScheme.primary,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        i18n.loginInfoText,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showHelpDialog(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(i18n.loginHelpTitle),
        content: Text(i18n.loginHelpContent),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text(i18n.commonOk),
          ),
        ],
      ),
    );
  }
}
