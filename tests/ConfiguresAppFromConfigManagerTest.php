<?php

namespace yii1tech\config\test;

use Yii;
use yii1tech\config\ConfiguresAppFromConfigManager;
use yii1tech\config\Manager;
use yii1tech\config\StorageArray;

class ConfiguresAppFromConfigManagerTest extends TestCase
{
    protected function createConfigManager(): Manager
    {
        return (new Manager())
            ->setStorage($this->createStorage())
            ->setItems([
                'appName' => [
                    'path' => 'name',
                ],
                'dateFormat' => [
                    'path' => 'components.format.dateFormat',
                ],
                'existingParam' => [
                    'path' => 'params.param1',
                ],
                'newParam' => [
                    'path' => 'params.paramNew',
                ],
            ]);
    }

    protected function createStorage(): StorageArray
    {
        $storage = new StorageArray();

        $storage->save([
            'appName' => 'app-name-override',
            'dateFormat' => 'date-format-override',
            'existingParam' => 'param1-override',
            'newParam' => 'param-new-value',
        ]);

        return $storage;
    }

    public function testConfigureApplication(): void
    {
        $app = Yii::app();

        $app->setComponent('appConfigManager', $this->createConfigManager());

        $behavior = new ConfiguresAppFromConfigManager();

        $app->attachBehavior('test', $behavior);

        $app->run();

        $this->assertSame('app-name-override', $app->name);

        $this->assertSame('param1-override', $app->params['param1']);
        $this->assertSame('param2-value', $app->params['param2']);
        $this->assertSame('param-new-value', $app->params['paramNew']);

        $this->assertSame('date-format-override', $app->getFormat()->dateFormat);
    }
}