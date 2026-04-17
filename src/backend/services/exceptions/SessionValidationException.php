<?php

namespace App\Backend\Services\Exceptions;

use Exception;

class SessionValidationException extends Exception
{
}

\class_alias(__NAMESPACE__ . '\\SessionValidationException', 'SessionValidationException');
