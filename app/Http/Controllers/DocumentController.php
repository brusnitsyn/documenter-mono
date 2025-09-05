<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function show($id)
    {
        $template = DocumentTemplate::findOrFail($id);

        return Inertia::render('ContractGenerator', [
            'template' => $template,
            'initialFormData' => $this->extractVariables($template->content)
        ]);
    }

    public function updatePreview(Request $request)
    {
        $template = DocumentTemplate::findOrFail($request->template_id);
        $formData = $request->form_data;

        // Обновляем структуру документа с учетом выбранных значений
        $updatedStructure = $this->applyFormData(
            $template->content['structure'],
            $formData
        );

        return response()->json([
            'success' => true,
            'updatedStructure' => $updatedStructure,
            'compiledText' => $this->generatePlainText($updatedStructure)
        ]);
    }

    private function formatLabel($key)
    {
        return ucfirst(str_replace('_', ' ', $key));
    }

    private function extractVariables($content)
    {
        $variables = [];

        foreach ($content['structure'] as $section) {
            foreach ($section['elements'] as $element) {
                if (!empty($element['variables'])) {
                    foreach ($element['variables'] as $variable) {
                        if (is_array($variable)) {
                            // Переменная с опциями выбора
                            $variables[$variable['name']] = [
                                'type' => $variable['type'] ?? 'text',
                                'value' => $variable['default'] ?? '',
                                'options' => $variable['options'] ?? [],
                                'label' => $variable['label'] ?? $this->formatLabel($variable['name'])
                            ];
                        } else {
                            // Простая текстовая переменная
                            $variables[$variable] = [
                                'type' => 'text',
                                'value' => '',
                                'label' => $this->formatLabel($variable)
                            ];
                        }
                    }
                }
            }
        }

        return $variables;
    }

    private function applyFormData($structure, $formData)
    {
        foreach ($structure as &$section) {
            // Проверяем условия для секции
            if (isset($section['conditions'])) {
                $section['enabled'] = $this->evaluateConditions($section['conditions'], $formData);
            }

            if (!$section['enabled']) continue;

            foreach ($section['elements'] as &$element) {
                // Проверяем условия для элемента
                if (isset($element['conditions'])) {
                    $element['enabled'] = $this->evaluateConditions($element['conditions'], $formData);
                }

//                if (!$element['enabled']) continue;

                if ($element['type'] === 'html' && !empty($element['variables'])) {
                    $element['compiledContent'] = $this->compileHtml(
                        $element['content'],
                        $formData
                    );
                }
            }
        }

        return $structure;
    }

    private function evaluateConditions($conditions, $formData)
    {
        // Простая реализация проверки условий
        foreach ($conditions as $field => $expectedValue) {
            if (($formData[$field] ?? '') !== $expectedValue) {
                return false;
            }
        }
        return true;
    }

    private function compileHtml($html, $formData)
    {
        foreach ($formData as $key => $value) {
            if (!empty($value) && is_string($value)) {
                // 1. Заменяем простые плейсхолдеры
                $html = str_replace("{{{$key}}}", $value, $html);

                // 2. Заменяем span-плейсхолдеры с помощью preg_replace
                $pattern = '/<span[^>]*data-variable="' . preg_quote($key, '/') . '"[^>]*>.*?<\/span>/';
                $html = preg_replace($pattern, $value, $html);
            }
        }

        return $html;
    }

    private function generatePlainText($structure)
    {
        $text = '';

        foreach ($structure as $section) {
            foreach ($section['elements'] as $element) {
                if (in_array($element['type'], ['heading', 'html'])) {
                    $content = $element['compiledContent'] ?? $element['content'];
                    $text .= strip_tags($content) . "\n\n";
                }
            }
        }

        return $text;
    }
}
