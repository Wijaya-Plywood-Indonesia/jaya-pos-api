<?php

namespace App\Services;

use ZipArchive;

/**
 * Penulis file Excel (.xlsx) sederhana tanpa paket tambahan.
 * Mendukung beberapa sheet berisi teks dan angka. Butuh ekstensi PHP zip (bawaan Laragon).
 */
class SimpleXlsx
{
    /**
     * @param  array<string, array<int, array<int, int|float|string|null>>>  $sheets  nama sheet => baris (baris pertama = judul kolom, dicetak tebal)
     */
    public static function make(array $sheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $names = array_keys($sheets);
        $count = count($names);
        $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

        $types = $head
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

        for ($i = 1; $i <= $count; $i++) {
            $types .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        $zip->addFromString('[Content_Types].xml', $types . '</Types>');

        $zip->addFromString('_rels/.rels', $head
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $sheetTags = '';
        $relTags = '';

        foreach ($names as $i => $name) {
            $id = $i + 1;
            $sheetTags .= '<sheet name="' . self::esc(self::sheetName($name, $id)) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
            $relTags .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
        }

        $relTags .= '<Relationship Id="rId' . ($count + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $zip->addFromString('xl/workbook.xml', $head
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetTags . '</sheets></workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', $head
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $relTags . '</Relationships>');

        // Gaya sel: 0 = biasa, 1 = tebal (judul), 2 = angka dengan pemisah ribuan
        $zip->addFromString('xl/styles.xml', $head
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>');

        $i = 0;
        foreach ($sheets as $rows) {
            $i++;
            $zip->addFromString('xl/worksheets/sheet' . $i . '.xml', self::sheetXml(array_values($rows)));
        }

        $zip->close();

        $data = (string) file_get_contents($path);
        @unlink($path);

        return $data;
    }

    private static function sheetXml(array $rows): string
    {
        // Lebar kolom mengikuti isi terpanjang (maksimal 50 karakter)
        $widths = [];
        foreach ($rows as $row) {
            foreach (array_values($row) as $c => $value) {
                $len = is_string($value) ? mb_strlen($value) : strlen((string) $value) + 2;
                $widths[$c] = max($widths[$c] ?? 8, min(50, $len + 2));
            }
        }

        $cols = '';
        foreach ($widths as $c => $w) {
            $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        $body = '';
        foreach ($rows as $r => $row) {
            $body .= '<row r="' . ($r + 1) . '">';

            foreach (array_values($row) as $c => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $ref = self::col($c) . ($r + 1);

                if (is_int($value) || is_float($value)) {
                    $body .= '<c r="' . $ref . '" s="2"><v>' . $value . '</v></c>';
                } else {
                    $style = $r === 0 ? ' s="1"' : '';
                    $body .= '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">' . self::esc((string) $value) . '</t></is></c>';
                }
            }

            $body .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . ($cols !== '' ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $body . '</sheetData></worksheet>';
    }

    /** 0 -> A, 25 -> Z, 26 -> AA */
    private static function col(int $i): string
    {
        $s = '';
        $i++;

        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m) . $s;
            $i = intdiv($i - 1, 26);
        }

        return $s;
    }

    private static function sheetName(string $name, int $id): string
    {
        $name = trim(str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $name));

        return mb_substr($name !== '' ? $name : 'Sheet' . $id, 0, 31);
    }

    private static function esc(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
