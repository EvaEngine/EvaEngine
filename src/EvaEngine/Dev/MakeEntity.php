<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Dev;

use Eva\EvaEngine\Db\ColumnsFactory;
use Eva\EvaEngine\Exception;
use Phalcon\Db\Column;
use Phalcon\Db\Adapter;
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

    protected $template;

    public function setTemplate()
    {

    }

    /**
     * CLI configure
     */
    protected function configure()
    {

        $this->template = realpath(__DIR__ . '/../../../templates/entity/Entity.php');

        $this
            ->setName('make:entity')
            ->setDescription('Create an entity')
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
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity generation target dir'
            )
            ->addOption(
                'extends',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Entity parent class name',
                'Eva\EvaEngine\Mvc\Model'
            )
            ->addOption(
                'db-config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database connection config file path'
            )
            ->addOption(
                'db-prefix',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database table prefix',
                'eva_'
            )
            ->addOption(
                'db-table',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database table name (without prefix)'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     * @throws Exception\RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runPath = getcwd();
        $name = $input->getArgument('name');
        $app = $input->getOption('app');
        $module = $input->getOption('module');
        $target = $input->getOption('target');
        $target = $target ?: $runPath;
        $namespace = $input->getOption('namespace');
        $extends = $input->getOption('extends');
        $dbTable = $input->getOption('db-table');
        $dbPrefix = $input->getOption('db-prefix');
        $dbConnection = $input->getOption('db-config');
        $dbConnection = $dbConnection ?: $runPath . '/engine-config.php';

        if (false === file_exists($this->template)) {
            $output->writeln(sprintf(
                '<error>Template file %s not exists</error>',
                $this->template
            ));
            return false;
        }

        $dbColumns = [];
        if ($dbTable) {
            $dbTable = $dbPrefix . $dbTable;
            if (false === file_exists($dbConnection)) {
                $output->writeln(sprintf(
                    '<error>DB connection config file %s not exists</error>',
                    $dbConnection
                ));
                return false;
            }

            $dbConfig = array_merge([
                'adapter' => 'mysql',
                'dbname' => '',
                'username' => 'root',
                'host' => 'localhost',
                'password' => '',
                'charset' => 'utf8',
            ], include $dbConnection);

            $adapterKey = $dbConfig['adapter'];
            $adapterMapping = array(
                'mysql' => 'Phalcon\Db\Adapter\Pdo\Mysql',
                'oracle' => 'Phalcon\Db\Adapter\Pdo\Oracle',
                'postgresql' => 'Phalcon\Db\Adapter\Pdo\Postgresql',
                'sqlite' => 'Phalcon\Db\Adapter\Pdo\Sqlite',
            );

            $adapterClass = empty($adapterMapping[$adapterKey]) ? $adapterKey : $adapterMapping[$adapterKey];

            if (false === class_exists($adapterClass)) {
                throw new Exception\RuntimeException(sprintf('No matched DB adapter found by %s', $adapterClass));
            }

            /** @var Adapter $db */
            $db = new $adapterClass($dbConfig);
            $dbColumns = $this->dbTableToEntity($db, $dbTable);
        }

        $fs = new Filesystem();
        $content = $this->loadTemplate($this->template, [
            'name' => $name,
            'namespace' => rtrim($namespace, '\\'),
            'columns' => $dbColumns,
            'extends' => $extends,
            'phalconTypes' => [
                Column::TYPE_INTEGER => 'integer',
                Column::TYPE_DATE => 'date',
                Column::TYPE_VARCHAR => 'string',
                Column::TYPE_DECIMAL => 'float',
                Column::TYPE_DATETIME => 'datetime',
                Column::TYPE_CHAR => 'string',
                Column::TYPE_TEXT => 'text',
            ],
            //Swagger Types: https://github.com/swagger-api/swagger-spec/blob/master/versions/2.0.md#data-types
            'swaggerTypes' => [
                Column::TYPE_INTEGER => 'integer',
                Column::TYPE_DATE => 'date',
                Column::TYPE_VARCHAR => 'string',
                Column::TYPE_DECIMAL => 'number',
                Column::TYPE_DATETIME => 'date-time',
                Column::TYPE_CHAR => 'string',
                Column::TYPE_TEXT => 'text',
            ]
        ]);
        $fs->dumpFile(__DIR__ . '/test.php', $content);

    }

    public function loadTemplate($path, array $vars = [])
    {
        ob_start();
        extract($vars);
        include $path;
        $content = ob_get_clean();
        return $content;
    }

    public function dbTableToEntity(Adapter $db, $tableName)
    {
        //Note: Phalcon use `DESCRIBE db.table` to get scheme
        //Not able to get scheme comments
        return ColumnsFactory::factory($db->describeColumns($tableName), $db, $tableName);
    }
}
