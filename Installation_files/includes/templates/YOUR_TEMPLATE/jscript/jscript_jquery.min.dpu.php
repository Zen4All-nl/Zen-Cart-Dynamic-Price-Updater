<?php
//Load jQuery ONLY if jQuery has not been loaded. This is to support versions of Zen Cart prior to v1.5.4 because jQuery was not part of core code
//Also want to prevent jQuery from loading again if bundled with a plugin or template package
?>
<script type="text/javascript">
if (typeof jQuery == 'undefined') {
    document.write('<script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"><\/script>');
}
</script>
