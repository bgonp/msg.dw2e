CREATE TABLE `attachment` (
  `id` int(10) UNSIGNED NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mime_type` varchar(31) NOT NULL,
  `height` int(10) UNSIGNED DEFAULT NULL,
  `width` int(10) UNSIGNED DEFAULT NULL,
  `filename` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `chat` (
  `id` int(10) UNSIGNED NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(127) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `contact` (
  `user_1_id` int(10) UNSIGNED NOT NULL,
  `user_2_id` int(10) UNSIGNED NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_upd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `state` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `user_state_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `message` (
  `id` int(10) UNSIGNED NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `chat_id` int(10) UNSIGNED NOT NULL,
  `attachment_id` int(10) UNSIGNED DEFAULT NULL,
  `content` varchar(1023) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `participate` (
  `chat_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_read` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user` (
  `id` int(10) UNSIGNED NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(127) DEFAULT NULL,
  `name` varchar(127) DEFAULT NULL,
  `password` char(60) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `confirmed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `admin` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `code` varchar(32) DEFAULT NULL,
  `expiration` timestamp NULL DEFAULT NULL
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

ALTER TABLE `contact`
  ADD PRIMARY KEY (`user_1_id`,`user_2_id`),
  ADD KEY `FK_contact_user_2` (`user_2_id`),
  ADD KEY `FK_contact_user_state` (`user_state_id`);

ALTER TABLE `message`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attachment_id` (`attachment_id`),
  ADD KEY `FK_message_user` (`user_id`),
  ADD KEY `FK_message_chat` (`chat_id`);

ALTER TABLE `participate`
  ADD PRIMARY KEY (`chat_id`,`user_id`),
  ADD KEY `FK_participate_user` (`user_id`),
  ADD KEY `FK_participate_message` (`last_read`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `option`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `attachment`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `chat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `message`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `option`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

ALTER TABLE `contact`
  ADD CONSTRAINT `FK_contact_user_1` FOREIGN KEY (`user_1_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_contact_user_2` FOREIGN KEY (`user_2_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_contact_user_state` FOREIGN KEY (`user_state_id`) REFERENCES `user` (`id`);

ALTER TABLE `message`
  ADD CONSTRAINT `FK_message_attachment` FOREIGN KEY (`attachment_id`) REFERENCES `attachment` (`id`),
  ADD CONSTRAINT `FK_message_chat` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`),
  ADD CONSTRAINT `FK_message_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

ALTER TABLE `participate`
  ADD CONSTRAINT `FK_participate_chat` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`),
  ADD CONSTRAINT `FK_participate_message` FOREIGN KEY (`last_read`) REFERENCES `message` (`id`),
  ADD CONSTRAINT `FK_participate_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
