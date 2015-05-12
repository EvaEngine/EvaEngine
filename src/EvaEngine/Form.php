<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine;

use Phalcon\Annotations\Collection as Property;
use Phalcon\Mvc\Model as PhalconModel;
use Phalcon\Annotations\Adapter\Memory as Annotations;
use Phalcon\Forms\ElementInterface;

/**
 * Class Form
 * @package Eva\EvaEngine
 */
class Form extends \Phalcon\Forms\Form
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var
     */
    protected $exclude;

    /**
     * @var
     */
    protected $model;

    /**
     * @var
     */
    protected $values;

    /**
     * @var
     */
    protected $formset;

    /**
     * @var
     */
    protected $relationKey;

    /**
     * @var Form
     */
    protected $parentForm;

    /**
     * @var string
     */
    protected $defaultModelClass;

    /**
     * @var bool
     */
    protected $initializedWithModel = false;

    /**
     * @var array
     */
    protected $rawPostData = array();

    /**
     * @var array
     */
    protected $elementAlias = array(
        'check' => 'Phalcon\Forms\Element\Check',
        'date' => 'Phalcon\Forms\Element\Date',
        'email' => 'Phalcon\Forms\Element\Email',
        'file' => 'Phalcon\Forms\Element\File',
        'hidden' => 'Phalcon\Forms\Element\Hidden',
        'numeric' => 'Phalcon\Forms\Element\Numeric',
        'number' => 'Phalcon\Forms\Element\Numeric',
        'password' => 'Phalcon\Forms\Element\Password',
        'select' => 'Phalcon\Forms\Element\Select',
        'submit' => 'Phalcon\Forms\Element\Submit',
        'text' => 'Phalcon\Forms\Element\Text',
        'textarea' => 'Phalcon\Forms\Element\TextArea',
    );

    /**
     * @var array
     */
    protected $validatorAlias = array(
        'between' => 'Phalcon\Validation\Validator\Between',
        'confirmation' => 'Phalcon\Validation\Validator\Confirmation',
        'email' => 'Phalcon\Validation\Validator\Email',
        'exclusionin' => 'Phalcon\Validation\Validator\ExclusionIn',
        'exclusion' => 'Phalcon\Validation\Validator\ExclusionIn',
        'identical' => 'Phalcon\Validation\Validator\Identical',
        'inclusionin' => 'Phalcon\Validation\Validator\InclusionIn',
        'inclusion' => 'Phalcon\Validation\Validator\InclusionIn',
        'presenceof' => 'Phalcon\Validation\Validator\PresenceOf',
        'regex' => 'Phalcon\Validation\Validator\Regex',
        'stringlength' => 'Phalcon\Validation\Validator\StringLength',
    );

    public function getRelationKey()
    {
        return $this->relationKey;
    }

    public function setRelationKey($key)
    {
        $this->relationKey = $key;

        return $this;
    }

    public function getDefaultModelClass()
    {
        return $this->defaultModelClass;
    }

    public function setDefaultModelClass($model)
    {
        $this->defaultModelClass = $model;

        return $this;
    }

    public function setParentForm($form)
    {
        $this->parentForm = $form;

        return $this;
    }

    public function getParentForm()
    {
        return $this->parentForm;
    }

    public function getRawPostData()
    {
        return $this->rawPostData;
    }

    public function setRawPostData($data)
    {
        $this->rawPostData = $data;

        return $this;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function setValues(array $data)
    {
        if (!$data) {
            return $this;
        }
        foreach ($data as $key => $value) {
            if ($this->has($key)) {
                $this->get($key)->setDefault($value);
            }
        }
        $this->values = $data;

        return $this;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        if (!$this->model) {
            return $this;
        }

        /*
        $elements = $this->_elements;
        foreach ($elements as $key => $element) {
            $element->setName($prefix . '[' . $element->getName() . ']');
        }
        */

        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setExclude($exclude)
    {
        $this->exclude = $exclude;

        return $this;
    }

    public function getExclude()
    {
        return $this->exclude;
    }

    public function getFormset()
    {
        return $this->formset;
    }

    public function setFormset($formset)
    {
        $this->formset = $formset;

        return $this;
    }

    public function getFullMessages()
    {
        if (!$this->formset) {
            return $this->getMessages();
        }

        $messages = $this->getMessages();
        foreach ($this->formset as $key => $form) {
            if ($subForm = $this->getForm($key)) {
                $messages->appendMessages($subForm->getMessages());
            }
        }

        return $messages;
    }

    public function getForm($formKey)
    {
        if (!isset($this->formset[$formKey])) {
            return false;
        }

        $form = $this->formset[$formKey];
        $form->initializeWithModel();

        return $form;
    }

    public function setModel(PhalconModel $model, $autoParse = true)
    {
        $this->model = $model;
        $this->setEntity($model);
        $reader = new Annotations();
        $modelProperties = $reader->getProperties($model);
        $formProperties = $reader->getProperties($this);
        foreach ($modelProperties as $key => $property) {
            //already added in initialize
            if ($this->has($key)) {
                continue;
            }
            $formProperty = isset($formProperties[$key]) ? $formProperties[$key] : null;
            $element = $this->createElementByProperty($key, $property, $formProperty);
            if ($element) {
                $this->add($element);
            }
        }
        $this->afterSetModel();
        return $this;
    }

    public function afterSetModel()
    {
        //callback
    }

    public function isFullValid($data, $entity = null)
    {
        $this->setRawPostData($data);

        if (!$this->formset) {
            $entity = $entity ? $entity : $this->model;
            return $this->isValid($data, $entity);
        }

        $formCount = count($this->formset);
        $validResult = 0;
        foreach ($this->formset as $key => $subForm) {
            $form = $this->getForm($key);
            if (isset($data[$key])) {
                if ($form->isValid($data[$key], $form->getModel())) {
                    $validResult++;
                }
            } else {
                $validResult++;
            }
        }

        $this->bind($data, $this->getModel());
        if ($this->isValid($data, $this->getModel())) {
            $validResult++;
        }

        return $validResult === $formCount + 1 ? true : false;
    }

    public function save($modelSaveMethod = 'save')
    {
        if (!$model = $this->model) {
            return $this;
        }

        if ($this->formset) {
            foreach ($this->formset as $relationKey => $subForm) {
                $relationModel = $this->getModel($relationKey);
                if ($model) {
                    $model->$relationKey = $relationModel;
                }
            }
        }

        $model->setModelForm($this);
        if ($modelSaveMethod == 'save') {
            if (!$model->save()) {
                throw new Exception\RuntimeException(get_class($model) . ' save failed');
            }

            return $model;
        } else {
            return $model->$modelSaveMethod($this->getRawPostData());
        }
    }

    public function initializeFormAnnotations()
    {
        $reader = new Annotations();
        $formProperties = $reader->getProperties($this);
        foreach ($formProperties as $key => $property) {
            //$formProperty = isset($formProperties[$key]) ? $formProperties[$key] : null;
            $element = $this->createElementByProperty($key, $property);
            if ($element && $element instanceof ElementInterface) {
                $this->add($element);
            }
        }

        return $this;
    }

    public function registerElementAlias($elementAlias, $elementClass)
    {
        $this->elementAlias[$elementAlias] = $elementClass;

        return $this;
    }

    public function getElementAlias()
    {
        return $this->elementAlias;
    }

    public function getModel($modelName = null)
    {
        if (!$modelName) {
            return $this->model;
        }

        //Get model from subform when model name not null
        if ($this->getForm($modelName)) {
            return $this->getForm($modelName)->getModel();
        }

        return $this->model;
    }

    public function render($name, $attributes = null)
    {
        if (!$this->prefix) {
            return parent::render($name, $attributes);
        }
        $attributes = array_merge(
            array(
            'name' => $this->prefix . '[' . $this->get($name)->getName() . ']'
            ),
            (array)$attributes
        );

        return parent::render($name, $attributes);
    }

    /*
    *  Simple usage
    *  $userForm->addForm('Profile', 'Eva\EvaUser\Forms\ProfileForm');
    *  Full usage
    *  $userForm->addForm('Profile', array(
            'form' => 'Eva\EvaUser\Forms\ProfileForm',
            'relationKey' => 'Profile',
            'relation' => 'hasOne',
            'relationModel' => 'Eva\EvaUser\Models\Profile',
        ));
    *
    *
    */
    public function addForm($prefix, $formOptions)
    {
        if (is_string($formOptions)) {
            $formClass = new $formOptions();
        } else {
            $formClass = isset($formOptions['form']) ? new $formOptions['form']() : null;
        }

        if (!($formClass instanceof Form)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Add formset failed by incorrect form class instance %s', $prefix)
            );
        }

        $formClass->setPrefix($prefix);
        $relationKey =
            is_array($formOptions)
            && isset($formOptions['relationKey'])
            ? $formOptions['relationKey']
            : $prefix;
        $formClass->setRelationKey($relationKey);
        $relationModel =
            is_array($formOptions)
            && isset($formOptions['relationModel'])
            ? $formOptions['relationModel']
            : null;
        if ($relationModel) {
            $formClass->setDefaultModelClass($relationModel);
        }

        $this->formset[$prefix] = $formClass;
        $formClass->setParentForm($this);

        return $this;
    }

    public function initializeWithModel()
    {
        if ($this->initializedWithModel || !$this->parentForm || !$this->relationKey) {
            return $this;
        }

        $relationKey = $this->relationKey;
        $model = $this->parentForm->getModel();

        if (isset($model->$relationKey) && $model->$relationKey) {
            $this->setModel($model->$relationKey);
        } else {
            $defaultModelClass = $this->getDefaultModelClass();
            if (!$defaultModelClass || false == class_exists($defaultModelClass)) {
                throw new Exception\RuntimeException(
                    sprintf('Form connected to incorrect model %s', $defaultModelClass)
                );
            }
            $this->setModel(new $defaultModelClass());
        }
        $this->initializedWithModel = true;

        return $this;
    }

    protected function createElementByProperty($elementName, Property $baseProperty, Property $mergeProperty = null)
    {
        $elementType = 'Phalcon\Forms\Element\Text';
        if (!$baseProperty && !$mergeProperty) {
            return new $elementType($elementName);
        }

        $property = $mergeProperty && $mergeProperty->has('Type') ? $mergeProperty : $baseProperty;
        if ($property->has('Type')) {
            $typeArguments = $property->get('Type')->getArguments();
            $alias = isset($typeArguments[0]) ? strtolower($typeArguments[0]) : null;
            $elementType = isset($this->elementAlias[$alias]) ? $this->elementAlias[$alias] : $elementType;
        }

        $property = $mergeProperty && $mergeProperty->has('Name') ? $mergeProperty : $baseProperty;
        if ($property->has('Name')) {
            $arguments = $property->get('Name')->getArguments();
            $elementName = isset($arguments[0]) ? $arguments[0] : $elementName;
        }
        $element = new $elementType($elementName);

        $property = $mergeProperty && $mergeProperty->has('Attr') ? $mergeProperty : $baseProperty;
        if ($property->has('Attr')) {
            $element->setAttributes($property->get('Attr')->getArguments());
        }

        $addValidator = function ($property, $element, $validatorAlias) {
            foreach ($property as $annotation) {
                if ($annotation->getName() != 'Validator') {
                    continue;
                }
                $arguments = $annotation->getArguments();
                if (!isset($arguments[0])) {
                    continue;
                }
                $validatorName = strtolower($arguments[0]);
                if (!isset($validatorAlias[$validatorName])) {
                    continue;
                }
                $validator = $validatorAlias[$validatorName];
                $element->addValidator(new $validator($arguments));
            }

            return $element;
        };
        if ($baseProperty->has('Validator')) {
            $element = $addValidator($baseProperty, $element, $this->validatorAlias);
        }
        if ($mergeProperty && $mergeProperty->has('Validator')) {
            $element = $addValidator($mergeProperty, $element, $this->validatorAlias);
        }

        $addFilter = function ($property, $element) {
            foreach ($property as $annotation) {
                if ($annotation->getName() != 'Filter') {
                    continue;
                }
                $arguments = $annotation->getArguments();
                if (!isset($arguments[0])) {
                    continue;
                }
                $filterName = strtolower($arguments[0]);
                $element->addFilter($filterName);
            }

            return $element;
        };
        if ($baseProperty->has('Filter')) {
            $element = $addFilter($baseProperty, $element);
        }
        if ($mergeProperty && $mergeProperty->has('Filter')) {
            $element = $addFilter($mergeProperty, $element);
        }

        $property = $mergeProperty && $mergeProperty->has('Options') ? $mergeProperty : $baseProperty;
        if ($property->has('Options')) {
            $element->setAttributes($property->get('Options')->getArguments());
        }

        $addOption = function ($property, $element) {
            $options = array();
            foreach ($property as $annotation) {
                if ($annotation->getName() != 'Option') {
                    continue;
                }
                $options += $annotation->getArguments();
            }
            $element->setOptions($options);

            return $element;
        };

        if ($baseProperty->has('Option')) {
            $element = $addOption($baseProperty, $element);
        }
        if ($mergeProperty && $mergeProperty->has('Option')) {
            $element = $addOption($mergeProperty, $element);
        }

        return $element;
    }
}
