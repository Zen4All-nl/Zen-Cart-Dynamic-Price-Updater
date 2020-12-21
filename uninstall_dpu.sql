/* Paste and run this sql in the Zen Cart Admin->SQL patch Tool AFTER removing all the DPU files */

SELECT @DPUgID := configuration_group_id FROM configuration WHERE configuration_key = 'DPU_VERSION';
DELETE FROM configuration WHERE configuration_group_id = @DPUgID; 
DELETE FROM admin_pages WHERE page_key = 'configDynamicPriceUpdater';
DELETE FROM configuration_group WHERE configuration_group_id = @DPUgID;