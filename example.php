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
    App::init('example_app_config.xml');

    // creating new learner with ukrainian native language
    $learner = App::getSingleton('Learner', ['ukrainian']);

    $learner->learnLanguages(['english', 'german', 'russian']);
}
catch (\Litvinenko\Common\App\Exception $e)
{
    echo "App exception: " . $e->getMessage();
}