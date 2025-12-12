<?php

declare(strict_types=1);

namespace ElieTest\PHPDI\Config\Tool;

use Elie\PHPDI\Config\Config;
use Elie\PHPDI\Config\ContainerFactory;
use Elie\PHPDI\Config\Tool\AutowiresConfigDumperCommand;
use ElieTest\PHPDI\Config\TestAsset\DelegatorService;
use ElieTest\PHPDI\Config\TestAsset\UserManager;
use Exception;
use Laminas\Stdlib\ConsoleHelper;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function sprintf;
use const STDERR;
use const STDOUT;

class AutowiresConfigDumperCommandTest extends TestCase
{
    use ProphecyTrait;

    private vfsStreamDirectory $configDir;

    private ObjectProphecy|ConsoleHelper $helper;

    private AutowiresConfigDumperCommand $command;

    public static function helpArguments(): array
    {
        return [
            'short' => ['-h'],
            'long' => ['--help'],
            'literal' => ['help'],
        ];
    }

    public function testEmitsHelpWhenNoArgumentsProvided(): void
    {
        $command = $this->command;
        $this->assertHelp();
        $this->assertEquals(0, $command([]));
    }

    #[DataProvider('helpArguments')]
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testCliIntegrationAddsUserManagerToAutowires(): void
    {
        // Use a real ConsoleHelper for integration
        $command = new AutowiresConfigDumperCommand(
            AutowiresConfigDumperCommand::class,
            new ConsoleHelper()
        );
        $configDir = vfsStream::setup('project');
        vfsStream::newDirectory('config', 0775)->at($configDir);
        $configFile = vfsStream::url('project/config/integration.config.php');

        // Run the command to add UserManager
        $exitCode = $command([
            $configFile,
            UserManager::class,
        ]);
        $this->assertEquals(0, $exitCode);

        // Check the config file was created and contains UserManager in autowires
        $this->assertFileExists($configFile);

        $generated = include $configFile;

        $this->assertArrayHasKey('dependencies', $generated);
        $this->assertArrayHasKey('autowires', $generated['dependencies']);
        $this->assertContains(
            UserManager::class,
            $generated['dependencies']['autowires']
        );

        // Build a container from the generated config and check UserManager is instantiable
        $factory = new ContainerFactory();
        $config = new Config($generated);
        $container = $factory($config);
        $userManager = $container->get(UserManager::class);
        $this->assertInstanceOf(UserManager::class, $userManager);
    }

    protected function setUp(): void
    {
        $this->configDir = vfsStream::setup('project');
        $this->helper = $this->prophesize(ConsoleHelper::class);
        $this->command = new AutowiresConfigDumperCommand(
            AutowiresConfigDumperCommand::class,
            $this->helper->reveal()
        );
    }

    /**
     * @param resource $stream
     */
    protected function assertHelp(mixed $stream = STDOUT): void
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
}
