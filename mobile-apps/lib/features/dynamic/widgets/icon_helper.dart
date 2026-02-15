import 'package:flutter/material.dart';

/// Helper para convertir nombres de iconos (string) a IconData
class IconHelper {
  static final Map<String, IconData> _iconMap = {
    // General
    'extension': Icons.extension,
    'settings': Icons.settings,
    'home': Icons.home,
    'search': Icons.search,
    'add': Icons.add,
    'edit': Icons.edit,
    'delete': Icons.delete,
    'check': Icons.check,
    'close': Icons.close,
    'refresh': Icons.refresh,
    'share': Icons.share,
    'favorite': Icons.favorite,
    'star': Icons.star,
    'info': Icons.info,
    'help': Icons.help,
    'warning': Icons.warning,
    'error': Icons.error,

    // Comunicación
    'email': Icons.email,
    'phone': Icons.phone,
    'chat': Icons.chat,
    'chat_bubble': Icons.chat_bubble,
    'message': Icons.message,
    'forum': Icons.forum,
    'notifications': Icons.notifications,
    'send': Icons.send,

    // Navegación
    'map': Icons.map,
    'location_on': Icons.location_on,
    'directions': Icons.directions,
    'directions_car': Icons.directions_car,
    'directions_bike': Icons.directions_bike,
    'navigation': Icons.navigation,
    'place': Icons.place,

    // Personas
    'person': Icons.person,
    'people': Icons.people,
    'groups': Icons.groups,
    'group': Icons.group,
    'account_circle': Icons.account_circle,
    'face': Icons.face,
    'handshake': Icons.handshake,
    'volunteer_activism': Icons.volunteer_activism,

    // Eventos y tiempo
    'event': Icons.event,
    'event_seat': Icons.event_seat,
    'calendar_today': Icons.calendar_today,
    'schedule': Icons.schedule,
    'access_time': Icons.access_time,
    'today': Icons.today,
    'date_range': Icons.date_range,

    // Comercio
    'shopping_cart': Icons.shopping_cart,
    'store': Icons.store,
    'local_offer': Icons.local_offer,
    'receipt': Icons.receipt,
    'payment': Icons.payment,
    'credit_card': Icons.credit_card,
    'attach_money': Icons.attach_money,
    'euro': Icons.euro,
    'currency_bitcoin': Icons.currency_bitcoin,

    // Trabajo
    'work': Icons.work,
    'business': Icons.business,
    'business_center': Icons.business_center,
    'assignment': Icons.assignment,
    'task': Icons.task,
    'checklist': Icons.checklist,

    // Educación
    'school': Icons.school,
    'menu_book': Icons.menu_book,
    'local_library': Icons.local_library,
    'class_': Icons.class_,
    'science': Icons.science,
    'lightbulb': Icons.lightbulb,

    // Media
    'perm_media': Icons.perm_media,
    'image': Icons.image,
    'photo': Icons.photo,
    'video_library': Icons.video_library,
    'music_note': Icons.music_note,
    'mic': Icons.mic,
    'podcasts': Icons.podcasts,
    'radio': Icons.radio,
    'headphones': Icons.headphones,
    'play_arrow': Icons.play_arrow,
    'pause': Icons.pause,

    // Naturaleza y ecología
    'eco': Icons.eco,
    'nature': Icons.nature,
    'park': Icons.park,
    'grass': Icons.grass,
    'compost': Icons.compost,
    'recycling': Icons.recycling,
    'water_drop': Icons.water_drop,
    'wb_sunny': Icons.wb_sunny,

    // Transporte
    'local_parking': Icons.local_parking,
    'pedal_bike': Icons.pedal_bike,
    'electric_bike': Icons.electric_bike,
    'car_rental': Icons.car_rental,
    'airport_shuttle': Icons.airport_shuttle,

    // Edificios y lugares
    'location_city': Icons.location_city,
    'apartment': Icons.apartment,
    'meeting_room': Icons.meeting_room,
    'restaurant': Icons.restaurant,
    'local_bar': Icons.local_bar,
    'local_cafe': Icons.local_cafe,
    'hotel': Icons.hotel,
    'house': Icons.house,

    // Participación
    'how_to_vote': Icons.how_to_vote,
    'ballot': Icons.ballot,
    'campaign': Icons.campaign,
    'public': Icons.public,
    'visibility': Icons.visibility,
    'account_balance': Icons.account_balance,
    'gavel': Icons.gavel,

    // Salud y bienestar
    'health_and_safety': Icons.health_and_safety,
    'medical_services': Icons.medical_services,
    'local_hospital': Icons.local_hospital,
    'fitness_center': Icons.fitness_center,
    'spa': Icons.spa,
    'self_improvement': Icons.self_improvement,

    // Tecnología
    'computer': Icons.computer,
    'smartphone': Icons.smartphone,
    'code': Icons.code,
    'terminal': Icons.terminal,
    'api': Icons.api,
    'cloud': Icons.cloud,
    'trending_up': Icons.trending_up,
    'auto_awesome': Icons.auto_awesome,
    'smart_toy': Icons.smart_toy,

    // Herramientas
    'build': Icons.build,
    'construction': Icons.construction,
    'handyman': Icons.handyman,
    'palette': Icons.palette,
    'brush': Icons.brush,

    // Documentos
    'description': Icons.description,
    'article': Icons.article,
    'folder': Icons.folder,
    'file_present': Icons.file_present,
    'attach_file': Icons.attach_file,
    'picture_as_pdf': Icons.picture_as_pdf,

    // Social
    'thumb_up': Icons.thumb_up,
    'comment': Icons.comment,
    'bookmark': Icons.bookmark,
    'report': Icons.report,
    'report_problem': Icons.report_problem,

    // Misc
    'touch_app': Icons.touch_app,
    'chevron_right': Icons.chevron_right,
    'arrow_forward': Icons.arrow_forward,
    'more_vert': Icons.more_vert,
    'more_horiz': Icons.more_horiz,
    'download': Icons.download,
    'upload': Icons.upload,
    'qr_code': Icons.qr_code,
    'badge': Icons.badge,
    'verified': Icons.verified,
  };

  static IconData getIcon(String name) {
    return _iconMap[name] ?? Icons.extension;
  }

  static Color getStatusColor(String? estado) {
    switch (estado?.toLowerCase()) {
      case 'activo':
      case 'active':
      case 'confirmado':
      case 'confirmed':
      case 'aprobado':
      case 'approved':
      case 'pagado':
      case 'paid':
      case 'completado':
      case 'completed':
      case 'disponible':
      case 'available':
        return Colors.green;

      case 'pendiente':
      case 'pending':
      case 'en_proceso':
      case 'processing':
      case 'en_revision':
      case 'review':
      case 'programado':
      case 'scheduled':
        return Colors.orange;

      case 'cancelado':
      case 'cancelled':
      case 'rechazado':
      case 'rejected':
      case 'inactivo':
      case 'inactive':
      case 'error':
      case 'fallido':
      case 'failed':
        return Colors.red;

      case 'borrador':
      case 'draft':
      case 'nuevo':
      case 'new':
        return Colors.blue;

      default:
        return Colors.grey;
    }
  }

  static String getStatusLabel(String? estado) {
    if (estado == null) return '';

    final labels = {
      'activo': 'Activo',
      'active': 'Activo',
      'pendiente': 'Pendiente',
      'pending': 'Pendiente',
      'cancelado': 'Cancelado',
      'cancelled': 'Cancelado',
      'confirmado': 'Confirmado',
      'confirmed': 'Confirmado',
      'aprobado': 'Aprobado',
      'approved': 'Aprobado',
      'rechazado': 'Rechazado',
      'rejected': 'Rechazado',
      'completado': 'Completado',
      'completed': 'Completado',
      'en_proceso': 'En proceso',
      'processing': 'En proceso',
      'borrador': 'Borrador',
      'draft': 'Borrador',
      'pagado': 'Pagado',
      'paid': 'Pagado',
    };

    return labels[estado.toLowerCase()] ?? estado;
  }
}
