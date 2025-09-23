<?php
/*
 * Minimal embedded SimpleXLSX reader (subset) for .xlsx parsing
 * Source: https://github.com/shuchkin/simplexlsx (MIT)
 * Note: For brevity and maintenance, consider replacing with full upstream file if you need more features.
 */

declare(strict_types=1);

if (!class_exists('SimpleXLSX')) {
    class SimpleXLSX {
        private $workbook;
        private $sharedStrings = [];
        private $sheets = [];

        public static function parse(string $filename): ?self {
            $xlsx = new self();
            if (!$xlsx->open($filename)) {
                return null;
            }
            return $xlsx;
        }

        public function rows(int $sheetIndex = 0): array {
            if (!isset($this->sheets[$sheetIndex])) return [];
            return $this->sheets[$sheetIndex];
        }

        private function open(string $filename): bool {
            $zip = new ZipArchive();
            if ($zip->open($filename) !== true) return false;

            // shared strings
            $this->sharedStrings = [];
            $ss = $zip->getFromName('xl/sharedStrings.xml');
            if ($ss !== false) {
                $xml = @simplexml_load_string($ss);
                if ($xml && isset($xml->si)) {
                    foreach ($xml->si as $si) {
                        if (isset($si->t)) {
                            $this->sharedStrings[] = (string) $si->t;
                        } else if (isset($si->r)) {
                            $text = '';
                            foreach ($si->r as $r) { $text .= (string) $r->t; }
                            $this->sharedStrings[] = $text;
                        }
                    }
                }
            }

            // first sheet only (minimal)
            $sheetPaths = ['xl/worksheets/sheet1.xml', 'xl/worksheets/sheet01.xml'];
            $sheetXml = null;
            foreach ($sheetPaths as $p) {
                $sheetXml = $zip->getFromName($p);
                if ($sheetXml !== false) break;
            }
            if ($sheetXml === false) { $zip->close(); return false; }

            $rows = [];
            $xml = @simplexml_load_string($sheetXml);
            if ($xml && isset($xml->sheetData) && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $cells = [];
                    foreach ($row->c as $c) {
                        $type = (string) $c['t'];
                        $v = isset($c->v) ? (string) $c->v : '';
                        if ($type === 's') { // shared string
                            $idx = (int) $v;
                            $cells[] = $this->sharedStrings[$idx] ?? '';
                        } else {
                            $cells[] = $v;
                        }
                    }
                    $rows[] = $cells;
                }
            }

            $this->sheets[0] = $rows;
            $zip->close();
            return true;
        }
    }
}


