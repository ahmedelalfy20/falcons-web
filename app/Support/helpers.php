<?php

use App\Models\Setting;
use Illuminate\Support\HtmlString;

if (! function_exists('site')) {
    /**
     * Read a site setting using dot notation, e.g. site('hero.title').
     * Localised automatically: returns `title_ar` for Arabic when present.
     */
    function site(string $path, mixed $default = null, bool $localize = true): mixed
    {
        [$group, $key] = array_pad(explode('.', $path, 2), 2, null);
        $data = Setting::get($group, []);
        if ($key === null) {
            return $data ?: $default;
        }
        if ($localize && app()->getLocale() === 'ar') {
            $ar = data_get($data, $key.'_ar');
            if (filled($ar)) {
                return $ar;
            }
        }

        return data_get($data, $key, $default);
    }
}

if (! function_exists('rich_text')) {
    /**
     * Render plain admin-entered text safely: paragraphs from blank lines and
     * bullet lists from lines starting with "•", "-" or "*". Everything is escaped.
     */
    function rich_text(?string $text, string $listClass = 'rich-list'): HtmlString
    {
        // Lightweight, safe formatting for admin-entered text (everything is escaped first):
        // blank line = paragraph, "- item" / "* item" / "• item" = list, "### Title" = heading,
        // "> note" = highlighted note, **bold** inline.
        $text = trim(str_replace(["\r\n", "\t"], ["\n", ' '], (string) $text));
        if ($text === '') {
            return new HtmlString('');
        }
        $rtl = app()->getLocale() === 'ar';
        $inline = function (string $t) use ($rtl) {
            $h = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e($t));

            // Latin-only lines inside Arabic text: isolate them so punctuation stays where it belongs.
            return ! $rtl || preg_match('/\p{Arabic}/u', $t) || ! preg_match('/\p{L}/u', $t) ? $h : '<bdi dir="ltr">'.$h.'</bdi>';
        };
        $html = '';
        $list = [];
        $para = [];
        $flush = function () use (&$list, &$html, $listClass, $inline) {
            if ($list) {
                $html .= '<ul class="'.$listClass.'">'.implode('', array_map(fn ($i) => '<li>'.$inline($i).'</li>', $list)).'</ul>';
                $list = [];
            }
        };
        $flushPara = function () use (&$para, &$html, $inline) {
            if ($para) {
                $html .= '<p>'.implode('<br>', array_map($inline, $para)).'</p>';
                $para = [];
            }
        };
        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                $flushPara();
                $flush();

                continue;
            }
            if (preg_match('/^#{1,4}\s+(.+)$/u', $line, $m)) {
                $flushPara();
                $flush();
                $html .= '<h3>'.$inline(trim($m[1])).'</h3>';
            } elseif (preg_match('/^>\s*(.+)$/u', $line, $m)) {
                $flushPara();
                $flush();
                $html .= '<p class="rich-note">'.$inline(trim($m[1])).'</p>';
            } elseif (preg_match('/^[•\-\*]\s+(.+)$/u', $line, $m) && ! str_starts_with($line, '**')) {
                $flushPara();
                $list[] = trim($m[1]);
            } else {
                $flush();
                $para[] = $line;
            }
        }
        $flushPara();
        $flush();

        return new HtmlString($html);
    }
}

if (! function_exists('whatsapp_url')) {
    function whatsapp_url(?string $message = null): string
    {
        $number = preg_replace('/\D/', '', (string) site('contact.whatsapp', '201044416826', false));
        $message ??= (string) site('contact.whatsapp_message', 'Hello, I want to join Falcons Academy');

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}

if (! function_exists('format_duration')) {
    function format_duration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        $hms = sprintf('%02d:%02d:%02d', $h, $m, $s);

        return $d > 0 ? $d.'d '.$hms : $hms;
    }
}

if (! function_exists('join_url')) {
    /** "Join us" link: the Telegram channel from settings, falling back to WhatsApp. */
    function join_url(): string
    {
        return site('social.telegram', null, false) ?: whatsapp_url();
    }
}

if (! function_exists('media')) {
    /** Public URL for a stored media path (bundled media/ or uploaded storage/). */
    function media(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
