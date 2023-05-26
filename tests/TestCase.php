<?php

namespace yii1tech\config\test;

use CConsoleApplication;
use CMap;
use Yii;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApplication();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->destroyApplication();
    }

    /**
     * Populates Yii::app() with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = CConsoleApplication::class)
    {
        Yii::setApplication(null);

        new $appClass(CMap::mergeArray([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'components' => [
                'db' => [
                    'class' => \CDbConnection::class,
                    'connectionString' => 'sqlite::memory:',
                ],
                'cache' => [
                    'class' => \CDummyCache::class,
                ],
            ],
        ], $config));
    }

    /**
     * Destroys Yii application by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::setApplication(null);
    }
}