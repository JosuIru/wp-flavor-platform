import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Widget selector de emojis con categorías y favoritos
class EmojiPicker extends StatefulWidget {
  final Function(String emoji) onEmojiSelected;
  final double height;
  final Color? backgroundColor;
  final Color? categoryColor;

  const EmojiPicker({
    super.key,
    required this.onEmojiSelected,
    this.height = 250,
    this.backgroundColor,
    this.categoryColor,
  });

  @override
  State<EmojiPicker> createState() => _EmojiPickerState();
}

class _EmojiPickerState extends State<EmojiPicker> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<String> _recentEmojis = [];
  static const String _recentKey = 'recent_emojis';
  static const int _maxRecent = 30;

  // Categorías de emojis
  static const Map<String, List<String>> _emojiCategories = {
    'Recientes': [], // Se llena dinámicamente
    'Caras': _faceEmojis,
    'Gestos': _gestureEmojis,
    'Personas': _peopleEmojis,
    'Animales': _animalEmojis,
    'Comida': _foodEmojis,
    'Actividades': _activityEmojis,
    'Viajes': _travelEmojis,
    'Objetos': _objectEmojis,
    'Símbolos': _symbolEmojis,
  };

  static const List<IconData> _categoryIcons = [
    Icons.access_time,
    Icons.emoji_emotions,
    Icons.waving_hand,
    Icons.person,
    Icons.pets,
    Icons.restaurant,
    Icons.sports_soccer,
    Icons.directions_car,
    Icons.lightbulb,
    Icons.favorite,
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: _emojiCategories.length,
      vsync: this,
    );
    _loadRecentEmojis();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadRecentEmojis() async {
    final prefs = await SharedPreferences.getInstance();
    final recent = prefs.getStringList(_recentKey) ?? [];
    setState(() {
      _recentEmojis = recent;
    });
  }

  Future<void> _addToRecent(String emoji) async {
    // Remover si ya existe
    _recentEmojis.remove(emoji);
    // Añadir al principio
    _recentEmojis.insert(0, emoji);
    // Limitar cantidad
    if (_recentEmojis.length > _maxRecent) {
      _recentEmojis = _recentEmojis.sublist(0, _maxRecent);
    }
    // Guardar
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_recentKey, _recentEmojis);
    setState(() {});
  }

  void _onEmojiTap(String emoji) {
    _addToRecent(emoji);
    widget.onEmojiSelected(emoji);
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final bgColor = widget.backgroundColor ?? colorScheme.surface;
    final catColor = widget.categoryColor ?? colorScheme.primary;

    return Container(
      height: widget.height,
      color: bgColor,
      child: Column(
        children: [
          // Tabs de categorías
          Container(
            color: bgColor,
            child: TabBar(
              controller: _tabController,
              isScrollable: true,
              indicatorColor: catColor,
              labelColor: catColor,
              unselectedLabelColor: colorScheme.onSurfaceVariant,
              tabs: List.generate(_emojiCategories.length, (index) {
                return Tab(
                  icon: Icon(_categoryIcons[index], size: 22),
                );
              }),
            ),
          ),
          // Grid de emojis
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: _emojiCategories.entries.map((entry) {
                final emojis = entry.key == 'Recientes'
                    ? _recentEmojis
                    : entry.value;

                if (emojis.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.emoji_emotions_outlined,
                          size: 48,
                          color: colorScheme.onSurfaceVariant.withOpacity(0.5),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          entry.key == 'Recientes'
                              ? 'Los emojis usados aparecerán aquí'
                              : 'Sin emojis',
                          style: TextStyle(
                            color: colorScheme.onSurfaceVariant,
                          ),
                        ),
                      ],
                    ),
                  );
                }

                return GridView.builder(
                  padding: const EdgeInsets.all(8),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 8,
                    childAspectRatio: 1,
                  ),
                  itemCount: emojis.length,
                  itemBuilder: (context, index) {
                    final emoji = emojis[index];
                    return InkWell(
                      onTap: () => _onEmojiTap(emoji),
                      borderRadius: BorderRadius.circular(8),
                      child: Center(
                        child: Text(
                          emoji,
                          style: const TextStyle(fontSize: 24),
                        ),
                      ),
                    );
                  },
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  // =========================================================================
  // LISTAS DE EMOJIS
  // =========================================================================

  static const List<String> _faceEmojis = [
    '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃',
    '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '☺️', '😚',
    '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔',
    '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '😮‍💨',
    '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢',
    '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '🥸',
    '😎', '🤓', '🧐', '😕', '😟', '🙁', '☹️', '😮', '😯', '😲',
    '😳', '🥺', '😦', '😧', '😨', '😰', '😥', '😢', '😭', '😱',
    '😖', '😣', '😞', '😓', '😩', '😫', '🥱', '😤', '😡', '😠',
    '🤬', '😈', '👿', '💀', '☠️', '💩', '🤡', '👹', '👺', '👻',
    '👽', '👾', '🤖', '😺', '😸', '😹', '😻', '😼', '😽', '🙀',
    '😿', '😾', '🙈', '🙉', '🙊', '💋', '💌', '💘', '💝', '💖',
    '💗', '💓', '💞', '💕', '💟', '❣️', '💔', '❤️‍🔥', '❤️‍🩹', '❤️',
    '🧡', '💛', '💚', '💙', '💜', '🤎', '🖤', '🤍', '💯', '💢',
    '💥', '💫', '💦', '💨', '🕳️', '💣', '💬', '👁️‍🗨️', '🗨️', '🗯️',
    '💭', '💤', '🎉', '🎊', '✨', '🌟', '⭐', '🔥', '💪', '👍',
  ];

  static const List<String> _gestureEmojis = [
    '👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞',
    '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍',
    '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝',
    '🙏', '✍️', '💅', '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂',
    '🦻', '👃', '🧠', '🫀', '🫁', '🦷', '🦴', '👀', '👁️', '👅',
    '👄', '👶', '🧒', '👦', '👧', '🧑', '👱', '👨', '🧔', '👩',
  ];

  static const List<String> _peopleEmojis = [
    '👨‍⚕️', '👩‍⚕️', '👨‍🎓', '👩‍🎓', '👨‍🏫', '👩‍🏫', '👨‍⚖️', '👩‍⚖️', '👨‍🌾', '👩‍🌾',
    '👨‍🍳', '👩‍🍳', '👨‍🔧', '👩‍🔧', '👨‍🏭', '👩‍🏭', '👨‍💼', '👩‍💼', '👨‍🔬', '👩‍🔬',
    '👨‍💻', '👩‍💻', '👨‍🎤', '👩‍🎤', '👨‍🎨', '👩‍🎨', '👨‍✈️', '👩‍✈️', '👨‍🚀', '👩‍🚀',
    '👨‍🚒', '👩‍🚒', '👮', '🕵️', '💂', '🥷', '👷', '🤴', '👸', '👳',
    '👲', '🧕', '🤵', '👰', '🤰', '🤱', '👼', '🎅', '🤶', '🦸',
    '🦹', '🧙', '🧚', '🧛', '🧜', '🧝', '🧞', '🧟', '💆', '💇',
    '🚶', '🧍', '🧎', '🏃', '💃', '🕺', '👯', '🧖', '🧗', '🤸',
    '🏌️', '🏇', '⛷️', '🏂', '🏋️', '🤼', '🤽', '🤾', '🤺', '⛹️',
    '🏊', '🚣', '🧘', '🛀', '🛌', '👭', '👫', '👬', '💏', '💑',
    '👪', '👨‍👩‍👦', '👨‍👩‍👧', '👨‍👩‍👧‍👦', '👨‍👩‍👦‍👦', '👨‍👩‍👧‍👧', '👨‍👦', '👨‍👦‍👦', '👨‍👧', '👨‍👧‍👦',
  ];

  static const List<String> _animalEmojis = [
    '🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐻‍❄️', '🐨',
    '🐯', '🦁', '🐮', '🐷', '🐽', '🐸', '🐵', '🙈', '🙉', '🙊',
    '🐒', '🐔', '🐧', '🐦', '🐤', '🐣', '🐥', '🦆', '🦅', '🦉',
    '🦇', '🐺', '🐗', '🐴', '🦄', '🐝', '🪱', '🐛', '🦋', '🐌',
    '🐞', '🐜', '🪰', '🪲', '🪳', '🦟', '🦗', '🕷️', '🕸️', '🦂',
    '🐢', '🐍', '🦎', '🦖', '🦕', '🐙', '🦑', '🦐', '🦞', '🦀',
    '🐡', '🐠', '🐟', '🐬', '🐳', '🐋', '🦈', '🐊', '🐅', '🐆',
    '🦓', '🦍', '🦧', '🦣', '🐘', '🦛', '🦏', '🐪', '🐫', '🦒',
    '🦘', '🦬', '🐃', '🐂', '🐄', '🐎', '🐖', '🐏', '🐑', '🦙',
    '🐐', '🦌', '🐕', '🐩', '🦮', '🐕‍🦺', '🐈', '🐈‍⬛', '🪶', '🐓',
    '🦃', '🦤', '🦚', '🦜', '🦢', '🦩', '🕊️', '🐇', '🦝', '🦨',
    '🦡', '🦫', '🦦', '🦥', '🐁', '🐀', '🐿️', '🦔', '🐾', '🐉',
  ];

  static const List<String> _foodEmojis = [
    '🍏', '🍎', '🍐', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐',
    '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑',
    '🥦', '🥬', '🥒', '🌶️', '🫑', '🌽', '🥕', '🫒', '🧄', '🧅',
    '🥔', '🍠', '🥐', '🥯', '🍞', '🥖', '🥨', '🧀', '🥚', '🍳',
    '🧈', '🥞', '🧇', '🥓', '🥩', '🍗', '🍖', '🦴', '🌭', '🍔',
    '🍟', '🍕', '🫓', '🥪', '🥙', '🧆', '🌮', '🌯', '🫔', '🥗',
    '🥘', '🫕', '🍝', '🍜', '🍲', '🍛', '🍣', '🍱', '🥟', '🦪',
    '🍤', '🍙', '🍚', '🍘', '🍥', '🥠', '🥮', '🍢', '🍡', '🍧',
    '🍨', '🍦', '🥧', '🧁', '🍰', '🎂', '🍮', '🍭', '🍬', '🍫',
    '🍿', '🍩', '🍪', '🌰', '🥜', '🍯', '🥛', '🍼', '🫖', '☕',
    '🍵', '🧃', '🥤', '🧋', '🍶', '🍺', '🍻', '🥂', '🍷', '🥃',
    '🍸', '🍹', '🧉', '🍾', '🧊', '🥄', '🍴', '🍽️', '🥣', '🥡',
  ];

  static const List<String> _activityEmojis = [
    '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱',
    '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳',
    '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛼', '🛷',
    '⛸️', '🥌', '🎿', '⛷️', '🏂', '🪂', '🏋️', '🤼', '🤸', '⛹️',
    '🤺', '🤾', '🏌️', '🏇', '⛹️', '🏊', '🚣', '🧗', '🚵', '🚴',
    '🏆', '🥇', '🥈', '🥉', '🏅', '🎖️', '🏵️', '🎗️', '🎫', '🎟️',
    '🎪', '🤹', '🎭', '🩰', '🎨', '🎬', '🎤', '🎧', '🎼', '🎹',
    '🥁', '🪘', '🎷', '🎺', '🪗', '🎸', '🪕', '🎻', '🎲', '♟️',
    '🎯', '🎳', '🎮', '🎰', '🧩', '🎴', '🀄', '🃏', '🪄', '🎩',
  ];

  static const List<String> _travelEmojis = [
    '🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐',
    '🛻', '🚚', '🚛', '🚜', '🦯', '🦽', '🦼', '🛴', '🚲', '🛵',
    '🏍️', '🛺', '🚨', '🚔', '🚍', '🚘', '🚖', '🚡', '🚠', '🚟',
    '🚃', '🚋', '🚞', '🚝', '🚄', '🚅', '🚈', '🚂', '🚆', '🚇',
    '🚊', '🚉', '✈️', '🛫', '🛬', '🛩️', '💺', '🛰️', '🚀', '🛸',
    '🚁', '🛶', '⛵', '🚤', '🛥️', '🛳️', '⛴️', '🚢', '⚓', '🪝',
    '⛽', '🚧', '🚦', '🚥', '🚏', '🗺️', '🗿', '🗽', '🗼', '🏰',
    '🏯', '🏟️', '🎡', '🎢', '🎠', '⛲', '⛱️', '🏖️', '🏝️', '🏜️',
    '🌋', '⛰️', '🏔️', '🗻', '🏕️', '⛺', '🛖', '🏠', '🏡', '🏘️',
    '🏚️', '🏗️', '🏢', '🏬', '🏣', '🏤', '🏥', '🏦', '🏨', '🏪',
    '🏫', '🏩', '💒', '🏛️', '⛪', '🕌', '🕍', '🛕', '🕋', '⛩️',
    '🛤️', '🛣️', '🗾', '🎑', '🏞️', '🌅', '🌄', '🌠', '🎇', '🎆',
    '🌇', '🌆', '🏙️', '🌃', '🌌', '🌉', '🌁', '🛤️', '🌍', '🌎',
    '🌏', '🪐', '💫', '⭐', '🌟', '✨', '⚡', '☄️', '💥', '🔥',
  ];

  static const List<String> _objectEmojis = [
    '⌚', '📱', '📲', '💻', '⌨️', '🖥️', '🖨️', '🖱️', '🖲️', '🕹️',
    '🗜️', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥',
    '📽️', '🎞️', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙️', '🎚️',
    '🎛️', '🧭', '⏱️', '⏲️', '⏰', '🕰️', '⌛', '⏳', '📡', '🔋',
    '🔌', '💡', '🔦', '🕯️', '🪔', '🧯', '🛢️', '💸', '💵', '💴',
    '💶', '💷', '🪙', '💰', '💳', '💎', '⚖️', '🪜', '🧰', '🪛',
    '🔧', '🔨', '⚒️', '🛠️', '⛏️', '🪚', '🔩', '⚙️', '🪤', '🧱',
    '⛓️', '🧲', '🔫', '💣', '🧨', '🪓', '🔪', '🗡️', '⚔️', '🛡️',
    '🚬', '⚰️', '🪦', '⚱️', '🏺', '🔮', '📿', '🧿', '💈', '⚗️',
    '🔭', '🔬', '🕳️', '🩹', '🩺', '💊', '💉', '🩸', '🧬', '🦠',
    '🧫', '🧪', '🌡️', '🧹', '🪠', '🧺', '🧻', '🚽', '🚰', '🚿',
    '🛁', '🛀', '🧼', '🪥', '🪒', '🧽', '🪣', '🧴', '🛎️', '🔑',
    '🗝️', '🚪', '🪑', '🛋️', '🛏️', '🛌', '🧸', '🪆', '🖼️', '🪞',
    '🪟', '🛍️', '🛒', '🎁', '🎈', '🎏', '🎀', '🪄', '🪅', '🎊',
    '🎉', '🎎', '🏮', '🎐', '🧧', '✉️', '📩', '📨', '📧', '💌',
    '📥', '📤', '📦', '🏷️', '🪧', '📪', '📫', '📬', '📭', '📮',
  ];

  static const List<String> _symbolEmojis = [
    '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔',
    '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️',
    '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐',
    '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐',
    '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️', '📴', '📳',
    '🈶', '🈚', '🈸', '🈺', '🈷️', '✴️', '🆚', '💮', '🉐', '㊙️',
    '㊗️', '🈴', '🈵', '🈹', '🈲', '🅰️', '🅱️', '🆎', '🆑', '🅾️',
    '🆘', '❌', '⭕', '🛑', '⛔', '📛', '🚫', '💯', '💢', '♨️',
    '🚷', '🚯', '🚳', '🚱', '🔞', '📵', '🚭', '❗', '❕', '❓',
    '❔', '‼️', '⁉️', '🔅', '🔆', '〽️', '⚠️', '🚸', '🔱', '⚜️',
    '🔰', '♻️', '✅', '🈯', '💹', '❇️', '✳️', '❎', '🌐', '💠',
    'Ⓜ️', '🌀', '💤', '🏧', '🚾', '♿', '🅿️', '🛗', '🈳', '🈂️',
    '🛂', '🛃', '🛄', '🛅', '🚹', '🚺', '🚼', '⚧️', '🚻', '🚮',
    '🎦', '📶', '🈁', '🔣', 'ℹ️', '🔤', '🔡', '🔠', '🆖', '🆗',
    '🆙', '🆒', '🆕', '🆓', '0️⃣', '1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣',
    '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟', '🔢', '#️⃣', '*️⃣', '⏏️', '▶️',
    '⏸️', '⏯️', '⏹️', '⏺️', '⏭️', '⏮️', '⏩', '⏪', '⏫', '⏬',
    '◀️', '🔼', '🔽', '➡️', '⬅️', '⬆️', '⬇️', '↗️', '↘️', '↙️',
    '↖️', '↕️', '↔️', '↪️', '↩️', '⤴️', '⤵️', '🔀', '🔁', '🔂',
    '🔄', '🔃', '🎵', '🎶', '➕', '➖', '➗', '✖️', '♾️', '💲',
    '💱', '™️', '©️', '®️', '👁️‍🗨️', '🔚', '🔙', '🔛', '🔝', '🔜',
    '〰️', '➰', '➿', '✔️', '☑️', '🔘', '🔴', '🟠', '🟡', '🟢',
    '🔵', '🟣', '⚫', '⚪', '🟤', '🔺', '🔻', '🔸', '🔹', '🔶',
    '🔷', '🔳', '🔲', '▪️', '▫️', '◾', '◽', '◼️', '◻️', '🟥',
    '🟧', '🟨', '🟩', '🟦', '🟪', '⬛', '⬜', '🟫', '🔈', '🔇',
    '🔉', '🔊', '🔔', '🔕', '📣', '📢', '💬', '💭', '🗯️', '♠️',
    '♣️', '♥️', '♦️', '🃏', '🎴', '🀄', '🕐', '🕑', '🕒', '🕓',
    '🕔', '🕕', '🕖', '🕗', '🕘', '🕙', '🕚', '🕛', '🕜', '🕝',
    '🕞', '🕟', '🕠', '🕡', '🕢', '🕣', '🕤', '🕥', '🕦', '🕧',
  ];
}

/// Muestra el selector de emojis como bottom sheet
Future<void> showEmojiPicker(
  BuildContext context, {
  required Function(String emoji) onEmojiSelected,
  double height = 280,
}) async {
  await showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Handle
          Container(
            margin: const EdgeInsets.only(top: 8),
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey[400],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          // Picker
          EmojiPicker(
            onEmojiSelected: (emoji) {
              onEmojiSelected(emoji);
              Navigator.pop(context);
            },
            height: height,
          ),
        ],
      ),
    ),
  );
}
