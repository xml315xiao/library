<?php  defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('directory_map'))
{
    /**
     * Create a Directory Map
     *
     * Reads the specified directory and builds an array
     * representation of it. Sub-folders contained with the
     * directory will be mapped as well.
     *
     * @param	string	$source_dir		Path to source
     * @param	int	$directory_depth	Depth of directories to traverse
     *				        (<=0 fully recursive, 1 : current dir, 2 : most 2 depth  etc)
     * @param	bool	$hidden			Whether to show hidden files
     * @return	array
     */
    function directory_map($source_dir, $directory_depth = 0, $hidden = FALSE)
    {
        if ( ! is_dir($source_dir)) {
            return FALSE;
        }

        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $directory_depth = $directory_depth - 1;
        $handle = @opendir($source_dir);
        $files  = array();

        while (FALSE !== ($file = readdir($handle)))
        {
            if ($file === '.' OR $file === '..' OR ($hidden === FALSE && $file[0] === '.')) {
                continue;
            }

            if (is_dir($source_dir. $file) && 0 !== $directory_depth) {
                $files[$file] = directory_map($source_dir. $file, $directory_depth, $hidden);
            } else {
                $files[] = $file;
            }
        }

        closedir($handle);
        return $files;
    }

}

if ( ! function_exists('fetch_files'))
{
    /**
     * Get file in directory
     *
     * Reads the specified directory and builds an array containing the filenames.
     * Any sub-folders contained within the specified path are read as well.
     *
     * @param	string	$source_dir path to source
     * @param	bool	$include_path whether to include the path as part of the filename
     * @return	array
     */
    function fetch_files($source_dir, $include_path = FALSE)
    {
        static $files = array();

        if ( ! is_dir($source_dir)) {
            return FALSE;
        }
        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $handle = @opendir($source_dir);
        while (FALSE !== ($file = readdir($handle)))
        {
            if ($file === '.' OR $file === '..') {
                continue;
            }

            if (is_dir($source_dir. $file)) {
                fetch_files($source_dir.$file.DIRECTORY_SEPARATOR, $include_path);
            } else {
                $files[] = ($include_path === TRUE) ? $source_dir.$file : $file;
            }
        }

        closedir($handle);
        return $files;

    }
}

if ( ! function_exists('create_dir'))
{
    /**
     * Create a Directory
     *
     * @param $path
     * @return bool
     */
    function create_dir($path)
    {
        if (file_exists($path)) {
            return FALSE;
        }

        return mkdir($path, 0755, TRUE);
    }
}

if ( ! function_exists('create_file'))
{
    /**
     * Create a file
     *
     * @param $filename
     * @param bool $overwrite Whether overwrite the file exist.
     * @return bool
     */
    function create_file($filename, $overwrite = FALSE)
    {
        if (file_exists($filename) && $overwrite === FALSE) {
            return FALSE;
        }

        if (file_exists($filename) && $overwrite === TRUE) {
            unlink($filename);
        }

        @mkdir(dirname($filename), 0755, TRUE);

        return touch($filename);
    }
}

if ( ! function_exists('delete_file'))
{
    /**
     * Delete a file
     * @param $filename
     * @return bool
     */
    function delete_file($filename)
    {
        if ( ! file_exists($filename)) {
            return FALSE;
        }

        return unlink($filename);
    }
}

if ( ! function_exists('copy_file'))
{
    /**
     * Copy a file
     * @param string $filename
     * @param string $path
     * @param bool $overwrite
     * @return bool
     */
    function copy_file($filename, $path, $overwrite = TRUE)
    {
        if ( ! file_exists($filename)) {
            return FALSE;
        }
        if ( ! file_exists($path) && $overwrite === FALSE) {
            return FALSE;
        }
        if ( ! file_exists($path) && $overwrite === TRUE) {
            unlink($path);
        }
        $directory = dirname($path);
        create_dir($directory);

        return copy($filename, $path);
    }
}

if ( ! function_exists('copy_dir'))
{
    /**
     * Copy a dir
     * @param string $source
     * @param string $path
     * @return bool
     */
    function copy_dir($source, $path)
    {
        if ( ! is_dir($source) || is_file($path)) {
            return FALSE;
        }
        if ( ! file_exists($path)) {
            create_dir($path);
        }
        $source = rtrim($source, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $path   = rtrim($path, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $handle = @opendir($source);
        while (FALSE !== ($filename = readdir($handle)))
        {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (is_dir($source. $filename)) {
                copy_dir($source. $filename, $path. $filename);
            } else {
                copy_file($source. $filename, $path. $filename);
            }
        }

        closedir($handle);
        return TRUE;
    }
}

if ( ! function_exists('move_dir'))
{
    /**
     * Move a dir
     * @param string $source
     * @param string $path
     * @return bool
     */
    function move_dir($source, $path)
    {
        if ( ! is_dir($source) || is_file($path)) {
            return FALSE;
        }
        if ( ! file_exists($path)) {
            create_dir($path);
        }
        $source = rtrim($source, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $path   = rtrim($path, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $handle = @opendir($source);
        while (FALSE !== ($filename = readdir($handle)))
        {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (is_dir($source. $filename)) {
                move_dir($source. $filename, $path. $filename);
            } else {
                rename($source. $filename, $path. $filename);
            }
        }

        closedir($handle);
        return rmdir($source);
    }
}

if ( ! function_exists('delete_dir'))
{
    /**
     * Delete a directory
     * @param string $directory
     * @return bool
     */
    function delete_dir($directory)
    {
        if ( ! file_exists($directory) || is_dir($directory)) {
            return FALSE;
        }
        $directory = rtrim($directory, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $handle = @opendir($directory);
        while (FALSE !== ($filename = readdir($handle)))
        {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (is_dir($directory. $filename)) {
                delete_dir($directory. $filename);
            } else {
                unlink($directory. $filename);
            }
        }

        closedir($handle);
        return @rmdir($directory);
    }
}

if ( ! function_exists('get_file_info'))
{
    /**
     * Get the main information of the file.
     * @param string $file the path of the file
     * @return array
     */
    function get_file_info($file)
    {
        if ( ! file_exists($file)) {
            return FALSE;
        }

        return [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'date' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
}

if ( ! function_exists('get_dir_info'))
{
    /**
     * Get the main information of the directory.
     * @param string $source_dir the path of the directory
     * @param int $directory_depth show depth of the directory
     * @param bool $hidden whether show the hidden files
     * @return array
     */
    function get_dir_info($source_dir, $directory_depth = 0, $hidden = FALSE)
    {
        if ( ! is_dir($source_dir)) {
            return FALSE;
        }

        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $directory_depth = $directory_depth - 1;
        $handle = @opendir($source_dir);
        $files  = array();

        while (FALSE !== ($file = readdir($handle)))
        {
            if ($file === '.' OR $file === '..' OR ($hidden === FALSE && $file[0] === '.')) {
                continue;
            }

            if (is_dir($source_dir. $file) && 0 !== $directory_depth) {
                $files[$file] = get_dir_info($source_dir. $file, $directory_depth, $hidden);
            } else {
                $files[$file] = get_file_info($source_dir. $file);
            }
        }

        closedir($handle);
        return $files;
    }
}

if ( ! function_exists('get_files'))
{
    /**
     * Get the name of files in directory
     * @param string $source_dir the path of the directory
     * @param bool $show_path whether show the directory path
     * @return array
     */
    function get_files($source_dir, $show_path = FALSE)
    {
        static $files = array();
        if ( ! is_dir($source_dir)) {
            return FALSE;
        }

        $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $handle = @opendir($source_dir);

        while (FALSE !== ($file = readdir($handle)))
        {
            if ($file === '.' OR $file === '..' OR $file[0] === '.') {
                continue;
            }

            if (is_dir($source_dir. $file)) {
                get_files($source_dir. $file, $show_path);
            } else {
                $files[] = (FALSE !== $show_path) ? $source_dir. $file : $file;
            }
        }

        closedir($handle);
        return $files;
    }
}
