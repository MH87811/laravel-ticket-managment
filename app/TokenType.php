<?php

namespace App;

enum TokenType: string
{
    case ACCESS = 'access';
    case REFRESH = 'refresh';
}
