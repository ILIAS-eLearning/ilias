<?php namespace ILIAS\GlobalScreen\Scope\Layout\MetaContent\Media;

/**
 * Class CssCollection
 *
 * @package ILIAS\Services\UICore\Page\Media
 */
class CssCollection extends AbstractCollection
{

    /**
     * @param Css $item
     */
    public function addItem(Css $item)
    {
        $this->items[] = $item;
    }


    /**
     * @return Css[]
     */
    public function getItemsInOrderOfDelivery() : array
    {
        return parent::getItemsInOrderOfDelivery();
    }
}
