CREATE TABLE `chat` (
  `id` int(10) UNSIGNED NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` varchar(127) DEFAULT NULL
);

CREATE TABLE `contacto` (
  `usuario_1_id` int(10) UNSIGNED NOT NULL,
  `usuario_2_id` int(10) UNSIGNED NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fecha_upd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `usuario_estado_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mensaje` (
  `id` int(10) UNSIGNED NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `chat_id` int(10) UNSIGNED NOT NULL,
  `contenido` varchar(1023) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `participa` (
  `chat_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_readed` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `usuario` (
  `id` int(10) UNSIGNED NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(127) DEFAULT NULL,
  `nombre` varchar(127) DEFAULT NULL,
  `password` char(60) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `confirmado` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `admin` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `clave` varchar(32) DEFAULT NULL,
  `caducidad` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `option` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(127) NOT NULL,
  `type` varchar(127) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `option` (`id`, `key`, `type`, `value`) VALUES
(1, 'regex_password', 'text', '^(?=.*[0-9]+)(?=.*[A-Z]+)(?=.*[a-z]+).{6,16}$'),
(2, 'regex_name', 'text', '^[a-zA-Z\\s].{3,32}$'),
(3, 'regex_email', 'text', '^[^@]+@[^@]+\\.[a-zA-Z]{2,}$'),
(4, 'mail_confirm', 'number', '1'),
(5, 'color_main', 'color', '#1b377a'),
(6, 'color_bg', 'color', '#f0f5ff'),
(7, 'color_border', 'color', '#939db5'),
(8, 'image_maxweight', 'number', '512');

ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `contacto`
  ADD PRIMARY KEY (`usuario_1_id`,`usuario_2_id`),
  ADD KEY `FK_contacto_usuario_2` (`usuario_2_id`),
  ADD KEY `FK_contacto_usuario_estado` (`usuario_estado_id`);

ALTER TABLE `mensaje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_mensaje_usuario` (`usuario_id`),
  ADD KEY `FK_mensaje_chat` (`chat_id`);

ALTER TABLE `participa`
  ADD PRIMARY KEY (`chat_id`,`usuario_id`),
  ADD KEY `FK_participa_usuario` (`usuario_id`),
  ADD KEY `FK_participa_mensaje` (`last_readed`);

ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `option`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `chat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `mensaje`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `usuario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `option`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

ALTER TABLE `contacto`
  ADD CONSTRAINT `FK_contacto_usuario_1` FOREIGN KEY (`usuario_1_id`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `FK_contacto_usuario_2` FOREIGN KEY (`usuario_2_id`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `FK_contacto_usuario_estado` FOREIGN KEY (`usuario_estado_id`) REFERENCES `usuario` (`id`);

ALTER TABLE `mensaje`
  ADD CONSTRAINT `FK_mensaje_chat` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`),
  ADD CONSTRAINT `FK_mensaje_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

ALTER TABLE `participa`
  ADD CONSTRAINT `FK_participa_chat` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`),
  ADD CONSTRAINT `FK_participa_mensaje` FOREIGN KEY (`last_readed`) REFERENCES `mensaje` (`id`),
  ADD CONSTRAINT `FK_participa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);
