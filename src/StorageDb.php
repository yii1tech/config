<?php

namespace yii1tech\config;

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
class StorageDb extends Storage {
    /**
     * @var string id of the database connection application component.
     */
    public $db = 'db';
    /**
     * @var string name of the table, which should store values.
     */
    public $table = 'app_config';

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
        $this->clear();

        $data = [];
        foreach ($values as $id => $value) {
            $data[] = array(
                'id' => $id,
                'value' => $value,
            );
        }

        $insertedRowsCount = $this->getDbConnection()
            ->getCommandBuilder()
            ->createMultipleInsertCommand($this->table, $data)
            ->execute();

        return (count($values) == $insertedRowsCount);
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
            $values[$row['id']] = $row['value'];
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->getDbConnection()->createCommand()->delete($this->table);

        return true;
    }
}