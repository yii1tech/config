<p align="center">
    <a href="https://github.com/yii1tech" target="_blank">
        <img src="https://avatars.githubusercontent.com/u/134691944" height="100px">
    </a>
    <h1 align="center">Application Runtime Configuration Extension for Yii 1</h1>
    <br>
</p>

This extension introduces persistent configuration repository for Laravel.
Its usage in particular provides support for application runtime configuration, loading config from database.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://img.shields.io/packagist/v/yii1tech/config.svg)](https://packagist.org/packages/yii1tech/config)
[![Total Downloads](https://img.shields.io/packagist/dt/yii1tech/config.svg)](https://packagist.org/packages/yii1tech/config)
[![Build Status](https://github.com/yii1tech/config/workflows/build/badge.svg)](https://github.com/yii1tech/config/actions)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii1tech/config
```

or add

```json
"yii1tech/config": "*"
```

to the "require" section of your composer.json.


Usage
-----

This extension allows reconfiguring already created Yii application instance using config extracted from external storage
like relational database. It allows to reconfigure any application property, component or module.
Configuration is performed by `\yii1tech\config\Manager` component, which should be added to the application configuration.
For example:

```php
[
    'behaviors' => [
        'configFromManagerBehavior' => [
            'class' => yii1tech\config\ConfiguresAppFromConfigManager::class,
        ],
        // ...
    ],
    'components' => [
        'configManager' => [
            'class' => yii1tech\config\Manager::class,
            'items' => [
                'appName' => [
                    'path' => 'name',
                    'label' => 'Application Name',
                    'rules' => [
                        ['required'],
                    ],
                ],
                'dateFormat' => [
                    'path' => 'components.format.dateFormat',
                    'label' => 'HTML representing not set value',
                    'rules' => [
                        ['required'],
                    ],
                ],
            ],
        ],
        ...
    ],
];
```

In order to apply configuration defined via `\yii1tech\config\Manager` - `yii1tech\config\ConfiguresAppFromConfigManager` application
behavior is used. It automatically updates the application configuration before request processing begins.
You can apply config manually to the application or any `\CModule` descendant, using following code:

```php
$configManager = Yii::app()->get('configManager');
$configManager->configure(Yii::app());
```


## Configuration items specification <span id="configuration-items-specification"></span>

Application parts, which should be reconfigured are determined by `\yii1tech\config\Manager::$items`, which is a list
of `\yii1tech\config\Item`. Each configuration item determines the configuration path - a list of keys in application
configuration array, which leads to the target value. For example: path 'components.format.dateFormat' (or
`['components', 'format', 'dateFormat']`) points to the property 'dateFormat' of `\CFormatter` component,
path 'name' points to `\CApplication::$name` and so on.

> Note: if no path is specified it will be considered as a key inside `\CModule::$params` array, which matches
configuration item id (name of key in `\yii1tech\config\Manager::$items` array).

Configuration item may also have several properties, which supports creation of web interface for configuration setup.
These are:

- 'label' - string, input label.
- 'description' - string, configuration parameter description or input hint.
- 'rules' - array, value validation rules.
- 'inputOptions' - array, list of any other input options.

Here are some examples of item specifications:

```php
'appName' => [
    'path' => 'name',
    'label' => 'Application Name',
    'rules' => [
        ['required'],
        ['string'],
    ],
],
'nullDisplay' => [
    'path' => 'components.format.dateFormat',
    'label' => 'Date representation format',
    'rules' => [
        ['required'],
        ['string'],
    ],
],
'adminEmail' => [
    'label' => 'Admin email address',
    'rules' => [
        ['required'],
        ['email'],
    ],
],
'adminTheme' => [
    'label' => 'Admin interface theme',
    'path' => ['modules', 'admin', 'theme'],
    'rules' => [
        ['required'],
        ['in', 'range' => ['classic', 'bootstrap']],
    ],
    'options' => [
        'type' => 'dropDown',
        'items' => [
            'classic' => 'Classic',
            'bootstrap' => 'Twitter Bootstrap',
        ],
    ],
],
```

> Tip: since runtime configuration may consist of many items and their declaration may cost a lot of code, it can
be moved into a separated file and specified by this file name.


## Configuration storage <span id="configuration-storage"></span>

Declared configuration items may be saved into persistent storage and then retrieved from it.
The actual item storage is determined via `\yii1tech\config\Manager::$storage`.

Following storages are available:
- [\yii1tech\config\StoragePhp](src/StoragePhp.php) - stores configuration inside PHP files
- [\yii1tech\config\StorageDb](src/StorageDb.php) - stores configuration inside relational database
- [\yii1tech\config\StorageActiveRecord](src/StorageActiveRecord.php) - finds configuration using ActiveRecord
- [\yii1tech\config\StorageArray](src/StorageArray.php) - uses internal array for the config storage, could be useful in unit tests

Please refer to the particular storage class for more details.


## Creating configuration web interface <span id="creating-configuration-web-interface"></span>

The most common use case for this extension is creating a web interface, which allows control of application
configuration in runtime.
`\yii1tech\config\Manager` serves not only for applying of the configuration - it also helps to create an
interface for configuration editing.

The web controller for configuration management may look like following:

```php
<?php

namespace app\controllers;

use CController;
use CHtml;
use Yii;

class ConfigController extends CController
{
    /**
     * Performs batch updated of application configuration records.
     */
    public function actionIndex()
    {
        /* @var $configManager \yii1tech\config\Manager */
        $configManager = Yii::app()->get('configManager');
        
        $configManager->restoreValues();

        $models = $configManager->getItems();

        if (!empty($_POST)) {
            $valid = true;
            foreach ($models as $model) {
                $modelName = CHtml::modelName($model);
                if (isset($_POST[$modelName][$model->id])) {
                    $model->setAttributes($_POST[$modelName][$model->id]);
                }
                $valid = $valid && $model->validate();
            }
            
            if ($valid) {
                $configManager->save();

                Yii::app()->getComponent('user')->setFlash('success', 'Configuration saved.');
                
                $controller->refresh();
                
                return;
            }
        }

        return $this->render('index', [
            'models' => $models,
        ]);
    }

    /**
     * Restores default values for the application configuration.
     */
    public function actionDefault()
    {
        /* @var $configManager \yii1tech\config\Manager */
        $configManager = Yii::$app->get('configManager');
        $configManager->reset();
        
        Yii::app()->getComponent('user')->setFlash('success', 'Default configuration restored.');
        
        $this->redirect(['index']);
        
        return;
    }
}
```

The main view file can be following:

```php
<?php
/** @var $this CController */ 
/** @var $form CActiveForm */ 
?>
<?php $form = $this->beginWidget(CActiveForm::class); ?>
    <?php foreach ($models as $model):?>
        <?php echo $form->labelEx($model, 'value'); ?>
        <div class="row">
            <?php echo $form->textField($model, '[' . $model->id . ']value'); ?>
        </div>
        <?php echo $form->error($model, 'value'); ?>
    <?php endforeach;?>
    
    <div class="form-group">
        <?= CHtml::link('Restore defaults', ['default'], ['class' => 'btn btn-danger', 'data-confirm' => 'Are you sure you want to restore default values?']); ?>
        &nbsp;
        <?= CHtml::submitButton('Save', ['class' => 'btn btn-primary']) ?>
    </div>

<?php $this->endWidget(); ?>
```
