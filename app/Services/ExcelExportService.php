<?php

namespace App\Services;

class ExcelExportService
{
    public function download(string $filename, array $headers, array $rows, array $meta = []): void
    {
        $safeFilename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename);
        if (!str_ends_with(strtolower($safeFilename), '.xls')) {
            $safeFilename .= '.xls';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<!doctype html><html><head><meta charset="UTF-8">';
        echo '<style>';
        echo 'table{border-collapse:collapse;font-family:Arial,sans-serif;font-size:10pt;}';
        echo 'th{font-weight:bold;background:#d9eaf7;border:1px solid #7f8c8d;padding:6px;}';
        echo 'td{border:1px solid #bdc3c7;padding:5px;mso-number-format:"\@";}';
        echo '.meta td{border:none;font-weight:bold;background:#f6f8fa;}';
        echo '</style></head><body>';

        if (!empty($meta)) {
            echo '<table class="meta">';
            foreach ($meta as $label => $value) {
                echo '<tr><td>' . $this->escape($label) . '</td><td>' . $this->escapeCell($value) . '</td></tr>';
            }
            echo '</table><br>';
        }

        echo '<table><thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . $this->escape($header) . '</th>';
        }
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($headers as $key => $header) {
                $field = is_string($key) ? $key : $header;
                echo '<td>' . $this->escapeCell($row[$field] ?? '') . '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    }

    private function escapeCell(mixed $value): string
    {
        $text = $this->stringValue($value);
        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@'], true)) {
            $text = "'" . $text;
        }
        return $this->escape($text);
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return (string)$value;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
