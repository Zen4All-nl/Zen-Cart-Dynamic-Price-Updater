<?php
/**
  V4.0.0, What changed:
  - updated the jquery to 4.3.1
  - rewrote the class and javascript, removing old code, and using the built-in ajax functions of Zen Cart
  - removed the Multi code from the class as it is not used (since v1). If needed it can always be put back in.
  - Added code changes from Torvista, MC12345678, and Zen4All

*/
$db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'DPU_PLUGIN_CHECK'");

