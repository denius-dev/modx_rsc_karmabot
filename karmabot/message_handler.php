<?php

function handleMessage($message) {
    global $modx, $botToken, $allowedChatIds, $filters, $adminIds;

    $chatId = $message['chat']['id'];
    $text = $message['text'];
    $replyToMessage = isset($message['reply_to_message']) ? $message['reply_to_message'] : null;
    $fromId = $message['from']['id'];
    $isAdmin = in_array($fromId, $adminIds);

    // Проверка разрешённых чатов
    if (!in_array($chatId, $allowedChatIds)) {
        $modx->log(modX::LOG_LEVEL_INFO, "Получено сообщение из не разрешённого чата: $chatId");
        return;
    }

    // Обработка команд админа
    if (strpos($text, '/addf ') === 0 || strpos($text, '/rmf ') === 0 || strpos($text, '/lf') === 0 || $text === '/lf@modx_karma_bot') {
        if (!$isAdmin) {
            $deleteUserMessageUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $message['message_id'];
            @file_get_contents($deleteUserMessageUrl);
            // Если команду использует не админ
            $response = [
                'chat_id' => $chatId,
                'text' => "❌ Для выполнения этой команды нужно быть админом.",
            ];

            $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
            $sentMessage = json_decode(file_get_contents($apiUrl), true);

            // Удаление сообщения через 5 секунд
            if (isset($sentMessage['result']['message_id'])) {
                sleep(5);
                $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
                file_get_contents($deleteUrl);
            }
            return;
        }

        // Если команду использует админ
        if (strpos($text, '/addf ') === 0) {
            $deleteUserMessageUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $message['message_id'];
            @file_get_contents($deleteUserMessageUrl);
            $phrase = trim(substr($text, strlen('/addf ')));
            addFilter($phrase);

            $response = [
                'chat_id' => $chatId,
                'text' => "✅ Фраза добавлена в фильтры: $phrase",
                'disable_notification' => true,
                // 'reaction' => json_encode([['type' => 'emoji', 'emoji' => '✔️']]),
            ];

            $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
            $sentMessage = json_decode(file_get_contents($apiUrl), true);

            // Удаление сообщения через 5 секунд
            if (isset($sentMessage['result']['message_id'])) {
                sleep(5);
                $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
                file_get_contents($deleteUrl);
            }
            return;
        } elseif (strpos($text, '/rmf ') === 0) {
            $deleteUserMessageUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $message['message_id'];
            @file_get_contents($deleteUserMessageUrl);
            $phrase = trim(substr($text, strlen('/rmf ')));
            removeFilter($phrase);

            $response = [
                'chat_id' => $chatId,
                'text' => "✅ Фраза удалена из фильтров: $phrase",
                'disable_notification' => true,
                // 'reaction' => json_encode([['type' => 'emoji', 'emoji' => '✔️']]),
            ];

            $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
            $sentMessage = json_decode(file_get_contents($apiUrl), true);

            // Удаление сообщения через 5 секунд
            if (isset($sentMessage['result']['message_id'])) {
                sleep(5);
                $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
                file_get_contents($deleteUrl);
            }
            return;
        } elseif (strpos($text, '/lf') === 0 || strpos($text, '/lf@modx_karma_bot') === 0) {
            $filtersList = getFiltersList();
            if (empty($filtersList)) {
                $response = [
                    'chat_id' => $chatId,
                    'text' => "📋 Список фильтров пуст.",
                ];
            } else {
                // Берём последние 20 фильтров
                $last20Filters = array_slice($filtersList, -20, 20);
                $totalFilters = count($filtersList);
                $responseText = "📋 Последние 20 фильтров:\n" . implode("\n", $last20Filters);

                // Если фильтров больше 20, показываем количество предыдущих
                if ($totalFilters > 20) {
                    $previousCount = $totalFilters - 20;
                    $responseText .= "\n\n...и ещё $previousCount предыдущих фильтров.";
                }

                $response = [
                    'chat_id' => $chatId,
                    'text' => $responseText,
                ];
            }

            $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
            $sentMessage = json_decode(file_get_contents($apiUrl), true);

            // Удаление сообщения через 5 секунд
            if (isset($sentMessage['result']['message_id'])) {
                sleep(5);
                $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
                file_get_contents($deleteUrl);
            }
            return;
        }
    }

    // Проверка на запрещённые фразы (фильтры)
    if ($chatId == -1001051277497) {
        if (checkForBannedPhrases($text, $filters)) {
            $userId = $message['from']['id'];
            $username = isset($message['from']['username']) ? $message['from']['username'] : 'пользователь';
            $isPremium = isset($message['from']['is_premium']) ? $message['from']['is_premium'] : false;

            if ($isPremium) {
                $banUrl = "https://api.telegram.org/bot$botToken/banChatMember?chat_id=$chatId&user_id=$userId&revoke_messages=1";
                file_get_contents($banUrl);

                $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $message['message_id'];
                file_get_contents($deleteUrl);

                // sendBanMessageAndDelete($chatId, $userId, $username, $botToken);
                $modx->log(1, "Забанен пользователь @$username: $text");
            } else {
                $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $message['message_id'];
                file_get_contents($deleteUrl);
                $modx->log(1, "Сообщение от непремиум-пользователя @$username удалено: $text");
            }
            return;
        }
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
        $aiRequest = $text . " Вопрос связан с MODX Revolution.";
        $rawResponse = getAIResponse($aiRequest);

        $modx->log(1, "ИИ запрос: " . print_r($aiRequest, true));
        $modx->log(1, "ИИ исходный ответ: " . print_r($rawResponse, true));

        function escapeMarkdownV2($text) {
            return preg_replace('/([_*\[\]()~`>#+\-=|{}.!])/u', '\\\\$1', $text);
        }

        $cleanResponse = preg_replace([
            '/\\\\(boxed|underline|mathbf){/u',
            '/}/u'
        ], '', $rawResponse);
        
        $cleanResponse = escapeMarkdownV2($cleanResponse);
        $cleanResponse = trim($cleanResponse);

        $maxLength = 4096;
        if (mb_strlen($cleanResponse) > $maxLength) {
            $cleanResponse = mb_substr($cleanResponse, 0, $maxLength - 3) . '...';
            $modx->log(1, "Ответ ИИ обрезан до 4096 символов");
        }

        $responseData = [
            'chat_id' => $chatId,
            'text' => $cleanResponse,
            'parse_mode' => 'MarkdownV2',
            'disable_web_page_preview' => true
        ];

        $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $responseData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($result === false || $httpCode !== 200) {
            $errorMsg = curl_error($ch);
            $modx->log(1, "Ошибка отправки ИИ-ответа: " . print_r([
                'error' => $errorMsg,
                'response' => $result,
                'code' => $httpCode
            ], true));
        } else {
            $modx->log(1, "ИИ-ответ успешно отправлен: " . print_r(json_decode($result, true), true));
        }
        
        curl_close($ch);
        return;
    }

    // Обработка кармы
    handleKarma($message);
}

// Функции для работы с фильтрами
function addFilter($phrase) {
    $filters = include 'filters.php';
    $filters[] = $phrase;
    file_put_contents('filters.php', "<?php\nreturn " . var_export($filters, true) . ";\n");
}

function removeFilter($phrase) {
    $filters = include 'filters.php';
    $index = array_search($phrase, $filters);
    if ($index !== false) {
        unset($filters[$index]);
        file_put_contents('filters.php', "<?php\nreturn " . var_export(array_values($filters), true) . ";\n");
    }
}

function getFiltersList() {
    $filters = include 'filters.php'; // Используем @, чтобы подавить ошибки, если файл отсутствует
    return is_array($filters) ? $filters : [];
}

function sendMessage($chatId, $text) {
    global $botToken;
    $url = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query([
        'chat_id' => $chatId,
        'text' => $text
    ]);
    file_get_contents($url);
}
