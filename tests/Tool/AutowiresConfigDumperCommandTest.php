<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\Tool;

use Elie\PHPDI\Config\Tool\AutowiresConfigDumperCommand;
use ElieTest\PHPDI\Config\TestAsset\DelegatorService;
use Laminas\Stdlib\ConsoleHelper;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

use function sprintf;

use const STDERR;
use const STDOUT;

class AutowiresConfigDumperCommandTest extends TestCase
{
    use ProphecyTrait;

    private vfsStreamDirectory $configDir;

    /** @var ConsoleHelper|ObjectProphecy */
    private ObjectProphecy $helper;

    private AutowiresConfigDumperCommand $command;

    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');
        $this->helper    = $this->prophesize(ConsoleHelper::class);
        $this->command   = new AutowiresConfigDumperCommand(
            AutowiresConfigDumperCommand::class,
            $this->helper->reveal()
        );
    }

    /**
     * @param false|resource $stream
     */
    protected function assertHelp($stream = STDOUT): void
    {
        $this->helper->writeLine(
            Argument::containingString('<info>Usage:</info>'),
            true,
            $stream
        )->shouldBeCalled();
    }

    protected function assertErrorRaised(string $message): void
    {
        $this->helper->writeErrorMessage(
            Argument::containingString($message)
        )->shouldBeCalled();
    }

    public function helpArguments(): array
    {
        return [
            'short'   => ['-h'],
            'long'    => ['--help'],
            'literal' => ['help'],
        ];
    }

    public function testEmitsHelpWhenNoArgumentsProvided(): void
    {
        $command = $this->command;
        $this->assertHelp();
        $this->assertEquals(0, $command([]));
    }

    /**
     * @dataProvider helpArguments
     */
    public function testEmitsHelpWhenHelpArgumentProvidedAsFirstArgument(string $argument): void
    {
        $command = $this->command;
        $this->assertHelp();
        $this->assertEquals(0, $command([$argument]));
    }

    public function testEmitsErrorWhenTooFewArgumentsPresent(): void
    {
        $command = $this->command;
        $this->assertErrorRaised('Missing class name');
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command(['foo']));
    }

    public function testRaisesExceptionIfConfigFileNotFoundAndDirectoryNotWritable(): void
    {
        $command = $this->command;
        vfsStream::newDirectory('config', 0550)
            ->at($this->configDir);
        $config = vfsStream::url('project/config/test.config.php');

        $this->assertErrorRaised(sprintf('Cannot create configuration at path "%s"; not writable.', $config));
        $this->assertHelp(STDERR);
        $this->assertEquals(1, $command([$config, 'Not\A\Real\Class']));
    }

    public function testEmitsErrorWhenConfigurationDoesNotHaveDependenciesArray(): void
    {
        $command = $this->command;
        vfsStream::newFile('config/invalid.config.php')
            ->at($this->configDir)
            ->setContent("<?php return ['dependencies' => 0];");

        $config = vfsStream::url('project/config/invalid.config.php');

        $this->assertErrorRaised(sprintf('Unable to create config for "%s"', DelegatorService::class));
        $this->assertHelp(STDERR);

        $this->assertEquals(1, $command([$config, DelegatorService::class]));
    }

    public function testGeneratesConfigFile(): void
    {
        $command = $this->command;
        vfsStream::newDirectory('config', 0775)
            ->at($this->configDir);
        $config = vfsStream::url('project/config/test.config.php');

        $this->helper->writeLine('<info>[DONE]</info> Changes written to ' . $config)->shouldBeCalled();
        $this->assertEquals(0, $command([$config, DelegatorService::class]));

        $generated = include $config;

        $this->assertArrayHasKey('dependencies', $generated);
        $dependencies = $generated['dependencies'];
        $this->assertArrayHasKey('autowires', $dependencies);
        $autowiresConfig = $dependencies['autowires'];
        $this->assertContains(DelegatorService::class, $autowiresConfig);
    }

    public function testEmitsErrorWhenConfigurationFileDoesNotReturnArray(): void
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

    public function testEmitsErrorWhenClassDoesNotExist(): void
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
