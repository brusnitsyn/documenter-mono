<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Services\DocxParser;
use App\Services\DocxVariableExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PhpOffice\PhpWord\IOFactory;

class DocImportController extends Controller
{
    public function store(Request $request, DocxParser $parser)
    {
//        dd($request->files);

        $data = $request->validate([
            'file' => 'required|file|mimes:docx|max:10240',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'variables' => 'nullable|array',
        ]);

        $file = $request->file('file');
        $templateFolderName = md5(uniqid(rand(), true));
        $templateFileName = 'source.' . $file->getClientOriginalExtension();
        $previewFileName = 'preview.html';
        $laravelPath = 'app/private/templates/' . $templateFolderName;
        $templateDir = storage_path($laravelPath);
        $templatePath = $file->move($templateDir, $templateFileName);
        $previewPath = storage_path("{$laravelPath}/{$previewFileName}");

        $escapedTemplateDir = escapeshellarg($templateDir);

        // Команда конвертации
        $command = "cd $escapedTemplateDir && pandoc -f docx -t html5 $templateFileName -o $previewPath --self-contained";
//        $command = "cd {$escapedTemplateDir} && libreoffice --headless --convert-to html {$templateFileName}";

        $result = Process::timeout(300)->run($command);

        \Log::info('Convert DOCX to HTML output: ' . $result->output());
        \Log::info('Convert DOCX to HTML error: ' . $result->errorOutput());

        if (!$result->successful()) {
            throw new \Exception('LibreOffice conversion failed: ' . $result->errorOutput());
        }

        $hasConverted = File::exists($templateDir. '/' . $previewFileName);

        $htmlContent = file_get_contents($templateDir. '/' . $previewFileName);
        $cleanedHtml = $this->cleanHtml($htmlContent);

        $template = DocumentTemplate::create([
            'name' => $data['name'] ?? 'Тест',
            'description' => $data['description'],
            'content' => $cleanedHtml,
            'variables' => $data['variables'] ?? [],
            'source_path' => $laravelPath . '/' . $templateFileName,
        ]);

//        try {
//            $file = $request->file('doc_file');
//
//            $file->store('templates/source');
//
//            $phpword = IOFactory::createReader()->load($request->doc_file);
//            $phpwriter = IOFactory::createWriter($phpword, 'HTML');
//
//            // Сохраняем во временный файл
//            $tempHtmlPath = storage_path('temp_' . uniqid() . '.html');
//            $phpwriter->save($tempHtmlPath);
//
//            // Загружаем HTML и извлекаем содержимое body
//            $dom = new \DOMDocument();
//            @$dom->loadHTMLFile($tempHtmlPath);
//
//            $pages = [];
//            $body = $dom->getElementsByTagName('body')->item(0);
//            if ($body) {
//                $currentPage = 1;
//                $currentPageContent = '';
//
//                foreach ($body->childNodes as $child) {
//                    // Проверяем, является ли элемент div с классом Page[n]
//                    if ($child->nodeType === XML_ELEMENT_NODE &&
//                        $child->nodeName === 'div' &&
//                        preg_match('/^Page(\d+)$/', $child->getAttribute('class'), $matches)) {
//
//                        // Сохраняем предыдущую страницу, если есть контент
//                        if (!empty(trim($currentPageContent))) {
//                            $pages[] = trim($currentPageContent);
//                        }
//
//                        // Начинаем новую страницу
//                        $currentPage = (int)$matches[1];
//                        $currentPageContent = '';
//                        continue;
//                    }
//
//                    // Добавляем HTML контент к текущей странице
//                    $currentPageContent .= $dom->saveHTML($child);
//                }
//
//                // Добавляем последнюю страницу
//                if (!empty(trim($currentPageContent))) {
//                    $pages[] = trim($currentPageContent);
//                }
//            }
//
//            // Удаляем временный файл
//            unlink($tempHtmlPath);
//
//            $template = DocumentTemplate::create([
//                'name' => $request->name ?? 'Тест',
//                'description' => $request->description,
//                'content' => $pages,
//                'variables_config' => $templateData['variables_config'] ?? [],
//                'source' => 'doc_import'
//            ]);
//
//            return redirect()->route('templates.edit', $template->id)
//                ->with('success', 'Шаблон успешно импортирован!');
//
//        } catch (\Exception $e) {
//            return back()->with('error', 'Ошибка импорта: ' . $e->getMessage());
//        }
    }

    private function cleanHtml($html) : string
    {
        // Убираем ненужные теги и атрибуты
        $html = preg_replace('/<!DOCTYPE[^>]*>/', '', $html);
        $html = preg_replace('/<html[^>]*>/', '', $html);
        $html = preg_replace('/<\/html>/', '', $html);
        $html = preg_replace('/<head>.*?<\/head>/si', '', $html);
        $html = preg_replace('/<body[^>]*>/', '', $html);
        $html = preg_replace('/<\/body>/', '', $html);

        // Убираем пустые теги и лишние пробелы
        $html = preg_replace('/<p[^>]*>(\s|&nbsp;)*<\/p>/', '', $html);
        $html = preg_replace('/<br\s*\/?>\s*<br\s*\/?>/', '<br>', $html);

        // Очищаем ссылки ConsultantPlus
        $html = preg_replace('/<a[^>]*consultantplus[^>]*>([^<]*)<\/a>/', '$1', $html);

        // Упрощаем теги font
        $html = preg_replace('/<font[^>]*face="([^"]*)"[^>]*>/', '<span style="font-family: $1">', $html);
//        $html = preg_replace('/<font[^>]*size="([^"]*)"[^>]*>/', '<span style="font-size: $1pt">', $html);
        $html = preg_replace('/<font[^>]*color="([^"]*)"[^>]*>/', '<span style="color: $1">', $html);
        $html = str_replace('</font>', '</span>', $html);

        // Обрабатываем вложенные font теги
        $html = preg_replace('/<span[^>]*><span[^>]*>/', '<span>', $html);
        $html = preg_replace('/<\/span><\/span>/', '</span>', $html);

        // Заменяем закладки и якоря
        $html = preg_replace('/<a name="[^"]*"><\/a>/', '', $html);

        // Обрабатываем шаблонные переменные {{ }}
        $html = preg_replace('/<span lang="en-US"><b>\{\{<\/b><\/span>/', '{{', $html);
        $html = preg_replace('/<span lang="ru-RU"><b>([^<]*)<\/b><\/span><span lang="en-US"><b>\}\}<\/b><\/span>/', '$1}}', $html);

        // Улучшаем таблицы
        $html = preg_replace('/<table[^>]*>/', '<table class="docx-table">', $html);
        $html = preg_replace('/<td[^>]*>/', '<td class="docx-td">', $html);
        $html = preg_replace('/<th[^>]*>/', '<th class="docx-th">', $html);

        // Убираем лишние классы western
        $html = str_replace('class="western"', '', $html);

        // Стили для красивого отображения
        $styles = '
            <style>
                .libreoffice-preview {
                    font-family: "Times New Roman", serif;
                    line-height: 1.2;
                    padding: 2cm 1.5cm 2cm 3cm;
                    background: white;
                    max-width: 210mm;
                    margin: 0 auto;
                    color: #00000a;
                    font-size: 12pt;
                }

                .libreoffice-preview p {
//                    margin: 12px 0;
                    text-align: justify;
                }

                .libreoffice-preview p.align-center {
                    text-align: center;
                }

                .libreoffice-preview p.align-left {
                    text-align: left;
                }

                .libreoffice-preview p.align-right {
                    text-align: right;
                }

                .libreoffice-preview b, .libreoffice-preview strong {
                    font-weight: bold;
                }

                .libreoffice-preview i, .libreoffice-preview em {
                    font-style: italic;
                }

                .libreoffice-preview u {
                    text-decoration: underline;
                }

                /* Таблицы */
                .docx-table {
                    border-collapse: collapse;
                    width: 100%;
                    margin: 15px 0;
                    border: 1px solid #000000;
                }

                .docx-td, .docx-th {
                    border: 1px solid #000000;
                    padding: 8px 12px;
                    vertical-align: top;
                }

                .docx-th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                    text-align: center;
                }

                /* Заголовки */
                .libreoffice-preview h1, .libreoffice-preview h2, .libreoffice-preview h3 {
                    text-align: center;
                    margin: 20px 0;
                    font-weight: bold;
                }

                /* Отступы для списков */
                .libreoffice-preview ul, .libreoffice-preview ol {
                    margin: 10px 0;
                    padding-left: 30px;
                }

                .libreoffice-preview li {
                    margin: 5px 0;
                }

                /* Шаблонные переменные */
                .template-var {
                    background-color: #fff3cd;
                    border: 1px dashed #ffc107;
                    padding: 2px 4px;
                    border-radius: 3px;
                    font-weight: bold;
                    color: #856404;
                }

                /* Подписи */
                .signature {
                    margin-top: 40px;
                    border-top: 1px solid #000;
                    padding-top: 10px;
                }

                /* Реквизиты */
                .requisites {
                    font-size: 10pt;
                    line-height: 1.4;
                }

                /* Отступы для абзацев с отступами */
                .indent-1 { text-indent: 1.25cm; }
                .indent-095 { text-indent: 0.95cm; }
                .indent-1cm { text-indent: 1cm; }

                /* Отступы */
                .margin-bottom-035 { margin-bottom: 0.35cm; }
                .margin-top-021 { margin-top: 0.21cm; }
            </style>
        ';

        // Добавляем классы для выравнивания
        $html = preg_replace('/<p[^>]*align="center"[^>]*>/', '<p class="align-center">', $html);
        $html = preg_replace('/<p[^>]*align="left"[^>]*>/', '<p class="align-left">', $html);
        $html = preg_replace('/<p[^>]*align="right"[^>]*>/', '<p class="align-right">', $html);

        // Добавляем классы для отступов
        $html = preg_replace('/style="[^"]*text-indent:\s*1\.25cm[^"]*"/', 'class="indent-1"', $html);
        $html = preg_replace('/style="[^"]*text-indent:\s*0\.95cm[^"]*"/', 'class="indent-095"', $html);
        $html = preg_replace('/style="[^"]*text-indent:\s*1cm[^"]*"/', 'class="indent-1cm"', $html);

        // Обрабатываем шаблонные переменные
        $html = preg_replace('/\{\{([^}]+)\}\}/', '<span class="template-var">{{$1}}</span>', $html);

        return '<div class="libreoffice-preview">' . $html . '</div>' . $styles;
    }

    public function previewVariables(Request $request, DocxVariableExtractor $extractor)
    {
        $rules = [
            'doc_file' => 'required|file|mimes:docx,doc|max:10240'
        ];
        $messages = [
            'doc_file.required' => 'Вы не приложили документ'
        ];

        $validator = \Validator::make($request->all(), $rules, $messages);

        $validator->validate();

        try {
            $file = $request->file('doc_file');
            $tempPath = $file->getRealPath();

            $variables = $extractor->extractVariables($tempPath);

            return response()->json([
                'success' => true,
                'variables' => $variables,
                'count' => count($variables)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при анализе файла: ' . $e->getMessage()
            ], 500);
        }
    }
}
