<?php
$leaderboardDir = "leaderboard";
$imagesDir = "images";
$fileSuffix = ".png";

$lockFileExpirationSeconds = 30; # If a lock file (blank .txt file) is older than this many seconds, try solving it again.
$captchaFileExpirationSeconds = 10 * 60; # If a captcha file (image) is older than this many seconds, ignore/delete it.

$captchaListFrequencyMSeconds = 5000; # refresh list of captchas every x milliseconds
$titleUpdateFrequencyMSeconds = 3000; # check every x milliseconds for unsolved captchas, update page title
$leaderboardUpdateFrequencyMSeconds = 12000; # update the leaderboard automatically, after this many mseconds

?>