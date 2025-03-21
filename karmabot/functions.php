<?php

// Функция для транслитерации текста (замена английских букв на русские)
function transliterateText($text) {
    $replacements = [
        'a' => 'а', 'c' => 'с', 'e' => 'е', 'o' => 'о', 'p' => 'р', 
        'x' => 'х', 'y' => 'у', 'A' => 'А', 'B' => 'В', 'C' => 'С', 
        'E' => 'Е', 'H' => 'Н', 'K' => 'К', 'M' => 'М', 'O' => 'О', 
        'P' => 'Р', 'T' => 'Т', 'X' => 'Х', 'Y' => 'У', 'u' => 'и', 
        'U' => 'И', 'b' => 'ь', 'B' => 'Ь', 'd' => 'д', 'D' => 'Д', 
        'g' => 'д', 'G' => 'Д', 'l' => 'л', 'L' => 'Л', 'n' => 'п', 
        'N' => 'П', 's' => 'с', 'S' => 'С', 't' => 'т', 'v' => 'в', 
        'V' => 'В', 'z' => 'з', 'Z' => 'З', 'm' => 'м', 'k' => 'к', 
        'h' => 'н', 'w' => 'ш', 'W' => 'Ш', 'q' => 'к', 'Q' => 'К', 
        'j' => 'ж', 'J' => 'Ж', 'f' => 'ф', 'F' => 'Ф', 'r' => 'р', 
        'R' => 'Р', 'i' => 'и', 'I' => 'И', 'ё' => 'е', 'Ё' => 'Е',
        'ᴧ' => 'л', 'ᴇ' => 'е', 'ᴄ' => 'с', 'ᴏ' => 'о', 'ᴩ' => 'р', 
        'ʙ' => 'в', 'ᴨ' => 'п', 'ʀ' => 'р', 'ʜ' => 'н', 'ʟ' => 'л', 
        'ʏ' => 'у', 'ñ' => 'и', 'à' => 'а', 'ê' => 'е', 'ü' => 'у'
    ];
    return strtr(mb_strtolower($text), $replacements);
}

// Функция для нормализации текста (удаление лишних пробелов и транслитерация)
function normalizeText($text) {
    $text = mb_strtolower($text);
    $text = transliterateText($text);
    $text = preg_replace('/[^\p{L}\s]/u', '', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

// Функция для получения ответа от API
function getAIResponse($message) {
    global $apiKey, $apiUrl;

    // Данные для запроса
    $data = [
        'model' => 'deepseek-r1-zero:free', // Список моделей - https://bothub.chat/models
        'messages' => [
            ['role' => 'user', 'content' => $message]
        ]
    ];

    // Настройки HTTP-запроса
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\nAuthorization: Bearer $apiKey",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];

    // Создаём контекст запроса
    $context  = stream_context_create($options);

    // Отправляем запрос
    $response = file_get_contents($apiUrl, false, $context);

    // Обработка ошибок
    if ($response === FALSE) {
        $error = error_get_last();
        return "Ошибка при запросе к API: " . $error['message'];
    }

    // Декодируем ответ
    $responseData = json_decode($response, true);

    // Проверяем, есть ли ошибка в ответе
    if (isset($responseData['error'])) {
        return "Ошибка API: " . $responseData['error']['message'];
    }

    // Проверяем, есть ли ответ
    if (!isset($responseData['choices'][0]['message']['content'])) {
        return "Не удалось получить ответ от API.";
    }

    // Возвращаем ответ
    return $responseData['choices'][0]['message']['content'];
}
