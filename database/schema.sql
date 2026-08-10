CREATE TABLE IF NOT EXISTS run_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    run_date DATE NOT NULL,                 -- para localizar "el registro de hoy" desde el cron de mediodía
    executed_at DATETIME NOT NULL,
    city VARCHAR(100) NOT NULL,
    temperature_celsius DECIMAL(4,1) NOT NULL,
    liters_per_adult DECIMAL(4,2) NOT NULL,
    liters_baby DECIMAL(4,2) NOT NULL,
    total_liters DECIMAL(5,2) NOT NULL,
    boils_needed INT NOT NULL,
    morning_notified TINYINT(1) DEFAULT 0,
    noon_notified TINYINT(1) DEFAULT 0,
    notification_status ENUM('pending','sent','failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_run_date (run_date)      -- garantiza un solo cálculo por día, protege contra doble ejecución del cron de las 6am
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;