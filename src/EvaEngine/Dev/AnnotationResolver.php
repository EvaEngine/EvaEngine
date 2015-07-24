<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Dev;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class AnnotationResolver extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ('Stmt_Property' !== $node->getType()) {
            return;
        }

        $property = isset($node->props[0]->name) ? $node->props[0]->name : '';
        if (!$property) {
            return;
        }

        $annotation = $node->getDocComment();
        $docComment = $annotation ? $annotation->getText() : '';
        $docComment = MakeEntity::annotationResolveCallback($property, $docComment);
        if ($annotation && $docComment) {
            $annotation->setText($docComment);
        }
    }
}
