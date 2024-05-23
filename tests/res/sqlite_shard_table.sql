CREATE TABLE `web_admin_users_info_{{:shard}}` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `userId` INTEGER NOT NULL DEFAULT 0,
  `tel` TEXT NOT NULL DEFAULT '',
  `realName` TEXT NOT NULL DEFAULT '',
  `headPortrait` TEXT NOT NULL DEFAULT '',
  `sex` TEXT NOT NULL DEFAULT '',
  `education` TEXT NOT NULL DEFAULT '',
  `roleId` INTEGER NOT NULL DEFAULT 0,
  `caid` INTEGER NOT NULL DEFAULT 0,
  `ctime` TEXT NOT NULL DEFAULT ''
);










