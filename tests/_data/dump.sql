DROP TABLE IF EXISTS `profile`;
CREATE TABLE `profile` (
                           `id` int NOT NULL AUTO_INCREMENT,
                           `name` varchar(255) NOT NULL,
                           `nick_name` varchar(255) NOT NULL,
                           PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `profile` (`id`, `name`, `nick_name`) VALUES
                                                      (1,	'John Doe',	'Administrator'),
                                                      (2,	'Jane Doe',	'User');

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
                        `id` int NOT NULL AUTO_INCREMENT,
                        `email_address` varchar(255) NOT NULL,
                        `profile_id` int DEFAULT NULL,
                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `user` (`id`, `email_address`, `profile_id`) VALUES
                                                             (1,	'admin@admin.com',	1),
                                                             (2,	'user@user.com',	2);
