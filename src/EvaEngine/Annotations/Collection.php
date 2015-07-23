<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Annotations;

use Phalcon\Annotations\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public function __construct(array $reflectionData = null)
    {
        $annotations = [];
        foreach ($reflectionData as $key => $annotationData) {
            $annotations[] = new Annotation($annotationData);
        }
        $this->_annotations = $annotations;
    }
}
