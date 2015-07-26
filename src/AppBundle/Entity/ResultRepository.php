<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ResultRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ResultRepository extends EntityRepository
{

    public function findUniqueMatchdaysByLeagueCodeAndSeason($league_code, $season){

        $query = $this->createQueryBuilder('r')
        ->select('r.matchDate')
        ->where('r.league_code = :league_code')
        ->andWhere('r.season = :season')
        ->setParameter('league_code', $league_code)
        ->setParameter('season', $season)
        ->orderBy('r.matchDate', 'DESC')
        ->distinct()
        ->getQuery();

        return $query->getResult();

    }

    public function getUpcomingFixtures($league_code){

        $query = $this->createQueryBuilder('r')
            ->where('r.league_code = :league_code')
            ->andWhere('r.matchDate > :from_date')
            ->andWhere('r.matchDate < :to_date')
            ->setParameter('league_code', $league_code)
            ->setParameter('from_date', date("Y-m-d"))
            ->setParameter('to_date', date("Y-m-d",strtotime("+4 week")))
            ->orderBy('r.league_code', 'DESC')
            ->addOrderBy('r.matchDate', 'ASC')
            ->distinct()
            ->getQuery();

        return $query->getResult();

    }

    public function findUniqueSeasonsByLeagueCode($league_code){

        $query = $this->createQueryBuilder('r')
        ->select('r.season')
        ->where('r.league_code = :league_code')
        ->setParameter('league_code', $league_code)
        ->orderBy('r.matchDate', 'DESC')
        ->distinct()
        ->getQuery();

        return $query->getResult();

    }

    public function findFixturesByLeagueCodeAndMatchday($league_code, $matchday){

        $query = $this->createQueryBuilder('r')
        ->where('r.league_code = :league_code')
        ->setParameter('league_code', $league_code)
        ->andWhere('r.matchDate = :matchDate')
        ->setParameter('matchDate', $matchday)
        ->orderBy('r.matchDate', 'DESC')
        ->getQuery();

        return $query->getResult();

    }

    public function findHistoricalFixturesHomeTeamAndAwayTeam($home_team, $away_team, $matchday=false, $limit=10){

        $query = $this->createQueryBuilder('r')
        ->where('r.hometeam = :home_team')
        ->andWhere('r.awayteam = :away_team')
        ->setParameter('home_team', $home_team)
        ->setParameter('away_team', $away_team);
        if($matchday){
            $query = $query
                ->andWhere('r.matchDate < :matchday')
                ->setParameter('matchday', $matchday);
        }
        $query = $query
            ->orderBy('r.matchDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        return $query->getResult();

    }

    public function getRecentResults($team_id, $limit=20, $home_team_only = false, $away_team_only = false, $before_matchday=false){

        if($home_team_only){
            $query = $this->createQueryBuilder('r')
                ->where('r.hometeam = :team_id')
                ->setParameter('team_id', $team_id);
        }
        if($away_team_only){
            $query = $this->createQueryBuilder('r')
                ->where('r.awayteam = :team_id')
                ->setParameter('team_id', $team_id);
        }
        if(!$home_team_only && !$away_team_only){
            $query = $this->createQueryBuilder('r')
                ->where('r.hometeam = :team_id')
                ->orWhere('r.awayteam = :team_id')
                ->setParameter('team_id', $team_id);
        }
        if($before_matchday){
            $query = $query
                ->andWhere('r.matchDate < :matchday')
                ->setParameter('matchday', $before_matchday);
        }
        $query = $query
            ->orderBy('r.matchDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        return $query->getResult();

    }
}
