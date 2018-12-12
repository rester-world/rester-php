<?php
/**
 *	@class		File
 *	@author	    Kevin Park (kevinpark@webace.co.kr)
 *	@version	1.0
 *	@brief		파일 컨트롤 클래스.
 *	@date		2018.05.10 - 생성
 */
class file
{
    /**
     * @var array default values
     */
    protected $config = array(
        'upload_path'=>'rester/files',
        'upload_path_detail'=>'Y-m/d',
        'extensions'=>['jpg','png','jpeg','gif','svg','pdf','hwp','doc','docx','xls','xlsx','ppt','pptx','txt'],
        'max_count'=>5,
        'path_group'=>true,
        'upload_tmp'=>true
    );
    protected $module_name; // 호출 모듈명
    protected $data;        // 데이터
    protected $upload_path; // 파일업로드 경로

    /**
     * @param string $v
     */
    public function set_upload_path($v) { $this->config['upload_path'] = $v; }

    /**
     * @param array $v
     */
    public function set_extensions($v) { $this->config['extensions'] = $v; }

    /**
     * @param int $v
     */
    public function set_max_count($v) { $this->config['max_count'] = $v; }

    /**
     * @param bool $v
     */
    public function set_path_group($v) { $this->config['path_group'] = $v; }

    /**
     * @param string $v
     */
    public function set_path_detail($v) { $this->config['path_detail'] = $v; }

    /**
     * @param bool $v
     */
    public function set_upload_tmp($v) { $this->config['upload_tmp'] = $v; }

    /**
     * @param null|string $key
     *
     * @return array|string
     */
    public function get($key=null)
    {
        if($key===null) return $this->data;
        return $this->data[$key];
    }

    /**
     * 수동으로 다른 모듈을 설정 할 경우
     * 업로드 경로에 영향을 미친다.
     *
     * @param string $name 모듈명
     */
    public function set_module($name)
    {
        $this->module_name = $name;
    }

    /**
     * @return string
     */
    public function get_uploaded_path()
    {
        return $this->upload_path.$this->data['file_local_name'];
    }

    /**
     * file constructor.
     *
     * @param null|array|File $data 파일데이터
     */
    public function __construct($data=null)
    {
        try
        {
            $this->module_name = cfg::module();
            foreach(cfg::Get('file') as $k=>$v)
            {
                if($k=='extensions') $v = array_filter(explode(',',$v));
                if($v) $this->config[$k] = $v;
            }
        }
        catch (Exception $e)
        {
            rester::failure();
            rester::msg("Config load failure. ".$e->getMessage());
        }

        if(is_object($data)) { $this->data = $data->get(); }
        elseif(null !== $data && is_array($data)) $this->data = $data;

        ///=====================================================================
        // Gen upload path
        ///=====================================================================
        $path = explode('/',$this->config['upload_path']);
        if($this->data)
        {
            if($this->config['path_group']) $path[] = $this->data['file_module'];
            $path = array_merge($path,explode('/',date($this->config['upload_path_detail'],strtotime($this->data['file_datetime']))));
        }
        else
        {
            if($this->config['path_group']) $path[] = $this->module_name;
            $path = array_merge($path,explode('/',date($this->config['upload_path_detail'])));
        }
        // 경로명 설정에 방해가 될 수 있는 / 제거
        array_walk($path, function(&$item){ $item = str_replace('/','',$item); } );
        $path = array_filter($path);

        // 최종파일 추가 공백이 들어가면 / 추가됨
        $path[] = '';

        // 최종 업로드 경로
        $this->upload_path = implode('/',$path);
    }

    /**
     * 업로드될 파일 경로 생성
     *
     * 웹에서 실행가능한 파일들 방지
     * 중복된 파일이 있을경우 반복해서 파일명 생성
     *
     * @param string $file_name
     * @return string 생성된 파일 경로
     */
    public function gen_filename($file_name)
    {
        // 아래의 문자열이 들어간 파일은 -x 를 붙여서 웹경로를 알더라도 실행을 하지 못하도록 함
        $file_name = preg_replace("/\.(php|phtm|htm|cgi|pl|exe|jsp|asp|inc)/i", "$0-x", $file_name);
        // 공백을 _로 변환
        $file_name = str_replace(" ", "_", $file_name);
        $file_name = urlencode($file_name);

        do
        {
            $gen_file_name = substr(md5(uniqid(time())),0,20).'_'.$file_name;
        } while(is_file($this->upload_path.$gen_file_name));

        return $gen_file_name;
    }

    /**
     * 업로드 폴더 생성
     */
    protected function prepare_upload()
    {
        umask(0);
        mkdir($this->upload_path, 0775, true);
    }

    /**
     * 파일 업로드
     *
     * 클라이언트에서 전달 받은 파일을 업로드 한다.
     *  - 업로드 하려는 위치의 폴더를 생성한다.
     *  - 단일파일 또는 멀티파일 모두를 지원 하기위한 전처리
     *  - 파일 개수만큼 데이터베이스 레코드를 삽입하고 업로드된 목록을 반환해 줌
     *
     * @param string $form_name
     *
     * @return array 업로드된 파일목록
     */
    public function upload($form_name)
    {
        $this->prepare_upload();

        // 업로드된 파일
        $uploaded_files = array();

        try
        {
            // 폼이름
            $name = $form_name;

            // 2차원 배열구조로 파일업로드가 되었는지 검사
            if(is_assoc($_FILES[$name]['name']))
            {
                $sub_names = array_keys($_FILES[$name]['name']);
                foreach($sub_names as $subname)
                {
                    $_file_names = $_FILES[$name]['name'][$subname];
                    $_file_types = $_FILES[$name]['type'][$subname];
                    $_file_tmp_name = $_FILES[$name]['tmp_name'][$subname];
                    $_file_size = $_FILES[$name]['size'][$subname];
                    $_file_erros = $_FILES[$name]['error'][$subname];

                    $upload_file_count = sizeof($_file_names);
                    if($upload_file_count>$this->config['max_count'])
                    {
                        throw new Exception("File upload failed: Max upload file count({$this->config['max_count']}) Upload({$upload_file_count})");
                    }

                    // 파일개수만큼 돌기
                    foreach($_file_names as $k=>$v)
                    {
                        if(!$v) continue;
                        $file_name = $_file_names[$k];
                        $file_ext = array_pop(explode('.',$file_name));
                        $type = $_file_types[$k];
                        $tmp_name = $_file_tmp_name[$k];
                        $size = $_file_size[$k];

                        // 확장자 체크
                        if(!in_array($file_ext,$this->config['extensions']))
                        {
                            throw new Exception("Not allowed file extension. ({$file_ext})");
                        }
                        // 파일 업로드
                        else if(is_uploaded_file($tmp_name))
                        {
                            $real_file_name = $this->gen_filename($file_name);
                            $dest_file = $this->upload_path.$real_file_name;

                            if(move_uploaded_file($tmp_name, $dest_file))
                            {
                                umask(0);
                                chmod($dest_file, 0664);

                                $uploaded_files[$subname][$k] = array(
                                    'file_module'=>$this->module_name,
                                    'file_name'=>$file_name,
                                    'file_local_name'=>$real_file_name,
                                    'file_size'=>$size,
                                    'file_type'=>$type,
                                    'file_datetime'=>date("Y-m-d H:i:s")
                                );
                            }
                        }
                    }
                }
            }
            else
            {
                // 단일파일 => 파일 배열
                if(!is_array($_FILES[$name]['name']) && $_FILES[$name]['name'])
                {
                    $files['name'][0] = $_FILES[$name]['name'];
                    $files['type'][0] = $_FILES[$name]['type'];
                    $files['tmp_name'][0] = $_FILES[$name]['tmp_name'];
                    $files['size'][0] = $_FILES[$name]['size'];
                    $_FILES[$name] = $files;
                }

                $upload_file_count = sizeof($_FILES[$name]['name']);
                if($upload_file_count>$this->config['max_count'])
                {
                    throw new Exception("File upload failed: Max upload file count({$this->config['max_count']}) Upload({$upload_file_count})");
                }

                // 파일개수만큼 돌기
                foreach($_FILES[$name]['name'] as $k=>$v)
                {
                    $file_name = $_FILES[$name]['name'][$k];
                    $file_ext = array_pop(explode('.',$file_name));
                    $type = $_FILES[$name]['type'][$k];
                    $tmp_name = $_FILES[$name]['tmp_name'][$k];
                    $size = $_FILES[$name]['size'][$k];

                    // 확장자 체크
                    if(!in_array($file_ext,$this->config['extensions']))
                    {
                        throw new Exception("Not allowed file extension. ({$file_ext})");
                    }
                    // 파일 업로드
                    else if(is_uploaded_file($tmp_name))
                    {
                        $real_file_name = $this->gen_filename($file_name);
                        $dest_file = $this->upload_path.$real_file_name;

                        if(move_uploaded_file($tmp_name, $dest_file))
                        {
                            umask(0);
                            chmod($dest_file, 0664);

                            $uploaded_files[] = array(
                                'file_module'=>$this->module_name,
                                'file_name'=>$file_name,
                                'file_local_name'=>$real_file_name,
                                'file_size'=>$size,
                                'file_type'=>$type,
                                'file_datetime'=>date("Y-m-d H:i:s")
                            );
                        }
                    }
                }
            }

        }
        catch (Exception $e)
        {
            rester::failure();
            rester::msg($e->getMessage());
            $uploaded_files = false;
        }
        return $uploaded_files;
    }

    /**
     * 파일명으로 검색되는 모든 파일을 다 삭제한다.
     * 생성된 썸네일을 모두 삭제한다.
     * 데이터베이스에 연결되어 있으면 레코드도 삭제함
     */
    public function delete()
    {
        if($this->data['file_local_name'])
        {
            foreach (glob($this->get_uploaded_path().'*') as $v)
            {
                if(is_file($v)) unlink($v);
            }
        }
    }

    /**
     * @param string $url
     * @param string $saveto
     */
    public function grab_file($url,$saveto)
    {
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $raw=curl_exec($ch);
        curl_close ($ch);
        if(file_exists($saveto)){
            unlink($saveto);
        }
        $fp = fopen($saveto,'x');
        fwrite($fp, $raw);
        fclose($fp);
    }

    /**
     * @param $url string
     *
     * @return bool|File 업로드된 파일목록
     */
    public function upload_from_url($url)
    {
        $this->prepare_upload();

        $path = parse_url($url, PHP_URL_PATH);
        $file_name = basename($path);

        $real_file_name = $this->gen_filename($file_name);
        $dest_file = $this->upload_path.$real_file_name;

        // 파일 다운로드
        $this->grab_file($url,$dest_file);

        $uploaded_file = false;
        if(file_exists($dest_file))
        {
            umask(0);
            chmod($dest_file, 0664);
            $type = mime_content_type($dest_file);
            $size = filesize($dest_file);

            $uploaded_file = array(
                'file_module'=>$this->module_name,
                'file_name'=>$file_name,
                'file_local_name'=>$real_file_name,
                'file_size'=>$size,
                'file_type'=>$type
            );
        }
        else
        {
            rester::failure();
            rester::msg("File download failure.");
        }
        return $uploaded_file;
    }

    /**
     * 이미지 형태의 파일을 읽어들인다.
     * 파일 형식에 맞게 이미지 리소스를 로드함
     * 이미지 형식이 아닌 파일은 false를 반환하며 에러코드를 남긴다.
     * $echo 변수에 따라 바로 출력하거나 리소스를 반환한다.
     *
     * @param string $path 이미지 경로
     *
     * @return false|resource 이미지 리소스 반환 실패시 false
     *
     * @throws Exception
     */
    protected function load($path)
    {
        if(is_file($path))
        {
            $mime_type = mime_content_type($path);
            switch($mime_type)
            {
                case 'image/jpeg':
                    $resource = imagecreatefromjpeg($path);
                    break;
                case 'image/png':
                    $resource = imagecreatefrompng($path);
                    $background = imagecolorallocate($resource, 0, 0, 0);
                    imagecolortransparent($resource, $background);
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                    break;
                case "image/gif":
                    $resource = imagecreatefromgif($path);
                    $background = imagecolorallocate($resource, 0, 0, 0);
                    imagecolortransparent($resource, $background);
                    break;
                case 'image/svg+xml':
                    $resource = file_get_contents($path);
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
     * 썸네일 이미지 생성
     * 썸네일 비율 유지한채 축소 여백에는 가장 많이 쓰인 컬러키 값으로 체움
     * 이미 생성된 썸네일은 다시 생성하지 않음
     *
     * @param string $path_source
     * @param integer $thumb_width
     * @param integer $thumb_height
     *
     * @return string
     * @throws Exception
     */
    public function create_thumb($path_source, $thumb_width=0, $thumb_height=0)
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

            if($thumb_width && $thumb_height)
            {
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
            }
            elseif($thumb_width)
            {
                $ratio = $thumb_width/$width;
                $height = $height*$ratio;
                $width = $thumb_width;
                $thumb_height = $height;
            }
            elseif($thumb_height)
            {
                $ratio = $thumb_height/$height;
                $width = $width*$ratio;
                $height = $thumb_height;
                $thumb_width = $width;
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
            umask(0);
            @chmod($thumb_path, 0664); // 추후 삭제를 위하여 파일모드 변경
            imagedestroy($source);
        }

        return $thumb_path;
    }

    /**
     * 원본이미지 출력
     * @return false|resource
     */
    public function image()
    {
        $res = file_get_contents($this->get_uploaded_path());
        if(!$res)
        {
            rester::failure();
            rester::msg("Image load failure. ");
        }
        return $res;
    }

    /**
     * 썸네일 이미지 출력
     *
     * @param integer $thumb_width
     * @param integer $thumb_height
     *
     * @return bool|string
     */
    public function thumb($thumb_width, $thumb_height)
    {
        $res = false;
        try
        {
            $image_path = $this->get_uploaded_path();
            $thumb = $this->create_thumb($image_path, $thumb_width, $thumb_height);
            $res = file_get_contents($thumb);
            if(!$res)
            {
                throw new Exception("Can not load file.");
            }
        }
        catch (Exception $e)
        {
            rester::failure();
            rester::msg("Image load failure. ".$e->getMessage());
        }
        return $res;
    }

    /**
     * @param resource $res
     * @param string   $mime_type
     *
     * @throws Exception
     */
    public function printImage($res, $mime_type)
    {
        header('Content-Type: '.$mime_type);
        switch($mime_type)
        {
            case 'image/jpeg':
                imagejpeg($res);
                break;
            case 'image/png':
                imagepng($res);
                break;
            case "image/gif":
                imagegif($res);
                break;
            case 'image/svg+xml':
                echo $res;
                break;
            default : throw new Exception("지원되는 이미지 타입이 아닙니다.");
        }
    }

    /**
     *  파일 출력
     */
    public function download()
    {
        $filename = $this->data['file_name'];
        $filesize = $this->data['file_size'];
        $filepath = $this->get_uploaded_path();

        $headers = [
            "Pragma: public",
            "Expires: 0",
            "Content-Type: application/octet-stream",
            "Content-Disposition: attachment; filename='$filename'",
            "Content-Transfer-Encoding: binary",
            "Content-Length: $filesize"
        ];
        rester::set_header($headers);

        return file_get_contents($filepath);
    }

}
