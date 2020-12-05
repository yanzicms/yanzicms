<?php
/**
 * Project: Yanzicms
 * Producer: Yanzicms [ http://www.Yanzicms.com ]
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.Yanzicms.com All rights reserved.
 */
namespace extend\croppic;
class Croppic
{
    public function crop($output_filename)
    {
        $imgUrl = $_POST['imgUrl'];
        $imgInitW = $_POST['imgInitW'];
        $imgInitH = $_POST['imgInitH'];
        $imgW = $_POST['imgW'];
        $imgH = $_POST['imgH'];
        $imgY1 = $_POST['imgY1'];
        $imgX1 = $_POST['imgX1'];
        $cropW = $_POST['cropW'];
        $cropH = $_POST['cropH'];
        $angle = $_POST['rotation'];
        $jpeg_quality = 100;
        $what = getimagesize($imgUrl);
        switch(strtolower($what['mime']))
        {
            case 'image/png':
                $source_image = imagecreatefrompng($imgUrl);
                break;
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($imgUrl);
                break;
            case 'image/gif':
                $source_image = imagecreatefromgif($imgUrl);
                break;
            default: die('image type not supported');
        }
        if(!is_writable(dirname(ROOT . $output_filename))){
            $response = [
                'status' => 'error',
                'message' => 'Can`t write cropped File'
            ];
        }else{
            $resizedImage = imagecreatetruecolor($imgW, $imgH);
            imagecopyresampled($resizedImage, $source_image, 0, 0, 0, 0, $imgW, $imgH, $imgInitW, $imgInitH);
            $rotated_image = imagerotate($resizedImage, -$angle, 0);
            $rotated_width = imagesx($rotated_image);
            $rotated_height = imagesy($rotated_image);
            $dx = $rotated_width - $imgW;
            $dy = $rotated_height - $imgH;
            $cropped_rotated_image = imagecreatetruecolor($imgW, $imgH);
            imagecolortransparent($cropped_rotated_image, imagecolorallocate($cropped_rotated_image, 0, 0, 0));
            imagecopyresampled($cropped_rotated_image, $rotated_image, 0, 0, $dx / 2, $dy / 2, $imgW, $imgH, $imgW, $imgH);
            $final_image = imagecreatetruecolor($cropW, $cropH);
            imagecolortransparent($final_image, imagecolorallocate($final_image, 0, 0, 0));
            imagecopyresampled($final_image, $cropped_rotated_image, 0, 0, $imgX1, $imgY1, $cropW, $cropH, $cropW, $cropH);
            imagejpeg($final_image, ROOT . $output_filename, $jpeg_quality);
            $response = [
                'status' => 'success',
                'url' => str_replace('\\', '/', $output_filename)
            ];
        }
        return $response;
    }
}