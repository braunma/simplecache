<?php

/**
 * @link https://github.com/braunmar/simplecache
 * @copyright Copyright (c) 2016 Marek Braun
 * @license https://github.com/braunmar/simplecache/blob/master/LICENSE
 */

namespace braunmar\simple\cache;

/**
 * Simple cache class. Avaliable format is php, json and bin.
 * 
 * See https://github.com/braunmar/simplecache
 * 
 *      // you can pass config in constructor
 *      $cache = new braunmar\simple\cache\SimpleCache();
 * 
 *      // config
 *      $cache->config([
 *          'path' => __DIR__ . '/some/path',
 *          'filename' => 'filename',
 *          'type' => 'php',
 *          //...
 *      ]);
 * 
 *      // cache something
 *      $cache->cache(['key1' => 'value1', 'key2' => 'value2']);
 *      // load cache data
 *      $data = $cache->load();
 * 
 * This class is used with \raunmar\simple\classloader\ClassLoader.
 * See https://github.com/braunmar/classloader
 *      
 */
class SimpleCache
{

    /**
     * Save cache data in PHP format
     */
    const TYPE_PHP = 'php';

    /**
     * Save cache data in JSON format
     */
    const TYPE_JSON = 'json';

    /**
     * Save cache data in serialize format
     */
    const TYPE_SERIALIZE = "bin";

    /**
     * Availablle types for save
     */
    const TYPES = [self::TYPE_PHP, self::TYPE_JSON, self::TYPE_SERIALIZE];

    /**
     * File name
     * @var string
     */
    private $filename = 'classCache';

    /**
     * Set output to minify
     * Avaliable for JSON only
     * @var boolean
     */
    private $minify = true;

    /**
     * Path to save dir. Default is to this class dir
     * @var string
     */
    private $path;

    /**
     * Actual type for save format
     * @var string
     */
    private $type = self::TYPE_PHP;

    /**
     * Constructor
     * @param array $config Configure array
     */
    public function __construct($config = [])
    {
        $this->path = __DIR__;
        $this->config($config);
    }

    /**
     * Config
     * @param array $config Config array
     * @throws \InvalidArgumentException
     */
    public function config($config = [])
    {
        foreach ($config as $key => $value) {
            if (!isset($this->{$key})) {
                throw new \InvalidArgumentException("Parameter \"{$key}\" doesn't exist.");
            }

            $this->{$key} = $value;
        }

        if (!in_array($this->type, self::TYPES)) {
            throw new \InvalidArgumentException('Bad save type.');
        }

        if (!is_dir($this->path)) {
            throw new \InvalidArgumentException("Dir \"{$this->path}\" doesn't exist.");
        }
    }

    /**
     * Cache data into file
     * @param mixed $data Data to cache
     */
    public function cache($data = [])
    {
        if ($this->type == self::TYPE_PHP) {
            $this->writeToFile($this->makePhpFile(var_export($data, true)));
        } else if ($this->type == self::TYPE_JSON) {
            $this->writeToFile(json_encode($data, !$this->minify ? JSON_PRETTY_PRINT : 0));
        } else if ($this->type == self::TYPE_SERIALIZE) {
            $this->writeToFile(serialize($data));
        }
    }

    /**
     * Load cached data from file
     * @return mixed Data
     */
    public function load()
    {
        if (!is_file($this->getFullFilename())) {
            touch($this->getFullFilename());
        }

        if ($this->type == self::TYPE_JSON) {
            return json_decode($this->getFileContent());
        }

        if ($this->type == self::TYPE_SERIALIZE) {
            return unserialize($this->getFileContent());
        }

        return require $this->getFullFilename();
    }

    /**
     * Get path to cache file
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path to cache file
     * @param string $path Path to dir
     * @return SimpleCache $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get filename
     * @return string Filename
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set file name 
     * @param string $filename Filename
     * @return SimpleCache $this
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get data store type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     * @param string $type Type
     * @return SimpleCache $this
     * @throws \InvalidArgumentException Type not supported
     */
    public function setType($type)
    {
        if (!in_array($this->type, self::TYPES)) {
            throw new \InvalidArgumentException('Bad save type.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get minify
     * @return boolean
     */
    public function getMinify()
    {
        return $this->minify;
    }

    /**
     * Set minify. Avaliable for JSON only.
     * @param boolean $minify
     * @return SimpleCache
     */
    public function setMinify($minify)
    {
        $this->minify = $minify;

        return $this;
    }

    /**
     * Write text to file
     * @param string $data
     */
    protected function writeToFile($data)
    {
        $fp = $this->openFile('w');

        fwrite($fp, $data);

        fclose($fp);
    }

    /**
     * Return full filename
     * @return string
     */
    protected function getFullFilename()
    {
        return $this->path . '/' . $this->filename . '.' . $this->type;
    }

    /**
     * Open file for stored class
     * @param string $mode
     * @return stream
     */
    protected function openFile($mode = 'r')
    {
        return fopen($this->getFullFilename(), $mode);
    }

    /**
     * Get file content
     * @return string File content
     */
    protected function getFileContent()
    {
        return file_get_contents($this->getFullFilename(), 'r');
    }

    /**
     * Prepend php script tag and append ";\n" to make correct php script
     * @param string $str
     * @return string Modify string
     */
    protected function makePhpFile($str)
    {
        return "<?php\nreturn " . $str . ";\n";
    }

}
