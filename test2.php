<?php
echo "1 - شروع شد<br>";

require_once __DIR__ . '/includes/config.php';

echo "2 - config بارگذاری شد<br>";

require_once __DIR__ . '/includes/auth.php';

echo "3 - auth بارگذاری شد<br>";

echo "4 - همه چی درسته!";
?>