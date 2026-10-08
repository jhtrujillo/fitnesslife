CREATE TABLE IF NOT EXISTS `marcas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `img` varchar(255) DEFAULT NULL,
  `descripcion` text,
  `pos` int DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `pos` int DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `productos` ADD COLUMN `categoria_id` int DEFAULT NULL AFTER `series`;

-- Insert default hardcoded brands
INSERT INTO `marcas` (`name`, `slug`, `img`, `pos`) VALUES
('Life Fitness', 'lifefitness', 'assets/brand_lifefitness.webp', 1),
('Precor', 'precor', 'assets/brand_precor.webp', 2),
('Matrix', 'matrix', 'assets/brand_matrix.webp', 3),
('Freemotion', 'freemotion', 'assets/brand_freemotion.webp', 4),
('Hoist', 'hoist', 'assets/brand_hoist.webp', 5),
('True Fitness', 'truefitness', 'assets/brand_truefitness.webp', 6),
('Realleader', 'realleader', 'assets/brand_realleader.webp', 7),
('Keiser', 'keiser', 'assets/brand_keiser.webp', 8);

-- Insert default hardcoded categories
INSERT INTO `categorias` (`name`, `slug`, `tag`, `img`, `badge`, `pos`) VALUES
('Elípticas', 'elipticas', 'Cardio de bajo impacto', 'assets/cat_eliptica_main.webp', '01', 1),
('Escaleras', 'bicicletas', 'Cardio de alta intensidad', 'assets/cat_escaleras_v3.webp', '02', 2),
('Trotadoras', 'trotadoras', 'Caminadoras profesionales', 'assets/cat_trotadora_main.webp', '03', 3),
('Máquinas de Pesas', 'pesas', 'Fuerza y musculación', 'assets/cat_pesas_main.webp', '04', 4),
('Línea Comercial', 'comercial', 'Dotación para gimnasios', 'assets/sol_comercial.webp', '', 5),
('Línea Institucional', 'institucional', 'Hoteles y Clubes', 'assets/sol_institucional.webp', '', 6),
('Equipos para Hogar', 'hogar', 'Entrena en casa', 'assets/sol_hogar.webp', '', 7);
