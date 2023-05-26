<?php

namespace yii1tech\config;

use ArrayAccess;
use CModel;
use CModule;
use CValidator;
use LogicException;
use Yii;

/**
 * Item represents a single application configuration item.
 * It allows extraction and composition of the config value for the particular
 * config array keys sequence setup by {@see path}.
 *
 * @see \yii1tech\config\Manager
 *
 * @property string $value public alias of {@see _value}.
 * @property array $rules public alias of {@see _rules}.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Item extends CModel
{
    /**
     * @var string|int config parameter unique identifier.
     */
    public $id;
    /**
     * @var string label for the {@see $value} attribute.
     */
    public $label = 'Value';
    /**
     * @var mixed config parameter value.
     */
    private $_value;
    /**
     * @var mixed origin (before apply persistent storage) value of this item.
     */
    private $_originValue;
    /**
     * @var array validation rules.
     * Unlike the configuration for the common model, each rule should not contain attribute name
     * as it already determined as {@see value}.
     */
    private $_rules = [];
    /**
     * @var string|array application config path. Path is sequence of the config array keys.
     * It could be either a string, where keys are separated by '.', or an array of keys.
     * For example:
     * 'params.myparam';
     * array('params', 'myparam');
     * 'components.securityManager.validationKey';
     * array('components', 'securityManager', 'validationKey');
     * If path is not set it will point to {@see \CApplication::$params} with the key equals ot {@see $id}.
     */
    public $path;
    /**
     * @var string brief description for the config item.
     */
    public $description;
    /**
     * @var string|null native type for the value to be cast to.
     */
    public $cast;
    /**
     * @var array|null additional descriptive options for this item.
     * This field may contain any data, which can be consumed by other part of the program.
     * For example: it may hold options for the form input composition:
     *
     * ```php
     * [
     *    'inputType' => 'text',
     *    'inputCssClass' => 'config-input',
     * ]
     * ```
     */
    public $options;
    /**
     * @var object|null configuration source object.
     * If not set current Yii application instance will be used.
     */
    public $source;

    /**
     * @param mixed $value
     * @return static self reference.
     */
    public function setValue($value): self
    {
        if ($this->_originValue === null) {
            $this->_originValue['value'] = $this->getValue();
        }

        $this->_value = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->_value === null) {
            $this->_value = $this->extractCurrentValue();
        }

        return $this->_value;
    }

    /**
     * Prepares value for the saving into persistent storage, performing typecast if necessary.
     *
     * @return mixed value to be saved in persistent storage.
     */
    public function serializeValue()
    {
        $value = $this->getValue();

        if ($this->cast === null) {
            return $value;
        }

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return json_encode($value);
    }

    /**
     * Restores value from the raw one extracted from persistent storage, performing typecast if necessary.
     *
     * @param mixed $value value from persistent storage.
     * @return mixed actual config value.
     */
    public function unserializeValue($value)
    {
        $value = $this->castValue($value);

        $this->setValue($value);

        return $value;
    }

    /**
     * Restores original (before apply persistent storage) value of this item.
     *
     * @return static self reference.
     */
    public function resetValue(): self
    {
        if ($this->_originValue !== null) {
            $this->setValue($this->_originValue['value']);
            $this->_originValue = null;
        }

        return $this;
    }

    /**
     * @param array $rules
     * @return static self reference.
     */
    public function setRules(array $rules): self
    {
        $this->_rules = $rules;

        return $this;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->_rules;
    }

    /**
     * Returns the config path parts.
     * @return array config path parts.
     */
    public function getPathParts(): array
    {
        if (empty($this->path)) {
            $this->path = $this->composeDefaultPath();
        }

        if (is_array($this->path)) {
            $pathParts = $this->path;
        } else {
            $pathParts = explode('.', $this->path);
        }

        return $pathParts;
    }

    /**
     * Returns the list of attribute names of the model.
     * @return array list of attribute names.
     */
    public function attributeNames(): array
    {
        return [
            'value'
        ];
    }

    /**
     * Returns the attribute labels.
     * @return array<string, string> attribute labels in format `[name => label]`
     */
    public function attributeLabels(): array
    {
        return [
            'value' => $this->label,
        ];
    }

    /**
     * Creates validator objects based on the specification in {@see $rules}.
     * This method is mainly used internally.
     * @throws \LogicException on invalid configuration.
     * @return \CList validators built based on {@see rules()}.
     */
    public function createValidators()
    {
        $validatorList = parent::createValidators();
        $rules = $this->getRules();
        array_unshift($rules, ['safe']);

        foreach ($rules as $rule) {
            if (!isset($rule[0])) { // validator name
                throw new LogicException('Invalid validation rule for "' . $this->getAttributeLabel('value') . '". The rule must specify the validator name.');
            }

            $validatorList->add(CValidator::createValidator($rule[0], $this, 'value', array_slice($rule, 2)));
        }

        return $validatorList;
    }

    /**
     * Composes default config path, which points to {@see \CApplication::$params} array
     * with key equal to {@see $id}.
     * @return array config path.
     */
    protected function composeDefaultPath(): array
    {
        return ['params', $this->id];
    }

    /**
     * Extracts current config item value from the current application instance.
     * @return mixed current value.
     */
    public function extractCurrentValue()
    {
        $pathParts = $this->getPathParts();

        return $this->findConfigPathValue($this->source ? $this->source : Yii::app(), $pathParts);
    }

    /**
     * Finds the given config path inside given source.
     * @param array|object $source config source
     * @param array $pathParts config path parts.
     * @return mixed config param value.
     * @throws \LogicException on failure.
     */
    protected function findConfigPathValue($source, array $pathParts)
    {
        if (empty($pathParts)) {
            throw new LogicException('Empty extraction path.');
        }

        $name = array_shift($pathParts);
        if (is_array($source)) {
            if (!array_key_exists($name, $source)) {
                throw new LogicException('Key "' . $name . '" not present!');
            }

            $result = $source[$name];
        } elseif (is_object($source)) {
            if ($source instanceof CModule && $name === 'components') {
                $result = $source->getComponents(false);
            } else {
                if (isset($source->$name)) {
                    $result = $source->$name;
                } else {
                    if ($source instanceof ArrayAccess) {
                        $result = $source[$name];
                    } else {
                        throw new LogicException('Property "' . get_class($source) . '::' . $name . '" not present!');
                    }
                }
            }
        } else {
            throw new LogicException('Unable to extract path "' . implode('.', $pathParts) . '" from "' . gettype($source) . '"');
        }

        if (empty($pathParts)) {
            return $result;
        }

        return $this->findConfigPathValue($result, $pathParts);
    }

    /**
     * Composes application configuration array, which can setup this config item.
     * @return array application configuration array.
     */
    public function composeConfig()
    {
        $pathParts = $this->getPathParts();

        return $this->composeConfigPathValue($pathParts);
    }

    /**
     * Composes the configuration array by given path parts.
     * @param array $pathParts config path parts.
     * @return array configuration array segment.
     * @throws \LogicException on failure.
     */
    protected function composeConfigPathValue(array $pathParts)
    {
        if (empty($pathParts)) {
            throw new LogicException('Empty extraction path.');
        }

        $basis = [];
        $name = array_shift($pathParts);

        if (empty($pathParts)) {
            $basis[$name] = $this->value;
        } else {
            $basis[$name] = $this->composeConfigPathValue($pathParts);
        }

        return $basis;
    }

    /**
     * Typecasts raw value from persistent storage to the actual one according to {@see $cast} value.
     *
     * @param string $value value from persistent storage.
     * @return mixed actual value after typecast.
     */
    protected function castValue($value)
    {
        if ($this->cast === null) {
            return $value;
        }

        if ($value === null) {
            return $value;
        }

        switch ($this->cast) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return json_decode($value);
            case 'array':
            case 'json':
                return json_decode($value, true);
            default:
                throw new LogicException('Unsupported "' . get_class($this) . '::$cast" value: ' . print_r($this->cast, true));
        }
    }
}