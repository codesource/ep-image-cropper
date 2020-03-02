<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Service;


class HeroParser
{

    /**
     * @var int
     */
    protected $minimalWidth = 80;

    /**
     * @var int
     */
    protected $minimalHeight = 250;

    /**
     * @var int
     */
    protected $sizeTolerance = 10;

    /**
     * @var int
     */
    protected $loop = 0;

    /**
     * @var string
     */
    protected $temporaryDirectory;


    /**
     * HeroesExport constructor.
     */
    public function __construct()
    {
        $this->temporaryDirectory = rtrim(realpath(rtrim(__DIR__, '/') . '/../../public/temp/'), '/') . '/';
    }


    public function parse($filename, $extension, array $existingHeroes)
    {
        $resource = $this->getResource($filename, $extension);
        if ($resource) {
            $heroesByColor = $this->getHeroes($resource);
            foreach ($heroesByColor as $color => $heroes) {
                if (!isset($existingHeroes[$color])) {
                    $existingHeroes[$color] = [];
                }
                foreach ($heroes as $key => $hero) {
                    $width = $hero[2] - $hero[0];
                    $height = $hero[3] - $hero[1];
                    $image = imagecrop($resource, [
                        'x' => $hero[0],
                        'y' => $hero[1],
                        'width' => $width,
                        'height' => $height,
                    ]);
                    if ($image) {
                        $index = count($existingHeroes[$color]);
                        $key = substr($key, 0, -5) . str_pad($index, 5, '0', STR_PAD_LEFT);
                        $existingHeroes[$color][$key] = [
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
            'blue' => [91, 172, 235, 250],
            'purple' => [249, 118, 248, 250],
            'green' => [35, 243, 26, 200],
        ];
        $heroes = [];
        $index = 0;
        foreach ($colors as $color => $reference) {
            $hero = $this->getHero($resource, $reference, null);
            while ($hero) {
                $stars = $this->getStars($resource, $hero);
                // Ignore low level heroes
                if($stars < 3){
                    $hero = $this->getHero($resource, $reference, $hero);
                    continue;
                }
                if (!isset($heroes[$color])) {
                    $heroes[$color] = [];
                }
                $key = sprintf(
                    '%s%s%s%s',
                    $stars,
                    $this->getBadges($resource, $hero),
                    str_pad($this->getLevel($resource, $hero), 3, '0', STR_PAD_LEFT),
                    str_pad($index, 5, '0', STR_PAD_LEFT)
                );
                $heroes[$color][$key] = $hero;
                $hero = $this->getHero($resource, $reference, $hero);
                $index++;
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
                if ($this->isColorMatching($resource, $x, $y, $reference, true)) {
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
                            if (
                                $this->isColorMatching($resource, $topRight[0], $k, $reference) ||
                                $this->isColorMatching($resource, $topRight[0] + 1, $k, $reference) ||
                                $this->isColorMatching($resource, $topRight[0] + 2, $k, $reference)
                            ) {
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
     * @param array $hero
     *
     * @return int
     */
    protected function getStars($resource, $hero)
    {
        $xPercentages = [
            0.165745856,
            0.314917127,
            0.464088398,
            0.613259669,
            0.762430939,
        ];
        $yPercentage = 0.789473684;
        $width = $hero[2] - $hero[0];
        $height = $hero[3] - $hero[1];
        $y = $hero[1] + round($height * $yPercentage);
        $reference = [255, 213, 0, 320];
        $stars = 0;
        foreach ($xPercentages as $percentage) {
            $x = $hero[0] + round($width * $percentage);
            if ($this->isColorMatching($resource, $x, $y, $reference)) {
                $stars++;
            } else {
                break;
            }
        }

        return $stars;
    }

    /**
     * @param resource $resource
     * @param array $hero
     *
     * @return int
     */
    protected function getBadges($resource, $hero)
    {
        $xPercentage = 0.806629834;
        $yPercentages = [
            0.614832536,
            0.583732057,
            0.552631579,
            0.5215311,
        ];
        $width = $hero[2] - $hero[0];
        $height = $hero[3] - $hero[1];
        $x = $hero[0] + round($width * $xPercentage);
        $reference = [255, 218, 0, 320];
        $badges = 0;

        foreach ($yPercentages as $percentage) {
            $y = $hero[1] + round($height * $percentage);
            if ($this->isColorMatching($resource, $x, $y, $reference)) {
                $badges++;
            } else {
                break;
            }
        }

        return $badges;
    }

    /**
     * @param resource $resource
     * @param array $hero
     *
     * @return int
     */
    protected function getLevel($resource, $hero)
    {
        $percentages = [
            'x' => 0.331491713,
            'y' => 0.56937799,
            'w' => 0.220994475,
            'h' => 0.064593301,
        ];
        $width = $hero[2] - $hero[0];
        $height = $hero[3] - $hero[1];
        $image = imagecreate(120, 81);
        $copied = imagecopyresized(
            $image,
            $resource,
            0,
            0,
            $hero[0] + round($width * $percentages['x']),
            $hero[1] + round($height * $percentages['y']),
            120,
            81,
            round($width * $percentages['w']),
            round($height * $percentages['h']),
        );
        $level = 0;
        if ($copied) {
            $filename = tempnam($this->temporaryDirectory, 'heroLevel_');
            imagepng($image, $filename, 0);

            $ch = curl_init('http://ocr2.leniver.ch/');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'digit' => true,
                'file' => curl_file_create($filename),
            ]);
            $level = intval(curl_exec($ch));
            curl_close($ch);

            unlink($filename);
        }
        imagedestroy($image);

        return $level ?: 1;
    }

    /**
     * @param resource $resource
     * @param int $x
     * @param int $y
     * @param array $reference
     * @param bool $restricted
     *
     * @return bool
     */
    protected function isColorMatching($resource, $x, $y, array $reference, $restricted = false)
    {
        $rgb = imagecolorat($resource, $x, $y);

        $red = ($rgb >> 16) & 0xFF;
        $green = ($rgb >> 8) & 0xFF;
        $blue = $rgb & 0xFF;

        $redMean = ($red + $reference[0]) / 2;
        $diffRed = abs($red - $reference[0]);
        $diffGreen = abs($green - $reference[1]);
        $diffBlue = abs($blue - $reference[2]);
        $distance = sqrt(
            (((512 + $redMean) * pow($diffRed, 2)) >> 8) +
            (4 * pow($diffGreen, 2)) +
            (((767 - $redMean) * pow($diffBlue, 2)) >> 8)
        );

        return $distance < $reference[3] && (!$restricted || ($diffRed < 100 && $diffGreen < 100 && $diffBlue < 100));
    }
}