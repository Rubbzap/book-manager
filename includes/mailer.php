<?php

function sendBorrowEmail(string $to, string $username, string $bookTitle, string $volumeLabel, int $durationDays, string $dueAt): bool {
    $subject = 'แจ้งเตือนการยืมหนังสือ: ' . $bookTitle;
    $message = implode("\n", [
        "สวัสดี {$username}",
        "",
        "คุณได้ยืมหนังสือ: {$bookTitle}",
        "เล่ม/ตัวเลือก: {$volumeLabel}",
        "ระยะเวลา: {$durationDays} วัน",
        "กำหนดคืน: {$dueAt}",
        "",
        "ระบบ Book Manager จะนับเวลาถอยหลังให้ในหน้าบัญชีของคุณ",
    ]);

    $from = getenv('MAIL_FROM') ?: 'Book Manager <no-reply@book-manager.local>';
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $from,
    ];

    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, implode("\r\n", $headers));
}
