<?php
/**
 * Generador de PDF para Boletas Electronicas
 *
 * Uso: php generar-pdf.php <archivo_xml> [archivo_pdf_salida]
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

if ($argc < 2) {
    echo "Uso: php generar-pdf.php <archivo_xml> [archivo_pdf_salida]\n";
    exit(1);
}

$xmlFile = $argv[1];
$pdfFile = $argv[2] ?? str_replace('.xml', '.pdf', $xmlFile);

// Convertir a rutas absolutas
if ($xmlFile[0] !== '/') {
    $xmlFile = realpath($xmlFile);
}
if ($pdfFile[0] !== '/') {
    $pdfDir = dirname(realpath(dirname($pdfFile) ?: '.'));
    $pdfFile = $pdfDir . '/' . basename($pdfFile);
}

if (!file_exists($xmlFile)) {
    echo "Error: Archivo XML no encontrado: $xmlFile\n";
    exit(1);
}

// Cargar y parsear XML
$xml = simplexml_load_file($xmlFile);
$xml->registerXPathNamespace('sii', 'http://www.sii.cl/SiiDte');

// Extraer datos del documento
$documento = $xml->Documento ?? $xml->children('http://www.sii.cl/SiiDte')[0];
$encabezado = $documento->Encabezado;
$idDoc = $encabezado->IdDoc;
$emisor = $encabezado->Emisor;
$receptor = $encabezado->Receptor;
$totales = $encabezado->Totales;

// Datos del documento
$tipoDte = (int)$idDoc->TipoDTE;
$folio = (string)$idDoc->Folio;
$fechaEmision = (string)$idDoc->FchEmis;

// Datos del emisor
$rutEmisor = (string)$emisor->RUTEmisor;
$razonSocialEmisor = (string)($emisor->RznSocEmisor ?? $emisor->RznSoc);
$giroEmisor = (string)($emisor->GiroEmisor ?? $emisor->GiroEmis);
$dirEmisor = (string)$emisor->DirOrigen;
$cmnaEmisor = (string)$emisor->CmnaOrigen;

// Datos del receptor
$rutReceptor = (string)$receptor->RUTRecep;
$razonSocialReceptor = (string)$receptor->RznSocRecep;
$dirReceptor = (string)$receptor->DirRecep;
$cmnaReceptor = (string)$receptor->CmnaRecep;

// Totales
$montoNeto = (int)$totales->MntNeto;
$montoIva = (int)$totales->IVA;
$montoExento = (int)($totales->MntExe ?? 0);
$montoTotal = (int)$totales->MntTotal;

// Detalle de items
$detalles = [];
foreach ($documento->Detalle as $det) {
    $detalles[] = [
        'nro' => (int)$det->NroLinDet,
        'nombre' => (string)$det->NmbItem,
        'cantidad' => (float)$det->QtyItem,
        'precio' => (float)$det->PrcItem,
        'monto' => (float)$det->MontoItem,
        'exento' => isset($det->IndExe) ? (int)$det->IndExe : 0,
        'unidad' => (string)($det->UnmdItem ?? ''),
    ];
}

// TED para codigo de barras PDF417
$ted = $documento->TED;
$tedString = '';
if ($ted) {
    $dom = new DOMDocument();
    $dom->loadXML($ted->asXML());
    $tedString = $dom->saveXML($dom->documentElement);
    $tedString = preg_replace('/>\s+</', '><', $tedString);
}

// Nombre del tipo de documento
$tipoNombre = match($tipoDte) {
    33 => 'FACTURA ELECTRONICA',
    34 => 'FACTURA NO AFECTA O EXENTA ELECTRONICA',
    39 => 'BOLETA ELECTRONICA',
    41 => 'BOLETA EXENTA ELECTRONICA',
    52 => 'GUIA DE DESPACHO ELECTRONICA',
    56 => 'NOTA DE DEBITO ELECTRONICA',
    61 => 'NOTA DE CREDITO ELECTRONICA',
    default => "DOCUMENTO TRIBUTARIO ELECTRONICO ($tipoDte)",
};

// Crear PDF
class BoletaPDF extends TCPDF {
    public $tipoNombre;
    public $folio;
    public $rutEmisor;

    public function Header() {
        // Sin header predeterminado
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Documento Tributario Electronico - Timbre Electronico SII', 0, 0, 'C');
    }
}

$pdf = new BoletaPDF('P', 'mm', 'LETTER', true, 'UTF-8');
$pdf->tipoNombre = $tipoNombre;
$pdf->folio = $folio;
$pdf->rutEmisor = $rutEmisor;

$pdf->SetCreator('Akibara SII - LibreDTE');
$pdf->SetAuthor($razonSocialEmisor);
$pdf->SetTitle("$tipoNombre N° $folio");
$pdf->SetSubject('Documento Tributario Electronico');

$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// Colores
$colorPrimario = [0, 51, 102];
$colorSecundario = [51, 51, 51];
$colorLinea = [200, 200, 200];

// === ENCABEZADO DEL DOCUMENTO ===

// Recuadro del tipo de documento (derecha)
$pdf->SetFillColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
$pdf->SetDrawColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
$pdf->RoundedRect(120, 15, 75, 35, 2, '1111', 'DF');

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetXY(120, 17);
$pdf->Cell(75, 5, 'R.U.T.: ' . $rutEmisor, 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY(120, 24);
$pdf->MultiCell(75, 5, $tipoNombre, 0, 'C');

$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(120, 38);
$pdf->Cell(75, 6, 'N° ' . $folio, 0, 1, 'C');

// Logo/Datos del emisor (izquierda)
$pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(15, 15);
$pdf->Cell(100, 7, $razonSocialEmisor, 0, 1);

$pdf->SetTextColor($colorSecundario[0], $colorSecundario[1], $colorSecundario[2]);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetXY(15, 24);
$pdf->Cell(100, 5, $giroEmisor, 0, 1);
$pdf->SetXY(15, 29);
$pdf->Cell(100, 5, $dirEmisor . ', ' . $cmnaEmisor, 0, 1);

// Fecha de emision
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(15, 40);
$fechaFormateada = date('d/m/Y', strtotime($fechaEmision));
$pdf->Cell(100, 5, 'Fecha Emision: ' . $fechaFormateada, 0, 1);

// Linea separadora
$pdf->SetDrawColor($colorLinea[0], $colorLinea[1], $colorLinea[2]);
$pdf->Line(15, 55, 195, 55);

// === DATOS DEL RECEPTOR ===
$pdf->SetY(60);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
$pdf->Cell(30, 6, 'RECEPTOR:', 0, 0);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor($colorSecundario[0], $colorSecundario[1], $colorSecundario[2]);

$pdf->SetX(45);
$pdf->Cell(0, 6, 'RUT: ' . $rutReceptor . '    |    ' . $razonSocialReceptor, 0, 1);

if ($dirReceptor || $cmnaReceptor) {
    $pdf->SetX(45);
    $pdf->Cell(0, 5, 'Direccion: ' . $dirReceptor . ', ' . $cmnaReceptor, 0, 1);
}

// === DETALLE DE ITEMS ===
$pdf->SetY(80);

// Encabezado de la tabla
$pdf->SetFillColor(240, 240, 240);
$pdf->SetDrawColor($colorLinea[0], $colorLinea[1], $colorLinea[2]);
$pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
$pdf->SetFont('helvetica', 'B', 9);

$colWidths = [12, 80, 20, 25, 15, 28];
$pdf->Cell($colWidths[0], 7, '#', 1, 0, 'C', true);
$pdf->Cell($colWidths[1], 7, 'Descripcion', 1, 0, 'L', true);
$pdf->Cell($colWidths[2], 7, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell($colWidths[3], 7, 'Precio Unit.', 1, 0, 'R', true);
$pdf->Cell($colWidths[4], 7, 'E/A', 1, 0, 'C', true);
$pdf->Cell($colWidths[5], 7, 'Total', 1, 1, 'R', true);

// Filas de detalle
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor($colorSecundario[0], $colorSecundario[1], $colorSecundario[2]);
$pdf->SetFillColor(255, 255, 255);

foreach ($detalles as $det) {
    $cantidad = $det['cantidad'];
    if ($det['unidad']) {
        $cantidad = $det['cantidad'] . ' ' . $det['unidad'];
    }
    $tipoItem = $det['exento'] ? 'E' : 'A';

    $pdf->Cell($colWidths[0], 6, $det['nro'], 1, 0, 'C');
    $pdf->Cell($colWidths[1], 6, $det['nombre'], 1, 0, 'L');
    $pdf->Cell($colWidths[2], 6, $cantidad, 1, 0, 'C');
    $pdf->Cell($colWidths[3], 6, '$' . number_format($det['precio'], 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell($colWidths[4], 6, $tipoItem, 1, 0, 'C');
    $pdf->Cell($colWidths[5], 6, '$' . number_format($det['monto'], 0, ',', '.'), 1, 1, 'R');
}

// === TOTALES ===
$yTotales = $pdf->GetY() + 5;
$xTotales = 135;

$pdf->SetY($yTotales);
$pdf->SetX($xTotales);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor($colorSecundario[0], $colorSecundario[1], $colorSecundario[2]);

// Monto Neto
if ($montoNeto > 0) {
    $pdf->SetX($xTotales);
    $pdf->Cell(30, 6, 'Monto Neto:', 0, 0, 'L');
    $pdf->Cell(30, 6, '$' . number_format($montoNeto, 0, ',', '.'), 0, 1, 'R');
}

// Monto Exento
if ($montoExento > 0) {
    $pdf->SetX($xTotales);
    $pdf->Cell(30, 6, 'Monto Exento:', 0, 0, 'L');
    $pdf->Cell(30, 6, '$' . number_format($montoExento, 0, ',', '.'), 0, 1, 'R');
}

// IVA
if ($montoIva > 0) {
    $pdf->SetX($xTotales);
    $pdf->Cell(30, 6, 'IVA (19%):', 0, 0, 'L');
    $pdf->Cell(30, 6, '$' . number_format($montoIva, 0, ',', '.'), 0, 1, 'R');
}

// Total
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
$pdf->SetX($xTotales);
$pdf->Cell(30, 8, 'TOTAL:', 0, 0, 'L');
$pdf->Cell(30, 8, '$' . number_format($montoTotal, 0, ',', '.'), 0, 1, 'R');

// === TIMBRE ELECTRONICO (PDF417) ===
if (!empty($tedString)) {
    $yTimbre = $pdf->GetY() + 10;

    // Recuadro para el timbre
    $pdf->SetDrawColor($colorLinea[0], $colorLinea[1], $colorLinea[2]);
    $pdf->RoundedRect(15, $yTimbre, 180, 55, 2, '1111', 'D');

    $pdf->SetY($yTimbre + 3);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor($colorPrimario[0], $colorPrimario[1], $colorPrimario[2]);
    $pdf->Cell(0, 4, 'Timbre Electronico SII', 0, 1, 'C');

    // Generar codigo de barras PDF417
    $style = [
        'border' => false,
        'padding' => 0,
        'fgcolor' => [0, 0, 0],
        'bgcolor' => false,
        'module_width' => 0.35,
        'module_height' => 1.5,
    ];

    try {
        $pdf->write2DBarcode($tedString, 'PDF417', 25, $yTimbre + 10, 160, 35, $style);
    } catch (Exception $e) {
        $pdf->SetY($yTimbre + 15);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->MultiCell(170, 4, 'Timbre Electronico: ' . substr($tedString, 0, 200) . '...', 0, 'C');
    }

    $pdf->SetY($yTimbre + 47);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(128, 128, 128);
    $pdf->Cell(0, 4, 'Res. Ex. SII N°0 del ' . $fechaEmision . ' - Verifique documento en www.sii.cl', 0, 1, 'C');
}

// Guardar PDF
$pdf->Output($pdfFile, 'F');

echo "PDF generado: $pdfFile\n";
