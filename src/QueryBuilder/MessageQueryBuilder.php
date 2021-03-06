<?php
/**
 * This file contains a class to quickly generate database queries for message
 *
 * @package    BZiON\Models\QueryBuilder
 * @license    https://github.com/allejo/bzion/blob/master/LICENSE.md GNU General Public License Version 3
 */

/**
 * This class can be used to search for messages with specific characteristics
 * in the database.
 *
 * @package    BZiON\Models\QueryBuilder
 */
class MessageQueryBuilder extends QueryBuilder
{
    /**
     * Only include messages
     *
     * @return self
     */
    public function messagesOnly()
    {
        $this->whereConditions[] = 'event_type IS NULL';

        return $this;
    }

    /**
     * Only include conversation events
     *
     * @return self
     */
    public function eventsOnly()
    {
        $this->whereConditions[] = 'event_type IS NOT NULL';

        return $this;
    }

    /**
     * Only return messages that are sent from/to a specific player
     *
     * @param  Player $player The player related to the messages
     * @return self
     */
    public function forPlayer($player)
    {
        $this->extras .= '
            LEFT JOIN `conversations` ON conversations.id = messages.conversation_to
            LEFT JOIN `player_conversations` ON player_conversations.conversation=conversations.id
        ';

        $this->column('player_conversations.player')->is($player);
        $this->column('conversations.status')->isOneOf(Conversation::getActiveStatuses());

        return $this;
    }

    /*
    * Locate messages that contain keywords in a search string
    *
    * @param  string $query The search query
    * @return self
    */
    public function search($query)
    {
        $keywords = preg_split('/\s+/', trim($query));

        $query = "";

        $first = true;
        foreach ($keywords as $keyword) {
            if (!$first) {
                $query .= ' AND ';
            } else {
                $first = false;
            }

            $query .= "(message LIKE CONCAT('%', ?, '%'))";
            $this->parameters[] = $keyword;
        }

        $this->whereConditions[] = $query;

        return $this;
    }
}
