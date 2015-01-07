<?php
namespace Litvinenko\Common;

/**
 * General-purpose App class that can:
 *  - dispatch events in Magento style (see http://www.solvingmagento.com/event-driven-architecture-in-magento-observer-pattern/ or http://codegento.com/2011/04/observers-and-dispatching-events/)
 *  - create singletones in Magento style (see http://inchoo.net/magento/making-use-of-magento-getsingleton-method/)
 *
 *  This class uses Magento-like XML config files (see http://www.solvingmagento.com/event-driven-architecture-in-magento-observer-pattern/).
 *
 *  --------------------------- To init App: ---------------------
 * Just run \Litvinenko\Common\App::init() method and pass to it path to your config file (default path is 'config.xml'). This will register all events and observers
 * XML config file for this app is very similar to Magento XML config files (see http://www.solvingmagento.com/event-driven-architecture-in-magento-observer-pattern/).
 * It should look like:
 *
 *     <?xml version="1.0"?>
 *     <config> <!-- root element. don't care about it-->
 *         <events> <!-- (optional) element containing all event info -->
 *             <some_event_name> <!-- event name. first param for App:dispatchEvent method -->
 *                 <observers> <!-- all observers for current event -->
 *                     <some_observer_unique_identifier> <!-- unique observer name -->
 *                         <class>MyClass</class> <!-- observer class -->
 *                         <method>myMethod</method> <!-- observer method to be called -->
 *                     </some_observer_unique_identifier>
 *                     <second_observer> <!-- second observer -->
 *                         <class>MyOtherClass</class>
 *                         <method>myOtherMethod</method>
 *                         <singleton>1</singleton> <!-- (optional) this param (0 or 1, true or false) tells app that observer object should be obtained with getSingleton method -->
 *                     </second_observer>
 *                 </observers>
 *             </some_event_name>
 *         </events>
 *         <developer_mode>0</developer_mode> <!-- (optional) this param (0 or 1, true or false) tells app that we are in developer mode-->
 *     </config>
 *
 *  --------------------------- To dispatch events using App: ---------------------
 *  firstly, init app: \Litvinenko\Common\App::init()
 *  then, in any place you need paste next code: App::dispatchEvent('event_name', array('param1' => $value1, 'param2' => $value2, ...));
 *
 *  for example, App::dispatchEvent('language_learned', array('language' => $language, 'learner' => $this));
 */
class App
{
    /**
     * Path to XML config file
     *
     * @var string
     */
    protected static $configFile;

    /**
     * Whole config read from configFile
     *
     * @var SimpleXMLElement
     */
    protected static $config;

    /**
     * Option taken from configFile. Says, whether developer mode is enabled
     *
     * @var bool
     */
    protected static $developerMode = false;

    /**
     * Events and event observers(listeners) taken from configFile
     *
     * @var array
     */
    protected static $events;

    /**
     * All singletons created with getSingleton method
     *
     * @var array
     */
    protected static $singletons;

    /**
     * Initializes app: reads config from config file
     *
     * @param type $configFile string
     */
    public static function init($configFile = 'config.xml')
    {
        self::$configFile = $configFile;
        if (file_exists(self::$configFile))
        {
            if ($config = simplexml_load_file($configFile))
            {
                self::$config = $config;
                self::parseConfig(self::$config);
            }
            else
            {
                throw new App\Exception("Config file '{$configFile}' does not contain valid XML");
            }
        }
        else
        {
            throw new App\Exception("Config file '{$configFile}' does not exist");
        }
    }

    /**
     * Returns TRUE if app is in developer mode
     *
     * @return bool
     */
    public static function getIsDeveloperMode()
    {
        return self::$developerMode;
    }

    /**
     * Parses config from config file
     *
     * @param SimpleXMLElement $config
     */
    protected static function parseConfig($config)
    {
        self::parseEventConfig($config);

        self::$developerMode = self::getBooleanNodeValue($config->developer_mode);
    }

    public static function getBooleanNodeValue($node)
    {
        return (!is_null($node) && in_array($node, ['1', 'true']));
    }

    /**
     * Initializes all events from given config
     *
     * @param SimpleXMLElement $config
     */
    protected static function parseEventConfig($config)
    {
        self::$events = [];

        foreach ($config->events->children() as $eventName => $eventConfig)
        {
            $event = ['observers' => []];
            foreach ($eventConfig->observers->children() as $obsName => $obsConfig)
            {
                $event['observers'][$obsName] = [
                    'class'     => (string)$obsConfig->class,
                    'method'    => (string)$obsConfig->method,
                    'singleton' => self::getBooleanNodeValue($obsConfig->singleton)
                ];
            }

            self::$events[$eventName] = $event;
        }
    }

    /**
     * Creates object of given class
     *
     * @param  string $class class name
     * @param  array  $constructorArguments arguments for class constructor (any number of arguments can be used)
     *
     * @return object
     */
    public static function getModel($class, $constructorArguments = array())
    {
        if (class_exists($class))
        {
            // creates new class with variable number of arguments thrown to constructor
            return (new \ReflectionClass($class))->newInstanceArgs($constructorArguments);
        }
        else
        {
            throw new App\Exception("Class {$class} doesn't exist");
        }

    }

    /**
     * Returns previously created (with this method) object of given class.
     * If object is not registered yet, creates object and registers it.
     *
     * Analogue of Magento Mage::getSingleton method (see http://inchoo.net/magento/making-use-of-magento-getsingleton-method/)
     *
     * @param  string $class class name
     * @param  array  $constructorArguments arguments for class constructor (any number of arguments can be used)
     *
     * @return object
     */
    public static function getSingleton($class, $constructorArguments = array())
    {
        if (!isset(self::$singletons[$class]))
        {
            self::$singletons[$class] = self::getModel($class, $constructorArguments);
        }

        return self::$singletons[$class];
    }

    /**
     * Dispatches events to all registered observers.
     * If observer is singleton, obtains it with getSingleton method
     *
     * Simplified analogue of Magento Mage::dispatchEvent method
     *
     * @param  string $eventName
     * @param  array  $eventData data to be set to event (Event is assumed to be instance of Litvinenko\Common\Object)
     */
    public function dispatchEvent($eventName, array $eventData = array())
    {
       if (isset(self::$events[$eventName]))
       {
            $eventConfig = self::$events[$eventName];
            foreach ($eventConfig['observers'] as $observer)
            {
                $class    = $observer['class'];
                $method   = $observer['method'];
                $observer = (isset($observer['singleton']) && ($observer['singleton'])) ? self::getSingleton($class) : self::getModel($class);

                $event = new App\Event($eventData);
                $event->setName($eventName);
                if (method_exists($observer, $method))
                {
                    $observer->$method($event);
                }
                elseif (self::getIsDeveloperMode())
                {
                    throw new App\Exception('Method "'.$method.'" is not defined in "'.get_class($observer).'"');
                }
            }
        }
    }
}
