<?php

namespace App;

class PdfExporter
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN_LEFT = 48;
    private const MARGIN_TOP = 48;
    private const FONT_SIZE = 12;
    private const LINE_HEIGHT = 16;

    public static function fromTable(string $title, array $headers, array $rows): string
    {
        $lines = [];
        $cleanTitle = self::normalizeText($title !== '' ? $title : 'Rapor');
        $lines[] = $cleanTitle;
        $dividerLength = max(20, self::length($cleanTitle));
        $lines[] = str_repeat('-', $dividerLength);

        if (!empty($headers)) {
            $headerLine = implode(' | ', array_map([self::class, 'normalizeText'], $headers));
            $lines[] = $headerLine;
            $lines[] = str_repeat('-', max($dividerLength, self::length($headerLine)));
        }

        foreach ($rows as $row) {
            $lines[] = implode(' | ', array_map([self::class, 'normalizeText'], $row));
        }

        if (count($lines) === 2) {
            $lines[] = 'Kayıt bulunamadı.';
        }

        return self::buildPdf($lines);
    }

    private static function normalizeText($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('d.m.Y H:i');
        } elseif (is_bool($value)) {
            $value = $value ? 'Evet' : 'Hayır';
        } elseif (is_array($value)) {
            $value = implode(', ', array_map([self::class, 'normalizeText'], $value));
        }

        $string = trim((string) $value);
        $string = preg_replace('/[\r\n\t]+/u', ' ', $string);
        return $string;
    }

    private static function buildPdf(array $lines): string
    {
        $linesPerPage = max(1, (int) floor((self::PAGE_HEIGHT - (self::MARGIN_TOP * 2)) / self::LINE_HEIGHT));
        $pages = array_chunk($lines, $linesPerPage);
        if (empty($pages)) {
            $pages = [['']];
        }

        $objectId = 1;
        $catalogId = $objectId++;
        $pagesId = $objectId++;
        $fontId = $objectId++;
        $objects = [];
        $kids = [];

        foreach ($pages as $pageLines) {
            $contentStream = self::buildContentStream($pageLines);
            $contentId = $objectId++;
            $pageId = $objectId++;

            $objects[$contentId] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($contentStream), $contentStream);
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>',
                $pagesId,
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $fontId,
                $contentId
            );
            $kids[] = sprintf('%d 0 R', $pageId);
        }

        if (empty($kids)) {
            $contentStream = self::buildContentStream(['']);
            $contentId = $objectId++;
            $pageId = $objectId++;
            $objects[$contentId] = sprintf("<< /Length %d >>\nstream\n%s\nendstream", strlen($contentStream), $contentStream);
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>',
                $pagesId,
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $fontId,
                $contentId
            );
            $kids[] = sprintf('%d 0 R', $pageId);
        }

        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[$pagesId] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), count($kids));
        $objects[$catalogId] = sprintf('<< /Type /Catalog /Pages %d 0 R >>', $pagesId);

        ksort($objects);

        $header = "%PDF-1.4\n";
        $body = '';
        $offsets = [];

        foreach ($objects as $id => $content) {
            $offsets[$id] = strlen($header) + strlen($body);
            $body .= $id . " 0 obj\n" . $content . "\nendobj\n";
        }

        $xrefOffset = strlen($header) + strlen($body);
        $totalObjects = empty($objects) ? 0 : max(array_keys($objects));
        $xref = "xref\n0 " . ($totalObjects + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $totalObjects; $i++) {
            $position = $offsets[$i] ?? strlen($header);
            $xref .= sprintf('%010d 00000 n %s', $position, "\n");
        }

        $trailer = sprintf(
            "trailer\n<< /Size %d /Root %d 0 R >>\nstartxref\n%d\n%%EOF",
            $totalObjects + 1,
            $catalogId,
            $xrefOffset
        );

        return $header . $body . $xref . $trailer;
    }

    private static function buildContentStream(array $lines): string
    {
        $lines = array_values($lines);
        if (empty($lines)) {
            $lines = [''];
        }

        $stream = "BT\n";
        $stream .= sprintf('/F1 %d Tf\n', self::FONT_SIZE);
        $stream .= sprintf('1 0 0 1 %.2f %.2f Tm\n', self::MARGIN_LEFT, self::PAGE_HEIGHT - self::MARGIN_TOP);
        $stream .= sprintf('%.2f TL\n', self::LINE_HEIGHT);

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "T*\n";
            }
            $stream .= '(' . self::escapeText($line) . ") Tj\n";
        }

        $stream .= 'ET';
        return $stream;
    }

    private static function escapeText(string $text): string
    {
        if (function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, 'Windows-1254', 'UTF-8');
        }
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/u', '', $text) ?? '';
        return $text;
    }

    private static function length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}
