<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Test;

/**
 * Class TestCase
 * @package Eva\EvaEngine\Test
 */
class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    use EngineTestCaseTrait;

    /**
     * @var \PDO
     */
    static private $pdo;

    /**
     * @var string
     */
    static private $dbPrefix;

    /**
     * @var
     */
    private $conn;

    /**
     * Returns the test database connection.
     *
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        if ($this->conn) {
            return $this->conn;
        }

        $config = $this->engine->getDI()->getConfig()->dbAdapter;
        $config = $config->master;

        if (self::$pdo == null) {
            self::$pdo = new \PDO(
                "{$config->adapter}:dbname={$config->dbname};host={$config->host}",
                $config->username,
                $config->password,
                [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
        }
        return $this->conn = $this->createDefaultDBConnection(self::$pdo, $config->dbname);
    }

    /**
     * @param $tableName
     * @return string
     */
    protected function getTableName($tableName)
    {
        $prefix = self::$dbPrefix ?: self::$dbPrefix = $this->engine->getDI()->getConfig()->dbAdapter->prefix;
        return $prefix . $tableName;
    }


    /**
     * @param $table
     * @return bool
     */
    protected function truncateTable($table)
    {
        $table = $this->getTableName($table);
        /* @var $db \Phalcon\Db\Adapter\Pdo\Mysql */
        $db = $this->engine->getDI()->get('dbMaster');
        $db->execute("SET FOREIGN_KEY_CHECKS = 0");
        $success = $db->execute("TRUNCATE TABLE `$table`");
        $db->execute("SET FOREIGN_KEY_CHECKS = 1");
        return $success;
    }

    /**
     * Returns the test dataset.
     * @return \PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet(getenv('APPLICATION_ROOT') . '/tests/data_set.xml');
    }
}
