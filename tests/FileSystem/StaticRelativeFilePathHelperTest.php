<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Tests\FileSystem;

use PHPUnit\Framework\TestCase;
use TomasVotruba\Tryml\FileSystem\StaticRelativeFilePathHelper;

final class StaticRelativeFilePathHelperTest extends TestCase
{
    public function test(): void
    {
        $relativeFilePath = StaticRelativeFilePathHelper::resolveFromCwd(__DIR__ . '/Fixture/some-file.php');
        $this->assertSame('tests/FileSystem/Fixture/some-file.php', $relativeFilePath);
    }
}
