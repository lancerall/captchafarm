<?php
require_once("config.php");

$debug = false;
$cleanup = false;
$getchallenge = $_GET["challenge"];
$textFile = $imagesDir.'/'.$getchallenge.".txt";
$imageFile = $imagesDir.'/'.$getchallenge.$fileSuffix;

if (ISSET($_GET["debug"])) $debug = true;
if (ISSET($_GET["cleanup"])) $cleanup = true;

if ($debug) print "<!-- Received request: $getchallenge -->\n";
if ($debug) print "<!-- Looking for file $textFile -->\n";

if (file_exists($textFile)) {
	if ($debug) print "<!-- File exists. -->\n";
	$text=file_get_contents($textFile); // grab the contents of the txt file if it exists
	if ($debug) print "<!-- File contents are $text-->\n";
	print $text;
	
	if ($text != ""){
		if ($debug && $cleanup) print "<!-- Deleting $textFile -->\n";
		if ($cleanup) unlink($textFile); #clean up

		if ($debug && $cleanup) print "<!-- Deleting $imageFile -->\n";
		if ($cleanup) unlink($imageFile); #clean up
		
	}
}


?>