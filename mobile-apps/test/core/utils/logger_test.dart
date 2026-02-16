import 'package:flutter_test/flutter_test.dart';
import 'package:chat_ia_apps/core/utils/logger.dart';

void main() {
  group('Logger', () {
    test('should format message with tag correctly', () {
      // Logger solo imprime en debug mode, verificamos que no lanza errores
      expect(() => Logger.d('Test message', tag: 'TestTag'), returnsNormally);
      expect(() => Logger.i('Info message', tag: 'TestTag'), returnsNormally);
      expect(() => Logger.w('Warning message', tag: 'TestTag'), returnsNormally);
      expect(() => Logger.e('Error message', tag: 'TestTag'), returnsNormally);
    });

    test('should handle null tag gracefully', () {
      expect(() => Logger.d('Message without tag'), returnsNormally);
    });

    test('should handle error objects', () {
      final error = Exception('Test error');
      expect(
        () => Logger.e('Error occurred', tag: 'Test', error: error),
        returnsNormally,
      );
    });

    test('should handle API logging', () {
      expect(
        () => Logger.api('/api/test', method: 'GET', statusCode: 200),
        returnsNormally,
      );
    });

    test('should handle navigation logging', () {
      expect(
        () => Logger.nav('/profile'),
        returnsNormally,
      );
      expect(
        () => Logger.nav('/home', params: {'id': '123'}),
        returnsNormally,
      );
    });

    test('should handle state logging', () {
      expect(
        () => Logger.state('UserState', 'update', data: {'id': 1, 'name': 'Test'}),
        returnsNormally,
      );
    });

    group('Performance Timer', () {
      test('should create timer without errors', () {
        expect(() => Logger.startTimer('test_operation'), returnsNormally);
      });

      test('should end timer without errors', () {
        final stopwatch = Logger.startTimer('test_op');
        expect(() => Logger.endTimer(stopwatch, 'test_op'), returnsNormally);
      });

      test('should handle ending null timer', () {
        expect(() => Logger.endTimer(null, 'non_existent'), returnsNormally);
      });
    });
  });
}
