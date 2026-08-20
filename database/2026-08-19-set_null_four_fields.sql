ALTER TABLE run_logs
    MODIFY temperature_celsius DOUBLE(16,2) NULL,
    MODIFY liters_per_adult DOUBLE(16,2) NULL,
    MODIFY liters_baby DOUBLE(16,2) NULL,
    MODIFY total_liters DOUBLE(16,2) NULL,
    MODIFY boils_needed INT NULL;