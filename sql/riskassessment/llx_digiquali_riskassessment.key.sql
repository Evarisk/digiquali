-- Copyright (C) 2025 EVARISK <technique@evarisk.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

ALTER TABLE llx_digiquali_riskassessment ADD INDEX idx_digiquali_riskassessment_rowid (rowid);
ALTER TABLE llx_digiquali_riskassessment ADD INDEX idx_digiquali_riskassessment_ref (ref);
ALTER TABLE llx_digiquali_riskassessment ADD CONSTRAINT llx_digiquali_riskassessment_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);
ALTER TABLE llx_digiquali_riskassessment ADD CONSTRAINT llx_digiquali_riskassessment_fk_activity FOREIGN KEY (fk_activity) REFERENCES llx_digiquali_activity (rowid);
ALTER TABLE llx_digiquali_riskassessment ADD CONSTRAINT llx_digiquali_riskassessment_fk_projet FOREIGN KEY (fk_projet) REFERENCES llx_projet (rowid);
ALTER TABLE llx_digiquali_riskassessment ADD UNIQUE uk_riskassessment_ref (ref, entity);
