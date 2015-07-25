<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Result
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ResultRepository")
 */
class Result
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="home_score", type="integer", nullable=true)
     */
    private $homeScore;

    /**
     * @var integer
     *
     * @ORM\Column(name="away_score", type="integer", nullable=true)
     */
    private $awayScore;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="match_date", type="date", nullable=true)
     */
    private $matchDate;

    /**
     * @var string
     *
     * @ORM\Column(name="result", type="string", length=255, nullable=true)
     */
    private $result;

    /**
     * @var string
     *
     * @ORM\Column(name="league_code", type="string", length=255, nullable=true)
     */
    private $league_code;

    /**
     * @var string
     *
     * @ORM\Column(name="season", type="string", length=255, nullable=true)
     */
    private $season;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Team", inversedBy="hometeam_data")
     * @ORM\JoinColumn(name="hometeam_id", referencedColumnName="id")
     */
    private $hometeam;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Team", inversedBy="awayteam_data")
     * @ORM\JoinColumn(name="awayteam_id", referencedColumnName="id")
     */
    private $awayteam;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set homeScore
     *
     * @param integer $homeScore
     * @return Result
     */
    public function setHomeScore($homeScore)
    {
        $this->homeScore = $homeScore;

        return $this;
    }

    /**
     * Get homeScore
     *
     * @return integer 
     */
    public function getHomeScore()
    {
        return $this->homeScore;
    }

    /**
     * Set awayScore
     *
     * @param integer $awayScore
     * @return Result
     */
    public function setAwayScore($awayScore)
    {
        $this->awayScore = $awayScore;

        return $this;
    }

    /**
     * Get awayScore
     *
     * @return integer 
     */
    public function getAwayScore()
    {
        return $this->awayScore;
    }

    /**
     * Set matchDate
     *
     * @param \DateTime $matchDate
     * @return Result
     */
    public function setMatchDate($matchDate)
    {
        $this->matchDate = $matchDate;

        return $this;
    }

    /**
     * Get matchDate
     *
     * @return \DateTime 
     */
    public function getMatchDate()
    {
        return $this->matchDate;
    }

    /**
     * @param string $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $awayteam
     */
    public function setAwayteam($awayteam)
    {
        $this->awayteam = $awayteam;
    }

    /**
     * @return mixed
     */
    public function getAwayteam()
    {
        return $this->awayteam;
    }

    /**
     * @param mixed $hometeam
     */
    public function setHometeam($hometeam)
    {
        $this->hometeam = $hometeam;
    }

    /**
     * @return mixed
     */
    public function getHometeam()
    {
        return $this->hometeam;
    }

    /**
     * @param string $league_code
     */
    public function setLeagueCode($league_code)
    {
        $this->league_code = $league_code;
    }

    /**
     * @return string
     */
    public function getLeagueCode()
    {
        return $this->league_code;
    }

    /**
     * @param string $season
     */
    public function setSeason($season)
    {
        $this->season = $season;
    }

    /**
     * @return string
     */
    public function getSeason()
    {
        return $this->season;
    }

}
