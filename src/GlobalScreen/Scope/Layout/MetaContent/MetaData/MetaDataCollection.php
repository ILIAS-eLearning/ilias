<?php namespace ILIAS\GlobalScreen\Scope\Layout\MetaContent\MetaData;

use Iterator;

/**
 * Class MetaDataCollection
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class MetaDataCollection
{
    /**
     * @var MetaDatum[]
     */
    protected array $items = [];
    
    public function add(MetaDatum $meta_datum) : void
    {
        $this->items[] = $meta_datum;
    }
    
    public function clear()
    {
        $this->items = [];
    }
    
    /**
     * @return Iterator|MetaDatum[]
     */
    public function getItems() : Iterator
    {
        yield from $this->items;
    }
    
    /**
     * @return array
     */
    public function getItemsAsKeyValuePairs() : array
    {
        $key_value_pairs = [];
        array_walk($this->items, function (MetaDatum $d) use (&$key_value_pairs) {
            $key_value_pairs[$d->getKey()] = $d->getValue();
        });
        return $key_value_pairs;
    }
}
