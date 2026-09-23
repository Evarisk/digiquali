-- Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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

-- 1.6.0

INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(1, 0, 'UniqueChoice', 'UniqueChoice', '', 1, 1);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(2, 0, 'MultipleChoices', 'MultipleChoices', '', 1, 10);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(3, 0, 'Text', 'Text', '', 1, 20);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(4, 0, 'Percentage', 'Percentage', '', 1, 30);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(5, 0, 'Range', 'Range', '', 1, 40);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(6, 0, 'OkKo', 'OkKo', '', 1, 50);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(7, 0, 'OkKoToFixNonApplicable', 'OkKoToFixNonApplicable', '', 1, 60);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(8, 0, 'MarqueNF', 'MarqueNF', '', 1, 70);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(9, 0, 'Iso9001', 'Iso9001', '', 1, 80);
INSERT INTO llx_c_question_type (rowid, entity, ref, label, description, active, position) VALUES(10, 0, 'Duration', 'Duration', '', 1, 45);

INSERT INTO llx_c_control_attendants_role (rowid, entity, ref, label, description, active, position) VALUES(1, 0, 'Controller', 'Controller', '', 1, 1);
INSERT INTO llx_c_control_attendants_role (rowid, entity, ref, label, description, active, position) VALUES(2, 0, 'Attendant', 'Attendant', '', 1, 20);

-- 1.11.0
INSERT INTO llx_c_survey_attendants_role (rowid, entity, ref, label, description, active, position) VALUES(1, 0, 'Attendant', 'Attendant', '', 1, 1);
