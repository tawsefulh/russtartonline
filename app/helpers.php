<?php
/* ---------- output ---------- */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Link to a page, in the current language. */
function url(string $path = ''): string
{
    return BASE_URL . (LANG === 'ru' ? '/ru' : '') . '/' . ltrim($path, '/');
}

/** Link without a language prefix (files, language switch). */
function base_url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** The current page in another language. */
function lang_url(string $code): string
{
    $query = $_SERVER['QUERY_STRING'] ?? '';
    return BASE_URL . ($code === 'ru' ? '/ru' : '') . ROUTE_PATH . ($query !== '' ? '?' . $query : '');
}

function asset(string $path): string
{
    $file = ROOT . '/assets/' . $path;
    return base_url('assets/' . $path) . '?v=' . (is_file($file) ? filemtime($file) : 1);
}

function upload_url(?string $file): ?string
{
    return $file ? base_url('uploads/' . $file) : null;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function render(string $view, array $data = []): void
{
    global $config, $errors;
    extract($data);
    ob_start();
    require ROOT . "/app/views/$view.php";
    $content = ob_get_clean();
    require ROOT . '/app/views/layout.php';
}

function partial(string $name, array $data = []): void
{
    extract($data);
    require ROOT . "/app/views/partials/$name.php";
}

function not_found(): void
{
    http_response_code(404);
    render('404', ['title' => 'Page not found']);
    exit;
}

/* ---------- session helpers ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);   // 419 is not a standard code and Apache turns it into 500
        exit(t('This form expired. Go back, refresh the page and try again.'));
    }
}

/** A one-time confirmation shown inside a section of the next page ("Profile saved."). */
function notice(string $key, string $message): void
{
    $_SESSION['notice'][$key] = $message;
}

function take_notice(string $key): ?string
{
    $message = $_SESSION['notice'][$key] ?? null;
    unset($_SESSION['notice'][$key]);
    return $message;
}

/** Value typed into a form before it failed validation. */
function old(string $key, $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) || is_numeric($v) ? (string) $v : '';
}

function post(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function query(string $key): string
{
    $v = $_GET[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

/* ---------- text ---------- */

function slugify(string $text): string
{
    static $cyr = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
        'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
        'х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];
    $text = strtr(mb_strtolower($text), $cyr);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $ascii !== false ? $ascii : $text);
    return substr(trim($text, '-'), 0, 80);
}

function unique_slug(string $table, string $text): string
{
    $base = slugify($text) ?: 'post';
    $slug = $base;
    while (q("SELECT 1 FROM `$table` WHERE slug = ?", [$slug])->fetch()) {
        $slug = $base . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
    }
    return $slug;
}

function excerpt(string $text, int $length = 180): string
{
    $text = trim(preg_replace('/\s+/u', ' ', plain_text($text)));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $cut = mb_substr($text, 0, $length);
    $space = mb_strrpos($cut, ' ');
    return rtrim(mb_substr($cut, 0, $space ?: $length), " ,.;:") . '…';
}

/* ---------- simple text formatting for articles and advertisements ----------
   What people type                 What readers see
   **bold**                         bold
   *italic*                         italic
   __underline__                    underlined
   ## Heading / ### Small heading   headings
   - item                           bulleted list
   1. item                          numbered list
   > text                           quote
   [text](https://...)              link (opens in a new tab)
   Everything is escaped first, so only these signs become formatting. */

/** Bold, italic, underline and links inside one line (the text is escaped first). */
function format_inline(string $line): string
{
    $s = e($line);
    $s = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/u', '<strong>$1</strong>', $s);
    $s = preg_replace('/__(?=\S)(.+?)(?<=\S)__/u', '<u>$1</u>', $s);
    $s = preg_replace('/(?<![*\w])\*(?=[^\s*])(.+?)(?<=[^\s*])\*(?![*\w])/u', '<em>$1</em>', $s);
    // links last, so a link can hold formatted text; only http(s) addresses are allowed
    return preg_replace_callback('/\[([^\]\n]{1,200})\]\((https?:\/\/[^\s()<>"]{1,500})\)/u',
        fn($m) => '<a href="' . $m[2] . '" target="_blank" rel="nofollow noopener ugc">' . $m[1] . '</a>', $s);
}

/** Article / advertisement text to HTML: paragraphs, headings, lists, quotes and inline formatting. */
function format_body(string $text): string
{
    $html = '';
    $para = [];
    $list = null;            // ['ul' | 'ol', [items]]
    $quote = [];

    $flushPara = function () use (&$html, &$para) {
        if ($para) {
            $html .= '<p>' . implode('<br>', array_map('format_inline', $para)) . '</p>';
            $para = [];
        }
    };
    $flushList = function () use (&$html, &$list) {
        if ($list) {
            $html .= '<' . $list[0] . '>' . implode('', array_map(fn($i) => '<li>' . format_inline($i) . '</li>', $list[1])) . '</' . $list[0] . '>';
            $list = null;
        }
    };
    $flushQuote = function () use (&$html, &$quote) {
        if ($quote) {
            $html .= '<blockquote><p>' . implode('<br>', array_map('format_inline', $quote)) . '</p></blockquote>';
            $quote = [];
        }
    };
    $flushAll = function () use ($flushPara, $flushList, $flushQuote) {
        $flushPara();
        $flushList();
        $flushQuote();
    };

    foreach (preg_split('/\R/u', trim($text)) as $raw) {
        $line = rtrim($raw);
        if (trim($line) === '') {                                   // empty line ends a block
            $flushAll();
            continue;
        }
        if (preg_match('/^(#{2,3})\s+(.+)$/u', $line, $m)) {          // ## heading / ### small heading
            $flushAll();
            $tag = strlen($m[1]) === 2 ? 'h2' : 'h3';
            $html .= "<$tag>" . format_inline($m[2]) . "</$tag>";
            continue;
        }
        if (preg_match('/^\s*[-•]\s+(.+)$/u', $line, $m)) {           // - bulleted item
            $flushPara();
            $flushQuote();
            if ($list && $list[0] !== 'ul') {
                $flushList();
            }
            $list = $list ?: ['ul', []];
            $list[1][] = $m[1];
            continue;
        }
        if (preg_match('/^\s*\d{1,3}[.)]\s+(.+)$/u', $line, $m)) {    // 1. numbered item
            $flushPara();
            $flushQuote();
            if ($list && $list[0] !== 'ol') {
                $flushList();
            }
            $list = $list ?: ['ol', []];
            $list[1][] = $m[1];
            continue;
        }
        if (preg_match('/^>\s?(.*)$/u', $line, $m)) {                  // > quote
            $flushPara();
            $flushList();
            $quote[] = $m[1];
            continue;
        }
        $flushList();
        $flushQuote();
        $para[] = $line;
    }
    $flushAll();
    return $html;
}

/** The text without formatting signs, for short previews in lists. */
function plain_text(string $text): string
{
    $text = preg_replace('/^#{2,3}\s+.*$/mu', '', $text);                         // headings are not part of the preview
    $text = preg_replace('/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/u', '$1', $text);   // links keep their text
    $text = preg_replace('/^\s*(?:[-•]|\d{1,3}[.)]|>)\s?/mu', '', $text);            // list and quote signs
    $text = str_replace(['**', '__'], '', $text);                                    // bold and underline first,
    return preg_replace('/(?<![*\w])\*(?=\S)(.+?)(?<=\S)\*(?![*\w])/u', '$1', $text); // then italic
}

function reading_time(string $text): int
{
    return max(1, (int) round(count(preg_split('/\s+/u', trim($text))) / 200));
}

function short_date(string $datetime): string
{
    $t = strtotime($datetime);
    $diff = time() - $t;
    if ($diff < 3600) {
        return t('%d min ago', max(1, (int) floor($diff / 60)));
    }
    if ($diff < 86400) {
        return t('%d h ago', (int) floor($diff / 3600));
    }
    if ($diff < 7 * 86400) {
        return t('%s ago', tn((int) floor($diff / 86400), 'day'));
    }
    return date('j', $t) . ' ' . month_name((int) date('n', $t), true) . (date('Y') === date('Y', $t) ? '' : date(' Y', $t));
}

/** A member's round picture, or their initials when they have not uploaded one. */
function avatar(string $name, ?string $file, string $class = ''): string
{
    $cls = trim('avatar ' . $class);
    if ($file) {
        return '<span class="' . $cls . ' has-photo" aria-hidden="true"><img src="' . e(upload_url($file)) . '" alt="" loading="lazy"></span>';
    }
    return '<span class="' . $cls . '" aria-hidden="true">' . e(initials($name)) . '</span>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name));
    $i = mb_substr($parts[0] ?? '?', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '');
    return mb_strtoupper($i);
}

function price_label($price): string
{
    if ($price === null || $price === '') {
        return t('Price on request');
    }
    return number_format((float) $price, 0, '.', ' ') . ' ₽';
}

/* ---------- validation ---------- */

/** Returns the phone as +digits, or null when it is not a valid international number. */
function normalize_phone(string $raw): ?string
{
    $digits = preg_replace('/[\s().\-]/', '', $raw);
    if (preg_match('/^8\d{10}$/', $digits)) {          // Russian local style 8 9xx xxx xx xx
        $digits = '+7' . substr($digits, 1);
    }
    if (!preg_match('/^\+?\d{10,15}$/', $digits)) {
        return null;
    }
    return '+' . ltrim($digits, '+');
}

/** Telegram: @username, t.me link, or a phone number. Returns the stored form or null. */
function normalize_telegram(string $raw): ?string
{
    $raw = preg_replace('#^(https?://)?t(elegram)?\.me/#i', '', trim($raw));
    if (preg_match('/^@?([A-Za-z][A-Za-z0-9_]{4,31})$/', $raw, $m)) {
        return '@' . $m[1];
    }
    return normalize_phone($raw);
}

function telegram_link(string $stored): string
{
    return 'https://t.me/' . ($stored[0] === '@' ? substr($stored, 1) : $stored);
}

/* ---------- uploads ---------- */

/**
 * Save an uploaded image. Returns the new file name, null when nothing was
 * uploaded, and puts a message in $errors[$field] when the file is rejected.
 */
function save_image(string $field): ?string
{
    global $errors;
    $f = $_FILES[$field] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 3 * 1024 * 1024) {
        $errors[$field] = t('The image must be smaller than 3 MB.');
        return null;
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($types[$mime]) || !@getimagesize($f['tmp_name'])) {
        $errors[$field] = t('Upload a JPG, PNG or WebP image.');
        return null;
    }
    $name = bin2hex(random_bytes(12)) . '.' . $types[$mime];
    if (!move_uploaded_file($f['tmp_name'], ROOT . '/uploads/' . $name)) {
        $errors[$field] = t('The image could not be saved. Try again.');
        return null;
    }
    return $name;
}

function delete_upload(?string $file): void
{
    if ($file && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $file)) {
        @unlink(ROOT . '/uploads/' . $file);
    }
}

/* ---------- cities & categories ---------- */

function all_cities(): array
{
    global $config;
    static $rows = null;
    return $rows ??= q('SELECT c.id, ' . name_sql('c') . ' AS name, c.slug FROM cities c
                        ORDER BY c.slug = ? DESC, c.is_default DESC, IF(c.is_default, c.id, 0), name',
        [$config['main_city']])->fetchAll();
}

function all_categories(string $type): array
{
    return q('SELECT k.id, ' . name_sql('k') . ' AS name, k.slug FROM categories k
              WHERE k.type = ? ORDER BY k.is_default DESC, IF(k.is_default, k.id, 0), name', [$type])->fetchAll();
}

function clean_name(string $name): string
{
    $name = trim(preg_replace('/\s+/u', ' ', $name));
    return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
}

/**
 * Turn the value of a city dropdown into a city id.
 * "__other" plus a typed name creates the city (or reuses an existing one).
 */
function resolve_city(string $field = 'city'): ?int
{
    global $errors;
    $value = post($field);
    if ($value === '__other') {
        $name = clean_name(post($field . '_other'));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || !preg_match('/^[\p{L}][\p{L}\s.\'\-]*$/u', $name)) {
            $errors[$field] = t('Type the name of your city.');
            return null;
        }
        $slug = slugify($name);
        if ($slug === '') {
            $errors[$field] = t('Type the name of your city.');
            return null;
        }
        $found = q('SELECT id FROM cities WHERE slug = ? OR name = ? OR name_ru = ?', [$slug, $name, $name])->fetch();
        if ($found) {
            return (int) $found['id'];
        }
        q('INSERT INTO cities (name, slug) VALUES (?, ?)', [$name, $slug]);
        return (int) db()->lastInsertId();
    }
    if (ctype_digit($value) && q('SELECT 1 FROM cities WHERE id = ?', [$value])->fetch()) {
        return (int) $value;
    }
    $errors[$field] = t('Choose a city.');
    return null;
}

function resolve_category(string $type, string $field = 'category'): ?int
{
    global $errors;
    $value = post($field);
    if ($value === '__other') {
        $name = clean_name(post($field . '_other'));
        $slug = slugify($name);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || $slug === '') {
            $errors[$field] = t('Type a name for the new category.');
            return null;
        }
        $found = q('SELECT id FROM categories WHERE type = ? AND (slug = ? OR name = ? OR name_ru = ?)', [$type, $slug, $name, $name])->fetch();
        if ($found) {
            return (int) $found['id'];
        }
        q('INSERT INTO categories (type, name, slug) VALUES (?, ?, ?)', [$type, $name, $slug]);
        return (int) db()->lastInsertId();
    }
    if (ctype_digit($value) && q('SELECT 1 FROM categories WHERE id = ? AND type = ?', [$value, $type])->fetch()) {
        return (int) $value;
    }
    $errors[$field] = t('Choose a category.');
    return null;
}

/* ---------- form fields ---------- */

function field_error(string $name): string
{
    global $errors;
    return isset($errors[$name]) ? '<p class="field-error" id="' . e($name) . '-error">' . e($errors[$name]) . '</p>' : '';
}

/**
 * Text input / textarea with a floating label.
 * Options: type, value, hint, required, attrs (extra raw attributes), rows.
 */
function field(string $name, string $label, array $o = []): string
{
    global $errors;
    $type  = $o['type'] ?? 'text';
    $value = $o['value'] ?? old($name);
    $attrs = ($o['attrs'] ?? '') . (!empty($o['required']) ? ' required' : '');
    $bad   = isset($errors[$name]);
    if ($bad) {
        $attrs .= ' aria-invalid="true" aria-describedby="' . e($name) . '-error"';
    }
    $id = 'f-' . $name;

    if ($type === 'textarea') {
        $control = '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . (int) ($o['rows'] ?? 6) . '" placeholder=" "' . $attrs . '>' . e($value) . '</textarea>';
    } else {
        $shown = $type === 'password' ? '' : e($value);
        $control = '<input id="' . $id . '" type="' . e($type) . '" name="' . e($name) . '" value="' . $shown . '" placeholder=" "' . $attrs . '>';
    }
    $toggle = $type === 'password'
        ? '<button type="button" class="field-toggle" data-toggle-password aria-label="' . et('Show password') . '">' . et('Show') . '</button>'
        : '';
    $hint = !empty($o['hint']) ? '<p class="field-hint">' . e($o['hint']) . '</p>' : '';

    return '<div class="field' . ($type === 'textarea' ? ' field-area' : '') . ($bad ? ' has-error' : '') . '">'
        . '<div class="field-box">' . $control . '<label for="' . $id . '">' . e($label) . '</label>' . $toggle . '</div>'
        . field_error($name) . $hint . '</div>';
}

/**
 * Custom dropdown (enhanced by assets/js/app.js).
 * $options: rows with id + name. Options: selected, other (label for the
 * "add your own" entry), any (label for an empty "all" entry), value_key.
 */
function select_field(string $name, string $label, array $options, array $o = []): string
{
    global $errors;
    $key      = $o['value_key'] ?? 'id';
    $selected = (string) ($o['selected'] ?? old($name));
    $bad      = isset($errors[$name]);
    $id       = 'f-' . $name;

    $html = '<div class="field' . ($bad ? ' has-error' : '') . '">'
        . '<div class="select" data-select data-label="' . e($label) . '">'
        . '<label for="' . $id . '">' . e($label) . '</label>'
        . '<select id="' . $id . '" name="' . e($name) . '">';
    $html .= '<option value="">' . e($o['any'] ?? '') . '</option>';
    foreach ($options as $opt) {
        $v = (string) $opt[$key];
        $html .= '<option value="' . e($v) . '"' . ($v === $selected ? ' selected' : '') . '>' . e($opt['name']) . '</option>';
    }
    if (!empty($o['other'])) {
        $html .= '<option value="__other"' . ($selected === '__other' ? ' selected' : '') . ' data-other>' . e($o['other']) . '</option>';
    }
    $html .= '</select></div>';

    if (!empty($o['other'])) {
        $html .= '<div class="select-other" data-other-for="' . $id . '"' . ($selected === '__other' ? '' : ' hidden') . '>'
            . '<div class="field-box"><input id="' . $id . '-other" type="text" name="' . e($name) . '_other" value="' . e(old($name . '_other')) . '" placeholder=" " maxlength="80">'
            . '<label for="' . $id . '-other">' . e($o['other_label'] ?? t('Type it here')) . '</label></div></div>';
    }
    return $html . field_error($name) . '</div>';
}

/** Keep current filters in pagination / filter links. */
function query_with(array $changes): string
{
    $params = array_filter(array_merge($_GET, $changes), fn($v) => $v !== '' && $v !== null);
    return $params ? '?' . http_build_query($params) : '';
}
