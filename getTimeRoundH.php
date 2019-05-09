<?php
# get time and round hour
# Uhrzeit für das Wetter auf die volle Stunde aufrunden, aus "10:42" wird dann "11:00"
# z.B. [[!getTimeRoundH]] oder plus 4h [[!getTimeRoundH? &add=`4`]]

$timestamp = time();
$timestamp = ceil($timestamp/3600)*3600;

if (isset($add)) {
   $timestamp = $timestamp + ($add * 3600); # 3600 = 1h
}

return date('H:i', $timestamp);
