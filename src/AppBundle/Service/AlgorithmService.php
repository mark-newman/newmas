<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 03/08/2015
 * Time: 19:14
 */

namespace AppBundle\Service;


class AlgorithmService {

    private $em;

    public function __construct(\Doctrine\ORM\EntityManager $em){
        $this->em = $em;
    }

    public function goalsGaloreAlgorithmFunction($home_team_id, $away_team_id, $historical_fixtures, $matchday, $minimum_history=5, $include_x_league_form=0, $minimum_league_form=0)
    {
        $k_factor = count($historical_fixtures);
        if($include_x_league_form==0){
            if($minimum_league_form > $k_factor){
                $include_x_league_form = $minimum_league_form;
            }else{
                $include_x_league_form = $k_factor;
            }
        }
        $response_array = array();

        if($k_factor < $minimum_history){
            $response_array['error'] = 'Only '.count($historical_fixtures).' previous meetings';
            if($k_factor == 0){
                $response_array['error'] = 'No previous meetings';
            }
            if($k_factor == 1){
                $response_array['error'] = 'Only '.count($historical_fixtures).' previous meeting';
            }
        }

        $home_scored = 0;
        $away_scored = 0;
        foreach($historical_fixtures as $fixture){
            if($fixture->getHomeScore()>0){
                $home_scored++;
            }
            if($fixture->getAwayScore()>0){
                $away_scored++;
            }
        }

        $home_team_recent = $this->em->getRepository('AppBundle:Result')->getRecentResults($home_team_id, $include_x_league_form, true, false, $matchday);
        $away_team_recent = $this->em->getRepository('AppBundle:Result')->getRecentResults($away_team_id, $include_x_league_form, false, true, $matchday);

        $home_scored_recent = 0;
        $away_scored_recent = 0;


        foreach ($home_team_recent as $fixture)
        {
            if($fixture->getHomeScore() > 0){
                $home_scored_recent++;
            }
        }

        foreach ($away_team_recent as $fixture)
        {
            if($fixture->getAwayScore() > 0){
                $away_scored_recent++;
            }
        }

        if($k_factor == 0){
            $response_array['score'] = 0;
        }else{
            $response_array['score'] = $home_scored/$k_factor * $away_scored/$k_factor * $home_scored_recent/$k_factor * $away_scored_recent/$k_factor;
        }
        $response_array['score_breakdown'] = 'Number of fixtures counted:'.$k_factor.'&#13;Same Fixture Home Scored: '.$home_scored.'&#13;Same Fixture Away Scored: '.$away_scored.'&#13;Home Form Scored: '.$home_scored_recent.'&#13;Away Form Scored: '.$away_scored_recent;

        return $response_array;
    }

} 