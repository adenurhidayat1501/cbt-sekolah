<?php
/**
 * Stubs for static code analysis & IDE indexing (Intelephense / PHP Language Server)
 * when composer vendor directory is not installed or indexed in the workspace.
 * When PhpOffice is installed via Composer, these stubs are safely ignored.
 */

namespace {
    defined('BASEPATH') or exit('No direct script access allowed');
}

namespace PhpOffice\PhpSpreadsheet {
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet', false)) {
        class Spreadsheet {
            public function getActiveSheet() { return new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet(); }
        }
    }
}

namespace PhpOffice\PhpSpreadsheet\Worksheet {
    if (!class_exists('PhpOffice\PhpSpreadsheet\Worksheet\Worksheet', false)) {
        class Worksheet {
            public function setTitle($title) { return $this; }
            public function setCellValue($coord, $value) { return $this; }
            public function getStyle($coord) { return new \PhpOffice\PhpSpreadsheet\Style\Style(); }
            public function getColumnDimension($col) { return new \PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension(); }
        }
    }
    if (!class_exists('PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension', false)) {
        class ColumnDimension {
            public function setAutoSize($val) { return $this; }
        }
    }
}

namespace PhpOffice\PhpSpreadsheet\Style {
    if (!class_exists('PhpOffice\PhpSpreadsheet\Style\Style', false)) {
        class Style {
            public function getFont() { return new Font(); }
            public function getFill() { return new Fill(); }
        }
    }
    if (!class_exists('PhpOffice\PhpSpreadsheet\Style\Font', false)) {
        class Font {
            public function setBold($bold) { return $this; }
            public function setSize($size) { return $this; }
            public function setItalic($italic) { return $this; }
        }
    }
    if (!class_exists('PhpOffice\PhpSpreadsheet\Style\Fill', false)) {
        class Fill {
            const FILL_SOLID = 'solid';
            public function setFillType($type) { return $this; }
            public function getStartColor() { return new Color(); }
        }
    }
    if (!class_exists('PhpOffice\PhpSpreadsheet\Style\Color', false)) {
        class Color {
            public function setARGB($argb) { return $this; }
        }
    }
}

namespace PhpOffice\PhpSpreadsheet\Writer {
    if (!class_exists('PhpOffice\PhpSpreadsheet\Writer\Xlsx', false)) {
        class Xlsx {
            public function __construct($spreadsheet = null) {}
            public function save($filename) {}
        }
    }
}
