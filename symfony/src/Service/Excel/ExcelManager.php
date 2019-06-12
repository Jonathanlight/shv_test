<?php
namespace App\Service\Excel;

use App\Service\Excel\SheetContents\TRCSheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExcelManager
{
    const COLUMN_MAX_WIDTH = 50;
    /**
     * @var Spreadsheet
     */
    private $spreadsheet;
    /**
     * @var bool
     */
    private $nbSheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->firstSheet = true;
    }

    /**
     * @param $data
     */
    public function setSheet($data)
    {
        $spreadsheet = $this->spreadsheet;
        $nbSheet = $this->nbSheet;
        Cell::setValueBinder(new AdvancedValueBinder());
        if ($this->firstSheet) {
            $sheet = $spreadsheet->getActiveSheet();
            $this->firstSheet = false;
        } else {
            $sheet = $spreadsheet->createSheet();
        }
        $sheet->setTitle($data->getTitle());
        $header = $data->getHeader();
        $contents = $data->getData();
        $depth = $data->getDepth();
        for ($col = 'A', $t = 0; $t < count($header); ++$col, ++$t) {
            if (!is_array($header[$t])) {
                $sheet->mergeCells($col. 1 .':'.$col.$depth);
                $sheet->setCellValue($col. 1, $header[$t]);
                continue;
            }
            $subHeader = $header[$t][key($header[$t])];
            $nbSubHeader = count($subHeader);
            $prevCol = $col;
            $sheet->setCellValue($col. 1, key($header[$t]));
            for ($i = 0; $i < $nbSubHeader; ++$i) {
                $sheet->setCellValue($col.$depth, $subHeader[$i]);
                if ($i < $nbSubHeader - 1) {
                    ++$col;
                }
            }
            $sheet->mergeCells($prevCol. 1 .':'.$col. 1);
        }
        $lastColumn = $sheet->getHighestColumn();
        ++$lastColumn;
        ++$depth;
        foreach ($contents as $contentList) {
            foreach ($contentList as $key => $content) {
                $col = 'A';
                foreach ($content as $key => $value) {
                    $columnTitle = $sheet->getCell($col.'1')->getValue();
                    if ('Libellé' == $columnTitle) {
                        $value = strtoupper($value);
                    }
                    $sheet->setCellValue($col.$depth, trim($value));
                    if (strtotime($value) && !is_numeric($value)) {
                        $sheet->getStyle($col.$depth)
                            ->getNumberFormat()
                            ->setFormatCode('dd/mm/yyyy');
                    }
                    ++$col;
                }
                ++$depth;
            }
        }
        $this->setStyle($data, $sheet);
    }

    /**
     * @param $data
     * @param $sheet
     */
    public function setStyle($data, $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        ++$lastColumn;
        for ($column = 'A'; $column != $lastColumn; ++$column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
            $this->spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->spreadsheet->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $columnTitle = $sheet->getCell($column.'1')->getValue();
            if ('num camp' == $columnTitle) {
                $sheet->getColumnDimension($column)->setVisible(false);
            }
            for ($i = 1; $i <= $data->getDepth(); ++$i) {
                $sheet->getStyle($column.$i)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                        'bottom' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                        'left' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                        'right' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            }
        }
    }

    /**
     * Resize columns if text is too long
     */
    public function checkColumnWidth()
    {
        $spreadsheet = $this->spreadsheet;
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet->calculateColumnWidths();
            foreach ($sheet->getColumnDimensions() as $colDim) {
                $colWidth = $colDim->getWidth();
                if (!$colDim->getAutoSize() || $colWidth <= self::COLUMN_MAX_WIDTH) {
                    continue;
                }
                $colDim->setAutoSize(false);
                $colDim->setWidth(self::COLUMN_MAX_WIDTH);
                $column = $colDim->getColumnIndex();
                for ($i = 1; $i <= $sheet->getHighestRow($column); ++$i) {
                    if (strlen($sheet->getCell($column.$i)->getValue()) > 43) {
                        $sheet->getStyle($column.$i)->getAlignment()->setWrapText(true);
                        $sheet->getStyle($column.$i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function generateExcel(): string
    {
        $this->checkColumnWidth();
        ob_start();
        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
        $excelFile = ob_get_clean();

        return $excelFile;
    }
}