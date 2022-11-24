<?php

namespace Socodo\ORM\Enums;

enum QueryTypes
{
    case Select;
    case Insert;
    case Update;
    case Upsert;
    case Delete;
}