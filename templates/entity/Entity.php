<?='<?php'?>


namespace <?=$namespace?>;

use <?=$extends?> as BaseEntity;

/**
 * Class <?=$name?>

 *
 * @package <?=$namespace?>
 *
 * @SWG\Model(id="<?="$namespace\\$name"?>")
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
     * @SWG\Property(
     *   name="<?=$column->getName()?>",
     *   type="<?=$swaggerTypes[$column->getType()]?>",
     *   description="<?=$column->getComment()?>"
     * )
     *
     * @var <?=$phalconTypes[$column->getType()] . "\n";?>
     */
    public $<?=$column->getName()?><?=$column->getDefault() ? " = '{$column->getDefault()}'" : ''?>;

<?endforeach?>
<?endif?>

    /**
     * Database table name (Not including prefix)
     * @var string
     */
    protected $tableName = '<?=$tableName?>';
}
