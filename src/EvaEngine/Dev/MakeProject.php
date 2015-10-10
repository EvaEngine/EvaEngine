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
 * Class MakeProject
 * @package Eva\EvaEngine\Dev
 */
class MakeProject extends Command
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
     * @var OutputInterface
     */
    protected $output;

    /**
     * CLI configure
     */
    protected function configure()
    {
        $this->target = getcwd();
        $this
            ->setName('make:project')
            ->setDescription('Create an EvaEngine project')
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Module target path, default is cwd path'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        $this->target = $target ?: $this->target;
        $this->templatesDir = realpath(__DIR__ . '/../../../templates/project');
        $this->output = $output;

        $res = $this->initProject($this->target);
        if (true === $res) {
            $output->writeln(sprintf("<info>Created project in %s</info>", $this->target));
        }
    }

    /**
     * Init an evaengine project
     * @param $target
     * @return bool
     */
    public function initProject($target)
    {
        $fs = new Filesystem();

        if (false === $fs->exists($target)) {
            $this->output->writeln(sprintf(
                '<error>Target %s not exists</error>',
                $target
            ));
            return false;
        }

        $templatesDir = $this->templatesDir;

        if (true === $fs->exists($target . '/public/index.php')) {
            $this->output->writeln(sprintf(
                '<error>Project already inited on %s</error>',
                $target
            ));
            return false;
        }

        $this->output->writeln(sprintf("Target dir %s", $target));
        $this->output->writeln(sprintf("Template dir %s", $templatesDir));

        $fs->mirror($templatesDir, $target);
        $this->output->writeln(sprintf("Copy files from %s to %s", $templatesDir, $target));

        $finder = new Finder();
        $finder->files()->name('*.php');
        foreach ($finder->in($target) as $file) {
            $filePath = $file->getRealpath();
            $content = $this->loadTemplate($filePath, [
            ]);
            $fs->dumpFile($filePath, $content);
            $this->output->writeln(sprintf("Updated file %s", $filePath));
        }
        return true;
    }
}
