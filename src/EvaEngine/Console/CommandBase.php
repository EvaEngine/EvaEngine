<?php
// +----------------------------------------------------------------------
// | wallstreetcn
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/17 下午7:25
// +----------------------------------------------------------------------
// + CommandBase.php
// +----------------------------------------------------------------------

namespace Eva\EvaEngine\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class CommandBase extends Command
{
    private $logger;
    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;
    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Style\SymfonyStyle
     */
    protected $output;

    public function getLogger()
    {
        if ($this->logger == null) {
            $this->logger = eva_get('consoleLogger');
        }
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Run the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = new SymfonyStyle($input, $output);

        return parent::run($input, $output);
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->fire();
    }

    abstract public function fire();

    /**
     * Call another console command.
     *
     * @param  string $command
     * @param  array $arguments
     * @return int
     */
//    public function call($command, array $arguments = [])
//    {
//        $instance = $this->getApplication()->find($command);
//        $arguments['command'] = $command;
//
//        return $instance->run(new ArrayInput($arguments), $this->output);
//    }

    /**
     * Call another console command silently.
     *
     * @param  string $command
     * @param  array $arguments
     * @return int
     */
    public function callSilent($command, array $arguments = [])
    {
        $instance = $this->getApplication()->find($command);
        $arguments['command'] = $command;

        return $instance->run(new ArrayInput($arguments), new NullOutput);
    }

    /**
     * Get the value of a command argument.
     *
     * @param  string $key
     * @return string|array
     */
    public function argument($key = null)
    {
        if (is_null($key)) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    /**
     * Get the value of a command option.
     *
     * @param  string $key
     * @return string|array
     */
    public function option($key = null)
    {
        if (is_null($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    /**
     * Confirm a question with the user.
     *
     * @param  string $question
     * @param  bool $default
     * @return bool
     */
    public function confirm($question, $default = false)
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Prompt the user for input.
     *
     * @param  string $question
     * @param  string $default
     * @return string
     */
    public function ask($question, $default = null)
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string $question
     * @param  array $choices
     * @param  string $default
     * @return string
     */
    public function anticipate($question, array $choices, $default = null)
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string $question
     * @param  array $choices
     * @param  string $default
     * @return string
     */
    public function askWithCompletion($question, array $choices, $default = null)
    {
        $question = new Question($question, $default);
        $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * @param  string $question
     * @param  bool $fallback
     * @return string
     */
    public function secret($question, $fallback = true)
    {
        $question = new Question($question);
        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param  string $question
     * @param  array $choices
     * @param  string $default
     * @param  mixed $attempts
     * @param  bool $multiple
     * @return bool
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
    {
        $question = new ChoiceQuestion($question, $choices, $default);
        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Format input to textual table.
     *
     * @param  array $headers
     * @param  array $rows
     * @param  string $style
     * @return void
     */
    public function table(array $headers, array $rows, $style = 'default')
    {
        $table = new Table($this->output);

        $table->setHeaders($headers)->setRows($rows)->setStyle($style)->render();
    }

    /**
     * Write a string as information output.
     *
     * @param  string $string
     * @return void
     */
    public function info($string)
    {
        $this->output->writeln("<info>$string</info>");
    }

    /**
     * Write a string as standard output.
     *
     * @param  string $string
     * @return void
     */
    public function line($string)
    {
        $this->output->writeln($string);
    }

    /**
     * Write a string as comment output.
     *
     * @param  string $string
     * @return void
     */
    public function comment($string)
    {
        $this->output->writeln("<comment>$string</comment>");
    }

    /**
     * Write a string as question output.
     *
     * @param  string $string
     * @return void
     */
    public function question($string)
    {
        $this->output->writeln("<question>$string</question>");
    }

    /**
     * Write a string as error output.
     *
     * @param  string $string
     * @return void
     */
    public function error($string)
    {
        $this->output->writeln("<error>$string</error>");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * Get the output implementation.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

}