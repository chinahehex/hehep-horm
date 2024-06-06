CREATE TABLE web_admin_users_info (
    "id" INT IDENTITY(1,1) PRIMARY KEY,
    "userId" integer DEFAULT 0,
    "tel" VARCHAR (200) DEFAULT '',
    "realName" varchar(60) DEFAULT '',
    "headPortrait" varchar(150) NOT NULL DEFAULT '',
    "sex" varchar(30) NOT NULL DEFAULT '',
    "education" varchar(30) NOT NULL DEFAULT '' ,
    "roleId" integer DEFAULT 0 ,
    "caid" integer DEFAULT '0',
    "ctime" varchar(32) DEFAULT ''
) ;










