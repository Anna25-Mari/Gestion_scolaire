<?php
/**
 * Lecteur de fichiers .xlsx natif (sans librairie externe).
 * Un fichier xlsx est une archive ZIP contenant des XML.
 * Requiert l'extension php_zip et SimpleXML (incluses dans XAMPP).
 */
class XlsxReader
{
    private $zip;
    private $sharedStrings = [];
    private $sheets = [];
    private $dateStyles = [];

    public function __construct($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception('Fichier Excel introuvable.');
        }
        if (!class_exists('ZipArchive')) {
            throw new Exception('L\'extension PHP ZipArchive est requise pour lire les fichiers Excel.');
        }
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception('Impossible d\'ouvrir le fichier Excel (fichier corrompu ou format invalide).');
        }
        $this->zip = $zip;
        $this->loadSharedStrings();
        $this->loadDateStyles();
        $this->loadSheets();
    }

    public function __destruct()
    {
        if ($this->zip instanceof ZipArchive) {
            $this->zip->close();
        }
    }

    /**
     * Retourne la liste des noms d'onglets du classeur.
     */
    public function getSheetNames()
    {
        return array_keys($this->sheets);
    }

    /**
     * Retourne les lignes d'un onglet sous forme de tableau indexé :
     * [ [0 => 'A1', 1 => 'B1', ...], ... ]
     * Les cellules vides sont comblées avec ''. Retourne null si l'onglet n'existe pas.
     */
    public function getRows($sheetName)
    {
        if (!isset($this->sheets[$sheetName])) {
            return null;
        }
        $xml = $this->readXml($this->sheets[$sheetName]);
        if (!$xml || !isset($xml->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = (string)$cell['r'];
                $colIndex = $this->colIndexFromRef($ref);
                $cells[$colIndex] = $this->cellValue($cell);
            }
            if (empty($cells)) {
                continue;
            }
            $max = max(array_keys($cells));
            $line = [];
            for ($i = 0; $i <= $max; $i++) {
                $line[$i] = isset($cells[$i]) ? trim((string)$cells[$i]) : '';
            }
            $rows[] = $line;
        }
        return $rows;
    }

    private function cellValue($cell)
    {
        $type = isset($cell['t']) ? (string)$cell['t'] : '';
        switch ($type) {
            case 's': // shared string
                $index = isset($cell->v) ? (int)$cell->v : -1;
                return isset($this->sharedStrings[$index]) ? $this->sharedStrings[$index] : '';
            case 'inlineStr':
                $text = '';
                if (isset($cell->is)) {
                    if (isset($cell->is->t)) {
                        foreach ($cell->is->t as $t) {
                            $text .= (string)$t;
                        }
                    } elseif (isset($cell->is->r)) {
                        foreach ($cell->is->r as $run) {
                            $text .= (string)$run->t;
                        }
                    }
                }
                return $text;
            case 'b': // boolean
                return (isset($cell->v) && (int)$cell->v === 1) ? 'OUI' : 'NON';
            case 'e': // erreur
                return '';
            default: // nombre ou chaîne brute
                $raw = isset($cell->v) ? (string)$cell->v : '';
                $style = isset($cell['s']) ? (int)$cell['s'] : 0;
                if ($raw !== '' && is_numeric($raw) && isset($this->dateStyles[$style])) {
                    return $this->excelSerialToDateString($raw);
                }
                return $raw;
        }
    }

    private function loadSharedStrings()
    {
        $xml = $this->readXml('xl/sharedStrings.xml');
        if (!$xml) {
            return;
        }
        foreach ($xml->si as $si) {
            $text = '';
            if (isset($si->t)) {
                foreach ($si->t as $t) {
                    $text .= (string)$t;
                }
            } elseif (isset($si->r)) {
                foreach ($si->r as $run) {
                    $text .= (string)$run->t;
                }
            }
            $this->sharedStrings[] = $text;
        }
    }

    private function loadSheets()
    {
        $workbook = $this->readXml('xl/workbook.xml');
        $rels = $this->readXml('xl/_rels/workbook.xml.rels');
        if (!$workbook || !$rels) {
            return;
        }

        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $relMap[(string)$rel['Id']] = (string)$rel['Target'];
        }

        foreach ($workbook->sheets->sheet as $sheet) {
            $name = (string)$sheet['name'];
            $attributes = $sheet->attributes('r', true);
            $rid = isset($attributes['id']) ? (string)$attributes['id'] : '';
            if (!isset($relMap[$rid])) {
                continue;
            }
            $target = $relMap[$rid];
            if (strpos($target, '/') === 0) {
                $path = ltrim($target, '/');
            } else {
                while (strpos($target, './') === 0) {
                    $target = substr($target, 2);
                }
                $path = 'xl/' . $target;
            }
            $this->sheets[$name] = $path;
        }
    }

    private function loadDateStyles()
    {
        $styles = $this->readXml('xl/styles.xml');
        if (!$styles) {
            return;
        }

        // Formats intégrés d'Excel correspondant à des dates
        $builtinDateIds = [14, 15, 16, 17, 18, 19, 20, 21, 22, 27, 30, 36, 45, 46, 47];
        $customFormats = [];
        if (isset($styles->numFmts->numFmt)) {
            foreach ($styles->numFmts->numFmt as $fmt) {
                $customFormats[(string)$fmt['numFmtId']] = strtolower((string)$fmt['formatCode']);
            }
        }

        if (!isset($styles->cellXfs->xf)) {
            return;
        }
        foreach ($styles->cellXfs->xf as $index => $xf) {
            $id = isset($xf['numFmtId']) ? (string)$xf['numFmtId'] : '0';
            if (in_array((int)$id, $builtinDateIds, true)) {
                $this->dateStyles[$index] = true;
                continue;
            }
            if (isset($customFormats[$id])) {
                $code = $customFormats[$id];
                // Heuristique : un format contenant y ou d/m est une date
                if (strpos($code, 'y') !== false || (strpos($code, 'd') !== false && strpos($code, 'm') !== false)) {
                    $this->dateStyles[$index] = true;
                }
            }
        }
    }

    private function excelSerialToDateString($serial)
    {
        // Base : 1900-01-01 => série 1 ; correction du bug Excel (29/02/1900 fictif)
        $unixDays = (float)$serial - 25569;
        return gmdate('Y-m-d', (int)round($unixDays * 86400));
    }

    private function colIndexFromRef($ref)
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    private function readXml($path)
    {
        $content = $this->zip->getFromName($path);
        if ($content === false) {
            return null;
        }
        return simplexml_load_string($content);
    }
}
