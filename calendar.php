<?php

class CI_Calendar
{

    /**
     * Class constructor
     *
     * Sets the default time reference.
     *
     * @param array	$config	Calendar options
     */
    public function __construct($config = array())
    {
        empty($config) OR $this->initialize($config);
    }

    /**
     * Initialize the user preferences
     *
     * Accepts an associative array as input, containing display preferences
     *
     * @param	array
     * @return	object
     */
    public function initialize($config = array())
    {
        foreach ($config as $key => $val)
        {
            if (isset($this->$key))
            {
                $this->$key = $val;
            }
        }

        return $this;
    }

    /**
     * Generate the calendar
     *
     * @param   int      $month
     * @param   string   $year
     * @return	string
     */
    public function generate($month = 0, $year = '')
    {
        $local_time = time();

        // Set and validate the supplied month/year
        if (empty($year)){
            $year = date('Y', $local_time);
        } elseif (strlen($year) === 1) {
            $year = '200'.$year;
        } elseif (strlen($year) === 2) {
            $year = '20'.$year;
        }

        if (empty($month)){
            $month = date('m', $local_time);
        } elseif(strlen($month) === 1) {
            $month = '0'.$month;
        }

        $adjusted_date = $this->adjust_date($month, $year);

        $month = $adjusted_date['month'];
        $year = $adjusted_date['year'];

        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $day = 1 - date('w', $first_day);
        $total_days = $this->days_in_month($month, $year);

        // Begin building the calendar output
        $out = '<table border="0" cellpadding="4" cellspacing="0">'. "\n". '<tr/>'. "\n";

        // Heading containing the month/year
        $out .= '<th colspan="7">June&nbsp;2016</th>'."\n<tr>\n";

        // Week Row
        $out .= "</tr>\n<td/>Sun</td><td/>Mon</td><td/>Tus</td><td/>Wed</td><td/>Thu</td><td/>Fri</td><td/>Sat</td>\n<tr>\n";

        // Build the main body of the calendar
        while ($day <= $total_days)
        {
            for ($i = 0; $i < 7; $i++)
            {
                if ($day <= 0 OR $day > $total_days)
                    $out.= "<td>&nbsp;</td>";
                elseif ($day == 5)
                    $out.= "<strong><td>". $day. "</strong></td>";
                else
                    $out.= "<td style=\"color: #666;\">". $day. "</td>";

                $day++;
            }

            $out.= "\n</tr>\n<tr>\n";
        }

        $out.= "</tr>";
        $out.= "\n</table>";

        return $out;
    }

    /**
     * Adjust Date
     *
     * This function makes sure that we have a valid month/year.
     * For example, if you submit 13 as the month, the year will
     * increment and the month will become January.
     *
     * @param	int	 $month
     * @param	int	 $year
     * @return	array
     */
    public function adjust_date($month, $year)
    {
        $date = array();

        $date['month']	= $month;
        $date['year']	= $year;

        while ($date['month'] > 12)
        {
            $date['month'] -= 12;
            $date['year']++;
        }

        while ($date['month'] <= 0)
        {
            $date['month'] += 12;
            $date['year']--;
        }

        if (strlen($date['month']) === 1)
        {
            $date['month'] = '0'.$date['month'];
        }

        return $date;
    }

    /**
     * Number of days in a month
     *
     * Takes a month/year as input and returns the number of days
     * for the given month/year. Takes leap years into consideration.
     *
     * @param	int	    $month
     * @param	string  $year
     * @return	int
     */
    public function days_in_month($month = 0, $year = '')
    {

        if ($month < 1 OR $month > 12)
            return 0;
        if ( ! is_numeric($year) OR strlen($year) !== 4)
            $year = date('Y');
        if (defined('CAL_GREGORIAN'))
            return cal_days_in_month(CAL_GREGORIAN, $month, $year);
        if ($year >= 1970)
            return (int) date('t', mktime(12, 0, 0, $month, 1, $year));
        if ($month == 2 && ($year % 400 === 0 OR ($year % 4 === 0 && $year % 100 !== 0)))
            return 29;

        $days_in_month	= array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        return $days_in_month[$month - 1];
    }

}