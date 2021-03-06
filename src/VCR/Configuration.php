<?php

namespace VCR;

use VCR\Util\Assertion;

/**
 * Configuration stores a Videorecorders configuration options.
 *
 * Those configuration options might be:
 *  - which library hooks to use,
 *  - where to store cassettes or
 *  - which files to scan when filtering source code.
 */
class Configuration
{
    private $cassettePath = 'tests/fixtures';

    // All are enabled by default
    private $enabledLibraryHooks;
    private $availableLibraryHooks = array(
        'stream_wrapper' => 'VCR\LibraryHooks\StreamWrapperHook',
        'curl'           => 'VCR\LibraryHooks\CurlHook',
        'soap'           => 'VCR\LibraryHooks\SoapHook',
    );

    // Yaml by default
    private $enabledStorage = 'yaml';
    private $availableStorages = array(
        'json' => 'VCR\Storage\Json',
        'yaml' => 'VCR\Storage\Yaml',
    );

    // All are enabled by default
    private $enabledRequestMatchers;
    private $availableRequestMatchers = array(
        'method'       => array('VCR\RequestMatcher', 'matchMethod'),
        'url'          => array('VCR\RequestMatcher', 'matchUrl'),
        'host'         => array('VCR\RequestMatcher', 'matchHost'),
        'headers'      => array('VCR\RequestMatcher', 'matchHeaders'),
        'body'         => array('VCR\RequestMatcher', 'matchBody'),
        'post_fields'  => array('VCR\RequestMatcher', 'matchPostFields'),
        'query_string' => array('VCR\RequestMatcher', 'matchQueryString'),
    );
    private $whiteList = array();
    private $blackList = array('src/VCR/LibraryHooks/', 'src/VCR/Util/SoapClient', 'tests/VCR/Filter');

    /**
     *
     * @return array
     */
    public function getBlackList()
    {
        return $this->blackList;
    }

    /**
     * @param string|array $paths
     *
     * @return Configuration
     */
    public function setBlackList($paths)
    {
        $paths = (is_array($paths)) ? $paths : array($paths);

        $this->blackList = $paths;

        return $this;
    }

    /**
     * Returns the path to where cassettes are stored.
     *
     * @return string Path to where cassettes are stored.
     */
    public function getCassettePath()
    {
        $this->assertValidCassettePath($this->cassettePath);

        return $this->cassettePath;
    }

    /**
     * @param string $cassettePath Path where to store cassettes.
     *
     * @return $this
     */
    public function setCassettePath($cassettePath)
    {
        $this->assertValidCassettePath($cassettePath);
        $this->cassettePath = $cassettePath;

        return $this;
    }

    /**
     * Validates a specified cassette path.
     *
     * @param string $cassettePath Path to a cassette.
     * @throws VCRException If cassette path is invalid.
     */
    private function assertValidCassettePath($cassettePath)
    {
        Assertion::directory(
            $cassettePath,
            "Cassette path '{$cassettePath}' is not a directory. Please either "
            . "create it or set a different cassette path using "
            . "VCR::configure()->setCassettePath('directory')."
        );
    }

    public function getLibraryHooks()
    {
        if (is_null($this->enabledLibraryHooks)) {
            return array_values($this->availableLibraryHooks);
        }

        return array_values(array_intersect_key(
            $this->availableLibraryHooks,
            array_flip($this->enabledLibraryHooks)
        ));
    }

    public function enableLibraryHooks($hooks)
    {
        $hooks = is_array($hooks) ? $hooks : array($hooks);
        $invalidHooks = array_diff($hooks, array_keys($this->availableLibraryHooks));
        if ($invalidHooks) {
            throw new \InvalidArgumentException("Library hooks don't exist: " . join(', ', $invalidHooks));
        }
        $this->enabledLibraryHooks = $hooks;

        return $this;
    }

    public function getStorage()
    {
        return $this->availableStorages[$this->enabledStorage];
    }

    public function getRequestMatchers()
    {
        if (is_null($this->enabledRequestMatchers)) {
            return array_values($this->availableRequestMatchers);
        }

        return array_values(array_intersect_key(
            $this->availableRequestMatchers,
            array_flip($this->enabledRequestMatchers)
        ));
    }

    public function addRequestMatcher($name, $callback)
    {
        Assertion::minLength($name, 1, "A request matchers name must be at least one character long. Found '{$name}'");
        Assertion::isCallable($callback, "Request matcher '{$name}' is not callable.");
        $this->availableRequestMatchers[$name] = $callback;

        return $this;
    }

    public function enableRequestMatchers(array $matchers)
    {
        $invalidMatchers = array_diff($matchers, array_keys($this->availableRequestMatchers));
        if ($invalidMatchers) {
            throw new \InvalidArgumentException("Request matchers don't exist: " . join(', ', $invalidMatchers));
        }
        $this->enabledRequestMatchers = $matchers;
    }

    public function setStorage($storageName)
    {
        Assertion::keyExists($this->availableStorages, $storageName, "Storage '{$storageName}' not available.");
        $this->enabledStorage = $storageName;

        return $this;
    }

    /**
     * Provides a former defined class paths white list.
     * @return array
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }

    /**
     * Defines a set of relative class file paths.
     *
     * @param string|array $paths Set of relative paths to a class file the class should be
     * @return $this
     */
    public function setWhiteList($paths)
    {
        $paths = (is_array($paths)) ? $paths : array($paths);

        $this->whiteList = $paths;

        return $this;
    }
}
