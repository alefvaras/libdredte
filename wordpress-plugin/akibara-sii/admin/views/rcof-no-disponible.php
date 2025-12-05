<?php
/**
 * Vista: RCOF no disponible (solo producción)
 */
defined('ABSPATH') || exit;
?>
<div class="wrap">
    <h1><?php _e('RCOF - Reporte de Consumo de Folios', 'akibara-sii'); ?></h1>

    <div class="notice notice-warning">
        <p>
            <strong><?php _e('RCOF solo disponible en producción', 'akibara-sii'); ?></strong>
        </p>
        <p>
            <?php _e('El Reporte de Consumo de Folios (RCOF) solo se puede enviar en ambiente de producción.', 'akibara-sii'); ?>
        </p>
        <p>
            <?php _e('Actualmente está en ambiente de certificación.', 'akibara-sii'); ?>
        </p>
    </div>

    <p>
        <?php printf(
            __('Para cambiar al ambiente de producción, vaya a %s.', 'akibara-sii'),
            '<a href="' . admin_url('admin.php?page=akibara-configuracion') . '">' . __('Configuración', 'akibara-sii') . '</a>'
        ); ?>
    </p>
</div>
