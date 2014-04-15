<html>
<head><title>Captcha Farm&trade;</title>
	<link rel="icon" 
	      type="image/png" 
	      href="favicon.png">
</head>
<body style="font-family:Arial,Vardana,Sans-serif; font-size: 12px;">
<?php
require_once("config.php");

$debug = false;
if (ISSET($_GET["debug"])) $debug = true; # Display play-by-play commentary in html comments
else $debug = false;
if (ISSET($_GET["cleanup"])) $cleanup = true; # Delete any files that don't belong
else $cleanup = false;
if (ISSET($_GET["safe"])) $safe = true; # Don't delete anything 
else $safe = false;

if ($debug) {
	print "<!-- Configs:\n
		Debug: $debug\n
		cleanup: $cleanup\n
		Safe: $safe\n
		Images dir: $imagesDir\n
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
			if (file_exists($imagesDir.'/'.$posttextfile)) { // if it's a text file and it exists
				if ($debug) print "<!-- $posttextfile exists, it's a txt file... -->\n";
				$text=file_get_contents($imagesDir."/".$posttextfile); // grab the contents of the txt file if it exists
				if ($debug) print "<!-- Its contents are $text-->\n";
				if ($text==""){
					if ($debug) print "<!-- Since the file is blank, I'm replacing its contents with $postresponse -->\n";
					file_put_contents($imagesDir."/".$posttextfile, $postresponse);
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


$thisdir = scandir($imagesDir);
print '<dir id="statusboard" style="width: 208px; border: 2px solid #ddd; padding:0px; float:left; margin:10px;">';
foreach($thisdir as $file){
	if (substr($file,0,1) != "."){
		$thisFileSuffix = substr($file,strlen($file)-strlen($fileSuffix),strlen($fileSuffix));
		$thisFileNoSuffix = substr($file,0,strlen($file)-strlen($thisFileSuffix));
		if ($thisFileSuffix == $fileSuffix) {
			$textfile = substr($file,0,strlen($file)-strlen($fileSuffix)).".txt"; // construct .txt file name
			$fullTextFileName = $imagesDir."/".$textfile;
			if (file_exists($fullTextFileName)) {
				$text=file_get_contents($fullTextFileName);
				if ($text != "") {
					$status="complete"; // This captcha has been solved
				}
				else {
					$now = microtime(true);
					$diff = $now - filemtime($fullTextFileName);
					if ($diff > $lockFileExpirationSeconds) $status="open"; // Lock file exists but has expired
					else $status="locked"; // Lock file exists and it is recent
				}
			}
			else $status="open";
			print '<div style="width:200px; border: 2px solid #444; margin: 2px;">';
			if ($status == "complete") $icon = "yes.gif";
			elseif ($status == "locked") $icon = "no.gif";
			elseif ($status == "open") $icon = "blank.gif";
			print '<img src="'.$icon.'" width=30 style="vertical-align: middle;" />';
			if ($status=="locked") {
				$countdown = $diff - $lockFileExpirationSeconds;
				
				print '<script>
				var count'.$thisFileNoSuffix.'='.abs(round($countdown)).';
				var counter'.$thisFileNoSuffix.'=setInterval(timer'.$thisFileNoSuffix.', 1000);
				function timer'.$thisFileNoSuffix.'()
				{
				  count'.$thisFileNoSuffix.'=count'.$thisFileNoSuffix.'-1;
				  if (count'.$thisFileNoSuffix.' <= 0)
				  {
				     clearInterval(counter'.$thisFileNoSuffix.');
					document.getElementById("'.$thisFileNoSuffix.'").innerHTML=0; 
				     return;
				  }

				 document.getElementById("'.$thisFileNoSuffix.'").innerHTML=count'.$thisFileNoSuffix.'; 
				}
				</script>';
				print '(<span id="'.$thisFileNoSuffix.'">'.abs(round($countdown)).'</span>) ';
				}
			print $file.'</div>';
		}
	} 
}
print '</dir>';


$thisdir = scandir($imagesDir);
foreach($thisdir as $file){
	$fullFileName = $imagesDir."/".$file;
	$thisFileSuffix = substr($file,strlen($file)-strlen($fileSuffix),strlen($fileSuffix));
	$thisFileNoSuffix = substr($file,0,strlen($file)-strlen($thisFileSuffix));
	$textfile = substr($file,0,strlen($file)-strlen($fileSuffix)).".txt"; // construct .txt file name
	$fullTextFileName = $imagesDir."/".$textfile;
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
						if ($debug) print "<!-- The challenge file is officially: $imagesDir/$challengefile -->\n";
						break(1);
					}
				}
				else if ($debug) print "<!-- Skipping file since this captcha has already been answered: $text -->\n";
			}
			else { // no text file exists, so this one is valid to use
				if ($debug) print "<!-- The file does not exist, so I am creating one: $fullTextFileName -->\n";
				file_put_contents($fullTextFileName, "");
				$challengefile = $file; //winner
				if ($debug) print "<!-- The challenge file is officially: $imagesDir/$challengefile -->\n";
				break(1);
			}
		}
	}
	else{
		if ($debug) print "<!-- File is not of type $fileSuffix -->\n";
		if (substr($file,0,1) != "."){ # Please do not delete . or .. or .files
			if ($thisFileSuffix == ".txt") {# Please do not delete lock files that do not have corresponding captchas
				if (file_exists($imagesDir."/".$thisFileNoSuffix.$fileSuffix)) {
					# Do nothing.
					if ($debug) print "<!-- Skipping cleanup of this txt file since $imagesDir/$thisFileNoSuffix$fileSuffix exists. -->\n";
				}
				else if ($cleanup) {
					if ($debug) print "<!-- Purging $fullFileName -->\n";
					if (!$safe) unlink($fullFileName);
					else if ($debug) print "<!-- SAFE MODE: Skipping delete. -->\n";
				}
			}
			else if ($cleanup) {
				if ($debug) print "<!-- Purging $fullFileName -->\n";
				if (!$safe) unlink($fullFileName);
				else if ($debug) print "<!-- SAFE MODE: Skipping delete. -->\n";
			}
		}
	}
}

if ($challengefile) {
	print '<div id="captchaformdiv" style="border: 2px solid #ddd; width: 500px; float: left; margin-top: 10px; padding: 10px;">';
	print '<form name="captcha" method="post" action="captcha-form.php?';
	if ($debug) print 'debug';
	if ($cleanup) print '&cleanup';
	if ($safe) print '&safe';
	print '" >'."\n";
	
	print "\t$imagesDir/$challengefile<br />\n";
	
	print "\t".'<img src="'.$imagesDir.'/'.$challengefile.'" /><br /><br />'."\n";
		
	print "\t".'<input type="text" name="response" />'."\n";
	
	print "\t".'<input type="hidden" name="textfile" value="'.$textfile.'" />'."\n";
	
	print "\t".'<input type="submit" value="Submit">'."\n";
	
	print "</form>\n";
	print "</div>";
}
else {
	print '<div id="captchaformdiv" style="border: 2px solid #ddd; width: 500px; float: left; margin-top: 10px; padding: 10px;">';
	print "No challenges to answer.";
	print "</div>";
}

?>
</body>
</html>