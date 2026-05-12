<?php
for ($i = 7; $i >= 0; $i--) {
    $weekStart = date('Y-m-d', strtotime("monday this week -$i weeks"));
    $weekEnd = date('Y-m-d', strtotime("sunday this week -$i weeks"));
    echo "i=$i: $weekStart to $weekEnd\n";
}
