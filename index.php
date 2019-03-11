<?php

use App\Server;

require 'vendor/autoload.php';
require 'src/Server.php';

$loop = React\EventLoop\Factory::create();

$socketServer = stream_socket_server("tcp://127.0.0.1:8080");

if (!$socketServer) {
    exit(1);
}

$server = new Server($loop, $socketServer, 8080);

$server->handleRequest();

$loop->run();