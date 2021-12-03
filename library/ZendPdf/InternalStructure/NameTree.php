<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Pdf
 */

namespace ZendPdf\InternalStructure;

use ArrayAccess;
use Countable;
use Iterator;
use ZendPdf as Pdf;
use ZendPdf\Exception;
use ZendPdf\InternalType;

/**
 * PDF name tree representation class
 *
 * @todo implement lazy resource loading so resources will be really loaded at access time
 *
 * @package    Zend_PDF
 * @subpackage Zend_PDF_Internal
 */
class NameTree implements ArrayAccess, Countable, Iterator
{
    /**
     * Elements
     * Array of name => object tree entries
     *
     * @var array
     */
    protected array $_items = array();

    /**
     * Object constructor
     *
     * @param $rootDictionary root of name dictionary
     * @throws \ZendPdf\Exception\ExceptionInterface
     */
    public function __construct(InternalType\AbstractTypeObject $rootDictionary)
    {
        if ($rootDictionary->getType() != InternalType\AbstractTypeObject::TYPE_DICTIONARY) {
            throw new Exception\CorruptedPdfException('Name tree root must be a dictionary.');
        }

        $intermediateNodes = array();
        $leafNodes         = array();
        if ($rootDictionary->Kids !== null) {
            $intermediateNodes[] = $rootDictionary;
        } else {
            $leafNodes[] = $rootDictionary;
        }

        while (count($intermediateNodes) != 0) {
            $newIntermediateNodes = array();
            foreach ($intermediateNodes as $node) {
                foreach ($node->Kids->items as $childNode) {
                    if ($childNode->Kids !== null) {
                        $newIntermediateNodes[] = $childNode;
                    } else {
                        $leafNodes[] = $childNode;
                    }
                }
            }
            $intermediateNodes = $newIntermediateNodes;
        }

        foreach ($leafNodes as $leafNode) {
            $destinationsCount = count($leafNode->Names->items)/2;
            for ($count = 0; $count < $destinationsCount; $count++) {
                $this->_items[$leafNode->Names->items[$count*2]->value] = $leafNode->Names->items[$count*2 + 1];
            }
        }
    }

    public function current(): mixed
    {
        return current($this->_items);
    }


    public function next(): void
    {
        next($this->_items);
    }


    public function key(): string
    {
        return key($this->_items);
    }


    public function valid(): bool
    {
        return current($this->_items)!==false;
    }


    public function rewind(): void
    {
        reset($this->_items);
    }


    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->_items);
    }


    public function offsetGet($offset): mixed
    {
        return $this->_items[$offset];
    }


    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->_items[]        = $value;
        } else {
            $this->_items[$offset] = $value;
        }
    }


    public function offsetUnset($offset): void
    {
        unset($this->_items[$offset]);
    }


    public function clear()
    {
        $this->_items = array();
    }

    public function count(): int
    {
        return count($this->_items);
    }
}
