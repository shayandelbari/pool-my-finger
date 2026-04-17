<?php

namespace App\Backend\Services\Exceptions;

use Exception;

class InvalidCredentialsException extends Exception
{
}

\class_alias(__NAMESPACE__ . '\\InvalidCredentialsException', 'InvalidCredentialsException');
