<?php
namespace Improwised\ApiHelper;

use Illuminate\Support\Facades\Facade;

class ApiHelperFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'apibuilder';
    }
}
