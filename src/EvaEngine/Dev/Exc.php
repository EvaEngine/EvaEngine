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
use Eva\EvaEngine\Exception\StandardException;
use FilesystemIterator;
use GlobIterator;
use Phalcon\Config\Adapter\Ini;
use Phalcon\Text;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Make App
 *
 * @package Eva\EvaEngine\Dev
 */
class Exc extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * CLI configure
     */
    protected function configure()
    {
        $this
            ->setName('exc')
            ->setDescription('List/Search Exceptions');

        $this->addArgument(
            'code',
            InputArgument::OPTIONAL,
            'Exception code'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->root = $root = getcwd() . '/';


        $finder = new Finder();
        $files = $finder->in($root)->files()->name('*Exception.php');

        $this->output->writeln(sprintf('Start scan dir %s', $root));
        $mapping = [];
        foreach ($files as $key => $file) {
            /** @var SplFileInfo $file */
            if (false === preg_match("/namespace (.+);\n/", $file->getContents(), $matches)) {
                continue;
            }
            if (empty($matches[1])) {
                continue;
            }
            $class = $matches[1] . '\\' . $file->getBasename('.php');
            $code = StandardException::classNameToCode($class);
            $mapping[$code] = $class;
        }
        $inputCode = $input->getArgument('code');
        if (!$inputCode) {
            foreach ($mapping as $code => $class) {
                $this->output->writeln(sprintf('%s => %s', $code, $class));
            }
            return false;
        }

        if (isset($mapping[$inputCode])) {
            return $this->output->writeln(sprintf('%s => %s', $inputCode, $mapping[$inputCode]));
        }
        $this->output->writeln(sprintf('Not found code %s', $inputCode));
        return false;
    }
}
