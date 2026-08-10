<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TelegramNotifier
{
    private Client $http;

    public function __construct(private string $botToken, private string $chatId) 
    {
        $this->http = new Client(['timeout' => 10]);
    }

    /**
     * Envía un mensaje de texto plano al chat configurado.
     * Retorna true si Telegram confirmó el envío, false si falló.
     */
    public function send(string $message): bool
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        try {
            $response = $this->http->post($url, [
                'json' => [
                    'chat_id'    => $this->chatId,
                    'text'       => $message,
                    'parse_mode' => 'HTML',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            return $data['ok'] ?? false;

        } catch (GuzzleException $e) {
            // No relanzamos: una notificación fallida no debe tumbar
            // el resto del script (el log en DB ya se guardó igual).
            error_log("Telegram notification failed: " . $e->getMessage());
            return false;
        }
    }
}