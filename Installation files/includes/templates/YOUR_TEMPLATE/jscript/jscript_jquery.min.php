<?php
//Load jQuery ONLY if jQuery has not been loaded. This is to support versions of Zen Cart prior to v1.5.4 because jQuery was not part of core code
//Also want to prevent jQuery from loading again if bundled with a plugin or template package
?>
<script type="text/javascript">
if (typeof jQuery == 'undefined') {
    document.write('script type="text/javascript" src="//code.jquery.com/jquery-1.12.4.min.js"><\/script>');
}
</script>
