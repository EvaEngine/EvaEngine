<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Dev;

use Eva\EvaEngine\Exception\IOException;
use Phalcon\Config\Adapter\Ini;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class Make App
 * @package Eva\EvaEngine\Dev
 */
class Env extends Command
{

    /**
     * @var string
     */
    protected static $envFile;

    /**
     * @var Ini
     */
    protected static $env;

    /**
     * @return string
     */
    public static function getEnvFile()
    {
        return self::$envFile ?: (self::$envFile = getcwd() . '/.env');
    }

    /**
     * @param $file
     */
    public static function setEnvFile($file)
    {
        self::$env = $file;
    }

    /**
     * Get a variable
     * Get order as
     * 1. User input argument
     * 2. .env file
     * 3. PHP env
     *
     * @param $key
     * @param InputInterface $input
     * @return mixed|string
     * @throws IOException
     */
    public static function getVariable($key, InputInterface $input = null)
    {
        if ($input && $value = $input->getOption(strtolower($key))) {
            return $value;
        }
        $env = self::getVariables();
        $key = str_replace('-', '_', strtoupper($key));
        return $env->get($key) ?: getenv($key);
    }

    /**
     * Get variables from env file
     * @return Ini
     * @throws IOException
     */
    public static function getVariables()
    {
        if (false === file_exists($path = self::getEnvFile())) {
            throw new IOException(sprintf('Environment file %s not exists', $path));
        }

        if (!self::$env) {
            //TODO:: ENV section is phalcon bug, waiting for fix
            return self::$env = with(new Ini($path))->ENV;
        }

        return self::$env;
    }

    /**
     * Simple ini file writer, update vars by string replace only
     * Only support one level, no filter & validator
     *
     * @param string $file
     * @param array $params
     * @return int
     */
    public static function simpleIniWriter($file, array $params)
    {
        $content = file_get_contents($file);
        foreach ($params as $key => $value) {
            if (!$value) {
                continue;
            }
            $key = strtoupper($key);
            $content = preg_replace("/($key=)(.*)/", '$1' . $value, $content);
        }
        return file_put_contents($file, $content);
    }

    /**
     * CLI configure
     */
    protected function configure()
    {
        $this
            ->setName('env')
            ->setDescription('Get/set environment variable');

        $env = self::getVariables();

        $this->addArgument(
            'variable',
            InputArgument::OPTIONAL,
            'Variable name'
        );

        foreach ($env as $key => $value) {
            $key = str_replace('_', '-', strtolower($key));
            $this->addOption(
                strtolower($key),
                null,
                InputOption::VALUE_OPTIONAL
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($variableName = $input->getArgument('variable')) {
            $output->writeln(sprintf(
                '<info>%s</info>',
                self::getVariable($variableName, $input)
            ));
            return false;
        }

        $env = self::getVariables();
        $envAllowKeys = array_keys($env->toArray());
        $options = $input->getOptions();
        $updateOptions = [];

        foreach ($options as $key => $value) {
            if ($value && true === in_array(strtoupper($key), $envAllowKeys)) {
                $updateOptions[$key] = $value;
            }
        }
        if ($updateOptions) {
            $res = self::simpleIniWriter(self::getEnvFile(), $updateOptions);
            if ($res) {
                $output->writeln(sprintf(
                    '<info>Env file updated, fields: %s</info>',
                    json_encode($updateOptions)
                ));
            } else {
                $output->writeln(sprintf(
                    '<error>Env file %s updated failed</error>',
                    self::getEnvFile()
                ));
            }
            return false;
        }


        //No options, show all env
        foreach ($env as $key => $value) {
            $output->writeln(sprintf(
                '<info>%s : %s</info>',
                $key,
                $value
            ));
        }

    }
}
