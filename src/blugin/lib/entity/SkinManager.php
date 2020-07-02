<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\lib\entity;

use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\types\SkinData;

abstract class SkinManager{
    /** @var string[] */
    public static $skinData = [];
    /** @var string */
    private static $geometryName = [];
    /** @var string */
    private static $geometryData = [];
    /** @var Skin[] */
    private static $skinCache = [];

    /**
     * @param string $key
     * @param string $geometryData
     * @param string $geometryName = null
     */
    public static function registerGeometry(string $key, string $geometryData, string $geometryName = null) : void{
        self::$geometryData[$key] = $geometryData;
        self::$geometryName[$key] = $geometryName ?? array_keys(json_decode($geometryData, true))[0];

        //Removes cached Skin instance when skin changes
        unset(self::$skinCache[$key]);
    }

    /**
     * @param string $key
     * @param string $skinData
     */
    public static function registerSkin(string $key, string $skinData) : void{
        self::$skinData[$key] = $skinData;

        //Removes cached Skin instance when skin changes
        unset(self::$skinCache[$key]);
    }

    /**
     * @param string $key
     * @param bool   $toSkinData
     *
     * @return Skin|SkinData
     */
    public static function get(string $key, $toSkinData = false){
        //Create if there is no cached Skin instance
        if(!isset(self::$skinCache[$key])){
            self::$skinCache[$key] = new Skin("WardenMonster_" . $key, self::$skinData[$key], "", self::$geometryName[$key], self::$geometryData[$key]);
        }
        $skin = clone self::$skinCache[$key];
        return $toSkinData ? SkinAdapterSingleton::get()->toSkinData($skin) : $skin;
    }

    /**
     * @param string $filename
     *
     * @return string Skindata
     *
     * @throws InvalidSkinException
     */
    public static function png2skindata(string $filename) : string{
        $image = imagecreatefrompng($filename);
        $width = imagesx($image);
        $height = imagesy($image);
        $size = $width * $height * 4;
        if(!in_array($size, Skin::ACCEPTED_SKIN_SIZES, true))
            throw new InvalidSkinException("Invalid skin data size $size bytes (allowed sizes: " . implode(", ", Skin::ACCEPTED_SKIN_SIZES) . ")");

        $skinData = "";
        for($y = 0; $y < $height; $y++){
            for($x = 0; $x < $width; $x++){
                $rgba = imagecolorat($image, $x, $y);
                $a = (127 - (($rgba >> 24) & 0x7F)) * 2;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $skinData .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        imagedestroy($image);
        return $skinData;
    }
}