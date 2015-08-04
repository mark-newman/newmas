<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 03/08/2015
 * Time: 16:28
 */

namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AlgorithmStatsController extends Controller {

    private $em;

    /**
     * @Route("/algorithm-stats", name="algorithm_stats_index")
     */
    public function algorithmStatsAction()
    {
        $algorithm_choices = array(
            'gg1'
        );

        $league_choices = array(
            'E0',
            'E1',
            'E2',
            'E3',
        );

        return $this->render('algorithm-stats/index.html.twig', compact('algorithm_choices', 'league_choices'));
    }

    /**
     * @Route("/algorithm-stats/run", name="get_individual_algorithm_stats")
     */
    public function getIndividualAlgorithmStatsAction(Request $request)
    {
        $league = $request->get('league_code');
        $season = $request->get('season');
        $algorithm = $request->get('algorithm');
        $algorithm_success_value = $request->get('algorithm_success_value');
        $minimum_history = $request->get('minimum_history');
        $include_x_league_form = $request->get('include_x_league_form');
        $minimum_league_form = $request->get('minimum_league_form');

        if(!$league || !$season || !$algorithm || $algorithm_success_value === '' || $minimum_history === '' || $include_x_league_form === '' || $minimum_league_form === ''){
            die;
        }

        $em = $this->getDoctrine()->getManager();
        $this->em = $em;
        $matches = $this->em->getRepository('AppBundle:Result')->findBy(array('season' => $season, 'league_code' => $league), array('matchDate' => 'ASC'));

        $correct = 0;
        $incorrect = 0;
        $prediction_count = 0;
        $fixture_data = array();

        foreach ($matches as $match)
        {
            $home_team_id = $match->getHometeam()->getId();
            $away_team_id = $match->getAwayteam()->getId();
            $matchday = $match->getMatchDate();
            $historical_fixtures = $this->em->getRepository('AppBundle:Result')->findHistoricalFixturesHomeTeamAndAwayTeam($home_team_id, $away_team_id, $matchday);

            $algorithm_data = $this->get('algorithm_service')->goalsGaloreAlgorithmFunction($home_team_id, $away_team_id, $historical_fixtures, $matchday, $minimum_history, $include_x_league_form, $minimum_league_form);
            if(array_key_exists('error', $algorithm_data) || ($algorithm_data['score'] < $algorithm_success_value)){
                continue;
            }elseif($match->getHomeScore()>0 && $match->getAwayScore()>0){
                $prediction_count++;
                $correct++;
                $fixture_data[$match->getId()]['fixture'] = $match;
                $fixture_data[$match->getId()]['history'] = $historical_fixtures;
            }else{
                $prediction_count++;
                $incorrect++;
                $fixture_data[$match->getId()]['fixture'] = $match;
                $fixture_data[$match->getId()]['history'] = $historical_fixtures;
            }
        }

        return $this->render('algorithm-stats/partials/algorithmResults.html.twig', compact('league', 'season', 'algorithm_success_score', 'matches','prediction_count', 'correct', 'fixture_data', 'algorithm_success_value'));

    }

}