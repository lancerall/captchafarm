<?php

include('config/config.php');

print '<div id="leaderboard" style="border: 2px solid #444; width: 200px; float: right; margin: 10px; padding: 2px;background-image:url(\'img/grad-c-f.jpg\'); background-size:100% 100%">'."\n";
print '<div style="text-align: center;">Leaderboard</div>';
$leaders = array();
$thisdir = scandir($leaderboardDir);
foreach($thisdir as $file){
	if (substr($file,strlen($file)-6,6) == ".score"){
		$username = substr($file,0,strlen($file)-6);
		$score = file_get_contents($leaderboardDir."/".$file);
		$leaders[$username] = $score;
	}
}

$maxwidth=208;
$maxscore=0;
$thermometerImage = 'img/grad-ccf.gif';
$thermometerBackground = '#ddf';

arsort($leaders);

foreach($leaders as $name => $score){
	if (intval($score) > $maxscore) {$maxscore = $score; }
	$relativesize = round(($score/$maxscore) * $maxwidth);
	print '<div style="border: 2px solid #ddd; margin: 2px; padding:2 10 2 2; text-align: right; background-color: '.$thermometerBackground.'; background-image:url(\''.$thermometerImage.'\'); background-size:'.$relativesize.'px 100%; background-repeat:repeat-y; background-position: left;">';
	
	print $name." : ".$score;
	print "</div>\n";
	$total += $score;
}

print '<div style="border: 2px solid #ddd; background-color: #eee; margin: 2px; padding:2 10 2 2; text-align: right">';
print "Total : ".$total;
print '</div>';

print '</div>'."\n";

?>