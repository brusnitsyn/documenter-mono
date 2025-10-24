<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;

class DocxTemplateProcessor
{
    /**
     * Подстановка значений в DOCX файл
     */
    public function processWithPhpWord($templatePath, $data): string
    {
        try {
            $templateProcessor = new TemplateProcessor($templatePath);

            foreach ($data as $value) {
                if (array_key_exists('value', $value)) {
                    if (isset($value['value'])) {
                        $templateProcessor->setValue($value['name'], $value['value']);
                    } else {
                        $templateProcessor->setValue($value['value'], $value['value']);
                    }
                }
            }

            $outputPath = storage_path('app/temp/' . uniqid() . '.docx');
            $templateProcessor->saveAs($outputPath);

            return $outputPath;
        } finally {
            $this->cleanupTemplate($templatePath);
        }
    }

    protected function cleanupTemplate($templatePath): void
    {
        try {
            if (file_exists($templatePath)) {
                unlink($templatePath);
            }

            $templateDir = dirname($templatePath);
            if (is_dir($templateDir) && $this->isDirEmpty($templateDir)) {
                rmdir($templateDir);
            }
        } catch (\Exception $e) {
            \Log::warning("Template cleanup warning: " . $e->getMessage());
        }
    }

    protected function isDirEmpty($dir): bool
    {
        return count(scandir($dir)) == 2; // только . и ..
    }
}
