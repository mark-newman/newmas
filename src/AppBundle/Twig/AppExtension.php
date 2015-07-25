<?php

namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    private $em;

    public function __construct(\Doctrine\ORM\EntityManager $em){
        $this->em = $em;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('gg_alg', array($this, 'goalsGaloreAlgorithmFunction')),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('season', array($this, 'seasonFilter')),
        );
    }

    public function seasonFilter($season_code)
    {
        switch($season_code){
            case 'E0':
                $season = 'Premier League';
                break;
            case 'E1':
                $season = 'Championship';
                break;
            case 'E2':
                $season = 'League 1';
                break;
            case 'E3':
                $season = 'League 2';
                break;
            default:
                $season = 'Error finding season name';

        }
        return $season;
    }

    public function goalsGaloreAlgorithmFunction($home_team_id, $away_team_id, $historical_fixtures, $matchday)
    {
        $k_factor = count($historical_fixtures);

//        if($k_factor < 5){
//            return array(
//                'error' => 'Only '.count($historical_fixtures).' previous meetings'
//            );
//        }

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

        $home_team_recent = $this->em->getRepository('AppBundle:Result')->getRecentResults($home_team_id, $k_factor, true, false, $matchday);
        $away_team_recent = $this->em->getRepository('AppBundle:Result')->getRecentResults($away_team_id, $k_factor, false, true, $matchday);

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

        return array(
            'score' => $home_scored/$k_factor * $away_scored/$k_factor * $home_scored_recent/$k_factor * $away_scored_recent/$k_factor,
            'score_breakdown' => 'Same Fixture Home Scored: '.$home_scored.'&#13;Same Fixture Away Scored: '.$away_scored.'&#13;Home Form Scored: '.$home_scored_recent.'&#13;Away Form Scored: '.$away_scored_recent,
        );
    }



    public function getName()
    {
        return 'app_extension';
    }
}

?>