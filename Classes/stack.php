<?php


/**
 * This function simulates the "Stack" data structure found in most languages.
 *
 * It works by pushing data onto the end of an array, and poping data only from
 * the end of such array.  It does not use array_push or array_pop, just its own
 * pointer.  It also is able to search a stack for a specific data.  It starts at the
 * beginning of the stack (first item pushed), and returns the key position, closest to
 * the top of the stack.
 *
 * It also supports other functionality, such as "Peek".  Which allows you to see the top
 * item in the stack, without actually poping it.  Peeking an empty stack will return null
 *
 * If you pop an empty stack, null will be returned.
 *
 * Creation Date: 07/22/2007
 * Last Updated:  07/22/2007
 * Author: Brian Price (http://www.brianprice.ca)
 * 
 * License:
 * 	See license.txt
 *  This product is provided without warranty.  Use at your own risk.
 *
 * Support:
 *  Please report bugs to http://bugs.brianprice.ca
 */

class Stack {

	private $stackArray  	=  array();		// This is where the data is stored
	private $size 			=  0;			// Double purpose.  Keeps track of top of stack and total size


	/**
	 * Constructor.  Doesn't really need to be called, but may be useful later
	 */
	function Stack() {
		$this->size 	= 0;
	}
	
	/**
	 * This method determines if the array has any elements in it
	 *
	 * @return 	boolean 	True is returned if Stack is empty
	 */
	function is_empty() {
	
		// If Stack is empty, return true
		if ($this->size == 0) {
			return true;
		}
		
		// Otherwise return false
		return false;
	}

	/**
	 * This method will add data onto the stack
	 *
	 * @param 	Data 	This is the data we are pushing onto the stack
	 *
	 * @return 	void
	 */
	function push($data) {
	
		// Push the data onto the stack.  Increment the top
		$this->stackArray[$this->size] = $data;
		$this->size++;
	}
	
	/**
	 * This method will grab the topmost data off the stack
	 *
	 * @return	Data 	The data which is on the top of the stack
	 * 					Null is returned if stack is empty.
	 */
	function pop() {
	
		// If stack is empty, return null
		if ($this->size == 0) {
			return null;
		}
	
		// We're removing the item from the stack, move size back one.
		$this->size--;
		
		// Return the data at the top of the stack
		$dataToReturn = $this->stackArray[$this->size];
		return $dataToReturn;
	
	}
	
	/**
	 * This method will peek at the top most position without removing
	 * it from the stack.  It also allows for an optional "Position" param.
	 * If one is specified, we will look at that position, otherwise we look
	 * at the top of the stack.
	 *
	 * @param 	Position 	The position on the stack we want to look at.
	 * 						Optional.  If left blank, will look at top.
	 *
	 * @return 	Data 		The data at this position
	 */
	function peek($position = -1) {
	
		// If size is 0, return null
		if ($this->size == 0) {
			return null;
		}
		
		// If no position given, set to top of stack
		if ($position == -1) {
			$position = $this->size - 1;
		}
	
		// Get the data, and return it
		$dataToReturn = $this->stackArray[$position];
		return $dataToReturn;
	}
	
	/**
	 * This method will search for the supplied data within
	 * the stack.  It will return the position of the data
	 * within the stack, starting at 0.  -1 is returned if
	 * data is not found within the stack.
	 *
	 * If the data appears more than once in the stack, the latest
	 * position will be returned.
	 *
	 * @param 	Data 		The data we're looking for within the stack
	 *
	 * @return 	Integer		The location of the data in the stack.
	 * 						-1 is returned if was not found.
	 */
	function search($data) {
	
		// Initialize at -1
		$position = -1;
		
		// Iterate through the stack
		foreach ($stackArray as $key => $value) {
		
			// If we find it, set position to the KEY (position)
			if ($value == $data) {
				$position = $key;
			}
		}
		
		return $position;
	}
	
	/**
	 * This method will return the size of the stack
	 *
	 * @return 	Integer 	The number of items contained in the stack.
	 */
	function size() {
		return $this->size;
	}

	/**
	 * Copie the stack into a new one
	 * @return Stack a copie of the current stack
	 */
	function copy(){
		$newStack= new Stack();

		for($i=0; $i < $this->size; $i++)
			$newStack->push($this->stackArray[$i]);

		return $newStack;
	}

}







?>
