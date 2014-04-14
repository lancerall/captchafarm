<?php
require_once("config.php");

$debug = false;
$cleanup = false;
$getchallenge = $_GET["challenge"];

if (ISSET($_GET["debug"])) $debug = true;
if (ISSET($_GET["cleanup"])) $cleanup = true;

if ($debug) print "<!-- Received request: $getchallenge -->\n";
if ($debug) print "<!-- Looking for file $imagesDir/$getchallenge.txt -->\n";

if (file_exists($imagesDir.'/'.$getchallenge.".txt")) {
	if ($debug) print "<!-- File exists. -->\n";
	$text=file_get_contents($imagesDir."/".$getchallenge.".txt"); // grab the contents of the txt file if it exists
	if ($debug) print "<!-- File contents are $text-->\n";
	print $text;
	
	if ($text != ""){
		if ($debug && $cleanup) print "<!-- Deleting $imagesDir/$getchallenge.txt -->\n";
		if ($cleanup) unlink($imagesDir.'/'.$getchallenge.".txt"); #clean up

		if ($debug && $cleanup) print "<!-- Deleting $imagesDir/$getchallenge.png -->\n";
		if ($cleanup) unlink($imagesDir.'/'.$getchallenge.".png"); #clean up
		
	}
}


?>