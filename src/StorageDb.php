<?php

namespace yii1tech\config;

use CDbCriteria;
use Yii;

/**
 * StorageDb represents the configuration storage based on database table.
 *
 * Example migration for such table:
 *
 * ```php
 * $tableName = 'app_config';
 * $columns = [
 *     'id' => 'string',
 *     'value' => 'text',
 *     'PRIMARY KEY(id)',
 * ];
 * $this->createTable($tableName, $columns);
 * ```
 *
 * @property \CDbConnection $dbConnection database connection instance.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StorageDb extends Storage
{
    /**
     * @var string id of the database connection application component.
     */
    public $db = 'db';
    /**
     * @var string name of the table, which should store values.
     */
    public $table = 'app_config';
    /**
     * @var string name of the column, which should store config item key.
     */
    public $keyColumn = 'id';
    /**
     * @var string name of the column, which should store config item value.
     */
    public $valueColumn = 'value';

    /**
     * @return \CDbConnection database connection application component.
     */
    public function getDbConnection()
    {
        return Yii::app()->getComponent($this->db);
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $values): bool
    {
        $existingValues = $this->get();

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $existingValues)) {
                if ($value === $existingValues[$key]) {
                    continue;
                }

                $criteria = new CDbCriteria();
                $criteria->addColumnCondition([$this->keyColumn => $key]);
                $data = [$this->valueColumn => $value];

                $this->getDbConnection()
                    ->getCommandBuilder()
                    ->createUpdateCommand($this->table, $data, $criteria)
                    ->execute();
            } else {
                $data = [
                    $this->keyColumn => $key,
                    $this->valueColumn => $value,
                ];

                $this->getDbConnection()
                    ->getCommandBuilder()
                    ->createInsertCommand($this->table, $data)
                    ->execute();
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(): array
    {
        $command = $this->getDbConnection()->createCommand();
        $command->setFrom($this->table);
        $rows = $command->queryAll();

        $values = [];
        foreach ($rows as $row) {
            $values[$row[$this->keyColumn]] = $row[$this->valueColumn];
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->getDbConnection()
            ->createCommand()
            ->delete($this->table);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clearValue($key): bool
    {
        $criteria = new CDbCriteria();
        $criteria->addColumnCondition([$this->keyColumn => $key]);

        $this->getDbConnection()
            ->createCommand()
            ->delete($this->table, $criteria->condition, $criteria->params);

        return true;
    }
}