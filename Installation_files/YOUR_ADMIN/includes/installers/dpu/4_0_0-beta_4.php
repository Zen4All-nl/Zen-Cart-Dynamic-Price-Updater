<?php
/**
  V4.0.0, What changed:
  - updated the jquery to 4.3.1
  - rewritten the class and javascript. Removing all the ancient code, and moving to the builtin ajax functions of Zen Cart
  - removed the Multi code form the class, as it is not used (since v1). If neededit can always be put back in.
  - Added code changes from Torvista, MC12345678, and Zen4All
  
*/
$db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'DPU_PLUGIN_CHECK'");