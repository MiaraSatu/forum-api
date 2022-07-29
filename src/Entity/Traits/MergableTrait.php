<?php

namespace App\Entity\Traits;

trait MergableTrait {
	public function mergeWith(Object $other, array $mergables = []): self {
		// if other has same type than this
		if(get_class($this) != get_class($other))
			return $this;
		foreach($mergables as $mergable) {
			$setMethod = 'set'.ucfirst($mergable);
            $getMethod = 'get'.ucfirst($mergable);
            // if this has set method -> other has get method
            if(method_exists($this, $setMethod)) {
            	// if other mergable field is not null
                if($newValue = $other->$getMethod()) {
            		// update if value are different
                	if($this->$getMethod() != $newValue)
                	$this->$setMethod($newValue);
                }
            }
		}
		return $this;
	}
}