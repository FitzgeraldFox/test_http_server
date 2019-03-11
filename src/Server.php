<?php

namespace App;

use App\Interfaces\ServerInterface;
use React\EventLoop\LoopInterface;

require 'Interfaces/ServerInterface.php';

class Server implements ServerInterface
{
    const FILE_DIRECTORY = 'files';

    /**
     * @var resource
     */
    protected $socketServer;

    /**
     * @var LoopInterface
     */
    protected $eventLoop;
    
    public function __construct(LoopInterface $loop, $socketServer, int $port)
    {
        $this->eventLoop = $loop;
        $this->socketServer = $socketServer;
        echo "\n Listening On port $port For Connection... \n\n";
    }

    /**
     * @param $conn
     * @return array
     */
    protected function getRequestHeaders($conn): array
    {
        $input = fgets($conn);
        $incoming = explode("\r\n", $input);
        return explode(" ", $incoming[0]);
    }

    /**
     * @throws \Exception
     */
    public function handleRequest(): void
    {
        $this->eventLoop->addReadStream($this->socketServer, function ($socketServer) {

            $conn = stream_socket_accept($socketServer);

            $requestHeaders = $this->getRequestHeaders($conn);

            if ($requestHeaders[0] !== 'GET') {
                $responseString = "HTTP/1.1 404 NOT FOUND Connection: closed \r\n\r\n";
            } else {
                $responseString = $this->getFileResponseHeadersString($requestHeaders);
            }

            $this->sendResponse($conn, $responseString);

        });
    }

    /**
     * @param array $requestHeaders
     * @return string
     */
    protected function getFileResponseHeadersString(array $requestHeaders): ?string
    {
        clearstatcache();

        $fileName = $this->getFileName($requestHeaders);

        $filePath = self::FILE_DIRECTORY. '/' . $fileName;

        if ($fileName === null || !file_exists($filePath)) {
            return "HTTP/1.1 404 NOT FOUND Connection: closed \r\n\r\n";
        } else {
            $fileSize = filesize($filePath);
            $fileContent = file_get_contents($filePath);

            $headers = "HTTP/1.1 200 OK" .
                " Connection: closed " .
                " Content-Length: " . $fileSize .
                " Content-Type: " . mime_content_type($filePath) . " \r\n\r\n";

            return $headers . $fileContent;
        }
    }

    /**
     * @param array $fetchArray
     * @return string|null
     */
    protected function getFileName(array $fetchArray): ?string
    {
        $filePath = $fetchArray[1];

        if ($filePath != "/" && strpos($filePath, '../') === false) {
            return explode("/", $filePath)[1];
        }

        return null;
    }

    /**
     * @param $socketConnection
     * @param string $responseString
     * @throws \Exception
     */
    protected function sendResponse($socketConnection, string $responseString): void
    {
        $this->eventLoop->addWriteStream($socketConnection, function ($conn) use (&$responseString) {
            fwrite($conn, $responseString);
            fclose($conn);
            $this->eventLoop->removeWriteStream($conn);
        });
    }
}