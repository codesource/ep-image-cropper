<?php
/**
 * @copyright Copyright (c) 2019 Biceps
 */

namespace CDSRC\EmpirePuzzles\Action\Page;


use CDSRC\EmpirePuzzles\Service\HeroParser;

class HeroesExport extends AbstractActionResolver
{

    protected $temporaryDirectory;


    public function __construct()
    {
        $this->temporaryDirectory = rtrim(realpath(rtrim(__DIR__, '/') . '/../../../public/temp/'), '/') . '/';
    }

    /**
     * @param string $action
     *
     * @return array
     */
    public function resolve($action)
    {
        switch ($action) {
            case 'export':
                if (isset($_FILES['heroes']) && isset($_FILES['heroes']['tmp_name']) && is_array($_FILES['heroes']['tmp_name'])) {
                    $parser = new HeroParser();
                    $heroesByColor = [];
                    foreach ($_FILES['heroes']['tmp_name'] as $index => $file) {
                        $heroesByColor = $parser->parse($file, strtolower(pathinfo($_FILES['heroes']['name'][$index], PATHINFO_EXTENSION)), $heroesByColor);
                    }
                    $files = [];
                    $baseName = $this->getFileName();
                    foreach ($heroesByColor as $color => $heroes) {
                        $imageWidth = 0;
                        $imageHeight = 0;
                        $images = $this->extractImagesAndPosition($heroes, $imageWidth, $imageHeight);
                        if ($images && $imageWidth && $imageHeight) {
                            $fileName = $baseName . '-' . $color . '.jpg';
                            $fullPath = $this->temporaryDirectory . $fileName;
                            $background = imagecreatetruecolor($imageWidth, $imageHeight);
                            $allocatedColor = imagecolorallocate($background, 0, 0, 0);
                            imagefill($background, 0, 0, $allocatedColor);
                            foreach ($images as $image) {
                                imagecopymerge($background, $image[0], $image[1], $image[2], 0, 0, $image[3], $image[4], 100);
                                imagedestroy($image[0]);
                            }
                            imagejpeg($background, $fullPath);
                            imagedestroy($background);
                            if (file_exists($fullPath)) {
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

    protected function extractImagesAndPosition(array $heroes, &$finalWidth, &$finalHeight)
    {
        $images = [];
        $col = 0;
        $gab = 5;
        $currentY = $gab;
        $rowHeight = 0;
        $rowWidth = $gab;
        foreach ($heroes as $hero) {
            if ($rowHeight < $hero[2]) {
                $rowHeight = $hero[2] + $gab;
            }
            $images[] = [
                $hero[0],
                $rowWidth,
                $currentY,
                $hero[1],
                $hero[2],
            ];
            $rowWidth += $hero[1] + $gab;

            $col++;
            if ($col >= 5) {
                $col = 0;
                $currentY = $rowHeight;
                $finalHeight += $rowHeight;
                if ($finalWidth < $rowWidth) {
                    $finalWidth = $rowWidth;
                }
                $rowHeight = 0;
                $rowWidth = $gab;
            }
        }
        if ($col < 5) {
            $finalHeight += $rowHeight + $gab;
            if ($finalWidth < $rowWidth) {
                $finalWidth = $rowWidth;
            }
        }

        return $images;
    }

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