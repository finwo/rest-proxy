<?php

namespace Finwo\Framework;

class DataTransformer
{
    public static function convertTo($data, $type = 'json')
    {
        return json_encode($data);
    }
}