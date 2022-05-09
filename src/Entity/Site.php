<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Site
 *
 * @ORM\Table(name="Project")
 * @ORM\Entity
 */
class Site
/* implements JsonLdSerializable */
{
    protected static $termsById = null;

    public static function initTerms($em)
    {
        if (!is_null(self::$termsById)) {
            return; // already initialized
        }

        self::$termsById = [];

        $qb = $em->createQueryBuilder();

        $qb->select([ 'T' ])
            ->from('App\Entity\Term', 'T')
            ->andWhere('T.category IN (:categories) AND T.status <> -1')
            ->setParameter('categories', [
                'type',
                'roleActor',
                'condition',
                'education',
            ])
            ;

        foreach ($qb->getQuery()->getResult() as $term) {
            self::$termsById[$term->getId()] = $term;
        }
    }

    use InfoTrait;

    protected $info = [];
    protected $extractFromNotes = [ 'address', 'general' ];

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status = '0';

    /**
     * @ORM\Column(name="type", type="simple_array", nullable=false)
     */
    private $types;

    /**
     * @var Place The location of, for example, where an event is happening, where an organization is located, or where an action takes place. .
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Place", inversedBy="sites")
     * @ORM\JoinColumn(name="locality_id", referencedColumnName="id")
     */
    protected $location;

    /**
     * @var string Name of the location.
     *
     * @ORM\Column(nullable=true,name="locality")
     */
    protected $locationLabel;

    /**
     * @var string Street Address of the location.
     *
     * @ORM\Column(nullable=true,name="street_address")
     */
    protected $streetAddress;

    /**
     * @var string Postal Code of the location.
     *
     * @ORM\Column(nullable=true,name="postal_code")
     */
    protected $postalCode;

    /**
     * @var double The latitude of the place.
     *
     * @ORM\Column(nullable=true)
     *
     */
    protected $latitude;

    /**
     * @var double The longitude of the place.
     *
     * @ORM\Column(nullable=true)
     *
     */
    protected $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="start_date", type="string", nullable=true)
     */
    private $startDate;

    /**
     * @var string
     *
     * @ORM\Column(name="realized_date", type="string", nullable=true)
     */
    private $realizedDate;

    /**
     * @var string
     *
     * @ORM\Column(name="end_date", type="string", nullable=true)
     */
    private $endDate;

    /**
     * @var string
     *
     * @ORM\Column(name="destruction_date", type="string", nullable=true)
     */
    private $destructionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=511, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="alternate_name", type="text", nullable=true)
     */
    private $alternateName;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="json_array", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var double
     *
     * @ORM\Column(name="operating_area", type="decimal", nullable=true)
     */
    private $operatingArea;

    /**
     * @var array
     *
     * @ORM\Column(name="operating_area_description", type="json_array", length=65535, nullable=true)
     */
    private $operatingAreaDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var array|null
     *
     * @ORM\Column(name="notes", type="json_array", length=65535, nullable=true)
     */
    private $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer", nullable=true)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="changed", type="datetime", nullable=true)
     */
    private $changedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="changed_by", type="integer", nullable=true)
     */
    private $changedBy;

    /**
     * @var integer
     *
     * xORM\Column(name="flags", type="integer", nullable=false)
     */
    private $flags = '0';

    /**
     * xORM\ManyToMany(targetEntity="Person", mappedBy="sites")
     * xORM\OrderBy({"familyName" = "ASC", "givenName" = "ASC"})
     */
    protected $persons;

    public static function extractYear($datetime)
    {
        if (is_null($datetime)) {
            return $datetime;
        }

        if (preg_match('/^([\d]+)/', $datetime, $matches)) {
            return (int)($matches[1]);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAlternateName()
    {
        return $this->alternateName;
    }

    public function getStatus()
    {
        return $this->status;
    }

    private function getTypeIds()
    {
        if (empty($this->types)) {
            return $this->types;
        }

        // simple_array doesn't trim space after ,
        return array_map('trim', $this->types);
    }

    public function getTypes()
    {
        $terms = [];

        $typeIds = $this->getTypeIds();
        if (empty($typeIds) || is_null(self::$termsById)) {
            return $terms;
        }

        foreach ($typeIds as $termId) {
            if (array_key_exists($termId, self::$termsById)) {
                $terms[] = self::$termsById[$termId];
            }
        }

        return $terms;
    }

    public function buildStatusLabel()
    {
        if (is_null($this->status) || !array_key_exists($this->status, \App\Search\SearchListBuilder::$STATUS_LABELS)) {
            return '';
        }

        return \App\Search\SearchListBuilder::$STATUS_LABELS[$this->status];
    }

    /**
     * Sets foundingLocation.
     *
     * @param Place $foundingLocation
     *
     * @return $this
     */
    public function setFoundingLocation(Place $foundingLocation = null)
    {
        $this->foundingLocation = $foundingLocation;

        return $this;
    }

    /**
     * Gets location.
     *
     * @return Place
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Gets locationLabel.
     *
     * @return string
     */
    public function getLocationLabel()
    {
        return $this->locationLabel;
    }

    /**
     * Gets location info
     *
     */
    public function getLocationInfo($locale = 'en')
    {
        if (!is_null($this->location)) {
            return Agent::buildPlaceInfo($this->location, $locale);
        }

        if (!empty($this->locationLabel)) {
            return [
                'name' => $this->locationLabel,
            ];
        }
    }

    /**
     * Gets streetAddress.
     *
     * @return string
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * Gets postalCode.
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Gets geo.
     *
     * @return string
     */
    public function getGeo()
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            if (!is_null($this->location)) {
                return $this->location->getGeo();
            }

            return null;
        }

        return implode(',', [$this->latitude, $this->longitude]);
    }

    public function getStartDate()
    {
        return $this->startDate; // self::stripTime($this->startDate);
    }

    public function getRealizedDate()
    {
        return $this->realizedDate; // self::stripTime($this->endDate);
    }

    public function getEndDate()
    {
        return $this->endDate; // self::stripTime($this->endDate);
    }

    public function getStartear()
    {
        return self::extractYear($this->startDate);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getOperatingArea()
    {
        return $this->operatingArea;
    }

    /**
     *
     */
    public function getOperatingAreaDescriptionLocalized($locale = 'de')
    {
        if (empty($this->operatingAreaDescription)) {
            return;
        }

        if (is_array($this->operatingAreaDescription)) {
            if (array_key_exists($locale, $this->operatingAreaDescription)
                && !empty($this->operatingAreaDescription[$locale]))
            {
                return $this->operatingAreaDescription[$locale];
            }

            // fallback
            if (array_key_exists('de', $this->operatingAreaDescription)
                && !empty($this->operatingAreaDescription['de']))
            {
                return $this->operatingAreaDescription['de'];
            }
        }
    }

    /**
     * TODO
     */
    public function getDescriptionLocalized($locale = 'de')
    {
        if (empty($this->description)) {
            return;
        }

        if (is_array($this->description)) {
            if (array_key_exists($locale, $this->description)
                && !empty($this->description[$locale]))
            {
                return $this->description[$locale];
            }

            // fallback
            if (array_key_exists('de', $this->description)
                && !empty($this->description['de']))
            {
                return $this->description['de'];
            }
        }

        return $this->getDescription();
    }

    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * Sets url.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Gets url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function getPersons()
    {
        return $this->persons;
    }

    public function jsonLdSerialize($locale, $omitContext = false)
    {
        $ret = [
            '@context' => 'http://schema.org',
            '@type' => 'CreateAction',
            'name' => $this->getName(),
        ];

        if ($omitContext) {
            unset($ret['@context']);
        }

        foreach ([ 'start', 'end'] as $key) {
            $property = $key . 'date';
            if (!empty($this->$property)) {
                $ret[$property . 'Time'] = \AppBundle\Utils\JsonLd::formatDate8601($this->$property);
            }
        }

        if (!empty($this->location)) {
            $ret['location'] = [
                '@type' => 'Place',
                'name' => $this->location->getName(),
            ];

            // TODO
            $addresses = [];
            /*
            $addresses = array_map(function ($address) { return $address['info']; }, $this->location->getAddressesSeparated());
            if (!empty($addresses)) {
                $ret['location']['address'] = join(', ', $addresses);
            }
            */

            /*
            // TODO
            $place = $this->location->getPlace();
            if (!empty($place)) {
                $ret['location']['containedInPlace'] = $place->jsonLdSerialize($locale, true);
            }
            */
        }

        $description = $this->getDescriptionLocalized($locale);
        if (!empty($description)) {
            $ret['description'] = $description;
        }

        foreach ([ 'url' ] as $property) {
            if (!empty($this->$property)) {
                $ret[$property] = $this->$property;
            }
        }

        return $ret;
    }

    /* make private properties public through a generic __get / __set */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            return $this->$name = $value;
        }
    }
}
