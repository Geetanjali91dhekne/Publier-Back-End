/* 17-07-2023 */

/* added new gam_api_status column in dt_sites table */
ALTER TABLE `dt_sites` ADD `gam_api_status` ENUM('Y','N') NOT NULL DEFAULT 'N' AFTER `updated_at`;

/* added new account_manager_id column in dt_sites table */
ALTER TABLE `dt_sites` ADD `account_manager_id` VARCHAR(255) NULL AFTER `gam_api_status`;

/* added new business_name, same_mcm_email, mcm_email column in dt_publisher table */
ALTER TABLE `dt_publisher` ADD `business_name` VARCHAR(255) NULL DEFAULT NULL AFTER `updated_at`, ADD `same_mcm_email` ENUM('Y','N') NOT NULL DEFAULT 'N' AFTER `business_name`, ADD `mcm_email` VARCHAR(255) NULL DEFAULT NULL AFTER `same_mcm_email`;

/* added new account_manager_id column in table */
ALTER TABLE `dt_manage_sellers` ADD `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_date`;

/* updated business_name column value same as full_name in dt_publisher table */
UPDATE `dt_publisher` set business_name = full_name where business_name is null;