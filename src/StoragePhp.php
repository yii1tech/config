<?php

namespace yii1tech\config;

use Yii;

/**
 * StoragePhp represents the configuration storage based on local PHP files.
 *
 * @property string $fileName public alias of {@see _fileName}.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StoragePhp extends Storage {
    /**
     * @var string name of the file, which should be used to store values.
     */
    protected $_fileName;

    /**
     * @param string $fileName
     * @return static self reference.
     */
    public function setFileName($fileName): self
    {
        $this->_fileName = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        if (empty($this->_fileName)) {
            $this->_fileName = $this->defaultFileName();
        }

        return $this->_fileName;
    }

    /**
     * Creates default {@see fileName} value.
     * @return string default file name.
     */
    protected function defaultFileName(): string
    {
        return Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . 'app_config_data.php';
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $values): bool
    {
        $this->clear();

        $fileName = $this->getFileName();

        $dirName = dirname($fileName);
        if (!file_exists($dirName)) {
            mkdir($dirName, 0777, true);
        }

        $bytesWritten = file_put_contents($fileName, $this->composeFileContent($values));
        $this->invalidateScriptCache($fileName);

        return ($bytesWritten > 0);
    }

    /**
     * {@inheritdoc}
     */
    public function get(): array
    {
        $fileName = $this->getFileName();
        if (file_exists($fileName)) {
            return require($fileName);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $fileName = $this->getFileName();
        if (file_exists($fileName)) {
            $this->invalidateScriptCache($fileName);

            return unlink($fileName);
        }

        return true;
    }

    /**
     * Composes file content for the given values.
     * @param array $values values to be saved.
     * @return string file content.
     */
    protected function composeFileContent(array $values): string
    {
        $content = '<?php return ' . var_export($values, true) . ';';

        return $content;
    }

    /**
     * Invalidates precompiled script cache (such as OPCache or APC) for the given file.
     * @param string $fileName file name.
     */
    protected function invalidateScriptCache($fileName)
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($fileName, true);
        }
        if (function_exists('apc_delete_file')) {
            @apc_delete_file($fileName);
        }
    }
}