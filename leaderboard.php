<?php

print '<div id="leaderboard" style="border: 2px solid #444; width: 200px; float: right; margin: 10px; padding: 2px;">'."\n";
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

arsort($leaders);

foreach($leaders as $name => $score){
	print '<div style="border: 2px solid #ddd; margin: 2px; padding:2 10 2 2; text-align: right">';
	print $name." : ".$score;
	print "</div>\n";
}

print '</div>'."\n";

?>