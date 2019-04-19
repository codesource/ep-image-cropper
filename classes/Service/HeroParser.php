<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Service;


class HeroParser
{

    protected $minimalWidth = 80;

    protected $minimalHeight = 250;

    protected $sizeTolerance = 10;

    protected $loop = 0;


    public function parse($filename, $extension, array $existingHeroes)
    {
        $resource = $this->getResource($filename, $extension);
        $images = [];
        if ($resource) {
            $heroesByColor = $this->getHeroes($resource);
            foreach ($heroesByColor as $color => $heroes) {
                if(!isset($existingHeroes[$color])){
                    $existingHeroes[$color] = [];
                }
                foreach ($heroes as $hero) {
                    $width = $hero[2] - $hero[0];
                    $height = $hero[3] - $hero[1];
                    $image = imagecrop($resource, [
                        'x' => $hero[0],
                        'y' => $hero[1],
                        'width' => $width,
                        'height' => $height,
                    ]);
                    if ($image) {
                        $existingHeroes[$color][] = [
                            $image,
                            $width,
                            $height,
                        ];
                    }
                }
            }
        }

        return $existingHeroes;
    }

    /**
     * @param resource $resource
     *
     * @return array
     */
    protected function getHeroes($resource)
    {
        $colors = [
            'yellow' => [238, 221, 70, 320],
            'red' => [255, 54, 46, 180],
            'blue' => [65, 174, 255, 250],
            'purple' => [249, 118, 248, 250],
            'green' => [35, 243, 26, 200],
        ];
        $heroes = [];
        foreach ($colors as $color => $reference) {
            $hero = $this->getHero($resource, $reference, null);
            while ($hero) {
                if (!isset($heroes[$color])) {
                    $heroes[$color] = [];
                }
                $heroes[$color][] = $hero;
                $hero = $this->getHero($resource, $reference, $hero);
            }
        }

        return $heroes;
    }

    /**
     * @param $filename
     * @param $extension
     *
     * @return false|resource|null
     */
    protected function getResource($filename, $extension)
    {
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                return imagecreatefromjpeg($filename);
            case 'png':
                return imagecreatefrompng($filename);
            case 'gif':
                return imagecreatefromgif($filename);
        }

        return null;
    }

    /**
     * @param resource $resource
     * @param array $reference
     * @param array $lastHero
     * @return array
     */
    protected function getHero($resource, array $reference, array $lastHero = null)
    {
        $width = imagesx($resource);
        $height = imagesy($resource);
        if ($lastHero) {
            $startX = $lastHero[2];
            $startY = $lastHero[1];
            $cellHeight = $lastHero[3];
        } else {
            $startX = 0;
            $startY = 0;
            $cellHeight = $height;
        }

        for ($y = $startY; $y < $cellHeight; $y++) {
            $topLeft = [];
            $topRight = [];
            $bottomRight = [];
            for ($x = $startX; $x < $width; $x++) {
                if ($this->isColorMatching($resource, $x, $y, $reference)) {
                    if ($topLeft) {
                        $topRight = [$x, $y];
                    } else {
                        $topLeft = [$x, $y];
                    }
                } else if ($topRight) {
                    // Make sure that width of hero's box is in bounds
                    $heroWidth = abs($topLeft[0] - $topRight[0]);
                    if ($heroWidth < $this->minimalWidth || $heroWidth > $width / 4) {
                        $topLeft = [];
                        $topRight = [];
                    } else {
                        for ($k = $y + 1; $k < $cellHeight; $k++) {
                            if ($this->isColorMatching($resource, $topRight[0], $k, $reference)) {
                                $bottomRight = [$topRight[0], $k];
                            } else {
                                // Make sure that height of hero's box is in bounds
                                $heroHeight = abs($topRight[1] - $bottomRight[1]);
                                if ($heroHeight < $this->minimalHeight || $heroHeight > $height / 2.5) {
                                    $topLeft = [];
                                    $topRight = [];
                                    $bottomRight = [];
                                }
                                break;
                            }
                        }
                    }
                    if ($bottomRight) {
                        return [$topLeft[0] - 2, $topLeft[1], $bottomRight[0] + 2, $bottomRight[1] + 3];
                    }
                } else {
                    $topLeft = [];
                }
            }
        }
        if ($lastHero && $startY !== $cellHeight) {
            return $this->getHero($resource, $reference, [$width, $lastHero[3], 0, $height]);
        }

        return [];
    }

    /**
     * @param resource $resource
     * @param int $x
     * @param int $y
     * @param array $reference
     * @return bool
     */
    protected function isColorMatching($resource, $x, $y, array $reference)
    {
        $rgb = imagecolorat($resource, $x, $y);

        $red = ($rgb >> 16) & 0xFF;
        $green = ($rgb >> 8) & 0xFF;
        $blue = $rgb & 0xFF;

        $redMean = ($red + $reference[0]) / 2;
        $diffRed = pow($red - $reference[0], 2);
        $diffGreen = pow($green - $reference[1], 2);
        $diffBlue = pow($blue - $reference[2], 2);
        $distance = sqrt(
            (((512 + $redMean) * $diffRed) >> 8) +
            (4 * $diffGreen) +
            (((767 - $redMean) * $diffBlue) >> 8)
        );

        return $distance < $reference[3];
    }
}