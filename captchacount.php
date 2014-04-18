<?php
require_once("config/config.php");

$numFiles = 0;
$thisdir = scandir($imagesDir);
foreach($thisdir as $file){
	if (substr($file,0,1) != ".") {
			$fullFileName = $imagesDir."/".$file;
			$thisFileSuffix = substr($file,strlen($file)-strlen($fileSuffix),strlen($fileSuffix));
			$thisFileNoSuffix = substr($file,0,strlen($file)-strlen($thisFileSuffix));
			$textfile = substr($file,0,strlen($file)-strlen($fileSuffix)).".txt"; // construct .txt file name
			$fullTextFileName = $imagesDir."/".$textfile;
			$now = microtime(true);

			if ($thisFileSuffix == $fileSuffix){ // if it's the right file type
				$thisFileAge = $now - filemtime($fullFileName);
				if ($thisFileAge > $captchaFileExpirationSeconds) {} // if the file has expired
				else{ // file is new enough to answer
					if (file_exists($fullTextFileName)) {
						$text=file_get_contents($fullTextFileName); // grab the contents of the txt file if it exists
						if ($text == "") {
							$diff = $now - filemtime($fullTextFileName);
							if ($diff > $lockFileExpirationSeconds) { // lock file has expired, this one is valid to answer
								$numFiles++;
							}
							else { // Lock file exists and it is recent 
								}
						}
						else { // This captcha has already been answered
							}
					}
					else { // no text file exists, so this one is valid to use
						$numFiles++;
					}
				}
			}
			else{ // invalid file extension
			}
		}
	}

print $numFiles;

?>