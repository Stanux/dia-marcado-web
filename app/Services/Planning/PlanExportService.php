<?php

namespace App\Services\Planning;

use App\Models\WeddingPlan;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PlanExportService
{
    public function download(WeddingPlan $plan)
    {
        $spreadsheet = $this->buildSpreadsheet($plan);
        $filename = 'planejamento-' . $plan->id . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename);
    }

    protected function buildSpreadsheet(WeddingPlan $plan): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planejamento');

        $sheet->setCellValue('A1', 'Planejamento: ' . $plan->title);
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = [
            'Tarefa',
            'Descrição',
            'Categoria',
            'Responsável',
            'Status',
            'Início',
            'Data Limite',
            'Prioridade',
            'Valor Estimado',
            'Valor Real',
        ];

        $headerRow = 3;
        $column = 'A';

        foreach ($headers as $header) {
            $sheet->setCellValue($column . $headerRow, $header);
            $sheet->getStyle($column . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($column . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $column++;
        }

        $row = $headerRow + 1;

        foreach ($plan->tasks()->with(['category', 'assignedUser'])->orderBy('due_date')->get() as $task) {
            $sheet->setCellValue('A' . $row, $task->title);
            $sheet->setCellValue('B' . $row, $task->description);
            $sheet->setCellValue('C' . $row, $task->category?->name);
            $sheet->setCellValue('D' . $row, $task->assignedUser?->name);
            $sheet->setCellValue('E' . $row, $this->mapStatus($task->effective_status));
            $sheet->setCellValue('F' . $row, $task->start_date?->format('d/m/Y'));
            $sheet->setCellValue('G' . $row, $task->due_date?->format('d/m/Y'));
            $sheet->setCellValue('H' . $row, $this->mapPriority($task->priority));
            $sheet->setCellValue('I' . $row, $task->estimated_value);
            $sheet->setCellValue('J' . $row, $task->actual_value);
            $row++;
        }

        $summaryRow = $row + 1;
        $sheet->setCellValue('H' . $summaryRow, 'Totais');
        $sheet->setCellValue('I' . $summaryRow, $plan->tasks()->sum('estimated_value'));
        $sheet->setCellValue('J' . $summaryRow, $plan->tasks()->sum('actual_value'));
        $sheet->getStyle('H' . $summaryRow . ':J' . $summaryRow)->getFont()->setBold(true);

        $sheet->getStyle('I' . ($headerRow + 1) . ':J' . $summaryRow)
            ->getNumberFormat()
            ->setFormatCode('"R$" #,##0.00');

        foreach (range(1, 10) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'overdue' => 'Atrasada',
            'pending' => 'Pendente',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluída',
            'cancelled' => 'Cancelada',
            default => $status,
        };
    }

    protected function mapPriority(?string $priority): string
    {
        return match ($priority) {
            'low' => 'Baixa',
            'high' => 'Alta',
            default => 'Média',
        };
    }
}
