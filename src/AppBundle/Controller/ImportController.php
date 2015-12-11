<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Result;
use AppBundle\Entity\Team;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Ddeboer\DataImport\Reader\CsvReader;
use Symfony\Component\Finder\SplFileInfo;

class ImportController extends Controller
{

    /**
     * @Route("/import/update-everything")
     */
    public function updateEverything(){
        echo $this->importResultsAction();
        flush(); sleep(5);
        echo $this->importFixturesAction();
        flush(); sleep(5);
        die(print_r("Complete!", true));
    }

    /**
     * @Route("/import/csvs")
     */
    public function importCSVsAction()
    {
        ini_set('memory_limit', '-1');
        ini_set ('max_execution_time', 60000);
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/../../../app/Resources/csv_data/15_16')->name('*.csv');
        $em = $this->getDoctrine()->getManager();
        $continue_count = 0;
        $new_count = 0;

        // look through results and find new teams, ensure no duplications
        $teams_array = array();
        $new_teams_array = array();
        $teams = $em->getRepository('AppBundle:Team')->findAll();
        foreach ($teams as $team)
        {
            $teams_array[$team->getName()] = $team;
        }
        echo "TEAMS ARRAY BUILT<BR />";

        // build a monster results array so we don't have to keep making queries to check if it's already saved
        $all_results = $em->getRepository('AppBundle:Result')->findAll();
        $all_existing_results = array();
        foreach ($all_results as $existing_result)
        {
            $all_existing_results[$existing_result->getMatchDate()->format('d/m/Y')][$existing_result->getHometeam()->getId()][$existing_result->getAwayteam()->getId()] = $existing_result;
        }

        echo "RESULTS ARRAY BUILT<BR />";

        flush(); sleep(5);

        foreach ($finder as $file)
        {
            $reader = new CsvReader($file->openFile());
            $reader->setStrict(false);
            $reader->setHeaderRowNumber(0, CsvReader::DUPLICATE_HEADERS_INCREMENT);

            $count = 0;
            foreach ($reader as $row) {
                $count++;
                if(!is_array($row)){
                    continue;
                }
                if(array_key_exists('HomeTeam', $row) && array_key_exists('AwayTeam', $row) ){
                    $new_teams_array[] = $row['HomeTeam'];
                    $new_teams_array[] = $row['AwayTeam'];
                }else{
                    $new_teams_array[] = $row['HT'];
                    $new_teams_array[] = $row['AT'];
                }
            }

            $new_teams_array = array_unique($new_teams_array);
            foreach ($new_teams_array as $new_team)
            {
                if(!in_array($new_team, array_keys($teams_array))){
                    $team = new Team();
                    $team->setName($new_team);
                    $em->persist($team);
                    $teams_array[$team->getName()] = $team;
                }
            }
            $em->flush();

            // now loop through and save results, sloowwlly
            foreach ($reader as $row) {
                if(!is_array($row)){
                    continue;
                }
                if(array_key_exists('HomeTeam', $row) && array_key_exists('AwayTeam', $row) ){
                    $hometeam = $row['HomeTeam'];
                    $awayteam = $row['AwayTeam'];
                }else{
                    $hometeam = $row['HT'];
                    $awayteam = $row['AT'];
                }
                $hometeam = $teams_array[$hometeam];
                $awayteam = $teams_array[$awayteam];
                $date1 = new \DateTime();
                $date = $date1->createFromFormat('d/m/y', $row['Date']);
                if(!is_object($date)){
                    $date = $date1->createFromFormat('d/m/Y', $row['Date']);
                    if(!is_object($date)){
                        $date = null;
                        continue;
                    }
                }

                if(array_key_exists($date->format('d/m/Y'), $all_existing_results) && array_key_exists($hometeam->getId(), $all_existing_results[$date->format('d/m/Y')]) && array_key_exists($awayteam->getId(), $all_existing_results[$date->format('d/m/Y')][$hometeam->getId()])){
                    $continue_count++;
                    continue;
                }else{
                    $result = new Result();
                    $result->setSeason($file->getPathInfo()->getFilename());
                    $result->setLeagueCode($row['Div']);
                    $result->setAwayScore($row['FTAG']);
                    $result->setHomeScore($row['FTHG']);
                    $result->setResult($row['FTR']);
                    $result->setHometeam($hometeam);
                    $result->setAwayteam($awayteam);
                    $result->setMatchDate($date);
                    $em->persist($result);
                    $new_count++;
                }

            }
            $em->flush();
            echo "Continue count: $continue_count<br />";
            echo "New count: $new_count<br />";
            flush(); sleep(5);

        }
        die(print_r('Success', true));


    }

    /**
     * @Route("/import/fixtures")
     */
    public function importFixturesAction()
    {
        ini_set('memory_limit', '-1');
        ini_set ('max_execution_time', 60000);
        $em = $this->getDoctrine()->getManager();

        $this->downloadFixturesFile();
        $file = new \SplFileObject('data/fixtures/'.date("Y-m-d").'.csv');
        $reader = new CsvReader($file);
        $reader->setStrict(false);
        $reader->setHeaderRowNumber(0, CsvReader::DUPLICATE_HEADERS_INCREMENT);

        $league_codes = array(
            'E0', 'E1', 'E2', 'E3'
        );
        $fixture_count = 0;

        $teams_array = array();
        $teams = $em->getRepository('AppBundle:Team')->findAll();
        foreach ($teams as $team)
        {
            $teams_array[$team->getName()] = $team;
        }

        $all_results = $em->getRepository('AppBundle:Result')->findAll();
        $all_existing_results = array();
        foreach ($all_results as $existing_result)
        {
            $all_existing_results[$existing_result->getMatchDate()->format('d/m/Y')][$existing_result->getHometeam()->getId()][$existing_result->getAwayteam()->getId()] = $existing_result;
        }

        foreach ($reader as $row) {
            if(!is_array($row)){
                continue;
            }
            if(in_array($row['Div'], $league_codes)){

                if(array_key_exists('HomeTeam', $row) && array_key_exists('AwayTeam', $row) ){
                    $hometeam = $row['HomeTeam'];
                    $awayteam = $row['AwayTeam'];
                }else{
                    $hometeam = $row['HT'];
                    $awayteam = $row['AT'];
                }
                $hometeam = $teams_array[$hometeam];
                $awayteam = $teams_array[$awayteam];
                $date1 = new \DateTime();
                $date = $date1->createFromFormat('d/m/y', $row['Date']);
                if(!is_object($date)){
                    $date = $date1->createFromFormat('d/m/Y', $row['Date']);
                    if(!is_object($date)){
                        $date = null;
                        continue;
                    }
                }

                if(array_key_exists($date->format('d/m/Y'), $all_existing_results) && array_key_exists($hometeam->getId(), $all_existing_results[$date->format('d/m/Y')]) && array_key_exists($awayteam->getId(), $all_existing_results[$date->format('d/m/Y')][$hometeam->getId()])){
                    continue;
                }else{
                    $result = new Result();
                    $result->setSeason('15_16');
                    $result->setLeagueCode($row['Div']);
                    $result->setHometeam($hometeam);
                    $result->setAwayteam($awayteam);
                    $result->setMatchDate($date);
                    $em->persist($result);
                    $fixture_count++;
                }
            }

        }
        $em->flush();
        echo "New fixtures added: $fixture_count<br />";
        flush(); sleep(5);

        die(print_r('Success', true));

    }

    /**
     * @Route("/import/results")
     */
    public function importResultsAction()
    {
        ini_set('memory_limit', '-1');
        ini_set ('max_execution_time', 60000);
        $em = $this->getDoctrine()->getManager();

        $league_codes = array(
            'E0', 'E1', 'E2', 'E3'
        );

        foreach ($league_codes as $league_code)
        {
            $this->downloadResultsFile($league_code);
        }

        $finder = new Finder();
        $finder->files()->in('data/results/'.date("Y-m-d"))->name('*.csv');

        $result_count = 0;

        $teams_array = array();
        $teams = $em->getRepository('AppBundle:Team')->findAll();
        foreach ($teams as $team)
        {
            $teams_array[$team->getName()] = $team;
        }

        $all_results = $em->getRepository('AppBundle:Result')->findAll();
        $all_existing_results = array();
        foreach ($all_results as $existing_result)
        {
            $all_existing_results[$existing_result->getMatchDate()->format('d/m/Y')][$existing_result->getHometeam()->getId()][$existing_result->getAwayteam()->getId()] = $existing_result;
        }

        foreach ($finder as $file)
        {

            $reader = new CsvReader($file->openFile());
            $reader->setStrict(false);
            $reader->setHeaderRowNumber(0, CsvReader::DUPLICATE_HEADERS_INCREMENT);
            foreach ($reader as $row) {
                if(!is_array($row)){
                    continue;
                }

                if(array_key_exists('HomeTeam', $row) && array_key_exists('AwayTeam', $row) ){
                    $hometeam = $row['HomeTeam'];
                    $awayteam = $row['AwayTeam'];
                }else{
                    $hometeam = $row['HT'];
                    $awayteam = $row['AT'];
                }
                $hometeam = $teams_array[$hometeam];
                $awayteam = $teams_array[$awayteam];
                $date1 = new \DateTime();
                $date = $date1->createFromFormat('d/m/y', $row['Date']);
                if(!is_object($date)){
                    $date = $date1->createFromFormat('d/m/Y', $row['Date']);
                    if(!is_object($date)){
                        $date = null;
                        continue;
                    }
                }

                if(array_key_exists($date->format('d/m/Y'), $all_existing_results) && array_key_exists($hometeam->getId(), $all_existing_results[$date->format('d/m/Y')]) && array_key_exists($awayteam->getId(), $all_existing_results[$date->format('d/m/Y')][$hometeam->getId()])){

                    $match = $all_existing_results[$date->format('d/m/Y')][$hometeam->getId()][$awayteam->getId()];
                    if(is_null($match->getHomeScore())){

                        $match->setAwayScore($row['FTAG']);
                        $match->setHomeScore($row['FTHG']);
                        $match->setResult($row['FTR']);
                        $em->persist($match);
                        $result_count++;

                    }

                }

            }
            $em->flush();
            flush(); sleep(5);
        }
        return "Results updated: $result_count<br />";

    }

    private function downloadFixturesFile(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,
            'http://www.football-data.co.uk/fixtures.csv');
        $fp = fopen('data/fixtures/'.date("Y-m-d").'.csv', 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec ($ch);
        curl_close ($ch);
        fclose($fp);
        return true;
    }


    private function downloadResultsFile($filename){
        $dir = 'data/results/'.date("Y-m-d");
        if( is_dir($dir) === false )
        {
            mkdir($dir);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,
            "http://www.football-data.co.uk/mmz4281/1516/$filename.csv");
        $fp = fopen('data/results/'.date("Y-m-d").'/'.$filename.'.csv', 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec ($ch);
        curl_close ($ch);
        fclose($fp);
        return true;
    }


}