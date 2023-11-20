<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use TomasVotruba\Tryml\Console\ColorConsoleDiffFormatter;

final class TrymlDiffer
{
    private readonly Differ $differ;

    public function __construct(
        private readonly ColorConsoleDiffFormatter $colorConsoleDiffFormatter,
    ) {
        $this->differ = new Differ(new UnifiedDiffOutputBuilder());
    }

    public function diffForConsole(string $old, string $new): string
    {
        $diff = $this->differ->diff($old, $new);
        return $this->colorConsoleDiffFormatter->format($diff);
    }
}
