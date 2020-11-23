<?php
declare(strict_types=1);
$location = $_SERVER['REQUEST_URI'];
$location = str_replace("actions.php", "discussion-record.php", $location);
header('Location: ' . $location);
