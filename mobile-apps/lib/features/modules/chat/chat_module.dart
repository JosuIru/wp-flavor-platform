/// Módulo de Chat tipo Telegram/WhatsApp
///
/// Este módulo proporciona funcionalidad completa de mensajería:
/// - Chats individuales con cifrado E2E
/// - Grupos con niveles de privacidad (público, privado, secreto)
/// - Estados/Historias que duran 24 horas
/// - Mensajes de voz y video
/// - Stickers, GIFs y emojis
/// - Llamadas de voz y video
/// - Búsqueda avanzada de mensajes
/// - Reacciones y respuestas
/// - Encuestas y ubicaciones
library chat_module;

// Pantallas principales
export 'chat_conversations_screen.dart';
export 'chat_main_screen.dart';

// Pantallas secundarias
export 'screens/status_screen.dart';
export 'screens/group_info_screen.dart';
export 'screens/search_messages_screen.dart';
export 'screens/call_screen.dart';
export 'screens/create_group_screen.dart';

// Widgets
export 'widgets/message_bubble.dart';
export 'widgets/chat_input.dart';
export 'widgets/sticker_picker.dart';

// Servicio
export '../../../core/services/chat_service.dart';
