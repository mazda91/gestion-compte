<?php
// src/AppBundle/Entity/User.php

namespace AppBundle\Entity;

use DateTime;
use AppBundle\Repository\RegistrationRepository;
use Doctrine\ORM\EntityRepository;
use FOS\OAuthServerBundle\Model\ClientInterface;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="fos_user")
 * @UniqueEntity("member_number")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     * @Assert\NotBlank(message="Merci d'entrer votre numéro d'adhérent.", groups={"Registration"})
     */
    protected $member_number;

    /**
     * @var bool
     *
     * @ORM\Column(name="withdrawn", type="boolean", nullable=true, options={"default" : 0})
     */
    private $withdrawn;

    /**
     * @var bool
     *
     * @ORM\Column(name="frozen", type="boolean", nullable=true, options={"default" : 0})
     */
    private $frozen;

    /**
     * @ORM\OneToMany(targetEntity="Registration", mappedBy="user",cascade={"persist", "remove"})
     * @OrderBy({"date" = "DESC"})
     */
    private $registrations;

    /**
     * @ORM\OneToOne(targetEntity="Registration",cascade={"persist"})
     * @ORM\JoinColumn(name="last_registration_id", referencedColumnName="id")
     */
    private $lastRegistration;

    /**
     * @ORM\OneToMany(targetEntity="Registration", mappedBy="registrar",cascade={"persist", "remove"})
     * @OrderBy({"date" = "DESC"})
     */
    private $recordedRegistrations;

    /**
     * @ORM\OneToMany(targetEntity="Beneficiary", mappedBy="user",cascade={"persist", "remove"})
     */
    private $beneficiaries;

    /**
     * One User has One Main Beneficiary.
     * @ORM\OneToOne(targetEntity="Beneficiary",cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="main_beneficiary_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $mainBeneficiary;

    /**
     * One User has One Address.
     * @ORM\OneToOne(targetEntity="Address",cascade={"persist"})
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    /**
     * Many Users have Many clients.
     * @ORM\ManyToMany(targetEntity="Client", inversedBy="users")
     * @ORM\JoinTable(name="users_clients")
     */
    private $clients;

    /**
     * @ORM\OneToMany(targetEntity="Note", mappedBy="subject",cascade={"persist", "remove"})
     * @OrderBy({"created_at" = "ASC"})
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity="Note", mappedBy="author",cascade={"persist", "remove"})
     * @OrderBy({"created_at" = "DESC"})
     */
    private $annotations;

    /**
     * @ORM\OneToMany(targetEntity="Proxy", mappedBy="owner",cascade={"persist", "remove"})
     */
    private $given_proxies;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="first_shift_date", type="date", nullable=true)
     */
    private $firstShiftDate;

    // array of date
    private $_startOfCycle;
    private $_endOfCycle;

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->registrations = new ArrayCollection();
        $this->beneficiaries = new ArrayCollection();
    }

    /**
     * Set memberNumber
     *
     * @param integer $memberNumber
     *
     * @return User
     */
    public function setMemberNumber($memberNumber)
    {
        $this->member_number = $memberNumber;

        return $this;
    }

    /**
     * Get memberNumber
     *
     * @return integer
     */
    public function getMemberNumber()
    {
        return $this->member_number;
    }

    /**
     * Add registration
     *
     * @param \AppBundle\Entity\Registration $registration
     *
     * @return User
     */
    public function addRegistration(\AppBundle\Entity\Registration $registration)
    {
        $this->registrations[] = $registration;

        if (!$this->getLastRegistration() || $registration->getDate() > $this->getLastRegistration()->getDate()){
            $this->setLastRegistration($registration);
        }

        return $this;
    }

    /**
     * Remove registration
     *
     * @param \AppBundle\Entity\Registration $registration
     */
    public function removeRegistration(\AppBundle\Entity\Registration $registration)
    {
        $this->registrations->removeElement($registration);

        if ($this->getLastRegistration() === $registration){
            if ($this->getRegistrations()->count()){
                $this->setLastRegistration($this->getRegistrations()->first());
            }
        }
    }

    /**
     * Get registrations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRegistrations()
    {
        return $this->registrations;
    }

    /**
     * Add beneficiary
     *
     * @param \AppBundle\Entity\Beneficiary $beneficiary
     *
     * @return User
     */
    public function addBeneficiary(\AppBundle\Entity\Beneficiary $beneficiary)
    {
        $this->beneficiaries[] = $beneficiary;

        return $this;
    }

    /**
     * Remove beneficiary
     *
     * @param \AppBundle\Entity\Beneficiary $beneficiary
     */
    public function removeBeneficiary(\AppBundle\Entity\Beneficiary $beneficiary)
    {
        $this->beneficiaries->removeElement($beneficiary);
    }

    /**
     * Get beneficiaries
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBeneficiaries()
    {
        return $this->beneficiaries;
    }

    /**
     * Set address
     *
     * @param \AppBundle\Entity\Address $address
     *
     * @return User
     */
    public function setAddress(\AppBundle\Entity\Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \AppBundle\Entity\Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    public function getFirstname() {
        $mainBeneficiary = $this->getMainBeneficiary();
        if ($mainBeneficiary)
            return $mainBeneficiary->getFirstname();
        else
            return $this->getUsername();

    }

    public function getLastname() {
        $mainBeneficiary = $this->getMainBeneficiary();
        if ($mainBeneficiary)
            return $mainBeneficiary->getLastname();
        else
            return '';
    }

    public function __toString()
    {
        return '#'.$this->getMemberNumber().' '.$this->getFirstname().' '.$this->getLastname();
    }

    public function getTmpToken($key = ''){
        return md5($this->getEmail().$this->getLastname().$this->getPassword().$key.date('d'));
    }

    public  function getAnonymousEmail(){
        $email = $this->getEmail();
        $splited = explode("@",$email);
        $return = '';
        foreach ($splited as $part){
            $splited_part = explode(".",$part);
            foreach ($splited_part as $mini_part){
                $first_char = substr($mini_part,0,1);
                $last_char = substr($mini_part,strlen($mini_part)-1,1);
                $center = substr($mini_part,1,strlen($mini_part)-2);
                if (strlen($center)>0)
                    $return .= $first_char.preg_replace('/./','_',$center).$last_char;
                elseif(strlen($mini_part)>1)
                    $return .= $first_char.$last_char;
                else
                    $return .= $first_char;
                $return .= '.';
            }
            $return = substr($return,0,strlen($return)-1);
            $return .= '@';
        }
        $return = substr($return,0,strlen($return)-1);
        return preg_replace('/_{3}_*/','___',$return);
    }

    public  function getAnonymousLastname(){
        $lastname = $this->getLastname();
        $splited = explode(" ",$lastname);
        $return = '';
        foreach ($splited as $part){
            $splited_part = explode("-",$part);
            foreach ($splited_part as $mini_part){
                $first_char = substr($mini_part,0,1);
                $last_char = substr($mini_part,strlen($mini_part)-1,1);
                $center = substr($mini_part,1,strlen($mini_part)-2);
                if (strlen($center)>0)
                    $return .= $first_char.preg_replace('/./','*',$center).$last_char;
                else
                    $return .= $first_char.$last_char;
                $return .= '-';
            }
            $return = substr($return,0,strlen($return)-1);
            $return .= ' ';
        }
        $return = substr($return,0,strlen($return)-1);
        return $return;
    }

    static function makeUsername($firstname,$lastname,$extra = ''){
        $lastname = preg_replace('/[-\/]+/', ' ', $lastname);
        $ln = explode(' ',$lastname);
//        if (in_array(strtolower($ln[0]),array('la','du','de'))&&count($ln>1))
        if (strlen($ln[0])<3&&count($ln)>1)
            $ln = $ln[0].$ln[1];
        else
            $ln = $ln[0];
        $username = strtolower(substr(explode(' ',$firstname)[0],0,1).$ln);
        $username = preg_replace('/[^a-z0-9]/', '', $username);
        $username .= $extra;
        return $username;
    }

    static function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    /**
     * Set mainBeneficiary
     *
     * @param \AppBundle\Entity\Beneficiary $mainBeneficiary
     *
     * @return User
     */
    public function setMainBeneficiary(\AppBundle\Entity\Beneficiary $mainBeneficiary = null)
    {
        if ($mainBeneficiary)
            $this->addBeneficiary($mainBeneficiary);

        $this->mainBeneficiary = $mainBeneficiary;

        return $this;
    }

    /**
     * Get mainBeneficiary
     *
     * @return \AppBundle\Entity\Beneficiary
     */
    public function getMainBeneficiary()
    {
        if (!$this->mainBeneficiary){
            if ($this->getBeneficiaries()->count())
                $this->setMainBeneficiary($this->getBeneficiaries()->first());
        }
        return $this->mainBeneficiary;
    }

    /**
     * Set withdrawn
     *
     * @param boolean $withdrawn
     *
     * @return User
     */
    public function setWithdrawn($withdrawn)
    {
        $this->withdrawn = $withdrawn;

        return $this;
    }

    /**
     * Get isWithdrawn
     *
     * @return boolean
     */
    public function isWithdrawn()
    {
        return $this->withdrawn;
    }

    /**
     * Get withdrawn
     *
     * @return boolean
     */
    public function getWithdrawn()
    {
        return $this->withdrawn;
    }


    /**
     * Add recordedRegistration
     *
     * @param \AppBundle\Entity\Registration $recordedRegistration
     *
     * @return User
     */
    public function addRecordedRegistration(\AppBundle\Entity\Registration $recordedRegistration)
    {
        $this->recordedRegistrations[] = $recordedRegistration;

        return $this;
    }

    /**
     * Remove recordedRegistration
     *
     * @param \AppBundle\Entity\Registration $recordedRegistration
     */
    public function removeRecordedRegistration(\AppBundle\Entity\Registration $recordedRegistration)
    {
        $this->recordedRegistrations->removeElement($recordedRegistration);
    }

    /**
     * Get recordedRegistrations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecordedRegistrations()
    {
        return $this->recordedRegistrations;
    }

    public function getCommissions(){
        $commissions = array();
        foreach ($this->getBeneficiaries() as $beneficiary){
            $commissions = array_merge($beneficiary->getCommissions()->toArray(),$commissions);
        }
        return new ArrayCollection($commissions);
    }

    /**
     * Set frozen
     *
     * @param boolean $frozen
     *
     * @return User
     */
    public function setFrozen($frozen)
    {
        $this->frozen = $frozen;

        return $this;
    }

    /**
     * Get frozen
     *
     * @return boolean
     */
    public function getFrozen()
    {
        return $this->frozen;
    }

    /**
     * Set lastRegistration
     *
     * @param \AppBundle\Entity\Registration $lastRegistration
     *
     * @return User
     */
    public function setLastRegistration(\AppBundle\Entity\Registration $lastRegistration = null)
    {
        $this->lastRegistration = $lastRegistration;
        return $this;
    }
    /**
     * Get lastRegistration
     *
     * @return \AppBundle\Entity\Registration
     */
    public function getLastRegistration()
    {
        return $this->lastRegistration;
    }

    /**
     * determine whether the given client (ClientInterface) is allowed by the user, or not.
     * @param ClientInterface $client
     * @return bool
     */
    public function isAuthorizedClient(Client $client)
    {
        return $this->getClients()->contains($client);
    }

    /**
     * Add client
     *
     * @param \AppBundle\Entity\Client $client
     *
     * @return User
     */
    public function addClient(\AppBundle\Entity\Client $client)
    {
        $this->clients[] = $client;

        return $this;
    }

    /**
     * Remove client
     *
     * @param \AppBundle\Entity\Client $client
     */
    public function removeClient(\AppBundle\Entity\Client $client)
    {
        $this->clients->removeElement($client);
    }

    /**
     * Get clients
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Get total shift duration for current cycle
     */
    public function getCycleShiftsDuration($cycleOffset = 0, $excludeDismissed = false)
    {
        $duration = 0;
        foreach ($this->getShiftsOfCycle($cycleOffset, $excludeDismissed) as $shift) {
            $duration += $shift->getDuration();
        }
        return $duration;
    }

    /**
     * Get all shifts for all beneficiaries
     */
    public function getAllShifts($excludeDismissed = false)
    {
        $shifts = new ArrayCollection();
        foreach ($this->getBeneficiaries() as $beneficiary) {
            foreach ($beneficiary->getShifts() as $shift) {
                $shifts->add($shift);
            }
        }
        if ($excludeDismissed) {
            return $shifts->filter(function($shift) {
                return !$shift->getIsDismissed();
            });
        } else {
            return $shifts;
        }
    }

    /**
     * Get all booked shifts for all beneficiaries
     */
    public function getAllBookedShifts()
    {
        $shifts = new ArrayCollection();
        foreach ($this->getBeneficiaries() as $beneficiary) {
            foreach ($beneficiary->getBookedShifts() as $shift) {
                $shifts->add($shift);
            }
        }
        return $shifts;
    }

    /**
     * Get all reserved shifts for all beneficiaries
     */
    public function getReservedShifts()
    {
        $shifts = new ArrayCollection();
        foreach ($this->getBeneficiaries() as $beneficiary) {
            foreach ($beneficiary->getReservedShifts() as $shift) {
                $shifts->add($shift);
            }
        }
        return $shifts;
    }


    /**
     * Get shifts of a specific cycle
     * @param $cycleOffset to chose a cycle (0 for current cycle, 1 for next, -1 for previous)
     */
    public function getShiftsOfCycle($cycleOffset = 0, $excludeDismissed = false)
    {
        return $this->getAllShifts($excludeDismissed)->filter(function($shift) use ($cycleOffset) {
            return $shift->getStart() > $this->startOfCycle($cycleOffset) &&
                $shift->getEnd() < $this->endOfCycle($cycleOffset);
        });
    }

    /**
     * Get start date of current cycle
     * IMPORTANT : time are reset, only date are kept
     */
    public function startOfCycle($cycleOffset = 0)
    {
        if (!isset($this->_startOfCycle) || !isset($this->_startOfCycle[$cycleOffset])) {
            if (!isset($this->_startOfCycle) || !isset($this->_startOfCycle[0])){
                if (!isset($this->_startOfCycle)) {
                    $this->_startOfCycle = array();
                }
                $firstDate = $this->getFirstShiftDate();
                $modFirst = null;
                $now = new DateTime('now');
                $now->setTime(0, 0, 0);
                if ($firstDate) {
                    $diff = $firstDate->diff($now);
                    $currentCycleCount = intval($diff->format('%a') / 28);
                }else{
                    $firstDate = new DateTime('now');
                    $currentCycleCount = 0;
                }
                $this->_startOfCycle[0] = clone($firstDate);
                if ($firstDate < $now) {
                    $this->_startOfCycle[0]->modify("+" . (28 * $currentCycleCount) . " days");
                }
            }
            if ($cycleOffset != 0 ){
                $this->_startOfCycle[$cycleOffset] = clone($this->_startOfCycle[0]);
                $this->_startOfCycle[$cycleOffset]->modify((($cycleOffset>0)?"+":"").(28*$cycleOffset)." days");
            }
        }

        return $this->_startOfCycle[$cycleOffset];
    }

    /**
     * Get end date of current cycle
     */
    public function endOfCycle($cycleOffset = 0)
    {
        if (!isset($this->_endOfCycle) || !isset($this->_endOfCycle[$cycleOffset])) {
            if (!isset($this->_endOfCycle) || !isset($this->_endOfCycle[0])) {
                if (!isset($this->_endOfCycle)) {
                    $this->_endOfCycle = array();
                }
                $this->_endOfCycle[0] = clone($this->startOfCycle());
                $this->_endOfCycle[0]->modify("+27 days");
                $this->_endOfCycle[0]->setTime(23, 59, 59);
            }

            if ($cycleOffset != 0 ){
                $this->_endOfCycle[$cycleOffset] = clone($this->_endOfCycle[0]);
                $this->_endOfCycle[$cycleOffset]->modify("+".(28*$cycleOffset)."days");
            }
        }

        return $this->_endOfCycle[$cycleOffset];
    }

    /**
     * Get all rebooked shifts in the future
     */
    public function getFutureRebookedShifts()
    {
        return $this->getAllBookedShifts()->filter(function($shift) {
            return $shift->getStart() > new DateTime('now') &&
                $shift->getBooker() != $shift->getShifter();
        });
    }

    /**
     * Can book a shift
     *
     * @param \AppBundle\Entity\Beneficiary $beneficiary
     * @param \AppBundle\Entity\Shift $shift
     *
     * @return Boolean
     */
    public function canBook(Beneficiary $beneficiary = null, Shift $shift = null)
    {
        if (!$beneficiary || !$shift) // in general, not for a specific beneficiary and shift
    	    return $this->remainingToBook() > 0 || $this->remainingToBook(1) > 0 ;
        else{
            if ($beneficiary->getUser() != $this){
                return false;
            }
            if ($shift->getShifter() && !$shift->getIsDismissed()) {
                return false;
            }
            if ($shift->getRole() && !$beneficiary->getRoles()->contains($shift->getRole())){
                return false;
            }
            return ($shift->getStart() > $this->endOfCycle() || $shift->getDuration() <= $this->remainingToBook())
            && ($shift->getStart() < $this->startOfCycle(1) || $shift->getDuration() <= $this->remainingToBook(1))
            && (($shift->getIsDismissed() && $shift->getBooker()->getId() != $beneficiary->getId()) || !$shift->getShifter())
            && ((!$shift->getLastShifter() || $beneficiary->getId() == $shift->getLastShifter()->getId()));
        }
    }

    /**
     * Get total shift time for a cycle
     */
    // TODO Valeur à mettre dans une conf
    public function shiftTimeByCycle()
    {
        return 60 * 3;
    }

    /**
     * Get remaining time to book
     */
    public function remainingToBook($cycleOffset = 0, $excludeDismissed = false) {
        return max(0, $this->shiftTimeByCycle() - $this->getCycleShiftsDuration($cycleOffset, $excludeDismissed));
    }

    /**
     * Add note
     *
     * @param \AppBundle\Entity\Note $note
     *
     * @return User
     */
    public function addNote(\AppBundle\Entity\Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Remove note
     *
     * @param \AppBundle\Entity\Note $note
     */
    public function removeNote(\AppBundle\Entity\Note $note)
    {
        $this->notes->removeElement($note);
    }

    /**
     * Get notes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Add annotation
     *
     * @param \AppBundle\Entity\Note $annotation
     *
     * @return User
     */
    public function addAnnotation(\AppBundle\Entity\Note $annotation)
    {
        $this->annotations[] = $annotation;

        return $this;
    }

    /**
     * Remove annotation
     *
     * @param \AppBundle\Entity\Note $annotation
     */
    public function removeAnnotation(\AppBundle\Entity\Note $annotation)
    {
        $this->annotations->removeElement($annotation);
    }

    /**
     * Get annotations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * Check if registration is possible
     *
     * @param \DateTime $date
     * @return boolean
     */
    public function canRegister(\DateTime $date = null)
    {
        $remainder = $this->getRemainder($date);
        if ( ! $remainder->invert ){ //still some days
            $min_delay_to_anticipate =  \DateInterval::createFromDateString('15 days');
            $now = new \DateTimeImmutable();
            $away = $now->add($min_delay_to_anticipate);
            $now = new \DateTimeImmutable();
            $expire = $now->add($remainder);
            return ($expire < $away);
        }
        else {
            return true;
        }
    }

    /**
     * get remainder
     *
     * @return \DateInterval|false
     */
    public function getRemainder(\DateTime $date = null)
    {
        if (!$date){
            $date = new \DateTime('now');
        }
        if (!$this->getLastRegistration()){
            $expire = new \DateTime('-1 day');
            return date_diff($date,$expire);
        }
        $expire = clone $this->getLastRegistration()->getDate();
        $expire = $expire->add(\DateInterval::createFromDateString('1 year'));
        return date_diff($date,$expire);
    }

    /**
     * Add receivedProxy
     *
     * @param \AppBundle\Entity\Proxy $receivedProxy
     *
     * @return User
     */
    public function addReceivedProxy(\AppBundle\Entity\Proxy $receivedProxy)
    {
        $this->received_proxys[] = $receivedProxy;

        return $this;
    }

    /**
     * Remove receivedProxy
     *
     * @param \AppBundle\Entity\Proxy $receivedProxy
     */
    public function removeReceivedProxy(\AppBundle\Entity\Proxy $receivedProxy)
    {
        $this->received_proxys->removeElement($receivedProxy);
    }

    /**
     * Get receivedProxys
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReceivedProxys()
    {
        return $this->received_proxys;
    }

    /**
     * Get receivedProxies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReceivedProxies()
    {
        return $this->received_proxies;
    }

    /**
     * Add givenProxy
     *
     * @param \AppBundle\Entity\Proxy $givenProxy
     *
     * @return User
     */
    public function addGivenProxy(\AppBundle\Entity\Proxy $givenProxy)
    {
        $this->given_proxies[] = $givenProxy;

        return $this;
    }

    /**
     * Remove givenProxy
     *
     * @param \AppBundle\Entity\Proxy $givenProxy
     */
    public function removeGivenProxy(\AppBundle\Entity\Proxy $givenProxy)
    {
        $this->given_proxies->removeElement($givenProxy);
    }

    /**
     * Get givenProxies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGivenProxies()
    {
        return $this->given_proxies;
    }

    public function getAutocompleteLabel(){
        if ($this->getMainBeneficiary())
            return '#'.$this->getMemberNumber().' '.$this->getFirstname().' '.$this->getLastname();
        else
            return '#'.$this->getMemberNumber().' '.$this->getUsername();
    }


    /**
     * Set firstShiftDate
     *
     * @param \DateTime $firstShiftDate
     *
     * @return User
     */
    public function setFirstShiftDate($firstShiftDate)
    {
        $this->firstShiftDate = $firstShiftDate;
        return $this;
    }

    /**
     * Get firstShiftDate
     *
     * @return \DateTime
     */
    public function getFirstShiftDate()
    {
        return $this->firstShiftDate;
    }
}
