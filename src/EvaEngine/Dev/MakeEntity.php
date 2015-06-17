<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Dev;

use Eva\EvaEngine\Engine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class Make Entity
 * @package Eva\EvaEngine\Dev
 */
class MakeEntity extends Command
{
    /**
     * @var string
     */
    protected $target;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function bootstrapEngine()
    {
        $engine = new Engine();
        return $this;
    }

    /**
     * CLI configure
     */
    protected function configure()
    {
        $this
            ->setName('make:entity')
            ->setDescription('Create a entity')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Entity name'
            )
            ->addOption(
                'app',
                null,
                InputOption::VALUE_OPTIONAL,
                'App name'
            )
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Module name'
            )
            ->addOption(
                'namespace',
                'ns',
                InputOption::VALUE_OPTIONAL,
                'Entity namespace'
            )
            ->addOption(
                'target',
                't',
                InputOption::VALUE_OPTIONAL,
                'Entity generation target dir'
            )
            ->addOption(
                'extends',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Entity parent class name'
            )
            ->addOption(
                'from-database',
                'db',
                InputOption::VALUE_OPTIONAL,
                'Generate entity from database'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $app = $input->getOption('app');
        $module = $input->getOption('module');
        $target = $input->getOption('target');
        $namespace = $input->getOption('namespace');
        $extends = $input->getOption('extends');
        $fromDb = $input->getOption('from-database');

        $this->app = $app ?: 'wscn';

    }
}
