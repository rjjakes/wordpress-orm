<?php

namespace Symlink\ORM\Collections;

// http://fabien.potencier.org/iterator-or-iteratoraggregate.html

class RelatedCollection implements \Countable, \IteratorAggregate, \ArrayAccess {

  private $list;

  public function __construct() {
    $list = [];
  }

  public function count() {
    return count($this->list);
  }

  public function getIterator()
  {
    return new \ArrayIterator($this->list);
  }

  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->list[] = $value;
    } else {
      $this->list[$offset] = $value;
    }
  }

  public function offsetExists($offset) {
    return isset($this->list[$offset]);
  }

  public function offsetUnset($offset) {
    unset($this->list[$offset]);
  }

  public function offsetGet($offset) {
    return isset($this->list[$offset]) ? $this->list[$offset] : null;
  }

}
