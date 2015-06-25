<?php
// +----------------------------------------------------------------------
// | wallstreetcn
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/17 下午6:56
// +----------------------------------------------------------------------
// + CLIApplication.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Console;


use Eva\EvaEngine\Foundation\ApplicationInterface;
use Imagine\Exception\RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApplication extends Application implements ApplicationInterface
{
    protected $output;
    protected $input;
    protected $di;
    protected $debug;

    public function __construct(
        $name = 'EvaEngine',
        $debug = true
    ) {
        parent::__construct($name);
        $this->input = new ArgvInput();
        $this->output = new ConsoleOutput(ConsoleOutput::VERBOSITY_VERY_VERBOSE);
    }

    public function initializeErrorHandler()
    {
        set_error_handler(function () {
//            dd(func_get_args());
        });
//        new Formatter
        set_exception_handler(function (\Exception $e) {
            $this->renderException($e, $this->output);
        });
        register_shutdown_function(function () {
//            dd(func_get_args());
        });

        return $this;
    }

    public function initialize()
    {
        $moduleManager = eva_get('moduleManager');

        foreach ($moduleManager->getAllRoutesConsole()->toArray() as $commandName => $commandClass) {
            if (!class_exists($commandClass)) {

                throw new RuntimeException(sprintf('Class of Command "%s" not found, class: %s', $commandName,
                    $commandClass));
            }
            /** @var \Symfony\Component\Console\Command\Command $command */
            $command = new $commandClass($commandName);

            $this->add($command);
        }

        return $this;
    }

    public function fire()
    {
        $this->run($this->input, $this->output);
    }

    /**
     * Sets the dependency injector
     *
     * @param \Phalcon\DiInterface $dependencyInjector
     */
    public function setDI($dependencyInjector)
    {
        $this->di = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }
}