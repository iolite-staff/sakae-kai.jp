<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html#home_recruit_area', true, 303);
    exit;
}

function clean_line(string $value): string {
    return trim(str_replace(["\r", "\n"], '', $value));
}

function fail_request(string $message, int $status = 400): never {
    http_response_code($status);
    $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>送信エラー</title><link rel="stylesheet" href="css/screen.css"><link rel="stylesheet" href="css/recruit-integrated.css"></head><body><main class="recruit-result-page"><div class="recruit-result-card"><p class="recruit-en">Error</p><h1>送信できませんでした</h1><p>' . $safe . '</p><a class="recruit-result-button" href="index.html#home_recruit_area">入力画面に戻る</a></div></main></body></html>';
    exit;
}

// スパム対策：画面に表示されない欄に入力がある場合は送信しません。
if (!empty($_POST['website'] ?? '')) {
    header('Location: thanks.html', true, 303);
    exit;
}

$name = clean_line((string)($_POST['name'] ?? ''));
$email = clean_line((string)($_POST['email'] ?? ''));
$phone = clean_line((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || mb_strlen($name) > 80) fail_request('お名前を正しく入力してください。');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) fail_request('メールアドレスを正しく入力してください。');
if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{6,30}$/u', $phone)) fail_request('電話番号を正しく入力してください。');
if ($message === '' || mb_strlen($message) > 3000) fail_request('お問い合わせ内容を3000文字以内で入力してください。');

$to = 'info@tanagokoro-kango.net';
$subject = '【採用ページ】新しいお問い合わせ';
$body = "採用ページからお問い合わせがありました。\n\n"
    . "お名前: {$name}\nメールアドレス: {$email}\n電話番号: " . ($phone !== '' ? $phone : '未入力')
    . "\n\nお問い合わせ内容:\n{$message}\n";
$headers = [
    'From: =?UTF-8?B?' . base64_encode('たなごころ採用フォーム') . '?= <no-reply@sakae-kai.jp>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit'
];
$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

if (!mail($to, $encodedSubject, $body, implode("\r\n", $headers))) {
    fail_request('メール送信でエラーが発生しました。お手数ですが、045-790-5668へお電話ください。', 500);
}
header('Location: thanks.html', true, 303);
exit;
