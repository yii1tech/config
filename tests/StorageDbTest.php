<?php

namespace yii1tech\config\test;

use Yii;
use yii1tech\config\StorageDb;

class StorageDbTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestConfigTable();
    }

    /**
     * @return string test table name
     */
    protected function getTestTableName(): string
    {
        return '_test_config';
    }

    /**
     * Creates test config table.
     */
    protected function createTestConfigTable(): void
    {
        $dbConnection = Yii::app()->db;
        $columns = [
            'id' => 'string',
            'value' => 'string',
        ];
        $dbConnection->createCommand()->createTable($this->getTestTableName(), $columns);
    }

    /**
     * @return \yii1tech\config\StorageDb test storage instance.
     */
    protected function createTestStorage() {
        $config = array(
            'class' => StorageDb::class,
            'db' => 'db',
            'table' => $this->getTestTableName(),
        );

        return Yii::createComponent($config);
    }

    // Tests :

    public function testSave(): void
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $this->assertTrue($storage->save($values), 'Unable to save values!');
    }

    /**
     * @depends testSave
     */
    public function testGet(): void
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $storage->save($values);
        $this->assertEquals($values, $storage->get(), 'Unable to get values!');
    }

    /**
     * @depends testGet
     */
    public function testClear(): void
    {
        $storage = $this->createTestStorage();
        $values = [
            'name1' => 'value1',
            'name2' => 'value2',
        ];
        $storage->save($values);

        $this->assertTrue($storage->clear(), 'Unable to clear values!');
        $this->assertEquals([], $storage->get(), 'Values are not cleared!');
    }
}