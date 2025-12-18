<?php

namespace App\Repository;

use DateTime;
use DateInterval;
use App\Entity\Visitor;
use InvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class VisitorRepository
 *
 * Repository for visitor database entity
 *
 * @extends ServiceEntityRepository<Visitor>
 *
 * @package App\Repository
 */
class VisitorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visitor::class);
    }

    /**
     * Get list of all IDs from the database
     *
     * @return array<int> An array containing all IDs from the database
     */
    public function getAllIds(): array
    {
        // select ids
        $queryBuilder = $this->createQueryBuilder('v')->select('v.id');
        $query = $queryBuilder->getQuery();

        // get results
        $results = $query->getScalarResult();

        // return id list
        return array_column($results, 'id');
    }

    /**
     * Get visitors based on the specified time filter
     *
     * @param string $filter The filter for the time period
     *
     * @return array<mixed> An array of visitors filtered by the specified time range
     *
     * @throws InvalidArgumentException If the filter is not valid
     */
    public function findByTimeFilter(string $filter): array
    {
        $now = new DateTime();
        $startDate = null;

        // calculate start date based on the filter
        switch ($filter) {
            case 'H':
                $startDate = $now->sub(new DateInterval('PT1H'));
                break;
            case 'D':
                $startDate = $now->sub(new DateInterval('P1D'));
                break;
            case 'W':
                $startDate = $now->sub(new DateInterval('P7D'));
                break;
            case 'M':
                $startDate = $now->sub(new DateInterval('P1M'));
                break;
            case 'Y':
                $startDate = $now->sub(new DateInterval('P1Y'));
                break;
            case 'ALL':
                return $this->findAll();
            default:
                throw new InvalidArgumentException("Invalid filter: $filter");
        }

        // create a query builder
        $qb = $this->createQueryBuilder('v');
        $qb->where('v.first_visit >= :start_date')->setParameter('start_date', $startDate);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get visitors based on the specified time filter as an iterable result
     *
     * @param string $filter The filter for the time period
     *
     * @return iterable<Visitor> An iterable of visitors filtered by the specified time range
     *
     * @throws InvalidArgumentException If the filter is not valid
     */
    public function findByTimeFilterIterable(string $filter): iterable
    {
        $now = new DateTime();
        $startDate = null;

        // calculate start date based on the filter
        switch ($filter) {
            case 'H':
                $startDate = $now->sub(new DateInterval('PT1H'));
                break;
            case 'D':
                $startDate = $now->sub(new DateInterval('P1D'));
                break;
            case 'W':
                $startDate = $now->sub(new DateInterval('P7D'));
                break;
            case 'M':
                $startDate = $now->sub(new DateInterval('P1M'));
                break;
            case 'Y':
                $startDate = $now->sub(new DateInterval('P1Y'));
                break;
            case 'ALL':
                return $this->createQueryBuilder('v')->getQuery()->toIterable();
            default:
                throw new InvalidArgumentException("Invalid filter: $filter");
        }

        // create a query builder
        $qb = $this->createQueryBuilder('v');
        $qb->where('v.first_visit >= :start_date')->setParameter('start_date', $startDate);

        return $qb->getQuery()->toIterable();
    }

    /**
     * Get count of visitors grouped by date based on the specified time period
     *
     * @param string $period The time period for which to retrieve the visitor count.
     *               Valid values are 'last_24_hours', 'last_week', 'last_month', 'last_year', and 'all_time'
     *
     * @return array<string,int> An associative array where the key is the date (formatted based on the period)
     *               and the value is the count of visitors for that date
     *
     * @throws InvalidArgumentException If an invalid period is specified
     */
    public function getVisitorsCountByPeriod(string $period): array
    {
        $qb = $this->createQueryBuilder('v');

        // select data where period
        switch ($period) {
            case 'last_24_hours':
                $qb->select('v.last_visit AS visitDate')
                   ->where('v.last_visit >= :startDate')
                   ->setParameter('startDate', new DateTime('-24 hours'));
                break;

            case 'last_week':
                $qb->select('v.last_visit AS visitDate')
                   ->where('v.last_visit >= :startDate')
                   ->setParameter('startDate', new DateTime('-7 days'));
                break;

            case 'last_month':
                $qb->select('v.last_visit AS visitDate')
                   ->where('v.last_visit >= :startDate')
                   ->setParameter('startDate', new DateTime('-1 month'));
                break;

            case 'last_year':
                $qb->select('v.last_visit AS visitDate')
                   ->where('v.last_visit >= :startDate')
                   ->setParameter('startDate', new DateTime('-1 year'));
                break;

            case 'all_time':
                $qb->select('v.last_visit AS visitDate');
                break;
            default:
                throw new InvalidArgumentException('Invalid period specified.');
        }

        // get results
        $results = $qb->getQuery()->getResult();

        // format view date results
        $visitorCounts = [];
        foreach ($results as $result) {
            $date = $result['visitDate'];
            switch ($period) {
                case 'last_24_hours':
                    $dateKey = $date->format('H');
                    break;
                case 'last_week':
                    $dateKey = $date->format('m/d');
                    break;
                case 'last_month':
                    $dateKey = $date->format('m/d');
                    break;
                case 'last_year':
                    $dateKey = $date->format('Y/m');
                    break;
                case 'all_time':
                    $dateKey = $date->format('Y/m');
                    break;
            }
            if (!isset($visitorCounts[$dateKey])) {
                $visitorCounts[$dateKey] = 0;
            }
            $visitorCounts[$dateKey]++;
        }

        // set not found visitors count to 0
        if ($period === 'last_24_hours') {
            $visitorsCountByHour = [];
            for ($i = 0; $i < 24; $i++) {
                $hourKey = (new DateTime("-{$i} hours"))->format('H');
                $visitorsCountByHour[$hourKey] = $visitorCounts[$hourKey] ?? 0;
            }
            return $visitorsCountByHour;
        }

        return $visitorCounts;
    }

    /**
     * Get list of countries and their count
     *
     * @return array<string,int> An associative array where the key is the country and the value is the count of visitors for that country
     */
    public function getVisitorsByCountry(): array
    {
        $results = $this->createQueryBuilder('v')
            ->select('v.country AS country, COUNT(v.id) AS visitorCount')
            ->groupBy('v.country')
            ->orderBy('visitorCount', 'DESC')
            ->getQuery()
            ->getResult();

        // convert results to associative array
        $visitorsByCountry = [];
        foreach ($results as $result) {
            $visitorsByCountry[$result['country']] = $result['visitorCount'];
        }

        return $visitorsByCountry;
    }

    /**
     * Get list of cities and their count
     *
     * @return array<string,int> An associative array where the key is the city and the value is the count of visitors for that city
     */
    public function getVisitorsByCity(): array
    {
        $results = $this->createQueryBuilder('v')
            ->select('v.city AS city, COUNT(v.id) AS visitorCount')
            ->groupBy('v.city')
            ->orderBy('visitorCount', 'DESC')
            ->getQuery()
            ->getResult();

        // convert results to associative array
        $visitorsByCity = [];
        foreach ($results as $result) {
            $visitorsByCity[$result['city']] = $result['visitorCount'];
        }

        return $visitorsByCity;
    }

    /**
     * Get list of used browsers and their count
     *
     * @return array<string,int> An associative array where the key is the browser and the value is the count of visitors for that browser
     */
    public function getVisitorsUsedBrowsers(): array
    {
        $results = $this->createQueryBuilder('v')
            ->select('v.browser AS browser, COUNT(v.id) AS visitorCount')
            ->groupBy('v.browser')
            ->orderBy('visitorCount', 'DESC')
            ->getQuery()
            ->getResult();

        // convert results to associative array
        $visitorsByBrowser = [];
        foreach ($results as $result) {
            $visitorsByBrowser[$result['browser']] = $result['visitorCount'];
        }

        return $visitorsByBrowser;
    }

    /**
     * Get visitors referers
     *
     * @return array<string, int> referers and visitors count
     */
    public function getVisitorsReferers(): array
    {
        $results = $this->createQueryBuilder('v')
            ->select('v.referer AS referer, COUNT(v.id) AS visitorCount')
            ->groupBy('v.referer')
            ->orderBy('visitorCount', 'DESC')
            ->getQuery()
            ->getResult();

        // convert results to associative array
        $visitorsReferers = [];
        foreach ($results as $result) {
            $visitorsReferers[$result['referer']] = $result['visitorCount'];
        }

        return $visitorsReferers;
    }
}
