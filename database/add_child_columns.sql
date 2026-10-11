-- ============================================================
-- Run this SQL in phpMyAdmin on database: child_management_db
-- This adds all missing columns to the existing 'children' table
-- ============================================================

ALTER TABLE `children`
    ADD COLUMN `name_english` VARCHAR(255) NULL AFTER `full_name`,
    ADD COLUMN `name_korean` VARCHAR(255) NULL AFTER `name_english`,
    ADD COLUMN `alias` VARCHAR(255) NULL AFTER `name_korean`,
    ADD COLUMN `religion` VARCHAR(255) NULL AFTER `gender`,
    ADD COLUMN `area` VARCHAR(255) NULL AFTER `religion`,
    ADD COLUMN `office_code` VARCHAR(255) NULL AFTER `area`,
    ADD COLUMN `office_name` VARCHAR(255) NULL AFTER `office_code`,
    ADD COLUMN `service_state` VARCHAR(255) NULL AFTER `office_name`,
    ADD COLUMN `sponsor_state` VARCHAR(255) NULL AFTER `service_state`,
    ADD COLUMN `guardian_type` VARCHAR(255) NULL AFTER `sponsor_state`,
    ADD COLUMN `caregiver` VARCHAR(255) NULL AFTER `guardian_type`,
    ADD COLUMN `curriculum` VARCHAR(255) NULL AFTER `caregiver`,
    ADD COLUMN `favorite_subject` VARCHAR(255) NULL AFTER `curriculum`,
    ADD COLUMN `pass_fail` VARCHAR(255) NULL AFTER `favorite_subject`,
    ADD COLUMN `dream` VARCHAR(255) NULL AFTER `pass_fail`,
    ADD COLUMN `dream_description` TEXT NULL AFTER `dream`,
    ADD COLUMN `favorite_activity` VARCHAR(255) NULL AFTER `dream_description`,
    ADD COLUMN `health` VARCHAR(255) NULL AFTER `favorite_activity`,
    ADD COLUMN `health_description` TEXT NULL AFTER `health`,
    ADD COLUMN `disability_type` VARCHAR(255) NULL AFTER `health_description`,
    ADD COLUMN `disability_description` TEXT NULL AFTER `disability_type`,
    ADD COLUMN `qr_code_path` VARCHAR(255) NULL AFTER `disability_description`;
