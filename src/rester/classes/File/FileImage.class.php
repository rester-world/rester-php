<?php
namespace Rester\File;
use Exception;

/**
 * Class FileImage
 *
 * @package Rester\File
 */
class FileImage extends File
{


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 원본이미지 출력
     *
     * @param integer $file_no
     *
     * @throws Exception
     */
    public function image($file_no)
    {
        try
        {
            $this->fetch($file_no);
        }
        catch (Exception $e)
        {
            throw $e;
        }

        header('Content-Type: '.$this->file_type());
        $this->load($this->get_uploaded_path(),true);
        exit;
    }


    /**
     * 이미지 형태의 파일을 읽어들인다.
     * 파일 형식에 맞게 이미지 리소스를 로드함
     * 이미지 형식이 아닌 파일은 false를 반환하며 에러코드를 남긴다.
     * $echo 변수에 따라 바로 출력하거나 리소스를 반환한다.
     *
     * @param string $path 이미지 경로
     * @param bool $echo
     *
     * @return false|resource 이미지 리소스 반환 실패시 false
     *
     * @throws Exception
     */
    protected function load($path, $echo = false)
    {
        if(is_file($path))
        {
            switch(mime_content_type($path))
            {
                case 'image/jpeg':
                    $resource = imagecreatefromjpeg($path);
                    if($echo) imagejpeg($resource);
                    break;
                case 'image/png':
                    $resource = imagecreatefrompng($path);
                    $background = imagecolorallocate($resource, 0, 0, 0);
                    imagecolortransparent($resource, $background);
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                    if($echo) imagepng($resource);
                    break;
                case "image/gif":
                    $resource = imagecreatefromgif($path);
                    $background = imagecolorallocate($resource, 0, 0, 0);
                    imagecolortransparent($resource, $background);
                    if($echo) imagegif($resource);
                    break;
                default : throw new Exception("지원되는 이미지 타입이 아닙니다.");
            }
        }
        else
        {
            throw new Exception("1번째 파라미터는 읽을수 있는 파일 경로가 필요합니다.");
        }
        return $resource;
    }

    /**
     * 썸네일 이미지 출력
     *
     * @param integer $file_no
     * @param integer $thumb_width
     * @param integer $thumb_height
     *
     * @throws Exception
     */
    public function thumb($file_no, $thumb_width, $thumb_height)
    {
        try
        {
            $this->fetch($file_no);
        }
        catch (Exception $e)
        {
            throw $e;
        }
        $image_path = $this->get_uploaded_path();

        try
        {
            $thumb = $this->create_thumb($image_path, $thumb_width, $thumb_height);
        }
        catch (Exception $e)
        {
            throw $e;
        }

        // 썸네일 이미지 출력
        header('Content-Type: image/jpeg');
        imagejpeg($thumb);
        imagedestroy($thumb);
        exit;
    }

    /**
     * 썸네일 이미지 생성
     * 썸네일 비율 유지한채 축소 여백에는 가장 많이 쓰인 컬러키 값으로 체움
     * 이미 생성된 썸네일은 다시 생성하지 않음
     *
     * @param string $path_source
     * @param integer $thumb_width
     * @param integer $thumb_height
     *
     * @return false|null|resource
     * @throws Exception
     */
    public function create_thumb($path_source, $thumb_width, $thumb_height)
    {
        // 타겟 이미지 (썸네일)
        $thumb_path = sprintf("%s_%s_%s", $path_source, $thumb_width, $thumb_height);

        // 썸네일 이미지 인스턴스
        $target = null;

        if(!is_file($thumb_path))
        {
            // 소스이미지
            $source = $this->load($path_source);
            list($ori_width, $ori_height) = getimagesize($path_source);

            // 썸네일 비율 및 사이즈 계산
            // 찌그러지지 않도록 비율로 줄인다.

            // 1. 원본영상 크기를 긴 사이즈 기준으로 비율축소함
            // 2. 짧은 사이즈 가 썸네일 크기를 초과하지 않으면 바로 적용
            $width = $ori_width;
            $height = $ori_height;

            if($width>$height)
            {
                $ratio = $thumb_width/$width;
                $height = $height*$ratio;
                $width = $thumb_width;

                // 썸네일 크기 보다 초과될 경우 다시 줄인다.
                if($height>$thumb_height)
                {
                    $ratio = $thumb_height/$height;
                    $width = $width*$ratio;
                    $height = $thumb_height;
                }
            }
            else
            {
                $ratio = $thumb_height/$height;
                $width = $width*$ratio;
                $height = $thumb_height;

                // 썸네일 크기 보다 초과될 경우 다시 줄인다.
                if($width>$thumb_width)
                {
                    $ratio = $thumb_width/$width;
                    $height = $height*$ratio;
                    $width = $thumb_width;
                }
            }

            // 칠하기 포지션 계산
            $diff_width = abs($thumb_width - $width);
            $diff_height = abs($thumb_height - $height);

            $dest_x = 0;
            $dest_y = 0;
            $dest_width = $width;
            $dest_height = $height;

            // 가로 너비가 넓을 경우 가로기준 맞추고 상하에 배경색 칠함
            if($diff_width>$diff_height)
            {
                $dest_x = round($diff_width/2);
            }
            else
            {
                $dest_y = round($diff_height/2);
            }

            // 배경을 깔기위한 컬러키 값 뽑기
            // 가장 많이 사용된 컬러 값
            $colors = array();
            for($x = 0; $x < $ori_width; $x+=2)
            {
                for($y = 0; $y < $ori_height; $y+=2)
                {
                    $c = imagecolorat($source, $x, $y);
                    if(array_key_exists($c, $colors)) $colors[$c]++;
                    else $colors[$c] = 1;
                }
            }

            // 정렬하여 가장 많이 사용된 컬러키 값을 뽑아옴
            arsort($colors);
            $bgColorRGB = imagecolorsforindex($source, array_shift(array_keys($colors)));

            $target = @imagecreatetruecolor($thumb_width, $thumb_height);
            imagefill($target,0,0,imagecolorallocate($target,$bgColorRGB['red'],$bgColorRGB['green'],$bgColorRGB['blue']));

            @imagecopyresampled(
                $target,
                $source,
                $dest_x,
                $dest_y,
                0,
                0,
                $dest_width,
                $dest_height,
                $ori_width,
                $ori_height
            );

            // 파일로 쓰기
            @imagejpeg($target, $thumb_path, 100);
            @chmod($thumb_path, 0664); // 추후 삭제를 위하여 파일모드 변경

            imagedestroy($source);
        }
        else
        {
            // 타겟이미지
            $target = $this->load($thumb_path);
        }

        return $target;
    }

}


