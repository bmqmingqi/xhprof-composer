<?php


namespace xhprof\xhprof_lib\utils;


use xhprof\xhprof_lib\iXHProfRuns;

class xhprofRunsDefault implements iXHProfRuns
{
    private $dir = '';
    private $suffix = 'xhprof';

    private function gen_run_id($type)
    {
        return uniqid();
    }

    private function file_name($run_id, $type)
    {

        $file = "$run_id.$type." . $this->suffix;

        if (!empty($this->dir)) {
            $file = $this->dir . "/" . $file;
        }
        return $file;
    }

    public function __construct($dir = null)
    {

        // if user hasn't passed a directory location,
        // we use the xhprof.output_dir ini setting
        // if specified, else we default to the directory
        // in which the error_log file resides.

        if (empty($dir) && !($dir = getenv('XHPROF_OUTPUT_DIR'))) {
            $dir = ini_get("xhprof.output_dir");
            if (empty($dir)) {

                $dir = sys_get_temp_dir();

                $this->xhprof_error("Warning: Must specify directory location for XHProf runs. " .
                    "Trying {$dir} as default. You can either pass the " .
                    "directory location as an argument to the constructor " .
                    "for XHProfRuns_Default() or set xhprof.output_dir " .
                    "ini param, or set XHPROF_OUTPUT_DIR environment variable.");
            }
        }
        $this->dir = $dir;
    }

    public function get_run($run_id, $type, &$run_desc)
    {
        $file_name = $this->file_name($run_id, $type);

        if (!file_exists($file_name)) {
            xhprof_error("Could not find file $file_name");
            $run_desc = "Invalid Run Id = $run_id";
            return null;
        }

        $contents = file_get_contents($file_name);
        $run_desc = "XHProf Run (Namespace=$type)";
        return unserialize($contents);
    }

    public function save_run($xhprof_data, $type, $run_id = null)
    {

        // Use PHP serialize function to store the XHProf's
        // raw profiler data.
        $xhprof_data = serialize($xhprof_data);

        if ($run_id === null) {
            $run_id = $this->gen_run_id($type);
        }

        $file_name = $this->file_name($run_id, $type);
        $file = fopen($file_name, 'w');

        if ($file) {
            fwrite($file, $xhprof_data);
            fclose($file);
        } else {
            xhprof_error("Could not open $file_name\n");
        }

        // echo "Saved run in {$file_name}.\nRun id = {$run_id}.\n";
        return $run_id;
    }

    function list_runs()
    {
        if (is_dir($this->dir)) {
            echo "<hr/>Existing runs:\n<ul>\n";
            $files = glob("{$this->dir}/*.{$this->suffix}");
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            foreach ($files as $file) {
                list($run, $source) = explode('.', basename($file));
                echo '<li><a href="' . htmlentities($_SERVER['SCRIPT_NAME'])
                    . '?run=' . htmlentities($run) . '&source='
                    . htmlentities($source) . '">'
                    . htmlentities(basename($file)) . "</a><small> "
                    . date("Y-m-d H:i:s", filemtime($file)) . "</small></li>\n";
            }
            echo "</ul>\n";
        }
    }

    function xhprof_error($message)
    {
        if (!function_exists('xhprof_error')) {
            function xhprof_error($message)
            {
                error_log($message);
            }
        }
    }
}