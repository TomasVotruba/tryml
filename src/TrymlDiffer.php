<?php

namespace TomasVotruba\Tryml;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use TomasVotruba\Tryml\Console\ColorConsoleDiffFormatter;

final class TrymlDiffer
{
    private Differ $differ;

    private ColorConsoleDiffFormatter $colorConsoleDiffFormatter;

    public function __construct()
    {
        $this->differ = new Differ(new UnifiedDiffOutputBuilder());
        $this->colorConsoleDiffFormatter = new ColorConsoleDiffFormatter();
    }

    public function diffForConsole(string $old, string $new): string
    {
        $diff = $this->differ->diff($old, $new);
        return $this->colorConsoleDiffFormatter->format($diff);
    }
}
