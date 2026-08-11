<?php
namespace Core;

class HttpException extends \Exception
{
    public function __construct(int $code = 500, string $message = '')
    {
        parent::__construct($message, $code);
    }
}
