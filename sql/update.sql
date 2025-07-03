-- Copyright (C) 2022-2025 EVARISK <technique@evarisk.com>
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

-- 1.1.0
ALTER TABLE llx_dolismq_controldet ADD answer_photo TEXT NOT NULL AFTER answer;

-- 1.2.0
ALTER TABLE llx_dolismq_control ADD note_public TEXT NULL AFTER status;
ALTER TABLE llx_dolismq_control ADD note_private TEXT NULL AFTER note_public;
ALTER TABLE llx_dolismq_control DROP fk_product;
ALTER TABLE llx_dolismq_control DROP fk_lot;
ALTER TABLE llx_dolismq_control DROP fk_soc;
ALTER TABLE llx_dolismq_control DROP fk_project;
ALTER TABLE llx_dolismq_control DROP fk_task;

-- 1.3.0
ALTER TABLE llx_dolismq_sheet ADD element_linked TEXT NULL AFTER label;

ALTER TABLE llx_dolismq_question ADD show_photo BOOLEAN NULL AFTER description;
ALTER TABLE llx_dolismq_question ADD authorize_answer_photo BOOLEAN NULL AFTER show_photo;
ALTER TABLE llx_dolismq_question ADD enter_comment BOOLEAN NULL AFTER authorize_answer_photo;

ALTER TABLE llx_dolismq_control ADD fk_project INTEGER NULL AFTER fk_user_controller;

-- 1.4.0
ALTER TABLE llx_dolismq_control CHANGE fk_project projectid integer;
ALTER TABLE llx_element_element ADD position INTEGER;
UPDATE llx_element_element SET sourcetype = 'dolismq_question' WHERE sourcetype = 'question';
UPDATE llx_element_element SET sourcetype = 'dolismq_sheet' WHERE sourcetype = 'sheet';
UPDATE llx_element_element SET sourcetype = 'dolismq_control' WHERE sourcetype = 'control';
UPDATE llx_element_element SET targettype = 'dolismq_question' WHERE targettype = 'question';
UPDATE llx_element_element SET targettype = 'dolismq_sheet' WHERE targettype = 'sheet';
UPDATE llx_element_element SET targettype = 'dolismq_control' WHERE targettype = 'control';

-- 1.5.0
DELETE FROM llx_document_model WHERE nom =  'calypso';
ALTER TABLE llx_dolismq_control CHANGE tms tms TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE llx_dolismq_control CHANGE status status INT(11) DEFAULT 1 NOT NULL;
ALTER TABLE llx_dolismq_control CHANGE import_key import_key VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE llx_dolismq_controldet CHANGE tms tms TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE llx_dolismq_controldet CHANGE status status INT(11) DEFAULT 1 NOT NULL;
ALTER TABLE llx_dolismq_controldet CHANGE import_key import_key VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE llx_dolismq_dolismqdocuments CHANGE tms tms TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE llx_dolismq_dolismqdocuments CHANGE status status INT(11) DEFAULT 1 NOT NULL;
ALTER TABLE llx_dolismq_dolismqdocuments CHANGE import_key import_key VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE llx_dolismq_question CHANGE tms tms TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE llx_dolismq_question CHANGE status status INT(11) DEFAULT 1 NOT NULL;
ALTER TABLE llx_dolismq_question CHANGE import_key import_key VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE llx_dolismq_sheet CHANGE tms tms TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE llx_dolismq_sheet CHANGE status status INT(11) DEFAULT 1 NOT NULL;
ALTER TABLE llx_dolismq_sheet CHANGE import_key import_key VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

-- 1.6.0
ALTER TABLE llx_dolismq_question CHANGE label label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL NOT NULL;
ALTER TABLE llx_dolismq_question CHANGE type type varchar(128) NOT NULL;
ALTER TABLE llx_dolismq_sheet CHANGE label label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL NOT NULL;
ALTER TABLE llx_dolismq_answer CHANGE pictogram pictogram VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE llx_dolismq_sheet ADD description text AFTER label;
ALTER TABLE llx_dolismq_question ADD UNIQUE INDEX uk_dolismq_question_ref (ref, entity);
ALTER TABLE llx_dolismq_sheet ADD UNIQUE INDEX uk_dolismq_sheet_ref (ref, entity);
ALTER TABLE llx_dolismq_control ADD UNIQUE INDEX uk_dolismq_control_ref (ref, entity);
ALTER TABLE llx_dolismq_controldet ADD UNIQUE INDEX uk_dolismq_controldet_ref (ref, entity);
ALTER TABLE llx_dolismq_control ADD photo TEXT NULL AFTER verdict;
UPDATE llx_dolismq_question SET type = 'OkKoToFixNonApplicable' WHERE type = '';

-- 1.7.0
ALTER TABLE llx_dolismq_control ADD track_id VARCHAR(128) NOT NULL AFTER photo;
ALTER TABLE llx_dolismq_sheet ADD mandatory_questions text AFTER element_linked;
UPDATE llx_dolismq_question SET type = 'OkKoToFixNonApplicable' WHERE type IS NULL;
UPDATE llx_dolismq_sheet SET mandatory_questions = '{}' WHERE mandatory_questions IS NULL;
ALTER TABLE llx_dolismq_sheet CHANGE mandatory_questions mandatory_questions text;
ALTER TABLE llx_dolismq_control ADD next_control_date DATETIME AFTER track_id;

-- 1.8.0

ALTER TABLE llx_dolismq_answer RENAME TO llx_digiquali_answer;
ALTER TABLE llx_dolismq_question RENAME TO llx_digiquali_question;
ALTER TABLE llx_dolismq_question_extrafields RENAME TO llx_digiquali_question_extrafields;
ALTER TABLE llx_dolismq_sheet RENAME TO llx_digiquali_sheet;
ALTER TABLE llx_dolismq_sheet_extrafields RENAME TO llx_digiquali_sheet_extrafields;
ALTER TABLE llx_dolismq_control RENAME TO llx_digiquali_control;
ALTER TABLE llx_dolismq_control_extrafields RENAME TO llx_digiquali_control_extrafields;
ALTER TABLE llx_dolismq_controldet RENAME TO llx_digiquali_controldet;
ALTER TABLE llx_dolismq_controldet_extrafields RENAME TO llx_digiquali_controldet_extrafields;
ALTER TABLE llx_control_equipment RENAME TO llx_digiquali_control_equipment;
UPDATE llx_const SET name = REPLACE(name, 'DOLISMQ', 'DIGIQUALI') WHERE name LIKE '%DOLISMQ%';
UPDATE llx_const SET value = REPLACE(value, 'dolismq', 'digiquali') WHERE value LIKE '%dolismq%';
UPDATE llx_element_element SET sourcetype = REPLACE(sourcetype, 'dolismq', 'digiquali') WHERE sourcetype LIKE '%dolismq%';
UPDATE llx_element_element SET targettype = REPLACE(targettype, 'dolismq', 'digiquali') WHERE targettype LIKE '%dolismq%';
DELETE FROM llx_menu WHERE module = 'dolismq';
DELETE FROM llx_menu WHERE mainmenu LIKE '%dolismq%';
UPDATE llx_rights_def SET module = 'digiquali' WHERE module = 'dolismq';
UPDATE llx_actioncomm SET elementtype = REPLACE(elementtype, 'dolismq', 'digiquali') WHERE elementtype LIKE '%dolismq%';
UPDATE llx_extrafields SET elementtype = REPLACE(elementtype, 'dolismq', 'digiquali') WHERE elementtype LIKE '%dolismq%';
UPDATE llx_extrafields SET langs = REPLACE(langs, 'dolismq', 'digiquali') WHERE langs LIKE '%dolismq%';
UPDATE llx_extrafields SET enabled = REPLACE(enabled, 'dolismq', 'digiquali') WHERE enabled LIKE '%dolismq%';
UPDATE llx_ecm_files SET filepath = REPLACE(filepath, 'dolismq', 'digiquali') WHERE filepath LIKE '%dolismq%';
UPDATE llx_ecm_files SET src_object_type = REPLACE(src_object_type, 'dolismq', 'digiquali') WHERE src_object_type LIKE '%dolismq%';
UPDATE llx_saturne_object_documents SET ref_ext = REPLACE(ref_ext, 'dolismq', 'digiquali') WHERE ref_ext LIKE '%dolismq%';
UPDATE llx_saturne_object_documents SET module_name = REPLACE(module_name, 'dolismq', 'digiquali') WHERE module_name LIKE '%dolismq%';
UPDATE llx_saturne_object_signature SET module_name = REPLACE(module_name, 'dolismq', 'digiquali') WHERE module_name LIKE '%dolismq%';
UPDATE llx_bookmark SET url = REPLACE(url, 'dolismq', 'digiquali') WHERE url LIKE '%dolismq%';
UPDATE llx_ecm_directories SET label = REPLACE(label, 'dolismq', 'digiquali') WHERE label LIKE '%dolismq%';
ALTER TABLE llx_digiquali_control_equipment ADD fk_lot integer AFTER fk_product;
ALTER TABLE llx_digiquali_control ADD control_date DATETIME AFTER next_control_date;

-- 1.10.0
ALTER TABLE llx_digiquali_sheet ADD photo TEXT NULL AFTER element_linked;
ALTER TABLE llx_digiquali_sheet ADD success_rate DOUBLE(24,8) NULL AFTER photo;
ALTER TABLE llx_digiquali_control ADD success_rate DOUBLE(24,8) NULL AFTER next_control_date;

-- 1.11.0
ALTER TABLE llx_digiquali_sheet CHANGE type type VARCHAR(128) NOT NULL;
ALTER TABLE llx_digiquali_survey ADD success_rate DOUBLE(24,8) NULL AFTER photo;

-- 1.13.0
ALTER TABLE llx_digiquali_control ADD label VARCHAR(255) NULL AFTER status;

-- 20.1.0
ALTER TABLE llx_digiquali_question ADD json TEXT NULL AFTER photo_ko;

-- 21.1.0
UPDATE llx_element_element SET sourcetype = 'productlot' WHERE sourcetype = 'productbatch' AND targettype = 'digiquali_control';
UPDATE llx_element_element SET sourcetype = 'productlot' WHERE sourcetype = 'productbatch' AND targettype = 'digiquali_survey';

-- 21.2.0
ALTER TABLE `llx_digiquali_controldet` ADD `fk_question_group` integer DEFAULT 0 NOT NULL AFTER `fk_question`;
ALTER TABLE `llx_digiquali_surveydet` ADD `fk_question_group` integer DEFAULT 0 NOT NULL AFTER `fk_question`;
UPDATE `llx_digiquali_controldet` SET `fk_question_group` = 0 WHERE `fk_question_group` IS NULL;
UPDATE `llx_digiquali_surveydet` SET `fk_question_group` = 0 WHERE `fk_question_group` IS NULL;
ALTER TABLE `llx_digiquali_question` ADD `points` FLOAT DEFAULT 1 NOT NULL AFTER `description`;
ALTER TABLE `llx_digiquali_answer` ADD `correct` BOOLEAN DEFAULT 0 NOT NULL AFTER `position`;
ALTER TABLE `llx_digiquali_questiongroup` ADD `success_rate` double(24,8) DEFAULT 0 NOT NULL AFTER `description`;