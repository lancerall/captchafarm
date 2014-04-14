<?php
$debug = false;
$cleanup = false;
$images_dir = "images";
$getchallenge = $_GET["challenge"];

if (ISSET($_GET["debug"])) $debug = true;
if (ISSET($_GET["cleanup"])) $cleanup = true;

if ($debug) print "<!-- Received request: $getchallenge -->\n";
if ($debug) print "<!-- Looking for file $images_dir/$getchallenge.txt -->\n";

if (file_exists($images_dir.'/'.$getchallenge.".txt")) {
	if ($debug) print "<!-- File exists. -->\n";
	$text=file_get_contents($images_dir."/".$getchallenge.".txt"); // grab the contents of the txt file if it exists
	if ($debug) print "<!-- File contents are $text-->\n";
	print $text;
	
	if ($text != ""){
		if ($debug && $cleanup) print "<!-- Deleting $images_dir/$getchallenge.txt -->\n";
		if ($cleanup) unlink($images_dir.'/'.$getchallenge.".txt"); #clean up

		if ($debug && $cleanup) print "<!-- Deleting $images_dir/$getchallenge.png -->\n";
		if ($cleanup) unlink($images_dir.'/'.$getchallenge.".png"); #clean up
		
	}
}


?>