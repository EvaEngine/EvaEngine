<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Dev;

use Eva\EvaEngine\Annotations\Reader;
use Eva\EvaEngine\Db\ColumnsFactory;
use Eva\EvaEngine\Exception;
use Phalcon\Annotations\Adapter\Memory as AnnotationHandler;
use Phalcon\Annotations\Annotation;
use Phalcon\Db\Column;
use Phalcon\Db\Adapter;
use Phalcon\Text;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
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

    protected static $processingModule = [
        'name' => '',
        'namespace' => '',
        'class' => '',
        'extends' => '',
        'dbPrefix' => '',
        'dbTable' => '',
        'dbFullTable' => '',
        'target' => '',
    ];

    protected static $processingEntity = [
        'class' => '',
        'target' => '',
    ];

    protected static $annotations = [];

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function registerEnvOptions(...$options)
    {
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                call_user_func_array([$this, 'addOption'], $option);
                continue;
            }

            $this->addOption(
                $option,
                null,
                InputOption::VALUE_OPTIONAL
            );
        }
    }

    /**
     * Dynamic load module & get module namespace by Module Name
     *
     * @return array
     * @throws Exception\IOException
     * @throws Exception\LogicException
     */
    public function getModuleInfo()
    {
        $moduleName = Env::getVariable('module');
        $modulePath = getcwd() . '/modules/' . $moduleName;
        if (false == file_exists($modulePath)) {
            throw new Exception\IOException(sprintf("Path %s not exists", $modulePath));
        }

        $srcPath = $modulePath . '/src/' . $moduleName;
        $bootstrapFile = $modulePath . '/Module.php';
        if (false == file_exists($bootstrapFile)) {
            throw new Exception\IOException(sprintf("Module bootstrapfile %s not exists", $bootstrapFile));
        }

        require_once $bootstrapFile;
        $classes = get_declared_classes();
        $moduleRef = null;
        foreach ($classes as $class) {
            if (false === strpos($class, '\\') //Module MUST have namespace
                || true === Text::startsWith($class, 'Phalcon') //Skip Phalcon official classes
                || true === Text::startsWith($class, 'Symfony') //Skip Symfony classes
            ) {
                continue;
            }

            list($lastName, $middleName) = array_reverse(explode('\\', $class));

            if ($lastName === 'Module' && $middleName === $moduleName) {
                $moduleRef = new \ReflectionClass($class);
                break;
            }
        }

        if (!$moduleRef) {
            throw new Exception\LogicException(
                sprintf("Expected class %s not found in module bootstrap file", "$moduleName\\Module")
            );
        }

        if (false === array_key_exists('Eva\EvaEngine\Module\StandardInterface', $moduleRef->getInterfaces())) {
            throw new Exception\LogicException(
                sprintf("Expected class %s not implements EvaEngine module interface", $class)
            );
        }

        return [
            'moduleName' => $moduleName,
            'srcPath' => $srcPath,
            'entityPath' => $srcPath . '/Entities',
            'bootstrapFile' => $bootstrapFile,
            'class' => $class,
            'namespace' => $moduleRef->getNamespaceName() . '\\Entities',
        ];
    }

    public function getDbConnection()
    {
        $dbConfig = [
            'adapter' => Env::getVariable('db-adapter'),
            'dbname' => Env::getVariable('db-dbname'),
            'username' => Env::getVariable('db-username'),
            'host' => Env::getVariable('db-host'),
            'password' => Env::getVariable('db-password'),
            'charset' => Env::getVariable('db-charset'),
        ];

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
        return $db;
    }

    /**
     * CLI configure
     */
    protected function configure()
    {
        $this->template = realpath(__DIR__ . '/../../../templates/entity/Entity.php');

        $this->registerEnvOptions('app', 'module', 'db-perfix');

        $this
            ->setName('make:entity')
            ->setDescription('Create an entity')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Entity name'
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
        $name = $input->getArgument('name');

        if (false === file_exists($this->template)) {
            $output->writeln(sprintf(
                '<error>Template file %s not exists</error>',
                $this->template
            ));
            return false;
        }

        $module = $this->getModuleInfo();
        $namespace = $input->getOption('namespace') ?: $module['namespace'];
        $dbTable = $input->getOption('db-table');
        $dbPrefix = Env::getVariable('db-prefix');

        $target = $input->getOption('target') ?: $module['entityPath'] . '/' . ucfirst($name) . '.php';
        self::$processingEntity = array_merge(self::$processingEntity, [
            'name' => $name,
            'namespace' => $namespace,
            'class' => "$namespace\\$name",
            'extends' => $input->getOption('extends'),
            'dbPrefix' => $dbPrefix,
            'dbTable' => $dbTable,
            'dbFullTable' => $dbPrefix . $dbTable,
            'target' => $target,
        ]);

        if (true === file_exists($target)) {
            return $this->update($input, $output, $module);
            /*
            $output->writeln(sprintf(
                '<error>Target entity file %s already exists</error>',
                $target
            ));
            return false;
            */
        }

        return $this->create($input, $output, $module);

    }

    protected function create(InputInterface $input, OutputInterface $output, $module)
    {
        $entity = self::$processingEntity;
        $name = $entity['name'];
        $target = $entity['target'];
        $namespace = $entity['namespace'];
        $extends = $entity['extends'];
        $dbTable = $entity['dbTable'];
        $dbFullTable = $entity['dbFullTable'];

        $dbColumns = [];
        if ($dbTable) {
            /** @var Adapter $db */
            $db = $this->getDbConnection();
            $dbColumns = $this->dbTableToEntity($db, $dbFullTable);
        }

        $fs = new Filesystem();
        $content = $this->loadTemplate($this->template, [
            'name' => $name,
            'namespace' => rtrim($namespace, '\\'),
            'columns' => $dbColumns,
            'extends' => $extends,
            'tableName' => $dbTable,
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

        $fs->dumpFile($target, $content);
        $output->writeln(sprintf("<info>Entity %s created as file %s</info>", $name, $target));
    }

    protected function update(InputInterface $input, OutputInterface $output, $module)
    {
        $entity = self::$processingEntity;
        $name = $entity['name'];
        $target = $entity['target'];
        $namespace = $entity['namespace'];
        $extends = $entity['extends'];
        $dbTable = $entity['dbTable'];
        $dbFullTable = $entity['dbFullTable'];
        $dbColumns = [];

        require_once $target;

        if ($dbTable) {
            /** @var Adapter $db */
            $db = $this->getDbConnection();
            $dbColumns = $this->dbTableToEntity($db, $dbFullTable);
        }

        self::$processingModule = $module;
        $parser = new Parser(new Emulative());
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new AnnotationResolver());
        $prettyPrinter = new Standard();
        $stmts = $parser->parse(file_get_contents($target));
        $stmts = $traverser->traverse($stmts);
        $code = $prettyPrinter->prettyPrintFile($stmts);

        $fs = new Filesystem();
        $fs->dumpFile($target, $code);
        $output->writeln(sprintf("<info>Entity %s updated as file %s</info>", $name, $target));
    }

    /**
     * @param $class
     * @return \Phalcon\Annotations\Reflection
     */
    public static function getAnnotations($class)
    {
        if (isset(self::$annotations[$class])) {
            return self::$annotations[$class];
        }

        $annotationHandler = new AnnotationHandler();
        $annotationHandler->setReader(new Reader());
        return self::$annotations[$class] = $annotationHandler->get($class);
    }

    public static function getPropertyAnnotation($class, $property)
    {
        $annotation = self::getAnnotations($class);
        $annotations = $annotation->getPropertiesAnnotations();
        if (isset($annotations[$property])) {
            return $annotations[$property];
        }
        return null;
    }

    public static function annotationResolveCallback($property, $rawAnnotation)
    {
        $entity = self::$processingEntity;
        $class = $entity['class'];
        if (!$class) {
            return $rawAnnotation;
        }

        $annotationCollection = self::getPropertyAnnotation($class, $property);
        $annotations = $annotationCollection->getAnnotations();
        return self::annotationsToString($annotations);
    }

    public static function annotationsToString(array $annotations = [])
    {
        $docComment = '';
        if (!$annotations) {
            return "/**\n*/\n";
        }

        $docComment .= "/**\n";

        foreach ($annotations as $key => $annotation) {
            /** @var Annotation $annotation */
            $docComment .= " * @{$annotation->getName()}";
            print_r($annotation);exit;

            if ($annotation->numberArguments() > 0) {
                $docComment .= '(';
                $arguments = $annotation->getArguments();
                $argumentsArray = [];
                foreach ($arguments as $key => $value) {
                    $argumentsArray[] = "$key=\"$value\"";
                }
                $docComment .= implode(',', $argumentsArray) . ")";
            }
            $docComment .= "\n";
        }

        $docComment .= "*/\n";
        dd($docComment);
        exit;
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
