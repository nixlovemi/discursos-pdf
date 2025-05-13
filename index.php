<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
# require_once __DIR__ . '/app/helpers/GoogleClient.php';
# require_once __DIR__ . '/app/helpers/DateUtil.php';
# require_once __DIR__ . '/app/pdf/Speeches.php';

use App\Helpers\GoogleClient;
use App\Pdf\Speeches;
use \Carbon\Carbon;

// variables
$months = isset($_GET['months']) && $_GET['months'] > 0 ? $_GET['months']: 3;

// file vars
$fileName = 'Discursos.pdf';
$fileFullPath = __DIR__ . '/' . $fileName;

// update every 30 min
$fileTimestamp = (file_exists($fileFullPath)) ? filemtime($fileFullPath): 0;
$now = strtotime(date('Y-m-d H:i:s'));
$diffMinutes = round(abs($now - $fileTimestamp) / 60, 2);

if ($diffMinutes > 30 || TRUE) { #skipping for now
    // Today + next {variable} months
    $start = Carbon::now()->startOfMonth()->format('Y-m-d');
    $end = Carbon::now()->addMonths($months)->endOfMonth()->format('Y-m-d');

    $useLocalFile = true;
    $GC = new GoogleClient($useLocalFile);
    $Speeches = new Speeches($GC->getSpeechData(), $start, $end);
    $PDF = $Speeches->getPdf();

    // Save PDF document
    $PDF->Output($fileFullPath, 'F');
}

// send file to browser
header('Pragma: no-cache');
header('Content-type:application/pdf');
header('Content-disposition: inline; filename="'.$fileName.'"');
header('content-Transfer-Encoding:binary');
header('Accept-Ranges:bytes');
readfile($fileFullPath);