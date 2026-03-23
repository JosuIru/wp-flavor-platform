import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:add_2_calendar/add_2_calendar.dart';
import 'package:share_plus/share_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'package:uuid/uuid.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';
import '../../main_client.dart' show clientEmailProvider;

/// Provider para el email guardado del cliente
final savedClientEmailProvider = FutureProvider<String?>((ref) async {
  final prefs = await SharedPreferences.getInstance();
  return prefs.getString('client_email');
});

/// Provider para el estado de verificación
final emailVerificationStatusProvider = FutureProvider<Map<String, dynamic>?>((ref) async {
  final api = ref.read(apiClientProvider);
  final prefs = await SharedPreferences.getInstance();
  final deviceId = await _getDeviceId(prefs);

  try {
    final response = await api.getEmailVerificationStatus(deviceId: deviceId);
    if (response.success && response.data != null) {
      return response.data!;
    }
  } catch (e) {
    // Ignorar errores
  }
  return null;
});

/// Obtener o crear device ID único
Future<String> _getDeviceId(SharedPreferences prefs) async {
  var deviceId = prefs.getString('device_id');
  if (deviceId == null || deviceId.isEmpty) {
    deviceId = const Uuid().v4();
    await prefs.setString('device_id', deviceId);
  }
  return deviceId;
}

/// Provider para mis reservas (cliente) con soporte offline y verificación
final myReservationsProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final api = ref.read(apiClientProvider);
  final prefs = await SharedPreferences.getInstance();
  final deviceId = await _getDeviceId(prefs);

  // Obtener email del cliente
  final email = ref.read(clientEmailProvider) ?? prefs.getString('client_email');

  if (email == null || email.isEmpty) {
    // Si no hay email, intentar cargar desde caché offline
    final cachedData = prefs.getString('my_reservations_cache');
    if (cachedData != null) {
      try {
        final list = json.decode(cachedData) as List;
        final reservations = list.map((r) => Reservation.fromJson(r as Map<String, dynamic>)).toList();
        return {'reservations': reservations, 'requires_verification': false};
      } catch (e) {
        debugPrint('Error parseando reservas cacheadas: $e');
      }
    }
    return {'reservations': <Reservation>[], 'requires_verification': false, 'no_email': true};
  }

  // Intentar obtener del servidor
  try {
    final response = await api.getClientReservations(
      email: email,
      deviceId: deviceId,
      includePast: true,
    );

    if (response.success && response.data != null) {
      // Verificar si requiere verificación
      if (response.data!['requires_verification'] == true) {
        return {
          'reservations': <Reservation>[],
          'requires_verification': true,
          'masked_email': response.data!['masked_email'] ?? email,
        };
      }

      final reservations = response.data!['reservations'] as List? ?? [];
      final result = reservations.map((r) => Reservation.fromJson(r as Map<String, dynamic>)).toList();

      // Guardar en caché para uso offline
      await prefs.setString('my_reservations_cache', json.encode(reservations));
      await prefs.setInt('my_reservations_cache_time', DateTime.now().millisecondsSinceEpoch);

      return {
        'reservations': result,
        'requires_verification': false,
        'verified_email': response.data!['verified_email'],
      };
    }
  } catch (e) {
    debugPrint('Error obteniendo reservas del servidor: $e');
    // Si falla la red, intentar caché offline
    final cachedData = prefs.getString('my_reservations_cache');
    if (cachedData != null) {
      try {
        final list = json.decode(cachedData) as List;
        final reservations = list.map((r) => Reservation.fromJson(r as Map<String, dynamic>)).toList();
        return {'reservations': reservations, 'requires_verification': false, 'offline': true};
      } catch (parseError) {
        debugPrint('Error parseando caché offline de reservas: $parseError');
      }
    }
  }

  return {'reservations': <Reservation>[], 'requires_verification': false};
});

/// Pantalla de mis reservas (cliente) - Billetera de tickets
class MyReservationsScreen extends ConsumerStatefulWidget {
  const MyReservationsScreen({super.key});

  @override
  ConsumerState<MyReservationsScreen> createState() => _MyReservationsScreenState();
}

class _MyReservationsScreenState extends ConsumerState<MyReservationsScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  final _emailController = TextEditingController();
  final _codeController = TextEditingController();
  bool _isOffline = false;
  bool _isSendingCode = false;
  bool _isVerifying = false;
  String? _pendingEmail;
  String? _verificationError;

  @override
  void initState() {
    super.initState();
    _loadSavedEmail();
  }

  Future<void> _loadSavedEmail() async {
    final prefs = await SharedPreferences.getInstance();
    final savedEmail = prefs.getString('client_email');
    if (savedEmail != null && savedEmail.isNotEmpty) {
      _emailController.text = savedEmail;
      ref.read(clientEmailProvider.notifier).state = savedEmail;
    }
  }

  Future<String> _getDeviceIdLocal() async {
    final prefs = await SharedPreferences.getInstance();
    return _getDeviceId(prefs);
  }

  Future<void> _saveEmail(String email) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('client_email', email);
    ref.read(clientEmailProvider.notifier).state = email;
    ref.invalidate(myReservationsProvider);
    ref.invalidate(emailVerificationStatusProvider);
  }

  Future<void> _sendVerificationCode(String email) async {
    setState(() {
      _isSendingCode = true;
      _verificationError = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final deviceId = await _getDeviceIdLocal();

      final response = await api.sendEmailVerificationCode(
        email: email,
        deviceId: deviceId,
      );

      if (response.success && response.data?['success'] == true) {
        _pendingEmail = email;
        if (mounted) {
          Navigator.pop(context); // Cerrar diálogo de email
          _showVerificationCodeDialog();
        }
      } else {
        setState(() {
          _verificationError = response.error ?? 'Error al enviar código';
        });
      }
    } catch (e) {
      setState(() {
        _verificationError = 'Error de conexión';
      });
    } finally {
      setState(() {
        _isSendingCode = false;
      });
    }
  }

  Future<void> _verifyCode(String code) async {
    if (_pendingEmail == null) return;

    setState(() {
      _isVerifying = true;
      _verificationError = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final deviceId = await _getDeviceIdLocal();

      final response = await api.verifyEmailCode(
        email: _pendingEmail!,
        code: code,
        deviceId: deviceId,
      );

      if (response.success && response.data?['success'] == true) {
        await _saveEmail(_pendingEmail!);
        _pendingEmail = null;
        _codeController.clear();
        if (mounted) {
          Navigator.pop(context); // Cerrar diálogo de código
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(i18n.emailVerificadoCorrectamente13db6f),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        setState(() {
          _verificationError = response.data?['message'] ?? response.error ?? 'Código incorrecto';
        });
      }
    } catch (e) {
      setState(() {
        _verificationError = 'Error de conexión';
      });
    } finally {
      setState(() {
        _isVerifying = false;
      });
    }
  }

  void _showEmailDialog({bool isChange = false}) {
    _verificationError = null;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: Text(isChange ? 'Cambiar email' : 'Verificar email'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(i18n.ticketsEmailPrompt),
              const SizedBox(height: 16),
              TextField(
                controller: _emailController,
                decoration: InputDecoration(
                  labelText: i18n.emailCe8ae9,
                  hintText: i18n.tuEmailComE48501,
                  prefixIcon: const Icon(Icons.email),
                  errorText: _verificationError,
                ),
                keyboardType: TextInputType.emailAddress,
                autofocus: true,
                enabled: !_isSendingCode,
              ),
              if (_isSendingCode) ...[
                const SizedBox(height: 16),
                Row(
                  children: [
                    SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                    SizedBox(width: 12),
                    Text(i18n.enviandoCDigo36cd31),
                  ],
                ),
              ],
            ],
          ),
          actions: [
            TextButton(
              onPressed: _isSendingCode ? null : () => Navigator.pop(context),
              child: Text(i18n.commonCancel),
            ),
            FilledButton(
              onPressed: _isSendingCode
                  ? null
                  : () {
                      final email = _emailController.text.trim();
                      if (email.isNotEmpty && email.contains('@')) {
                        _sendVerificationCode(email);
                        setDialogState(() {}); // Refresh dialog state
                      } else {
                        setDialogState(() {
                          _verificationError = 'Email inválido';
                        });
                      }
                    },
              child: Text(i18n.sendCodeLabel),
            ),
          ],
        ),
      ),
    );
  }

  void _showVerificationCodeDialog() {
    _codeController.clear();
    _verificationError = null;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: Text(i18n.verificationCodeLabel),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(i18n.reservationsOtpSent(_pendingEmail ?? '')),
              const SizedBox(height: 16),
              TextField(
                controller: _codeController,
                decoration: InputDecoration(
                  labelText: i18n.cDigoC54e67,
                  hintText: i18n.t000000670b14,
                  prefixIcon: const Icon(Icons.lock),
                  errorText: _verificationError,
                  counterText: '',
                ),
                keyboardType: TextInputType.number,
                textAlign: TextAlign.center,
                maxLength: 6,
                autofocus: true,
                enabled: !_isVerifying,
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 8,
                ),
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                ],
                onChanged: (value) {
                  if (value.length == 6) {
                    _verifyCode(value);
                    setDialogState(() {});
                  }
                },
              ),
              if (_isVerifying) ...[
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                    SizedBox(width: 12),
                    Text(i18n.verificando1361c9),
                  ],
                ),
              ],
              const SizedBox(height: 16),
              Center(
                child: TextButton(
                  onPressed: _isSendingCode
                      ? null
                      : () {
                          _sendVerificationCode(_pendingEmail!);
                          setDialogState(() {});
                        },
                  child: Text(i18n.resendCodeLabel),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: _isVerifying
                  ? null
                  : () {
                      _pendingEmail = null;
                      _codeController.clear();
                      Navigator.pop(context);
                    },
              child: Text(i18n.commonCancel),
            ),
            FilledButton(
              onPressed: _isVerifying
                  ? null
                  : () {
                      if (_codeController.text.length == 6) {
                        _verifyCode(_codeController.text);
                        setDialogState(() {});
                      }
                    },
              child: Text(i18n.verificar8e93d9),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final emailAsync = ref.watch(savedClientEmailProvider);
    final reservationsAsync = ref.watch(myReservationsProvider);
    final clientEmail = ref.watch(clientEmailProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.misTicketsD730aa),
        actions: [
          IconButton(
            onPressed: () => _showEmailDialog(isChange: true),
            icon: const Icon(Icons.person),
            tooltip: i18n.cambiarEmailC3a46a,
          ),
          IconButton(
            onPressed: () {
              ref.invalidate(myReservationsProvider);
              ref.invalidate(emailVerificationStatusProvider);
            },
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: emailAsync.when(
        data: (savedEmail) {
          final email = clientEmail ?? savedEmail;

          // Si no hay email, pedir que lo ingrese
          if (email == null || email.isEmpty) {
            return _buildEmailPrompt();
          }

          return reservationsAsync.when(
            data: (data) {
              // Verificar si requiere verificación
              if (data['requires_verification'] == true) {
                return _buildVerificationRequired(data['masked_email'] ?? email);
              }

              if (data['no_email'] == true) {
                return _buildEmailPrompt();
              }

              final reservations = data['reservations'] as List<Reservation>;
              final isVerified = data['verified_email'] != null;
              final isOfflineData = data['offline'] == true;

              if (reservations.isEmpty) {
                return _buildEmptyState(email, isVerified);
              }

              // Separar en próximas y pasadas
              final now = DateTime.now();
              final upcoming = reservations.where((r) {
                final date = DateTime.tryParse(r.date);
                return date != null && date.isAfter(now.subtract(const Duration(days: 1)));
              }).toList();
              final past = reservations.where((r) {
                final date = DateTime.tryParse(r.date);
                return date != null && date.isBefore(now.subtract(const Duration(days: 1)));
              }).toList();

              return RefreshIndicator(
                onRefresh: () async {
                  ref.invalidate(myReservationsProvider);
                },
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    // Banner de modo offline
                    if (isOfflineData)
                      Container(
                        padding: const EdgeInsets.all(12),
                        margin: const EdgeInsets.only(bottom: 16),
                        decoration: BoxDecoration(
                          color: Colors.orange.shade50,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.orange.shade200),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.wifi_off, color: Colors.orange, size: 20),
                            SizedBox(width: 8),
                            Expanded(
                              child: Text(AppLocalizations.of(context)!.modoOfflineMostrandoTicketsGuardados,
                                style: TextStyle(color: Colors.orange),
                              ),
                            ),
                          ],
                        ),
                      ),

                    // Info del email actual con badge de verificado
                    Container(
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: isVerified
                            ? Colors.green.shade50
                            : Theme.of(context).colorScheme.surfaceContainerHighest,
                        borderRadius: BorderRadius.circular(8),
                        border: isVerified ? Border.all(color: Colors.green.shade200) : null,
                      ),
                      child: Row(
                        children: [
                          Icon(
                            isVerified ? Icons.verified : Icons.email,
                            size: 18,
                            color: isVerified ? Colors.green : Theme.of(context).colorScheme.primary,
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(email, style: const TextStyle(fontSize: 13)),
                                if (isVerified)
                                  Text(AppLocalizations.of(context)!.emailVerificado,
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: Colors.green.shade700,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                              ],
                            ),
                          ),
                          TextButton(
                            onPressed: () => _showEmailDialog(isChange: true),
                            child: Text(i18n.cambiarD1bdc3),
                          ),
                        ],
                      ),
                    ),

                    if (upcoming.isNotEmpty) ...[
                      Text(
                        'Próximos (${upcoming.length})',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      const SizedBox(height: 12),
                      ...upcoming.map((r) => _ReservationCard(
                            reservation: r,
                            isUpcoming: true,
                          )),
                      const SizedBox(height: 24),
                    ],
                    if (past.isNotEmpty) ...[
                      Text(
                        'Pasados (${past.length})',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).colorScheme.outline,
                            ),
                      ),
                      const SizedBox(height: 12),
                      ...past.map((r) => _ReservationCard(
                            reservation: r,
                            isUpcoming: false,
                          )),
                    ],
                  ],
                ),
              );
            },
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) {
              _isOffline = true;
              return _buildErrorState(error.toString());
            },
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (_, __) => _buildEmailPrompt(),
      ),
    );
  }

  Widget _buildVerificationRequired(String maskedEmail) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.orange.shade50,
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.mark_email_unread,
                size: 64,
                color: Colors.orange.shade600,
              ),
            ),
            const SizedBox(height: 24),
            Text(AppLocalizations.of(context)!.verificacionRequerida,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(AppLocalizations.of(context)!.paraVerTusTicketsNecesitasVerificarTuEmail,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Theme.of(context).colorScheme.outline,
                  ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              maskedEmail,
              style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            FilledButton.icon(
              onPressed: () {
                _sendVerificationCode(_emailController.text.isNotEmpty
                    ? _emailController.text
                    : ref.read(clientEmailProvider) ?? '');
              },
              icon: const Icon(Icons.send),
              label: Text(i18n.enviarCDigoDeVerificaciNEd6c4b),
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () => _showEmailDialog(isChange: true),
              icon: const Icon(Icons.edit),
              label: Text(i18n.usarOtroEmail7573e1),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmailPrompt() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.confirmation_number_outlined,
              size: 80,
              color: Theme.of(context).colorScheme.primary.withOpacity(0.5),
            ),
            const SizedBox(height: 24),
            Text(AppLocalizations.of(context)!.tuBilleteraDeTickets,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(AppLocalizations.of(context)!.verificaTuEmailParaAccederATusTicketsDeFormaSegura,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Theme.of(context).colorScheme.outline,
                  ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            FilledButton.icon(
              onPressed: () => _showEmailDialog(),
              icon: const Icon(Icons.email),
              label: Text(i18n.verificarEmailDa92ce),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState(String email, bool isVerified) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.event_available,
              size: 80,
              color: Theme.of(context).colorScheme.primary.withOpacity(0.5),
            ),
            const SizedBox(height: 24),
            Text(i18n.noTienesReservas,
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              'Las reservas asociadas a $email aparecerán aquí',
              style: TextStyle(color: Theme.of(context).colorScheme.outline),
              textAlign: TextAlign.center,
            ),
            if (isVerified) ...[
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.verified, size: 16, color: Colors.green.shade600),
                  const SizedBox(width: 4),
                  Text(i18n.emailVerificado,
                    style: TextStyle(
                      color: Colors.green.shade600,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 24),
            OutlinedButton.icon(
              onPressed: () => _showEmailDialog(isChange: true),
              icon: const Icon(Icons.edit),
              label: Text(i18n.cambiarEmailC3a46a),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorState(String error) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.wifi_off, size: 64, color: Colors.orange),
            const SizedBox(height: 16),
            Text(i18n.sinConexion,
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(i18n.mostrandoTicketsGuardadosEnElDispositivo,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: () => ref.invalidate(myReservationsProvider),
              icon: const Icon(Icons.refresh),
              label: Text(i18n.reintentar179654),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _emailController.dispose();
    _codeController.dispose();
    super.dispose();
  }
}

class _ReservationCard extends StatelessWidget {
  final Reservation reservation;
  final bool isUpcoming;

  const _ReservationCard({
    required this.reservation,
    required this.isUpcoming,
  });

  Color _getStatusColor() {
    switch (reservation.status) {
      case 'pendiente':
        return Colors.orange;
      case 'usado':
        return Colors.green;
      case 'cancelado':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatDate(String date) {
    try {
      final dt = DateTime.parse(date);
      final weekdays = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
      final months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
      return '${weekdays[dt.weekday - 1]}, ${dt.day} ${months[dt.month - 1]}';
    } catch (e) {
      return date;
    }
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () => _showDetails(context),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: _getStatusColor().withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      reservation.statusDisplay,
                      style: TextStyle(
                        color: _getStatusColor(),
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const Spacer(),
                  Text(
                    _formatDate(reservation.date),
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                reservation.ticketName,
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  Icon(
                    Icons.confirmation_number,
                    size: 14,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    reservation.ticketCode,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          fontFamily: 'monospace',
                        ),
                  ),
                ],
              ),
              if (isUpcoming && reservation.isPending) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _showQR(context),
                        icon: const Icon(Icons.qr_code, size: 18),
                        label: Text(i18n.verQr93c2a8),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _addToCalendar(context),
                        icon: const Icon(Icons.calendar_month, size: 18),
                        label: Text(i18n.calendarioB0743a),
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  void _showDetails(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) => _ReservationDetailsSheet(reservation: reservation),
    );
  }

  void _showQR(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => _QRDialog(reservation: reservation),
    );
  }

  void _addToCalendar(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    try {
      final date = DateTime.parse(reservation.date);
      final event = Event(
        title: reservation.ticketName,
        description: 'Código: ${reservation.ticketCode}',
        startDate: date,
        endDate: date.add(const Duration(hours: 2)),
      );
      Add2Calendar.addEvent2Cal(event);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(i18n.errorAlAAdirAlCalendario030367)),
      );
    }
  }
}

class _QRDialog extends StatelessWidget {
  final Reservation reservation;

  const _QRDialog({required this.reservation});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              reservation.ticketName,
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              reservation.ticketCode,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    fontFamily: 'monospace',
                  ),
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
              child: QrImageView(
                data: reservation.ticketCode,
                version: QrVersions.auto,
                size: 200,
              ),
            ),
            const SizedBox(height: 16),
            Text(AppLocalizations.of(context)!.muestraEsteCodigoEnLaEntrada,
              style: Theme.of(context).textTheme.bodySmall,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () {
                      Share.share(
                        'Mi reserva: ${reservation.ticketName}\nCódigo: ${reservation.ticketCode}\nFecha: ${reservation.date}',
                      );
                    },
                    icon: const Icon(Icons.share),
                    label: Text(i18n.compartirFba5ba),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(
                    onPressed: () => Navigator.pop(context),
                    child: Text(i18n.cerrar92eb39),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ReservationDetailsSheet extends StatelessWidget {
  final Reservation reservation;

  const _ReservationDetailsSheet({required this.reservation});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;

    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      minChildSize: 0.4,
      maxChildSize: 0.9,
      expand: false,
      builder: (context, scrollController) {
        return Container(
          padding: const EdgeInsets.all(24),
          child: SingleChildScrollView(
            controller: scrollController,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Handle
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: colorScheme.outline.withOpacity(0.3),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Título y estado
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        reservation.ticketName,
                        style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: _getStatusColor().withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        reservation.statusDisplay,
                        style: TextStyle(
                          color: _getStatusColor(),
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),

                // QR Code
                if (reservation.isPending)
                  Center(
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.1),
                            blurRadius: 10,
                          ),
                        ],
                      ),
                      child: QrImageView(
                        data: reservation.ticketCode,
                        version: QrVersions.auto,
                        size: 150,
                      ),
                    ),
                  ),

                const SizedBox(height: 24),

                // Detalles
                _DetailRow(
                  icon: Icons.confirmation_number,
                  label: 'Código',
                  value: reservation.ticketCode,
                ),
                _DetailRow(
                  icon: Icons.calendar_today,
                  label: 'Fecha',
                  value: reservation.date,
                ),
                if (reservation.checkin != null)
                  _DetailRow(
                    icon: Icons.check_circle,
                    label: 'Check-in',
                    value: reservation.checkin!,
                  ),

                const SizedBox(height: 24),

                // Acciones
                if (reservation.isPending)
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () {
                            Share.share(
                              'Mi reserva: ${reservation.ticketName}\nCódigo: ${reservation.ticketCode}\nFecha: ${reservation.date}',
                            );
                          },
                          icon: const Icon(Icons.share),
                          label: Text(i18n.compartirFba5ba),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: () {
                            try {
                              final date = DateTime.parse(reservation.date);
                              final event = Event(
                                title: reservation.ticketName,
                                description: 'Código: ${reservation.ticketCode}',
                                startDate: date,
                                endDate: date.add(const Duration(hours: 2)),
                              );
                              Add2Calendar.addEvent2Cal(event);
                            } catch (e) {
                              // Error
                            }
                          },
                          icon: const Icon(Icons.calendar_month),
                          label: Text(i18n.aAdirD20f65),
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          ),
        );
      },
    );
  }

  Color _getStatusColor() {
    switch (reservation.status) {
      case 'pendiente':
        return Colors.orange;
      case 'usado':
        return Colors.green;
      case 'cancelado':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Theme.of(context).colorScheme.primary),
          const SizedBox(width: 12),
          Text(
            label,
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface.withOpacity(0.7),
            ),
          ),
          const Spacer(),
          Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }
}
