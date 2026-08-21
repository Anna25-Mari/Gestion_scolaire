<?php
/**
 * Générateur minimaliste de fichiers .xlsx (sans librairie externe).
 * Utilisé pour produire le modèle d'import à remplir par l'admin.
 */
class XlsxWriter
{
    private $sheets = [];

    public function addSheet($name, array $rows)
    {
        $this->sheets[] = ['name' => $name, 'rows' => $rows];
    }

    public function save($filePath)
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Impossible de créer le fichier Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());

        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sheet['rows']));
        }

        return $zip->close();
    }

    private function contentTypes()
    {
        $overrides = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function rootRels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml()
    {
        $sheets = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $name = htmlspecialchars($sheet['name'], ENT_QUOTES, 'UTF-8');
            $sheets .= '<sheet name="' . $name . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets>'
            . '</workbook>';
    }

    private function workbookRels()
    {
        $rels = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    private function sheetXml(array $rows)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $r => $cells) {
            $rowXml = '<row r="' . ($r + 1) . '">';
            foreach ($cells as $c => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $ref = $this->cellRef($r, $c);
                $text = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                $rowXml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
            }
            $rowXml .= '</row>';
            $xml .= $rowXml;
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function cellRef($rowIndex, $colIndex)
    {
        $letters = '';
        $col = $colIndex + 1;
        while ($col > 0) {
            $mod = ($col - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $col = intdiv($col - $mod - 1, 26);
        }
        return $letters . ($rowIndex + 1);
    }
}
