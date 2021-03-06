<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Proxy
 *
 * @ORM\Table(name="proxy")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProxyRepository")
 */
class Proxy
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="proxys")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity="Beneficiary", inversedBy="received_proxies")
     * @ORM\JoinColumn(name="owner", referencedColumnName="id", onDelete="CASCADE")
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="given_proxies")
     * @ORM\JoinColumn(name="giver", referencedColumnName="id", onDelete="CASCADE")
     */
    private $giver;


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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Proxy
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set event
     *
     * @param \AppBundle\Entity\Event $event
     *
     * @return Proxy
     */
    public function setEvent(\AppBundle\Entity\Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return \AppBundle\Entity\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set owner
     *
     * @param \AppBundle\Entity\Beneficiary $owner
     *
     * @return Proxy
     */
    public function setOwner(\AppBundle\Entity\Beneficiary $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \AppBundle\Entity\Beneficiary
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set giver
     *
     * @param \AppBundle\Entity\User $giver
     *
     * @return Proxy
     */
    public function setGiver(\AppBundle\Entity\User $giver = null)
    {
        $this->giver = $giver;

        return $this;
    }

    /**
     * Get giver
     *
     * @return \AppBundle\Entity\User
     */
    public function getGiver()
    {
        return $this->giver;
    }
}
