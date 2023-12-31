<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Enum;

final class ServiceKey
{
    /**
     * @var string
     */
    public const DEFAULTS = '_defaults';

    /**
     * @var string
     */
    public const ARGUMENTS = 'arguments';

    /**
     * @var string
     */
    public const DECORATES = 'decorates';

    /**
     * @var string
     */
    public const FACTORY = 'factory';
}
