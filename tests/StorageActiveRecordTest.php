<?php

namespace yii1tech\config\test;

use Yii;
use yii1tech\config\StorageActiveRecord;
use yii1tech\config\test\support\ConfigItem;

class StorageActiveRecordTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestDbSchema();
    }

    /**
     * @return string test table name
     */
    protected function getTestTableName(): string
    {
        return 'test_config';
    }

    /**
     * Creates test config table.
     */
    protected function createTestDbSchema(): void
    {
        $dbConnection = Yii::app()->db;
        $columns = [
            'id' => 'pk',
            'key' => 'string',
            'value' => 'string',
        ];
        $dbConnection->createCommand()->createTable($this->getTestTableName(), $columns);
    }

    /**
     * @return \yii1tech\config\StorageActiveRecord test storage instance.
     */
    protected function createTestStorage(): StorageActiveRecord
    {
        $config = [
            'class' => StorageActiveRecord::class,
            'model' => ConfigItem::class,
        ];

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

        $returnedValues = $storage->get();

        $this->assertEquals($values, $returnedValues, 'Unable to get values!');
    }

    /**
     * @depends testSave
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
        $this->assertEmpty($storage->get(), 'Values are not cleared!');
    }

    /**
     * @depends testSave
     */
    public function testClearValue()
    {
        $storage = $this->createTestStorage();
        $values = [
            'test.name' => 'Test name',
            'test.title' => 'Test title',
        ];

        $storage->save($values);
        $storage->clearValue('test.name');

        $returnedValues = $storage->get();

        $this->assertFalse(array_key_exists('test.name', $returnedValues));
        $this->assertTrue(array_key_exists('test.title', $returnedValues));
    }
}