<?php

declare(strict_types = 1);

namespace ZendTest\DI\Config\Tool;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\DI\Config\Tool\AutowiresConfigDumperCommand;
use Zend\Stdlib\ConsoleHelper;
use ZendTest\DI\Config\TestAsset\DelegatorService;

class AutowiresConfigDumperCommandTest extends TestCase
{

    private $configDir;
    private $helper;
    private $command;

    protected function setUp()
    {
        $this->configDir = vfsStream::setup('project');
        $this->helper = $this->prophesize(ConsoleHelper::class);
        $this->command = new AutowiresConfigDumperCommand(
            AutowiresConfigDumperCommand::class, $this->helper->reveal()
        );
    }

    protected function assertHelp($stream = STDOUT)
    {
        $this->helper->writeLine(
            Argument::containingString('<info>Usage:</info>'),
            true,
            $stream
        )->shouldBeCalled();
    }

    protected function assertErrorRaised($message)
    {
        $this->helper->writeErrorMessage(
            Argument::containingString($message)
        )->shouldBeCalled();
    }

    public function helpArguments()
    {
        return [
            'short'   => ['-h'],
            'long'    => ['--help'],
            'literal' => ['help'],
        ];
    }

    public function testEmitsHelpWhenNoArgumentsProvided()
    {
        $command = $this->command;
        $this->assertHelp();
        $this->assertEquals(0, $command([]));
    }

    /**
     * @dataProvider helpArguments
     */
    public function testEmitsHelpWhenHelpArgumentProvidedAsFirstArgument($argument)
    {
        $command = $this->command;
        $this->assertHelp();
        $this->assertEquals(0, $command([$argument]));
    }

    public function testEmitsErrorWhenTooFewArgumentsPresent()
    {
        $command = $this->command;
        $this->assertErrorRaised('Missing class name');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command(['foo']));
    }

    public function testRaisesExceptionIfConfigFileNotFoundAndDirectoryNotWritable()
    {
        $command = $this->command;
        vfsStream::newDirectory('config', 0550)
            ->at($this->configDir);
        $config = vfsStream::url('project/config/test.config.php');

        $this->assertErrorRaised(sprintf('Cannot create configuration at path "%s"; not writable.', $config));
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class']));
    }

    public function testEmitsErrorWhenConfigurationDoesNotHaveDependenciesArray()
    {
        $command = $this->command;
        $command = $this->command;
        vfsStream::newFile('config/invalid.config.php')
            ->at($this->configDir)
            ->setContent("<?php return ['dependencies' => 0];");

        $config = vfsStream::url('project/config/invalid.config.php');

        $this->assertErrorRaised(sprintf('Unable to create config for "%s"', DelegatorService::class));
        $this->assertHelp(STDERR);

        $this->assertEquals(1, $command([$config, DelegatorService::class]));
    }

    public function testGeneratesConfigFile()
    {
        $command = $this->command;
        vfsStream::newDirectory('config', 0775)
            ->at($this->configDir);
        $config = vfsStream::url('project/config/test.config.php');

        $this->helper->writeLine('<info>[DONE]</info> Changes written to ' . $config)->shouldBeCalled();
        $this->assertEquals(0, $command([$config, DelegatorService::class]));

        $generated = include $config;

        $this->assertInternalType('array', $generated);
        $this->assertArrayHasKey('dependencies', $generated);
        $dependencies = $generated['dependencies'];
        $this->assertArrayHasKey('autowires', $dependencies);
        $autowiresConfig = $dependencies['autowires'];
        $this->assertInternalType('array', $autowiresConfig);
        $this->assertContains(DelegatorService::class, $autowiresConfig);
    }

    public function testEmitsErrorWhenConfigurationFileDoesNotReturnArray()
    {
        $command = $this->command;
        vfsStream::newFile('config/invalid.config.php')
            ->at($this->configDir)
            ->setContent('');

        $config = vfsStream::url('project/config/invalid.config.php');

        $this->assertErrorRaised('Configuration at path "' . $config . '" does not return an array.');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class']));
    }

    public function testEmitsErrorWhenClassDoesNotExist()
    {
        $command = $this->command;
        vfsStream::newDirectory('config', 0775)
            ->at($this->configDir);
        $config = vfsStream::url('project/config/test.config.php');

        $this->assertErrorRaised('Class "Not\\A\\Real\\Class" does not exist or could not be autoloaded.');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class']));
    }
}

