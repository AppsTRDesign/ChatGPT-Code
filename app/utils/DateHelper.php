<?php
class DateHelper
{
    public static function formatForTimezone(string $datetime, string $timezone): string
    {
        $date = new DateTime($datetime, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($timezone));
        return $date->format('Y-m-d H:i:s');
    }
}
