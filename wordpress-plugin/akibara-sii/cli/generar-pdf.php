<?php
/**
 * Generador de PDF para Boletas Electronicas - Formato SII Chile
 *
 * Basado en el formato real de e-boleta del SII
 * Estilo voucher/recibo simple
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

// Crear PDF estilo voucher SII
class BoletaPDF extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

// Formato papel: ancho 80mm (voucher termico) o carta
$anchoVoucher = 80; // mm
$pdf = new BoletaPDF('P', 'mm', [$anchoVoucher, 200], true, 'UTF-8');

$pdf->SetCreator('Akibara SII');
$pdf->SetAuthor($razonSocialEmisor);
$pdf->SetTitle("Boleta Electronica N $folio");

$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(true, 5);
$pdf->AddPage();

$pdf->SetTextColor(0, 0, 0);
$y = 8;

// === DATOS DEL EMISOR ===
$pdf->SetFont('courier', 'B', 10);
$pdf->SetXY(5, $y);
$pdf->Cell(70, 5, $razonSocialEmisor, 0, 1, 'L');
$y += 5;

$pdf->SetFont('courier', '', 9);
$pdf->SetXY(5, $y);
$pdf->Cell(70, 4, $rutEmisor, 0, 1, 'L');
$y += 5;

$pdf->SetXY(5, $y);
$pdf->MultiCell(70, 4, 'Giro: ' . $giroEmisor, 0, 'L');
$y = $pdf->GetY() + 1;

$pdf->SetXY(5, $y);
$pdf->Cell(70, 4, $dirEmisor, 0, 1, 'L');
$y += 4;

$pdf->SetXY(5, $y);
$pdf->Cell(70, 4, $cmnaEmisor, 0, 1, 'L');
$y += 6;

// === TIPO DOCUMENTO Y NUMERO ===
$tipoTexto = $tipoDte == 39 ? 'BOLETA ELECTRONICA' : ($tipoDte == 41 ? 'BOLETA EXENTA' : 'DTE ' . $tipoDte);
$pdf->SetFont('courier', 'B', 9);
$pdf->SetXY(5, $y);
$pdf->Cell(70, 5, $tipoTexto . ' NUMERO: ' . $folio, 0, 1, 'L');
$y += 6;

// Fecha
$pdf->SetFont('courier', '', 9);
$pdf->SetXY(5, $y);
$pdf->Cell(70, 4, 'Fecha: ' . $fechaEmision, 0, 1, 'L');
$y += 6;

// === DATOS RECEPTOR ===
if ($rutReceptor && $rutReceptor != '66666666-6') {
    $pdf->SetXY(5, $y);
    $pdf->Cell(70, 4, 'RUT Cliente: ' . $rutReceptor, 0, 1, 'L');
    $y += 4;
}

if ($razonSocialReceptor && $razonSocialReceptor != 'CONSUMIDOR FINAL') {
    $pdf->SetXY(5, $y);
    $pdf->Cell(70, 4, 'Cliente: ' . $razonSocialReceptor, 0, 1, 'L');
    $y += 4;
}

if ($dirReceptor) {
    $pdf->SetXY(5, $y);
    $pdf->Cell(70, 4, 'Direccion: ' . $dirReceptor, 0, 1, 'L');
    $y += 5;
}

// === DETALLE DE ITEMS ===
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line(5, $y, 75, $y);
$y += 2;

foreach ($detalles as $det) {
    $cantidad = $det['cantidad'];
    if ($det['unidad']) {
        $cantidad .= ' ' . $det['unidad'];
    }

    $pdf->SetFont('courier', '', 8);
    $pdf->SetXY(5, $y);

    // Nombre del item
    $itemText = $det['nombre'];
    if ($det['exento']) {
        $itemText .= ' (E)';
    }
    $pdf->Cell(50, 4, $itemText, 0, 0, 'L');
    $y += 4;

    // Cantidad x Precio = Total
    $pdf->SetXY(5, $y);
    $pdf->Cell(25, 4, $cantidad . ' x $' . number_format($det['precio'], 0, ',', '.'), 0, 0, 'L');
    $pdf->Cell(45, 4, '$ ' . number_format($det['monto'], 0, ',', '.'), 0, 1, 'R');
    $y += 5;
}

$pdf->Line(5, $y, 75, $y);
$y += 3;

// === TOTALES ===
$pdf->SetFont('courier', 'B', 10);
$pdf->SetXY(5, $y);
$pdf->Cell(35, 5, 'Venta', 0, 0, 'L');
$pdf->Cell(35, 5, '$ ' . number_format($montoTotal, 0, ',', '.'), 0, 1, 'R');
$y += 7;

// IVA incluido
$pdf->SetFont('courier', '', 8);
$pdf->SetXY(5, $y);
$pdf->MultiCell(70, 4, 'El IVA incluido en esta boleta es de: $ ' . number_format($montoIva, 0, ',', '.'), 0, 'L');
$y = $pdf->GetY() + 5;

// === TIMBRE ELECTRONICO PDF417 ===
if (!empty($tedString)) {
    $style = [
        'border' => false,
        'padding' => 0,
        'fgcolor' => [0, 0, 0],
        'bgcolor' => false,
        'module_width' => 0.25,
        'module_height' => 0.8,
    ];

    try {
        $pdf->write2DBarcode($tedString, 'PDF417', 8, $y, 64, 25, $style);
        $y += 28;
    } catch (Exception $e) {
        $pdf->SetFont('courier', '', 5);
        $pdf->SetXY(5, $y);
        $pdf->MultiCell(70, 3, substr($tedString, 0, 300) . '...', 0, 'L');
        $y = $pdf->GetY() + 3;
    }

    // Leyendas obligatorias
    $pdf->SetFont('courier', '', 8);
    $pdf->SetXY(5, $y);
    $pdf->Cell(70, 4, 'Timbre Electronico SII', 0, 1, 'L');
    $y += 4;

    $pdf->SetXY(5, $y);
    $pdf->Cell(70, 4, 'Res. 0 de 2025', 0, 1, 'L');  // Certificacion usa Res 0
    $y += 4;

    $pdf->SetXY(5, $y);
    $pdf->Cell(70, 4, 'Verifique documento en sii.cl', 0, 1, 'L');
}

// Guardar PDF
$pdf->Output($pdfFile, 'F');

echo "PDF generado: $pdfFile\n";
echo "Formato: Voucher SII e-boleta (80mm)\n";
