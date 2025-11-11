<?php

namespace _class;

class TelegramBot
{

    public function sendMessage($message)
    {
        $data = [
            'text' => $message,
            'chat_id' => TELEGRAM_CHAT_ID,
            'parse_mode' => 'html',
        ];

        file_get_contents("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage?" . http_build_query($data) );
    }

}