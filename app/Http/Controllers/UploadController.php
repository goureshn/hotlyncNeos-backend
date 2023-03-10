<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use File;


class UploadController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function showExcelInfo()
    {
        $this->parseExcelFile(public_path() . '\uploads\csv\csv_1460808698.xlsx');
    }

    public function parseExcelFile($path)
    {

    }

    function upload(Request $request)
    {
        // ini_set("post_max_size", "1024M");
        // ini_set("upload_max_filesize", "1024M");
        // ini_set("memory_limit", "1024M");
        $output_dir = "uploads/csv/";

        if(!File::isDirectory(public_path($output_dir)))
            File::makeDirectory(public_path($output_dir), 0777, true, true);

        $ret = array();

        $filekey = 'myfile';

        if ($request->hasFile($filekey) === false)
            return "No input file";

        if ($request->file($filekey)->isValid() === false)
            return "No valid file";

        //You need to handle  both cases
        //If Any browser does not support serializing of multiple files using FormData()
        if (!is_array($_FILES[$filekey]["name"])) //single file
        {
            $fileName = $_FILES[$filekey]["name"];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "csv_" . time() . "." . strtolower($ext);

            $dest_path = $output_dir . $filename1;
            move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);
            $ret = $this->parseExcelFile($dest_path);
            unlink($dest_path);

            return $ret;
        } else  //Multiple files, file[]
        {
            $fileCount = count($_FILES[$filekey]["name"]);
            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = $_FILES[$filekey]["name"][$i];
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $filename1 = "csv_" . time() . "." . strtolower($ext);

                $dest_path = $output_dir . $filename1;
                move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);
                $this->parseExcelFile($dest_path);
                unlink($dest_path);
            }
        }

        echo $ret[0];//json_encode($ret);

    }

    function mobileprofilephoto(Request $request)
    {
        $output_dir = "uploads/picture/";

        if(!File::isDirectory(public_path($output_dir)))
            File::makeDirectory(public_path($output_dir), 0777, true, true);

        $ret = array();

        $filekey = 'myfile';

        if ($request->hasFile($filekey) === false) {
            $ret['code'] = 201;
            $ret['message'] = "No input file";
            $ret['content'] = "";
            return Response::json($ret);
        }


        //You need to handle  both cases
        //If Any browser does not support serializing of multiple files using FormData()
        if (!is_array($_FILES[$filekey]["name"])) //single file
        {
            if ($request->file($filekey)->isValid() === false) {
                $ret['code'] = 202;
                $ret['message'] = "No valid file";
                return Response::json($ret);
            }

            $fileName = $_FILES[$filekey]["name"];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "pic_" . time() . "." . strtolower($ext);

            $dest_path = $output_dir . $filename1;

            move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);

            $ret['code'] = 200;
            $ret['message'] = "File is uploaded successfully";
            $ret['content'] = $dest_path;
            return Response::json($ret);
        } else  //Multiple files, file[]
        {
            $filename = array();
            $fileCount = count($_FILES[$filekey]["name"]);
            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = $_FILES[$filekey]["name"][$i];
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $filename1 = "pic_" . time() . '_' . ($i + 1) . "." . strtolower($ext);

                $dest_path = $output_dir . $filename1;
                move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);
                $filename[$i] = $dest_path;
            }

            $ret['code'] = 200;
            $ret['message'] = "File is uploaded successfully";
            $ret['content'] = $dest_path;
            return Response::json($ret);
        }
    }

    function uploadpicture(Request $request)
    {
        // $output_dir = "uploads/picture/";
        $output_dir = "uploads/chat/images/";

        if(!File::isDirectory(public_path($output_dir)))
            File::makeDirectory(public_path($output_dir), 0777, true, true);

        $ret = array();

        $filekey = 'myfile';

        if ($request->hasFile($filekey) === false) {
            $ret['code'] = 201;
            $ret['message'] = "No input file";
            $ret['content'] = array();
            return Response::json($ret);
        }


        //You need to handle  both cases
        //If Any browser does not support serializing of multiple files using FormData()
        if (!is_array($_FILES[$filekey]["name"])) //single file
        {
            if ($request->file($filekey)->isValid() === false) {
                $ret['code'] = 202;
                $ret['message'] = "No valid file";
                return Response::json($ret);
            }

            $fileName = $_FILES[$filekey]["name"];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "pic_" . time() . "." . strtolower($ext);

            $dest_path = $output_dir . $filename1;

            move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);

            $ret['code'] = 200;
            $ret['message'] = "File is uploaded successfully";
            $ret['content'] = $dest_path;
            return Response::json($ret);
        } else  //Multiple files, file[]
        {
            $filename = array();
            $fileCount = count($_FILES[$filekey]["name"]);
            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = $_FILES[$filekey]["name"][$i];
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $filename1 = "pic_" . time() . '_' . ($i + 1) . "." . strtolower($ext);

                $dest_path = $output_dir . $filename1;
                move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);
                $filename[$i] = $dest_path;
            }

            $ret['code'] = 200;
            $ret['message'] = "File is uploaded successfully";
            $ret['content'] = $filename;
            return Response::json($ret);
        }
    }


}
