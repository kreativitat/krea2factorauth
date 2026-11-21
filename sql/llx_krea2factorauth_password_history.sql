-- Copyright (C) 2026 Kreativität Works <mail@kreativitat.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

CREATE TABLE llx_krea2factorauth_password_history (
	rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
	entity integer DEFAULT 0 NOT NULL,
	fk_user integer NOT NULL,
	password_hash varchar(255) NOT NULL,
	date_creation datetime NOT NULL,
	fk_user_creat integer,
	source varchar(32)
) ENGINE=InnoDB;
