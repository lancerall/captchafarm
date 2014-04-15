<?php

$imagesDir = "images";
$fileSuffix = ".png";
$lockFileExpirationSeconds = 30; # If a lock file (blank .txt file) is older than this many seconds, try solving it again.
$captchaFileExpirationSeconds = 30 * 60; # If a captcha file (image) is older than this many seconds, ignore/delete it.

?>