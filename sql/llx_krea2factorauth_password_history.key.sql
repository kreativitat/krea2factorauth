-- Copyright (C) 2026 Kreativität Works <mail@kreativitat.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.

ALTER TABLE llx_krea2factorauth_password_history ADD INDEX idx_krea2factorauth_password_history_user (fk_user);
ALTER TABLE llx_krea2factorauth_password_history ADD INDEX idx_krea2factorauth_password_history_created (date_creation);
