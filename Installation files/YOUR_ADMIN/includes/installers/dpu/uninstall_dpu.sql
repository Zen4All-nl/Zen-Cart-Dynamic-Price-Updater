/* Tables need to be ensured to include the prefix if it exists. */


SELECT @DPUgID := configuration_group_id 
FROM configuration_group where configuration_group_title = 'Dynamic Price Updater Config';

DELETE FROM configuration WHERE configuration_group_id = @DPUgID; 

DELETE FROM admin_pages WHERE page_key = 'configDynamicPriceUpdater';

DELETE FROM configuration_group WHERE configuration_group_id = @DPUgID;