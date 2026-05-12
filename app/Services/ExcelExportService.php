<?php

namespace App\Services;

class ExcelExportService
{
    public function download(string $filename, array $headers, array $rows, array $meta = []): void
    {
        $safeFilename = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $filename) ?: 'export.xlsx';
        if (!str_ends_with(strtolower($safeFilename), '.xlsx')) {
            $safeFilename = preg_replace('/\.[^.]+$/', '', $safeFilename) . '.xlsx';
        }

        $xlsx = $this->buildWorkbook($headers, $rows, $meta);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($xlsx));

        echo $xlsx;
        exit;
    }

    public function buildWorkbook(array $headers, array $rows, array $meta = []): string
    {
        $files = [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'docProps/app.xml' => $this->appXml(),
            'docProps/core.xml' => $this->coreXml(),
            'xl/workbook.xml' => $this->workbookXml(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelsXml(),
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $this->sheetXml($headers, $rows, $meta),
        ];

        return $this->zipStore($files);
    }

    private function sheetXml(array $headers, array $rows, array $meta): string
    {
        $sheetRows = [];
        $rowIndex = 1;
        $columnCount = max(1, count($headers), 2);

        foreach ($meta as $label => $value) {
            $sheetRows[] = $this->rowXml($rowIndex++, [$label, $value], 2);
        }

        if (!empty($meta)) {
            $sheetRows[] = '<row r="' . $rowIndex++ . '"/>';
        }

        $headerRow = [];
        foreach ($headers as $header) {
            $headerRow[] = $header;
        }
        $sheetRows[] = $this->rowXml($rowIndex++, $headerRow, 1);

        foreach ($rows as $row) {
            $values = [];
            foreach ($headers as $key => $header) {
                $field = is_string($key) ? $key : $header;
                $values[] = $row[$field] ?? '';
            }
            $sheetRows[] = $this->rowXml($rowIndex++, $values, 0);
        }

        $freezeRow = count($meta) + (!empty($meta) ? 2 : 1);
        $freezeCell = 'A' . ($freezeRow + 1);
        $dimension = 'A1:' . $this->columnName($columnCount) . max(1, $rowIndex - 1);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="' . $dimension . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $freezeRow . '" topLeftCell="' . $freezeCell . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . $this->colsXml($columnCount)
            . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '</worksheet>';
    }

    private function rowXml(int $rowIndex, array $values, int $style): string
    {
        $cells = [];
        foreach (array_values($values) as $i => $value) {
            $ref = $this->columnName($i + 1) . $rowIndex;
            $cells[] = '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . $this->xml($this->safeCell($value)) . '</t></is></c>';
        }
        return '<row r="' . $rowIndex . '">' . implode('', $cells) . '</row>';
    }

    private function colsXml(int $columnCount): string
    {
        $cols = [];
        for ($i = 1; $i <= $columnCount; $i++) {
            $cols[] = '<col min="' . $i . '" max="' . $i . '" width="22" customWidth="1"/>';
        }
        return '<cols>' . implode('', $cols) . '</cols>';
    }

    private function safeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            $text = $value ? '1' : '0';
        } else {
            $text = (string)$value;
        }
        return $text !== '' && in_array($text[0], ['=', '+', '-', '@'], true) ? "'" . $text : $text;
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }

    private function zipStore(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        [$dosTime, $dosDate] = $this->dosDateTime();

        foreach ($files as $name => $data) {
            $crc = (int)sprintf('%u', crc32($data));
            $size = strlen($data);
            $nameLength = strlen($name);

            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0) . $name;
            $local .= $localHeader . $data;

            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $name;

            $offset += strlen($localHeader) + $size;
        }

        $entries = count($files);
        $centralSize = strlen($central);
        $end = pack('VvvvvVVv', 0x06054b50, 0, 0, $entries, $entries, $centralSize, $offset, 0);

        return $local . $central . $end;
    }

    private function dosDateTime(): array
    {
        $time = getdate();
        $dosTime = ((int)$time['hours'] << 11) | ((int)$time['minutes'] << 5) | ((int)($time['seconds'] / 2));
        $dosDate = (((int)$time['year'] - 1980) << 9) | ((int)$time['mon'] << 5) | (int)$time['mday'];
        return [$dosTime, $dosDate];
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Export" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAF7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right/><top/><bottom style="thin"><color rgb="FFB7C9D6"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Skeleton MVC</Application></Properties>';
    }

    private function coreXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Skeleton MVC</dc:creator><cp:lastModifiedBy>Skeleton MVC</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
    }
}
