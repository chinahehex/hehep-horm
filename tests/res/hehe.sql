-- hehe db
-- 字段列备注格式:name:列名,type:数据类型,form:表单名,desc:说明
CREATE TABLE `web_admin_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `username` VARCHAR (200) DEFAULT '' COMMENT '用户名',
  `password` varchar(100) DEFAULT '' COMMENT '登录密码:',
  `tel` VARCHAR (200) DEFAULT '' COMMENT '手机号',
  `realName` varchar(60) DEFAULT '' COMMENT '真实姓名',
  `headPortrait` varchar(150) NOT NULL DEFAULT '' COMMENT '头像:自然人头像',
  `safeCode` varchar(32) DEFAULT '' COMMENT '安全码随机码:作为加密key,',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态: 1 可用 2 禁用',
  `roleId` bigint(20) DEFAULT 0 COMMENT '角色id',
  `caid` bigint(20) DEFAULT '0' COMMENT '创建人id',
  `ctime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间:格式Y-m-d H:i:s',
   PRIMARY KEY (`id`),
   KEY `index_username` (`username`) USING BTREE,
   KEY `index_tel` (`tel`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理人员表';

INSERT INTO `web_admin_users`(`id`, `username`, `password`, `tel`, `realName`, `headPortrait`, `safeCode`, `status`, `roleId`, `caid`, `ctime`) VALUES (1, 'hehe1', '2a6798100721c14a037d5694017c3439', '13564768842', 'hehe', 'res/ad\\headimg\\2021/08/01\\610606abb1058.jpg', '', 1, 1, 0, '2021-03-02 20:29:57');
INSERT INTO `web_admin_users`(`id`, `username`, `password`, `tel`, `realName`, `headPortrait`, `safeCode`, `status`, `roleId`, `caid`, `ctime`) VALUES (2, 'admin', '123123', '13564768841', '哈哈熊', '', '', 1, 4, 0, '2021-10-30 10:41:44');
INSERT INTO `web_admin_users`(`id`, `username`, `password`, `tel`, `realName`, `headPortrait`, `safeCode`, `status`, `roleId`, `caid`, `ctime`) VALUES (3, 'hehex', '123123', '13564768841', '熊迪', '', '', 0, 3, 0, '2021-10-30 10:42:22');
INSERT INTO `web_admin_users`(`id`, `username`, `password`, `tel`, `realName`, `headPortrait`, `safeCode`, `status`, `roleId`, `caid`, `ctime`) VALUES (4, 'hello', '123123', '13564768841', '天收你', '', '', 0, 2, 0, '2021-10-30 10:42:52');


CREATE TABLE `web_admin_user_role` (
   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
   `roleName` varchar(100) DEFAULT '',
   `status` tinyint(1) DEFAULT '0' COMMENT '状态: 1 可用 0 禁用',
   `ctime` datetime COMMENT '创建时间:格式Y-m-d H:i:s',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理人员角色表';

INSERT INTO `web_admin_user_role`(`id`, `roleName`, `status`, `ctime`) VALUES (1, '超级管理员', 1, '2021-03-02 20:38:10');
INSERT INTO `web_admin_user_role`(`id`, `roleName`, `status`, `ctime`) VALUES (2, '销售人员', 1, '2021-03-03 20:38:10');
INSERT INTO `web_admin_user_role`(`id`, `roleName`, `status`, `ctime`) VALUES (3, '运营人员', 0, '2023-03-03 20:38:10');
INSERT INTO `web_admin_user_role`(`id`, `roleName`, `status`, `ctime`) VALUES (4, '财务人员', 1, '2023-03-04 20:38:10');










