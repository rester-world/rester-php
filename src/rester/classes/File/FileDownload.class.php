<?php
namespace Rester\File;

/**
 * Class FileDownload
 *
 * @package Rester\File
 */
class FileDownload extends File
{
    /**
     * FileDownload constructor.
     *
     * @param null|array|File $data
     *
     * @throws \Rester\Exception\RequireModuleName
     */
    public function __construct($data = null) { parent::__construct($data); }

    /**
     *  파일 출력
     */
    public function run()
    {
        $filename = $this->file_name();
        $filesize = $this->file_size();
        $filepath = $this->get_uploaded_path();

        header("Pragma: public");
        header("Expires: 0");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename='$filename'");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: $filesize");

        ob_clean();
        flush();
        readfile($filepath);
    }
}


