<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Action\Page;


use CDSRC\EmpirePuzzles\Service\HeroParser;

class HeroesExport extends AbstractActionResolver
{

    /**
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * @var string
     */
    protected $font;

    /**
     * @var int
     */
    protected $gab = 5;


    /**
     * HeroesExport constructor.
     */
    public function __construct()
    {
        $this->temporaryDirectory = rtrim(realpath(rtrim(__DIR__, '/') . '/../../../public/temp/'), '/') . '/';
        $this->font = rtrim(realpath(rtrim(__DIR__, '/') . '/../../../resources/fonts/'), '/') . '/verdanab.ttf';
    }

    /**
     * @param string $action
     *
     * @return array
     *
     * @throws \Exception
     */
    public function resolve($action)
    {
        switch ($action) {
            case 'export':
                if (isset($_FILES['heroes']) && isset($_FILES['heroes']['tmp_name']) && is_array($_FILES['heroes']['tmp_name'])) {
                    $parser = new HeroParser();
                    $sorting = [];
                    $heroesByColor = [];
                    if(isset($_POST['sorting']) && is_array($_POST['sorting'])){
                        $sorting = $_POST['sorting'];
                    }
                    foreach($sorting as $name){
                        $index = array_search($name, $_FILES['heroes']['name']);
                        if($index !== false){
                            $heroesByColor = $parser->parse($_FILES['heroes']['tmp_name'][$index], strtolower(pathinfo($_FILES['heroes']['name'][$index], PATHINFO_EXTENSION)), $heroesByColor);
                        }
                    }
                    $files = [];
                    $baseName = $this->getFileName();
                    foreach ($heroesByColor as $color => $heroes) {
                        $imageWidth = 0;
                        $imageHeight = 0;
                        $images = $this->extractImagesAndPosition($heroes, $imageWidth, $imageHeight);
                        if ($images && $imageWidth && $imageHeight) {
                            $fileName = $baseName . '-' . $color . '.jpg';
                            if ($this->generateHeroesImage($images, $imageWidth, $imageHeight, $fileName)) {
                                $files[] = [
                                    'file' => $fileName,
                                    'size' => count($images),
                                    'color' => $color,
                                ];
                            }
                        }
                    }
                }

                return [
                    'heroes' => $files,
                    'class' => 'exported',
                ];
            case 'download':
                if (isset($_POST['key']) && $_POST['key'] && isset($_POST['color']) && $_POST['color']) {
                    $file = $this->temporaryDirectory . $_POST['key'];
                    $color = $_POST['color'];
                    $date = new \DateTime();
                    if (file_exists($file)) {
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Transfer-Encoding: binary');
                        header(sprintf('Content-Disposition: attachment; filename="%s-%s.jpg"', $date->format('Ymd-Hi'), $color));
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($file));
                        ob_clean();
                        flush();
                        readfile($file);
                        exit;
                    }
                }
                break;
        }

        return [];
    }

    /**
     * @param array $images
     * @param int $imageWidth
     * @param int $imageHeight
     * @param string $fileName
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function generateHeroesImage(array $images, int $imageWidth, int $imageHeight, string $fileName)
    {
        list($date, $dateWidth, $dateHeight) = $this->getDateForImage($imageWidth);
        $fullPath = $this->temporaryDirectory . $fileName;
        $dateHeight += ($this->gab * 2);
        $dateExtendedWidth = $dateWidth + ($this->gab * 2);
        $finalWidth = max($imageWidth, $dateExtendedWidth);
        $background = imagecreatetruecolor($finalWidth, $imageHeight + $dateHeight);

        // Fill with black color
        $black = imagecolorallocate($background, 0, 0, 0);
        imagefill($background, 0, 0, $black);

        // Add date to header
        $white = imagecolorallocate($background, 255, 255, 255);
        $textX = ($finalWidth / 2) - ($dateWidth / 2);
        $textY = (($dateHeight + $this->gab) / 2) + $this->gab;
        imagettftext($background, 12, 0, $textX, $textY, $white, $this->font, $date);


        // Merge all heroes image
        $imageGab = $imageWidth > $dateExtendedWidth ? 0 : ($dateExtendedWidth - $imageWidth) / 2;
        foreach ($images as $image) {
            imagecopymerge($background, $image[0], $image[1] + $imageGab, $image[2] + $dateHeight, 0, 0, $image[3], $image[4], 100);
            imagedestroy($image[0]);
        }

        imagejpeg($background, $fullPath);
        imagedestroy($background);

        return file_exists($fullPath);
    }

    /**
     * @param $imageWidth
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getDateForImage($imageWidth)
    {
        $date = (new \DateTime())->format('d.m.Y H:i');
        $textBox = imagettfbbox(12, 0, $this->font, $date);
        $textWidth = abs($textBox[2] - $textBox[0]);
        $textHeight = abs($textBox[7] - $textBox[1]);

        return [
            $date,
            $textWidth,
            $textHeight
        ];
    }

    /**
     * @param array $heroes
     * @param int $finalWidth
     * @param int $finalHeight
     *
     * @return array
     */
    protected function extractImagesAndPosition(array $heroes, int &$finalWidth, int &$finalHeight)
    {
        $images = [];
        $col = 0;
        $currentY = $this->gab;
        $rowHeight = 0;
        $rowWidth = $this->gab;
        foreach ($heroes as $hero) {
            if ($rowHeight < $hero[2]) {
                $rowHeight = $hero[2] + $this->gab;
            }
            $images[] = [
                $hero[0],
                $rowWidth,
                $currentY,
                $hero[1],
                $hero[2],
            ];
            $rowWidth += $hero[1] + $this->gab;

            $col++;
            if ($col >= 5) {
                $col = 0;
                $currentY += $rowHeight;
                $finalHeight += $rowHeight;
                if ($finalWidth < $rowWidth) {
                    $finalWidth = $rowWidth;
                }
                $rowHeight = 0;
                $rowWidth = $this->gab;
            }
        }
        if ($col < 5) {
            $finalHeight += $rowHeight + $this->gab;
            if ($finalWidth < $rowWidth) {
                $finalWidth = $rowWidth;
            }
        }

        return $images;
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        $length = 32;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}