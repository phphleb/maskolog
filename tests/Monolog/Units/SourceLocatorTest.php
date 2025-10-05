<?php

declare(strict_types=1);

namespace MaskologLoggerTests\Monolog\Units;

use Maskolog\Internal\Exceptions\InvalidArgumentException;
use Maskolog\SourceLocator;
use PHPUnit\Framework\TestCase;

final class SourceLocatorTest extends TestCase
{
    public function testGetCurrentSourceWithDefaultLevel(): void
    {
        $result = SourceLocator::get();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(__CLASS__, $result[0]);
        $this->assertEquals(15, $result[1]);
    }

    public function testGetCurrentSourceWithLevelOne(): void
    {
        $result = $this->levelFirstFunction();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertStringContainsString(__CLASS__, $result[0]);
        $this->assertEquals(24, $result[1]);
    }

    public function testGetCurrentSourceWithLevelTwo(): void
    {
        $result = $this->levelSecondFunction();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $this->assertStringContainsString(__CLASS__, $result[0]);
        $this->assertEquals(56, $result[1]);
    }

    public function testInvalidLevelNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SourceLocator::get(-1);
    }

    private function levelFirstFunction(): array
    {
        return SourceLocator::get(1);
    }

    private function levelSecondFunction(): array
    {
        return $this->levelFirstFunction();
    }
}
