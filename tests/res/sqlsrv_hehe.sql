CREATE TABLE web_admin_users (
 "id" INT IDENTITY(1,1) PRIMARY KEY,
 "username" VARCHAR (200) DEFAULT '',
 "password" varchar(100) DEFAULT '' ,
 "tel" VARCHAR (200) DEFAULT '' ,
 "realName" varchar(60) DEFAULT '' ,
 "headPortrait" varchar(150) DEFAULT '' ,
 "safeCode" varchar(32) DEFAULT '' ,
 "status" integer DEFAULT 0 ,
 "roleId" integer DEFAULT 0 ,
 "caid" integer DEFAULT 0,
 "ctime" varchar(32) DEFAULT ''
);

SET IDENTITY_INSERT web_admin_users on;


INSERT INTO web_admin_users("id", "username", "password", "tel", "realName", "headPortrait", "safeCode", "status", "roleId", "caid", "ctime") VALUES (1, 'hehe1', '2a6798100721c14a037d5694017c3439', '13564768842', 'hehe', 'res/ad\\headimg\\2021/08/01\\610606abb1058.jpg', '', 1, 1, 0, '2021-03-02 20:29:57');
INSERT INTO web_admin_users("id", "username", "password", "tel", "realName", "headPortrait", "safeCode", "status", "roleId", "caid", "ctime") VALUES (2, 'admin', '123123', '13564768841', '哈哈熊', '', '', 1, 4, 0, '2021-10-30 10:41:44');
INSERT INTO web_admin_users("id", "username", "password", "tel", "realName", "headPortrait", "safeCode", "status", "roleId", "caid", "ctime") VALUES (3, 'hehex', '123123', '13564768841', '熊迪', '', '', 0, 3, 0, '2021-10-30 10:42:22');
INSERT INTO web_admin_users("id", "username", "password", "tel", "realName", "headPortrait", "safeCode", "status", "roleId", "caid", "ctime") VALUES (4, 'hello', '123123', '13564768841', '天收你', '', '', 0, 2, 0, '2021-10-30 10:42:52');

SET IDENTITY_INSERT web_admin_users off;

CREATE TABLE web_admin_user_role (
   "id" INT IDENTITY(1,1) PRIMARY KEY,
   "roleName" varchar(100) DEFAULT '',
   "status" integer DEFAULT 0,
   "ctime" varchar(32) DEFAULT ''
);

SET IDENTITY_INSERT web_admin_user_role on;

INSERT INTO web_admin_user_role("id", "roleName", "status", "ctime") VALUES (1, '超级管理员', 1, '2021-03-02 20:38:10');
INSERT INTO web_admin_user_role("id", "roleName", "status", "ctime") VALUES (2, '销售人员', 1, '2021-03-03 20:38:10');
INSERT INTO web_admin_user_role("id", "roleName", "status", "ctime") VALUES (3, '运营人员', 0, '2023-03-03 20:38:10');
INSERT INTO web_admin_user_role("id", "roleName", "status", "ctime") VALUES (4, '财务人员', 1, '2023-03-04 20:38:10');

SET IDENTITY_INSERT web_admin_user_role off;








