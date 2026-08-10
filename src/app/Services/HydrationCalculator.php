<?php

namespace App\Services;

class HydrationCalculator
{
    private const BASE_LITERS_PER_ADULT = 2.0;   // a 25°C de referencia
    private const REFERENCE_TEMP = 25.0;
    private const INCREMENT_PER_DEGREE = 0.08;   // calibrado con el dato real: 2.4L a 30°C
    private const BABY_LITERS_FIXED = 0.245;     // 245ml, punto medio de 240-250ml (9 meses)
    private const POT_CAPACITY_LITERS = 5.0;

    public function litersPerAdult(float $temperatureCelsius): float
    {
        $degreesAboveReference = max(0, $temperatureCelsius - self::REFERENCE_TEMP);
        return self::BASE_LITERS_PER_ADULT + (self::INCREMENT_PER_DEGREE * $degreesAboveReference);
    }

    public function calculate(float $temperatureCelsius, int $adultsCount): array
    {
        $litersPerAdult = $this->litersPerAdult($temperatureCelsius);
        $totalAdults    = $litersPerAdult * $adultsCount;
        $totalLiters    = round($totalAdults + self::BABY_LITERS_FIXED, 3);
        $boilsNeeded    = (int) ceil($totalLiters / self::POT_CAPACITY_LITERS);

        return [
            'liters_per_adult' => round($litersPerAdult, 2),
            'liters_baby'      => self::BABY_LITERS_FIXED,
            'total_liters'     => $totalLiters,
            'boils_needed'     => $boilsNeeded,
        ];
    }
}