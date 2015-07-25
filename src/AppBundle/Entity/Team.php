<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Team
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TeamRepository")
 * @UniqueEntity("name")
 */
class Team
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="alternate_name", type="string", length=255, nullable=true)
     */
    private $alternateName;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Result", mappedBy="hometeam")
     */
    private $hometeam_data;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Result", mappedBy="awayteam")
     */
    private $awayteam_data;

    public function __construct(){
        $this->hometeam_data = new ArrayCollection();
        $this->awayteam_data = new ArrayCollection();
    }

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
     * Set name
     *
     * @param string $name
     * @return Team
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set alternateName
     *
     * @param string $alternateName
     * @return Team
     */
    public function setAlternateName($alternateName)
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    /**
     * Get alternateName
     *
     * @return string 
     */
    public function getAlternateName()
    {
        return $this->alternateName;
    }

    /**
     * @param mixed $awayteam_data
     */
    public function setAwayteamData($awayteam_data)
    {
        $this->awayteam_data = $awayteam_data;
    }

    /**
     * @return mixed
     */
    public function getAwayteamData()
    {
        return $this->awayteam_data;
    }

    /**
     * @param mixed $hometeam_data
     */
    public function setHometeamData($hometeam_data)
    {
        $this->hometeam_data = $hometeam_data;
    }

    /**
     * @return mixed
     */
    public function getHometeamData()
    {
        return $this->hometeam_data;
    }

}
