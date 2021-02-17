<?php

namespace SVB\Mailing;

use SVB\Mailing\DependencyInjection\SVBMailingExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SVBMailingBundle extends Bundle
{
    public function getContainerExtension(): SVBMailingExtension
    {
        return new SVBMailingExtension();
    }
}
