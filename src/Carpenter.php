<?php

namespace Michaeljennings\Carpenter;

use Closure;
use Michaeljennings\Carpenter\View\ViewManager;
use Michaeljennings\Carpenter\Store\StoreManager;
use Michaeljennings\Carpenter\Session\SessionManager;
use Michaeljennings\Carpenter\Pagination\PaginationManager;
use Michaeljennings\Carpenter\Exceptions\TableLocationNotFound;
use Michaeljennings\Carpenter\Exceptions\CarpenterCollectionException;
use Michaeljennings\Carpenter\Contracts\Carpenter as CarpenterInterface;

class Carpenter implements CarpenterInterface
{
    /**
     * A collection of table closures
     *
     * @var array
     */
    protected $collection = [];

    /**
     * The manager extensions.
     *
     * @var array
     */
    protected $extensions = [
        'store' => [],
        'session' => [],
        'view' => [],
        'paginator' => [],
    ];

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Add a table closure into the table collection
     *
     * @param string          $name
     * @param callable|string $table
     */
    public function add($name, $table)
    {
        $this->collection[$name] = $table;
    }

    /**
     * Retrieve a table from the table collection and then create the table.
     * Optionally can pass a callback to be run on the table after it is
     * created.
     *
     * @param  string       $name
     * @param bool|callable $callback
     * @return Table
     * @throws CarpenterCollectionException
     */
    public function get($name, $callback = false)
    {
        if ( ! array_key_exists($name, $this->collection)) {
            throw new CarpenterCollectionException("No table was found with the name '{$name}'");
        }

        $tableCallback = $this->collection[$name];

        if (is_string($tableCallback)) {
            $tableCallback = $this->buildClassCallback($tableCallback);
        }

        if ( ! $callback) {
            return $this->buildTable($name, $tableCallback);
        }

        $table = $this->buildTable($name, $tableCallback);
        $callback($table);

        return $table;
    }

    /**
     * Create a new table without using the table collection.
     *
     * @param string   $name
     * @param callable $callback
     * @return Table
     */
    public function make($name, Closure $callback)
    {
        return $this->buildTable($name, $callback);
    }

    /**
     * Run the callback on the a table instance and then return the table.
     *
     * @param string  $name
     * @param Closure $callback
     * @return Table
     */
    protected function buildTable($name, Closure $callback)
    {
        list($store, $session, $view, $paginator) = $this->createManagers();

        $table = new Table($name, $store, $session, $view, $paginator, $this->config);
        $callback($table);

        return $table;
    }

    /**
     * Build a new callback from a class for the class based tables.
     *
     * @param string $callback
     * @return callable
     */
    protected function buildClassCallback($callback)
    {
        list($class, $method) = $this->parseClassCallback($callback);

        return function () use ($class, $method) {
            $callable = [new $class, $method];

            return call_user_func_array($callable, func_get_args());
        };
    }

    /**
     * Parse the class based table name to the class name and method.
     *
     * @param string $class
     * @return array
     */
    protected function parseClassCallback($class)
    {
        if (strpos($class, '@') !== false) {
            return explode('@', $class);
        }

        return [$class, 'build'];
    }

    /**
     * Set a manager extension.
     *
     * @param string         $manager
     * @param string         $key
     * @param string|Closure $extension
     * @return $this
     */
    public function extend($manager, $key, $extension)
    {
        $this->extensions[$manager][$key] = $extension;

        return $this;
    }

    /**
     * Create the carpenter drivers and return them as an array to be used with
     * the php list method.
     *
     * @return array
     */
    protected function createManagers()
    {
        return [
            $this->createStoreManager(),
            $this->createSessionManager(),
            $this->createViewManager(),
            $this->createPaginationManager()
        ];
    }

    /**
     * Create an instance of the store manager.
     *
     * @return StoreManager
     */
    protected function createStoreManager()
    {
        return new StoreManager($this->config['store'], $this->extensions['store']);
    }

    /**
     * Create an instance of the session manager.
     *
     * @return SessionManager
     */
    protected function createSessionManager()
    {
        return new SessionManager($this->config['session'], $this->extensions['session']);
    }

    /**
     * Create an instance of the view manager.
     *
     * @return ViewManager
     */
    protected function createViewManager()
    {
        return new ViewManager($this->config['view'], $this->extensions['view']);
    }

    /**
     * Create an instance of the pagination manager.
     *
     * @return PaginationManager
     */
    protected function createPaginationManager()
    {
        return new PaginationManager($this->config['paginator'], $this->extensions['paginator']);
    }

    /**
     * Load all of the table instances from the tables file.
     *
     * @throws TableLocationNotFound
     *
     * @depreciated This function is required if you are storing tables in a file, not
     * in service providers.
     *
     * @codeCoverageIgnore
     */
    public function loadTables()
    {
        $tableLocations = $this->config['tables']['location'];

        if (file_exists($tableLocations)) {
            require_once $tableLocations;
        } else {
            throw new TableLocationNotFound("No file found for the path '{$tableLocations}'");
        }
    }
}