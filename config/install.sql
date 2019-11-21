CREATE TABLE `attachment` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_upload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mime_type` varchar(31) NOT NULL,
  `filename` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `chat` (
  `id` int(10) UNSIGNED NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` varchar(127) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `chat_id` int(10) UNSIGNED NOT NULL,
  `attachment_id` int(10) UNSIGNED DEFAULT NULL,
  `contenido` varchar(1023) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `participa` (
  `chat_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_read` int(10) UNSIGNED DEFAULT NULL
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
  `name` varchar(127) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `option` (`id`, `key`, `type`, `name`, `value`) VALUES
(1, 'regex_password', 'text', 'Password conditions', '^(?=.*[0-9]+)(?=.*[A-Z]+)(?=.*[a-z]+).{6,16}$'),
(2, 'regex_name', 'text', 'Names conditions', '^\\w[ \\w]{2,32}\\w$'),
(3, 'regex_email', 'text', 'E-mail conditions', '^[^@]+@[^@]+[a-zA-Z]{2,}$'),
(4, 'color_main', 'color', 'Main color', '#1b377a'),
(5, 'color_bg', 'color', 'Background color', '#f0f5ff'),
(6, 'color_border', 'color', 'Border color', '#939db5'),
(7, 'image_maxweight', 'number', 'Max avatar weight (KB)', '512'),
(8, 'attachment_maxweight', 'number', 'Max attachment file weight (KB)', '2048'),
(9, 'email_confirm', 'number', 'E-mail confirmation required', '0'),
(10, 'email_host', 'text', 'E-mail host', ''),
(11, 'email_user', 'text', 'E-mail user', ''),
(12, 'email_pass', 'text', 'E-mail password', ''),
(13, 'email_from', 'text', 'E-mail from address', ''),
(14, 'email_name', 'text', 'E-mail from name', '');

ALTER TABLE `attachment`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `contacto`
  ADD PRIMARY KEY (`usuario_1_id`,`usuario_2_id`),
  ADD KEY `FK_contacto_usuario_2` (`usuario_2_id`),
  ADD KEY `FK_contacto_usuario_estado` (`usuario_estado_id`);

ALTER TABLE `mensaje`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attachment_id` (`attachment_id`),
  ADD KEY `FK_mensaje_usuario` (`usuario_id`),
  ADD KEY `FK_mensaje_chat` (`chat_id`);

ALTER TABLE `participa`
  ADD PRIMARY KEY (`chat_id`,`usuario_id`),
  ADD KEY `FK_participa_usuario` (`usuario_id`),
  ADD KEY `FK_participa_mensaje` (`last_read`);

ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `option`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `attachment`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `chat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `mensaje`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `usuario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `option`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

ALTER TABLE `contacto`
  ADD CONSTRAINT `FK_contacto_usuario_1` FOREIGN KEY (`usuario_1_id`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `FK_contacto_usuario_2` FOREIGN KEY (`usuario_2_id`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `FK_contacto_usuario_estado` FOREIGN KEY (`usuario_estado_id`) REFERENCES `usuario` (`id`);

ALTER TABLE `mensaje`
  ADD CONSTRAINT `FK_mensaje_attachment` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  ADD CONSTRAINT `FK_mensaje_chat` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`),
  ADD CONSTRAINT `FK_mensaje_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

ALTER TABLE `participa`
  ADD CONSTRAINT `FK_participa_chat` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`),
  ADD CONSTRAINT `FK_participa_mensaje` FOREIGN KEY (`last_read`) REFERENCES `mensaje` (`id`),
  ADD CONSTRAINT `FK_participa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);
