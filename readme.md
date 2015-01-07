#General-purpose App class

It can:
 * dispatch events in Magento style (see http://www.solvingmagento.com/event-driven-architecture-in-magento-observer-pattern/ or http://codegento.com/2011/04/observers-and-dispatching-events/)
 * create singletones in Magento style (see http://inchoo.net/magento/making-use-of-magento-getsingleton-method/)

 This class uses Magento-like XML config files (see http://www.solvingmagento.com/event-driven-architecture-in-magento-observer-pattern/).

##Installation
 Just install it throw composer. Create composer.json file in your app directory, fill it with
```json
{
    "require":
    {
        "litvinenko/app": "*"
    }
}
```
and run
```
composer install
```

##To init App
Just run \Litvinenko\Common\App::init() method and pass to it path to your config file (default path is 'config.xml'). This will register all events and observers.

XML config file for this app is very similar to Magento XML config files (see http://www.solvingmagento.com/event-driven-architecture-in-magento-observer-pattern/).

It should look like:
```xml
<?xml version="1.0"?>
<config> <!-- root element. don't care about it-->
    <events> <!-- (optional) element containing all event info -->
        <some_event_name> <!-- event name. first param for App:dispatchEvent method -->
            <observers> <!-- all observers for current event -->
                <some_observer_unique_identifier> <!-- unique observer name -->
                    <class>MyClass</class> <!-- observer class -->
                    <method>myMethod</method> <!-- observer method to be called -->
                </some_observer_unique_identifier>
                <second_observer> <!-- second observer -->
                    <class>MyOtherClass</class>
                    <method>myOtherMethod</method>
                    <singleton>1</singleton> <!-- (optional) this param (0 or 1, true or false) tells app that observer object should be obtained with getSingleton method -->
                </second_observer>
            </observers>
        </some_event_name>
    </events>
    <developer_mode>0</developer_mode> <!-- (optional) this param (0 or 1, true or false) tells app that we are in developer mode-->
</config>
```

##To dispatch events using App:
 firstly, init app:
```php
\Litvinenko\Common\App::init()
 ```
 then, in any place you need paste:
```php
App::dispatchEvent('event_name', array('param1' => $value1, 'param2' => $value2, ...));
```
 For example,
```php
App::dispatchEvent('language_learned', array('language' => $language, 'learner' => $this));
```

##Working example:

XML file ('example_config.xml')
```xml
<?xml version="1.0"?>
<config> <!-- root element. don't care about it-->
    <events> <!-- element containing all event info -->
        <language_learned> <!-- event name. first param for App:dispatchEvent method -->
            <observers> <!-- all observers for current event -->
                <logger_observer> <!-- unique observer name -->
                    <class>Logger</class> <!-- observer class -->
                    <method>logLearnedLanguage</method> <!-- observer method to be called -->
                </logger_observer>
                <learner_observer> <!-- seconde observer -->
                    <class>Learner</class>
                    <method>printLearnedLanguages</method>
                    <singleton>1</singleton> <!-- this param (0 or 1, true or false) tells app that observer object should be obtained with getSingleton method -->
                </learner_observer>
            </observers>
        </language_learned>
    </events>
    <developer_mode>0</developer_mode> <!-- this param (0 or 1, true or false) tells app that we are in developer mode-->
</config>
```

PHP file
```php
<?php
require 'vendor/autoload.php';

use Litvinenko\Common\App;

/**
 * Demo class 'Learner'.
 * When learner learns languages, he tells App 'hey, I learned new language' (App::dispatchEvent('language_learned'...)
 *
 * Then, app dispatches this event to all registered observers
 */
class Learner
{
    protected $learnedLanguages = [];

    public function __construct($nativeLanguage)
    {
        echo "Learner says: I know {$nativeLanguage}\n\n";
        $this->learnedLanguages[] = $nativeLanguage;
    }

    public function learnLanguages(array $languages)
    {
        foreach ($languages as $language)
        {
            // ....
            // learning language ...
            $this->learnedLanguages[] = $language;
            // ....
            App::dispatchEvent('language_learned', ['language' => $language, 'learner' => $this]);
        }
    }

    /**
     * If this method is declared as SINGLETON observer for 'language_learned' event
     *    and  Learner was created with getSingleton app method,
     * then app will NOT initiate new Learner but will dispatch event to already existing learner, i.e. the same object will fire and handle event
     *
     * @param  \Litvinenko\Common\App\Event $event
     */
    public function printLearnedLanguages($event)
    {
        echo "Learner says: I know " . implode(', ', $this->learnedLanguages) . "\n\n";
    }
}

class Logger
{
    /**
     * Function for demonstrating event dispatching mechanism
     *
     * @param  \Litvinenko\Common\App\Event $event
     */
    public function logLearnedLanguage($event)
    {
        echo "Logger  says: Hey, someone learned ". $event->getLanguage() . " language!\n";
    }
}

try
{
    // init app with custom config file
    App::init('example_config.xml');

    // creating new learner with ukrainian native language
    $learner = App::getSingleton('Learner', ['ukrainian']);

    $learner->learnLanguages(['english', 'german', 'russian']);
}
catch (\Litvinenko\Common\App\Exception $e)
{
    echo "App exception: " . $e->getMessage();
}
```
