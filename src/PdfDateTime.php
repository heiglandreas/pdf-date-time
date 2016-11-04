<?php
/**
 * Copyright (c) Andreas Heigl<andreas@heigl.org>
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright Andreas Heigl
 * @license   http://www.opensource.org/licenses/mit-license.php MIT-License
 * @since     04.11.2016
 * @link      http://github.com/heiglandreas/org.heigl.PdfDateTime
 */

namespace Org_Heigl\PdfDateTime;

class PdfDateTime
{
    /**
     * Converts an ISO 8824 encoded string into a datetime-Object
     * PDF defines a standard date format, which closely follows that of the
     * international standard ASN.1 (Abstract Syntax Notation One), defined in
     * ISO/ IEC 8824. A date is an ASCII string of the form
     *
     * (D: YYYYMMDDHHmmSSOHH'mm')
     *
     * where
     * * YYYY is the year
     * * MM is the month
     * * DD is the day (01–31)
     * * HH is the hour (00–23)
     * * mm is the minute (00–59)
     * * SS is the second (00–59)
     * * O is the relationship of local time to Universal Time (UT),
     *     denoted by one of the characters +, −, or Z (see below)
     * * HH followed by ' is the absolute value of the offset from UT
     *     in hours (00–23)
     * * mm followed by ' is the absolute value of the offset from UT
     *     in minutes (00–59)
     * The apostrophe character (') after HH and mm is part of the syntax.
     * All fields after the year are optional. (The prefix D:, although also
     * optional, is strongly recommended.) The default values for MM and DD are
     * both 01; all other numerical fields default to zero values. A plus sign
     * (+) as the value of the O field signifies that local time is later than
     * UT, a minus sign (−) signifies that local time is earlier than UT, and
     * the letter Z signifies that local time is equal to UT. If no UT
     * information is specified, the relationship of the specified time to UT
     * is considered to be unknown. Regardless of whether the time zone is
     * known, the rest of the date should be specified in local time.
     *
     * For example, December 23, 1998, at 7:52 PM, U.S. Pacific Standard Time,
     * is represented by the string D:199812231952−08'00'
     *
     * @param string $string An ISO8824 encoded DateTime-String
     *
     * @erturn DateTime
     */
    public static function createDateTimeFromPDFString($string)
    {
        // Clean the String from the prepend 'D:'
        if ( 0 === strpos($string, 'D:')) {
            $date = trim(substr($string, 2));
        }
        preg_match('/^(\d+)/i', $string, $datepart);
        $length = strlen($datepart[1]);
        if ($length < 4) {
            throw new \Exception(sprintf(
                'no PDF format (%1$s)',
                $string
            ));
        }
        $time = [
            'year'   => null,
            'month'  => 1,
            'day'    => 1,
            'hour'   => 0,
            'minute' => 0,
            'second' => 0,
            'offset' => null,
        ];
        $time['year'] = substr($string, 0, 4);
        // Check for month.
        if ($length >= 6) {
            $month = substr($string , 4, 2);
            if ($month < 1 || $month > 12) {
                throw new \Exception(sprintf(
                    'no RFC 2822 format (%1$s)',
                    $string
                ));
            }

            $time['month'] = $month;
        }
        // Check for day
        if ($length >= 8) {
            $day = substr($string, 6, 2);
            if ($day < 1 || $day > 31) {
                throw new \Exception(sprintf(
                    'no RFC 2822 format (%1$s)',
                    $string
                ));
            }

            $time['day'] = $day;
        }
        // Check for Hour
        if ($length >= 10) {
            $hour = substr($string, 8, 2);
            if ($hour < 0 || $hour > 23) {
                throw new \Exception(sprintf(
                    'no RFC 2822 format (%1$s)',
                    $string
                ));
            }
            $time['hour'] = $hour;
        }
        // Check for minute
        if ($length >= 12) {
            $minute = substr($string, 10, 2);
            if ($minute < 0 || $minute > 59) {
                throw new \Exception(sprintf(
                    'no RFC 2822 format (%1$s)',
                    $string
                ));
            }
            $time['minute'] = $minute;
        }
        // Check for seconds
        if ($length >= 14) {
            $second = substr($string, 12, 2);
            if ($second < 0 || $second > 59) {
                throw new \Exception(sprintf(
                    'no RFC 2822 format (%1$s)',
                    $string
                ));
            }
            $time['second'] = $second;
        }
        // Set Offset
        if (preg_match('/([Z\-\+])(\d{2}\'){0,1}(\d{2}\'){0,1}$/', $string, $off)) {
            $offset = $off[1];
            switch ($offset) {
                case '+':
                case '-':
                    $time['offset'] = $offset;
                    if (isset($off[2])) {
                        $offsetHours = substr($off[2], 0, 2);
                        if ($offsetHours < 0 || $offsetHours > 23) {
                            throw new \Exception(sprintf(
                                'no RFC 2822 format (%1$s)',
                                $string
                            ));
                        }
                        $time['offset'] .= $offsetHours;
                    }
                    if (isset($off[3])) {
                        $offsetMinutes = substr($off[3], 0, 2);
                        if ( $offsetMinutes < 0 || $offsetMinutes > 59 ) {
                            throw new \Exception(sprintf(
                                'no RFC 2822 format (%1$s)',
                                $string
                            ));
                        }
                        $time['offset'] .= ':' . $offsetMinutes;
                    }
                    break;
                case 'Z':
                default:
                    $time['offset'] = '+00:00';
            }
        }

        $timestring = $time['year']
                      . '-'
                      . $time['month']
                      . '-'
                      . $time['day']
                      . 'T'
                      . $time['hour']
                      . ':'
                      . $time['minute']
                      . ':'
                      . $time['second'];

        if ($time['offset'] === null) {
            return new \DateTimeImmutable($timestring);
        }
        // Raw-Data is present, so lets create a DateTime-Object from it:
        return new \DateTimeImmutable($timestring, new \Datetimezone($time['offset']));
    }

    /**
     * Creates an ISO 8824 timestring from a DateTime-Object
     *
     * This string can then be used as DateTime within a PDF-File.
     *
     * @param \DateTimeInterface $dateTime
     *
     * @see PDFReference v1.7 3.8.3 Dates
     * @erturn string
     */
    public static function createPDFStringFromDateTime(\DateTimeInterface $dateTime)
    {
        if ($dateTime->getOffset() === 0) {
            return 'D: ' . $dateTime->format('YmdHis') . 'Z';
        }

        return 'D: ' . str_replace(':', '\'', $dateTime->format('YmdHisP') . '\'');
    }
}
