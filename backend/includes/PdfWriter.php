<?php

class PdfWriter
{
    private const PAGE_W = 595.28;
    private const PAGE_H = 841.89;
    private const MARGIN = 50;
    private const BOTTOM = 75;

    private $pages = [];
    private $current = '';
    private $y;
    private $footerText = '';

    public function __construct()
    {
        $this->startPage();
    }

    private function startPage()
    {
        $this->current = '';
        $this->y = self::PAGE_H - self::MARGIN;
    }

    private function ensureSpace($needed)
    {
        if ($this->y - $needed < self::BOTTOM) {
            $this->pages[] = $this->current;
            $this->startPage();
        }
    }

    public function setFooter($text)
    {
        $this->footerText = $text;
    }

    private function enc($s)
    {
        $out = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        if ($out === false) {
            $out = preg_replace('/[^\x20-\x7E]/', '?', $s);
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $out);
    }

    private function strWidth($s, $size, $bold = false)
    {
        $bytes = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        if ($bytes === false) {
            $bytes = $s;
        }
        return strlen($bytes) * $size * ($bold ? 0.56 : 0.50);
    }

    private function op($s)
    {
        $this->current .= $s . "\n";
    }

    private function text($x, $y, $size, $bold, $r, $g, $b, $str)
    {
        $font = $bold ? 'F2' : 'F1';
        $this->op(sprintf(
            'BT /%s %.1f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET',
            $font,
            $size,
            $r,
            $g,
            $b,
            $x,
            $y,
            $this->enc($str)
        ));
    }

    private function rectFill($x, $y, $w, $h, $r, $g, $b)
    {
        $this->op(sprintf('%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f', $r, $g, $b, $x, $y, $w, $h));
    }

    private function centered($size, $bold, $r, $g, $b, $str, $offsetY = 0)
    {
        $w = $this->strWidth($str, $size, $bold);
        $x = max(self::MARGIN, (self::PAGE_W - $w) / 2);
        $this->text($x, $this->y + $offsetY, $size, $bold, $r, $g, $b, $str);
    }

    /* ===== Blocs de document ===== */

    public function headerBand($schoolName, $docTitle)
    {
        $bandH = 64;
        $top = self::PAGE_H - self::MARGIN + 14;
        $this->rectFill(0, $top - $bandH, self::PAGE_W, $bandH, 0.102, 0.322, 0.463);
        $this->rectFill(0, $top - $bandH - 4, self::PAGE_W, 4, 0.161, 0.502, 0.725);

        $w = $this->strWidth($schoolName, 17, true);
        $this->text((self::PAGE_W - $w) / 2, $top - 30, 17, true, 1, 1, 1, $schoolName);

        $this->y = $top - $bandH - 34;
        $this->centered(13, true, 0.12, 0.16, 0.21, $docTitle);
        $tw = $this->strWidth($docTitle, 13, true);
        $this->rectFill((self::PAGE_W - min($tw, 220)) / 2, $this->y - 6, min($tw, 220), 2.2, 0.161, 0.502, 0.725);
        $this->y -= 26;
    }

    public function sectionTitle($title)
    {
        $this->ensureSpace(46);
        $this->y -= 8;
        $this->text(self::MARGIN, $this->y, 11.5, true, 0.102, 0.322, 0.463, $title);
        $this->rectFill(self::MARGIN, $this->y - 6, self::PAGE_W - 2 * self::MARGIN, 1.4, 0.78, 0.85, 0.91);
        $this->y -= 20;
    }

    public function keyValue($label, $value, $zebra = true)
    {
        $rowH = 21;
        $this->ensureSpace($rowH + 4);
        if ($zebra) {
            $this->rectFill(self::MARGIN, $this->y - $rowH + 5, self::PAGE_W - 2 * self::MARGIN, $rowH, 0.941, 0.965, 0.988);
        }
        $this->text(self::MARGIN + 10, $this->y - 9, 10, true, 0.28, 0.33, 0.41, $label);
        $this->text(self::MARGIN + 200, $this->y - 9, 10, false, 0.12, 0.16, 0.21, $value);
        $this->y -= $rowH;
    }

    public function note($text)
    {
        $this->ensureSpace(24);
        $this->text(self::MARGIN, $this->y, 9, false, 0.45, 0.50, 0.55, $text);
        $this->y -= 16;
    }

    public function spacer($h = 12)
    {
        $this->y -= $h;
    }

    /* ===== Assemblage ===== */

    private function build()
    {
        if (trim($this->current) !== '') {
            $this->pages[] = $this->current;
            $this->current = '';
        }

        $total = count($this->pages);
        foreach ($this->pages as $i => &$content) {
            $fy = self::BOTTOM - 28;
            $this->op('');
            $content .= sprintf("%.3f %.3f %.3f RG 0.6 w %.2f %.2f m %.2f %.2f l S\n", 0.85, 0.88, 0.91, self::MARGIN, $fy + 14, self::PAGE_W - self::MARGIN, $fy + 14);
            if ($this->footerText !== '') {
                $content .= sprintf('BT /F1 8 Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET' . "\n", 0.45, 0.50, 0.55, self::MARGIN, $fy, $this->enc($this->footerText));
            }
            $pageLabel = 'Page ' . ($i + 1) . '/' . $total;
            $pl = $this->enc($pageLabel);
            $lw = strlen($pl) * 8 * 0.5;
            $content .= sprintf('BT /F1 8 Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET' . "\n", 0.45, 0.50, 0.55, self::PAGE_W - self::MARGIN - $lw, $fy, $pl);
        }
        unset($content);

        $objects = [];
        $nPages = count($this->pages);
        $firstPageObj = 3;
        $fontF1 = $firstPageObj + 2 * $nPages;
        $fontF2 = $fontF1 + 1;

        $kids = [];
        for ($i = 0; $i < $nPages; $i++) {
            $kids[] = ($firstPageObj + 2 * $i) . ' 0 R';
        }

        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count $nPages >>";

        for ($i = 0; $i < $nPages; $i++) {
            $pageObjNum = $firstPageObj + 2 * $i;
            $contObjNum = $pageObjNum + 1;
            $objects[$pageObjNum] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::PAGE_W . " " . self::PAGE_H . "] /Resources << /Font << /F1 $fontF1 0 R /F2 $fontF2 0 R >> >> /Contents $contObjNum 0 R >>";
            $stream = $this->pages[$i];
            $objects[$contObjNum] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        }

        $objects[$fontF1] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[$fontF2] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$body\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $maxNum = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxNum + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($n = 1; $n <= $maxNum; $n++) {
            if (isset($offsets[$n])) {
                $pdf .= sprintf("%010d 00000 n \n", $offsets[$n]);
            } else {
                $pdf .= "0000000000 65535 f \n";
            }
        }
        $pdf .= "trailer\n<< /Size " . ($maxNum + 1) . " /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

        return $pdf;
    }

    public function save($path)
    {
        file_put_contents($path, $this->build());
    }

    public function output()
    {
        echo $this->build();
    }
}
