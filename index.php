<?php

use App\Server;

require 'src/Server.php';

$server = new Server(8000);
$server->listen();