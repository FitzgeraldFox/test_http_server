<?php

use App\Server;

require 'src/Server.php';

$server = new Server(80);
$server->listen();