-- =====================================================================
-- Feature 006 — Ajustes ingreso/vaciado
-- Bloque para agregar al dump de producción (antes de las líneas finales
-- /*!40101 SET CHARACTER_SET_CLIENT=@OLD... */;). Ver procedimiento en memoria.
-- NOTA: verificar el último id de `migrations` en el dump y continuar la
-- numeración (este bloque asume que feature 005 dejó hasta id 41 → batch 9).
-- =====================================================================

-- 1) Tabla nueva: ingresos (padre BL)
CREATE TABLE `ingresos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bl` varchar(100) NOT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ingresos_cliente_id_index` (`cliente_id`),
  KEY `ingresos_fecha_ingreso_index` (`fecha_ingreso`),
  KEY `ingresos_bl_index` (`bl`),
  CONSTRAINT `ingresos_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `users` (`id`),
  CONSTRAINT `ingresos_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Vínculo contenedor → ingreso (nullable, compatibilidad)
ALTER TABLE `contenedores`
  ADD `ingreso_id` bigint(20) UNSIGNED DEFAULT NULL AFTER `id`,
  ADD KEY `contenedores_ingreso_id_foreign` (`ingreso_id`),
  ADD CONSTRAINT `contenedores_ingreso_id_foreign` FOREIGN KEY (`ingreso_id`) REFERENCES `ingresos` (`id`);

-- 3) Backfill: crear un Ingreso por cada contenedor con BL y sin ingreso, y vincularlo
--    (los ingresos de un solo contenedor de feature 005 siguen apareciendo en el listado)
INSERT INTO `ingresos` (`bl`, `cliente_id`, `fecha_ingreso`, `usuario_id`, `created_at`, `updated_at`)
SELECT c.`bl`,
       (SELECT r.`cliente_id` FROM `referencias` r WHERE r.`contenedor_id` = c.`id` LIMIT 1),
       DATE(COALESCE(c.`fecha_ingreso`, NOW())),
       NULL, NOW(), NOW()
FROM `contenedores` c
WHERE c.`bl` IS NOT NULL AND c.`ingreso_id` IS NULL;

-- Vincular cada contenedor legado con el ingreso recién creado para su mismo BL/fecha.
-- (En feature 005 cada contenedor tenía su propio BL; si hubiese BLs compartidos,
--  revisar manualmente. Para datos típicos, el match por bl + fecha es suficiente.)
UPDATE `contenedores` c
JOIN `ingresos` i ON i.`bl` = c.`bl` AND i.`fecha_ingreso` = DATE(COALESCE(c.`fecha_ingreso`, NOW()))
SET c.`ingreso_id` = i.`id`
WHERE c.`ingreso_id` IS NULL AND c.`bl` IS NOT NULL;

-- 4) Registrar las migraciones (ajustar ids/batch según el dump real)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(42, '2026_06_26_000001_create_ingresos_table', 9),
(43, '2026_06_26_000002_add_ingreso_id_to_contenedores_table', 9),
(44, '2026_06_26_000003_backfill_ingresos_from_contenedores_table', 9);

-- =====================================================================
-- Fin Feature 006
-- =====================================================================
