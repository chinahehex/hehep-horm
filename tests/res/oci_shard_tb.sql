CREATE TABLE "web_admin_users_info" (
  "id" NUMBER  PRIMARY KEY,
  "userId" integer DEFAULT 0,
  "tel" VARCHAR (200) DEFAULT '',
  "realName" varchar(60) DEFAULT '',
  "headPortrait" varchar(150) DEFAULT '',
  "sex" varchar(30)  DEFAULT '',
  "education" varchar(30)  DEFAULT '' ,
  "roleId" integer DEFAULT 0 ,
  "caid" integer DEFAULT '0',
  "ctime" varchar(32) DEFAULT ''
) ;

CREATE SEQUENCE "web_admin_users_info_seq" INCREMENT BY 1 START WITH 1;

create or replace trigger "web_admin_users_info_trigger"
  before insert on "web_admin_users_info"
  for each row
begin
select "web_admin_users_info_seq".nextval into :new."id" from dual--#
end--#;
