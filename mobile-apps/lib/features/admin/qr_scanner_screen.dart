import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:vibration/vibration.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';
import '../../core/widgets/common_widgets.dart';

/// Pantalla de escáner QR para check-in de reservas
class QRScannerScreen extends ConsumerStatefulWidget {
  const QRScannerScreen({super.key});

  @override
  ConsumerState<QRScannerScreen> createState() => _QRScannerScreenState();
}

class _QRScannerScreenState extends ConsumerState<QRScannerScreen> {
  AppLocalizations get i18n => AppLocalizations.of(context)!;
  late MobileScannerController _scannerController;
  bool _isProcessing = false;
  String? _lastScannedCode;
  ScanResult? _lastResult;
  bool _torchEnabled = false;
  bool _frontCamera = false;

  @override
  void initState() {
    super.initState();
    _scannerController = MobileScannerController(
      detectionSpeed: DetectionSpeed.normal,
      facing: CameraFacing.back,
      torchEnabled: false,
    );
  }

  @override
  void dispose() {
    _scannerController.dispose();
    super.dispose();
  }

  Future<void> _onBarcodeDetected(BarcodeCapture capture) async {
    if (_isProcessing) return;

    final barcode = capture.barcodes.firstOrNull;
    if (barcode == null || barcode.rawValue == null) return;

    final code = barcode.rawValue!;

    // Evitar procesar el mismo codigo repetidamente
    if (code == _lastScannedCode) return;

    setState(() {
      _isProcessing = true;
      _lastScannedCode = code;
    });

    // Feedback haptico
    await _vibrate();

    // Procesar el codigo QR
    await _processQRCode(code);
  }

  Future<void> _vibrate() async {
    try {
      final hasVibrator = await Vibration.hasVibrator() ?? false;
      if (hasVibrator) {
        await Vibration.vibrate(duration: 100);
      }
    } catch (e) {
      // Ignorar errores de vibracion
    }
  }

  Future<void> _processQRCode(String code) async {
    final api = ref.read(apiClientProvider);

    // Intentar buscar la reserva por codigo de ticket
    final response = await api.findReservationByCode(code);

    if (!mounted) return;

    if (response.success && response.data != null) {
      final reservation = response.data!['reservation'];
      final reservationId = reservation['id'] as int;
      final status = reservation['status'] as String;

      if (status == 'usado') {
        setState(() {
          _lastResult = ScanResult(
            success: false,
            message: 'Esta entrada ya fue usada',
            ticketCode: code,
            details: reservation,
          );
          _isProcessing = false;
        });
        await _vibrate();
      } else if (status == 'cancelado') {
        setState(() {
          _lastResult = ScanResult(
            success: false,
            message: 'Esta entrada fue cancelada',
            ticketCode: code,
            details: reservation,
          );
          _isProcessing = false;
        });
        await _vibrate();
      } else {
        // Realizar check-in
        final checkinResponse = await api.doCheckin(reservationId);

        if (!mounted) return;

        if (checkinResponse.success) {
          setState(() {
            _lastResult = ScanResult(
              success: true,
              message: 'Check-in realizado correctamente',
              ticketCode: code,
              details: reservation,
            );
            _isProcessing = false;
          });
          // Doble vibracion para exito
          await _vibrate();
          await Future.delayed(const Duration(milliseconds: 200));
          await _vibrate();
        } else {
          setState(() {
            _lastResult = ScanResult(
              success: false,
              message: checkinResponse.error ?? 'Error al realizar check-in',
              ticketCode: code,
              details: reservation,
            );
            _isProcessing = false;
          });
        }
      }
    } else {
      setState(() {
        _lastResult = ScanResult(
          success: false,
          message: 'Codigo QR no encontrado en el sistema',
          ticketCode: code,
        );
        _isProcessing = false;
      });
    }

    // Limpiar para permitir nuevo escaneo despues de un tiempo
    await Future.delayed(const Duration(seconds: 3));
    if (mounted) {
      setState(() {
        _lastScannedCode = null;
      });
    }
  }

  void _toggleTorch() {
    setState(() {
      _torchEnabled = !_torchEnabled;
    });
    _scannerController.toggleTorch();
  }

  void _switchCamera() {
    setState(() {
      _frontCamera = !_frontCamera;
    });
    _scannerController.switchCamera();
  }

  void _clearResult() {
    setState(() {
      _lastResult = null;
      _lastScannedCode = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: Text(i18n.serverConfigQrScanTitle),
        actions: [
          Semantics(
            label: _torchEnabled
                ? 'Desactivar linterna'
                : 'Activar linterna',
            button: true,
            child: IconButton(
              onPressed: _toggleTorch,
              icon: Icon(_torchEnabled ? Icons.flash_on : Icons.flash_off),
              tooltip: i18n.linterna8f3689,
            ),
          ),
          Semantics(
            label: _frontCamera
                ? 'Cambiar a camara trasera'
                : 'Cambiar a camara frontal',
            button: true,
            child: IconButton(
              onPressed: _switchCamera,
              icon: Icon(_frontCamera ? Icons.camera_front : Icons.camera_rear),
              tooltip: i18n.cambiarCamara5a36e3,
            ),
          ),
        ],
      ),
      body: Stack(
        children: [
          // Scanner
          MobileScanner(
            controller: _scannerController,
            onDetect: _onBarcodeDetected,
          ),

          // Overlay con guia de escaneo
          _ScannerOverlay(
            isProcessing: _isProcessing,
          ),

          // Resultado del escaneo
          if (_lastResult != null)
            Positioned(
              left: 16,
              right: 16,
              bottom: 32,
              child: _ScanResultCard(
                result: _lastResult!,
                onDismiss: _clearResult,
              ),
            ),

          // Indicador de procesamiento
          if (_isProcessing)
            Positioned(
              top: 100,
              left: 0,
              right: 0,
              child: Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 24,
                    vertical: 12,
                  ),
                  decoration: BoxDecoration(
                    color: colorScheme.surface.withOpacity(0.9),
                    borderRadius: BorderRadius.circular(24),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                      SizedBox(width: 12),
                      Text(i18n.procesandoF16b30),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// Overlay con guia de escaneo
class _ScannerOverlay extends StatelessWidget {
  final bool isProcessing;

  const _ScannerOverlay({required this.isProcessing});

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    return CustomPaint(
      painter: _ScannerOverlayPainter(
        borderColor: isProcessing
            ? Colors.orange
            : Theme.of(context).colorScheme.primary,
      ),
      child: Center(
        child: Container(
          width: 250,
          height: 250,
          decoration: BoxDecoration(
            border: Border.all(
              color: isProcessing
                  ? Colors.orange
                  : Theme.of(context).colorScheme.primary,
              width: 2,
            ),
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
    );
  }
}

class _ScannerOverlayPainter extends CustomPainter {
  final Color borderColor;

  _ScannerOverlayPainter({required this.borderColor});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.black.withOpacity(0.5)
      ..style = PaintingStyle.fill;

    final scanArea = Rect.fromCenter(
      center: Offset(size.width / 2, size.height / 2),
      width: 250,
      height: 250,
    );

    // Dibujar overlay oscuro alrededor del area de escaneo
    canvas.drawPath(
      Path.combine(
        PathOperation.difference,
        Path()..addRect(Rect.fromLTWH(0, 0, size.width, size.height)),
        Path()
          ..addRRect(RRect.fromRectAndRadius(scanArea, const Radius.circular(12))),
      ),
      paint,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// Tarjeta de resultado del escaneo
class _ScanResultCard extends StatelessWidget {
  final ScanResult result;
  final VoidCallback onDismiss;

  const _ScanResultCard({
    required this.result,
    required this.onDismiss,
  });

  @override
  Widget build(BuildContext context) {
    final i18n = AppLocalizations.of(context)!;
    final colorScheme = Theme.of(context).colorScheme;
    final isSuccess = result.success;

    return Semantics(
      label: isSuccess
          ? 'Check-in exitoso: ${result.message}'
          : 'Error de escaneo: ${result.message}',
      liveRegion: true,
      child: Card(
        color: isSuccess ? Colors.green.shade50 : Colors.red.shade50,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                children: [
                  ExcludeSemantics(
                    child: Icon(
                      isSuccess ? Icons.check_circle : Icons.error,
                      color: isSuccess ? Colors.green : Colors.red,
                      size: 32,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      result.message,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                            color: isSuccess ? Colors.green.shade700 : Colors.red.shade700,
                          ),
                    ),
                  ),
                  Semantics(
                    label: 'Cerrar resultado',
                    button: true,
                    child: IconButton(
                      onPressed: onDismiss,
                      icon: const Icon(Icons.close),
                      visualDensity: VisualDensity.compact,
                      tooltip: 'Cerrar',
                    ),
                  ),
                ],
              ),
            if (result.details != null) ...[
              const SizedBox(height: 12),
              const Divider(),
              const SizedBox(height: 8),
              _DetailItem(
                icon: Icons.confirmation_number,
                label: 'Codigo',
                value: result.ticketCode,
              ),
              if (result.details!['ticket_name'] != null)
                _DetailItem(
                  icon: Icons.category,
                  label: 'Ticket',
                  value: result.details!['ticket_name'],
                ),
              if (result.details!['date'] != null)
                _DetailItem(
                  icon: Icons.calendar_today,
                  label: 'Fecha',
                  value: result.details!['date'],
                ),
              if (result.details!['customer'] != null &&
                  result.details!['customer']['name'] != null)
                _DetailItem(
                  icon: Icons.person,
                  label: 'Cliente',
                  value: result.details!['customer']['name'],
                ),
            ],
          ],
        ),
      ),
      ),
    );
  }
}

class _DetailItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _DetailItem({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: '$label: $value',
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          children: [
            ExcludeSemantics(
              child: Icon(icon, size: 16, color: Colors.grey),
            ),
            const SizedBox(width: 8),
            ExcludeSemantics(
              child: Text(
                '$label: ',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Colors.grey,
                    ),
              ),
            ),
            ExcludeSemantics(
              child: Text(
                value,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w500,
                    ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Modelo para el resultado del escaneo
class ScanResult {
  final bool success;
  final String message;
  final String ticketCode;
  final Map<String, dynamic>? details;

  ScanResult({
    required this.success,
    required this.message,
    required this.ticketCode,
    this.details,
  });
}
