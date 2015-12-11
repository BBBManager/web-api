INSERT INTO auth_mode (auth_mode_id, name, system_key) VALUES (1, 'Local', 'MD5');
INSERT INTO auth_mode (auth_mode_id, name, system_key) VALUES (2, 'AD/LDAP', 'LDAP');
INSERT INTO auth_mode (auth_mode_id, name, system_key) VALUES (3, 'Mozilla Persona', 'PERSONA');

INSERT INTO access_profile (access_profile_id, name, create_date) values ( 1, 'Administrator', SYSDATE());
INSERT INTO access_profile (access_profile_id, name, create_date) values ( 2, 'Support', SYSDATE());
INSERT INTO access_profile (access_profile_id, name, create_date) values ( 3, 'Privileged User', SYSDATE());
INSERT INTO access_profile (access_profile_id, name, create_date) values ( 4, 'System User', SYSDATE());
INSERT INTO access_profile (access_profile_id, name, create_date) values ( 5, 'Inherited from group', SYSDATE());

insert into meeting_room_action (meeting_room_action_id, name) values(1,'Join meeting room');
insert into meeting_room_action (meeting_room_action_id, name) values(2,'Left meeting room');

INSERT INTO meeting_room_profile (meeting_room_profile_id, name, create_date, last_update) VALUES (1, 'Administrator', SYSDATE(), null);
INSERT INTO meeting_room_profile (meeting_room_profile_id, name, create_date, last_update) VALUES (2, 'Moderator', SYSDATE(), null);
INSERT INTO meeting_room_profile (meeting_room_profile_id, name, create_date, last_update) VALUES (3, 'Presenter', SYSDATE(), null);
INSERT INTO meeting_room_profile (meeting_room_profile_id, name, create_date, last_update) VALUES (4, 'Attendee',  SYSDATE(), null);

INSERT INTO access_log_description (controller, action, description) VALUES ('access-log-descriptions','delete','Log description removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-log-descriptions','get','Log description details');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-log-descriptions','index','Logs description list');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-log-descriptions','post','Log description new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-log-descriptions','put','Log descroption edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-logs','delete','Access log removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-logs','get','Access log details');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-logs','index','Access logs list');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-logs','post','Access log new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-logs','put','Access log edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-profiles','delete','Access profile delete');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-profiles','get','Access profile details');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-profiles','index','Access profiles list');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-profiles','post','Access profile new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('access-profiles','put','Access profile edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('error','error','Error screen');
INSERT INTO access_log_description (controller, action, description) VALUES ('groups','delete','Group removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('groups','get','Group details');
INSERT INTO access_log_description (controller, action, description) VALUES ('groups','index','Groups list');
INSERT INTO access_log_description (controller, action, description) VALUES ('groups','post','Group new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('groups','put','Group edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('index','index','System home');
INSERT INTO access_log_description (controller, action, description) VALUES ('invite-templates','delete','Invite template removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('invite-templates','get','Invite template details');
INSERT INTO access_log_description (controller, action, description) VALUES ('invite-templates','index','Invite templates list');
INSERT INTO access_log_description (controller, action, description) VALUES ('invite-templates','post','Invite template new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('invite-templates','put','Invite template edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('login','index','Login');
INSERT INTO access_log_description (controller, action, description) VALUES ('maintenance','index','Maintenance mode');
INSERT INTO access_log_description (controller, action, description) VALUES ('my-rooms','get','Room join event');
INSERT INTO access_log_description (controller, action, description) VALUES ('my-rooms','index','My rooms list');
INSERT INTO access_log_description (controller, action, description) VALUES ('record-tags','delete','Tag removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('record-tags','get','Tag detailhs');
INSERT INTO access_log_description (controller, action, description) VALUES ('record-tags','index','Tags list');
INSERT INTO access_log_description (controller, action, description) VALUES ('record-tags','post','Tag new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('record-tags','put','Tag edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('room-actions','index','Meeting room events list');
INSERT INTO access_log_description (controller, action, description) VALUES ('room-by-url','get','Room details by URL');
INSERT INTO access_log_description (controller, action, description) VALUES ('room-by-url','index','Rooms list by URL');
INSERT INTO access_log_description (controller, action, description) VALUES ('room-invites','get','Room invite details');
INSERT INTO access_log_description (controller, action, description) VALUES ('room-invites','post','Room invite send');
INSERT INTO access_log_description (controller, action, description) VALUES ('room-invites','put','Room invite edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('room-logs','index','Meeting room history');
INSERT INTO access_log_description (controller, action, description) VALUES ('rooms','delete','Meeting room removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('rooms','get','Meeting room details');
INSERT INTO access_log_description (controller, action, description) VALUES ('rooms','index','Meeting rooms list');
INSERT INTO access_log_description (controller, action, description) VALUES ('rooms','post','Meeting room new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('rooms','put','Meeting room edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('rooms-audience','index','Room audience');
INSERT INTO access_log_description (controller, action, description) VALUES ('security','index','ACL retrieval');
INSERT INTO access_log_description (controller, action, description) VALUES ('speed-profiles','delete','Speed profile removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('speed-profiles','get','Speed profile details');
INSERT INTO access_log_description (controller, action, description) VALUES ('speed-profiles','index','Speed profiles list');
INSERT INTO access_log_description (controller, action, description) VALUES ('speed-profiles','post','Speed profile new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('speed-profiles','put','Speed profile edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('users','delete','User removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('users','get','User details');
INSERT INTO access_log_description (controller, action, description) VALUES ('users','index','Users list');
INSERT INTO access_log_description (controller, action, description) VALUES ('users','post','User new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('users','put','User edit record');
INSERT INTO access_log_description (controller, action, description) VALUES ('users-reset-password','index','User password generation');
INSERT INTO access_log_description (controller, action, description) VALUES ('categories','delete','Categories removal');
INSERT INTO access_log_description (controller, action, description) VALUES ('categories','get','Categories details');
INSERT INTO access_log_description (controller, action, description) VALUES ('categories','index','Categories list');
INSERT INTO access_log_description (controller, action, description) VALUES ('categories','post','Categories new record');
INSERT INTO access_log_description (controller, action, description) VALUES ('categories','put','Categories edit record');

INSERT INTO invite_template (invite_template_id, name, subject, body, create_date) VALUES (1, 'Default invite', 'Meeting room invitation', '<p>Hi,</p><p>You have been invited to join the room __NOME_SALA__ on the following URL: __URL_SALA__ , presented by&nbsp;__PALESTRANTE_SALA__. The event starts at __INICIO_SALA__ and ends at __FIM_SALA__.</p>', SYSDATE());

INSERT INTO `user` (user_id, name, email, login, password, auth_mode_id, access_profile_id) VALUES (1, 'Administrator', 'admin@localhost', 'ADMIN', '61c99ce16cd1b0d10825581cf42d1fceaea2b009', 1, 1); -- pass = bbbmanager
INSERT INTO `group` (group_id, name, auth_mode_id, access_profile_id) VALUES (1, 'Administrator', 1, 1);
INSERT INTO user_group (user_id, group_id) values (1, 1);
