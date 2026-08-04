<?php

namespace Clinique\Services;

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Service d'export PDF / Excel – utilise dompdf & phpspreadsheet (déjà dans composer.json)
 */
class ExportService
{
    /**
     * Génère un PDF à partir de HTML
     */
    public static function exportPdf(string $html, string $filename = 'export.pdf'): void
    {
        if (!class_exists(Dompdf::class)) {
            throw new \RuntimeException("dompdf non installé. composer install");
        }

        $dompdf = new Dompdf([
            'defaultFont' => 'Helvetica',
            'isRemoteEnabled' => false, // sécurité: pas de fetch externe
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
    }

    /**
     * Exporte des données en Excel
     */
    public static function exportExcel(array $data, array $headers, string $filename = 'export.xlsx'): void
    {
        if (!class_exists(Spreadsheet::class)) {
            throw new \RuntimeException("phpspreadsheet non installé");
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        // Data
        $row = 2;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($rowData as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size
        foreach (range('A', $col) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Template HTML pour registre patientes PDF
     */
    public static function patientesPdfTemplate(array $patientes): string
    {
        $rows = '';
        foreach ($patientes as $p) {
            $rows .= sprintf(
                "<tr><td>%s</td><td>%s %s</td><td>%s</td><td>%s</td></tr>",
                htmlspecialchars($p['dossier_number'] ?? ''),
                htmlspecialchars($p['nom'] ?? ''),
                htmlspecialchars($p['prenom'] ?? ''),
                htmlspecialchars($p['telephone'] ?? ''),
                htmlspecialchars($p['groupe_sanguin'] ?? '')
            );
        }

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
body{font-family:Helvetica,Arial,sans-serif;font-size:12px} 
table{width:100%;border-collapse:collapse} th,td{border:1px solid #ddd;padding:8px;text-align:left} th{background:#8B5CF6;color:white}
h1{color:#8B5CF6}
</style></head><body>
<h1>Registre Patientes – Clinique Obstétrique</h1>
<p>Généré le {date('d/m/Y H:i')}</p>
<table><thead><tr><th>Dossier</th><th>Nom</th><th>Téléphone</th><th>GS</th></tr></thead><tbody>{$rows}</tbody></table>
</body></html>
HTML;
    }
}
