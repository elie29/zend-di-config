<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\Tool;

use Elie\PHPDI\Config\Tool\AutowiresConfigDumper;
use ElieTest\PHPDI\Config\TestAsset\DelegatorService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AutowiresConfigDumperTest extends TestCase
{
    private AutowiresConfigDumper $dumper;

    public function setUp(): void
    {
        $this->dumper = new AutowiresConfigDumper();
    }

    public function testCreateDependencyConfigExpectsDependiciesKeyToBeArray(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Configuration dependencies key must be an array');
        $this->dumper->createDependencyConfig(['dependencies' => 5], '');
    }

    public function testCreateDependencyConfigExpectsAutowiresNotExists(): void
    {
        $config = $this->dumper->createDependencyConfig(['dependencies' => []], 'test');

        $this->assertArrayHasKey('autowires', $config['dependencies']);
        $this->assertContains('test', $config['dependencies']['autowires']);
    }

    public function testCreateDependencyConfigExpectsAutowiresExists(): void
    {
        $config = $this->dumper->createDependencyConfig([
            'dependencies' => [
                'autowires' => 5,
            ],
        ], 'test');

        $this->assertArrayHasKey('autowires', $config['dependencies']);
        $this->assertContains('test', $config['dependencies']['autowires']);
    }

    public function testCreateDependencyConfigDoesNotOverrideExistingKeys(): void
    {
        $config = $this->dumper->createDependencyConfig([
            'invokables'            => [
                'autowires' => 5,
            ],
            DelegatorService::class => DelegatorService::class,
        ], DelegatorService::class);

        $this->assertArrayHasKey('autowires', $config['dependencies']);
        $this->assertArrayHasKey('invokables', $config);
        $this->assertContains(5, $config['invokables']);

        $data = $this->dumper->dumpConfigFile($config);
        $this->assertNotEmpty($data);
    }
}
