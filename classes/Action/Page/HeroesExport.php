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
    protected $gab = 10;


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
//                $files = [];
                $baseName = null;
                if (isset($_FILES['heroes']) && isset($_FILES['heroes']['tmp_name'])) {
                    $parser = new HeroParser();
                    $heroesByColor = $parser->parse($_FILES['heroes']['tmp_name'], strtolower(pathinfo($_FILES['heroes']['name'], PATHINFO_EXTENSION)));
                    $baseName = $this->getFileName();
                    mkdir($this->temporaryDirectory . $baseName);
                    foreach ($heroesByColor as $color => $heroes) {
                        foreach ($heroes as $key => $hero) {
                            $fileName = sprintf('%s/%s-%s%s%s-%s.jpg',
                                $baseName,
                                $color,
                                substr($key, 0, 1),
                                substr($key, 1, 1),
                                substr($key, 2, 3),
                                $this->getFileName()
                            );
                            imagejpeg($hero[0], $this->temporaryDirectory . $fileName);
                        }
                    }
                }
                header('Content-Type: application/json');
                echo json_encode(['key' => $baseName]);
                exit;
            case 'finalize':
                $keys = isset($_POST['keys']) ? $_POST['keys'] : null;
                if (!$keys || !is_array($keys)) {
                    header('HTTP/1.1 400 Bad Request');
                    exit;
                }
                $heroesByColor = [];
                $index = 0;
                foreach ($keys as $key) {
                    if (!$key) {
                        header('HTTP/1.1 400 Bad Request');
                        exit;
                    }
                    $dir = $this->temporaryDirectory . $key;
                    if (!is_dir($dir)) {
                        header('HTTP/1.1 400 Bad Request');
                        exit;
                    }
                    foreach (glob($dir . '/*.jpg') as $file) {
                        $image = imagecreatefromjpeg($file);
                        if (preg_match('/(blue|yellow|purple|green|red)-([0-9]{5})-.*.jpg/', $file, $match) && $image) {
                            if (!isset($heroesByColor[$match[1]])) {
                                $heroesByColor[$match[1]] = [];
                            }
                            $heroesByColor[$match[1]][$match[2] . str_pad($index, 5, '0', STR_PAD_LEFT)] = [
                                $image,
                                imagesx($image),
                                imagesy($image),
                            ];
                            $index++;
                        } else {
                            header('HTTP/1.1 400 Bad Request');
                            exit;
                        }
                        unlink($file);
                    }
                    rmdir($dir);
                }
                $files = [];
                $baseName = $this->getFileName();
                foreach ($heroesByColor as $color => $heroes) {
                    $imageWidth = 0;
                    $imageHeight = 0;
                    krsort($heroes);
                    $images = $this->extractImagesAndPosition($heroes, $imageWidth, $imageHeight);
                    if ($images && $imageWidth && $imageHeight) {
                        $fileName = $baseName . '-' . $color . '.jpg';
                        if ($this->generateHeroesImage($images, $imageWidth, $imageHeight, $fileName)) {
                            $files[] = [
                                'file' => $fileName,
                                'color' => $color,
                            ];
                        }
                    }
                }
                header('Content-Type: application/json');
                echo json_encode(['files' => $files]);
                exit;
                break;
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
        $currentStar = null;
        foreach ($heroes as $key => $hero) {
            $star = substr($key, 0, 1);
            if ($currentStar !== null && $star !== $currentStar) {
                $col = 0;
                $currentY += $rowHeight + (3 * $this->gab);
                $finalHeight += $rowHeight + (3 * $this->gab);
                if ($finalWidth < $rowWidth) {
                    $finalWidth = $rowWidth;
                }
                $rowHeight = 0;
                $rowWidth = $this->gab;
            }
            $currentStar = $star;

            if ($rowHeight < $hero[2]) {
                $rowHeight = $hero[2] + $this->gab;
            }
            $images[] = [
                $hero[0],
                $rowWidth,
                $currentY,
                $hero[1],
                $hero[2],
                $star,
                substr($key, 1, 1),
                substr($key, 2, 3),
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
    protected function getFileName($length = 16)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return time() . '-' . $randomString;
    }
}