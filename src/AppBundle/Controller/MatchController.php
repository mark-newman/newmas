<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MatchController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $league_choices = array(
            'E0',
            'E1',
            'E2',
            'E3',
        );

        if($this->get('session')->has('algorithm_success_value')){
            $algorithm_success_value = $this->get('session')->get('algorithm_success_value');
        }else{
            $algorithm_success_value = 0.35;
            $this->get('session')->set('algorithm_success_value', $algorithm_success_value);
        }


        $em = $this->getDoctrine()->getManager();
        $league_fixtures = array();
        foreach($league_choices as $league){

            $fixtures = $em->getRepository('AppBundle:Result')->getUpcomingFixtures($league);

            $fixture_data = array();

            foreach ($fixtures as $fixture)
            {
                $fixture_data[$fixture->getId()]['fixture'] = $fixture;
                $fixture_data[$fixture->getId()]['history'] = $em->getRepository('AppBundle:Result')->findHistoricalFixturesHomeTeamAndAwayTeam($fixture->getHomeTeam()->getId(), $fixture->getAwayTeam()->getId(), $fixture->getMatchDate());;
            }
            $league_fixtures[$league] = $fixture_data;

        }

        $upcoming = true;

        return $this->render('match/index.html.twig', compact('league_choices', 'league_fixtures', 'upcoming', 'algorithm_success_value'));
    }

    /**
     * @Route("/team/{team_id}", name="team")
     */
    public function teamAction($team_id)
    {
        $em = $this->getDoctrine()->getManager();
        $team = $em->getRepository('AppBundle:Team')->find($team_id);
        $fixture = array();
        $fixture['history'] = $em->getRepository('AppBundle:Result')->getRecentResults($team_id);

        return $this->render('match/team.html.twig', compact('team', 'fixture'));
    }

    /**
     * @Route("/ajax/season-list", name="ajax_season_list")
     */
    public function ajaxSeasonListAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $league_code = $request->get('league_code');
            $em = $this->getDoctrine()->getManager();
            $seasons = $em->getRepository('AppBundle:Result')->findUniqueSeasonsByLeagueCode($league_code);
            return $this->render('match/partials/seasonsSelect.html.twig', compact('seasons'));
        }
    }

    /**
     * @Route("/ajax/update-algorithm-success", name="update_algorithm_success_value")
     */
    public function ajaxUpdateAlgorithmSuccessAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $algorithm_updated_value = $request->get('algorithm_updated_value');
            if(is_numeric($algorithm_updated_value)){
                $this->get('session')->set('algorithm_success_value', $algorithm_updated_value);
            }

            return new Response('success');
        }
    }

    /**
     * @Route("/ajax/matchday-list", name="ajax_matchday_list")
     */
    public function ajaxMatchdayListAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $league_code = $request->get('league_code');
            $season = $request->get('season');
            $em = $this->getDoctrine()->getManager();
            $matchdays = $em->getRepository('AppBundle:Result')->findUniqueMatchdaysByLeagueCodeAndSeason($league_code, $season);
            return $this->render('match/partials/matchdaySelect.html.twig', compact('matchdays'));
        }
    }

    /**
     * @Route("/ajax/fixtures", name="ajax_fixtures_list")
     */
    public function ajaxFixturesListAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $league_code = $request->get('league_code');
            $matchday = $request->get('matchday');
            $em = $this->getDoctrine()->getManager();
            $fixtures = $em->getRepository('AppBundle:Result')->findFixturesByLeagueCodeAndMatchday($league_code, $matchday);

            $fixture_data = array();

            foreach ($fixtures as $fixture)
            {
                $fixture_data[$fixture->getId()]['fixture'] = $fixture;
                $fixture_data[$fixture->getId()]['history'] = $em->getRepository('AppBundle:Result')->findHistoricalFixturesHomeTeamAndAwayTeam($fixture->getHomeTeam()->getId(), $fixture->getAwayTeam()->getId(), $matchday);
            }
            $algorithm_success_value = $this->get('session')->get('algorithm_success_value');
            return $this->render('match/partials/resultsList.html.twig', compact('fixture_data', 'algorithm_success_value'));
        }
    }
}
