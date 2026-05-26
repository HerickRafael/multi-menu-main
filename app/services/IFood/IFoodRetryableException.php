<?php

declare(strict_types=1);

namespace App\Services\IFood;

use RuntimeException;

/**
 * Sinaliza ao IFoodJobWorker que a falha é transitória — agendar nova tentativa.
 * Qualquer outra exceção é tratada como permanente e o job vai para 'dead'.
 */
class IFoodRetryableException extends RuntimeException
{
}
