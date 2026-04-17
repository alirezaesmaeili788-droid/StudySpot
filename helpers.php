<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const STUDYSPOT_SPOT_TYPES = [
    'Cafe' => 'Cafe',
    'Bibliothek' => 'Bibliothek',
    'Uni' => 'Uni',
    'CoWorking' => 'CoWorking',
    'Sonstiges' => 'Sonstiges',
];

const STUDYSPOT_REQUEST_PLACE_TYPES = [
    'cafe' => 'Cafe',
    'bibliothek' => 'Bibliothek',
    'coworking' => 'Coworking / Lernraum',
    'sonstiges' => 'Sonstiges',
];

const STUDYSPOT_QUIET_LEVELS = [
    'quiet' => 'ruhig',
    'medium' => 'mittel',
    'loud' => 'lebhaft',
];

const STUDYSPOT_REQUEST_FEATURES = [
    'wifi' => 'WLAN',
    'steckdosen' => 'Steckdosen',
    'klimaanlage' => 'Klimaanlage',
    'getraenke' => 'Getraenke',
    'snacks' => 'Snacks',
    'wc' => 'WC',
];

const STUDYSPOT_REQUEST_SUITABLE = [
    'einzellernen' => 'Einzellernen',
    'gruppenarbeit' => 'Gruppenarbeit',
    'online_meeting' => 'Online-Meetings',
    'langes_lernen' => 'Lange Lernsessions',
];

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function build_url(string $path, array $query = []): string
{
    $filteredQuery = [];
    foreach ($query as $key => $value) {
        if ($value !== null && $value !== '') {
            $filteredQuery[$key] = $value;
        }
    }

    if ($filteredQuery === []) {
        return $path;
    }

    return $path . '?' . http_build_query($filteredQuery);
}

function is_post_request(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_role(): string
{
    return $_SESSION['role'] ?? 'guest';
}

function is_logged_in(): bool
{
    return current_user_id() > 0;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_role(array $roles): void
{
    require_login();

    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        exit('403 - Kein Zugriff');
    }
}

function is_admin(): bool
{
    return current_role() === 'admin';
}

function can_manage_spot($spotCreatedBy): bool
{
    if (is_admin()) {
        return true;
    }

    return current_role() === 'owner' && (int)$spotCreatedBy === current_user_id();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');

    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Die Anfrage konnte nicht bestaetigt werden. Bitte Seite neu laden und erneut versuchen.');
    }
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function checked(bool $value): string
{
    return $value ? 'checked' : '';
}

function selected(string $value, string $current): string
{
    return $value === $current ? 'selected' : '';
}

function post_checkbox_values(string $key, array $allowedValues): array
{
    $values = $_POST[$key] ?? [];

    if (!is_array($values)) {
        return [];
    }

    $allowedKeys = array_fill_keys(array_keys($allowedValues), true);
    $clean = [];

    foreach ($values as $value) {
        $value = (string)$value;
        if (isset($allowedKeys[$value])) {
            $clean[] = $value;
        }
    }

    return array_values(array_unique($clean));
}

function upload_image(array $file, string $directoryName, string $prefix, int $maxBytes = 3145728): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [true, null, ''];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return [false, null, 'Bild konnte nicht hochgeladen werden.'];
    }

    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxBytes) {
        return [false, null, 'Bild ist zu gross.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        return [false, null, 'Nur JPG, PNG oder WEBP sind erlaubt.'];
    }

    $targetDirFs = __DIR__ . '/uploads/' . $directoryName;
    if (!is_dir($targetDirFs) && !mkdir($targetDirFs, 0775, true) && !is_dir($targetDirFs)) {
        return [false, null, 'Upload-Ordner konnte nicht erstellt werden.'];
    }

    $filename = $prefix . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    $targetFs = $targetDirFs . '/' . $filename;
    $targetWeb = 'uploads/' . $directoryName . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetFs)) {
        return [false, null, 'Bild konnte nicht gespeichert werden.'];
    }

    return [true, $targetWeb, ''];
}

function quiet_label(?string $value): string
{
    return STUDYSPOT_QUIET_LEVELS[$value ?? ''] ?? 'mittel';
}

function spot_type_options(): array
{
    return STUDYSPOT_SPOT_TYPES;
}

function request_place_type_options(): array
{
    return STUDYSPOT_REQUEST_PLACE_TYPES;
}
