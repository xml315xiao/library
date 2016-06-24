<?php

if ( ! function_exists('mysql_date'))
{
    /**
     * Convert MySQL-formatted date
     * eg. Year: %Y Month: %m Day: %d - %h:%i %a
     *
     * @param   $format string
     * @param   $time   int
     * @return  string
     */
    function mysql_date($format, $time = NULL)
    {
        if (strlen($format) === 0) {
            return '';
        }

        if (empty($time)) {
            $time = time();
        }

        $format = str_replace('%\\', '', preg_replace('/([a-z]+){1}/Ui', '\\\\\\1', $format));

        return date($format, $time);
    }
}

if ( ! function_exists('human_date'))
{
    /**
     * Unix to Human
     * Formats Unix timestamp to the following prototype: 2006-08-21 11:35 PM
     *
     * @param $time
     * @param bool $is_am
     * @param bool $is_seconds
     * @return string
     */
    function human_date($time, $is_am = FALSE, $is_seconds = TRUE)
    {
        $date = date('Y-m-d', $time). ' ';

        if ($is_am) {
            $date.= date('h:i', $time);
        } else {
            $date.= date('H:i', $time);
        }

        if ($is_seconds) {
            $date.= ':'. date('s', $time);
        }

        if ($is_am) {
            return $date. ' '. date('A', $time);
        } else {
            return $date;
        }
    }
}

if ( ! function_exists('nice_date'))
{
    /**
     * Turns many "reasonably-date-like" strings into something
     * that is actually useful. This only works for dates after unix epoch.
     *
     * @param string $bad_date
     * @param string $format
     * @return bool|string
     */
    function nice_date($bad_date = '', $format = 'Y-m-d')
    {
        if (strlen($bad_date) === 0) {
            return FALSE;
        }

        // Date like: YYYYMM
        if (preg_match('/^\d{6}$/i', $bad_date)) {
            if (in_array(substr($bad_date, 0, 2), array('19', '20'))) {
                $year = substr($bad_date, 0, 4);
                $month = substr($bad_date, 4, 2);
            } else {
                $month = substr($bad_date, 0, 2);
                $year = substr($bad_date, 2, 4);
            }

            return date($format, strtotime($year . '-' . $month . '-01'));
        }

        // Date Like: YYYYMMDD
        if (preg_match('/^(\d{2})\d{2}(\d{4})$/i', $bad_date, $matches)) {
            return date($format, strtotime($matches[1] . '/01/' . $matches[2]));
        }

        // Date Like: MM-DD-YYYY __or__ M-D-YYYY (or anything in between)
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/i', $bad_date, $matches)) {
            return date($format, strtotime($matches[3] . '-' . $matches[1] . '-' . $matches[2]));
        }

        // Any other kind of string, when converted into UNIX time,
        // produces "0 seconds after epoc..." is probably bad...
        // return "Invalid Date".
        if (date('U', strtotime($bad_date)) === '0') {
            return FALSE;
        }

        // It's probably a valid-ish date format already
        return date($format, strtotime($bad_date));
    }
}

if ( ! function_exists('days_in_month'))
{
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
    function days_in_month($month = 0, $year = '')
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

if ( ! function_exists('zero_time_of_today'))
{
    /**
     * Fetch the beginning of today as unix format.
     */
    function zero_time_of_today()
    {
        return strtotime(date('Y-m-d 00:00:00'));
    }
}

if ( ! function_exists('zero_time_of_week'))
{
    /**
     * Fetch the beginning of this week as unix format
     */
    function zero_time_of_week()
    {
        $date_obj = new DateTime();
        $date_obj->modify('this week');
        $date_mon = $date_obj->format('Y-m-d 00:00:00');

        return strtotime($date_mon);
    }
}
