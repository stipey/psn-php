<?php
namespace Tustin\PlayStation\Factory;

use Iterator;
use IteratorAggregate;
use Tustin\PlayStation\Api;
use InvalidArgumentException;
use Tustin\PlayStation\Model\User;
use Tustin\PlayStation\Enum\ConsoleType;
use Tustin\PlayStation\Enum\LanguageType;
use Tustin\PlayStation\Model\TrophyTitle;
use Tustin\PlayStation\Interfaces\FactoryInterface;
use Tustin\PlayStation\Exception\NoTrophiesException;
use Tustin\PlayStation\Iterator\TrophyTitlesIterator;
use Tustin\PlayStation\Exception\MissingPlatformException;
use Tustin\PlayStation\Iterator\Filter\TrophyTitle\TrophyTitleNameFilter;
use Tustin\PlayStation\Iterator\Filter\TrophyTitle\TrophyTitleHasGroupsFilter;

class TrophyTitlesFactory extends Api implements IteratorAggregate, FactoryInterface
{
    /**
     * Platforms for filtering.
     *
     * @var array
     */
    protected $platforms = [];

    /**
     * The trophy title name for filtering.
     *
     * @var string
     */
    private $withName = '';

    /**
     * The user to get trophy titles for.
     *
     * @var User
     */
    private $user;

    /**
     * Filter property for having trophy groups.
     * 
     * We want this to be null by default so that if the client doesn't call hasTrophyGroups, it will return all titles.
     *
     * @var boolean|null
     */
    private ?bool $hasTrophyGroups = null;

    public function __construct(User $user)
    {
        parent::__construct($user->getHttpClient());

        $this->user = $user;
    }

    /**
     * Filters trophy titles only for the supplied platform(s).
     *
     * @param ConsoleType ...$platforms
     * @return TrophyTitlesFactory
     */
    public function platforms(ConsoleType ...$platforms) : TrophyTitlesFactory
    {
        $this->platforms = $platforms;

        return $this;
    }

    /**
     * Filters trophy titles that either have trophy groups or no trophy groups.
     *
     * @param boolean $value
     * @return TrophyTitlesFactory
     */
    public function hasTrophyGroups(bool $value = true) : TrophyTitlesFactory
    {
        $this->hasTrophyGroups = $value;

        return $this;
    }

    /**
     * Filters trophy titles to only get titles containing the supplied name.
     *
     * @param string $name
     * @return TrophyTitlesFactory
     */
    public function withName(string $name) : TrophyTitlesFactory
    {
        $this->withName = $name;
        
        return $this;
    }

    /**
     * Gets the iterator and applies any filters.
     *
     * @return Iterator
     */
    public function getIterator() : Iterator
    {
        if (empty($this->platforms))
        {
            throw new MissingPlatformException("TrophyTitles::platforms() must be called once with the specified platforms.");    
        }

        $iterator = new TrophyTitlesIterator($this);

        if ($this->withName)
        {
            $iterator = new TrophyTitleNameFilter($iterator, $this->withName);
        }

        if (!is_null($this->hasTrophyGroups))
        {
            $iterator = new TrophyTitleHasGroupsFilter($iterator, $this->hasTrophyGroups);
        }

        return $iterator;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    /**
     * Gets the current platforms passed to this instance.
     *
     * @return array
     */
    public function getPlatforms() : array
    {
        return $this->platforms;
    }
    
    /**
     * Gets the current language passed to this instance.
     * 
     * If the language has not been set prior, this will return LanguageType::english().
     *
     * @return LanguageType
     */
    public function getLanguage() : LanguageType
    {
        return $this->language ?? LanguageType::english();
    }

    /**
     * Gets the first trophy title in the collection.
     *
     * @return TrophyTitle
     */
    public function first() : TrophyTitle
    {
        try
        {
            return $this->getIterator()->current();
        }
        catch (InvalidArgumentException $e)
        {
            throw new NoTrophiesException("Client has no trophy titles.");
        }
    }
}