<?php

namespace Kraken\Runtime\Supervisor\Container;

use Kraken\Runtime\Supervisor\Solver;
use Kraken\Supervisor\SolverInterface;
use Error;
use Exception;

class ContainerStop extends Solver implements SolverInterface
{
    /**
     * @param Error|Exception $ex
     * @param mixed[] $params
     * @return mixed
     */
    protected function handler($ex, $params = [])
    {
        return $this->runtime->stop();
    }
}
