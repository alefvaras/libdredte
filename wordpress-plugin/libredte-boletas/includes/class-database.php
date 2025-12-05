<?php
/**
 * Clase para gestión de base de datos
 */

defined('ABSPATH') || exit;

class LibreDTE_Database {

    /**
     * Crear tablas del plugin
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de boletas emitidas
        $table_boletas = $wpdb->prefix . 'libredte_boletas';
        $sql_boletas = "CREATE TABLE $table_boletas (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            folio int(11) NOT NULL,
            tipo_dte int(11) NOT NULL DEFAULT 39,
            fecha_emision date NOT NULL,
            rut_receptor varchar(12) NOT NULL,
            razon_social_receptor varchar(100) DEFAULT 'CLIENTE',
            monto_neto int(11) DEFAULT 0,
            monto_iva int(11) DEFAULT 0,
            monto_exento int(11) DEFAULT 0,
            monto_total int(11) NOT NULL,
            estado varchar(20) NOT NULL DEFAULT 'generado',
            track_id varchar(50) DEFAULT NULL,
            ambiente varchar(20) NOT NULL DEFAULT 'certificacion',
            xml_documento longtext,
            xml_sobre longtext,
            respuesta_sii text,
            enviado_sii tinyint(1) DEFAULT 0,
            fecha_envio datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY folio_ambiente (folio, tipo_dte, ambiente),
            KEY estado (estado),
            KEY fecha_emision (fecha_emision)
        ) $charset_collate;";

        // Tabla de CAF (folios)
        $table_caf = $wpdb->prefix . 'libredte_caf';
        $sql_caf = "CREATE TABLE $table_caf (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tipo_dte int(11) NOT NULL DEFAULT 39,
            folio_desde int(11) NOT NULL,
            folio_hasta int(11) NOT NULL,
            folio_actual int(11) NOT NULL,
            fecha_vencimiento date DEFAULT NULL,
            archivo varchar(255) NOT NULL,
            ambiente varchar(20) NOT NULL DEFAULT 'certificacion',
            activo tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo_ambiente (tipo_dte, ambiente, activo)
        ) $charset_collate;";

        // Tabla de RCOF enviados
        $table_rcof = $wpdb->prefix . 'libredte_rcof';
        $sql_rcof = "CREATE TABLE $table_rcof (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            fecha date NOT NULL,
            sec_envio int(11) NOT NULL DEFAULT 1,
            cantidad_boletas int(11) NOT NULL DEFAULT 0,
            monto_neto int(11) DEFAULT 0,
            monto_iva int(11) DEFAULT 0,
            monto_exento int(11) DEFAULT 0,
            monto_total int(11) DEFAULT 0,
            folios_emitidos int(11) DEFAULT 0,
            rango_inicial int(11) DEFAULT NULL,
            rango_final int(11) DEFAULT NULL,
            track_id varchar(50) DEFAULT NULL,
            estado varchar(20) NOT NULL DEFAULT 'pendiente',
            ambiente varchar(20) NOT NULL DEFAULT 'produccion',
            xml_rcof longtext,
            respuesta_sii text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY fecha_ambiente (fecha, ambiente, sec_envio)
        ) $charset_collate;";

        // Tabla de log de operaciones
        $table_log = $wpdb->prefix . 'libredte_log';
        $sql_log = "CREATE TABLE $table_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tipo varchar(50) NOT NULL,
            mensaje text NOT NULL,
            datos longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_boletas);
        dbDelta($sql_caf);
        dbDelta($sql_rcof);
        dbDelta($sql_log);
    }

    /**
     * Guardar boleta
     */
    public static function save_boleta($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_boletas';

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    /**
     * Actualizar boleta
     */
    public static function update_boleta($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_boletas';

        return $wpdb->update($table, $data, ['id' => $id]);
    }

    /**
     * Obtener boleta por ID
     */
    public static function get_boleta($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_boletas';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    /**
     * Obtener boletas por fecha
     */
    public static function get_boletas_by_date($fecha, $ambiente = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_boletas';

        $sql = "SELECT * FROM $table WHERE fecha_emision = %s";
        $params = [$fecha];

        if ($ambiente) {
            $sql .= " AND ambiente = %s";
            $params[] = $ambiente;
        }

        $sql .= " ORDER BY folio ASC";

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    /**
     * Obtener boletas pendientes de envío
     */
    public static function get_boletas_pendientes($ambiente = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_boletas';

        $sql = "SELECT * FROM $table WHERE enviado_sii = 0";

        if ($ambiente) {
            $sql .= $wpdb->prepare(" AND ambiente = %s", $ambiente);
        }

        $sql .= " ORDER BY fecha_emision ASC, folio ASC";

        return $wpdb->get_results($sql);
    }

    /**
     * Guardar CAF
     */
    public static function save_caf($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_caf';

        // Desactivar CAFs anteriores del mismo tipo y ambiente
        $wpdb->update(
            $table,
            ['activo' => 0],
            [
                'tipo_dte' => $data['tipo_dte'],
                'ambiente' => $data['ambiente'],
            ]
        );

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtener CAF activo
     */
    public static function get_caf_activo($tipo_dte = 39, $ambiente = 'certificacion') {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_caf';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE tipo_dte = %d AND ambiente = %s AND activo = 1",
            $tipo_dte,
            $ambiente
        ));
    }

    /**
     * Obtener siguiente folio disponible
     */
    public static function get_siguiente_folio($tipo_dte = 39, $ambiente = 'certificacion') {
        $caf = self::get_caf_activo($tipo_dte, $ambiente);

        if (!$caf) {
            return new WP_Error('no_caf', 'No hay CAF activo disponible');
        }

        if ($caf->folio_actual > $caf->folio_hasta) {
            return new WP_Error('sin_folios', 'Se agotaron los folios del CAF');
        }

        return $caf->folio_actual;
    }

    /**
     * Incrementar folio actual del CAF
     */
    public static function incrementar_folio($tipo_dte = 39, $ambiente = 'certificacion') {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_caf';

        return $wpdb->query($wpdb->prepare(
            "UPDATE $table SET folio_actual = folio_actual + 1
             WHERE tipo_dte = %d AND ambiente = %s AND activo = 1",
            $tipo_dte,
            $ambiente
        ));
    }

    /**
     * Guardar RCOF
     */
    public static function save_rcof($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_rcof';

        $result = $wpdb->insert($table, $data);

        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtener RCOF por fecha
     */
    public static function get_rcof_by_date($fecha, $ambiente = 'produccion') {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_rcof';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE fecha = %s AND ambiente = %s ORDER BY sec_envio DESC LIMIT 1",
            $fecha,
            $ambiente
        ));
    }

    /**
     * Guardar log
     */
    public static function log($tipo, $mensaje, $datos = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_log';

        $wpdb->insert($table, [
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'datos' => $datos ? json_encode($datos) : null,
        ]);
    }

    /**
     * Obtener historial de boletas
     */
    public static function get_historial($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'libredte_boletas';

        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'ambiente' => null,
            'fecha_desde' => null,
            'fecha_hasta' => null,
            'estado' => null,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $params = [];

        if ($args['ambiente']) {
            $where[] = 'ambiente = %s';
            $params[] = $args['ambiente'];
        }

        if ($args['fecha_desde']) {
            $where[] = 'fecha_emision >= %s';
            $params[] = $args['fecha_desde'];
        }

        if ($args['fecha_hasta']) {
            $where[] = 'fecha_emision <= %s';
            $params[] = $args['fecha_hasta'];
        }

        if ($args['estado']) {
            $where[] = 'estado = %s';
            $params[] = $args['estado'];
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($sql, $params));

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";
        array_pop($params);
        array_pop($params);
        $total = $wpdb->get_var($wpdb->prepare($count_sql, $params));

        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
        ];
    }
}
