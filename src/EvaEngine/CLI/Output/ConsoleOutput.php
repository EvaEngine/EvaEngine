<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eva\EvaEngine\CLI\Output;

use Eva\EvaEngine\CLI\Formatter\OutputFormatterInterface;

/**
 * ConsoleOutput is the default class for all CLI output. It uses STDOUT.
 *
 * This class is a convenient wrapper around `StreamOutput`.
 *
 *     $output = new ConsoleOutput();
 *
 * This is equivalent to:
 *
 *     $output = new StreamOutput(fopen('php://stdout', 'w'));
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class ConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    private $stderr;

    /**
     * Constructor.
     *
     * @param int $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
     * @param bool|null $decorated Whether to decorate messages (null for auto-guessing)
     * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
     *
     * @api
     */
    public function __construct(
        $verbosity = self::VERBOSITY_NORMAL,
        $decorated = null,
        OutputFormatterInterface $formatter = null
    ) {
        $outputStream = 'php://stdout';
        if (!$this->hasStdoutSupport()) {
            $outputStream = 'php://output';
        }

        parent::__construct(fopen($outputStream, 'w'), $verbosity, $decorated, $formatter);

        $this->stderr = new StreamOutput(fopen('php://stderr', 'w'), $verbosity, $decorated, $this->getFormatter());
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated)
    {
        parent::setDecorated($decorated);
        $this->stderr->setDecorated($decorated);
    }

    /**
     * {@inheritdoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
        parent::setFormatter($formatter);
        $this->stderr->setFormatter($formatter);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity($level)
    {
        parent::setVerbosity($level);
        $this->stderr->setVerbosity($level);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorOutput()
    {
        return $this->stderr;
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorOutput(OutputInterface $error)
    {
        $this->stderr = $error;
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     *
     * IBM iSeries (OS400) exhibits character-encoding issues when writing to
     * STDOUT and doesn't properly convert ASCII to EBCDIC, resulting in garbage
     * output.
     *
     * @return bool
     */
    protected function hasStdoutSupport()
    {
        return ('OS400' != php_uname('s'));
    }

    public function writelnInfo($message, $type = self::OUTPUT_NORMAL)
    {
        $this->writeln("<info>{$message} </info>", $type);
    }

    public function writelnError($message, $type = self::OUTPUT_NORMAL)
    {
        $this->writeln("<error>{$message} </error>", $type);
    }

    public function writelnWarning($message, $type = self::OUTPUT_NORMAL)
    {
        $this->writeln("<warning>{$message} </warning>", $type);
    }

    public function writelnComment($message, $type = self::OUTPUT_NORMAL)
    {
        $this->writeln("<comment>{$message} </comment>", $type);
    }

    public function writelnSuccess($message, $type = self::OUTPUT_NORMAL)
    {
        $this->writeln("<success>{$message} </success>", $type);
    }

    public function writeList(array $list)
    {
        $maxLength = 0;
        foreach ($list as $k => $v) {
            $k = trim($k);
            if (strlen($k) > $maxLength) {
                $maxLength = strlen($k);
            }
        }
        foreach ($list as $k => $v) {
            $k = trim($k);
            $kLen = strlen($k);
            $this->write('<info>  ' . $k . $this->getSpace($maxLength - $kLen) . '    </info>');
            $this->writeln($v);
        }
        $this->writeln("");

    }

    private function getSpace($length)
    {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= ' ';
        }

        return $str;
    }
}
