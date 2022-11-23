<?php

namespace Socodo\ORM\Interfaces;

interface RowInterface
{
    public function getColumn (): ColumnInterface;

    public function getValue (): mixed;
}