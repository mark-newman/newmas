<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class OldApiMatchController extends Controller
{
    private $baseUri = 'http://api.football-data.org/';

    public function indexAction(Request $request)
    {
        $data = array();
        $form = $this->createFormBuilder($data)
            ->add('league', 'choice',
                array('choices' => array(
                    'PL'   => 'Premier League',
                )))
            ->add('submit', 'submit', array('label' => 'Submit'))
            ->getForm()
        ;

        if ($request->isMethod('POST')) {
            $form->bind($request);
            $data = $form->getData();
            $seasonData = $this->getSeasonData($data['league'], "2014");
//            return $this->redirect($this->generateUrl('last_fixtures_data', array('seasonId' => $seasonData['id'])));
            return $this->redirect($this->generateUrl('matchday_list', array('seasonId' => $seasonData['id'])));

        }

        return $this->render('default/index.html.twig', array(
            'form' => $form->createView()
        ));
    }

    public function getMatchdayList($seasonId)
    {
        $seasonData = $this->getSeasonDataById($seasonId);
        $fixtures = $this->sendRequest($seasonData['_links']['fixtures']['href']);
        $matchdays = $this->getMatchdays($fixtures);
        return $this->render('default/matchdayList.html.twig', array(
            'seasonData' => $seasonData,
            'matchdays' => array_reverse($matchdays, true),
        ));
    }

    public function getMatchdayData($seasonId, $matchdayId)
    {
        $uri = $this->baseUri.'alpha/soccerseasons/'.$seasonId.'/fixtures/?matchday='.$matchdayId;
        $fixtures = $this->sendRequest($uri);
        $seasonData = $this->getSeasonDataById($seasonId);
        foreach ($fixtures['fixtures'] as &$fixture)
        {
            $fixture['historicData'] = $this->getHistoricDataForFixture($fixture);

            preg_match('#(\d+)$#', $fixture['_links']['homeTeam']['href'], $matches);
            $fixture['homeTeamId'] = $matches[1];
            preg_match('#(\d+)$#', $fixture['_links']['awayTeam']['href'], $matches);
            $fixture['awayTeamId'] = $matches[1];
        }

        return $this->render('old-api/matchday.html.twig', array(
            'seasonData' => $seasonData,
            'fixtures' => $fixtures
        ));

    }

    public function getLastFixturesData($seasonId)
    {
        $seasonData = $this->getSeasonDataById($seasonId);
        $fixtures = $this->sendRequest($seasonData['_links']['fixtures']['href']);
        $matchdays = $this->getMatchdays($fixtures);
        end($matchdays);
        $matchdayId = key($matchdays);

        $uri = $this->baseUri.'alpha/soccerseasons/'.$seasonId.'/fixtures/?matchday='.$matchdayId;
        $fixtures = $this->sendRequest($uri);
        $seasonData = $this->getSeasonDataById($seasonId);
        foreach ($fixtures['fixtures'] as &$fixture)
        {
            $fixture['historicData'] = $this->getHistoricDataForFixture($fixture);
        }

        return $this->render('default/matchday.html.twig', array(
            'seasonData' => $seasonData,
            'fixtures' => $fixtures
        ));

    }

    public function getTeamData($teamId)
    {
        $uri = $this->baseUri.'alpha/teams/'.$teamId;
        $team = $this->sendRequest($uri);
        $fixtures = $this->sendRequest($team['_links']['fixtures']['href']);
        $fixtures['fixtures'] = array_reverse($fixtures['fixtures'], true);
        return $this->render('default/team.html.twig', array(
            'team' => $team,
            'fixtures' =>  $fixtures,
        ));
    }

    public function getSeasonDataById($seasonId)
    {
        $uri = $this->baseUri.'alpha/soccerseasons/'.$seasonId;
        $season = $this->sendRequest($uri);
        $season['id'] = $seasonId;
        return $season;
    }

    public function getSeasonData($leagueCode, $year)
    {
        $uri = $this->baseUri.'alpha/soccerseasons/?season='.$year;
        $seasons = $this->sendRequest($uri);
        foreach ($seasons as $season)
        {
            if($season['league'] == $leagueCode)
            {
                $matches = array();
                if (preg_match('#(\d+)$#', $season['_links']['self']['href'], $matches)) {
                    $season['id'] = $matches[1];
                }else{
                    throw $this->createNotFoundException('League id not matched');
                }
                return $season;
            }
        }

        throw $this->createNotFoundException('League id not matched');

    }

    public function getMatchdays($fixtures){
        $matchdays = array();
        foreach ($fixtures['fixtures'] as $fixture)
        {
            if(!in_array($fixture['matchday'], array_keys($matchdays))){
                $matchdays[$fixture['matchday']] = $fixture['date'];
            }
        }
        return $matchdays;
    }

    public function getHistoricDataForFixture($fixture){
        $data = $this->sendRequest($fixture['_links']['self']['href']);
        return $data;
    }

    private function sendRequest($uri)
    {
        $reqPrefs['http']['method'] = 'GET';
        $reqPrefs['http']['header'] = 'X-Auth-Token: '.$this->getParameter('football_data_api');
        $stream_context = stream_context_create($reqPrefs);
        $response = file_get_contents($uri, false, $stream_context);
        return json_decode($response, true);
    }

}
