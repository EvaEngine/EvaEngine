<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Db;

use Phalcon\Db\Adapter;
use Phalcon\Db;
use Phalcon\Text;

/**
 * Class ColumnsFactory
 * @package Eva\EvaEngine\Db
 */
class ColumnsFactory
{
    /**
     * Parse Enum string to array
     * Enum string format MUST as same as `enum('deleted','draft','published','pending')`
     *
     * @param $string
     * @return array
     */
    public static function parseEum($string)
    {
        if (false === Text::startsWith($string, 'enum')) {
            return [];
        }

        return explode("','", substr($string, 6, -2));
    }

    /**
     * Convert Phalcon columns array to EvaEngine columns array
     *
     * @param array $columns
     * @param Adapter $db
     * @param $dbTable
     * @return array
     * @throws Exception\BadMethodCallException
     */
    public static function factory(array $columns, Adapter $db, $dbTable)
    {
        if (get_class($db) !== 'Phalcon\Db\Adapter\Pdo\Mysql') {
            throw new Exception\BadMethodCallException(sprintf("EvaEngine columns only support Mysql currently"));
        }

        $dbName = $db->getDescriptor()['dbname'];
        $queryResult = $db->query("SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE, COLUMN_COMMENT FROM
                 information_schema.COLUMNS WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = '$dbTable'");
        $queryResult->setFetchMode(Db::FETCH_ASSOC);
        $schema = $queryResult->fetchAll(Db::FETCH_ASSOC);
        $columnExtras = [];

        foreach ($schema as $key => $value) {
            $enum = self::parseEum($value['COLUMN_TYPE']);
            $columnExtras[$value['COLUMN_NAME']] = [
                'isEnum' => $enum ? true : false,
                'enumerations' => $enum,
                'comment' => $value['COLUMN_COMMENT']
            ];
        }

        $columnsArray = [];
        /** @var Column $column */
        foreach ($columns as $key => $column) {
            $columnName = $column->getName();
            $columnArray = [
                'type' => $column->getType(),
                'typeValues' => $column->getTypeValues(),
                'typeReference' => $column->getTypeReference(),
                'notNull' => $column->isNotNull(),
                'primary' => $column->isPrimary(),
                'size' => $column->getSize(),
                'default' => $column->getDefault(),
                'unsigned' => $column->isUnsigned(),
                'isNumeric' => $column->isNumeric(),
                'autoIncrement' => $column->isAutoIncrement(),
                'first' => $column->isFirst(),
                'after' => $column->getAfterPosition(),
                'bindType' => $column->getBindType(),
            ];

            if (in_array($column->getType(), [
                Column::TYPE_INTEGER,
                Column::TYPE_DECIMAL,
                Column::TYPE_FLOAT,
            ])) {
                $columnArray['scale'] = $column->getScale();
            }

            $columnArray = array_merge($columnArray, $columnExtras[$columnName]);
            $column = new Column($columnName, $columnArray);
            $columnsArray[$column->getName()] = $column;
        }

        return $columnsArray;
    }
}
