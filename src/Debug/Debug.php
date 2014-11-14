<?php
/**
 * This file contains functionality related to debugging, logging and timing actions
 *
 * @package    BZiON
 * @license    https://github.com/allejo/bzion/blob/master/LICENSE.md GNU General Public License Version 3
 */

namespace BZIon\Debug;

/**
 * Useful debugging functions (will only waste time on a development environment)
 */
abstract class Debug
{
    /**
     * Start counting time for an event
     * @param  string $event The event's name
     * @return void
     */
    public static function startStopwatch($event)
    {
        if (!\Service::getContainer())
            return;

        $stopwatch = \Service::getContainer()->get('debug.stopwatch', null);

        if ($stopwatch)
            $stopwatch->start($event);
    }

    /**
     * Stop counting time for an event and get its duration
     * @param  string $event The event's name
     * @return int    The time in milliseconds
     */
    public static function finishStopwatch($event)
    {
        if (!\Service::getContainer())
            return 0;

        $stopwatch = \Service::getContainer()->get('debug.stopwatch', null);

        if (!$stopwatch)
            return 0;

        $event = $stopwatch->stop($event);
        $periods = $event->getPeriods();
        $duration = end($periods)->getDuration();

        return $duration;
    }

    /**
     * Log a database query
     * @param string         $query    The query
     * @param string|boolean $types    The types of the query
     * @param string|boolean $params   The parameters of the query
     * @param int            $duration The duration of the query in milliseconds
     */
    public static function logQuery($query, $types, $params, $duration)
    {
        $queryType = strtok($query, ' ');

        self::log("Database $queryType query", array(
            "query" => $query,
            "params" => $params,
            "duration" => "$duration ms"
        ), 'mysql');

        $collector = \Service::getContainer()->get('data_collector.bzion_database_collector', null);

        if ($collector) {
            $collector->logQuery($query);
        }
    }

    /**
     * Log a debug message
     * @param  string $message The message to return
     * @param  array  $context Any additional information to show
     * @param  string $channel The channel to store the log into
     * @return void
     */
    public static function log($message, array $context=array(), $channel='app')
    {
        if (!\Service::getContainer())
            return;

        $logger = \Service::getContainer()->get("monolog.logger.$channel", null);

        if (!$logger)
            return;

        $logger->debug($message, $context);
    }
}