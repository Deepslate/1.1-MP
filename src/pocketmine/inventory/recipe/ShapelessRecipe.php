<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\inventory\recipe;

use pocketmine\item\Item;

class ShapelessRecipe extends CraftingRecipe{
	/** @var Item[] */
	private $ingredients = [];
	/** @var Item[] */
	private $results = [];

	/**
	 * ShapelessRecipe constructor.
	 * @param array $ingredients
	 * @param array $results
	 */
	public function __construct(array $ingredients, array $results){
		foreach($ingredients as $ingredient){
			$this->addIngredient($ingredient);
		}

		$this->results = array_map(function(Item $item) : Item{ return clone $item; }, $results);
	}

	/**
	 * @param Item $item
	 *
	 * @return ShapelessRecipe
	 *
	 * @throws \InvalidArgumentException
	 */
	public function addIngredient(Item $item) : ShapelessRecipe{
		if(count($this->ingredients) + $item->getCount() > 9){
			throw new \InvalidArgumentException("Shapeless recipes cannot have more than 9 ingredients");
		}

		while($item->getCount() > 0){
			$this->ingredients[] = $item->pop();
		}

		return $this;
	}

	/**
	 * @param Item $item
	 *
	 * @return $this
	 */
	public function removeIngredient(Item $item){
		foreach($this->ingredients as $index => $ingredient){
			if($item->getCount() <= 0){
				break;
			}

			if($ingredient->equals($item, !$item->hasAnyDamageValue(), $item->hasCompoundTag())){
				unset($this->ingredients[$index]);
				$item->pop();
			}
		}

		return $this;
	}

	/**
	 * @return Item[]
	 */
	public function getIngredientList() : array{
		return array_map(function(Item $item) : Item{ return clone $item; }, $this->ingredients);
	}

	/**
	 * @return int
	 */
	public function getIngredientCount() : int{
		$count = 0;
		foreach($this->ingredients as $ingredient){
			$count += $ingredient->getCount();
		}
		return $count;
	}

	/**
	 * Returns a list of items created by crafting this recipe.
	 *
	 * @return Item[]
	 */
	public function getResults() : array{
		return array_map(function(Item $item) : Item{ return clone $item; }, $this->results);
	}

	/**
	 * @param CraftingManager $manager
	 */
	public function registerToCraftingManager(CraftingManager $manager) : void{
		$manager->registerShapelessRecipe($this);
	}
}
