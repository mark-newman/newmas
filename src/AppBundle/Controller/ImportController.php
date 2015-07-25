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
     * @Route("/import/csvs")
     */
    public function importCSVsAction()
    {
        ini_set('memory_limit', '-1');
        ini_set ('max_execution_time', 9000);
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/../../../app/Resources/csv_data')->name('*.csv');
        $em = $this->getDoctrine()->getManager();

        // truncate results

        $cmd = $em->getClassMetadata('AppBundle:Result');
        $connection = $em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $connection->beginTransaction();
        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
            $connection->executeUpdate($q);
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        }
        catch (\Exception $e) {
            $connection->rollback();
        }

        foreach ($finder as $file)
        {
            $reader = new CsvReader($file->openFile());
            $reader->setHeaderRowNumber(0, CsvReader::DUPLICATE_HEADERS_INCREMENT);

            // look through results and find new teams, ensure no duplications
            $teams_array = array();
            $new_teams_array = array();
            $teams = $em->getRepository('AppBundle:Team')->findAll();
            foreach ($teams as $team)
            {
                $teams_array[] = $team->getName();
            }

            foreach ($reader as $row) {
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
                if(!in_array($new_team, $teams_array)){
                    $team = new Team();
                    $team->setName($new_team);
                    $em->persist($team);
                }
            }
            $em->flush();

            // now loop through and save results, sloowwlly
            foreach ($reader as $row) {
                if(!is_array($row)){
                    continue;
                }
                if(array_key_exists('HomeTeam', $row) && array_key_exists('AwayTeam', $row) ){
                    $hometeam = $em->getRepository('AppBundle:Team')->findOneBy(array('name' => $row['HomeTeam']));
                    $awayteam = $em->getRepository('AppBundle:Team')->findOneBy(array('name' => $row['AwayTeam']));
                }else{
                    $hometeam = $em->getRepository('AppBundle:Team')->findOneBy(array('name' => $row['HT']));
                    $awayteam = $em->getRepository('AppBundle:Team')->findOneBy(array('name' => $row['AT']));
                }
                $date1 = new \DateTime();
                $date = $date1->createFromFormat('d/m/y', $row['Date']);
                if(!is_object($date)){
                    $date = $date1->createFromFormat('d/m/Y', $row['Date']);
                    if(!is_object($date)){
                        $date = null;
                    }
                }

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
            }
            $em->flush();

        }
        die(print_r('Success', true));


    }
}