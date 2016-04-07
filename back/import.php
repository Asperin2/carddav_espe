<?php

class convertToOwncloud
{

    public $host = '192.168.0.172';
    public $db_user = 'projecto';
    public $db_pass = 'pro3dav5';
    public $db_name = 'projectobook';
    public $host_carddav = 'localhost';
    public $db_user_carddav = 'root';
    public $db_pass_carddav = 'Be61HaP';
    public $db_name_carddav = 'owncloud';
    public $groups = array('emploers' => 'Сотрудники', 'dissmised' => 'Уволенные', 'child' => 'Декретники');
    const CELL_PHONES = 1;
    const WORK_PHONES = 2;
    const HOME_PHONES = 3;
    const WORK_EMAILS = 1;
    const PRIVATE_EMAILS = 2;
    const WORK_ADDRESSES = 1;
    const HOME_ADDRESSES = 2;

    public function importDb()
    {           
        $group_choice = array();
        date_default_timezone_set("Europe/Moscow");
        $mysqli = new mysqli($this->host, $this->db_user, $this->db_pass, $this->db_name);
        if ($result = $mysqli->query("SELECT * FROM addressbook ad INNER JOIN user_add_info ui ON ad.uid = ui.uid")) {
            while ($data = $result->fetch_assoc()) {
                $vcard = "BEGIN:VCARD\nVERSION:3.0";
                $vcard .= "\nUID:" . $data['uid'];
                $vcard .= "\nPRODID:-//ownCloud//NONSGML Contacts 0.2.5//EN \nREV:" . date("Y-m-d\TH:i:sP");
                $vcard .= "\nN:" . $data['lastname'] . "\;" . $data['firstname'] . "\;" . $data['firstname2'] . "\;\;";
                $vcard .= "\nFN:" . $data['lastname'] . " " . $data['firstname'] . " " . $data['firstname2'];
                $vcard .= "\nORG:" . $data['organization'];
                $vcard .= "\nTITLE:" . $data['jobtitle'] . " (" . $data['department'] . ")";
                if (!empty($data['work_phones'])) {
                    $vcard .= $this->getPhones($data['work_phones'], self::WORK_PHONES);
                }
                if (!empty($data['cell_phones'])) {
                    $vcard .= $this->getPhones($data['cell_phones'], self::CELL_PHONES);
                }
                if (!empty($data['private_phones'])) {
                    $vcard .= $this->getPhones($data['private_phones'], self::HOME_PHONES);
                }

                if (!empty($data['work_emails'])) {
                    $vcard .= $this->getEmails($data['work_emails'], self::WORK_EMAILS);
                }
                /*
		if (!empty($data['private_emails'])) {
                    $vcard .= $this->getEmails($data['private_emails'], self::PRIVATE_EMAILS);
                }
		*/
                if (!empty($data['work_addresses'])) {
                    $vcard .= $this->getAddresses($data['work_addresses'], self::WORK_ADDRESSES);
                }
                
                if (!empty($data['home_addresses'])) {
                    $vcard.= $this->getAddresses($data['home_addresses'], self::HOME_ADDRESSES);
                }
                
                $vcard .= "\nBDAY:" . $data['birthdate'];
                $photo = base64_encode(file_get_contents('photo/' . $data['uid'] . '.jpg'));
                $vcard .= "\nPHOTO;ENCODING=b;TYPE=JPEG:" . $photo;
                $vcard .= "\nEND:VCARD";

                $this->writeDb($data, $vcard);
                if ($data['dismissal_date']) {
                    $group_choice['dissmised'][] = $data['uid'];
                } elseif ($data['decret']) {
                    $group_choice['child'][] = $data['uid'];
                } else {
                    $group_choice['emploers'][] = $data['uid'];
                }
            }
            $this->makeGroups($group_choice);
            $this->syncDone();
        }
    }

    /**
     * Телефоны
     */
    private function getPhones($phones_data, $type)
    {
        $phones = '';
        switch ($type) {
            case self::CELL_PHONES:
                $phones_title = "\nTEL;TYPE=CELL:";
                break;
            case self::WORK_PHONES:
                $phones_title = "\nTEL;TYPE=WORK:";
                break;
            case self::HOME_PHONES:
                $phones_title = "\nTEL;TYPE=HOME:";
                break;

            default:
                $phones_title = '';
                break;
        }
        $phones_array = explode('|', $phones_data);
        foreach ($phones_array as $phone) {
            $phones .= $phones_title . $phone;
        }
        return $phones;
    }

    /**
     * Почтовики
     */
    private function getEmails($emails_data, $type)
    {
        $emails = '';
        switch ($type) {
            case self::WORK_EMAILS:
                $emails = "\nEMAIL;TYPE=WORK:";
                break;
            case self::PRIVATE_EMAILS:
                $emails = "\nEMAIL;TYPE=HOME:";
                break;

            default:
                $emails = '';
                break;
        }
        $str = str_replace('|', ';', $emails_data);
        $emails .= $str;
        return $emails;
    }

    /**
     * Адреса
     */
    private function getAddresses($addresses_data, $type)
    {
        $addr = '';
        switch ($type) {
            case self::WORK_ADDRESSES:
                $addr = "\nADR;TYPE=WORK:";
                break;
            case self::HOME_ADDRESSES:
                $addr = "\nADR;TYPE=HOME:";
                break;

            default:
                $addr = '';
                break;
        }
        $str = str_replace('|', ';', $addresses_data);
        $addr .= $str;
        return $addr;
    }

    /**
     * Пишу в бд основные данные
     */
    private function writeDb($data, $card)
    {
        $mysqli2 = new mysqli($this->host_carddav, $this->db_user_carddav, $this->db_pass_carddav, $this->db_name_carddav);
        $mysqli2->query("SET NAMES utf8");
        $uri = $data['uid'] . ".vcf";
        $mysqli2->query("DELETE FROM oc_contacts_cards WHERE uri = '" . $uri . "'");
        $mysqli2->query("INSERT INTO oc_contacts_cards (`addressbookid` ,`fullname` ,`carddata`,`uri` ,`lastmodified`) 
			VALUES (1, '" . $data['firstname'] . " " . $data['firstname2'] . " " . $data['lastname'] . "', '" . $card . "', '" . $uri . "', " . time() . ")");
        $mysqli2->close();
    }

    /**
     * Запись для синка
     */
    private function syncDone()
    {
        $mysqli2 = new mysqli($this->host_carddav, $this->db_user_carddav, $this->db_pass_carddav, $this->db_name_carddav);
        $mysqli2->query("UPDATE `oc_contacts_addressbooks` SET `ctag` = " . time() . " WHERE `oc_contacts_addressbooks`.`id` =1 LIMIT 1 ");
    }

    /**
     * Делаем группы
     */
    private function makeGroups($group_choice)
    {
        $mysqli2 = new mysqli($this->host_carddav, $this->db_user_carddav, $this->db_pass_carddav, $this->db_name_carddav);
        $mysqli2->query("SET NAMES utf8");
        foreach ($this->groups as $group => $group_name) {
            $mysqli2->query("DELETE FROM oc_contacts_cards WHERE fullname = '" . $group_name . "'");
            $uid_g = $this->gen_uuid();

            $vcard = "BEGIN:VCARD\nVERSION:3.0";
            $vcard.= "\nUID:" . $uid_g;
            $vcard.= "\nPRODID:-//ownCloud//NONSGML Contacts 0.2.5//EN \nREV:" . date("Y-m-d\TH:i:sP");
            $vcard.= "\nN:" . $group_name . ";;;;";
            $vcard.= "\nFN:" . $group_name;
            $vcard.= "\nX-ADDRESSBOOKSERVER-KIND:group";
            foreach ($group_choice[$group] as $uid) {
                $vcard.= "\nX-ADDRESSBOOKSERVER-MEMBER:urn:uuid:" . $uid;
            }
            $vcard.= "\nEND:VCARD";
            $mysqli2->query("INSERT INTO oc_contacts_cards (`addressbookid` ,`fullname` ,`carddata`,`uri` ,`lastmodified`) 
               VALUES (1, '" . $group_name . "', '" . $vcard . "', '" . $uid_g.".vcf" . "', " . time() . ")"); 
            }

    }

    private function gen_uuid()
    {
        $uuid = array(
            'time_low' => 0,
            'time_mid' => 0,
            'time_hi' => 0,
            'clock_seq_hi' => 0,
            'clock_seq_low' => 0,
            'node' => array()
        );

        $uuid['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
        $uuid['time_mid'] = mt_rand(0, 0xffff);
        $uuid['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
        $uuid['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
        $uuid['clock_seq_low'] = mt_rand(0, 255);

        for ($i = 0; $i < 6; $i++) {
            $uuid['node'][$i] = mt_rand(0, 255);
        }

        $uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
            $uuid['time_low'],
            $uuid['time_mid'],
            $uuid['time_hi'],
            $uuid['clock_seq_hi'],
            $uuid['clock_seq_low'],
            $uuid['node'][0],
            $uuid['node'][1],
            $uuid['node'][2],
            $uuid['node'][3],
            $uuid['node'][4],
            $uuid['node'][5]
        );

        return $uuid;
    }


}

error_reporting(E_ALL);
$obj = new convertToOwncloud;
echo $obj->importDb() . "\r\n\r\n";