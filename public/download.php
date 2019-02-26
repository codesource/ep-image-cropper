<?php
/**
 * @copyright Copyright (c) 2019 Code-Source
 */

class Downloader
{
    /**
     * @var string
     */
    protected $report;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * Downloader constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->report = $this->extractReport();
        $this->date = new \DateTime($this->getPostVars('date'));
    }

    /**
     * Send file to user
     */
    public function sendReport()
    {
        // Convert png to jpg
        $quality = 85;
        $pngFile = tempnam(sys_get_temp_dir(), 'ep-convert');
        $jpgFile = tempnam(sys_get_temp_dir(), 'ep-convert');
        file_put_contents($pngFile, $this->report);
        $image = imagecreatefrompng($pngFile);
        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, TRUE);
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);
        imagejpeg($bg, $jpgFile, $quality);
        imagedestroy($bg);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: binary');
        header(sprintf('Content-Disposition: attachment; filename="%s-report.jpg"', $this->date->format('Ymd-Hi')));
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($jpgFile));
        ob_clean();
        flush();
        readfile($jpgFile);
        unlink($jpgFile);
        unlink($pngFile);
        exit;
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    protected function extractReport()
    {
        $report = $this->getPostVars('report');
        if (strpos($report, 'data:image/png;base64,') !== 0) {

            throw new \Exception('Unable to decode image');
        }
        $report = base64_decode(str_replace('data:image/png;base64,', '', $report));

        if (!$report) {
            throw new \Exception('Unable to extract image');
        }

        return $report;
    }

    /**
     * Get variable from POST vars
     *
     * @param string $key
     *
     * @return string
     *
     * @throws Exception
     */
    protected function getPostVars($key)
    {
        if (!isset($_POST[$key]) || !is_string($_POST[$key]) || strlen($_POST[$key]) === 0) {
            throw new \Exception(sprintf('Required "%s" parameter not found', $key));
        }

        return $_POST[$key];
    }
}

try {
    $downloader = new Downloader();
    $downloader->sendReport();
} catch (\Exception $e) {
    header("HTTP/1.0 404 Not Found");
    header("X-REASON: " . $e->getMessage());
    exit;
}
