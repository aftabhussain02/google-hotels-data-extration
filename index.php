<?php
ignore_user_abort();
require 'vendor/autoload.php';

use Goutte\Client;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function outputProgress($current, $total) {
    echo "<span style='position: absolute;z-index:$current;background:#FFF;'>" . round($current / $total * 100) . "% </span>";
    myFlush();
}
function myFlush() {
    echo(str_repeat(' ', 256));
    if (@ob_get_contents()) {
        @ob_end_flush();
    }
    flush();
}
function downloadImages($dir, $url){
    $imageName=substr($url, strrpos($url, "/", -1) + 1);
    if (!file_exists('images/' . $dir . '/')) {
        mkdir('images/' . $dir, 0777, true);
    }
    $img='images/' . $dir . '/' . $imageName;
    file_put_contents($img, file_get_contents($url));
}

function getEmail($url){
    $curl=curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    $data=curl_exec($curl);
    curl_close($curl);
    preg_match_all('/<a [^>]*\bhref\s*=\s*"\K[^"]*contact[^"]*/', $data, $matches);
    $newUrl='';
    if(!empty($matches[0])) {
        if (strpos($matches[0][0], 'http') !== false) {
            $newUrl=$matches[0][0];
        } else {
            $newUrl=$url . '/' . $matches[0][0];
        }

        $curl=curl_init();
        curl_setopt($curl, CURLOPT_URL, $newUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $data=curl_exec($curl);
        curl_close($curl);
        preg_match_all("/([a-z0-9\.]{1,50}@[a-z0-9]{1,50}\.[a-z]{1,5})/ims", $data, $matches2);
        $email=[];
        foreach ($matches2[0] as $emails) {
            if (in_array($emails, $email) == false) {
                array_push($email, $emails);
            }
        }
        return implode($email, ', ');
    }else{
        return 'email is not available on site';
    }
}

function get_lat_long($address)
{


    $address=str_replace(" ", "+", $address);

    $key='AIzaSyDmGcyp1ZxeXnWq7JO8dPZTPJVyncFKBlA';

    $region='IN';

    $url="https://maps.google.com/maps/api/geocode/json?key=$key&address=$address+hotal+jaipur&sensor=false&region=$region";

    $ch=curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $response=curl_exec($ch);

    curl_close($ch);

    $response_a=json_decode($response);

    $placeid=$response_a->results[0]->place_id;

    $urlfordetail="https://maps.googleapis.com/maps/api/place/details/json?placeid=$placeid&key=$key";

    $ch2=curl_init();

    curl_setopt($ch2, CURLOPT_URL, $urlfordetail);

    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch2, CURLOPT_PROXYPORT, 3128);

    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);

    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);

    $response2=curl_exec($ch2);

    curl_close($ch2);

    $response_a2=json_decode($response2);

    $lat=$response_a2->result->geometry->location->lat;
    $long=$response_a2->result->geometry->location->lng;
    $phone=$response_a2->result->international_phone_number;
    $website=$response_a2->result->website;

    $latlon=array($lat, $long, $phone, $website);
    return $latlon;
}

$reader=\PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
$spreadsheet=$reader->load("data/trivago.csv");
$newSheet=new spreadsheet();
$ssheet=$newSheet->getActiveSheet();
$worksheet=$spreadsheet->getActiveSheet();
// Get the highest row and column numbers referenced in the worksheet
$highestRow=$worksheet->getHighestRow(); // e.g. 10
$highestColumn=$worksheet->getHighestColumn(); // e.g 'F'
$highestColumnIndex=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // e.g. 5

for ($row=1; $row <= $highestRow; ++$row) {
    $a='';
    for ($col=1; $col <= 19; ++$col) {
        if ($col == 1) {
            $a='A';
        } else {
            $a++;
        }
        $value=$worksheet->getCellByColumnAndRow($col, $row)->getValue();

        if ($a == 'A' and $row !== 1) {
            $lats=get_lat_long($value);
            //latitude
            $ssheet->setCellValue('T' . $row, $lats[0]);
            //longitude
            $ssheet->setCellValue('U' . $row, $lats[1]);
            //phone
            $ssheet->setCellValue('V' . $row, $lats[2]);
            if($lats[3] != '') {
                //website
                $ssheet->setCellValue('W' . $row, $lats[3]);
                //email
                $email=getEmail($lats[3]);
                $ssheet->setCellValue('X' . $row, $email);
            }
            $ssheet->setCellValue($a . $row, $value);
        } elseif ($a == 'C' and $row != 1 and !empty($value)) {
            $dir=$worksheet->getCell('A' . $row)->getValue();
            downloadImages($dir, $value);
            $ssheet->setCellValue($a . $row, $value);
        } elseif ($a == 'F' or $a == 'G' or $a == 'H' or $a == 'I' or $a == 'J') {
            $replace=preg_replace('/\s([A-Z])/', ', $1', $value);
            $replace=preg_replace('/\s([0-9])/', ', $1', $replace);
            $replace=preg_replace('(\/,)', '/', $replace);
            $ssheet->setCellValue($a . $row, $replace);
        }elseif ($a == 'S' and $row != 1){
            $star = substr_count($value,'<span class="icon-ic item__star">');
$value =  $star == 0 ? '-' : $star;
            $ssheet->setCellValue($a . $row, $value);
        } else {
            $ssheet->setCellValue($a . $row, $value);
        }
    }
    outputProgress($row,$highestRow);
}
//LATITUDE NAME SET
$ssheet->setCellValue('T1', 'latitude');
//LONGITUDE NAME SET
$ssheet->setCellValue('U1', 'longitude');
//PHONE NO NAME SET
$ssheet->setCellValue('V1', 'contact number');
//PHONE NO NAME SET
$ssheet->setCellValue('W1', 'website');
//PHONE NO NAME SET
$ssheet->setCellValue('X1', 'email');

$writer=new Xlsx($newSheet);
$writer->save('test.xlsx');

?>