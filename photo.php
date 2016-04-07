<?php

class carddavImages {

public $host = '192.168.0.172';
public $db_user = 'projecto';
public $db_pass = 'pro3dav5';
public $db_name = 'projectobook';
public $width = 500;
public $folder = '/home/eakhmetov/carddav/photo/';

function __construct() {
	$this->cardImages();
}

public function cardImages() {
		$mysqli = new mysqli($this->host, $this->db_user, $this->db_pass, $this->db_name);
		if ($result = $mysqli->query("SELECT ui.uid, ad.id, FROM_BASE64(ui.image) AS img FROM user_image ui JOIN addressbook ad ON ad.uid = ui.uid")) {
			while ($data = $result->fetch_assoc()) {
				$im  = imagecreatefromstring($data['img']);
				$koff = imagesx($im)/$this->width;
				$height = (int) imagesy($im)/$koff;
				$thumb = imagecreatetruecolor($this->width, $height);
				imagecopyresized($thumb, $im, 0, 0, 0, 0, $this->width, $height, imagesx($im), imagesy($im));
				if (imagejpeg($thumb, $this->folder.$data['uid'].".jpg")) {
					
				}
				imagedestroy($thumb);
			}
		}
	}
}

$q = new carddavImages();

