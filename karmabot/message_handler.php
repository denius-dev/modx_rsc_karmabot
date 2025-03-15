<?php

function handleMessage($message) {
    global $modx, $botToken, $allowedChatIds, $filters;

    $chatId = $message['chat']['id'];
    $text = $message['text'];
    $replyToMessage = isset($message['reply_to_message']) ? $message['reply_to_message'] : null;

    if (!in_array($chatId, $allowedChatIds)) {
        $modx->log(modX::LOG_LEVEL_INFO, "Message received in non-allowed chat: $chatId");
        return;
    }

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
            $modx->log(1, "Забанен Premium-пользователь @$username: $text");
        } else {
            $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $message['message_id'];
            file_get_contents($deleteUrl);
            $modx->log(1, "Сообщение от непремиум-пользователя @$username удалено: $text");
        }
        return;
    }

    handleKarma($message);
}