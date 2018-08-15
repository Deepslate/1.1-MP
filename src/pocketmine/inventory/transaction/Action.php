<?php

/*
 *
 * PM-1.1: The PMMP fork to support MCPE 1.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Tee7even
 *
*/

declare(strict_types=1);

namespace pocketmine\inventory\transaction;

use pocketmine\item\Item;
use pocketmine\Player;

abstract class Action{
	/** @var Item[] */
	protected $sourceItems;
	/** @var Item[] */
	protected $targetItems;
	/** @var float */
	protected $creationTime;

	/**
	 * @param Item|Item[] $sourceItems
	 * @param Item|Item[] $targetItems
	 */
	public function __construct($sourceItems, $targetItems){
		self::checkIfItemArray($sourceItems);
		self::checkIfItemArray($targetItems);

		$this->sourceItems = $sourceItems;
		$this->targetItems = $targetItems;
		$this->creationTime = microtime(true);
	}

	/**
	 * Ensures that the argument is an {@see Item} array.
	 * Because PHP can't haz strictly typed arrays and method overloading.
	 * @param Item|Item[] $items
	 */
	private static function checkIfItemArray(&$items){
		if(!is_array($items)){
			$items = [$items];
		}

		foreach($items as $item){
			if(!$item instanceof Item){
				throw new \InvalidArgumentException("Expected " . Item::class . ", got " .
					(is_object($item) ? get_class($item) : gettype($item)));
			}
		}
	}

	/**
	 * @return Item[]
	 */
	public function getSourceItems() : array{
		$sourceItems = [];
		foreach($this->sourceItems as $sourceItem){
			$sourceItems[] = clone $sourceItem;
		}
		return $sourceItems;
	}

	/**
	 * @return Item[]
	 */
	public function getTargetItems() : array{
		$targetItems = [];
		foreach($this->targetItems as $targetItem){
			$targetItems[] = clone $targetItem;
		}
		return $targetItems;
	}

	/**
	 * @return float
	 */
	public function getCreationTime() : float{
		return $this->creationTime;
	}

	/**
	 * @param Transaction $transaction
	 * @return bool
	 */
	abstract public function isValid(Transaction $transaction) : bool;

	/**
	 * @param Transaction $transaction
	 */
	public function onAddToTransaction(Transaction $transaction) : void{

	}

	/**
	 * @param Player $source
	 * @return bool
	 */
	public function onPreExecute(/** @noinspection PhpUnusedParameterInspection */ Player $source) : bool{
		return true;
	}

	/**
	 * @param Player $source
	 * @return bool
	 */
	abstract public function execute(Player $source) : bool;

	/**
	 * @param Player $source
	 */
	public function onExecuteSuccess(Player $source) : void{

	}

	/**
	 * @param Player $source
	 */
	public function onExecuteFail(Player $source) : void{

	}
}
