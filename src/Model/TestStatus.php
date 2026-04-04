<?php

declare(strict_types=1);

namespace DaveLiddament\TestMapper\Model;

enum TestStatus: string
{
    case NoTest = 'noTest';
    case UnexpectedChange = 'unexpectedChange';
    case NoTickets = 'noTickets';
    case Ok = 'ok';
}
