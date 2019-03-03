<?php

namespace App;

require 'Interfaces/ServerInterface.php';

use App\Interfaces\ServerInterface;

class Server implements ServerInterface
{
    /**
     * @var resource
     */
    protected $sock;
    
    public function __construct(int $port)
    {
        $this->sock = socket_create_listen($port);
        echo "\n Listening On port $port For Connection... \n\n";
    }

    public function listen(): void
    {
        while (1) {

            $client = socket_accept($this->sock);

            $input = socket_read($client, 1024);

            $incoming = explode("\r\n", $input);

            $fetchArray = explode(" ", $incoming[0]);

            if ($fetchArray[0] !== 'GET') {
                $output = "HTTP/1.1 404 NOT FOUND \r\n";
                socket_write($client, $output, strlen($output));
                socket_close($client);
            }

            $file = $fetchArray[1];

            if ($file != "/") {
                $filearray = explode("/", $file);
                $file = $filearray[1];
            }

            echo $fetchArray[0] . " Request " . $file . "\n";

            if (!file_exists($file)) {
                $output = "HTTP/1.1 404 NOT FOUND \r\n";
            } else {
                $fileContent = file_get_contents($file);

                $headers = "HTTP/1.1 200 OK \r\n" .
                    "Content-Length: " . filesize($file) . "\r\n" .
                    "Content-Type: " . mime_content_type($file) . " \r\n\r\n";

                $output = $headers . $fileContent;
            }

            socket_write($client, $output, strlen($output));
            socket_close($client);
        }
    }
}