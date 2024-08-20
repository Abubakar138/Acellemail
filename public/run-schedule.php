<?php
$output = shell_exec('/usr/local/bin/php -q /home/autoresponder/public_html/artisan schedule:run 2>&1');

if (strpos($output, 'No scheduled commands are ready to run.') !== false) {
    echo "Cron job executed successfully, but no scheduled commands were ready to run.";
} elseif (empty($output)) {
    echo "Cron job executed successfully, but no output was returned.";
} else {
    echo "Cron job executed successfully. Output:<br><pre>$output</pre>";
}
?>
