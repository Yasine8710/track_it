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

[$geminiResult, $geminiError] = callGeminiApi($prompt);

if ($geminiResult !== null) {
    echo json_encode(array_merge(['success' => true], $geminiResult));
} else {
    $fallback = buildFallbackResponse($userMessage, $summary);
    echo json_encode(array_merge(['success' => true], $fallback));
}

// ---------------------------------------------------------------------------

function callGeminiApi($prompt) {
    $url  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . GEMINI_API_KEY;
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

// ── Suggestion label constants ────────────────────────────────────────────
const SUGG_BALANCE  = "How's my balance?";
const SUGG_MONTH    = 'How was my month?';
const SUGG_TOP      = 'What are my top expenses?';
const SUGG_TIPS     = 'Tips to save money';
const SUGG_WHERE    = 'Where does my money go?';

function buildFallbackResponse($userMessage, $summary) {
    $msg = strtolower($userMessage);

    $data = [
        'balance'   => number_format($summary['current_balance'], 2),
        'monthIn'   => number_format($summary['month_inflow'],  2),
        'monthOut'  => number_format($summary['month_outflow'], 2),
        'totalIn'   => number_format($summary['total_inflow'],  2),
        'totalOut'  => number_format($summary['total_outflow'], 2),
        'txCount'   => $summary['month_transaction_count'],
        'saved'     => (float)$summary['month_inflow'] - (float)$summary['month_outflow'],
        'rate'      => $summary['month_inflow'] > 0
                       ? round(($summary['month_outflow'] / $summary['month_inflow']) * 100)
                       : 0,
        'topCats'   => $summary['top_categories'],
        'recentTxs' => $summary['recent_transactions'],
    ];
    $data['savedFmt'] = number_format(abs($data['saved']), 2);

    [$reply, $sugg] = matchIntent($msg, $data);
    return ['response' => $reply, 'suggestions' => $sugg];
}

function matchIntent($msg, $d) {
    if (str_contains($msg, 'balance') || str_contains($msg, 'how much')) {
        return replyBalance($d);
    }
    if (str_contains($msg, 'month') || str_contains($msg, 'summary')) {
        return replyMonth($d);
    }
    if (str_contains($msg, 'top') || str_contains($msg, 'biggest') || str_contains($msg, 'spend')
        || str_contains($msg, 'expense') || str_contains($msg, 'where') || str_contains($msg, 'most')) {
        return replyTopCats($d);
    }
    if (str_contains($msg, 'recent') || str_contains($msg, 'last') || str_contains($msg, 'latest')) {
        return replyRecent($d);
    }
    if (str_contains($msg, 'save') || str_contains($msg, 'tip') || str_contains($msg, 'advice')
        || str_contains($msg, 'improve') || str_contains($msg, 'manage') || str_contains($msg, 'cut')) {
        return replyTips($d);
    }
    if (str_contains($msg, 'earn') || str_contains($msg, 'income') || str_contains($msg, 'inflow')) {
        return replyIncome($d);
    }
    return replyGeneric($d);
}

function replyBalance($d) {
    $reply = "Your current balance is {$d['balance']} TND. "
           . "All-time you've earned {$d['totalIn']} TND and spent {$d['totalOut']} TND.";
    return [$reply, [SUGG_MONTH, SUGG_WHERE, SUGG_TIPS]];
}

function replyMonth($d) {
    if ($d['saved'] >= 0) {
        $reply = "Great month! You earned {$d['monthIn']} TND, spent {$d['monthOut']} TND, "
               . "and saved {$d['savedFmt']} TND ({$d['rate']}% spending rate) across {$d['txCount']} transactions.";
    } else {
        $reply = "This month you earned {$d['monthIn']} TND but spent {$d['monthOut']} TND — "
               . "you're {$d['savedFmt']} TND over your income. Consider cutting back next month.";
    }
    return [$reply, [SUGG_TOP, SUGG_BALANCE, SUGG_TIPS]];
}

function replyTopCats($d) {
    if (empty($d['topCats'])) {
        return ['No expense data for this month yet. Start logging transactions to see your breakdown!',
                [SUGG_MONTH, SUGG_BALANCE, SUGG_TIPS]];
    }
    $lines = [];
    foreach (array_slice($d['topCats'], 0, 3) as $i => $cat) {
        $pct     = $d['monthOut'] > 0 ? round(($cat['total'] / (float)$d['monthOut']) * 100) : 0;
        $lines[] = ($i + 1) . ". {$cat['name']}: " . number_format((float)$cat['total'], 2) . " TND ({$pct}%)";
    }
    $reply = "Your top spending categories this month:\n" . implode("\n", $lines);
    return [$reply, [SUGG_MONTH, SUGG_BALANCE, SUGG_TIPS]];
}

function replyRecent($d) {
    if (empty($d['recentTxs'])) {
        return ['No transactions recorded yet. Add your first one from the Home tab!',
                [SUGG_MONTH, SUGG_TOP, SUGG_BALANCE]];
    }
    $tx    = $d['recentTxs'][0];
    $sign  = $tx['type'] === 'inflow' ? '+' : '-';
    $amt   = number_format((float)$tx['amount'], 2);
    $cat   = $tx['category_name'] ?? 'Uncategorized';
    $desc  = $tx['description'] ? " ({$tx['description']})" : '';
    $reply = "Your most recent transaction: {$sign}{$amt} TND in {$cat}{$desc} on {$tx['transaction_date']}.";
    return [$reply, [SUGG_MONTH, SUGG_TOP, SUGG_BALANCE]];
}

function replyTips($d) {
    if (!empty($d['topCats'])) {
        $top    = $d['topCats'][0];
        $topAmt = number_format((float)$top['total'], 2);
        $advice = $d['rate'] > 80 ? "that's high, aim for under 70%." : "that's reasonable, keep it up!";
        $reply  = "Your biggest expense is {$top['name']} at {$topAmt} TND this month. "
                . "Try setting a weekly limit there. You're spending {$d['rate']}% of your income — {$advice}";
    } else {
        $reply = "Start by logging all your expenses so I can spot where you can cut back. "
               . "A good rule: keep spending under 70% of your income and save the rest.";
    }
    return [$reply, [SUGG_TOP, SUGG_MONTH, SUGG_BALANCE]];
}

function replyIncome($d) {
    $reply = "This month you earned {$d['monthIn']} TND. All-time total income: {$d['totalIn']} TND.";
    return [$reply, [SUGG_MONTH, SUGG_TOP, SUGG_TIPS]];
}

function replyGeneric($d) {
    if ($d['saved'] >= 0) {
        $reply = "You're doing well! Balance: {$d['balance']} TND. "
               . "This month: earned {$d['monthIn']} TND, spent {$d['monthOut']} TND, saved {$d['savedFmt']} TND.";
    } else {
        $reply = "Heads up — this month you spent more than you earned. "
               . "Balance: {$d['balance']} TND. Earned {$d['monthIn']} TND, spent {$d['monthOut']} TND.";
    }
    return [$reply, [SUGG_TOP, SUGG_TIPS, SUGG_BALANCE]];
}

