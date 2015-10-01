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
use Eva\EvaEngine\Annotations\Adapter\Memory as AnnotationHandler;
use Eva\EvaEngine\Annotations\Annotation;
use Eva\EvaEngine\Db\Column;
use Phalcon\Db\Adapter;
use Phalcon\Loader;
use Phalcon\Text;
use PhpParser\BuilderFactory;
use PhpParser\Lexer\Emulative;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeAbstract;
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
use Symfony\Component\Process\Process;

/**
 * Class Make Entity
 * @package Eva\EvaEngine\Dev
 */
class MakeMigration extends Command
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
            ->setName('migration:generate')
            ->setDescription('Generate migration files')
            ->addArgument(
                'db-table',
                InputArgument::OPTIONAL,
                'DB table name'
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
        $this->input = $input;
        $this->output = $output;

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
            return $this->update($module);
        }

        return $this->create($module);

    }

    /**
     * Create an entity
     *
     * @param $module
     * @throws Exception\RuntimeException
     */
    protected function create($module)
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
            'phalconTypes' => self::$phalconTypes,
            'swaggerTypes' => self::$swaggerTypes,
        ]);

        $fs->dumpFile($target, $content);
        $this->output->writeln(sprintf("<info>Entity %s created as file %s</info>", $name, $target));
    }

    /**
     * Update an entity
     *
     * @param $module
     * @return mixed
     * @throws Exception\LogicException
     * @throws Exception\RuntimeException
     */
    protected function update($module)
    {

        self::$propertiesInCode = [];
        self::$propertiesInDb = [];
        $entity = self::$processingEntity;
        $name = $entity['name'];
        $namespace = $entity['namespace'];
        $class = $entity['class'];
        $target = $entity['target'];
        $dbTable = $entity['dbTable'];
        $dbFullTable = $entity['dbFullTable'];
        self::$processingDb = null;

        //Register namespaces & path because entity may extends some base classes
        $loader = new Loader();
        $loader->registerNamespaces([
            $namespace => dirname($target)
        ]);
        $loader->register();

        if (false === class_exists($class)) {
            throw new Exception\LogicException(sprintf("Entity class %s not exist in file %s", $class, $target));
        }

        if ($dbTable) {
            /** @var Adapter $db */
            $db = $this->getDbConnection();
            self::$processingDb = $columns = $this->dbTableToEntity($db, $dbFullTable);
        }

        self::$processingModule = $module;
        try {
            //Parse code prepare
            $parser = new Parser(new Emulative());
            $traverser = new NodeTraverser();
            //Added callback
            $traverser->addVisitor(new AnnotationResolver());
            $stmts = $parser->parse(file_get_contents($target));
            //Refer: https://github.com/nikic/PHP-Parser/blob/1.x/doc/2_Usage_of_basic_components.markdown
            $stmts = $traverser->traverse($stmts);

            $columnKeys = array_keys(self::$processingDb);
            $properties = self::$propertiesInCode;
            //Maybe DB added some columns
            if ($diff = array_diff($columnKeys, $properties)) {
                //Append new columns to entity
                $stmts = $this->appendNewDbColomns($stmts, $diff);
            }
            //import SWG if not
            $stmts = $this->importSwagger($stmts);

            //Maybe user defined some custom properties OR DB removed some columns
            if ($diff = array_diff($properties, $columnKeys)) {
                //Nothing to do for now
            }

            //Print out code
            $prettyPrinter = new Standard();
            $code = $prettyPrinter->prettyPrintFile($stmts);
        } catch (\Exception $e) {
            return $this->output->writeln($e);
        }

        $fs = new Filesystem();
        $fs->dumpFile($target, $code);
        if ($diff) {
            $this->output->writeln(sprintf(
                "WARING: Found properties <error>%s</error> in Entity %s but not in DB.",
                implode(",", $diff),
                $name
            ));
        }
        $this->output->writeln(sprintf("<info>Entity %s updated as file %s</info>", $name, $target));
        $process = new Process("phpcbf $target --standard=PSR2");
        $process->run();
        if (!$process->isSuccessful()) {
            $this->output->writeln($process->getErrorOutput());
        } else {
            $this->output->writeln($process->getOutput());
        }
    }

    protected function importSwagger(array $stmts)
    {
        $namespaces = $stmts[0]->stmts;
        $imported = false;
        foreach ($namespaces as $key => $stmt) {
            /** @var \PhpParser\Node\Stmt\UseUse $stmt */
            if ('Stmt_Use' === $stmt->getType() && ['Swagger', 'Annotations'] === $stmt->uses[0]->name->parts) {
                $imported = true;
            }
        }

        if (false === $imported) {
            $factory = new BuilderFactory();
            $node = $factory->use('Swagger\Annotations')->as('SWG')->getNode();
            array_splice($namespaces, count($namespaces), 0, [$node]);
            $stmts[0]->stmts = $namespaces;
        }

        return $stmts;
    }

    protected function appendNewDbColomns(array $stmts, $diff)
    {
        $columns = self::$processingDb;

        //Stmts construct:
        //- PhpParser\Node\Stmt\Namespace_
        //---- PhpParser\Node\Stmt\Use_
        //---- PhpParser\Node\Stmt\Use_
        //---- PhpParser\Node\Stmt\Class_
        //-------- PhpParser\Node\Stmt\Property
        //-------- PhpParser\Node\Stmt\Property
        //-------- PhpParser\Node\Stmt\ClassMethod
        $classStmt = array_pop($stmts[0]->stmts);
        $innerStmts = $classStmt->stmts;
        $lastPropertyIndex = 0;
        foreach ($innerStmts as $key => $stmt) {
            $lastPropertyIndex = $key;
            if (!($stmt instanceof \PhpParser\Node\Stmt\Property)) {
                break;
            }
        }


        $factory = new BuilderFactory();
        $swaggerTypes = self::$swaggerTypes;
        $phalconTypes = self::$phalconTypes;
        foreach ($diff as $columnName) {
            if (empty($columns[$columnName])) {
                continue;
            }
            /** @var Column $column */
            $column = $columns[$columnName];
            $swaggerType = $swaggerTypes[$column->getType()];
            $phalconType = $phalconTypes[$column->getType()];
            $propery = $factory->property($columnName)->setDocComment(<<<DOC

/**
 * @SWG\Property(
 *   name="{$column->getName()}",
 *   type="$swaggerType",
 *   description="{$column->getComment()}"
 * )
 *
 * @var $phalconType
 */
DOC
            )->getNode();
            //Insert new DB property before last entity property
            array_splice($classStmt->stmts, $lastPropertyIndex - 1, 0, [$propery]);
        }

        array_push($stmts[0]->stmts, $classStmt);
        return $stmts;
    }

    /**
     * @return AnnotationHandler
     */
    public static function getAnnotationHandler()
    {
        if (self::$annotationHandler) {
            return self::$annotationHandler;
        }

        $annotationHandler = new AnnotationHandler();
        $annotationHandler->setReader(new Reader());
        return self::$annotationHandler = $annotationHandler;
    }

    /**
     * Called by PHP Parser node traverser when property has annoations
     *
     * @param $property
     * @param $rawAnnotation
     * @return string
     */
    public static function annotationResolveCallback($property, $rawAnnotation)
    {
        //Skip properties which not a DB column
        if ('tableName' !== $property) {
            self::$propertiesInCode[] = $property;
        }

        $entity = self::$processingEntity;
        $class = $entity['class'];
        if (!$class) {
            return $rawAnnotation;
        }

        /** @var Column $column */
        $column = isset(self::$processingDb[$property]) ? self::$processingDb[$property] : null;
        $annotationHandler = self::getAnnotationHandler();
        $reflection = $annotationHandler->get($class);
        $annotationCollection = $reflection->getPropertiesAnnotations();
        if (!$column && empty($annotationCollection[$property])) {
            return $rawAnnotation;
        }

        $annotations = $annotationCollection[$property]->getAnnotations();
        if ($column) {
            $hasSwaggerAnnotation = false;
            //Update Swagger Annotation by DB
            foreach ($annotations as $key => $annotation) {
                /** @var Annotation $annotation */
                if ('SWG\Property' == $annotation->getName()) {
                    $hasSwaggerAnnotation = true;
                    $annotations[$key] = self::mergeColumnToSwaggerAnnotation($column, $annotation);
                    break;
                }
            }

            if (false === $hasSwaggerAnnotation) {
                array_unshift($annotations, new Annotation([
                    'name' => 'SWG\Property',
                    'arguments' => [
                        [
                            'name' => 'name',
                            'expr' => [
                                'type' => 303, //Phalcon string type is 303
                                'value' => $property,
                            ],
                        ],
                        [
                            'name' => 'type',
                            'expr' => [
                                'type' => 303,
                                'value' => self::$swaggerTypes[$column->getType()],
                            ],
                        ],
                        [
                            'name' => 'description',
                            'expr' => [
                                'type' => 303,
                                'value' => $column->getComment()
                            ],
                        ],
                    ]
                ]));
            }
        }
        return self::annotationsToString($annotations);
    }

    private static function mergeColumnToSwaggerAnnotation(Column $column, Annotation $annotation)
    {
        $arguments = $annotation->getArguments();
        $arguments = array_merge($arguments, [
            'type' => self::$swaggerTypes[$column->getType()],
            //If already has description, not overwrite
            'description' => $arguments['description'] ?: $column->getComment()
        ]);
        $annotation->setArguments($arguments);
        return $annotation;
    }

    /**
     * @param array $annotations
     * @return string
     */
    public static function annotationsToString(array $annotations = [])
    {
        $docComment = '';
        if (!$annotations) {
            return "\n/**\n*/\n";
        }

        $docComment .= "\n/**\n";

        foreach ($annotations as $key => $annotation) {
            /** @var Annotation $annotation */
            $docComment .= (string)$annotation;
        }

        $docComment .= " */";

        return $docComment;
    }

    /**
     * Simple template engine
     *
     * @param $path
     * @param array $vars
     * @return string
     */
    public function loadTemplate($path, array $vars = [])
    {
        ob_start();
        extract($vars);
        include $path;
        $content = ob_get_clean();
        return $content;
    }

    /**
     * @param Adapter $db
     * @param $tableName
     * @return array
     * @throws \Eva\EvaEngine\Exception\BadMethodCallException
     */
    public function dbTableToEntity(Adapter $db, $tableName)
    {
        //Note: Phalcon use `DESCRIBE db.table` to get scheme
        //Not able to get scheme comments
        return ColumnsFactory::factory($db->describeColumns($tableName), $db, $tableName);
    }
}
