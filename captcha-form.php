<html>
<head><title>Captcha Farm&trade;</title>
	<link rel="icon" 
	      type="image/png" 
	      href="favicon.png">
</head>
<body style="font-family:Arial,Vardana,Sans-serif; font-size: 12px;" OnLoad="document.captcha.response.focus();">
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

$output = "";
$statusboard = "";

$statusboard .= '<div id="statusboard" style="width: 208px; border: 2px solid #ddd; padding:0px; float:left; margin:10px;">';

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

			if ($debug) print "<!-- Looking at $fullFileName ... -->\n";
			if ($thisFileSuffix == $fileSuffix){ // if it's the right file type
				if ($debug) print "<!-- $file is of type $fileSuffix ... -->\n";
				$thisFileAge = $now - filemtime($fullFileName);
				if ($thisFileAge > $captchaFileExpirationSeconds) {
					if ($debug) print "<!-- This file is greater than $captchaFileExpirationSeconds seconds old. Deleting it. -->\n";
					if (!$safe) unlink($fullFileName);
					else if ($debug) print "<!-- SAFE MODE: Skipping delete of $fullFileName. -->\n";
/*
					if (file_exists($fullTextFileName)){
						if (!$safe) unlink($fullTextFileName);
						else if ($debug) print "<!-- SAFE MODE: Skipping delete of $fullTextFileName -->\n";
					}
*/
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
								$status="open"; // Lock file exists but has expired
								$numFiles++;
								$statusboard .= create_statusboard_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff);
								if ($challengefile == "") {
									file_put_contents($fullTextFileName, ""); # Update modified date on lock file
									$challengefile = $file; //winner
									$challengetextfile = $textfile;
									if ($debug) print "<!-- The challenge file is officially: $imagesDir/$challengefile -->\n";
								}
								//break(1);
							}
							else {
								$status="locked"; // Lock file exists and it is recent
								$numFiles++;
								$statusboard .= create_statusboard_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff);
							}
						}
						else {
							if ($debug) print "<!-- Skipping file since this captcha has already been answered: $text -->\n";
							$status="complete";
							$numFiles++;
							$statusboard .= create_statusboard_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff);
						}
					}
					else { // no text file exists, so this one is valid to use
						$status="open";
						$numFiles++;
						$statusboard .= create_statusboard_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff);
						if ($debug) print "<!-- The file does not exist, so I am creating one: $fullTextFileName -->\n";
						if ($challengefile == "") {
							file_put_contents($fullTextFileName, "");
							$challengefile = $file; //winner
							$challengetextfile = $textfile;
							if ($debug) print "<!-- The challenge file is officially: $imagesDir/$challengefile -->\n";
						}
						//break(1);
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
	}



if ($numFiles==0) $statusboard .= '<div style="width:200px; border: 2px solid #444; margin: 2px; text-align: center; padding-top: 10px; padding-bottom: 10px;">No Captcha files.</div>';

$statusboard .= "</div>";
print $statusboard;

if (ISSET($challengefile)) {
	print '<div id="captchaformdiv" style="border: 2px solid #ddd; width: 500px; float: left; margin-top: 10px; padding: 10px;">';
	print '<form name="captcha" method="post" action="captcha-form.php?';
	if ($debug) print 'debug';
	if ($cleanup) print '&cleanup';
	if ($safe) print '&safe';
	print '" >'."\n";
	
	print "\t$imagesDir/$challengefile<br />\n";
	
	print "\t".'<img src="'.$imagesDir.'/'.$challengefile.'" /><br /><br />'."\n";
		
	print "\t".'<input type="text" name="response" />'."\n";
	
	print "\t".'<input type="hidden" name="textfile" value="'.$challengetextfile.'" />'."\n";
	
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

<?php


function create_statusboard_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff){
	GLOBAL $lockFileExpirationSeconds;
	$escapechars = array("!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "_", "+", "=", "`", "~", ",", ".", "<", ">", "?", "/", "{", "}", "[", "]", "\\", "|");
	$escapedThisFileNoSuffix = str_replace($escapechars, "", $thisFileNoSuffix);
	$statusboard .= "\n\t".'<div style="width:200px; border: 2px solid #444; margin: 2px;">'."\n";
	if ($status == "complete") $icon = "yes.gif";
	elseif ($status == "locked") $icon = "no.gif";
	elseif ($status == "open") $icon = "blank.gif";
	$statusboard .= "\t\t".'<img src="'.$icon.'" id="img'.$escapedThisFileNoSuffix.'" width=30 style="vertical-align: middle;" />'."\n";
	if ($status=="locked") {
		$countdown = $diff - $lockFileExpirationSeconds;

		$statusboard .= "\t\t".'<script>
		var count'.$escapedThisFileNoSuffix.'='.abs(round($countdown)).';
		var counter'.$escapedThisFileNoSuffix.'=setInterval(timer'.$escapedThisFileNoSuffix.', 1000);
		function timer'.$escapedThisFileNoSuffix.'()
		{
		  count'.$escapedThisFileNoSuffix.'=count'.$escapedThisFileNoSuffix.'-1;
		  if (count'.$escapedThisFileNoSuffix.' <= 0)
		  {
		     clearInterval(counter'.$escapedThisFileNoSuffix.');
			document.getElementById("'.$escapedThisFileNoSuffix.'").innerHTML=""; 
			document.getElementById("img'.$escapedThisFileNoSuffix.'").src="blank.gif";
		     return;
		  }

		 document.getElementById("'.$escapedThisFileNoSuffix.'").innerHTML="("+count'.$escapedThisFileNoSuffix.'+") "; 
		}
		</script>'."\n";
		$statusboard .= "\t\t".'<span id="'.$escapedThisFileNoSuffix.'">('.abs(round($countdown)).') </span>';
		}
	$statusboard .= '<a href="pollresponses.php?challenge='.$thisFileNoSuffix.'" target="_new">'.$file.'</a>';
	$statusboard .= "\t".'<a href="pollresponses.php?challenge='.$thisFileNoSuffix.'" target="_new"><img src="'.$fullFileName.'" style="width: 200px;" /></a>'."\n";
	$statusboard .= "\t".'</div>';
	
	return $statusboard;
}


?>