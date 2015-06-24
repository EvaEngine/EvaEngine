<?='<?php'?>

namespace <?=$namespace?>\<?=$name?>;

use <?=$extends?> as BaseEntity;

/**
* Class <?=$name?>
*
* @package <?=$namespace?>
*
* @SWG\Model(id="<?=str_replace('\\', '_', $namespace) . '_' . $name?>")
*
*/
class <?=$name?> extends BaseEntity
{
<?if ($columns) :?>
<?
/** @var $column \Eva\EvaEngine\Db\Column */
foreach ($columns as $key => $column) :
?>
    /**
     *
     * @var <?=$phalconTypes[$column->getType()] . "\n";?>
     */
    public $<?=$column->getName()?><?=$column->getDefault() ? " = '{$column->getDefault()}'" : ''?>;

<?endforeach?>
<?endif?>
}
