<?php

$imagesDir = "images";
$fileSuffix = ".png";
$lockFileExpirationSeconds = 10; # If a lock file (blank .txt file) is older than this many seconds, try solving it again.
$captchaFileExpirationSeconds = 10 * 60; # If a captcha file (image) is older than this many seconds, ignore/delete it.

?>