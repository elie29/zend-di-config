<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\Tool;

use PHPUnit\Framework\TestCase;
use Zend\DI\Config\Tool\AutowiresConfigDumper;
use ZendTest\DI\Config\TestAsset\DelegatorService;
use ZendTest\DI\Config\TestAsset\DelegatorServiceFactory;

class AutowiresConfigDumperTest extends TestCase
{

    private $dumper;

    public function setUp()
    {
        $this->dumper = new AutowiresConfigDumper();
    }

    public function testCreateDependencyConfigExpectsDependiciesKeyToBeArray()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Configuration dependencies key must be an array');
        $this->dumper->createDependencyConfig(['dependencies' => 5], '');
    }

    public function testCreateDependencyConfigExpectsAutowiresNotExists()
    {
        $config = $this->dumper->createDependencyConfig(['dependencies' => []], 'test');

        $this->assertArrayHasKey('autowires', $config['dependencies']);
        $this->assertContains('test', $config['dependencies']['autowires']);
    }

    public function testCreateDependencyConfigExpectsAutowiresExists()
    {
        $config = $this->dumper->createDependencyConfig(['dependencies' => [
            'autowires' => 5
        ]], 'test');

        $this->assertArrayHasKey('autowires', $config['dependencies']);
        $this->assertContains('test', $config['dependencies']['autowires']);
    }

    public function testCreateDependencyConfigDoesNotOverrideExistingKeys()
    {
        $config = $this->dumper->createDependencyConfig([
            'invokables' => [
                'autowires' => 5,
            ],
            DelegatorService::class => DelegatorService::class
        ], DelegatorService::class);

        $this->assertArrayHasKey('autowires', $config['dependencies']);
        $this->assertArrayHasKey('invokables', $config);
        $this->assertContains(5, $config['invokables']);

        $data = $this->dumper->dumpConfigFile($config);
        $this->assertNotEmpty($data);
    }
}
