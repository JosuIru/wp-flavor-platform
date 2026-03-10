/**
 * Generador de Apps - JavaScript
 */

(function($) {
    'use strict';

    var AppGenerator = {
        propuesta: null,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Templates rápidos
            $('.template-btn').on('click', function() {
                var template = $(this).data('template');
                var texto = $('#template-' + template).html().trim();
                $('#proyecto-descripcion').val(texto);
            });

            // Analizar
            $('#btn-analyze').on('click', function() {
                self.analyze();
            });

            // Generar
            $('#btn-generate').on('click', function() {
                self.generate();
            });

            // Navegación
            $('#btn-back-1').on('click', function() {
                self.goToStep(1);
            });

            $('#btn-new').on('click', function() {
                self.reset();
            });
        },

        showLoader: function(text) {
            $('.loader-text').text(text || FlavorAppGenerator.strings.analyzing);
            $('.generator-loader').removeClass('hidden');
        },

        hideLoader: function() {
            $('.generator-loader').addClass('hidden');
        },

        goToStep: function(step) {
            // Actualizar steps visuales
            $('.wizard-step').removeClass('active completed');
            $('.wizard-step').each(function() {
                var stepNum = $(this).data('step');
                if (stepNum < step) {
                    $(this).addClass('completed');
                } else if (stepNum === step) {
                    $(this).addClass('active');
                }
            });

            // Mostrar contenido
            $('.wizard-content').addClass('hidden');
            $('#step-' + step).removeClass('hidden');
        },

        analyze: function() {
            var self = this;
            var descripcion = $('#proyecto-descripcion').val().trim();

            if (!descripcion) {
                alert('Por favor, describe tu proyecto');
                return;
            }

            this.showLoader(FlavorAppGenerator.strings.analyzing);

            $.ajax({
                url: FlavorAppGenerator.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'flavor_app_generator_analyze',
                    nonce: FlavorAppGenerator.nonce,
                    descripcion: descripcion
                },
                success: function(response) {
                    self.hideLoader();

                    if (response.success) {
                        self.propuesta = response.data;
                        self.renderPropuesta(response.data);
                        self.goToStep(2);
                    } else {
                        alert(response.data.message || 'Error en el análisis');
                    }
                },
                error: function() {
                    self.hideLoader();
                    alert('Error de conexión');
                }
            });
        },

        renderPropuesta: function(data) {
            var html = '';

            // Header
            html += '<div class="propuesta-header">';
            html += '<h3>' + this.escapeHtml(data.nombre_proyecto) + '</h3>';
            html += '<span class="propuesta-tipo">' + this.escapeHtml(data.tipo_comunidad) + '</span>';
            html += '</div>';

            if (data.descripcion_corta) {
                html += '<p style="color: #666; margin-bottom: 20px;">' + this.escapeHtml(data.descripcion_corta) + '</p>';
            }

            // Módulos
            html += '<div class="propuesta-section">';
            html += '<h4>Módulos a Activar</h4>';
            html += '<div class="propuesta-modulos">';

            if (data.modulos_recomendados && data.modulos_recomendados.length > 0) {
                data.modulos_recomendados.forEach(function(modulo) {
                    html += '<label class="modulo-tag recomendado">';
                    html += '<input type="checkbox" checked data-modulo="' + modulo + '">';
                    html += ' ' + modulo.replace(/-/g, ' ');
                    html += '</label>';
                });
            }

            if (data.modulos_opcionales && data.modulos_opcionales.length > 0) {
                data.modulos_opcionales.forEach(function(modulo) {
                    html += '<label class="modulo-tag">';
                    html += '<input type="checkbox" data-modulo="' + modulo + '">';
                    html += ' ' + modulo.replace(/-/g, ' ');
                    html += '</label>';
                });
            }

            html += '</div></div>';

            // Páginas
            html += '<div class="propuesta-section">';
            html += '<h4>Páginas a Crear</h4>';
            html += '<div class="propuesta-paginas">';

            if (data.paginas_sugeridas && data.paginas_sugeridas.length > 0) {
                data.paginas_sugeridas.forEach(function(pagina, index) {
                    html += '<div class="pagina-item">';
                    html += '<input type="checkbox" checked data-pagina="' + index + '">';
                    html += '<div class="pagina-info">';
                    html += '<div class="pagina-titulo">' + this.escapeHtml(pagina.titulo) + '</div>';
                    html += '<div class="pagina-slug">/' + this.escapeHtml(pagina.slug) + '</div>';
                    html += '</div></div>';
                }.bind(this));
            }

            html += '</div></div>';

            // Tema de diseño
            if (data.todos_los_temas && Object.keys(data.todos_los_temas).length > 0) {
                var temasRecomendados = data.temas_recomendados || {};
                var todosLosTemas = data.todos_los_temas || {};

                html += '<div class="propuesta-section">';
                html += '<h4><span class="dashicons dashicons-art" style="margin-right:6px;"></span>Tema de Diseño</h4>';
                html += '<p class="section-desc">Selecciona un tema visual para tu sitio:</p>';

                // Temas recomendados
                if (Object.keys(temasRecomendados).length > 0) {
                    html += '<p class="temas-subtitle"><strong>Recomendados para tu tipo de comunidad:</strong></p>';
                    html += '<div class="propuesta-temas">';

                    var isFirst = true;
                    for (var temaId in temasRecomendados) {
                        var tema = temasRecomendados[temaId];
                        var isSelected = (temaId === data.tema_recomendado) || isFirst;
                        html += this.renderTemaCard(temaId, tema, isSelected, true);
                        isFirst = false;
                    }
                    html += '</div>';
                }

                // Otros temas
                var otrosTemas = {};
                for (var temaId in todosLosTemas) {
                    if (!temasRecomendados[temaId]) {
                        otrosTemas[temaId] = todosLosTemas[temaId];
                    }
                }

                if (Object.keys(otrosTemas).length > 0) {
                    html += '<details class="otros-temas-toggle">';
                    html += '<summary>Ver otros temas disponibles (' + Object.keys(otrosTemas).length + ')</summary>';
                    html += '<div class="propuesta-temas otros-temas">';

                    for (var temaId in otrosTemas) {
                        var tema = otrosTemas[temaId];
                        html += this.renderTemaCard(temaId, tema, false, false);
                    }
                    html += '</div></details>';
                }

                html += '</div>';
            }

            // Colores (solo mostrar si no hay temas o como info)
            if (data.colores_sugeridos) {
                html += '<div class="propuesta-section colores-avanzado" style="display:none;">';
                html += '<h4>Colores del Sitio</h4>';
                html += '<div class="propuesta-colores">';

                for (var nombre in data.colores_sugeridos) {
                    var color = data.colores_sugeridos[nombre];
                    html += '<div class="color-item">';
                    html += '<input type="color" value="' + color + '" data-color="' + nombre + '">';
                    html += '<label>' + nombre.charAt(0).toUpperCase() + nombre.slice(1) + '</label>';
                    html += '</div>';
                }

                html += '</div></div>';
            }

            $('#propuesta-content').html(html);

            // Eventos para selección de tema
            $('.tema-card').on('click', function() {
                $('.tema-card').removeClass('selected');
                $(this).addClass('selected');
                $(this).find('input[type="radio"]').prop('checked', true);
            });
        },

        generate: function() {
            var self = this;

            if (!this.propuesta) {
                alert('No hay propuesta para generar');
                return;
            }

            // Recopilar selecciones del usuario
            var propuestaFinal = JSON.parse(JSON.stringify(this.propuesta));

            // Filtrar módulos seleccionados
            var modulosSeleccionados = [];
            $('.modulo-tag input:checked').each(function() {
                modulosSeleccionados.push($(this).data('modulo'));
            });
            propuestaFinal.modulos_recomendados = modulosSeleccionados;

            // Filtrar páginas seleccionadas
            var paginasSeleccionadas = [];
            $('.pagina-item input:checked').each(function() {
                var index = $(this).data('pagina');
                if (self.propuesta.paginas_sugeridas[index]) {
                    paginasSeleccionadas.push(self.propuesta.paginas_sugeridas[index]);
                }
            });
            propuestaFinal.paginas_sugeridas = paginasSeleccionadas;

            // Obtener colores
            var colores = {};
            $('input[data-color]').each(function() {
                colores[$(this).data('color')] = $(this).val();
            });
            propuestaFinal.colores_sugeridos = colores;

            // Obtener tema seleccionado
            var temaSeleccionado = $('input[name="tema_seleccionado"]:checked').val();
            if (temaSeleccionado) {
                propuestaFinal.tema_recomendado = temaSeleccionado;
            }

            this.showLoader(FlavorAppGenerator.strings.generating);

            $.ajax({
                url: FlavorAppGenerator.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'flavor_app_generator_generate',
                    nonce: FlavorAppGenerator.nonce,
                    propuesta: JSON.stringify(propuestaFinal)
                },
                success: function(response) {
                    self.hideLoader();

                    if (response.success) {
                        self.renderResultado(response.data);
                        self.goToStep(3);
                    } else {
                        alert(response.data.message || 'Error en la generación');
                    }
                },
                error: function() {
                    self.hideLoader();
                    alert('Error de conexión');
                }
            });
        },

        renderResultado: function(data) {
            var html = '';

            html += '<div class="resultado-success">';
            html += '<div class="resultado-icon">✓</div>';
            html += '<h3>' + FlavorAppGenerator.strings.success + '</h3>';

            html += '<div class="resultado-stats">';
            html += '<div class="stat-item">';
            html += '<div class="stat-number">' + (data.paginas_creadas ? data.paginas_creadas.length : 0) + '</div>';
            html += '<div class="stat-label">Páginas creadas</div>';
            html += '</div>';
            html += '<div class="stat-item">';
            html += '<div class="stat-number">' + (data.modulos_activados ? data.modulos_activados.length : 0) + '</div>';
            html += '<div class="stat-label">Módulos activados</div>';
            html += '</div>';
            html += '</div>';

            // Configuración aplicada (tema, etc.)
            if (data.configuracion_aplicada && data.configuracion_aplicada.length > 0) {
                html += '<div class="resultado-config" style="margin: 20px 0; padding: 15px; background: #f0f6fc; border-radius: 8px; text-align: left;">';
                html += '<strong style="color: #2271b1;">Configuración aplicada:</strong>';
                html += '<ul style="margin: 8px 0 0 20px; color: #1d2327;">';
                data.configuracion_aplicada.forEach(function(config) {
                    html += '<li>' + config + '</li>';
                });
                html += '</ul></div>';
            }

            // Lista de páginas creadas
            if (data.paginas_creadas && data.paginas_creadas.length > 0) {
                html += '<div class="resultado-links">';
                html += '<h4>Páginas Creadas</h4>';
                html += '<div class="link-list">';

                data.paginas_creadas.forEach(function(pagina) {
                    html += '<div class="link-item">';
                    html += '<span>' + pagina.titulo + '</span>';
                    html += '<div>';
                    html += '<a href="' + pagina.url + '" target="_blank">Ver</a>';
                    html += ' | ';
                    html += '<a href="' + pagina.edit_url + '">Editar</a>';
                    html += '</div>';
                    html += '</div>';
                });

                html += '</div></div>';
            }

            // Errores si los hay
            if (data.errores && data.errores.length > 0) {
                html += '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">';
                html += '<strong>Avisos:</strong><ul style="margin: 10px 0 0 20px;">';
                data.errores.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul></div>';
            }

            html += '</div>';

            $('#resultado-content').html(html);
        },

        reset: function() {
            this.propuesta = null;
            $('#proyecto-descripcion').val('');
            $('#propuesta-content').html('');
            $('#resultado-content').html('');
            this.goToStep(1);
        },

        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        renderTemaCard: function(temaId, tema, isSelected, isRecomendado) {
            var html = '';
            var cardClass = 'tema-card' + (isSelected ? ' selected' : '') + (isRecomendado ? ' recomendado' : '');

            html += '<label class="' + cardClass + '" data-tema="' + temaId + '">';
            html += '<input type="radio" name="tema_seleccionado" value="' + temaId + '"' + (isSelected ? ' checked' : '') + '>';
            html += '<div class="tema-preview" style="background-color: ' + tema.color + ';">';
            html += '<span class="dashicons ' + tema.icon + '"></span>';
            html += '</div>';
            html += '<div class="tema-info">';
            html += '<div class="tema-nombre">' + this.escapeHtml(tema.label) + '</div>';
            html += '<div class="tema-desc">' + this.escapeHtml(tema.desc) + '</div>';
            html += '</div>';
            html += '</label>';

            return html;
        }
    };

    $(document).ready(function() {
        AppGenerator.init();
    });

})(jQuery);
