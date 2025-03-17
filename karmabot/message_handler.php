<?php

function handleMessage($message) {
    global $modx, $botToken, $allowedChatIds, $filters;

    $chatId = $message['chat']['id'];
    $text = $message['text'];
    $replyToMessage = isset($message['reply_to_message']) ? $message['reply_to_message'] : null;

    if (!in_array($chatId, $allowedChatIds)) {
        $modx->log(modX::LOG_LEVEL_INFO, "Получено сообщение из не разрешённого чата: $chatId");
        return;
    }

    // Обработка запросов к ИИ
    $callAI = [
        'ИИ ответь тоже',
        'ИИ тоже ответь',
        'тоже ответь ИИ',
        'ответь тоже ИИ',
        'ответь ИИ тоже'
    ];

    if (array_filter($callAI, function($phrase) use ($text) {
        return mb_stripos($text, $phrase) !== false;
    })) {
        $text = $text . "Вопрос связан с MODX Revolution, ответь кратко и ёмко";
        $response = getAIResponse($text);

        // Логируем запрос и ответ
        $modx->log(1, "Запрос: $question");
        $modx->log(1, "Ответ: $response");

        $cleanResponse = str_replace(array("\\boxed{", "}"), "", $response);

        // Отправляем ответ в чат
        $responseData = [
            'chat_id' => $chatId,
            'text' => $cleanResponse,
        ];

        $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($responseData);
        file_get_contents($apiUrl);
        return;
    }

    // Фильтрация фраз спамеров
    if (checkForBannedPhrases($text, $filters)) {
        $userId = $message['from']['id'];
        $username = isset($message['from']['username']) ? $message['from']['username'] : 'пользователь';
        $isPremium = isset($message['from']['is_premium']) ? $message['from']['is_premium'] : false;

        if ($isPremium) {
            $banUrl = "https://api.telegram.org/bot$botToken/banChatMember?chat_id=$chatId&user_id=$userId&revoke_messages=1";
            file_get_contents($banUrl);

            $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $message['message_id'];
            file_get_contents($deleteUrl);

            sendBanMessageAndDelete($chatId, $userId, $username, $botToken);
            $modx->log(1, "Забанен пользователь @$username: $text");
        } else {
            $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $message['message_id'];
            file_get_contents($deleteUrl);
            $modx->log(1, "Сообщение от непремиум-пользователя @$username удалено: $text");
        }
        return;
    }

    handleKarma($message);
}
