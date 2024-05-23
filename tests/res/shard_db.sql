-- hehe db
-- 字段列备注格式:name:列名,type:数据类型,form:表单名,desc:说明

CREATE TABLE `web_admin_users_info` (
   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
   `userId` bigint(20) DEFAULT 0 COMMENT '用户id',
   `tel` VARCHAR (200) DEFAULT '' COMMENT '手机号',
   `realName` varchar(60) DEFAULT '' COMMENT '真实姓名',
   `headPortrait` varchar(150) NOT NULL DEFAULT '' COMMENT '头像:自然人头像',
   `sex` varchar(30) NOT NULL DEFAULT '' COMMENT '性别:男,女,不限',
   `education` varchar(30) NOT NULL DEFAULT '' COMMENT '学历:初中,高中',
   `roleId` bigint(20) DEFAULT 0 COMMENT '角色id',
   `caid` bigint(20) DEFAULT '0' COMMENT '创建人id',
   `ctime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间:格式Y-m-d H:i:s',
   PRIMARY KEY (`id`),
   KEY `index_userId` (`userId`) USING BTREE,
   KEY `index_tel` (`tel`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理人员信息表';










