<?php
class PreController {

    function __construct() {
        //echo "hook init..\n";
        if(!empty($_SERVER["REQUEST_URI"])) {
            log_message('debug', "API PATH: ".$_SERVER["REQUEST_URI"]);
            log_message('debug', "API param: ".file_get_contents("php://input"));
        } else if(!empty($_SERVER["argv"])) {
            log_message('debug', "_SERVER['argv']: ".print_r($_SERVER['argv'], true));
        }
    }

    public function loginCheck()
    {
        // echo "loginCheck ...\n";

        // $data = file_get_contents("php://input");

        // var_dump($data);

        // exit;
        //return;
    }
}
?>