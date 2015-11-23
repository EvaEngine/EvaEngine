<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Validation\Validator;

use Eva\EvaEngine\Exception\InvalidArgumentException;
use Eva\EvaEngine\IoC;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

class Uniqueness extends Validator implements ValidatorInterface
{
    /**
     * @param Validation $validation
     * @param string     $attribute
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws \Eva\EvaEngine\Exception\RuntimeException
     */
    public function validate(
        Validation $validation,
        $attribute
    ) {
        $table = $this->getOption('table');
        $field = $this->getOption('field');
        $excludeField = $this->getOption('excludeField');
        $exclude = $this->getOption('exclude');
        $value = $validation->getValue($attribute);
        if (!$table || !$field) {
            throw new InvalidArgumentException(sprintf('DB table and field required for validation %s', __CLASS__));
        }
        if ($excludeField && !$exclude || !$excludeField && $exclude) {
            throw new InvalidArgumentException(sprintf('DB exclude field and value required both %s', __CLASS__));
        }
        $message = $this->getOption('message');
        $message = $message ?: sprintf('Field %s require unique', $field);

        /** @var Mysql $db */
        $db = IoC::get('dbMaster');
        $sql = "SELECT COUNT(*) AS QCOUNT FROM $table WHERE $field = :value";
        if ($exclude) {
            $sql .= " AND $excludeField != :exclude";
        }
        $bindValues = $exclude ? [
            'exclude' => $exclude,
            'value' => $value,
        ] : [
            'value' => $value,
        ];
        $res = $db->query($sql, $bindValues);

        if ($res && ($res = $res->fetch()) && !empty($res['QCOUNT'])) {
            $validation->appendMessage(new Validation\Message($message, $attribute, 'Uniqueness'));

            return false;
        }

        return true;
    }
}
