<?php
namespace Home\Model;

class ApiConstModel
{
    const ERROR = -1;
    const SUCCESS = 0;

    static $errArr = [
        self::ERROR => "Invalid Request",
        self::SUCCESS => "Successful"
    ];
}