<?php

namespace App\Repositories;

use PDO;
use PDOException;

class RunLogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Inserta el cálculo del día. Si ya existe un registro para hoy
     * (gracias al UNIQUE KEY uniq_run_date), retorna null en vez de duplicar.
     */
    public function insertToday(array $data): ?int
    {
        $sql = "INSERT INTO run_logs
                (run_date, executed_at, city, temperature_celsius,
                 liters_per_adult, liters_baby, total_liters, boils_needed,
                 morning_notified, notification_status)
                VALUES
                (:run_date, :executed_at, :city, :temperature_celsius,
                 :liters_per_adult, :liters_baby, :total_liters, :boils_needed,
                 0, 'pending')";

        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute($data);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Código 23000 = violación de constraint único (ya corrió hoy)
            if ($e->getCode() === '23000') {
                return null;
            }
            throw $e;
        }
    }

    public function findToday(string $today): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM run_logs WHERE run_date = :today LIMIT 1');
        $stmt->execute(['today' => $today]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}