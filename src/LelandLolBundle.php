<?php

namespace Leland\LolBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class LelandLolBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}