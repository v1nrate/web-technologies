<?php

namespace App\Support;

class ArticleMarkdown
{
    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace_callback('/!\[([^\]]*)\]\((https:\/\/[^\s)]+)\)/u', function ($m) {
            $url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            if (!filter_var($url, FILTER_VALIDATE_URL)) return $m[0];
            return '<img src="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" alt="'.$m[1].'" loading="lazy">';
        }, $text);
        $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u', function ($m) {
            $url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            if (!filter_var($url, FILTER_VALIDATE_URL)) return $m[1];
            return '<a href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener noreferrer">'.$m[1].'</a>';
        }, $text);
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/u', '<em>$1</em>', $text);
        $text = preg_replace('/`([^`]+)`/u', '<code>$1</code>', $text);
        return $text;
    }

    public static function render(string $markdown): string
    {
        $lines = preg_split('/\R/u', $markdown);
        $html = [];
        $list = null;
        $code = false;
        $codeLines = [];
        $closeList = function () use (&$list, &$html) {
            if ($list !== null) {
                $html[] = '</'.$list.'>';
                $list = null;
            }
        };
        foreach ($lines as $line) {
            if (preg_match('/^\s*```/', $line)) {
                $closeList();
                if ($code) {
                    $html[] = '<pre><code>'.htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</code></pre>';
                    $codeLines = [];
                }
                $code = !$code;
                continue;
            }
            if ($code) {
                $codeLines[] = $line;
                continue;
            }
            if (trim($line) === '') {
                $closeList();
                continue;
            }
            if (preg_match('/^(#{1,3})\s+(.+)$/u', $line, $m)) {
                $closeList();
                $level = strlen($m[1]) + 1;
                $id = 'section-'.substr(sha1($m[2]), 0, 10);
                $html[] = '<h'.$level.' id="'.$id.'">'.self::inline($m[2]).'</h'.$level.'>';
                continue;
            }
            if (preg_match('/^\s*(?:[-*])\s+(.+)$/u', $line, $m)) {
                if ($list !== 'ul') { $closeList(); $list = 'ul'; $html[] = '<ul>'; }
                $html[] = '<li>'.self::inline($m[1]).'</li>';
                continue;
            }
            if (preg_match('/^\s*\d+\.\s+(.+)$/u', $line, $m)) {
                if ($list !== 'ol') { $closeList(); $list = 'ol'; $html[] = '<ol>'; }
                $html[] = '<li>'.self::inline($m[1]).'</li>';
                continue;
            }
            $closeList();
            $html[] = '<p>'.self::inline($line).'</p>';
        }
        $closeList();
        if ($code) $html[] = '<pre><code>'.htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</code></pre>';
        return implode("\n", $html);
    }
}
