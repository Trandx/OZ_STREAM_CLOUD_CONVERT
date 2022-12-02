<?php
namespace App\Http\Controllers\Src\DailyMotion;


class ResponseException
{
    private $_data = '';
    private $_msg = '';

    public function __construct($message, $data=null)
    {
        $this->_data = $data;
        $this->_msg = $message;

        $this-> getData();
    }

    public function getData()
    {
        return [
            "message" =>  $this->_msg,
            "data" =>  $this->_data
        ];
    }
}
