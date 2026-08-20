<?php

namespace App\Services;

use GuzzleHttp\Client;
use RuntimeException;

class WeatherService
{
    private Client $http;
    private float $lat;
    private float $lon;
    private string $timezone;

    public function __construct(float $lat, float $lon, string $timezone)
    {
        $this->http = new Client(['timeout' => 10]);
        $this->lat = $lat;
        $this->lon = $lon;
        $this->timezone = $timezone;
    }

    /**
     * Devuelve la temperatura máxima pronosticada para HOY.
     * Lanza RuntimeException si la API falla o el dato no viene.
     */
    public function getTodayMaxTemperature(): float
    {
        $response = $this->http->get('https://api.open-meteo.com/v1/forecast', [
            'query' => [
                'latitude'      => $this->lat,
                'longitude'     => $this->lon,
                'daily'         => 'temperature_2m_max',
                'timezone'      => $this->timezone,
                'forecast_days' => 1,
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (!isset($data['daily']['temperature_2m_max'][0])) {
            throw new RuntimeException('Open-Meteo no devolvió temperature_2m_max en la respuesta.');
        }

        return (float) $data['daily']['temperature_2m_max'][0];
    }
}