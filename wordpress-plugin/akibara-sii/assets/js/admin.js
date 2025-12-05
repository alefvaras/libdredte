/**
 * Akibara SII - Admin JavaScript
 */

(function($) {
    'use strict';

    // Inicializar cuando el DOM este listo
    $(document).ready(function() {
        initTabs();
        initModals();
        initRutFormatter();
    });

    /**
     * Sistema de tabs
     */
    function initTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab') || $(this).attr('href').replace('#tab-', '');

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
        });
    }

    /**
     * Sistema de modales
     */
    function initModals() {
        // Cerrar modal al hacer clic fuera o en X
        $(document).on('click', '.modal-close, .akibara-modal', function(e) {
            if (e.target === this) {
                $(this).closest('.akibara-modal').hide();
            }
        });

        // Cerrar con ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.akibara-modal').hide();
            }
        });
    }

    /**
     * Formatear RUT chileno
     */
    function initRutFormatter() {
        $(document).on('blur', 'input[name*="rut"], #receptor_rut, #emisor_rut', function() {
            var rut = $(this).val();
            rut = formatRut(rut);
            $(this).val(rut);
        });
    }

    /**
     * Formatea un RUT chileno
     */
    function formatRut(rut) {
        if (!rut) return '';

        // Limpiar
        rut = rut.replace(/[^0-9kK]/g, '').toUpperCase();

        if (rut.length < 2) return rut;

        // Separar cuerpo y DV
        var dv = rut.slice(-1);
        var cuerpo = rut.slice(0, -1);

        // Agregar puntos
        cuerpo = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        return cuerpo + '-' + dv;
    }

    /**
     * Validar RUT chileno
     */
    function validarRut(rut) {
        if (!rut) return false;

        rut = rut.replace(/[^0-9kK]/g, '').toUpperCase();

        if (rut.length < 2) return false;

        var dv = rut.slice(-1);
        var cuerpo = rut.slice(0, -1);

        var suma = 0;
        var multiplo = 2;

        for (var i = cuerpo.length - 1; i >= 0; i--) {
            suma += parseInt(cuerpo.charAt(i)) * multiplo;
            multiplo = multiplo < 7 ? multiplo + 1 : 2;
        }

        var dvEsperado = 11 - (suma % 11);
        dvEsperado = dvEsperado == 11 ? '0' : (dvEsperado == 10 ? 'K' : dvEsperado.toString());

        return dv === dvEsperado;
    }

    /**
     * Formatear numero como moneda chilena
     */
    function formatMoney(value) {
        return '$' + parseInt(value).toLocaleString('es-CL');
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // Exponer funciones utiles globalmente
    window.LibreDTE = {
        formatRut: formatRut,
        validarRut: validarRut,
        formatMoney: formatMoney,
        escapeHtml: escapeHtml
    };

})(jQuery);
