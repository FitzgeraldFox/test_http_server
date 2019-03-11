<?php


namespace App\Interfaces;


interface ServerInterface
{
    public function handleRequest(): void;
}