<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="tw-h-screen" style="height: 100vh;">
        <iframe class="tw-w-full tw-h-full" src="<?= $url; ?>"></iframe>
    </div>
</div>
<script>
window.onmessage = function(event) {
    if (event.data === "closedBridge") {
        window.location = window.location.href.split('?')[0] + '?paying_outstanding';
    }
};
</script>
<?php init_tail(); ?>
</body>

</html>