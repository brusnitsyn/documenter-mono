<?php

namespace App\Models;

use App\Services\DocxTemplateProcessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'content',
        'variables',
        'source_path'
    ];

    protected $casts = [
        'variables' => 'array'
    ];

    /**
     * Подстановка значений в DOCX шаблон
     */
    public function generateDocument($data)
    {
        $tempDir = storage_path('app/temp/' . uniqid());
        mkdir($tempDir, 0755, true);

        // Копируем исходный шаблон
        $templatePath = $tempDir . '/template.docx';
        copy(storage_path($this->source_path), $templatePath);

        $docx = new DocxTemplateProcessor();

        // Подставляем значения
        $changedDocxPath = $docx->processWithPhpWord($templatePath, $data);

        // Возвращаем путь к сгенерированному файлу
        return $changedDocxPath;
    }

    /**
     * Конвертация в PDF для предпросмотра
     */
    public function convertToPdf($docxPath)
    {
        $pdfPath = str_replace('.docx', '.pdf', $docxPath);

        $command = "libreoffice --headless --convert-to pdf --outdir " .
            escapeshellarg(dirname($pdfPath)) . " " .
            escapeshellarg($docxPath);

        $result = shell_exec($command . " 2>&1");

        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF conversion failed: " . $result);
        }

        return $pdfPath;
    }
}
