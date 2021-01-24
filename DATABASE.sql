use pwpay;

CREATE TABLE `user` (
  `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `password` VARCHAR(255) NOT NULL DEFAULT '',
  `birthdate` DATETIME NOT NULL,
  `phone` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  `auth_token` VARCHAR(255) NOT NULL DEFAULT '',
  `is_activated` BOOLEAN,
  `balance` DOUBLE(10,2) NOT NULL DEFAULT 0,
  `owner_name` VARCHAR(255) NOT NULL DEFAULT '',
  `iban` VARCHAR(255) NOT NULL DEFAULT '',
  `profile_picture` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `transaction` (
  `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) unsigned NOT NULL,
  `amount` DOUBLE(10,2) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES user(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `money_request` (
  `id` INT(11) unsigned NOT NULL,
  `requester_id` INT(11) unsigned NOT NULL,
  `is_completed` BOOLEAN DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id`) REFERENCES transaction(`id`),
  FOREIGN KEY (`requester_id`) REFERENCES user(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `money_send` (
  `id` INT(11) unsigned NOT NULL,
  `recipient_id` INT(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id`) REFERENCES transaction(`id`),
  FOREIGN KEY (`recipient_id`) REFERENCES user(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `money_charge` (
  `id` INT(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id`) REFERENCES transaction(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

