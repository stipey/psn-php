<?php

namespace Tustin\PlayStation\Api\Model;

use Tustin\PlayStation\Api\Api;

abstract class Model extends Api
{
    protected ?object $cache = null;
}