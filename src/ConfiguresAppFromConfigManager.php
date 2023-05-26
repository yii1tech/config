<?php

namespace yii1tech\config;

use CApplication;
use CBehavior;
use CEvent;
use CLogger;
use LogicException;
use Yii;

/**
 * Configures (re-configures) application using data acquired from configuration manager.
 *
 * Application configuration example:
 *
 * ```php
 * [
 *     'behaviors' => [
 *         'configFromManagerBehavior' => [
 *             'class' => yii1tech\config\ConfiguresAppFromConfigManager::class,
 *         ],
 *         // ...
 *     ],
 *     'components' => [
 *         'appConfigManager' => [
 *             'class' => yii1tech\config\Manager::class,
 *             'items' => [
 *                 // ...
 *             ],
 *         ],
 *         // ...
 *     ],
 *     // ...
 * ];
 * ```
 *
 * @see \yii1tech\config\Manager
 *
 * @mixin \CApplication
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class ConfiguresAppFromConfigManager extends CBehavior
{
    /**
     * @var \yii1tech\config\Manager|string config manager instance or its ID inside application components.
     */
    public $configManager = 'appConfigManager';

    /**
     * @param \CApplication $app application instance to be configured.
     * @return \yii1tech\config\Manager config manager instance.
     * @throws \LogicException on failure
     */
    protected function getConfigManager(CApplication $app)
    {
        if (is_object($this->configManager)) {
            return $this->configManager;
        }

        $configManager = $app->getComponent($this->configManager);
        if (!is_object($configManager)) {
            throw new LogicException('Application component "' . $this->configManager . '" is missing.');
        }

        return $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function events(): array
    {
        return [
            'onBeginRequest' => 'beginRequest',
        ];
    }

    /**
     * This event raises before {@link CApplication}.
     * It update {@link CApplication::params} with database data.
     * @param CEvent $event event object.
     */
    public function beginRequest(CEvent $event): void
    {
        $this->configureApplication($event->sender);
    }

    /**
     * Updates application configuration from config manager.
     * @param \CApplication $app application instance to be configured.
     */
    protected function configureApplication(CApplication $app): void
    {
        try {
            $app->configure($this->getConfigManager($app)->fetchConfig());
        } catch (\Exception $exception) {
            // application can be run before the persistent storage is ready, for example: before the first DB migration applied
            Yii::log(
                '"' . get_class($this) . '" is unable to update application configuration from config manager:' . $exception->getMessage(),
                CLogger::LEVEL_WARNING
            );
        }
    }
}