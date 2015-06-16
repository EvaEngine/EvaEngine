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
 * Class Make App
 * @package Eva\EvaEngine\Dev
 */
class MakeApp extends MakeModule
{
    /**
     * CLI configure
     */
    protected function configure()
    {
        parent::configure();
        $this->target = getcwd() . '/apps';
        $this
            ->setName('make:app')
            ->setDescription('Create a EvaEngine App');
    }

    public function __construct()
    {
        $this->target = getcwd() . '/apps';
        parent::__construct();
    }
}
