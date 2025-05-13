<?php

namespace App\Helpers;

use Google_Client;
use Google_Service_Sheets;
use App\Helpers\DateUtil;

// google-sheet@discursos-sheet.iam.gserviceaccount.com
// https://www.nidup.io/blog/manipulate-google-sheets-in-php-with-api
class GoogleClient
{
    private string $_jsonConfigPath = '_files/discursos-sheet-1495a3cdce6a.json';
    private string $_spreadsheetId = '1E52r0gZZ7YhqXC8t2WQTKvYS4-Mb8RI94_RnSIbBCUw';
    private Google_Client $_client;
    private Google_Service_Sheets $_service;
    private bool $_useLocalJson = false;

    public function __construct(bool $useLocalJson = false)
    {
        $this->_useLocalJson = $useLocalJson;
        if ($this->_useLocalJson) {
            return;
        }

        // configure the Google Client
        $this->_client = $this->getGoogleClient();

        // configure the Sheets Service
        $this->_service = new Google_Service_Sheets($this->_client);

        // the spreadsheet id can be found in the url
        // https://docs.google.com/spreadsheets/d/1E52r0gZZ7YhqXC8t2WQTKvYS4-Mb8RI94_RnSIbBCUw/edit#gid=1416740343
        // $spreadsheetId = '1E52r0gZZ7YhqXC8t2WQTKvYS4-Mb8RI94_RnSIbBCUw';
        // $spreadsheet = $service->spreadsheets->get($spreadsheetId);
    }

    public function getSpeechData(): array
    {
        if ($this->_useLocalJson) {
            return $this->getSpeechDataFromLocal();
        }

        return $this->getSpeechDataFromGoogle();
    }

    private function getSpeechDataFromLocal(): array
    {
        // read local csv
        $csv = array_map('str_getcsv', file("_files/nw_schedule.csv"));

        // get all rows
        $speechData = [];
        for ($i=1; $i<count($csv); $i++) {
            $row = $csv[$i];

            // Date,Congregation,PublicSpeaker,OutlineNumber,OutlineName,Song,SpeakerConfirmed,AvailableForHospitality,Notes,Chairman,WatchtowerReader,CustomWeekendAssignment1,CustomWeekendAssignment2,Hospitality
            $date = $row[0] ?? null;
            $congregation = $row[1] ?? null;
            $speaker = $row[2] ?? null;
            $speech = $row[4] ?? null;
            $president = $row[9] ?? null;
            $reader = $row[10] ?? null;
            $hospitality = $row[13] ?? null;

            $speechData[] = [
                'date' => $date,
                'dbDate' => DateUtil::convertDate(
                    $date,
                    DateUtil::NW_PUBLISHER_DATE,
                    DateUtil::STANDARD_DATE
                ),
                'speech' => $speech,
                'speaker' => $speaker,
                'congregation' => $congregation,
                'president' => $president,
                'reader' => $reader,
                'hospitality' => $hospitality,
            ];
        }

        return $speechData;
    }

    private function getSpeechDataFromGoogle(): array
    {
        // get all the rows of a sheet
        $range = 'Dados - Discursos'; // here we use the name of the Sheet to get all the rows
        $response = $this->_service->spreadsheets_values->get($this->_spreadsheetId, $range);
        $speechRawData = array_slice($response->getValues(), 13);

        $speechData = [];
        foreach ($speechRawData as $row) {
            $date = $row[0] ?? null;
            $speech = $row[1] ?? null;
            $speaker = $row[2] ?? null;
            $congregation = $row[3] ?? null;
            $president = $row[4] ?? null;
            $reader = $row[5] ?? null;

            $speechData[] = [
                'date' => $date,
                'dbDate' => DateUtil::convertDate(
                    $date,
                    DateUtil::BR_DATE,
                    DateUtil::STANDARD_DATE
                ),
                'speech' => $speech,
                'speaker' => $speaker,
                'congregation' => $congregation,
                'president' => $president,
                'reader' => $reader,
            ];
        }

        return $speechData;
    }

    private function getGoogleClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');

        // credentials.json is the key file we downloaded while setting up our Google Sheets API
        $client->setAuthConfig($this->_jsonConfigPath);

        return $client;
    }
}