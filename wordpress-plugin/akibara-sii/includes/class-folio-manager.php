<?php
/**
 * Gestor de Folios - Akibara SII
 *
 * Maneja la asignacion automatica de folios verificando:
 * - Folios disponibles en el CAF
 * - Folios ya utilizados (consultando al SII o registro local)
 * - Siguiente folio disponible
 * - Soporte para ambientes: certificacion y produccion
 */

defined('ABSPATH') || define('ABSPATH', dirname(__DIR__) . '/');

class Akibara_Folio_Manager {

    // Constantes de ambiente
    const AMBIENTE_CERTIFICACION = 'certificacion';
    const AMBIENTE_PRODUCCION = 'produccion';

    // URLs de ambientes SII
    const SII_URLS = [
        self::AMBIENTE_CERTIFICACION => 'maullin.sii.cl',
        self::AMBIENTE_PRODUCCION => 'palena.sii.cl',
    ];

    private $cafPath;
    private $folioRegistryPath;
    private $caf;
    private $folioDesde;
    private $folioHasta;
    private $usedFolios = [];
    private $reservedFolios = [];  // Folios reservados temporalmente
    private $ambiente;
    private $tipoDte;

    /**
     * Constructor
     *
     * @param string $cafPath Ruta al archivo CAF
     * @param string $ambiente Ambiente: 'certificacion' o 'produccion'
     * @param string $registryPath Ruta opcional al archivo de registro
     */
    public function __construct(string $cafPath, string $ambiente = self::AMBIENTE_CERTIFICACION, string $registryPath = null) {
        $this->cafPath = $cafPath;
        $this->ambiente = $this->validateAmbiente($ambiente);
        $this->loadCaf();

        // Generar path del registro basado en ambiente y tipo DTE
        if ($registryPath === null) {
            $baseDir = dirname(__DIR__) . '/uploads/';
            $this->folioRegistryPath = $baseDir . "folio_registry_{$this->ambiente}_dte{$this->tipoDte}.json";
        } else {
            $this->folioRegistryPath = $registryPath;
        }

        $this->loadRegistry();
    }

    /**
     * Validar ambiente
     */
    private function validateAmbiente(string $ambiente): string {
        $ambiente = strtolower(trim($ambiente));
        if (!in_array($ambiente, [self::AMBIENTE_CERTIFICACION, self::AMBIENTE_PRODUCCION])) {
            throw new Exception("Ambiente invalido: '$ambiente'. Use 'certificacion' o 'produccion'.");
        }
        return $ambiente;
    }

    /**
     * Obtener ambiente actual
     */
    public function getAmbiente(): string {
        return $this->ambiente;
    }

    /**
     * Obtener nombre descriptivo del ambiente
     */
    public function getAmbienteNombre(): string {
        return $this->ambiente === self::AMBIENTE_CERTIFICACION ? 'Certificación' : 'Producción';
    }

    /**
     * Obtener URL del SII segun ambiente
     */
    public function getSiiUrl(): string {
        return self::SII_URLS[$this->ambiente];
    }

    /**
     * Verificar si es ambiente de certificacion
     */
    public function isCertificacion(): bool {
        return $this->ambiente === self::AMBIENTE_CERTIFICACION;
    }

    /**
     * Verificar si es ambiente de produccion
     */
    public function isProduccion(): bool {
        return $this->ambiente === self::AMBIENTE_PRODUCCION;
    }

    /**
     * Obtener tipo de DTE del CAF
     */
    public function getTipoDte(): int {
        return $this->tipoDte;
    }

    /**
     * Cargar informacion del CAF
     */
    private function loadCaf(): void {
        if (!file_exists($this->cafPath)) {
            throw new Exception("CAF no encontrado: {$this->cafPath}");
        }

        $xml = simplexml_load_file($this->cafPath);
        if (!$xml) {
            throw new Exception("Error al parsear CAF");
        }

        $this->folioDesde = (int) $xml->CAF->DA->RNG->D;
        $this->folioHasta = (int) $xml->CAF->DA->RNG->H;
        $this->tipoDte = (int) $xml->CAF->DA->TD;
    }

    /**
     * Cargar registro de folios usados y reservados
     */
    private function loadRegistry(): void {
        if (file_exists($this->folioRegistryPath)) {
            $data = json_decode(file_get_contents($this->folioRegistryPath), true);
            $this->usedFolios = $data['used_folios'] ?? [];
            $this->reservedFolios = $data['reserved_folios'] ?? [];

            // Limpiar reservas expiradas (más de 1 hora)
            $this->cleanExpiredReservations();
        }
    }

    /**
     * Limpiar reservas expiradas (más de 1 hora sin confirmar)
     */
    private function cleanExpiredReservations(): void {
        $now = time();
        $expireTime = 3600; // 1 hora
        $cleaned = false;

        foreach ($this->reservedFolios as $folio => $timestamp) {
            if (($now - $timestamp) > $expireTime) {
                unset($this->reservedFolios[$folio]);
                $cleaned = true;
            }
        }

        if ($cleaned) {
            $this->saveRegistry();
        }
    }

    /**
     * Guardar registro de folios usados y reservados
     */
    private function saveRegistry(): void {
        $dir = dirname($this->folioRegistryPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [
            'ambiente' => $this->ambiente,
            'ambiente_nombre' => $this->getAmbienteNombre(),
            'sii_url' => $this->getSiiUrl(),
            'tipo_dte' => $this->tipoDte,
            'caf_path' => $this->cafPath,
            'folio_desde' => $this->folioDesde,
            'folio_hasta' => $this->folioHasta,
            'used_folios' => $this->usedFolios,
            'reserved_folios' => $this->reservedFolios,
            'last_updated' => date('Y-m-d H:i:s'),
        ];

        file_put_contents($this->folioRegistryPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Obtener el siguiente folio disponible (no usado ni reservado)
     */
    public function getNextFolio(): int {
        for ($folio = $this->folioDesde; $folio <= $this->folioHasta; $folio++) {
            if (!in_array($folio, $this->usedFolios) && !isset($this->reservedFolios[$folio])) {
                return $folio;
            }
        }

        throw new Exception("No hay folios disponibles en el CAF (rango: {$this->folioDesde}-{$this->folioHasta})");
    }

    /**
     * Reservar un folio temporalmente (no lo marca como usado hasta confirmar)
     *
     * @return int El folio reservado
     */
    public function reserveFolio(): int {
        $folio = $this->getNextFolio();
        $this->reservedFolios[$folio] = time();
        $this->saveRegistry();
        return $folio;
    }

    /**
     * Confirmar un folio reservado (marcarlo como usado definitivamente)
     * Usar cuando el SII acepta el documento
     *
     * @param int $folio El folio a confirmar
     * @return bool true si se confirmó, false si no estaba reservado
     */
    public function confirmFolio(int $folio): bool {
        if (isset($this->reservedFolios[$folio])) {
            unset($this->reservedFolios[$folio]);
            $this->markAsUsed($folio);
            return true;
        }
        // Si no está reservado pero tampoco usado, marcarlo como usado
        if (!in_array($folio, $this->usedFolios)) {
            $this->markAsUsed($folio);
            return true;
        }
        return false;
    }

    /**
     * Liberar un folio reservado (no fue enviado o hubo error antes del envío)
     * IMPORTANTE: Solo liberar si el documento NO fue enviado al SII
     *
     * @param int $folio El folio a liberar
     * @return bool true si se liberó, false si no estaba reservado
     */
    public function releaseFolio(int $folio): bool {
        if (isset($this->reservedFolios[$folio])) {
            unset($this->reservedFolios[$folio]);
            $this->saveRegistry();
            return true;
        }
        return false;
    }

    /**
     * Verificar si un folio está reservado
     */
    public function isReserved(int $folio): bool {
        return isset($this->reservedFolios[$folio]);
    }

    /**
     * Obtener todos los folios reservados
     */
    public function getReservedFolios(): array {
        return $this->reservedFolios;
    }

    /**
     * Obtener N folios consecutivos disponibles (no usados ni reservados)
     */
    public function getNextFolios(int $count): array {
        $folios = [];
        $currentFolio = $this->folioDesde;

        while (count($folios) < $count && $currentFolio <= $this->folioHasta) {
            if (!in_array($currentFolio, $this->usedFolios) && !isset($this->reservedFolios[$currentFolio])) {
                $folios[] = $currentFolio;
            }
            $currentFolio++;
        }

        if (count($folios) < $count) {
            throw new Exception("No hay suficientes folios disponibles. Solicitados: $count, Disponibles: " . count($folios));
        }

        return $folios;
    }

    /**
     * Marcar folio como usado
     */
    public function markAsUsed(int $folio): void {
        if (!in_array($folio, $this->usedFolios)) {
            $this->usedFolios[] = $folio;
            sort($this->usedFolios);
            $this->saveRegistry();
        }
    }

    /**
     * Marcar multiples folios como usados
     */
    public function markMultipleAsUsed(array $folios): void {
        foreach ($folios as $folio) {
            if (!in_array($folio, $this->usedFolios)) {
                $this->usedFolios[] = (int) $folio;
            }
        }
        sort($this->usedFolios);
        $this->saveRegistry();
    }

    /**
     * Verificar si un folio esta disponible (no usado ni reservado)
     */
    public function isAvailable(int $folio): bool {
        if ($folio < $this->folioDesde || $folio > $this->folioHasta) {
            return false;
        }
        return !in_array($folio, $this->usedFolios) && !isset($this->reservedFolios[$folio]);
    }

    /**
     * Obtener estadisticas de folios
     */
    public function getStats(): array {
        $totalFolios = $this->folioHasta - $this->folioDesde + 1;
        $usedCount = count($this->usedFolios);
        $reservedCount = count($this->reservedFolios);
        $availableCount = $totalFolios - $usedCount - $reservedCount;

        return [
            'ambiente' => $this->ambiente,
            'ambiente_nombre' => $this->getAmbienteNombre(),
            'sii_url' => $this->getSiiUrl(),
            'tipo_dte' => $this->tipoDte,
            'caf_desde' => $this->folioDesde,
            'caf_hasta' => $this->folioHasta,
            'total' => $totalFolios,
            'used' => $usedCount,
            'reserved' => $reservedCount,
            'available' => $availableCount,
            'used_folios' => $this->usedFolios,
            'reserved_folios' => array_keys($this->reservedFolios),
            'next_available' => $availableCount > 0 ? $this->getNextFolio() : null,
        ];
    }

    /**
     * Sincronizar con folios usados del SII (verificacion online)
     */
    public function syncWithSii(callable $siiChecker): array {
        $synced = [];
        $errors = [];

        for ($folio = $this->folioDesde; $folio <= $this->folioHasta; $folio++) {
            if (in_array($folio, $this->usedFolios)) {
                continue; // Ya marcado como usado
            }

            try {
                $status = $siiChecker($folio);
                if ($status === 'used' || $status === 'accepted' || $status === 'rejected') {
                    $this->markAsUsed($folio);
                    $synced[] = $folio;
                }
            } catch (Exception $e) {
                $errors[] = ['folio' => $folio, 'error' => $e->getMessage()];
            }
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
            'stats' => $this->getStats(),
        ];
    }

    /**
     * Importar folios usados desde directorio de salida
     */
    public function importFromOutputDir(string $outputDir): array {
        $imported = [];

        if (!is_dir($outputDir)) {
            return $imported;
        }

        // Buscar XMLs con patron de folio
        $patterns = [
            '/F(\d+)\.xml$/',           // archivo_F2039.xml
            '/_F(\d+)_/',               // archivo_F2039_fecha.xml
            '/Folio[_-]?(\d+)/i',       // Folio2039 o Folio_2039
        ];

        $files = glob($outputDir . '/*.xml');
        foreach ($files as $file) {
            $filename = basename($file);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $filename, $matches)) {
                    $folio = (int) $matches[1];
                    if ($folio >= $this->folioDesde && $folio <= $this->folioHasta) {
                        if (!in_array($folio, $this->usedFolios)) {
                            $this->usedFolios[] = $folio;
                            $imported[] = $folio;
                        }
                    }
                    break;
                }
            }
        }

        // Tambien buscar dentro de los XMLs
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (preg_match('/<Folio>(\d+)<\/Folio>/', $content, $matches)) {
                $folio = (int) $matches[1];
                if ($folio >= $this->folioDesde && $folio <= $this->folioHasta) {
                    if (!in_array($folio, $this->usedFolios)) {
                        $this->usedFolios[] = $folio;
                        $imported[] = $folio;
                    }
                }
            }
        }

        if (!empty($imported)) {
            sort($this->usedFolios);
            $this->saveRegistry();
        }

        return array_unique($imported);
    }

    /**
     * Resetear registro (cuidado: solo para desarrollo)
     */
    public function reset(): void {
        $this->usedFolios = [];
        $this->saveRegistry();
    }

    /**
     * Obtener rango del CAF
     */
    public function getCafRange(): array {
        return [
            'desde' => $this->folioDesde,
            'hasta' => $this->folioHasta,
        ];
    }
}
