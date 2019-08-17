<?php

declare(strict_types=1);

namespace xenialdan\MagicWE2\selection;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\UUID;
use xenialdan\MagicWE2\API;
use xenialdan\MagicWE2\selection\shape\Cuboid;
use xenialdan\MagicWE2\selection\shape\Shape;
use xenialdan\MagicWE2\session\Session;

/**
 * Class Selection
 * @package xenialdan\MagicWE2
 */
class Selection implements \Serializable
{
    /** @var int */
    public $levelid;
    /** @var Vector3 */
    public $pos1;
    /** @var Vector3 */
    public $pos2;
    /** @var UUID */
    public $uuid;
    /** @var UUID */
    public $sessionUUID;
    /** @var Shape */
    public $shape;

    /**
     * Selection constructor.
     * @param UUID $sessionUUID
     * @param Level $level
     * @param ?int $minX
     * @param ?int $minY
     * @param ?int $minZ
     * @param ?int $maxX
     * @param ?int $maxY
     * @param ?int $maxZ
     */
    public function __construct(UUID $sessionUUID, Level $level, $minX = null, $minY = null, $minZ = null, $maxX = null, $maxY = null, $maxZ = null)
    {
        $this->sessionUUID = $sessionUUID;
        $this->setLevel($level);
        if (isset($minX) && isset($minY) && isset($minZ)) {
            $this->setPos1(new Vector3($minX, $minY, $minZ));
        }
        if (isset($maxX) && isset($maxY) && isset($maxZ)) {
            $this->setPos2(new Vector3($maxX, $maxY, $maxZ));
        }
        $this->setUUID(UUID::fromRandom());
    }

    /**
     * @return Level
     * @throws \Exception
     */
    public function getLevel(): Level
    {
        if (is_null($this->levelid)) {
            throw new \Exception("Level is not set!");
        }
        $level = Server::getInstance()->getLevel($this->levelid);
        if (is_null($level)) {
            throw new \Exception("Level is not found!");
        }
        return $level;
    }

    /**
     * @param Level $level
     */
    public function setLevel(Level $level): void
    {
        $this->levelid = $level->getId();
    }

    /**
     * @return Position
     * @throws \Exception
     */
    public function getPos1(): Position
    {
        if (is_null($this->pos1)) {
            throw new \Exception("Position 1 is not set!");
        }
        return Position::fromObject($this->pos1, $this->getLevel());
    }

    /**
     * @param Position $position
     */
    public function setPos1(Position $position): void
    {
        $this->pos1 = $position->asVector3()->floor();
        if ($this->pos1->y >= Level::Y_MAX) $this->pos1->y = Level::Y_MAX;
        if ($this->pos1->y < 0) $this->pos1->y = 0;
        if ($this->levelid !== $position->getLevel()->getId()) {//reset other position if in different level
            $this->pos2 = null;
        }
        $this->setLevel($position->getLevel());
        if (!$this->shape instanceof Shape && $this->isValid())
            $this->setShape(new Cuboid($this->getSizeX(), $this->getSizeY(), $this->getSizeZ()));
        $session = API::getSessionByUUID($this->sessionUUID);
        if ($session instanceof Session) $session->sendMessage(TF::GREEN . "Position 1 set to X: " . $this->pos1->getX() . " Y: " . $this->pos1->getY() . " Z: " . $this->pos1->getZ());
    }

    /**
     * @return Position
     * @throws \Exception
     */
    public function getPos2(): Position
    {
        if (is_null($this->pos2)) {
            throw new \Exception("Position 2 is not set!");
        }
        return Position::fromObject($this->pos2, $this->getLevel());
    }

    /**
     * @param Position $position
     */
    public function setPos2(Position $position): void
    {
        $this->pos2 = $position->asVector3()->floor();
        if ($this->pos2->y >= Level::Y_MAX) $this->pos2->y = Level::Y_MAX;
        if ($this->pos2->y < 0) $this->pos2->y = 0;
        if ($this->levelid !== $position->getLevel()->getId()) {
            $this->pos1 = null;
        }
        $this->setLevel($position->getLevel());
        if (!$this->shape instanceof Shape && $this->isValid())
            $this->setShape(new Cuboid($this->getSizeX(), $this->getSizeY(), $this->getSizeZ()));
        $session = API::getSessionByUUID($this->sessionUUID);
        if ($session instanceof Session) $session->sendMessage(TF::GREEN . "Position 2 set to X: " . $this->pos2->getX() . " Y: " . $this->pos2->getY() . " Z: " . $this->pos2->getZ());
    }

    /**
     * @return Shape
     * @throws \Exception
     */
    public function getShape(): Shape
    {
        if (!$this->shape instanceof Shape) throw new \Exception("Shape is not valid");
        return $this->shape;
    }

    /**
     * @param Shape $shape
     */
    public function setShape(Shape $shape): void
    {
        $this->shape = $shape;
    }

    /**
     * Checks if a Selection is valid. It is not valid if:
     * - The level is not set
     * - Any of the positions are not set
     * - The shape is not set / not a shape
     * - The positions are not in the same level
     * @return bool
     */
    public function isValid(): bool
    {
        try {
            #$this->getShape();
            $this->getLevel();
            if ($this->getPos1()->getLevel()->getId() !== $this->getPos2()->getLevel()->getId()) throw new \Exception("Position level not same");
        } catch (\Exception $e) {
            return false;
        } finally {
            return true;
        }
    }

    /**
     * @return int
     */
    public function getSizeX(): int
    {
        return abs($this->pos1->x - $this->pos2->x) + 1;
    }

    /**
     * @return int
     */
    public function getSizeY(): int
    {
        return abs($this->pos1->y - $this->pos2->y) + 1;
    }

    /**
     * @return int
     */
    public function getSizeZ(): int
    {
        return abs($this->pos1->z - $this->pos2->z) + 1;
    }

    /**
     * @param UUID $uuid
     */
    public function setUUID(UUID $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return UUID
     */
    public function getUUID(): UUID
    {
        return $this->uuid;
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize([
            $this->levelid,
            $this->pos1,
            $this->pos2,
            $this->uuid,
            $this->sessionUUID
        ]);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        /** @var Vector3 $pos1 , $pos2 */
        [
            $this->levelid,
            $this->pos1,
            $this->pos2,
            $this->uuid,
            $this->sessionUUID
        ] = unserialize($serialized);
    }
}