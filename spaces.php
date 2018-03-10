<?php
$string = '24-hour reception 24-hour room service Car park Concierge Conference rooms Express check-in / out Free WiFi in lobby Gym Hotel bar Hotel safe Laundry service Lift Non-smoking rooms Restaurant Room service WiFi in lobby
';
$replace = preg_replace('/\s([A-Z])/', ', $1', $string );
$replace = preg_replace('/\s([0-9])/', ', $1', $replace );
var_dump(preg_replace('(\/,)', '/',$replace));
