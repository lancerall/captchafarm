<script>

function makeHttpObject() {
  try {return new XMLHttpRequest();}
  catch (error) {}
  try {return new ActiveXObject("Msxml2.XMLHTTP");}
  catch (error) {}
  try {return new ActiveXObject("Microsoft.XMLHTTP");}
  catch (error) {}

  throw new Error("Could not create HTTP request object.");
}
	
</script>

<?php
require_once("config/config.php");

$debug = false;
if (ISSET($_GET["debug"])) $debug = true; # Display play-by-play commentary in html comments
else $debug = false;

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

$output = "";
$captchalist = "";

$captchalist .= '<div id="captchalist" style="width: 208px; border: 2px solid #ddd; padding:0px; float:left; margin:10px;">';

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
								$captchalist .= create_captchalist_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff);
								// if ($challengefile == "") {
								// 	file_put_contents($fullTextFileName, ""); # Update modified date on lock file
								// 	$challengefile = $file; //winner
								// 	$challengetextfile = $textfile;
								// 	if ($debug) print "<!-- The challenge file is officially: $imagesDir/$challengefile -->\n";
								// }
								// //break(1);
							}
							else {
								$status="locked"; // Lock file exists and it is recent
								$numFiles++;
								$captchalist .= create_captchalist_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff);
							}
						}
						else {
							if ($debug) print "<!-- Skipping file since this captcha has already been answered: $text -->\n";
							$status="complete";
							$numFiles++;
							$captchalist .= create_captchalist_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff);
						}
					}
					else { // no text file exists, so this one is valid to use
						$status="open";
						$numFiles++;
						$captchalist .= create_captchalist_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff);
						if ($debug) print "<!-- The file does not exist, so I am creating one: $fullTextFileName -->\n";
						// if ($challengefile == "") {
						// 	file_put_contents($fullTextFileName, "");
						// 	$challengefile = $file; //winner
						// 	$challengetextfile = $textfile;
						// 	if ($debug) print "<!-- The challenge file is officially: $imagesDir/$challengefile -->\n";
						// }
						// //break(1);
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



if ($numFiles==0) $captchalist .= '<div style="width:200px; border: 2px solid #444; margin: 2px; text-align: center; padding-top: 10px; padding-bottom: 10px;">No Captcha files.</div>';

$captchalist .= "</div>";
print $captchalist;


function create_captchalist_block($file, $fullFileName, $thisFileNoSuffix, $status, $diff){
	GLOBAL $lockFileExpirationSeconds;
	$escapechars = array("!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "_", "+", "=", "`", "~", ",", ".", "<", ">", "?", "/", "{", "}", "[", "]", "\\", "|");
	$escapedThisFileNoSuffix = str_replace($escapechars, "", $thisFileNoSuffix);
	$captchalist .= "\n\t".'<div style="width:200px; border: 2px solid #444; margin: 2px;">'."\n";
	if ($status == "complete") $icon = "yes.gif";
	elseif ($status == "locked") $icon = "no.gif";
	elseif ($status == "open") $icon = "blank.gif";
	$captchalist .= "\t\t".'<img src="img/'.$icon.'" id="img'.$escapedThisFileNoSuffix.'" width=30 style="vertical-align: middle;" />'."\n";
	if ($status=="locked") {
		$countdown = $diff - $lockFileExpirationSeconds;

		$captchalist .= "\t\t".'<script>
		var count'.$escapedThisFileNoSuffix.'='.abs(round($countdown)).';
		var counter'.$escapedThisFileNoSuffix.'=setInterval(timer'.$escapedThisFileNoSuffix.', 1000);
		function timer'.$escapedThisFileNoSuffix.'()
		{
		  count'.$escapedThisFileNoSuffix.'=count'.$escapedThisFileNoSuffix.'-1;
		  if (count'.$escapedThisFileNoSuffix.' <= 0)
		  {
		     clearInterval(counter'.$escapedThisFileNoSuffix.');
			document.getElementById("'.$escapedThisFileNoSuffix.'").innerHTML=""; 
			document.getElementById("img'.$escapedThisFileNoSuffix.'").src="img/blank.gif";
		     return;
		  }

		 document.getElementById("'.$escapedThisFileNoSuffix.'").innerHTML="("+count'.$escapedThisFileNoSuffix.'+") "; 
		}
		</script>'."\n";
		$captchalist .= "\t\t".'<span id="'.$escapedThisFileNoSuffix.'">('.abs(round($countdown)).') </span>';
		}
	$captchalist .= '<a href="pollresponses.php?challenge='.$thisFileNoSuffix.'" target="_new">'.$file.'</a>';
	$captchalist .= "\t".'<a href="pollresponses.php?challenge='.$thisFileNoSuffix.'" target="_new"><img src="'.$fullFileName.'" style="width: 200px;" /></a>'."\n";
	$captchalist .= "\t".'</div>';
	
	return $captchalist;
}

?>