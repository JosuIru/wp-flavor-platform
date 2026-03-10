(function () {
    'use strict';

    if (!window.flavorPortalTools) {
        return;
    }

    document.addEventListener('click', function (event) {
        var priorityButton = event.target.closest('.flavor-priority-filter__btn');

        if (priorityButton) {
            event.preventDefault();

            var priority = priorityButton.getAttribute('data-priority') || 'all';
            var buttons = document.querySelectorAll('.flavor-priority-filter__btn');

            Array.prototype.forEach.call(buttons, function (button) {
                button.classList.remove('is-active');
                button.setAttribute('aria-pressed', 'false');
            });

            priorityButton.classList.add('is-active');
            priorityButton.setAttribute('aria-pressed', 'true');

            applyPriorityFilter(priority);
            return;
        }

        var button = event.target.closest('.flavor-tool-favorite-toggle');

        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (button.disabled) {
            return;
        }

        var toolId = button.getAttribute('data-tool-id');

        if (!toolId) {
            return;
        }

        button.disabled = true;
        button.classList.add('is-loading');

        var formData = new FormData();
        formData.append('action', 'flavor_toggle_portal_tool_favorite');
        formData.append('nonce', flavorPortalTools.nonce);
        formData.append('tool_id', toolId);

        fetch(flavorPortalTools.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.success) {
                    throw new Error((payload && payload.data && payload.data.message) || flavorPortalTools.strings.saveError);
                }

                // Actualizar UI sin reload
                updateFavoriteUI(toolId, payload.data);
                button.disabled = false;
                button.classList.remove('is-loading');
            })
            .catch(function () {
                button.disabled = false;
                button.classList.remove('is-loading');
                window.alert(flavorPortalTools.strings.saveError);
            });
    });

    /**
     * Actualiza la UI cuando se toggle un favorito
     */
    function updateFavoriteUI(toolId, data) {
        var isFavorite = data && data.is_favorite;

        // Actualizar todos los botones de este tool
        var buttons = document.querySelectorAll('.flavor-tool-favorite-toggle[data-tool-id="' + toolId + '"]');
        Array.prototype.forEach.call(buttons, function (btn) {
            btn.classList.toggle('is-active', isFavorite);
            btn.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');

            // Actualizar icono si existe
            var icon = btn.querySelector('.dashicons');
            if (icon) {
                icon.classList.remove('dashicons-star-filled', 'dashicons-star-empty');
                icon.classList.add(isFavorite ? 'dashicons-star-filled' : 'dashicons-star-empty');
            }

            // Actualizar título/tooltip
            btn.setAttribute('title', isFavorite
                ? (flavorPortalTools.strings.removeFavorite || 'Quitar de favoritos')
                : (flavorPortalTools.strings.addFavorite || 'Añadir a favoritos')
            );
        });

        // Actualizar el card principal
        var cards = document.querySelectorAll('.flavor-quick-action-card[data-tool-id="' + toolId + '"]');
        Array.prototype.forEach.call(cards, function (card) {
            card.classList.toggle('is-favorite', isFavorite);
        });

        // Actualizar strip de favoritos
        updateFavoritesStrip(toolId, isFavorite, data);

        // Mostrar feedback visual
        showFavoriteToast(isFavorite);
    }

    /**
     * Actualiza el strip de favoritos
     */
    function updateFavoritesStrip(toolId, isFavorite, data) {
        var strip = document.querySelector('.flavor-tool-focus-strip');

        if (!strip) {
            return;
        }

        var existingItem = strip.querySelector('.flavor-focus-item[data-tool-id="' + toolId + '"]');

        if (isFavorite && !existingItem && data && data.tool_data) {
            // Añadir al strip
            var toolData = data.tool_data;
            var newItem = document.createElement('a');
            newItem.href = toolData.url || '#';
            newItem.className = 'flavor-focus-item';
            newItem.setAttribute('data-tool-id', toolId);
            newItem.innerHTML = '<span class="flavor-focus-item__icon">' + (toolData.icon || '⭐') + '</span>' +
                '<span class="flavor-focus-item__name">' + (toolData.title || toolId) + '</span>';

            // Insertar antes del mensaje vacío o al final
            var emptyMsg = strip.querySelector('.flavor-focus-empty');
            if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
            strip.appendChild(newItem);
        } else if (!isFavorite && existingItem) {
            // Quitar del strip con animación
            existingItem.style.opacity = '0';
            existingItem.style.transform = 'scale(0.8)';
            setTimeout(function () {
                existingItem.remove();

                // Mostrar mensaje vacío si no quedan favoritos
                var remainingItems = strip.querySelectorAll('.flavor-focus-item');
                if (remainingItems.length === 0) {
                    var emptyMsg = strip.querySelector('.flavor-focus-empty');
                    if (emptyMsg) {
                        emptyMsg.style.display = '';
                    }
                }
            }, 200);
        }
    }

    /**
     * Muestra un toast de feedback
     */
    function showFavoriteToast(isFavorite) {
        var message = isFavorite
            ? (flavorPortalTools.strings.addedToFavorites || 'Añadido a favoritos')
            : (flavorPortalTools.strings.removedFromFavorites || 'Quitado de favoritos');

        // Crear toast si no existe
        var toast = document.querySelector('.flavor-portal-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'flavor-portal-toast';
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.add('is-visible');

        setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2000);
    }

    function applyPriorityFilter(priority) {
        var normalizedPriority = priority || 'all';
        var selectors = [
            '.flavor-notification-item',
            '.flavor-quick-action-card',
            '.flavor-action-item'
        ];

        selectors.forEach(function (selector) {
            var items = document.querySelectorAll(selector);

            Array.prototype.forEach.call(items, function (item) {
                var itemSeverity = item.getAttribute('data-severity') || 'stable';
                var shouldShow = normalizedPriority === 'all' || itemSeverity === normalizedPriority;
                item.style.display = shouldShow ? '' : 'none';
            });
        });
    }
})();
