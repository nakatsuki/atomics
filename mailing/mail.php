<?php
/**
 * Handler pentru formularul de contact.
 *
 * Frontend-ul (app-*.js, modulul js-form) trimite POST JSON catre
 * /mailing/mail.php si asteapta un raspuns JSON de forma:
 *   {"status":"OK","message":"..."}  sau  {"status":"ERROR","message":"..."}
 *
 * IMPORTANT: schimba MAIL_TO cu adresa reala pe care vrei sa primesti mesajele.
 * Necesita hosting cu PHP si functia mail() activa (sau inlocuieste trimiterea
 * cu SMTP, ex. PHPMailer, daca hostingul cere autentificare).
 */

declare(strict_types=1);

const MAIL_TO      = 'contact@atomics.md';
const MAIL_SUBJECT = 'Mesaj nou de pe atomics.md';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(string $status, string $message, int $code = 200): void
{
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond('ERROR', 'Metodă nepermisă.', 405);
}

$raw  = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);

// acceptam si POST clasic (fallback fara JavaScript)
if (!is_array($data)) {
    $data = $_POST;
}

$name    = trim((string) ($data['name'] ?? ''));
$email   = trim((string) ($data['email'] ?? ''));
$message = trim((string) ($data['message'] ?? ''));

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond('ERROR', 'Te rugăm să completezi corect numele, adresa de email și mesajul.', 422);
}

if (mb_strlen($name) > 120 || mb_strlen($email) > 190 || mb_strlen($message) > 5000) {
    respond('ERROR', 'Mesajul este prea lung. Te rugăm să îl scurtezi.', 422);
}

// protectie minimala anti header-injection
foreach ([$name, $email] as $singleLine) {
    if (preg_match('/[\r\n]/', $singleLine)) {
        respond('ERROR', 'Datele trimise nu sunt valide.', 422);
    }
}

$body = "Nume: {$name}\n"
      . "Email: {$email}\n"
      . 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'necunoscut') . "\n"
      . 'Data: ' . date('c') . "\n\n"
      . "Mesaj:\n{$message}\n";

$headers = [
    'From: Website Atomics <no-reply@atomics.md>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=utf-8',
    'MIME-Version: 1.0',
];

$sent = @mail(
    MAIL_TO,
    '=?UTF-8?B?' . base64_encode(MAIL_SUBJECT) . '?=',
    $body,
    implode("\r\n", $headers)
);

if (!$sent) {
    respond('ERROR', 'Mesajul nu a putut fi trimis. Te rugăm să încerci din nou mai târziu.', 500);
}

respond('OK', 'Mulțumim! Mesajul a fost trimis, revenim cât de curând.');
