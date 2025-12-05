<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_caf = $wpdb->prefix . 'akibara_caf';

// Procesar subida de CAF
if (isset($_POST['akibara_upload_caf']) && wp_verify_nonce($_POST['_wpnonce'], 'akibara_caf')) {
    if (!empty($_FILES['caf_file']['tmp_name'])) {
        $xml_content = file_get_contents($_FILES['caf_file']['tmp_name']);

        // Parsear CAF
        $caf_data = parsear_caf($xml_content);

        if ($caf_data) {
            // Guardar archivo
            $upload_dir = wp_upload_dir();
            $caf_dir = $upload_dir['basedir'] . '/libredte/caf/';
            if (!file_exists($caf_dir)) {
                wp_mkdir_p($caf_dir);
                file_put_contents($caf_dir . '.htaccess', 'deny from all');
            }

            $filename = 'CAF_T' . $caf_data['tipo_dte'] . '_' . $caf_data['folio_desde'] . '-' . $caf_data['folio_hasta'] . '.xml';
            file_put_contents($caf_dir . $filename, $xml_content);

            // Verificar si ya existe
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_caf WHERE tipo_dte = %d AND folio_desde = %d AND folio_hasta = %d",
                $caf_data['tipo_dte'], $caf_data['folio_desde'], $caf_data['folio_hasta']
            ));

            if ($existe) {
                echo '<div class="notice notice-warning"><p>Este CAF ya estaba registrado.</p></div>';
            } else {
                // Insertar en BD
                $wpdb->insert($table_caf, array(
                    'tipo_dte' => $caf_data['tipo_dte'],
                    'folio_desde' => $caf_data['folio_desde'],
                    'folio_hasta' => $caf_data['folio_hasta'],
                    'fecha_autorizacion' => $caf_data['fecha_autorizacion'],
                    'rut_emisor' => $caf_data['rut_emisor'],
                    'xml_caf' => $xml_content,
                    'archivo' => $filename,
                    'estado' => 'activo',
                    'fecha_carga' => current_time('mysql')
                ));

                // Actualizar folio actual si es necesario
                $folio_actual = get_option('akibara_folio_actual_' . $caf_data['tipo_dte'], 0);
                if ($folio_actual < $caf_data['folio_desde']) {
                    update_option('akibara_folio_actual_' . $caf_data['tipo_dte'], $caf_data['folio_desde']);
                }

                echo '<div class="notice notice-success"><p>CAF cargado correctamente. Folios: ' .
                     $caf_data['folio_desde'] . ' - ' . $caf_data['folio_hasta'] . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Error al parsear el archivo CAF. Verifica que sea un XML valido.</p></div>';
        }
    }
}

// Funcion para parsear CAF
function parsear_caf($xml_content) {
    try {
        $xml = new SimpleXMLElement($xml_content);

        // Registrar namespace si existe
        $namespaces = $xml->getNamespaces(true);
        if (isset($namespaces[''])) {
            $xml->registerXPathNamespace('sii', $namespaces['']);
        }

        // Buscar datos
        $da = $xml->xpath('//DA') ?: $xml->xpath('//sii:DA');
        if (empty($da)) {
            return false;
        }

        $da = $da[0];

        return array(
            'tipo_dte' => (int)$da->TD,
            'folio_desde' => (int)$da->RNG->D,
            'folio_hasta' => (int)$da->RNG->H,
            'fecha_autorizacion' => (string)$da->FA,
            'rut_emisor' => (string)$da->RE
        );
    } catch (Exception $e) {
        return false;
    }
}

// Obtener CAFs
$cafs = $wpdb->get_results(
    "SELECT * FROM $table_caf WHERE tipo_dte = 39 ORDER BY folio_desde DESC"
);

// Calcular estadisticas de folios
$folio_actual = get_option('akibara_folio_actual_39', 0);
$total_folios = 0;
$folios_usados = 0;
$folios_disponibles = 0;
$caf_activo = null;

foreach ($cafs as $caf) {
    $rango = $caf->folio_hasta - $caf->folio_desde + 1;
    $total_folios += $rango;

    if ($caf->estado === 'activo' && $folio_actual >= $caf->folio_desde && $folio_actual <= $caf->folio_hasta) {
        $caf_activo = $caf;
        $folios_disponibles = $caf->folio_hasta - $folio_actual + 1;
    }
}

// Contar boletas emitidas
$table_boletas = $wpdb->prefix . 'akibara_boletas';
$folios_usados = $wpdb->get_var("SELECT COUNT(*) FROM $table_boletas WHERE tipo_dte = 39");

// Alerta de folios bajos
$alerta_folios = ($folios_disponibles > 0 && $folios_disponibles < 50);
?>

<div class="wrap libredte-caf">
    <h1>Gestion de CAF (Codigo de Autorizacion de Folios)</h1>

    <!-- Resumen de Folios -->
    <div class="folios-resumen">
        <div class="folio-card <?php echo $alerta_folios ? 'alerta' : ''; ?>">
            <span class="folio-icon"><span class="dashicons dashicons-tickets-alt"></span></span>
            <div class="folio-info">
                <span class="folio-numero"><?php echo number_format($folios_disponibles); ?></span>
                <span class="folio-label">Folios Disponibles</span>
            </div>
            <?php if ($alerta_folios): ?>
            <span class="alerta-texto">Quedan pocos folios!</span>
            <?php endif; ?>
        </div>

        <div class="folio-card">
            <span class="folio-icon"><span class="dashicons dashicons-chart-bar"></span></span>
            <div class="folio-info">
                <span class="folio-numero"><?php echo number_format($folios_usados); ?></span>
                <span class="folio-label">Folios Usados</span>
            </div>
        </div>

        <div class="folio-card">
            <span class="folio-icon"><span class="dashicons dashicons-database"></span></span>
            <div class="folio-info">
                <span class="folio-numero"><?php echo number_format($total_folios); ?></span>
                <span class="folio-label">Total Autorizados</span>
            </div>
        </div>

        <?php if ($caf_activo): ?>
        <div class="folio-card activo">
            <span class="folio-icon"><span class="dashicons dashicons-yes-alt"></span></span>
            <div class="folio-info">
                <span class="folio-numero"><?php echo $folio_actual; ?></span>
                <span class="folio-label">Proximo Folio</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Barra de progreso -->
    <?php if ($caf_activo): ?>
    <div class="card progreso-card">
        <h2>CAF Activo: <?php echo $caf_activo->folio_desde; ?> - <?php echo $caf_activo->folio_hasta; ?></h2>
        <?php
        $rango_total = $caf_activo->folio_hasta - $caf_activo->folio_desde + 1;
        $usados_en_caf = $folio_actual - $caf_activo->folio_desde;
        $porcentaje = round(($usados_en_caf / $rango_total) * 100, 1);
        ?>
        <div class="progreso-bar">
            <div class="progreso-fill <?php echo $porcentaje > 80 ? 'danger' : ($porcentaje > 50 ? 'warning' : ''); ?>"
                 style="width: <?php echo $porcentaje; ?>%"></div>
        </div>
        <div class="progreso-labels">
            <span>Inicio: <?php echo $caf_activo->folio_desde; ?></span>
            <span class="center">Usado: <?php echo $porcentaje; ?>% (<?php echo $usados_en_caf; ?> de <?php echo $rango_total; ?>)</span>
            <span>Fin: <?php echo $caf_activo->folio_hasta; ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Subir nuevo CAF -->
    <div class="card">
        <h2>Subir Nuevo CAF</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('akibara_caf'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="caf_file">Archivo CAF (.xml)</label></th>
                    <td>
                        <input type="file" id="caf_file" name="caf_file" accept=".xml" required>
                        <p class="description">
                            Sube el archivo XML del CAF obtenido desde el SII.
                            <br>El CAF debe ser para Tipo DTE 39 (Boleta Electronica).
                        </p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" name="akibara_upload_caf" class="button button-primary">
                    <span class="dashicons dashicons-upload"></span> Subir CAF
                </button>
            </p>
        </form>
    </div>

    <!-- Lista de CAFs -->
    <div class="card">
        <h2>CAFs Registrados</h2>
        <?php if (empty($cafs)): ?>
        <p>No hay CAFs registrados. Sube tu primer CAF para comenzar a emitir boletas.</p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Tipo DTE</th>
                    <th>Rango Folios</th>
                    <th>Cantidad</th>
                    <th>Disponibles</th>
                    <th>Fecha Autorizacion</th>
                    <th>RUT Emisor</th>
                    <th>Estado</th>
                    <th>Fecha Carga</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cafs as $caf):
                    $rango = $caf->folio_hasta - $caf->folio_desde + 1;
                    $disponibles_caf = 0;
                    if ($folio_actual >= $caf->folio_desde && $folio_actual <= $caf->folio_hasta) {
                        $disponibles_caf = $caf->folio_hasta - $folio_actual + 1;
                    } elseif ($folio_actual < $caf->folio_desde) {
                        $disponibles_caf = $rango;
                    }
                ?>
                <tr>
                    <td>39 - Boleta</td>
                    <td><strong><?php echo $caf->folio_desde; ?> - <?php echo $caf->folio_hasta; ?></strong></td>
                    <td><?php echo number_format($rango); ?></td>
                    <td>
                        <?php if ($disponibles_caf > 0): ?>
                        <span class="disponibles <?php echo $disponibles_caf < 20 ? 'bajo' : ''; ?>">
                            <?php echo number_format($disponibles_caf); ?>
                        </span>
                        <?php else: ?>
                        <span class="agotado">Agotado</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $caf->fecha_autorizacion; ?></td>
                    <td><?php echo $caf->rut_emisor; ?></td>
                    <td>
                        <span class="estado-caf estado-<?php echo $caf->estado; ?>">
                            <?php echo ucfirst($caf->estado); ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($caf->fecha_carga)); ?></td>
                    <td>
                        <button type="button" class="button button-small btn-ver-caf" data-id="<?php echo $caf->id; ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button type="button" class="button button-small btn-descargar-caf" data-id="<?php echo $caf->id; ?>">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Ayuda -->
    <div class="card">
        <h2>Como obtener un CAF?</h2>
        <ol>
            <li>Ingresa al portal del SII con tu certificado digital</li>
            <li>Ve a <strong>Factura Electronica > Administracion de Folios</strong></li>
            <li>Selecciona <strong>Solicitar Timbraje Electronico</strong></li>
            <li>Elige <strong>Tipo DTE 39 - Boleta Electronica</strong></li>
            <li>Indica la cantidad de folios que necesitas</li>
            <li>Descarga el archivo XML generado</li>
            <li>Subelo aqui usando el formulario de arriba</li>
        </ol>
    </div>
</div>

<style>
.folios-resumen {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.folio-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 200px;
    position: relative;
}
.folio-card.alerta {
    border: 2px solid #d63638;
    background: #fff5f5;
}
.folio-card.activo {
    border: 2px solid #00a32a;
    background: #f0fdf4;
}
.folio-icon {
    font-size: 30px;
    color: #2271b1;
}
.folio-card.alerta .folio-icon { color: #d63638; }
.folio-card.activo .folio-icon { color: #00a32a; }
.folio-numero {
    display: block;
    font-size: 28px;
    font-weight: bold;
    color: #1e293b;
}
.folio-label {
    color: #64748b;
    font-size: 13px;
}
.alerta-texto {
    position: absolute;
    top: 5px;
    right: 10px;
    color: #d63638;
    font-size: 11px;
    font-weight: 500;
}

.progreso-card { text-align: center; }
.progreso-bar {
    height: 24px;
    background: #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    margin: 15px 0;
}
.progreso-fill {
    height: 100%;
    background: #00a32a;
    transition: width 0.3s;
}
.progreso-fill.warning { background: #dba617; }
.progreso-fill.danger { background: #d63638; }
.progreso-labels {
    display: flex;
    justify-content: space-between;
    color: #64748b;
    font-size: 13px;
}
.progreso-labels .center { font-weight: 500; color: #1e293b; }

.disponibles {
    display: inline-block;
    padding: 3px 8px;
    background: #d1fae5;
    color: #065f46;
    border-radius: 4px;
    font-weight: 500;
}
.disponibles.bajo {
    background: #fee2e2;
    color: #991b1b;
}
.agotado {
    color: #9ca3af;
    font-style: italic;
}
.estado-caf {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}
.estado-activo { background: #d1fae5; color: #065f46; }
.estado-inactivo { background: #e5e7eb; color: #374151; }
.estado-agotado { background: #fee2e2; color: #991b1b; }
</style>
