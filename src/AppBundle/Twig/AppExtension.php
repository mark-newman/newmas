<?php

namespace AppBundle\Twig;

use AppBundle\Service\AlgorithmService;

class AppExtension extends \Twig_Extension
{
    private $em;
    private $algorithm_service;

    public function __construct(\Doctrine\ORM\EntityManager $em, AlgorithmService $algorithm_service){
        $this->em = $em;
        $this->algorithm_service = $algorithm_service;
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
        return $this->algorithm_service->goalsGaloreAlgorithmFunction($home_team_id, $away_team_id, $historical_fixtures, $matchday);
    }



    public function getName()
    {
        return 'app_extension';
    }
}

?>