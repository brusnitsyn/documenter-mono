<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
//use DOMDocument;
use Exception;

class DocxParser
{
    public function parse($filePath)
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $sections = $phpWord->getSections();

            $template = [
                'metadata' => [
                    'title' => basename($filePath, '.docx'),
                    'created_at' => now()->toISOString(),
                    'source' => 'docx',
                    'total_pages' => 0
                ],
                'structure' => []
            ];

            $sectionElements = [];
            foreach ($sections as $sectionIndex => $section) {
                foreach ($section->getElements() as $elementIndex => $element) {
//                    $sectionElements[] = $this->parseElement($element);
                    array_push($sectionElements, $this->parseElement($element, $elementIndex));
                }
            }

            $template['structure']['elements'] = $sectionElements;

//            dd($template);

            // Извлекаем переменные
            $template['variables_config'] = $this->extractVariables($template['structure']);

            return $template;

        } catch (Exception $e) {
            \Log::error("Ошибка парсинга DOCX: " . $e->getMessage(), $e->getTrace());
        }
    }

    private function parseElement($element, $elementIndex = 0)
    {
        $parsedElements = [];

        $elementType = get_class($element);

        switch ($elementType) {
            case 'PhpOffice\PhpWord\Element\Text':
                $parsedElements = $this->parseTextElement($element, $elementIndex++);
                break;

            case 'PhpOffice\PhpWord\Element\TextRun':
                $textRunElements = $this->parseTextRun($element, $elementIndex);

                // Получаем стиль параграфа для TextRun
                $paragraphStyle = $element->getParagraphStyle();

                // Если TextRun содержит только один элемент, добавляем его напрямую
                if (count($textRunElements) === 1) {
                    $textRunElements[0]['style'] = $this->parseParagraphStyle($paragraphStyle);
                    $isHeading = $this->isTextRunHeading($textRunElements[0]);
                    $textRunElements[0]['is_heading'] = $isHeading;
                    $textRunElements[0]['type'] = $isHeading ? 'heading' : 'paragraph';

                    $parsedElements = $textRunElements[0];
                    $elementIndex++;
                } else {
                    // Иначе создаем контейнер TextRun
                    $parsedElements = [
                        'id' => 'textrun-' . $elementIndex++,
                        'type' => 'text_run',
                        'elements' => $textRunElements,
                        'style' => $this->parseParagraphStyle($paragraphStyle)
//                            'formatting' => $this->getTextRunFormatting($element)
                    ];
                }
                break;

            case 'PhpOffice\PhpWord\Element\TextBreak':
                $parsedElements = [
                    'id' => 'break-' . $elementIndex++,
                    'type' => 'line_break',
                    'content' => '<br>'
                ];
                break;

            case 'PhpOffice\PhpWord\Element\Title':
                $parsedElements = $this->parseTitleElement($element, $elementIndex++);
                break;

            case 'PhpOffice\PhpWord\Element\Table':
                $parsedElements = $this->parseTableElement($element, $elementIndex++);
                break;

//                case 'PhpOffice\PhpWord\Element\Image':
//                    $parsedElements[] = $this->parseImageElement($element, $elementIndex++);
//                    break;

            default:
                // Пропускаем неизвестные элементы
                break;
        }

        return $parsedElements;
    }

    private function getTextRunFormatting($textRun)
    {
        $style = $textRun->getParagraphStyle();
        return $this->parseStyle($style);
    }

    private function parseTextElement($element, $index)
    {
        $text = $element->getText();
        $style = $element->getFontStyle();
        // Ищем переменные в тексте (например: {{variable}} или [VARIABLE])
        $variables = $this->extractVariablesFromText($text);

        return [
            'id' => 'element-' . $index,
            'type' => empty($variables) ? 'paragraph' : 'variable',
            'content' => $text,
            'formatting' => $this->parseStyle($style),
            'variables' => $variables,
            'is_inline' => true
        ];
    }

    public function isTextRunHeading($element) {
        if ($element['formatting'] && $element['style']) {
            if ($element['formatting']['bold'] && $element['style']['align'] === 'center') {
                return true;
            }
        }

        return false;
    }

    public function parseParagraphStyle($style)
    {
        if (!$style) return null;

        $paragraphStyle = [];

        // Выравнивание
        if ($style->getAlignment()) {
            $alignment = $style->getAlignment();
            $paragraphStyle['align'] = $this->mapAlignment($alignment);
        }

        // Отступы
        if ($style->getIndentation()) {
            $indentation = $style->getIndentation();
            $paragraphStyle['indent'] = [
                'left' => $indentation->getLeft(),
                'right' => $indentation->getRight(),
                'firstLine' => ($indentation->getFirstLine() / 1440) * 96, //\PhpOffice\PhpWord\Shared\Converter::twip($indentation->getFirstLine())
            ];
        }

//        dd($style->getLineHeight());

        // Междустрочный интервал
        if ($style->getLineHeight()) {
            $paragraphStyle['lineHeight'] = $style->getLineHeight();
        }

        // Интервалы до и после
        if ($style->getSpaceBefore()) {
            $paragraphStyle['spaceBefore'] = $style->getSpaceBefore();
        }

        if ($style->getSpaceAfter()) {
            $paragraphStyle['spaceAfter'] = $style->getSpaceAfter();
        }

        // Табуляция
        if ($style->getTabs()) {
            $paragraphStyle['tabs'] = $style->getTabs();
        }

        return !empty($paragraphStyle) ? $paragraphStyle : null;
    }

    private function mapAlignment($alignment)
    {
        $mapping = [
            \PhpOffice\PhpWord\SimpleType\Jc::START => 'left',
            \PhpOffice\PhpWord\SimpleType\Jc::END => 'right',
            \PhpOffice\PhpWord\SimpleType\Jc::CENTER => 'center',
            \PhpOffice\PhpWord\SimpleType\Jc::BOTH => 'justify',
            \PhpOffice\PhpWord\SimpleType\Jc::DISTRIBUTE => 'distribute'
        ];

        return $mapping[$alignment] ?? 'left';
    }

    public function parseTextRun($textRun, $startIndex)
    {
        $elements = [];
        $index = $startIndex;

        foreach ($textRun->getElements() as $runElement) {
            if (get_class($runElement) === 'PhpOffice\PhpWord\Element\Text') {
                $elements[] = $this->parseTextElement($runElement, $index++);
            }
        }

        return $elements;
    }

    private function parseTitleElement($element, $index)
    {
        $text = $element->getText();
        $level = $element->getDepth() + 1;

        return [
            'id' => 'heading-' . $index,
            'type' => 'heading',
            'level' => $level,
            'content' => $text,
            'formatting' => [
                'bold' => true,
                'fontSize' => $this->getHeadingSize($level)
            ]
        ];
    }

    private function parseTableElement($table, $index)
    {
        $rows = [];
        $rowIndex = 0;
        $tableIndex = $index;

        foreach ($table->getRows() as $row) {
            $cells = [];
            $cellIndex = 0;
            foreach ($row->getCells() as $cell) {
                $styles = $this->getCellStyles($cell);
                $cellElements = [];
                foreach ($cell->getElements() as $cellElement) {
                    $cellElements[] = $this->parseElement($cellElement, $index);
                    $index++;
                }

                $cells[] = [
                    'id' => 'cell-' . $rowIndex . '-' . $cellIndex,
                    'elements' => $cellElements,
//                    'variables' => $this->extra($cellElements),
                    'style' => $styles,
                    'width' => ($cell->getWidth() / 1440) * 96
                ];
                $cellIndex++;
            }

            $rows[] = [
                'id' => 'row-' . $rowIndex,
                'cells' => $cells
            ];
            $rowIndex++;
        }

        return [
            'id' => 'table-' . $tableIndex,
            'type' => 'table',
            'rows' => $rows,
            'cols' => count($rows[0]['cells'] ?? []),
//            'content' => $this->generateTableHTML($rows)
        ];
    }

    private function getCellStyles($cell)
    {
        $style = $cell->getStyle();

        return [
            'borderTopSize' => $style->getBorderTopSize(),
            'borderTopColor' => $style->getBorderTopColor(),
            'borderTopStyle' => $style->getBorderTopStyle(),
            'borderLeftSize' => $style->getBorderLeftSize(),
            'borderLeftColor' => $style->getBorderLeftColor(),
            'borderLeftStyle' => $style->getBorderLeftStyle(),
            'borderRightSize' => $style->getBorderRightSize(),
            'borderRightColor' => $style->getBorderRightColor(),
            'borderRightStyle' => $style->getBorderRightStyle(),
            'borderBottomSize' => $style->getBorderBottomSize(),
            'borderBottomColor' => $style->getBorderBottomColor(),
            'borderBottomStyle' => $style->getBorderBottomStyle(),
        ];
    }

    private function parseImageElement($element, $index)
    {
        return [
            'id' => 'image-' . $index,
            'type' => 'image',
            'src' => $element->getSource(),
            'width' => $element->getWidth(),
            'height' => $element->getHeight(),
            'alt' => $element->getAlt() ?? 'Image'
        ];
    }

    private function parseStyle($style)
    {
        if (!$style) return [];

        return [
            'bold' => $style->isBold(),
            'italic' => $style->isItalic(),
            'underline' => $style->getUnderline(),
            'fontSize' => $style->getSize(),
            'fontColor' => $style->getColor(),
            'fontFamily' => $style->getName()
        ];
    }

    // Формирование HTML
    public function generateParagraphStyle($paragraphStyle)
    {
        if (empty($paragraphStyle)) {
            return '';
        }

        $styles = [];

        // Выравнивание
        if (!empty($paragraphStyle['align'])) {
            $styles[] = "text-align: {$paragraphStyle['align']}";
        }

        // Междустрочный интервал
        if (!empty($paragraphStyle['lineHeight'])) {
            $lineHeight = $paragraphStyle['lineHeight'];
            if (is_numeric($lineHeight)) {
                $styles[] = "line-height: {$lineHeight}";
            } else {
                $styles[] = "line-height: {$lineHeight}";
            }
        }

        // Отступы
        if (!empty($paragraphStyle['spaceBefore'])) {
            $styles[] = "margin-top: {$paragraphStyle['spaceBefore']}pt";
        }

        if (!empty($paragraphStyle['spaceAfter'])) {
            $styles[] = "margin-bottom: {$paragraphStyle['spaceAfter']}pt";
        }

        // Отступы первой строки
        if (!empty($paragraphStyle['indent'])) {
            $indent = $paragraphStyle['indent'];

            if (!empty($indent['left'])) {
                $styles[] = "margin-left: {$indent['left']}pt";
            }

            if (!empty($indent['right'])) {
                $styles[] = "margin-right: {$indent['right']}pt";
            }

            if (!empty($indent['firstLine'])) {
                $styles[] = "text-indent: {$indent['firstLine']}pt";
            }
        }

        // Табуляция
        if (!empty($paragraphStyle['tabs'])) {
            $tabStops = [];
            foreach ($paragraphStyle['tabs'] as $tab) {
                if (!empty($tab['position'])) {
                    $position = $tab['position'];
                    $type = $tab['type'] ?? 'left';
                    $tabStops[] = "{$position}pt {$type}";
                }
            }

            if (!empty($tabStops)) {
                $styles[] = "tab-stops: " . implode(', ', $tabStops);
            }
        }

        return implode('; ', $styles);
    }

    public function generateTextRunStyle($formatting)
    {
        if (empty($formatting)) {
            return '';
        }

        $styles = [];

        // Размер шрифта
        if (!empty($formatting['fontSize'])) {
            $fontSize = $formatting['fontSize'];
            if (is_numeric($fontSize)) {
                $styles[] = "font-size: {$fontSize}pt";
            } else {
                $styles[] = "font-size: {$fontSize}";
            }
        }

        // Цвет шрифта
        if (!empty($formatting['fontColor'])) {
            $styles[] = "color: {$formatting['fontColor']}";
        }

        // Цвет фона
        if (!empty($formatting['backgroundColor'])) {
            $styles[] = "background-color: {$formatting['backgroundColor']}";
        }

        // Шрифт
        if (!empty($formatting['fontFamily'])) {
            $styles[] = "font-family: '{$formatting['fontFamily']}'";
        }

        // Жирный текст
        if (!empty($formatting['bold']) && $formatting['bold']) {
            $styles[] = "font-weight: bold";
        }

        // Курсив
        if (!empty($formatting['italic']) && $formatting['italic']) {
            $styles[] = "font-style: italic";
        }

        // Подчеркивание
        if (!empty($formatting['underline']) && $formatting['underline']) {
            $styles[] = "text-decoration: underline";
        }

        // Зачеркивание
        if (!empty($formatting['strikethrough']) && $formatting['strikethrough']) {
            $styles[] = "text-decoration: line-through";
        }

        // Надстрочный индекс
        if (!empty($formatting['superscript']) && $formatting['superscript']) {
            $styles[] = "vertical-align: super";
            $styles[] = "font-size: smaller";
        }

        // Подстрочный индекс
        if (!empty($formatting['subscript']) && $formatting['subscript']) {
            $styles[] = "vertical-align: sub";
            $styles[] = "font-size: smaller";
        }

        // Тень текста
        if (!empty($formatting['shadow']) && $formatting['shadow']) {
            $styles[] = "text-shadow: 1px 1px 2px rgba(0,0,0,0.3)";
        }

        // Трансформация текста
        if (!empty($formatting['textTransform'])) {
            $styles[] = "text-transform: {$formatting['textTransform']}";
        }

        // Межбуквенный интервал
        if (!empty($formatting['letterSpacing'])) {
            $styles[] = "letter-spacing: {$formatting['letterSpacing']}pt";
        }

        // Межсловный интервал
        if (!empty($formatting['wordSpacing'])) {
            $styles[] = "word-spacing: {$formatting['wordSpacing']}pt";
        }

        return implode('; ', $styles);
    }

    public function replaceVariables($content, $formData)
    {
        return $content;
        if (empty($content)) {
            return $content;
        }

        // Заменяем плейсхолдеры вида {{variable}}
        foreach ($formData as $key => $value) {
            if (!empty($value)) {
                $content = str_replace("{{{$key}}}", $value, $content);
                $content = str_replace("[[{$key}]]", $value, $content);
                $content = str_replace("__{$key}__", $value, $content);
            }
        }

        // Заменяем span-плейсхолдеры с data-variable
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8"><div>' . $content . '</div>');

        $xpath = new DOMXPath($dom);
        $placeholders = $xpath->query('//*[@data-variable]');

        foreach ($placeholders as $placeholder) {
            $variableName = $placeholder->getAttribute('data-variable');
            $value = $formData[$variableName] ?? '';

            if (!empty($value)) {
                // Создаем текстовый узел с значением
                $textNode = $dom->createTextNode($value);
                $placeholder->parentNode->replaceChild($textNode, $placeholder);
            }
        }

        // Извлекаем HTML обратно
        $html = $dom->saveHTML();
        $html = preg_replace('/^<!DOCTYPE.*?>\n?/', '', $html);
        $html = preg_replace('/<\?xml encoding="UTF-8"\?>\n?/', '', $html);
        $html = preg_replace('/^<div>/', '', $html);
        $html = preg_replace('/<\/div>$/', '', $html);

        return trim($html);
    }



    private function extractVariablesFromText($text)
    {
        $variables = [];

        // Ищем шаблоны переменных: {{variable}}, [VARIABLE], ${variable}, etc.
        $patterns = [
            '/\{\{(\w+)\}\}/',
            '/\[(\w+)\]/',
            '/\$(\w+)/',
            '/%(\w+)%/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $match) {
                    $variables[] = $match;
                }
            }
        }

        return array_unique($variables);
    }

    public function extractVariables($structure)
    {
        $variables = [];

        foreach ($structure['elements'] as $element) {
            if (!empty($element['variables'])) {
                foreach ($element['variables'] as $varName) {
                    if (!isset($variables[$varName])) {
                        $variables[$varName] = [
                            'type' => 'text',
                            'label' => $this->formatVariableLabel($varName),
                            'default' => ''
                        ];
                    }
                }
            }

            if (!array_key_exists('type', $element)) dd($element);
            // Для таблиц
            if ($element['type'] === 'table' && !empty($element['rows'])) {
                foreach ($element['rows'] as $row) {
                    foreach ($row['cells'] as $cell) {
                        if (!empty($cell['variables'])) {
                            foreach ($cell['variables'] as $varName) {
                                if (!isset($variables[$varName])) {
                                    $variables[$varName] = [
                                        'type' => 'text',
                                        'label' => $this->formatVariableLabel($varName),
                                        'default' => ''
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $variables;
    }

    private function formatVariableLabel($varName)
    {
        return ucfirst(str_replace(['_', '-'], ' ', $varName));
    }

    private function getHeadingSize($level)
    {
        $sizes = [
            1 => '24pt',
            2 => '20pt',
            3 => '18pt',
            4 => '16pt',
            5 => '14pt',
            6 => '12pt'
        ];

        return $sizes[$level] ?? '16pt';
    }

    private function generateTableHTML($rows)
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin: 1em 0;">';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row['cells'] as $cell) {
                $html .= '<td style="border: 1px solid #000; padding: 8px;">';
                $html .= htmlspecialchars($cell['content']);
                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }

    // Метод для обработки загруженного файла
    public function parseUploadedFile($uploadedFile)
    {
        $tempPath = $uploadedFile->getRealPath();
        $extension = $uploadedFile->getClientOriginalExtension();

        if ($extension !== 'docx') {
            throw new Exception("Поддерживаются только файлы .docx");
        }

        return $this->parse($tempPath);
    }
}
