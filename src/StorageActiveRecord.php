<?php

namespace yii1tech\config;

/**
 * StorageActiveRecord is an configuration storage based on ActiveRecord.
 *
 * Example migration for ActiveRecord table:
 *
 * ```php
 * $tableName = 'app_config';
 * $columns = [
 *     'id' => 'pk',
 *     'key' => 'string',
 *     'value' => 'text',
 *     'PRIMARY KEY(id)',
 * ];
 * $this->createTable($tableName, $columns);
 * ```
 *
 * @see \CActiveRecord
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StorageActiveRecord extends Storage
{
    /**
     * @var string|\CActiveRecord name of the ActiveRecord model class, which should store the config values.
     */
    public $model;

    /**
     * @var string name of the model attribute, which should store config item key.
     */
    public $keyAttribute = 'key';

    /**
     * @var string name of the model attribute, which should store config item value.
     */
    public $valueAttribute = 'value';

    /**
     * {@inheritdoc}
     */
    public function save(array $values): bool
    {
        $existingModels = $this->createModelFinder()->findAll();

        $result = true;

        foreach ($existingModels as $key => $existingModel) {
            if (array_key_exists($existingModel->{$this->keyAttribute}, $values)) {
                $existingModel->value = $values[$existingModel->{$this->keyAttribute}];
                $result = $result && $existingModel->save(false);

                unset($values[$existingModel->{$this->keyAttribute}]);
                unset($existingModels[$key]);
            }
        }

        foreach ($values as $key => $value) {
            /* @var $model \CActiveRecord */
            $model = new $this->model();
            $attributes = [$this->keyAttribute => $key, $this->valueAttribute => $value];
            foreach ($attributes as $attributeName => $attributeValue) {
                $model->{$attributeName} = $attributeValue;
            }
            $result = $result && $model->save(false);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): array
    {
        $result = [];

        foreach ($this->createModelFinder()->findAll() as $model) {
            $result[$model->{$this->keyAttribute}] = $model->{$this->valueAttribute};
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $result = true;

        foreach ($this->createModelFinder()->findAll() as $model) {
            $result = $result && $model->delete();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clearValue($key): bool
    {
        $model = $this->createModelFinder()
            ->findByAttributes([$this->keyAttribute => $key]);

        if (!empty($model)) {
            return $model->delete();
        }

        return true;
    }

    /**
     * @return \CActiveRecord model finder instance.
     */
    protected function createModelFinder()
    {
        $class = $this->model;

        return $class::model($class);
    }
}