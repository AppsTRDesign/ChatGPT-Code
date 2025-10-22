<?php
require_once __DIR__ . '/helpers.php';

class TrueTypeFont
{
    private string $data;
    private array $tables = [];
    private array $glyphWidths = [];
    private array $cmap = [];
    private int $unitsPerEm = 1000;
    private int $ascent = 0;
    private int $descent = 0;
    private int $capHeight = 0;
    private array $bbox = [0, 0, 0, 0];
    private int $numberOfHMetrics = 0;

    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new RuntimeException('Yazı tipi bulunamadı: ' . $path);
        }
        $this->data = file_get_contents($path);
        $this->parseTables();
        $this->parseMetrics();
        $this->parseCmap();
        $this->parseWidths();
    }

    private function parseTables(): void
    {
        $numTables = $this->readUInt16(4);
        $offset = 12;
        for ($i = 0; $i < $numTables; $i++) {
            $tag = substr($this->data, $offset, 4);
            $offset += 4;
            $offset += 4; // checksum
            $tableOffset = $this->readUInt32($offset);
            $offset += 4;
            $length = $this->readUInt32($offset);
            $offset += 4;
            $this->tables[$tag] = ['offset' => $tableOffset, 'length' => $length];
        }
    }

    private function parseMetrics(): void
    {
        $head = $this->getTable('head');
        $this->unitsPerEm = $this->readUInt16($head + 18);
        $xMin = $this->readInt16($head + 36);
        $yMin = $this->readInt16($head + 38);
        $xMax = $this->readInt16($head + 40);
        $yMax = $this->readInt16($head + 42);
        $this->bbox = [$xMin, $yMin, $xMax, $yMax];

        $hhea = $this->getTable('hhea');
        $this->ascent = $this->readInt16($hhea + 4);
        $this->descent = $this->readInt16($hhea + 6);
        $this->numberOfHMetrics = $this->readUInt16($hhea + 34);

        $os2 = $this->getTable('OS/2');
        $this->capHeight = $this->readInt16($os2 + 88);
        if ($this->capHeight === 0) {
            $this->capHeight = $this->ascent;
        }
    }

    private function parseCmap(): void
    {
        $cmapOffset = $this->getTable('cmap');
        $numTables = $this->readUInt16($cmapOffset + 2);
        $unicodeOffset = null;
        for ($i = 0; $i < $numTables; $i++) {
            $platform = $this->readUInt16($cmapOffset + 4 + ($i * 8));
            $encoding = $this->readUInt16($cmapOffset + 6 + ($i * 8));
            $subtableOffset = $this->readUInt32($cmapOffset + 8 + ($i * 8));
            if ($platform === 3 && ($encoding === 1 || $encoding === 10)) {
                $unicodeOffset = $cmapOffset + $subtableOffset;
                break;
            }
        }
        if ($unicodeOffset === null) {
            throw new RuntimeException('Unicode cmap tablosu bulunamadı.');
        }
        $format = $this->readUInt16($unicodeOffset);
        if ($format !== 4) {
            throw new RuntimeException('Desteklenmeyen cmap formatı: ' . $format);
        }
        $segCount = $this->readUInt16($unicodeOffset + 6) / 2;
        $endCountOffset = $unicodeOffset + 14;
        $startCountOffset = $endCountOffset + 2 + $segCount * 2;
        $idDeltaOffset = $startCountOffset + $segCount * 2;
        $idRangeOffsetOffset = $idDeltaOffset + $segCount * 2;
        $glyphIndexOffset = $idRangeOffsetOffset + $segCount * 2;
        for ($i = 0; $i < $segCount; $i++) {
            $endCode = $this->readUInt16($endCountOffset + ($i * 2));
            $startCode = $this->readUInt16($startCountOffset + ($i * 2));
            $idDelta = $this->readInt16($idDeltaOffset + ($i * 2));
            $idRangeOffset = $this->readUInt16($idRangeOffsetOffset + ($i * 2));
            for ($code = $startCode; $code <= $endCode; $code++) {
                if ($idRangeOffset === 0) {
                    $glyphId = ($code + $idDelta) & 0xFFFF;
                } else {
                    $offset = $idRangeOffset / 2 + ($code - $startCode) + $i - ($segCount - 1);
                    $glyphIndexLocation = $glyphIndexOffset + $offset * 2;
                    $glyphId = $this->readUInt16($glyphIndexLocation);
                    if ($glyphId !== 0) {
                        $glyphId = ($glyphId + $idDelta) & 0xFFFF;
                    }
                }
                $this->cmap[$code] = $glyphId;
            }
        }
    }

    private function parseWidths(): void
    {
        $hmtx = $this->getTable('hmtx');
        $numGlyphs = $this->getNumGlyphs();
        $offset = $hmtx;
        for ($i = 0; $i < $this->numberOfHMetrics; $i++) {
            $advanceWidth = $this->readUInt16($offset);
            $offset += 4;
            $this->glyphWidths[$i] = $advanceWidth;
        }
        $lastWidth = $this->glyphWidths[$this->numberOfHMetrics - 1] ?? 0;
        for ($i = $this->numberOfHMetrics; $i < $numGlyphs; $i++) {
            $this->glyphWidths[$i] = $lastWidth;
        }
    }

    public function getUnitsPerEm(): int
    {
        return $this->unitsPerEm;
    }

    public function getAscent(): int
    {
        return $this->ascent;
    }

    public function getDescent(): int
    {
        return $this->descent;
    }

    public function getCapHeight(): int
    {
        return $this->capHeight;
    }

    public function getBBox(): array
    {
        return $this->bbox;
    }

    public function getGlyphIndex(int $codepoint): int
    {
        return $this->cmap[$codepoint] ?? 0;
    }

    public function getGlyphWidth(int $glyphIndex): int
    {
        return $this->glyphWidths[$glyphIndex] ?? $this->glyphWidths[0] ?? 0;
    }

    public function getData(): string
    {
        return $this->data;
    }

    private function getNumGlyphs(): int
    {
        $maxp = $this->getTable('maxp');
        return $this->readUInt16($maxp + 4);
    }

    private function getTable(string $tag): int
    {
        if (!isset($this->tables[$tag])) {
            throw new RuntimeException($tag . ' tablosu bulunamadı.');
        }
        return $this->tables[$tag]['offset'];
    }

    private function readUInt16(int $offset): int
    {
        return unpack('n', substr($this->data, $offset, 2))[1];
    }

    private function readInt16(int $offset): int
    {
        $value = $this->readUInt16($offset);
        if ($value > 0x7FFF) {
            $value -= 0x10000;
        }
        return $value;
    }

    private function readUInt32(int $offset): int
    {
        $data = unpack('N', substr($this->data, $offset, 4))[1];
        if ($data < 0) {
            $data += 4294967296;
        }
        return $data;
    }
}

class InvoicePdf
{
    private TrueTypeFont $font;
    private string $fontName;
    private array $pages = [];
    private ?array $currentPage = null;
    private float $cursorY;
    private float $pageWidth;
    private float $pageHeight;
    private array $charMap = [];
    private array $cidToGlyph = [];
    private array $cidWidths = [];
    private array $cidToUnicode = [];
    private int $nextCid = 1;

    public function __construct(string $fontPath, string $fontName = 'CustomFont')
    {
        $this->font = new TrueTypeFont($fontPath);
        $this->fontName = preg_replace('/[^A-Za-z0-9_-]/', '', $fontName) ?: 'CustomFont';
        $this->pageWidth = $this->mmToPt(210);
        $this->pageHeight = $this->mmToPt(297);
    }

    public function addPage(): void
    {
        $this->currentPage = [
            'content' => '',
        ];
        $this->cursorY = $this->pageHeight - $this->mmToPt(20);
        $this->pages[] = &$this->currentPage;
    }

    public function writeLine(string $text, float $fontSize = 12, float $marginLeft = 20): void
    {
        if (!$this->currentPage) {
            $this->addPage();
        }
        $this->text($marginLeft, $this->ptToMm($this->pageHeight - $this->cursorY), $text, $fontSize);
        $lineHeight = $fontSize * 1.4;
        $this->cursorY -= $this->mmToPt($lineHeight / 3.7795275591); // convert px to mm approx
    }

    public function text(float $xMm, float $yMm, string $text, float $fontSize = 12): void
    {
        if (!$this->currentPage) {
            $this->addPage();
        }
        $x = $this->mmToPt($xMm);
        $y = $this->mmToPt($yMm);
        $y = $this->pageHeight - $y;
        $encoded = $this->encodeText($text);
        $cmd = sprintf("BT /F1 %.2F Tf 1 0 0 1 %.2F %.2F Tm <%s> Tj ET\n", $fontSize, $x, $y, $encoded);
        $this->currentPage['content'] .= $cmd;
    }

    public function drawTable(array $rows, array $columns, float $startYMm): void
    {
        $x = $this->mmToPt(20);
        $y = $this->pageHeight - $this->mmToPt($startYMm);
        $colWidths = array_map(fn($c) => $this->mmToPt($c['width']), $columns);
        $headerFontSize = 11;
        $bodyFontSize = 10;

        $content = '';
        // Header
        $currentX = $x;
        foreach ($columns as $index => $column) {
            $text = $this->encodeText($column['title']);
            $content .= sprintf("BT /F1 %.2F Tf 1 0 0 1 %.2F %.2F Tm <%s> Tj ET\n", $headerFontSize, $currentX, $y, $text);
            $currentX += $colWidths[$index];
        }
        $y -= $this->mmToPt(8);

        foreach ($rows as $row) {
            $currentX = $x;
            foreach ($columns as $index => $column) {
                $value = $row[$column['key']] ?? '';
                $text = $this->encodeText((string)$value);
                $content .= sprintf("BT /F1 %.2F Tf 1 0 0 1 %.2F %.2F Tm <%s> Tj ET\n", $bodyFontSize, $currentX, $y, $text);
                $currentX += $colWidths[$index];
            }
            $y -= $this->mmToPt(7);
        }

        $this->currentPage['content'] .= $content;
    }

    public function output(): string
    {
        $objects = [];
        $offsets = [];

        $fontData = $this->font->getData();
        $fontFileObject = $this->addObject($objects, sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($fontData), $fontData));

        $cidToGidMap = $this->buildCidToGidMap();
        $cidToGidObject = $this->addObject($objects, sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($cidToGidMap), $cidToGidMap));

        $toUnicode = $this->buildToUnicodeCMap();
        $toUnicodeObject = $this->addObject($objects, sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($toUnicode), $toUnicode));

        $descriptor = $this->buildFontDescriptor($fontFileObject);
        $descriptorObject = $this->addObject($objects, $descriptor);

        $cidFont = $this->buildCidFont($descriptorObject, $cidToGidObject);
        $cidFontObject = $this->addObject($objects, $cidFont);

        $type0Font = $this->addObject($objects, sprintf('<< /Type /Font /Subtype /Type0 /BaseFont /%s /Encoding /Identity-H /DescendantFonts [%d 0 R] /ToUnicode %d 0 R >>', $this->fontName, $cidFontObject, $toUnicodeObject));

        $pagesKids = [];
        foreach ($this->pages as $page) {
            $contentObject = $this->addObject($objects, sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($page['content']), $page['content']));
            $pageObject = $this->addObject($objects, sprintf('<< /Type /Page /Parent %s 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>', '{{PAGES}}', $this->pageWidth, $this->pageHeight, $type0Font, $contentObject));
            $pagesKids[] = $pageObject;
        }

        $pagesObject = $this->addObject($objects, sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' 0 R ', $pagesKids) . ' 0 R', count($pagesKids)));
        // replace placeholder
        foreach ($objects as &$object) {
            $object['content'] = str_replace('{{PAGES}}', (string)$pagesObject, $object['content']);
        }

        $catalogObject = $this->addObject($objects, sprintf('<< /Type /Catalog /Pages %d 0 R >>', $pagesObject));

        $pdf = "%PDF-1.7\n";
        foreach ($objects as $index => $object) {
            $number = $index + 1;
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $object['content'] . "\nendobj\n";
        }
        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root $catalogObject 0 R >>\nstartxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function addObject(array &$objects, string $content): int
    {
        $objects[] = ['content' => $content];
        return count($objects);
    }

    private function encodeText(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $encoded = '';
        foreach ($chars as $char) {
            $codepoint = mb_ord($char, 'UTF-8');
            if (!isset($this->charMap[$codepoint])) {
                $glyphIndex = $this->font->getGlyphIndex($codepoint);
                $cid = $this->nextCid++;
                $this->charMap[$codepoint] = $cid;
                $this->cidToGlyph[$cid] = $glyphIndex;
                $this->cidToUnicode[$cid] = $codepoint;
                $width = $this->font->getGlyphWidth($glyphIndex);
                $this->cidWidths[$cid] = (int)round($width / $this->font->getUnitsPerEm() * 1000);
            }
            $cid = $this->charMap[$codepoint];
            $encoded .= sprintf('%04X', $cid);
        }
        return $encoded;
    }

    private function buildCidToGidMap(): string
    {
        if (!$this->cidToGlyph) {
            return '';
        }
        $maxCid = max(array_keys($this->cidToGlyph));
        $map = str_repeat("\x00\x00", $maxCid + 1);
        foreach ($this->cidToGlyph as $cid => $gid) {
            $map[$cid * 2] = chr(($gid >> 8) & 0xFF);
            $map[$cid * 2 + 1] = chr($gid & 0xFF);
        }
        return $map;
    }

    private function buildToUnicodeCMap(): string
    {
        $lines = [];
        $lines[] = '/CIDInit /ProcSet findresource begin';
        $lines[] = '12 dict begin';
        $lines[] = 'begincmap';
        $lines[] = '/CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> def';
        $lines[] = '/CMapName /Adobe-Identity-UCS def';
        $lines[] = '/CMapType 2 def';
        $lines[] = '1 begincodespacerange';
        $lines[] = '<0000> <FFFF>';
        $lines[] = 'endcodespacerange';
        $chunks = array_chunk($this->cidToUnicode, 100, true);
        foreach ($chunks as $chunk) {
            $lines[] = count($chunk) . ' beginbfchar';
            foreach ($chunk as $cid => $codepoint) {
                $lines[] = sprintf('<%04X> <%04X>', $cid, $codepoint);
            }
            $lines[] = 'endbfchar';
        }
        $lines[] = 'endcmap';
        $lines[] = 'CMapName currentdict /CMap defineresource pop';
        $lines[] = 'end';
        $lines[] = 'end';
        return implode("\n", $lines);
    }

    private function buildFontDescriptor(int $fontFileObject): string
    {
        [$xMin, $yMin, $xMax, $yMax] = $this->font->getBBox();
        $bbox = sprintf('[%d %d %d %d]', $xMin, $yMin, $xMax, $yMax);
        $ascent = $this->font->getAscent();
        $descent = $this->font->getDescent();
        $capHeight = $this->font->getCapHeight();
        return sprintf('<< /Type /FontDescriptor /FontName /%s /Flags 32 /FontBBox %s /Ascent %d /Descent %d /CapHeight %d /StemV 80 /ItalicAngle 0 /FontFile2 %d 0 R >>', $this->fontName, $bbox, $ascent, $descent, $capHeight, $fontFileObject);
    }

    private function buildCidFont(int $descriptorObject, int $cidToGidObject): string
    {
        $widthEntries = [];
        foreach ($this->cidWidths as $cid => $width) {
            $widthEntries[] = sprintf('%d [%d]', $cid, $width);
        }
        $widths = $widthEntries ? '[ ' . implode(' ', $widthEntries) . ' ]' : '[]';
        return sprintf('<< /Type /Font /Subtype /CIDFontType2 /BaseFont /%s /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor %d 0 R /W %s /CIDToGIDMap %d 0 R >>', $this->fontName, $descriptorObject, $widths, $cidToGidObject);
    }

    private function mmToPt(float $mm): float
    {
        return $mm * 72 / 25.4;
    }

    private function ptToMm(float $pt): float
    {
        return $pt * 25.4 / 72;
    }
}
