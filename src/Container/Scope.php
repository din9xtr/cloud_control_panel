<?php

namespace Din9xtrCloud\Container;

enum Scope
{
    case Shared;
    case Request;
    case Factory;
}
