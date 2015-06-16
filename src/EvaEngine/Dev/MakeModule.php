<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Dev;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class MakeModule
 * @package Eva\EvaEngine\Dev
 */
class MakeModule extends Command
{
    /**
     * @var string
     */
    protected $templatesDir;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param $templatesDir
     * @return $this
     */
    public function setTemplatesDir($templatesDir)
    {
        $this->templatesDir = $templatesDir;
        return $this;
    }

    protected function configure()
    {
        $this
            ->setName('make:module')
            ->setDescription('Create a EvaEngine module')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Module name'
            )
            ->addOption(
                'namespace',
                'ns',
                InputOption::VALUE_OPTIONAL,
                'Module namespace'
            )
            ->addOption(
                'target',
                't',
                InputOption::VALUE_OPTIONAL,
                'Module generation target dir'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->name = $input->getArgument('name');
        $this->namespace = $input->getOption('namespace');
        $target = $input->getOption('target');
        $this->target = $target ?: getcwd();
        $this->templatesDir = realpath(__DIR__ . '/../../../templates/module');
        $this->output = $output;

        $res = $this->createModule($this->name, $this->namespace, $this->target);
        if (true === $res) {
            $output->writeln(sprintf("Created module %s in %s", $this->name, $this->target));
        }
    }

    public function createModule($moduleName, $namespace, $root)
    {
        /**
         * Module folders
         * - $root
         * ---- $moduleDir
         * ------ $moduleConfigDir
         * ------ $moduleSrcDir
         * ---- $moduleBootstrapFile
         */
        $fs = new Filesystem();
        $moduleDir = $root . '/' . $moduleName;
        $moduleSrcDir = $moduleDir . '/src/' . $moduleName;
        $moduleSrcDirSource = $moduleDir . '/src/_module';
        $templatesDir = $this->templatesDir;

        if (true === $fs->exists($moduleDir)) {
            $this->output->writeln(sprintf(
                '<error>Module %s already exists on %s</error>',
                $moduleName,
                $moduleDir
            ));
            return false;
        }

        $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $this->output->writeln(sprintf("Root dir %s", $root));
        $this->output->writeln(sprintf("Template dir %s", $templatesDir));

        //$fs->mkdir($moduleDir);
        $this->output->writeln(sprintf("Created module dir %s", $moduleDir));

        //$fs->mirror($templatesDir, $moduleDir);
        $this->output->writeln(sprintf("Copy files from %s to %s", $templatesDir, $moduleDir));

        //$fs->rename($moduleSrcDirSource, $moduleSrcDir);
        $this->output->writeln(sprintf(
            "Rename module dir from %s to %s",
            $moduleSrcDirSource,
            $moduleSrcDir
        ));

        return true;
    }
}
