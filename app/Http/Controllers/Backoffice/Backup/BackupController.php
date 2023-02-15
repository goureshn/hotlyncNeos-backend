<?php

namespace App\Http\Controllers\Backoffice\backup;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use File;


use Redirect;
use DB;
use Response;
use Datatables;

class BackupController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    public function getDaily()
    {
        $dir = public_path().'/backup/daily/';

        if(!File::isDirectory($dir))
            File::makeDirectory($dir, 0777, true, true);

        $dailyfiles = array();
        $files = array();
        $i = 0;
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $ctime = filectime($dir . $file);
                    $files[$ctime] = $file;
                }
            }
            closedir($handle);
            // sort
            if(count($files) > 0) {
                sort($files);
                // find the last modification
                //$reallyLastModified = end($files);
                foreach ($files as $file) {
                    $lastModified = date('F d Y, H:i:s', filectime($dir . $file));
                    $i++;
                    $dailyfiles[] = array('id' => $i, 'filename' => $file, 'date' => $lastModified);
                }
            }
        }
        return json_encode($dailyfiles);
    }
    public function getWeekly()
    {
        $dir = public_path().'/backup/weekly/';
        
        if(!File::isDirectory($dir))
            File::makeDirectory($dir, 0777, true, true);

        $dailyfiles = array();
        $files = array();
        $i = 0;
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $ctime = filectime($dir . $file);
                    $files[$ctime] = $file;
                }
            }
            closedir($handle);
            // sort
            if(count($files) > 0) {
                sort($files);
                // find the last modification
                //$reallyLastModified = end($files);
                foreach ($files as $file) {
                    $lastModified = date('F d Y, H:i:s', filectime($dir . $file));
                    $i++;
                    $dailyfiles[] = array('id' => $i, 'filename' => $file, 'date' => $lastModified);
                }
            }
        }
        return json_encode($dailyfiles);
    }
    public function getMonthly()
    {
        $dir = public_path().'/backup/monthly/';

        if(!File::isDirectory($dir))
            File::makeDirectory($dir, 0777, true, true);

        $dailyfiles = array();
        $files = array();
        $i = 0;
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $ctime = filectime($dir . $file);
                    $files[$ctime] = $file;
                }
            }
            closedir($handle);
            // sort
            if(count($files) > 0) {
                sort($files);
                // find the last modification
                //$reallyLastModified = end($files);
                foreach ($files as $file) {
                    $lastModified = date('F d Y, H:i:s', filectime($dir . $file));
                    $i++;
                    $dailyfiles[] = array('id' => $i, 'filename' => $file, 'date' => $lastModified);
                }
            }
        }
        $dailyfiles = array();
        return json_encode($dailyfiles);
    }

}