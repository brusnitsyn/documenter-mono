<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class PageBreaker
{
    private $maxPageHeight; // Максимальная высота страницы в px
    private $currentPageHeight;
    private $pages;
    private $currentPageContent;

    public function __construct($maxPageHeight = 1122) // 29.7cm * 37.8px/cm ≈ 1122px
    {
        $this->maxPageHeight = $maxPageHeight;
        $this->currentPageHeight = 0;
        $this->pages = [];
        $this->currentPageContent = '';
    }

    public function splitIntoPages($htmlContent)
    {
        if (empty($htmlContent)) {
            return [['content' => '', 'pageNumber' => 1]];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8"><div id="content">' . $htmlContent . '</div>');

        $this->pages = [];
        $this->currentPageHeight = 0;
        $this->currentPageContent = '';

        $body = $dom->getElementById('content');
        if ($body) {
            $this->processNode($body);
        }

        // Добавляем последнюю страницу
        if (!empty($this->currentPageContent)) {
            $this->addPage();
        }

        return $this->pages;
    }

    private function processNode($node)
    {
        $nodeName = strtolower($node->nodeName);

        // Элементы, которые нельзя разрывать
        $unbreakableElements = ['table', 'tr', 'img', 'pre', 'code'];

        if (in_array($nodeName, $unbreakableElements)) {
            $this->processUnbreakableElement($node);
        } else {
            $this->processBreakableElement($node);
        }
    }

    private function processUnbreakableElement($node)
    {
        $elementHtml = $this->getOuterHTML($node);
        $estimatedHeight = $this->estimateElementHeight($elementHtml);

        // Если элемент не помещается на текущую страницу
        if ($this->currentPageHeight + $estimatedHeight > $this->maxPageHeight && $this->currentPageHeight > 0) {
            $this->addPage();
        }

        $this->currentPageContent .= $elementHtml;
        $this->currentPageHeight += $estimatedHeight;
    }

    private function processBreakableElement($node)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $this->processTextNode($node);
            return;
        }

        // Для breakable элементов обрабатываем детей по отдельности
        foreach ($node->childNodes as $child) {
            $this->processNode($child);
        }

        // Добавляем закрывающий тег после обработки всех детей
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $this->currentPageContent .= '</' . $node->nodeName . '>';
        }
    }

    private function processTextNode($node)
    {
        $text = $node->textContent;
        $words = preg_split('/(\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($words as $word) {
            if (trim($word) === '') {
                $this->addContent($word, 4); // Примерная высота пробела
                continue;
            }

            $wordHeight = $this->estimateTextHeight($word);

            // Если слово не помещается на текущую страницу
            if ($this->currentPageHeight + $wordHeight > $this->maxPageHeight && $this->currentPageHeight > 0) {
                $this->addPage();
            }

            $this->addContent($word, $wordHeight);
        }
    }

    private function addContent($content, $height)
    {
        $this->currentPageContent .= $content;
        $this->currentPageHeight += $height;
    }

    private function addPage()
    {
        $this->pages[] = [
            'content' => $this->currentPageContent,
            'pageNumber' => count($this->pages) + 1,
            'height' => $this->currentPageHeight
        ];

        $this->currentPageContent = '';
        $this->currentPageHeight = 0;
    }

    private function estimateElementHeight($html)
    {
        // Упрощенная оценка высоты элемента
        $lineHeight = 20; // Примерная высота строки в px
        $lines = substr_count($html, '<br') + substr_count($html, '</p') + substr_count($html, '</div') + 1;

        return $lines * $lineHeight;
    }

    private function estimateTextHeight($text)
    {
        // Оценка высоты текста based на количество символов
        $avgCharWidth = 8; // Средняя ширина символа в px
        $lineHeight = 20; // Высота строки в px
        $maxWidth = 500; // Максимальная ширина контента в px

        $estimatedWidth = strlen($text) * $avgCharWidth;
        $lines = ceil($estimatedWidth / $maxWidth);

        return $lines * $lineHeight;
    }

    private function getOuterHTML($node)
    {
        $doc = new DOMDocument();
        $doc->appendChild($doc->importNode($node, true));
        return $doc->saveHTML();
    }

    // Альтернативный метод: разделение по количеству символов
    public function splitByCharacterCount($htmlContent, $charsPerPage = 3000)
    {
        $pages = [];
        $currentPage = '';
        $charCount = 0;

        // Разделяем HTML на теги и текст
        $tokens = preg_split('/(<[^>]+>)/', $htmlContent, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $openTags = [];

        foreach ($tokens as $token) {
            // Если это открывающий тег
            if (preg_match('/^<([^\/][^>]*)>$/', $token, $matches)) {
                $tagName = strtolower(explode(' ', $matches[1])[0]);
                $openTags[] = $tagName;
                $currentPage .= $token;
            }
            // Если это закрывающий тег
            elseif (preg_match('/^<\/([^>]+)>$/', $token, $matches)) {
                array_pop($openTags);
                $currentPage .= $token;
            }
            // Если это текст
            else {
                $text = $token;
                $textLength = mb_strlen($text);

                if ($charCount + $textLength > $charsPerPage && $charCount > 0) {
                    // Закрываем открытые теги
                    $currentPage .= $this->closeOpenTags($openTags);

                    $pages[] = [
                        'content' => $currentPage,
                        'pageNumber' => count($pages) + 1
                    ];

                    $currentPage = $this->reopenTags($openTags) . $text;
                    $charCount = $textLength;
                } else {
                    $currentPage .= $text;
                    $charCount += $textLength;
                }
            }
        }

        // Добавляем последнюю страницу
        if (!empty($currentPage)) {
            $pages[] = [
                'content' => $currentPage,
                'pageNumber' => count($pages) + 1
            ];
        }

        return $pages;
    }

    private function closeOpenTags($openTags)
    {
        $html = '';
        for ($i = count($openTags) - 1; $i >= 0; $i--) {
            $html .= '</' . $openTags[$i] . '>';
        }
        return $html;
    }

    private function reopenTags($openTags)
    {
        $html = '';
        foreach ($openTags as $tag) {
            $html .= '<' . $tag . '>';
        }
        return $html;
    }
}
