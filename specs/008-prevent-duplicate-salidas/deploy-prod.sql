-- ============================================================
-- Despliegue en producción — Feature 008 (idempotencia salidas)
-- Hosting compartido sin SSH: la tabla se crea vía migración, pero
-- si se aplica por dump/import manual, usar este prelude import-safe
-- (evita #1050 "Table already exists" en reimportaciones).
-- MariaDB / MySQL 8.
-- ============================================================

DROP TABLE IF EXISTS `idempotency_keys`;

CREATE TABLE `idempotency_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token` char(36) NOT NULL,
  `scope` varchar(40) NOT NULL,
  `usuario_id` bigint unsigned NOT NULL,
  `resource_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_keys_token_unique` (`token`),
  KEY `idempotency_keys_scope_resource_id_index` (`scope`, `resource_id`),
  KEY `idempotency_keys_created_at_index` (`created_at`),
  KEY `idempotency_keys_usuario_id_foreign` (`usuario_id`),
  CONSTRAINT `idempotency_keys_usuario_id_foreign`
    FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nota: si se despliega con `php artisan migrate`, NO ejecutar este archivo;
-- la migración 2026_07_08_000001_create_idempotency_keys_table.php ya lo crea.
-- Registrar la migración como ejecutada en `migrations` si se aplicó por SQL:
-- INSERT INTO migrations (migration, batch)
--   VALUES ('2026_07_08_000001_create_idempotency_keys_table', (SELECT COALESCE(MAX(batch),0)+1 FROM (SELECT batch FROM migrations) m));
