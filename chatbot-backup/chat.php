<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/chat_helpers.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input       = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if ($userMessage === '') {
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

$userId  = $_SESSION['user_id'];
$summary = getUserFinancialSummary($pdo, $userId);
$context = formatFinancialContext($summary);

$prompt = "You are DINARI, a finance assistant in a budget app. Be casual and encouraging. Keep the reply field to 1-2 short sentences only — never longer. Use TND as currency. Only reference the data below — never invent numbers. If the question is unrelated to finances, politely redirect.

FINANCIAL DATA:
$context

Respond ONLY in this exact JSON format:
{\"reply\": \"...\", \"suggestions\": [\"...\", \"...\", \"...\"]}

User: $userMessage";

[$geminiResult] = callGeminiApi($prompt);

if ($geminiResult !== null) {
    echo json_encode(array_merge(['success' => true], $geminiResult));
} else {
    $fallback = buildFallbackResponse($userMessage, $summary);
    echo json_encode(array_merge(['success' => true], $fallback));
}

// ---------------------------------------------------------------------------

function callGeminiApi($prompt) {
    $url  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;
    $body = json_encode([
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $prompt]]]
        ],
        'generationConfig' => [
            'temperature'     => 0.7,
            'maxOutputTokens' => 800,
        ],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        // SSL verification disabled for local XAMPP dev (no CA bundle configured)
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $raw    = curl_exec($ch);
    $errno  = curl_errno($ch);
    $errmsg = curl_error($ch);
    curl_close($ch);

    if ($errno || $raw === false) {
        return [null, "cURL error {$errno}: {$errmsg}"];
    }

    $apiData = json_decode($raw, true);
    $text    = $apiData['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($text === null) {
        $detail = $apiData['error']['message'] ?? $raw;
        return [null, "Gemini parse error: {$detail}"];
    }

    // Strip optional ```json ... ``` fences Gemini sometimes adds
    $clean  = preg_replace('/^```json\s*/s', '', trim($text));
    $clean  = trim(preg_replace('/\s*```$/s', '', $clean));
    $parsed = json_decode($clean, true);

    $defaultSuggestions = [
        'What should I cut back on?',
        'How is my spending this month?',
        'What are my top expenses?',
    ];

    $result = (is_array($parsed) && isset($parsed['reply']))
        ? ['response' => $parsed['reply'],  'suggestions' => $parsed['suggestions'] ?? $defaultSuggestions]
        : ['response' => $clean,            'suggestions' => $defaultSuggestions];

    return [$result, null];
}

function buildFallbackResponse($userMessage, $summary) {
    $balance  = number_format($summary['current_balance'], 2);
    $monthIn  = number_format($summary['month_inflow'], 2);
    $monthOut = number_format($summary['month_outflow'], 2);
    $txCount  = $summary['month_transaction_count'];
    $msg      = strtolower($userMessage);

    $defaultSuggestions = [
        "What's my balance?",
        'How was my month?',
        'What are my top expenses?',
    ];

    if (strpos($msg, 'balance') !== false) {
        $reply = "Your current balance is {$balance} TND, calculated from all your recorded transactions.";
    } elseif (strpos($msg, 'summary') !== false || strpos($msg, 'month') !== false) {
        $reply = "This month you earned {$monthIn} TND and spent {$monthOut} TND across {$txCount} transactions.";
    } elseif (strpos($msg, 'top') !== false || strpos($msg, 'biggest') !== false) {
        if (!empty($summary['top_categories'])) {
            $top   = $summary['top_categories'][0];
            $reply = "Your biggest spending category this month is {$top['name']} at "
                   . number_format((float)$top['total'], 2) . " TND.";
        } else {
            $reply = "No expense data found for this month yet — start logging transactions!";
        }
    } else {
        $reply = "I'm having trouble connecting right now. Here's your quick summary: "
               . "balance is {$balance} TND, and this month you earned {$monthIn} TND and spent {$monthOut} TND.";
    }

    return ['response' => $reply, 'suggestions' => $defaultSuggestions];
}

