<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Services\DocxParser;
use App\Services\PageBreaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use PhpOffice\PhpWord\IOFactory;

class DocumentController extends Controller
{
    public function show($id, Request $request)
    {
        $template = DocumentTemplate::find($id);

        return Inertia::render('ContractGenerator', [
            'template' => $template
        ]);
    }

    /**
     * Предпросмотр документа с подставленными значениями
     */
    public function preview(Request $request, $id)
    {
        $request->validate([
            'variables' => 'nullable|array'
        ]);

        try {
            $template = DocumentTemplate::findOrFail($id);

            // Генерируем DOCX с подставленными значениями
            $docxPath = $template->generateDocument($request->variables);

            // Конвертируем в PDF
            $pdfPath = $template->convertToPdf($docxPath);

            // Регистрируем функцию для удаления после завершения
            register_shutdown_function(function () use ($docxPath, $pdfPath) {
                File::delete($docxPath);
                File::delete($pdfPath);
            });

            // Отдаем PDF
            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview.pdf"'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Скачивание готового документа
     */
    public function download(Request $request, $id)
    {
        $request->validate([
            'variables' => 'nullable|array'
        ]);

        try {
            $template = DocumentTemplate::findOrFail($id);
            $docxPath = $template->generateDocument($request->variables);

            return response()->download($docxPath,
                $template->name . '.docx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'Content-Disposition' => 'attachment; filename="' . $template->name . '.docx"'
                ]
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
