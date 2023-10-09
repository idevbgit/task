<?php
    namespace App\Stats;

    use App\Entity\Project;
    use App\Entity\Timelog;
    use Doctrine\ORM\EntityManager;
    use Doctrine\ORM\Query;

    /**
     * The Stats class implements an application that
     * calculates the stats by days(until 6 months) or by months(until 5 years)
     * Maximal values can be increased if necessary
     */

    class Stats {
        public $types = ["day", "month"];
        public $seconds = [
            "day" => 86400,
            "month" => 30*86400
        ];
        public $maxValues = [
            "day" => 180,
            "month" => 60
        ];
        protected $em;

        public function __construct(EntityManager $em)
        {
            $this->em = $em;
        }

        /**
         * The method below returns the timelog rows starting from 
         * the received timestamp by start_time field
         * @param int $afterTimestamp
         * @return array[] of timelogs
         */
        public function getRows($afterTimestamp): Array {
            return $this->em->getRepository(Timelog::class)
                ->createQueryBuilder('c')
                ->where('c.start_time > :stime')
                ->setParameter('stime', $afterTimestamp)
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY);
        }

        public function hours($seconds): float {
            return number_format($seconds/3600, 1);
        }

        /**
         * The method below counts the hours of each day and 
         * stores the start timestamp of that day and the day
         * @param int $number - a number of days
         * @param array $rows - an array of timelogs
         * @param bool $forMonths - date for months (true) or final result?
         * @return array an array of an array of hours, days..
         */
        public function countHoursByDays($number, $rows, $forMonths = false): Array {
            $todayStarted = strtotime("today", time());
            $hours = array();
            $days = array();
            if($forMonths)
                $daysStart = array();

            $c = count($rows);
            for($i=0; $i<$number; $i++) {
                $endOfDay = $todayStarted-$i*$this->seconds["day"];
                $startOfDay = $todayStarted-($i+1)*$this->seconds["day"];
                $hours[$i] = 0;
                $days[$i] = date('d', $startOfDay);
                if($forMonths)
                    $daysStart[$i] = $startOfDay;
                for($j=0; $j<$c; $j++) {
                    if(($rows[$j]["start_time"] > $startOfDay) && ($rows[$j]["start_time"] <= $endOfDay) && $rows[$j]["finish_time"]) {
                        if($rows[$j]["finish_time"] > $endOfDay) {
                            $hours[$i] += $this->hours($endOfDay-$rows[$j]["start_time"]);
                        } else {
                            $hours[$i] += $this->hours($rows[$j]["finish_time"]-$rows[$j]["start_time"]);
                        }
                    } else if(($rows[$j]["finish_time"] > $startOfDay) && ($rows[$j]["finish_time"] <= $endOfDay)) {
                        $hours[$i] += $this->hours($rows[$j]["finish_time"]-$startOfDay);
                    } else if(($rows[$j]["finish_time"] > $endOfDay) && ($rows[$j]["start_time"] <= $startOfDay)) {
                        $hours[$i] += $this->hours($this->seconds["day"]);
                    }
                }
            }
            return $forMonths ? [
                "hours" => $hours,
                "days" => $days,
                "daysStart" => $daysStart
            ] : [
                "success" => true,
                "hours" => $hours,
                "hoursBy" => $days,
                "label" => "Total hours per day"
            ];
        }

        /**
         * The method below counts the hours of each month
         * @param int $numberOfMonths - a number of months
         * @param array $rows - an array of timelogs
         * @return array with fields: (array) hours, (array) days/hoursBy,
         * (array) dayStats or (bool) success and (string) label
         */
        public function countHoursByMonths($numberOfMonths, $rows) {
            $data = $this->countHoursByDays(round($numberOfMonths*30.5+30), $rows, true);

            $months = [];
            $hours = [];
            $days = $data["days"];
            $c = count($days);
            if($c > 2) {
                $lastMonth = date('M', $data["daysStart"][0]);
                $lastMonthHours = $data["hours"][0];

                for($i=1; $i<$c; $i++) {
                    if($days[$i-1] == '01') {
                        if($lastMonth) {
                            array_push($months, $lastMonth);
                            array_push($hours, number_format($lastMonthHours, 2));
                            if(count($months) == $numberOfMonths)
                                break;

                            $lastMonth = date('M', $data["daysStart"][$i]);
                            $lastMonthHours = $data["hours"][$i];
                        }
                    } else {
                        $lastMonthHours += $data["hours"][$i];
                    }
                }
            }

            return [
                "success" => true,
                "hours" => $hours,
                "hoursBy" => $months,
                "label" => "Total hours per month"
            ];
        }

        /**
         * The method below generates statistics depending on 
         * the type of day or month, the number of days or months
         * @param int $number - a number of days/months
         * @param array $type - type of stats (day/month)
         * @return array with fields: (bool) success, (array) hours, (array) hoursBy, (string) label
         */
        public function createStats($number, $type): Array {
            if(in_array($type, $this->types) && ($number > 0) && ($number < $this->maxValues[$type])) {
                $seconds = $number*$this->seconds[$type]+$this->seconds[$type];
                $rows = $this->getRows(time()-$seconds);

                if($type === "day")
                    return $this->countHoursByDays($number, $rows);
                if($type === "month")
                    return $this->countHoursByMonths($number, $rows);
            } 
            return [
                "success" => false
            ];
        }

        public function prepareStatsForCsv($data) {
            $result = array();
            $c = count($data["hours"]);

            for($i=0; $i<$c; $i++) {
                $result[$data["hoursBy"][$i]] = $data["hours"][$i];
            }

            return $result;
        }
    }
?>