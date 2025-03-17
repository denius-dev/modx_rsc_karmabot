<?php

function sendBanMessageAndDelete($chatId, $userId, $username, $botToken) {
    if ($username) {
        $responseText = "Пользователь @$username забанен за рекламу.";
    } else {
        $responseText = "Спамер забананен 🍌";
    }

    $response = [
        'chat_id' => $chatId,
        'text' => "$responseText",
    ];

    $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
    $sentMessage = json_decode(file_get_contents($apiUrl), true);

    if (isset($sentMessage['result']['message_id'])) {
        sleep(10);
        $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
        file_get_contents($deleteUrl);
    }
}

function checkForBannedPhrases($text, $filters) {
    global $modx;
    
    $normalizedText = normalizeText($text);
    $modx->log(1, "Исходный текст: $text");
    $modx->log(1, "Нормализованный текст: $normalizedText");

    foreach ($filters as $filter) {
        $transliteratedFilter = transliterateText($filter);
        $pattern = '/\b' . str_replace('\*', '.*', preg_quote($transliteratedFilter, '/')) . '\b/iu';
        
        $modx->log(1, "Фильтр: $filter → Транслитерированный: $transliteratedFilter → Регулярка: $pattern");
        
        if (preg_match($pattern, $normalizedText)) {
            $modx->log(1, "СОВПАДЕНИЕ! Фильтр: $filter");
            return true;
        }
    }
    return false;
}
