<?php

namespace App\Pdf;

use TCPDF;
use App\Helpers\DateUtil;

// https://tcpdf.org/
class Speeches
{
    private TCPDF $_pdf;
    private array $_speechData = [];
    private string $_dtStart;
    private string $_dtEnd;
    private int $_lineNbr = 0;

    private const LINE_HEADER_HEIGHT = 10;
    private const LINE_ROW_HEIGHT = 8;
    private const COL_DATE_W = 11;
    private const COL_SPEECH_W = 89;
    private const COL_SPEAKER_W = 29;
    private const COL_CONGREGATION_W = 18;
    private const COL_PRESIDENT_W = 31;
    private const COL_READER_W = 28;

    public function __construct(
        array $speechData,
        string $dtStart,
        string $dtEnd
    ) {
        $this->_speechData = $speechData;
        $this->_dtStart = $dtStart;
        $this->_dtEnd = $dtEnd;
        $this->filterSpeechData();
    }

    public function getPdf(): TCPDF
    {
        // create new PDF document
        $this->_pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // remove default header/footer
        $this->_pdf->setPrintHeader(false);
        $this->_pdf->setPrintFooter(false);

        // set margins
        $this->_pdf->SetMargins(2, 2, 2);

        // add a page
        $this->_pdf->AddPage();

        // header
        $this->setHeaderBg();
        $this->_pdf->Cell(0, self::LINE_HEADER_HEIGHT, 'Discursos Públicos — Congregação Esperança ' . DateUtil::getDateYear($this->_dtStart), 1, 1, 'C', 1, '', 0);

        // lines
        $lastMonth = null;
        foreach ($this->_speechData as $data) {
            $rowMonth = DateUtil::getBrMonthShort($data['dbDate']);

            if ($lastMonth !== $rowMonth) {
                $this->printHeader($data);
            }

            // row
            $this->printRow($data);

            // last date
            $lastMonth = $rowMonth;
        }

        return $this->_pdf;
    }

    private function setFont(int $size, bool $bold=false): void
    {
        $bold = ($bold) ? 'B': '';
        $this->_pdf->SetFont('helvetica', $bold, $size);
    }

    private function setHeaderBg(): void
    {
        $this->setFont(20, true);
      
        // blue header
        // $this->_pdf->SetFillColor(222, 234, 246);
        // $this->_pdf->SetTextColor(47, 84, 150);
      
        $this->_pdf->SetFillColor(33, 65, 81);
        $this->_pdf->SetTextColor(255, 255, 255);
    }

    private function setSubHeaderBg(): void
    {
        $this->_pdf->SetTextColor(255, 255, 255);
        $this->_pdf->SetFillColor(63, 120, 143);
    }

    private function setEvenLineBg(): void
    {
        $this->_pdf->SetFillColor(255, 255, 255);
    }

    private function setOddLineBg(): void
    {
        $this->_pdf->SetFillColor(239, 239, 239);
    }

    private function setSpecialLineBg(): void
    {
        $this->_pdf->SetFillColor(216, 208, 160);
    }

    private function filterSpeechData(): void
    {
        array_multisort(
            array_column($this->_speechData, 'dbDate'),
            SORT_ASC,
            $this->_speechData
        );

        // filter dates
        $start = $this->_dtStart;
        $end = $this->_dtEnd;
        $this->_speechData = array_filter($this->_speechData, function($value) use ($start, $end) {
            return ($value['dbDate'] >= $start) && ($value['dbDate'] <= $end);
        });

        // reset key ID
        $this->_speechData = array_values($this->_speechData);
    }

    private function printHeader(array $data): void
    {
        $date = $data['dbDate'] ?? null;
        $brMonthShort = DateUtil::getBrMonthShort($date);

        // green
        // $this->_pdf->SetTextColor(0, 0, 0);
        // $this->_pdf->SetFillColor(164, 202, 175);
      
      	$this->setSubHeaderBg();
        $this->setFont(17, true);

        $this->_pdf->Cell(self::COL_DATE_W, self::LINE_ROW_HEIGHT, $brMonthShort, 1, 0, 'C', 1, '', 0);
        $this->_pdf->Cell(self::COL_SPEECH_W, self::LINE_ROW_HEIGHT, 'Tema', 1, 0, 'C', 1, '', 0);
        $this->_pdf->Cell(self::COL_SPEAKER_W, self::LINE_ROW_HEIGHT, 'Orador', 1, 0, 'C', 1, '', 0);
        $this->_pdf->Cell(self::COL_CONGREGATION_W, self::LINE_ROW_HEIGHT, 'Cong.', 1, 0, 'C', 1, '', 0);
        $this->_pdf->Cell(self::COL_PRESIDENT_W, self::LINE_ROW_HEIGHT, 'Presidente', 1, 0, 'C', 1, '', 0);
        $this->_pdf->Cell(self::COL_READER_W, self::LINE_ROW_HEIGHT, 'Leitor', 1, 0, 'C', 1, '', 0);
        $this->_pdf->Ln(self::LINE_ROW_HEIGHT);

        $this->_lineNbr++;
    }

    private function isSpecialEvent(string $speech): bool
    {
        return $speech !== '' && preg_match('~[0-9]+~', $speech) <> 1;
    }

    private function printRow(array $data): void
    {
        $this->setFont(13, false);
        $this->_pdf->SetTextColor(0, 0, 0);
        $speech = $data['speech'] ?? '';

        // line color
        if ($this->isSpecialEvent($speech)) {
            // special event
            $this->setSpecialLineBg();
        } elseif ($this->_lineNbr % 2 == 0) {
            // even line
            $this->setEvenLineBg();
        } else {
            // odd line
            $this->setOddLineBg();
        }

        // cols
        $date = DateUtil::getDateDay($data['dbDate']);

        // calculate row height
        $rowHeight = 0;
        foreach ([
            $date => self::COL_DATE_W,
            $speech => self::COL_SPEECH_W,
            $data['speaker'] => self::COL_SPEAKER_W,
            $data['congregation'] => self::COL_CONGREGATION_W,
            $data['president'] => self::COL_PRESIDENT_W,
            $data['reader'] => self::COL_READER_W
        ] as $string => $width) {
            $height = $this->_pdf->getStringHeight($width, $string, true);
            if ($height > $rowHeight) {
                $rowHeight = $height;
            }
        }
        $rowHeight *= 1.1;
        // ====================

        $this->_pdf->MultiCell(self::COL_DATE_W, $rowHeight, $date, 1, 'C', 1, 0, '', '', true, 0, false, true);
        $this->_pdf->MultiCell(self::COL_SPEECH_W, $rowHeight, $speech, 1, 'C', 1, 0, '', '', true, 0, false, true);
        $this->_pdf->MultiCell(self::COL_SPEAKER_W, $rowHeight, $data['speaker'], 1, 'C', 1, 0, '', '', true, 0, false, true);
        $this->_pdf->MultiCell(self::COL_CONGREGATION_W, $rowHeight, $data['congregation'], 1, 'C', 1, 0, '', '', true, 0, false, true);
        $this->_pdf->MultiCell(self::COL_PRESIDENT_W, $rowHeight, $data['president'], 1, 'C', 1, 0, '', '', true, 0, false, true);
        $this->_pdf->MultiCell(self::COL_READER_W, $rowHeight, $data['reader'], 1, 'C', 1, 0, '', '', true, 0, false, true);
        $this->_pdf->Ln($rowHeight);

        $this->_lineNbr++;
    }
}