<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml;

use Webmozart\Assert\Assert;

final class ArrayUtils
{
    /**
     * @param string[] $items
     * @return string[]
     */
    public static function resolveDuplicatedItems(array $items): array
    {
        Assert::allString($items);

        $duplicatedItems = [];

        $itemsToCount = array_count_values($items);
        foreach ($itemsToCount as $item => $count) {
            if ($count === 1) {
                continue;
            }

            $duplicatedItems[] = $item;
        }

        sort($duplicatedItems);

        return array_unique($duplicatedItems);
    }
}
