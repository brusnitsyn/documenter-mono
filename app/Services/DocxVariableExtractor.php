<?php

namespace App\Services;

use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;

class DocxVariableExtractor
{
    public function extractVariables($docxPath): array
    {
        $phpWord = IOFactory::load($docxPath);
        $variables = [];
        $textBuffer = '';

        foreach ($phpWord->getSections() as $section) {
            $this->extractFromSection($section, $variables, $textBuffer);
        }

        // Проверяем остаток в буфере
        $this->extractFromText($textBuffer, $variables);

        // Убираем дубликаты по полю 'name'
        $uniqueVariables = [];
        $seenNames = [];

        foreach ($variables as $variable) {
            if (!in_array($variable['name'], $seenNames)) {
                $uniqueVariables[] = $variable;
                $seenNames[] = $variable['name'];
            }
        }

        return $uniqueVariables;
    }

    private function extractFromSection($section, array &$variables, string &$textBuffer): void
    {
        foreach ($section->getElements() as $element) {
            $this->extractFromElement($element, $variables, $textBuffer);
        }
    }

    private function extractFromElement($element, array &$variables, string &$textBuffer): void
    {
        $elementType = get_class($element);

        switch ($elementType) {
            case 'PhpOffice\PhpWord\Element\TextRun':
                $this->extractFromTextRun($element, $variables, $textBuffer);
                break;

            case 'PhpOffice\PhpWord\Element\Text':
                $textBuffer .= $element->getText();
                $this->extractFromText($textBuffer, $variables);
                break;

            case 'PhpOffice\PhpWord\Element\Table':
                $this->extractFromTable($element, $variables, $textBuffer);
                break;

            case 'PhpOffice\PhpWord\Element\Header':
            case 'PhpOffice\PhpWord\Element\Footer':
                $this->extractFromSection($element, $variables, $textBuffer);
                break;

            default:
                // Сбрасываем буфер при смене типа элемента
                $textBuffer = '';
                break;
        }
    }

    private function extractFromTextRun($textRun, array &$variables, string &$textBuffer): void
    {
        foreach ($textRun->getElements() as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                $textBuffer .= $element->getText();
            }
        }

        // Проверяем буфер после каждого TextRun
        $this->extractFromText($textBuffer, $variables);
        $textBuffer = ''; // Сбрасываем буфер после TextRun
    }

    private function extractFromTable($table, array &$variables, string &$textBuffer): void
    {
        foreach ($table->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                $cellBuffer = '';
                foreach ($cell->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                        $cellBuffer .= $element->getText();
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        foreach ($element->getElements() as $textElement) {
                            if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                $cellBuffer .= $textElement->getText();
                            }
                        }
                    }
                }
                $this->extractFromText($cellBuffer, $variables);
            }
        }
    }

    private function extractFromText(string $text, array &$variables): void
    {
        // Ищем переменные в формате ${variable_name}
        preg_match_all('/\$\{\s*([a-zA-Zа-яА-ЯёЁ0-9_]+)\s*\}/u', $text, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[0] as $index => $fullMatch) {
                $variables[] = [
                    'name' => $fullMatch,        // Полное выражение с ${}
                    'label' => $matches[1][$index] // Только содержимое внутри {}
                ];
            }
        }
    }

    // Альтернативный метод: чтение напрямую из XML
    public function extractVariablesFromXml($docxPath): array
    {
        $variables = [];

        // Временная распаковка DOCX
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) === TRUE) {
            // Читаем document.xml
            $documentXml = $zip->getFromName('word/document.xml');

            if ($documentXml) {
                // Ищем все текстовые узлы
                preg_match_all('/(\{\{\s*[a-zA-Zа-яА-ЯёЁ0-9_]+\s*\}\})/u', $documentXml, $matches);

                foreach ($matches[0] as $match) {
                    // Извлекаем имя переменной
                    preg_match('/\{\{\s*([a-zA-Zа-яА-ЯёЁ0-9_]+)\s*\}\}/u', $match, $varMatches);
                    if (isset($varMatches[1])) {
                        $variables[] = $varMatches[1];
                    }
                }
            }

            // Также проверяем headers и footers
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (preg_match('/word\/header\d+\.xml/', $filename) ||
                    preg_match('/word\/footer\d+\.xml/', $filename)) {
                    $content = $zip->getFromName($filename);
                    preg_match_all('/(\{\{\s*[a-zA-Zа-яА-ЯёЁ0-9_]+\s*\}\})/u', $content, $matches);

                    foreach ($matches[0] as $match) {
                        preg_match('/\{\{\s*([a-zA-Zа-яА-ЯёЁ0-9_]+)\s*\}\}/u', $match, $varMatches);
                        if (isset($varMatches[1])) {
                            $variables[] = $varMatches[1];
                        }
                    }
                }
            }

            $zip->close();
        }

        return array_unique($variables);
    }

    // Комбинированный метод
    public function extractVariablesCombined($docxPath): array
    {
        $variables1 = $this->extractVariables($docxPath);
        $variables2 = $this->extractVariablesFromXml($docxPath);

        return array_unique(array_merge($variables1, $variables2));
    }

    public function validateVariables(array $variables): array
    {
        $validated = [];

        foreach ($variables as $variable) {
            if (preg_match('/^[a-zA-Zа-яА-ЯёЁ0-9_]+$/u', $variable)) {
                $validated[] = $variable;
            }
        }

        return array_unique($validated);
    }

    public function getVariablesWithExamples($docxPath): array
    {
        $variables = $this->extractVariablesCombined($docxPath);
        $result = [];

        foreach ($variables as $variable) {
            $result[$variable] = [
                'name' => $variable,
                'human_name' => $this->makeHumanReadable($variable),
                'example' => $this->generateExample($variable),
                'type' => $this->detectType($variable)
            ];
        }

        return $result;
    }

    private function makeHumanReadable(string $variable): string
    {
        $readable = str_replace('_', ' ', $variable);
        $readable = mb_strtolower($readable, 'UTF-8');
        $readable = mb_convert_case($readable, MB_CASE_TITLE, 'UTF-8');

        return $readable;
    }

    private function generateExample(string $variable): string
    {
        $examples = [
            'name' => 'Иван Иванов',
            'date' => '15.01.2024',
            'number' => '123-2024',
            'company' => 'ООО "Рога и копыта"',
            'address' => 'г. Москва, ул. Примерная, д. 1',
            'amount' => '100 000 руб.',
            'quantity' => '5',
            'price' => '20 000 руб.'
        ];

        foreach ($examples as $key => $example) {
            if (stripos($variable, $key) !== false) {
                return $example;
            }
        }

        return 'Пример значения';
    }

    private function detectType(string $variable): string
    {
        if (stripos($variable, 'date') !== false) return 'date';
        if (stripos($variable, 'amount') !== false || stripos($variable, 'price') !== false) return 'money';
        if (stripos($variable, 'quantity') !== false || stripos($variable, 'number') !== false) return 'number';
        if (stripos($variable, 'list') !== false || stripos($variable, 'items') !== false) return 'array';

        return 'text';
    }
}
