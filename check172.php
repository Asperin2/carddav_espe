<?php
class Check172 {
    public $host = '192.168.0.172';
    public $db_user = 'projecto';
    public $db_pass = 'pro3dav5';
    public $db_name = 'projectobook';
    public $host_projecto = 'localhost';
    public $db_user_projecto = 'root';
    public $db_pass_projecto = 'root';
    public $db_name_projecto = 'org_app';
    public $error_arr = array();
    public $error_string;
    public $mail_tos = array('eakhmetov@ecocompany.ru', 'oprokopyev@ecocompany.ru');

    function __construct() {
        $this->checkData();
        $this->checkActive();
        $this->makeString();
       //echo $this->error_string;
       $this->sendMail();
    }
    
    private function checkData() {
        $mysqli = new mysqli($this->host, $this->db_user, $this->db_pass, $this->db_name);
        if ($result = $mysqli->query("SELECT * FROM addressbook")) {
            while ($data = $result->fetch_assoc()) {
               $ch = $mysqli->query("SELECT timestamp FROM user_add_info WHERE uid = '".$data['uid']."'");
               $data2 = $ch->fetch_row();
               if (empty($data2[0])) {
                   $this->error_arr[$data['uid']]['lastname'] = $data['lastname'];
                   $this->error_arr[$data['uid']]['firstname'] = $data['firstname'];
                   $this->error_arr[$data['uid']]['organization'] = $data['organization'];
                   $this->error_arr[$data['uid']]['jobtitle'] = $data['jobtitle'];
               }
            }
       }
    }
    
    private function sendMail() {
        $headers = 'From: projecto@ecocompany.ru' . "\r\n" .
                   'Reply-To: projecto@ecocompany.ru' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        $subject = "Error 172 server";
    
        foreach ($this->mail_tos as $mail) {
            mail($mail, $subject, $this->error_string, $headers);
        }
    }
    
    private function makeString() {
        foreach ($this->error_arr as $data) {
            $this->error_string.= $data['lastname']." ".$data['firstname']." - ".$data['organization']." (".$data['jobtitle'].")"." - ".$data['status_user']."\n\r";
        }
    }
    
    private function checkActive() {
        $mysqli2 = new mysqli($this->host_projecto, $this->db_user_projecto, $this->db_pass_projecto, $this->db_name_projecto);
        foreach ($this->error_arr as $uid => $data) {
            $ch2 = $mysqli2->query("SELECT status, id FROM users WHERE users.carddav_uid = '".$uid."'") OR die(mysqli_error($mysqli2));
            $uid_arr2 = $ch2->fetch_assoc();
            if (!empty($uid_arr2)) {
                if ($uid_arr2['status'] != 0) {
                    $this->error_arr[$uid]['status_user'] = 'Не активен в PROJECTO'; 
                } else {
                    $ch3 = $mysqli2->query("SELECT type FROM vacations WHERE user_id = '".$uid_arr2['id']."' AND time_end > NOW()") OR die(mysqli_error($mysqli2));
                    $voc = $ch3->fetch_assoc();
                    if (!empty($voc) AND $voc['type'] == 4) {
                        $this->error_arr[$uid]['status_user'] = 'В декрете'; 
                    } else {
                        $this->error_arr[$uid]['status_user'] = 'Активен в PROJECTO'; 
                    }
                }
            } else {
               $this->error_arr[$uid]['status_user'] = 'Нет в PROJECTO'; 
            }
        }
    }
}

new Check172();
