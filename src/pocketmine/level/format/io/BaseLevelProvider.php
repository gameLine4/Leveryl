<?php

/*
 *
 *  ____			_		_   __  __ _				  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___	  |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|	 |_|  |_|_|
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

declare(strict_types = 1);

namespace pocketmine\level\format\io;

use pocketmine\level\format\Chunk;
use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\scheduler\AsyncTask;

abstract class BaseLevelProvider implements LevelProvider
{
	/** @var Level */
	protected $level;
	/** @var string */
	protected $path;
	/** @var CompoundTag */
	protected $levelData;

	public function __construct(Level $level, string $path)
	{
		$this->level = $level;
		$this->path = $path;
		if(!file_exists($this->path)) {
			mkdir($this->path, 0777, true);
		}
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->readCompressed(file_get_contents($this->getPath() . "level.dat"));
		$levelData = $nbt->getData();
		if($levelData->Data instanceof CompoundTag) {
			$this->levelData = $levelData->Data;
		} else {
			throw new LevelException("Invalid level.dat");
		}

		if(!isset($this->levelData->generatorName)) {
			$this->levelData->generatorName = new StringTag("generatorName", (string)Generator::getGenerator("DEFAULT"));
		}

		if(!isset($this->levelData->generatorOptions)) {
			$this->levelData->generatorOptions = new StringTag("generatorOptions", "");
		}
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getServer()
	{
		return $this->level->getServer();
	}

	public function getLevel()
	{
		return $this->level;
	}

	public function getName(): string
	{
		return (string)$this->levelData["LevelName"];
	}

	public function getTime() : int
	{
		return intval($this->levelData["Time"]);
	}

	public function setTime(int $value)
	{
		$this->levelData->Time = new LongTag("Time", $value);
	}

	public function getSeed() : int
	{
		return intval($this->levelData["RandomSeed"]);
	}

	public function setSeed(int $value)
	{
		$this->levelData->RandomSeed = new LongTag("RandomSeed", $value);
	}

	public function getSpawn(): Vector3
	{
		return new Vector3((float)$this->levelData["SpawnX"], (float)$this->levelData["SpawnY"], (float)$this->levelData["SpawnZ"]);
	}

	public function setSpawn(Vector3 $pos)
	{
		$this->levelData->SpawnX = new IntTag("SpawnX", (int)$pos->x);
		$this->levelData->SpawnY = new IntTag("SpawnY", (int)$pos->y);
		$this->levelData->SpawnZ = new IntTag("SpawnZ", (int)$pos->z);
	}

	public function doGarbageCollection()
	{

	}

	/**
	 * @return CompoundTag
	 */
	public function getLevelData(): CompoundTag
	{
		return $this->levelData;
	}

	public function saveLevelData()
	{
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->setData(new CompoundTag("", [
			"Data" => $this->levelData,
		]));
		$buffer = $nbt->writeCompressed();
		file_put_contents($this->getPath() . "level.dat", $buffer);
	}

	public function requestChunkTask(int $x, int $z): AsyncTask
	{
		$chunk = $this->getChunk($x, $z, false);
		if(!($chunk instanceof Chunk)) {
			throw new ChunkException("Invalid Chunk sent");
		}

		return new ChunkRequestTask($this->level, $chunk);
	}

	public function updateGameRule($t, $s)
	{
		switch($t) {
			case "keepInventory";
				$this->levelData->GameRules->keepInventory = new StringTag("$t", "$s");
				break;
			case "showDeathMessages";
				$this->levelData->GameRules->showDeathMessages = new StringTag("$t", "$s");
				break;
			case "doTileDrops";
				$this->levelData->GameRules->doTileDrops = new StringTag("$t", "$s");
				break;
			case "doFireTick";
				$this->levelData->GameRules->doFireTick = new StringTag("$t", "$s");;
				break;
			case "doDaylightCycle";
				$this->levelData->GameRules->doDaylightCycle = new StringTag("$t", "$s");
				break;
		}
	}

	public function getGameRule($rule): bool
	{
		if(isset($this->levelData->GameRules)) {
			if(count($this->levelData->GameRules) == 5) {
				switch($rule) {
					case "keepInventory":
						if(isset($this->levelData->GameRules[$rule])) {
							return (boolean)$this->levelData->GameRules[$rule];
						}
						break;
					case "showDeathMessage":
						if(isset($this->levelData->GameRules[$rule])) {
							return (boolean)$this->levelData->GameRules[$rule];
						}
						break;
					case "doTileDrops":
						if(isset($this->levelData->GameRules[$rule])) {
							return (boolean)$this->levelData->GameRules[$rule];
						}
						break;
					case "doFireTick":
						if(isset($this->levelData->GameRules[$rule])) {
							return (boolean)$this->levelData->GameRules[$rule];
						}
						break;
					case "doDaylightCycle":
						if(isset($this->levelData->GameRules[$rule])) {
							return (boolean)$this->levelData->GameRules[$rule];
						}
						break;
				}
			} else {
				// Overwrite the NBT data cuz it's probably invalid.
				$this->levelData->GameRules = []; // Remove Everything from the GameRules CompoundTag.
				$this->levelData->GameRules = [
					"keepInventory"     => new StringTag("KeepInventory", "true"),
					"showDeathMessages" => new StringTag("showDeathMessages", "true"),
					"doTileDrops"       => new StringTag("doTileDrops", "true"),
					"doFireTick"        => new StringTag("doFireTick", "true"),
					"doDaylightCycle"   => new StringTag("doDaylightCycle", "true"),
				]; // Re-set the GameRules CompoundTag
			}
		}

		return false;
	}
}
