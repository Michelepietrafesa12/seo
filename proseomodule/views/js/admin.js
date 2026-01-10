/**
 * Pro SEO Module - Admin JavaScript
 *
 * Script per il pannello di amministrazione
 *
 * @author      Pro SEO Team
 * @copyright   2024
 * @license     AFL-3.0
 */

(function($) {
    'use strict';

    // Inizializzazione quando il DOM è pronto
    $(document).ready(function() {
        initProSeoAdmin();
    });

    /**
     * Inizializza le funzionalità admin
     */
    function initProSeoAdmin() {
        initFormValidation();
        initTooltips();
        initAjaxActions();
        initSchemaPreview();
        initProgressBars();
    }

    /**
     * Inizializza la validazione form
     */
    function initFormValidation() {
        // Validazione URL
        $('input[type="url"]').on('blur', function() {
            var $input = $(this);
            var value = $input.val();

            if (value && !isValidUrl(value)) {
                $input.addClass('has-error');
                showNotification('URL non valido: ' + value, 'warning');
            } else {
                $input.removeClass('has-error');
            }
        });

        // Validazione email
        $('input[name="PROSEO_ORGANIZATION_EMAIL"]').on('blur', function() {
            var $input = $(this);
            var value = $input.val();

            if (value && !isValidEmail(value)) {
                $input.addClass('has-error');
                showNotification('Email non valida', 'warning');
            } else {
                $input.removeClass('has-error');
            }
        });

        // Validazione telefono
        $('input[name="PROSEO_ORGANIZATION_PHONE"]').on('blur', function() {
            var $input = $(this);
            var value = $input.val();

            if (value && !isValidPhone(value)) {
                $input.addClass('has-error');
                showNotification('Formato telefono non valido. Usa formato internazionale es: +39 02 1234567', 'warning');
            } else {
                $input.removeClass('has-error');
            }
        });
    }

    /**
     * Inizializza i tooltip
     */
    function initTooltips() {
        $('[data-toggle="tooltip"]').tooltip();

        // Tooltip custom per help
        $('.proseo-help').each(function() {
            var $el = $(this);
            $el.attr('title', $el.data('help'));
        }).tooltip();
    }

    /**
     * Inizializza azioni AJAX
     */
    function initAjaxActions() {
        // Genera sitemap AJAX
        $('#btn-generate-sitemap-ajax').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.html();

            $btn.prop('disabled', true).html('<span class="proseo-loading"></span> Generazione...');

            $.ajax({
                url: proseo_ajax_url,
                type: 'POST',
                data: {
                    ajax: 1,
                    action: 'generateSitemap',
                    token: proseo_token
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Sitemap generata con successo!', 'success');
                        if (response.sitemap_url) {
                            $('#sitemap-url').attr('href', response.sitemap_url).text(response.sitemap_url);
                        }
                    } else {
                        showNotification(response.message || 'Errore durante la generazione', 'error');
                    }
                },
                error: function() {
                    showNotification('Errore di connessione', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Pulisci cache AJAX
        $('#btn-clear-cache-ajax').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalText = $btn.html();

            $btn.prop('disabled', true).html('<span class="proseo-loading"></span> Pulizia...');

            $.ajax({
                url: proseo_ajax_url,
                type: 'POST',
                data: {
                    ajax: 1,
                    action: 'clearCache',
                    token: proseo_token
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Cache pulita con successo!', 'success');
                        $('#cache-count').text('0');
                    } else {
                        showNotification(response.message || 'Errore durante la pulizia', 'error');
                    }
                },
                error: function() {
                    showNotification('Errore di connessione', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    /**
     * Inizializza preview schema
     */
    function initSchemaPreview() {
        $('#btn-preview-schema').on('click', function(e) {
            e.preventDefault();
            var testUrl = $('#test_url').val();

            if (!testUrl) {
                showNotification('Inserisci un URL da testare', 'warning');
                return;
            }

            var $modal = $('#schema-preview-modal');
            var $content = $('#schema-preview-content');

            $content.html('<div class="text-center"><span class="proseo-loading"></span> Caricamento schema...</div>');
            $modal.modal('show');

            $.ajax({
                url: proseo_ajax_url,
                type: 'POST',
                data: {
                    ajax: 1,
                    action: 'previewSchema',
                    url: testUrl,
                    token: proseo_token
                },
                success: function(response) {
                    if (response.success && response.schema) {
                        var formatted = syntaxHighlight(JSON.stringify(response.schema, null, 2));
                        $content.html('<pre class="schema-preview">' + formatted + '</pre>');
                    } else {
                        $content.html('<div class="alert alert-warning">Impossibile recuperare lo schema.</div>');
                    }
                },
                error: function() {
                    $content.html('<div class="alert alert-danger">Errore di connessione.</div>');
                }
            });
        });
    }

    /**
     * Inizializza le progress bar
     */
    function initProgressBars() {
        $('.proseo-progress').each(function() {
            var $bar = $(this).find('.proseo-progress-bar');
            var percentage = parseInt($bar.data('percentage') || 0);

            // Animazione
            setTimeout(function() {
                $bar.css('width', percentage + '%');
            }, 100);

            // Colore in base alla percentuale
            if (percentage >= 80) {
                $bar.addClass('good');
            } else if (percentage >= 50) {
                $bar.addClass('warning');
            } else {
                $bar.addClass('danger');
            }
        });
    }

    /**
     * Mostra notifica
     */
    function showNotification(message, type) {
        type = type || 'info';

        var iconMap = {
            success: 'check-circle',
            error: 'times-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };

        var $notification = $('<div class="alert alert-' + type + ' proseo-notification">' +
            '<i class="icon-' + iconMap[type] + '"></i> ' + message +
            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
            '</div>');

        // Rimuovi notifiche esistenti
        $('.proseo-notification').remove();

        // Aggiungi nuova notifica
        $('.panel:first').before($notification);

        // Auto-rimuovi dopo 5 secondi
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Syntax highlighting per JSON
     */
    function syntaxHighlight(json) {
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function(match) {
            var cls = 'number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'key';
                } else {
                    cls = 'string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'boolean';
            } else if (/null/.test(match)) {
                cls = 'null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    }

    /**
     * Validazione URL
     */
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    /**
     * Validazione email
     */
    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Validazione telefono
     */
    function isValidPhone(phone) {
        // Accetta formati internazionali
        var re = /^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/;
        return re.test(phone.replace(/\s/g, ''));
    }

    /**
     * Copy to clipboard
     */
    window.copyToClipboard = function(text) {
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        showNotification('Copiato negli appunti!', 'success');
    };

})(jQuery);
