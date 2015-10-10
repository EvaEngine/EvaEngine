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
use Symfony\Component\Finder\Finder;

/**
 * Class MakeModule
 * @package Eva\EvaEngine\Dev
 */
class MakeModule extends Command
{
    use TemplateSupportTrait;

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
     * CLI configure
     */
    protected function configure()
    {
        $this->target = getcwd() . '/modules';
        $this
            ->setName('make:module')
            ->setDescription('Create an EvaEngine module')
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->name = $input->getArgument('name');
        $namespace = $input->getOption('namespace');
        $namespace = $namespace ?: 'Eva\\' . $this->name;
        $this->namespace = $namespace;

        $target = $input->getOption('target');
        $this->target = $target ?: $this->target;
        $this->templatesDir = realpath(__DIR__ . '/../../../templates/module');
        $this->output = $output;

        $res = $this->createModule($this->name, $this->namespace, $this->target);
        if (true === $res) {
            $output->writeln(sprintf("<info>Created module %s in %s</info>", $this->name, $this->target));
        }
    }

    /**
     * Create a module from a template dir
     * @param string $moduleName
     * @param string $moduleNamespace
     * @param string $root
     * @return bool true if create success
     */
    public function createModule($moduleName, $moduleNamespace, $root)
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

        if (false === $fs->exists($root)) {
            $this->output->writeln(sprintf(
                '<error>Target %s not exists</error>',
                $root
            ));
            return false;
        }

        $moduleDir = $root . '/' . $moduleName;
        $moduleSrcDir = $moduleDir . '/src/' . $moduleName;
        $moduleSrcDirSource = $moduleDir . '/src/_module';
        $moduleTestDir = $moduleDir . '/tests/' . $moduleName . 'Tests';
        $moduleTestDirSource = $moduleDir . '/tests/_Tests';
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

        $fs->mkdir($moduleDir);
        $this->output->writeln(sprintf("Created module dir %s", $moduleDir));

        $fs->mirror($templatesDir, $moduleDir);
        $this->output->writeln(sprintf("Copy files from %s to %s", $templatesDir, $moduleDir));

        $fs->rename($moduleSrcDirSource, $moduleSrcDir);
        $this->output->writeln(sprintf(
            "Rename module dir from %s to %s",
            $moduleSrcDirSource,
            $moduleSrcDir
        ));

        $fs->rename($moduleTestDirSource, $moduleTestDir);
        $this->output->writeln(sprintf(
            "Rename module test dir from %s to %s",
            $moduleTestDirSource,
            $moduleTestDir
        ));

        $finder = new Finder();
        $finder->files()->name('*.*');
        foreach ($finder->in($moduleDir) as $file) {
            $filePath = $file->getRealpath();
            $content = $this->loadTemplate($filePath, [
                'moduleName' => $moduleName,
                'moduleNamespace' => $moduleNamespace,
            ]);
            $fs->dumpFile($filePath, $content);
            $this->output->writeln(sprintf("Updated file %s", $filePath));
        }

        return true;
    }
}
