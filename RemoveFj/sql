CREATE TABLE `error_log` (
  `error_message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fj_remove` (
  `menuid` int(11) NOT NULL DEFAULT 0,
  `tagid` int(10) NOT NULL DEFAULT 0,
  `articles` longtext COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `query` longtext COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `flag` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
