<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

class IdCardNumber extends Validator implements ValidatorInterface
{
    /**
     * @param $card
     *
     * @return bool|string
     */
    public static function to18Card($card)
    {
        $card = trim($card);

        if (strlen($card) == 18) {
            return $card;
        }

        if (strlen($card) != 15) {
            return false;
        }

        // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
        if (array_search(substr($card, 12, 3), array('996', '997', '998', '999')) !== false) {
            $card = substr($card, 0, 6) . '18' . substr($card, 6, 9);
        } else {
            $card = substr($card, 0, 6) . '19' . substr($card, 6, 9);
        }
        $card = $card . self::getVerifyNum($card);

        return $card;
    }

    /**
     * @param string $cardBase
     *
     * @return bool|string
     */
    private static function getVerifyNum($cardBase)
    {
        if (strlen($cardBase) != 17) {
            return false;
        }
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

        // 校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

        $checksum = 0;
        for ($i = 0; $i < strlen($cardBase); $i++) {
            $checksum += (int) (substr($cardBase, $i, 1) * $factor[$i]);
        }

        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];

        return $verify_number;
    }

    /**
     * @param Validation $validation
     * @param string     $attribute
     *
     * @return bool
     */
    public function validate(
        Validation $validation,
        $attribute
    ) {
        $number = $validation->getValue($attribute);
        $number = self::to18Card($number);
        $message = $this->getOption('message');
        $message = $message ?: 'Id card number not correct';

        if (strlen($number) !== 18) {
            $validation->appendMessage(new Validation\Message($message, $attribute, 'IdCardNumber'));

            return false;
        }

        $cardBase = substr($number, 0, 17);

        if (self::getVerifyNum($cardBase) !== strtoupper(substr($number, 17, 1))) {
            $validation->appendMessage(new Validation\Message($message, $attribute, 'IdCardNumber'));

            return false;
        }

        return true;
    }
}
