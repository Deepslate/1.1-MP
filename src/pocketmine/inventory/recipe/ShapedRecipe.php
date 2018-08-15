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
use pocketmine\item\ItemFactory;

class ShapedRecipe extends CraftingRecipe{
	/** @var Item[] char => Item map */
	private $ingredients = [];
	/** @var Item[] */
	private $results = [];

	/** @var int */
	private $height;
	/** @var int */
	private $width;

	/**
	 * ShapedRecipe constructor.
	 * @param Item[][] $ingredients
	 * @param Item[] $results
	 */
	public function __construct(array $ingredients, array $results){
		$this->height = count($ingredients);
		if($this->height > 3 or $this->height < 1){
			throw new \InvalidArgumentException("Shaped recipes may only have 1, 2 or 3 rows, not $this->height");
		}

		$this->width = count($ingredients[0]);
		if($this->width > 3 or $this->width < 1){
			throw new \InvalidArgumentException("Shaped recipes may only have 1, 2 or 3 columns, not $this->width");
		}

		foreach($ingredients as $y => $row){
			if(count($row) !== $this->width){
				throw new \InvalidArgumentException("Shaped recipe rows must all have the same length (expected $this->width, got " . count($row) . ")");
			}

			foreach($row as $x => $ingredient){
				$this->ingredients[$y][$x] = clone $ingredient;
			}
		}

		$this->results = array_map(function(Item $item) : Item{ return clone $item; }, $results);
	}

	/**
	 * @return int
	 */
	public function getHeight() : int{
		return $this->height;
	}

	/**
	 * @return int
	 */
	public function getWidth() : int{
		return $this->width;
	}

	/**
	 * @return Item[]
	 */
	public function getIngredientList() : array{
		$ingredients = [];

		for($y = 0; $y < $this->height; $y++){
			for($x = 0; $x < $this->width; $x++){
				$ingredient = $this->getIngredient($x, $y);
				if(!$ingredient->isNull()){
					$ingredients[] = $ingredient;
				}
			}
		}

		return $ingredients;
	}

	/**
	 * @return Item[][]
	 */
	public function getIngredientMap() : array{
		$ingredients = [];

		for($y = 0; $y < $this->height; $y++){
			for($x = 0; $x < $this->width; $x++){
				$ingredients[$y][$x] = $this->getIngredient($x, $y);
			}
		}

		return $ingredients;
	}

	/**
	 * @param int $x
	 * @param int $y
	 *
	 * @return Item
	 */
	public function getIngredient(int $x, int $y) : Item{
		$ingredient = $this->ingredients[$y][$x] ?? null;
		return $ingredient !== null ? clone $ingredient : ItemFactory::get(Item::AIR, 0, 0);
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
		$manager->registerShapedRecipe($this);
	}
}
