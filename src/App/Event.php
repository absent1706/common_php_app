<?php
namespace Litvinenko\Common\App;

use Litvinenko\Common\Object;

/**
 * Simple event class
 *
 * This class have 'name' property which allows to prevent conflicts with 'name' param in its data
 * This means that there can exist event param called 'name' and it won't conflice with actual event name
 */
class Event extends \Litvinenko\Common\Object
{
    protected $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
