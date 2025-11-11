<?php

namespace delivery\type;

class Factory
{
    public static function getClass($type)
    {
        switch($type){
            case "omniva":
                return new \delivery\type\Omniva;
            case "circlek":
                return new \delivery\type\CircleK;
            case "take_in_office":
                return new \delivery\type\TakeInOffice;
            case "lvpasts":
                return new \delivery\type\LatvijasPasts;
        }
    }
}