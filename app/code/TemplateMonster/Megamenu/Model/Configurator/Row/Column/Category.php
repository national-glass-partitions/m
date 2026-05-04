<?php
namespace TemplateMonster\Megamenu\Model\Configurator\Row\Column;

class Category extends Entity
{
    public $rendererClass = 'Category';

    private $_subCategory;

    public function __construct(
        array $data = []
    ) {
        parent::__construct($data);
    }


    public function getCategory()
    {
        if (!$this->_subCategory) {
            $nodes = $this->getNode()->getAllChildNodes();
            if(array_key_exists('category-node-' . $this->getValue(), $nodes)) {
                $this->_subCategory = $nodes['category-node-' . $this->getValue()];
            }
        }
        return $this->_subCategory;
    }

}