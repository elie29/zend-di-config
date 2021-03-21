<?php

declare(strict_types=1);

namespace Elie\PHPDI\Config\Tool;

use InvalidArgumentException;
use Laminas\Stdlib\ConsoleHelper;
use stdClass;

use function array_shift;
use function class_exists;
use function count;
use function dirname;
use function file_exists;
use function file_put_contents;
use function in_array;
use function is_array;
use function is_writable;
use function sprintf;

use const STDERR;
use const STDOUT;

class AutowiresConfigDumperCommand
{
    private const COMMAND_DUMP  = 'dump';
    private const COMMAND_ERROR = 'error';
    private const COMMAND_HELP  = 'help';

    private const HELP_TEMPLATE = <<<EOH
<info>Usage:</info>

  %s [-h|--help|help] <configFile> <className>

<info>Arguments:</info>

  <info>-h|--help|help</info>          This usage message
  <info><configFile></info>            Path to a config file for which to generate
                          configuration. If the file does not exist, it will
                          be created. If it does exist, it must return an
                          array, and the file will be updated with new
                          configuration.
  <info><className></info>             Name of the class to reflect and to be added
                          in as an new entry in the autowires configuration.

Reads the provided configuration file (creating it if it does not exist),
and adds the provided class name in the autowires array, writing the changes
back to the file. The class name is added once.
EOH;

    private ConsoleHelper $helper;

    public function __construct(private string $scriptName = self::class, ?ConsoleHelper $helper = null)
    {
        $this->helper = $helper ?? new ConsoleHelper();
    }

    /**
     * @param array $args Argument list, minus script name
     * @return int Exit status
     */
    public function __invoke(array $args): int
    {
        $arguments = $this->parseArgs($args);

        switch ($arguments->command) {
            case self::COMMAND_HELP:
                $this->help();
                return 0;
            case self::COMMAND_ERROR:
                $this->helper->writeErrorMessage($arguments->message);
                $this->help(STDERR);
                return 1;
            case self::COMMAND_DUMP:
                // fall-through
            default:
                break;
        }

        $dumper = new AutowiresConfigDumper();

        try {
            $config = $dumper->createDependencyConfig(
                $arguments->config,
                $arguments->class
            );
        } catch (InvalidArgumentException $e) {
            $this->helper->writeErrorMessage(sprintf(
                'Unable to create config for "%s": %s',
                $arguments->class,
                $e->getMessage()
            ));
            $this->help(STDERR);
            return 1;
        }

        file_put_contents($arguments->configFile, $dumper->dumpConfigFile($config));

        $this->helper->writeLine(sprintf(
            '<info>[DONE]</info> Changes written to %s',
            $arguments->configFile
        ));

        return 0;
    }

    /**
     * @param array $args
     */
    private function parseArgs(array $args): stdClass
    {
        if (! count($args)) {
            return $this->createHelpArgument();
        }

        $arg1 = array_shift($args);

        if (in_array($arg1, ['-h', '--help', 'help'], true)) {
            return $this->createHelpArgument();
        }

        if (! count($args)) {
            return $this->createErrorArgument('Missing class name');
        }

        $configFile = (string) $arg1;
        switch (file_exists($configFile)) {
            case true:
                $config = require $configFile;

                if (! is_array($config)) {
                    return $this->createErrorArgument(sprintf(
                        'Configuration at path "%s" does not return an array.',
                        $configFile
                    ));
                }

                break;
            case false:
                // fall-through
            default:
                if (! is_writable(dirname($configFile))) {
                    return $this->createErrorArgument(sprintf(
                        'Cannot create configuration at path "%s"; not writable.',
                        $configFile
                    ));
                }

                $config = [];
                break;
        }

        $class = (string) array_shift($args);

        if (! class_exists($class)) {
            return $this->createErrorArgument(sprintf(
                'Class "%s" does not exist or could not be autoloaded.',
                $class
            ));
        }

        return $this->createArguments(self::COMMAND_DUMP, $configFile, $config, $class);
    }

    /**
     * @param resource $resource Defaults to STDOUT
     */
    private function help($resource = STDOUT): void
    {
        $this->helper->writeLine(sprintf(
            self::HELP_TEMPLATE,
            $this->scriptName
        ), true, $resource);
    }

    private function createArguments(string $command, string $configFile, array $config, string $class): stdClass
    {
        return (object) [
            'command'    => $command,
            'configFile' => $configFile,
            'config'     => $config,
            'class'      => $class,
        ];
    }

    private function createErrorArgument(string $message): stdClass
    {
        return (object) [
            'command' => self::COMMAND_ERROR,
            'message' => $message,
        ];
    }

    private function createHelpArgument(): stdClass
    {
        return (object) [
            'command' => self::COMMAND_HELP,
        ];
    }
}
