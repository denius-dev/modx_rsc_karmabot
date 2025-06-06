# MODX RSC | Telegram Karma Bot
🤖 Telegram-бот для управления кармой пользователей в чате. Бот позволяет добавлять или убирать карму, банить пользователей за рекламу и отображать топ пользователей по карме.

## Возможности
- **Управление кармой**: Пользователи могут добавлять или убирать карму, используя `++`, `--`, смайлики или команды.
- **Бан за рекламу**: Бот автоматически банит пользователей, которые отправляют сообщения с запрещёнными фразами.
- **Топ пользователей**: Команда `/top` показывает топ-20 пользователей по карме.
- **Поддержка MODX**: Интеграция с MODX для хранения данных о пользователях и их карме.
- **Интеграция с ИИ через bothub.chat**: Возможность задавать вопросы ИИ с помощью дописания фразы `ИИ ответь тоже`.

## Установка

### Требования
- PHP 7.4 или выше
- MODX (для хранения данных о карме)

### Шаги установки
1. **Создайте бота в Telegram**:
   - Откройте Telegram и найдите бота BotFather.
   - Создайте нового бота с помощью команды `/newbot`.
   - Сохраните токен бота, он понадобится для настройки.

2. **Клонируйте репозиторий**:
   ```bash
   git clone https://github.com/ваш-username/modx_rsc_karmabot.git
   cd modx_rsc_karmabot
   ```

3. **Настройте конфигурацию**:
   - Откройте файл `config.php` и укажите:
     - Токен вашего бота.
     - ID чатов, в которых бот будет работать.
     - Смайлики для плюсов и минусов.
     - Фильтры для бана рекламы.

4. **Подключите MODX**:
   - Убедитесь, что MODX установлен и доступен.
   - Укажите путь к файлам MODX в `modx_connection.php`.

5. **Загрузите бота на сервер**:
   - Загрузите файлы на ваш сервер (например, через FTP или SSH).

6. **Настройте вебхук**:
   - Укажите URL вашего бота в Telegram с помощью ссылки:
     ```
     https://api.telegram.org/bot<ваш-токен>/setWebhook?url=https://ваш-домен.ru/karmabot/index.php
     ```

## Интеграция с bothub.ru
### Как это работает
   - Бот использует API bothub.chat для взаимодействия с ИИ.

   - Когда пользователь отправляет сообщение с фразой "ИИ ответь тоже", бот отправляет вопрос в bothub.chat и возвращает ответ в чат.

### Настройка
   - Получите API-ключ от bothub.chat.

   - Укажите ключ в файле config.php:

```php
$bothubApiKey = 'ваш-api-ключ-от-bothub';
$bothubApiUrl = 'https://bothub.chat/api/v2/openai/v1/chat/completions'; // URL API bothub.chat
```
   - Настройте обработку по фразам в message_handler.php.


## Использование

### Команды бота
- **Добавить карму**: Ответьте на сообщение пользователя с `++` или используйте смайлики: 👍, ❤️, 🤝, 🙌, 🔥.
- **Убрать карму**: Ответьте на сообщение пользователя с `--` или используйте смайлики: 👎, 🖕, 💩.
- **Топ пользователей**: Используйте команду `/top`, чтобы увидеть топ-20 пользователей по карме.
- **Помощь**: Используйте команду `/help`, чтобы получить инструкции по использованию бота.

### Команды админа(ов)
 - **Добавить фразу для фильтрации спамеров `/addf фраза`**
 - **Удалить фразу из фильтрации спамеров `/rmf фраза`**
 - **Посмотреть последние 20 фраз фильтрации и сколько ещё добавленных `/lf`**
 - P.S. В фразах можно использовать между словами символ `*`, для указания боту на вариативный контент между словами.

### Примеры
**Добавление кармы**:
- Пользователь A: "Спасибо за помощь!"
- Пользователь B (ответ на сообщение A): `++`
- Бот: "*Случайная фраза*
  
  Карма @UserA увеличена на 1! Текущая карма: 5."

**Уменьшение кармы**:
- Пользователь A: "Это было не очень хорошо..."
- Пользователь B (ответ на сообщение A): `--`
- Бот: "Карма @UserA уменьшена на 1! Текущая карма: 4."

**Топ пользователей**:
- Пользователь A: `/top`
- Бот:
  ```
  🏆 Топ 20 пользователей по карме:
  
  Пользователь B (@UserB): 10
  Пользователь C (@UserC): 8
  Пользователь A (@UserA): 5
  ...
  ```

## Настройка фильтров
Файл `config.php` содержит массив `$filters`, в который можно добавить фразы для бана рекламы. Например:
```php
$filters = [
    "дополнительный заработок",
    "удаленная работа",
    "заработок в интернете",
];
```

## Лицензия
Этот проект распространяется под лицензией MIT License. Подробности см. в файле [LICENSE](LICENSE).

## Вклад в проект
Если вы хотите внести свой вклад в проект, пожалуйста, следуйте этим шагам:
1. Форкните репозиторий.
2. Создайте новую ветку (`git checkout -b feature/ваш-фич`).
3. Зафиксируйте изменения (`git commit -m 'Добавлен новый функционал'`).
4. Запушьте ветку (`git push origin feature/ваш-фич`).
5. Создайте Pull Request.

## Автор
👤 Денис Усманов

GitHub: [denius-dev](https://github.com/denius-dev)  
Telegram: [@denius_dev](https://t.me/denius_dev)

## Благодарности
Спасибо MODX за мощную CMS.  
Спасибо Telegram Bot API за удобный API.

Если у вас есть вопросы или предложения, создайте Issue или свяжитесь со мной через Telegram.
