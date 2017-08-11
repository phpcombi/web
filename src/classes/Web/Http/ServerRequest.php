<?php

namespace Combi\Web\Http;


use Combi\{
    Helper as helper,
    Abort as abort,
    Core as core
};

use Combi\Web as inner;

use Psr\Http\Message\{
    ServerRequestInterface
};

class ServerRequest extends Request implements ServerRequestInterface
{

}