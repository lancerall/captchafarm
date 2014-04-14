<html>
<head><title>Captcha Farm&trade;</title>
	<link rel="icon" 
	      type="image/png" 
	      href="favicon.png">
</head>
<body>
<?php

$debug = false;
if (ISSET($_GET["debug"])) $debug = true; # Display play-by-play commentary in html comments
else $debug = false;
if (ISSET($_GET["purge"])) $purge = true; # Delete any files that don't belong
else $purge = false;
if (ISSET($_GET["safe"])) $safe = true; # Don't delete anything 
else $safe = false;

$images_dir = "images";
$fileSuffix = ".png";
$lockFileExpirationSeconds = 30; # If a lock file (blank .txt file) is older than this many seconds, try solving it again.
$captchaFileExpirationSeconds = 30 * 60; # If a captcha file (image) is older than this many seconds, ignore/delete it.

if ($debug) {
	print "<!-- Configs:\n
		Debug: $debug\n
		Purge: $purge\n
		Safe: $safe\n
		Images dir: $images_dir\n
		File suffix: $fileSuffix\n
		Retry after: $lockFileExpirationSeconds seconds\n
		Ignore/delete older than: $captchaFileExpirationSeconds seconds\n
		POST:\n";

	print_r($_POST);

	print "-->\n";
}

if (ISSET($_POST["response"])){
	if ($_POST["response"] != "") {
		if ($debug) print "<!-- Received response: ".$_POST['response']." --> \n";
		if ($debug) print "<!-- Received text file: ".$_POST['textfile']." --> \n";

		$posttextfile = $_POST["textfile"];
		$postresponse = $_POST["response"];

		if (substr($posttextfile,strlen($posttextfile)-4,4)==".txt"){
			if ($debug) print "<!-- $posttextfile is of type .txt -->\n";
			if (file_exists($images_dir.'/'.$posttextfile)) { // if it's a text file and it exists
				if ($debug) print "<!-- $posttextfile exists, it's a txt file... -->\n";
				$text=file_get_contents($images_dir."/".$posttextfile); // grab the contents of the txt file if it exists
				if ($debug) print "<!-- Its contents are $text-->\n";
				if ($text==""){
					if ($debug) print "<!-- Since the file is blank, I'm replacing its contents with $postresponse -->\n";
					file_put_contents($images_dir."/".$posttextfile, $postresponse);
					if ($debug) print "Stored challenge response of $postresponse for $posttextfile <br />";
				}
				else{
					if ($debug) print "Challenge has already been answered. <br />";
				}
			}	
		}
	}
	else if ($debug) print "<!-- Did not receive post with content. -->\n";
}
else if ($debug) print "<!-- Did not receive post. -->\n";

$thisdir = scandir($images_dir);
foreach($thisdir as $file){
	$fullFileName = $images_dir."/".$file;
	$thisFileSuffix = substr($file,strlen($file)-strlen($fileSuffix),strlen($fileSuffix));
	$thisFileNoSuffix = substr($file,0,strlen($file)-strlen($thisFileSuffix));
	$textfile = substr($file,0,strlen($file)-4).".txt"; // construct .txt file name
	$fullTextFileName = $images_dir."/".$textfile;
	$now = microtime(true);
	
	if ($debug) print "<!-- Looking at $fullFileName ... -->\n";
	if ($thisFileSuffix == $fileSuffix){ // if it's the right file type
		if ($debug) print "<!-- $file is of type $fileSuffix ... -->\n";
		$thisFileAge = $now - filemtime($fullFileName);
		if ($thisFileAge > $captchaFileExpirationSeconds) {
			if ($debug) print "<!-- This file is greater than $captchaFileExpirationSeconds seconds old. Deleting it. -->\n";
			if (!$safe) unlink($fullFileName);
			else if ($debug) print "<!-- SAFE MODE: Skipping delete. -->\n";
		}
		else{
			if ($debug) print "<!-- This file is new enough to answer ($thisFileAge < $captchaFileExpirationSeconds seconds). Proceeding. -->\n";
			if ($debug) print "<!-- Looking for text file named $fullTextFileName... -->\n";
			if (file_exists($fullTextFileName)) {
				if ($debug) print "<!-- The file exists. -->\n";
				$text=file_get_contents($fullTextFileName); // grab the contents of the txt file if it exists
				if ($text == "") {
					if ($debug) print "<!-- Its contents are $text-->\n";
					$diff = $now - filemtime($fullTextFileName);
					if ($debug) print "<!-- Creation date is ".filemtime($fullTextFileName)." ... now is ".$now." ... diff is ".$diff." -->\n";
					if ($diff > $lockFileExpirationSeconds) {
						if ($debug) print "<!-- Lock file is over $lockFileExpirationSeconds seconds old, retrying... -->\n";
						file_put_contents($fullTextFileName, ""); # Update modified date on lock file
						$challengefile = $file; //winner
						if ($debug) print "<!-- The challenge file is officially: $images_dir/$challengefile -->\n";
						break(1);
					}
				}
				else if ($debug) print "<!-- Skipping file since this captcha has already been answered: $text -->\n";
			}
			else { // no text file exists, so this one is valid to use
				if ($debug) print "<!-- The file does not exist, so I am creating one: $fullTextFileName -->\n";
				file_put_contents($fullTextFileName, "");
				$challengefile = $file; //winner
				if ($debug) print "<!-- The challenge file is officially: $images_dir/$challengefile -->\n";
				break(1);
			}
		}
	}
	else{
		if ($debug) print "<!-- File is not of type $fileSuffix -->\n";
		if (substr($file,0,1) != "."){ # Please do not delete . or .. or .files
			if ($thisFileSuffix == ".txt") {# Please do not delete lock files that do not have corresponding captchas
				if (file_exists($images_dir."/".$thisFileNoSuffix.$fileSuffix)) {
					# Do nothing.
					if ($debug) print "<!-- Skipping purge of this txt file since $images_dir/$thisFileNoSuffix$fileSuffix exists. -->\n";
				}
				else if ($purge) {
					if ($debug) print "<!-- Purging $fullFileName -->\n";
					if (!$safe) unlink($fullFileName);
					else if ($debug) print "<!-- SAFE MODE: Skipping delete. -->\n";
				}
			}
			else if ($purge) {
				if ($debug) print "<!-- Purging $fullFileName -->\n";
				if (!$safe) unlink($fullFileName);
				else if ($debug) print "<!-- SAFE MODE: Skipping delete. -->\n";
			}
		}
	}
}

if ($challengefile) {
	
	print '<form name="captcha" method="post" action="captcha-form.php?';
	if ($debug) print 'debug';
	if ($purge) print '&purge';
	if ($safe) print '&safe';
	print '" >'."\n";
	
	print "\t".'<img src="'.$images_dir.'/'.$challengefile.'" /><br />'."\n";
	
	print "\t$images_dir/$challengefile<br />\n";
	
	print "\t".'<input type="text" name="response" />'."\n";
	
	print "\t".'<input type="hidden" name="textfile" value="'.$textfile.'" />'."\n";
	
	print "\t".'<input type="submit" value="Submit">'."\n";
	
	print "</form>\n";
	
}
else print "No challenges to answer.";

?>
</body>
</html>