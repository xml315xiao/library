<?php  defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('upload'))
{
    /**
     * Upload file to destination dir.
     * @param string    $form the name of the form for file field.
     * @param string    $destination the directory of files uploaded
     * @param array     $allow_types allow files
     * @param int       $max_size    top size
     * @return JsonIncrementalParser
     */
    function upload($form, $destination, $allow_types = array('jpg', 'gif', 'png'), $max_size = 10240000)
    {
        $result = [
            'success' => FALSE,
            'filename'  => '',
            'error'     => '',
        ];

        if ( ! isset($_FILES[$form])) {
            $result['error'] = 'Upload no file selected.';
            return json_encode($result);
        }
        $file_info = $_FILES[$form];


        // validate upload path
        if ( strlen($destination) === 0 OR ! is_dir($destination) OR ! is_writable($destination) ) {
            $result['error'] = 'upload path disabled';
            return json_encode($result);
        }

        // whether the file was uploaded via HTTP POST
        if ( ! is_uploaded_file($file_info['tmp_name']) ) {
            $code = isset($file_info['error']) ? $file_info['error'] : 4;
            $errors = array(
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file to disk',
                8 => 'A PHP extension stopped the file upload'
            );
            $result['error'] = $errors[$code];
            return json_encode($result);
        }

        // validate file type
        if ( ! is_array($allow_types) ) {
            $result['error'] = 'upload no file types';
            return json_encode($result);
        }

        $suffix = strtolower(ltrim(strrchr($file_info['name'], '.'), '.'));
        if ( sizeof($allow_types) > 0 && ! in_array($suffix, $allow_types) ) {
            $result['error'] = 'The type of files not allowed';
            return json_encode($result);
        }

        // validate file size
        $size = round($file_info['size']/1024, 2);
        if ( $size > $max_size ) {
            $result['error'] = 'The size of file you uploaded too big';
            return json_encode($result);
        }

        // move uploaded file
        if ( ! file_exists($destination)) {
            mkdir($destination, 0755, TRUE);
        }
        $filename = time().rand(1000, 9999).$suffix;
        $file = rtrim($destination, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR. $filename;
        if ( ! @move_uploaded_file($file_info['tmp_name'], $file) ) {
            $result['error'] = 'upload destination error';
            return json_encode($result);
        }

        $result['success'] = TRUE;
        $result['filename'] = $filename;
        return json_encode($result);
    }
}

if ( ! function_exists('download'))
{
    /**
     * Doload file.
     * @param  string $filename the file you download
     * @return void
     */
    function download($filename)
    {
        if ( ! is_file($filename) OR ($filesize = @filesize($filename)) === FALSE) {
            return ;
        }

        // Generate the server headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'. basename($filename). '"');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.$filesize);
        header('Cache-Control: private, no-transform, no-store, must-revalidate');
        readfile($filename);

    }
}
