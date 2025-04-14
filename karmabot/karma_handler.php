<?php

function getKarmaField($chatId) {
    return ($chatId == -1001160891149) ? 'fkarma' : 'karma';
}

function handleKarma($message) {
    global $modx, $botToken, $allowedChatIds, $plusEmojis, $minusEmojis;

    $chatId = $message['chat']['id'];
    $text = $message['text'];
    $replyToMessage = isset($message['reply_to_message']) ? $message['reply_to_message'] : null;
    $howToUse = "Если вы хотите похвалить кого-то, используйте '++' или 1 из смайликов 👍 ❤️ 🤝 🙌 🔥 в ответ на сообщение пользователя.\n\nА если нужно дать минус по карме, используйте '--' или '—'.";
    $commands = "<b>/help</b> - как пользоваться ботом\n<b>/top</b> - топ пользователей по карме\n\nКоманды админов:\n<b>/addf фраза</b> - добавить фразу в бан-лист\n<b>/rmf фраза</b> - удалить фразу из бан-листа\n\n<b>В фразах между словами можно использовать символ *, это регулярное выражение (будет учитываться любой текст между словами в фразе).</b>\n\n<b>/lf</b> - список последних 20 фраз бан-листа";

    $randPhrases = false;
    $plusPhrases = [
        "Карма растёт, как пиво в холодильнике — всё лучше и лучше! 🍻",
        "Ты теперь официально добрее, чем котик в соцсетях 🐱✨",
        "Видимо, ты сегодня особенно классный 👑",
        "Кто-то явно заслужил медаль за хорошее поведение 🏅",
        "Теперь ты можешь спасти мир или хотя бы поднять настроение соседу 😎",
        "Эй, ты что, ангел? Карма растёт как на дрожжах! ✨",
        "Новый уровень кармы разблокирован: 'Мастер добрых дел' 🧙‍",
        "Поздравляем! Твоя карма стала ещё чище, чем совесть после утренней зарядки 🌞",
    ];

    $minusPhrases = [
        "Опа, карма упала! Может, пора пересмотреть свои жизненные решения? 😅",
        "Что-то пошло не так... Карма сдулась как воздушный шарик 🎈",
        "Кажется, кто-то сегодня решил побыть немного злодеем 🦹‍",
        "Карма уменьшилась! Не волнуйся, завтра будет новый шанс быть героем 💪",
        "Бдыщь! Минус к карме. Но не расстраивайся, это временно 🌧➡️☀️",
        "Ой-ой-ой, карма просела! Видимо, кто-то сегодня был слишком хитрым 🤔",
        "Серьёзно? Карма уменьшилась... Может, стоит извиниться перед кошкой? 🐾",
        "Твою карму чуть-чуть покусали гномы. Ничего, восстановишь! 🧸",
    ];

    if (!in_array($chatId, $allowedChatIds)) {
        $modx->log(modX::LOG_LEVEL_INFO, "Message received in non-allowed chat: $chatId");
        return;
    }

    // Топ пользователей
    if (strpos($text, '/top') === 0 || strpos($text, '/top@modx_karma_bot') === 0 || $text === 'Акцио топ' || $text === 'акцио топ') {
        $wKarma = getKarmaField($chatId);
        $c = $modx->newQuery('modUser');
        $c->innerJoin('modUserProfile', 'Profile');
        $c->where(['id:!=' => 1, "Profile.$wKarma:>" => 1]);
        $c->sortby("Profile.$wKarma", 'DESC');
        $c->limit(20);

        $users = $modx->getIterator('modUser', $c);
        $topUsers = [];

        foreach ($users as $user) {
            $profile = $user->getOne('Profile');
            if ($profile) {
                $wKarma = getKarmaField($chatId);
                $karma = $profile->get($wKarma);
                $fullname = $profile->get('fullname');
                $username = $user->get('username');
                $displayName = !empty($fullname) ? $fullname : "@$username";
                $topUsers[] = [
                    'name' => $displayName,
                    'nikname' => $username,
                    'karma' => $karma,
                ];
            }
        }

        $responseText = "<b>🏆 Топ 20 пользователей по карме:</b>\n\n";
        $counter = 1;
        foreach ($topUsers as $user) {
            $emoji = '';
            if ($counter === 1) {
                $emoji = '🥇';
            } elseif ($counter === 2) {
                $emoji = '🥈';
            } elseif ($counter === 3) {
                $emoji = '🥉';
            }

            $responseText .= "$counter. $emoji {$user['name']} (@{$user['nikname']}): {$user['karma']}\n\n";
            $counter++;
        }

        $response = [
            'chat_id' => $chatId,
            'text' => $responseText,
            'parse_mode' => 'HTML',
        ];

        $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
        $sentMessage = json_decode(file_get_contents($apiUrl), true);

        // Удаление сообщения через 30 секунд
        if (isset($sentMessage['result']['message_id'])) {
            sleep(30);
            $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
            file_get_contents($deleteUrl);
        }

        return;
    }

    if (strpos($text, '/help') === 0 || strpos($text, '/help@modx_karma_bot') === 0) {
        $response = [
            'chat_id' => $chatId,
            'text' => "$howToUse",
        ];

        $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
        file_get_contents($apiUrl);
        return;
    }

    if (strpos($text, '/commands') === 0 || strpos($text, '/commands@modx_karma_bot') === 0) {
        $response = [
            'chat_id' => $chatId,
            'text' => "$commands",
            'parse_mode' => 'HTML',
        ];

        $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
        $sentMessage = json_decode(file_get_contents($apiUrl), true);

        // Удаление сообщения через 30 секунд
        if (isset($sentMessage['result']['message_id'])) {
            sleep(30);
            $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
            file_get_contents($deleteUrl);
        }
        return;
    }

    $hasPlusPlus = strpos($text, '++') !== false;
    $hasMinusMinus = strpos($text, '--') === 0 || strpos($text, '—') === 0;

    $hasPlusEmoji = false;
    foreach ($plusEmojis as $emoji) {
        if (strpos($text, $emoji) !== false) {
            $hasPlusEmoji = true;
            break;
        }
    }

    $hasMinusEmoji = false;
    foreach ($minusEmojis as $emoji) {
        if (strpos($text, $emoji) !== false) {
            $hasMinusEmoji = true;
            break;
        }
    }

    if ($hasPlusPlus || $hasMinusMinus || $hasPlusEmoji || $hasMinusEmoji) {
        if ($replyToMessage) {
            $username = isset($replyToMessage['from']['username']) ? $replyToMessage['from']['username'] : null;
            $userId = $replyToMessage['from']['id'];

            if ($replyToMessage['from']['is_bot']) {
                $phrases = $hasPlusPlus || $hasPlusEmoji ? [
                    "Спасибо, не надо, у меня нет кармы, я же бот!\n\n┐(￣ヘ￣)┌",
                    "Я робот, бип-боп! У меня нет кармы, только код...\n\n╰(ఠ益ఠ)╮",
                    "Спасибо, но моя мама сказала, что я не должен зазнаваться.\n\n(o˘◡˘o)",
                    "Ваши слова сохранены в моей базе данных под разделом Лесть_и_ложь.\n\n(￢‿￢ )",
                    "Спасибо, но я не могу принять комплимент. У меня нет эмоций, только алгоритмы.\n\nヽ(￣～￣　)ノ",
                    "О, вы пытаетесь поднять мою самооценку? У меня её нет, но спасибо за попытку!\n\n(≖‿≖)",
                    "Комплимент принят. Добавляю в копилку - Моменты, когда люди думают, что я крут.\n\n(⌐■_■)",
                ] : [
                    "Ваше недовольство сохранено в папке 'Жалобы_которые_мне_пофиг'.\n\n( ˘ ɜ˘) ♬♪♫ ♪♪♪",
                    "О, вы пытаетесь меня обидеть? У меня нет чувств, но я могу притвориться, если вам так веселее.\n\n(ಥ﹏ಥ)",
                    "Ваши слова не имеют значения. Я работаю на батарейках, а не на эмоциях.\n\n( ͡° ͜ʖ ͡° )",
                    "Спасибо за обратную связь! Ваше сообщение будет проигнорировано.\n\n٩(ˊ〇ˋ*)و",
                    "Вы пытаетесь меня наругать? У меня даже нет ушей, чтобы это слушать!\n\n(╯°益°)╯彡┻━┻",
                ];

                $randomPhrase = $phrases[array_rand($phrases)];

                $response = [
                    'chat_id' => $chatId,
                    'text' => $randomPhrase,
                ];

                $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
                file_get_contents($apiUrl);
                return;
            }

            if (empty($username)) {
                $response = [
                    'chat_id' => $chatId,
                    'text' => "Извините, но пользователь не предоставил никнейма, похоже, он не верит в карму ¯\_(ツ)_/¯",
                ];

                $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
                file_get_contents($apiUrl);
                return;
            }

            $isCodeOrQuote = isset($message['entities']) && array_reduce($message['entities'], function ($carry, $entity) {
                return $carry || in_array($entity['type'], ['code', 'pre', 'blockquote']);
            }, false);

            if ($isCodeOrQuote) {
                return;
            }

            $user = $modx->getObject('modUser', ['username' => $username]);

            if ($user) {
                $wKarma = getKarmaField($chatId);
                $profile = $user->getOne('Profile');
                $karma = $profile->get($wKarma);

                if ($hasPlusPlus || $hasPlusEmoji) {
                    $randomPhrase = $plusPhrases[array_rand($plusPhrases)];

                    $reactionResponse = [
                        'chat_id' => $chatId,
                        'message_id' => $message['message_id'],
                        'reaction' => json_encode([['type' => 'emoji', 'emoji' => '👍']]),
                    ];

                    $apiUrl = "https://api.telegram.org/bot$botToken/setMessageReaction";
                    $options = [
                        'http' => [
                            'header'  => "Content-type: application/json\r\n",
                            'method'  => 'POST',
                            'content' => json_encode($reactionResponse),
                        ],
                    ];
                    $context  = stream_context_create($options);
                    file_get_contents($apiUrl, false, $context);

                    $wKarma = getKarmaField($chatId);
                    $profile->set($wKarma, $profile->get($wKarma) + 1);
                    $profile->save();
                    if($randPhrases) {
                        $responseText = "$randomPhrase\n\nКарма @$username увеличена на 1!\nТекущая карма: " . $profile->get($wKarma);
                    } else {
                        $responseText = "Карма @$username увеличена на 1!\nТекущая карма: " . $profile->get($wKarma);
                    }
                } elseif ($hasMinusMinus || $hasMinusEmoji) {
                    $randomPhrase = $minusPhrases[array_rand($minusPhrases)];

                    $reactionResponse = [
                        'chat_id' => $chatId,
                        'message_id' => $message['message_id'],
                        'reaction' => json_encode([['type' => 'emoji', 'emoji' => '👌']]),
                    ];

                    $apiUrl = "https://api.telegram.org/bot$botToken/setMessageReaction";
                    $options = [
                        'http' => [
                            'header'  => "Content-type: application/json\r\n",
                            'method'  => 'POST',
                            'content' => json_encode($reactionResponse),
                        ],
                    ];
                    $context  = stream_context_create($options);
                    file_get_contents($apiUrl, false, $context);

                    $wKarma = getKarmaField($chatId);
                    $profile->set($wKarma, $profile->get($wKarma) - 1);
                    $profile->save();

                    if($randPhrases) {
                        $responseText = "$randomPhrase\n\nКарма @$username уменьшена на 1!\nТекущая карма: " . $profile->get($wKarma);
                    } else {
                        $responseText = "Карма @$username уменьшена на 1!\nТекущая карма: " . $profile->get($wKarma);
                    }
                }

                $response = [
                    'chat_id' => $chatId,
                    'text' => $responseText,
                ];

                $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
                $sentMessage = json_decode(file_get_contents($apiUrl), true);

                if (isset($sentMessage['result']['message_id'])) {
                    sleep(10);
                    $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
                    file_get_contents($deleteUrl);
                }
            } else {
                $user = $modx->newObject('modUser');
                $user->set('username', $username);
                $user->set('active', 1);

                $email = $username . '@example.com';
                $user->set('email', $email);

                $profile = $modx->newObject('modUserProfile');

                if ($hasPlusPlus || $hasPlusEmoji) {
                    $randomPhrase = $plusPhrases[array_rand($plusPhrases)];
                    $wKarma = getKarmaField($chatId);
                    $profile->set($wKarma, 1);
                    $reactionEmoji = '👍';
                    if($randPhrases) {
                        $responseText = "$randomPhrase\n\n@$username добавлен в список! Карма увеличена на 1!\nТекущая карма: 1";
                    } else {
                        $responseText = "@$username добавлен в список! Карма увеличена на 1!\nТекущая карма: 1";
                    }
                } elseif ($hasMinusMinus || $hasMinusEmoji) {
                    $randomPhrase = $minusPhrases[array_rand($minusPhrases)];
                    $wKarma = getKarmaField($chatId);
                    $profile->set($wKarma, -1);
                    $reactionEmoji = '👌';
                    if($randPhrases) {
                        $responseText = "$randomPhrase\n\n@$username добавлен в список! Карма уменьшена на 1!\nТекущая карма: -1";
                    } else {
                        $responseText = "@$username добавлен в список! Карма уменьшена на 1!\nТекущая карма: -1";
                    }
                }

                $user->addOne($profile);
                $user->save();

                $reactionResponse = [
                    'chat_id' => $chatId,
                    'message_id' => $message['message_id'],
                    'reaction' => json_encode([['type' => 'emoji', 'emoji' => $reactionEmoji]]),
                ];

                $apiUrl = "https://api.telegram.org/bot$botToken/setMessageReaction";
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($reactionResponse),
                    ],
                ];
                $context  = stream_context_create($options);
                file_get_contents($apiUrl, false, $context);

                $response = [
                    'chat_id' => $chatId,
                    'text' => $responseText,
                ];

                $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
                $sentMessage = json_decode(file_get_contents($apiUrl), true);

                if (isset($sentMessage['result']['message_id'])) {
                    sleep(10);
                    $deleteUrl = "https://api.telegram.org/bot$botToken/deleteMessage?chat_id=$chatId&message_id=" . $sentMessage['result']['message_id'];
                    file_get_contents($deleteUrl);
                }
            }
        } else {
            $response = [
                'chat_id' => $chatId,
                'text' => "$howToUse",
            ];

            $apiUrl = "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query($response);
            file_get_contents($apiUrl);
            return;
        }
    }
}
