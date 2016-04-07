<?php
class Check172 {
    public $host = '192.168.0.172';
    public $db_user = 'projecto';
    public $db_pass = 'pro3dav5';
    public $db_name = 'projectobook';
    public $error_string;
    public $mail_tos = array('eakhmetov@ecocompany.ru', 'oprokopyev@ecocompany.ru');

    function __construct() {
        $this->checkData();
        $this->sendMail();
    }
    
    private function checkData() {
        $mysqli = new mysqli($this->host, $this->db_user, $this->db_pass, $this->db_name);
        if ($result = $mysqli->query("SELECT * FROM addressbook")) {
            while ($data = $result->fetch_assoc()) {
               $ch = $mysqli->query("SELECT timestamp FROM user_add_info WHERE uid = '".$data['uid']."'");
               $data2 = $ch->fetch_row();
               if (empty($data2[0])) {
                   $this->error_string.= $data['lastname']." ".$data['firstname']." - ".$data['organization']." (".$data['jobtitle'].")"."\n\r";
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
}

new Check172();
