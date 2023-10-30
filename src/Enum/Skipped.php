<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Enum;

final class Skipped
{
    /**
     * @var string[]
     */
    public const SKIPPED_SERVICE_NAMES = [
        // is being decorated
        'crv.mastercard_exchange_rates.grpc_service.client',

        // uses inner reference
        'crv.mastercard_exchange_rates.grpc_service.client.trace_decorator',

        // nested method call, handle separate manually
        'crv.misc.caching_pe_card_service',
    ];

    /**
     * @var string[]
     */
    public const SKIPPED_CLASSES = [
        'Core\Services\Send\Sms\TwilioClient',
        'Core\Services\Send\Sms\DelayedTwilioClient',
        'Core\Services\ExchangeRates\RatesProviderSelector',
    ];
}
