<?php
/**
 * This file contains functionality relating to the banned league players
 *
 * @package    BZiON\Models
 * @license    https://github.com/allejo/bzion/blob/master/LICENSE.md GNU General Public License Version 3
 */

/**
 * A ban imposed by an admin on a player
 * @package BZiON\Models
 */
class Ban extends UrlModel implements NamedModel
{
    /**
     * The id of the banned player
     * @var int
     */
    protected $player;

    /**
     * The ban expiration date
     * @var TimeDate
     */
    protected $expiration;

    /**
     * The message that will appear when a player is denied connecting to a game server
     * @var string
     */
    protected $server_message;

    /**
     * The ban reason
     * @var string
     */
    protected $reason;

    /**
     * The ban creation date
     * @var TimeDate
     */
    protected $created;

    /**
     * The date the ban was last updated
     * @var TimeDate
     */
    protected $updated;

    /**
     * The id of the ban author
     * @var int
     */
    protected $author;

    /**
     * Set to true when a player should NOT be penalized throughout the website while this ban is active
     * @var bool
     */
    protected $is_soft_ban;

    /**
     * The IP of the banned player if the league would like to implement a global ban list
     * @var string[]
     */
    protected $ipAddresses;

    const DEFAULT_STATUS = 'public';

    const DELETED_COLUMN = 'is_deleted';

    /**
     * The name of the database table used for queries
     */
    const TABLE = "bans";

    const CREATE_PERMISSION = Permission::ADD_BAN;
    const EDIT_PERMISSION = Permission::EDIT_BAN;
    const SOFT_DELETE_PERMISSION = Permission::SOFT_DELETE_BAN;
    const HARD_DELETE_PERMISSION = Permission::HARD_DELETE_BAN;

    /**
     * {@inheritdoc}
     */
    protected function assignResult($ban)
    {
        $this->player = $ban['player'];
        $this->expiration = ($ban['expiration'] === null) ? null : TimeDate::fromMysql($ban['expiration']);
        $this->server_message = $ban['server_message'];
        $this->reason = $ban['reason'];
        $this->is_soft_ban = $ban['is_soft_ban'];
        $this->created = TimeDate::fromMysql($ban['created']);
        $this->updated = TimeDate::fromMysql($ban['updated']);
        $this->author = $ban['author'];
        $this->is_deleted = $ban['is_deleted'];
    }

    /**
     * {@inheritdoc}
     */
    protected function assignLazyResult($result)
    {
        $this->ipAddresses = self::fetchIds('WHERE ban_id = ?', [$this->getId()], 'banned_ips', 'ip_address');
    }

    /**
     * Get the IP address of the banned player
     * @return string[]
     */
    public function getIpAddresses()
    {
        $this->lazyLoad();

        return $this->ipAddresses;
    }

    /**
     * Add an IP to the ban
     *
     * @param string $ipAddress The IP to add to a ban
     */
    public function addIP($ipAddress)
    {
        $this->lazyLoad();

        $this->ipAddresses[] = $ipAddress;
        $this->db->execute('INSERT IGNORE INTO banned_ips (id, ban_id, ip_address) VALUES (NULL, ?, ?)', [$this->getId(), $ipAddress]);
    }

    /**
     * Remove an IP from the ban
     *
     * @param string $ipAddress The IP to remove from the ban
     */
    public function removeIP($ipAddress)
    {
        $this->lazyLoad();

        // Remove $ipAddress from $this->ipAddresses
        $this->ipAddresses = array_diff($this->ipAddresses, [$ipAddress]);
        $this->db->execute('DELETE FROM banned_ips WHERE ban_id = ? AND ip_address = ?', [$this->getId(), $ipAddress]);
    }

    /**
     * Set the IP addresses of the ban
     *
     * @todo   Is it worth making this faster?
     * @param  string[] $ipAddresses The new IP addresses of the ban
     * @return self
     */
    public function setIPs($ipAddresses)
    {
        $this->lazyLoad();

        $oldIPs = $this->ipAddresses;
        $this->ipAddresses = $ipAddresses;

        $newIPs     = array_diff($ipAddresses, $oldIPs);
        $removedIPs = array_diff($oldIPs, $ipAddresses);

        foreach ($newIPs as $ip) {
            $this->addIP($ip);
        }

        foreach ($removedIPs as $ip) {
            $this->removeIP($ip);
        }

        return $this;
    }

    /**
     * Get the user who imposed the ban
     * @return Player The banner
     */
    public function getAuthor()
    {
        return Player::get($this->author);
    }

    /**
     * Get the creation time of the ban
     * @return TimeDate The creation time
     */
    public function getCreated()
    {
        return $this->created->copy();
    }

    /**
     * Get the expiration time of the ban
     * @return TimeDate
     */
    public function getExpiration()
    {
        return $this->expiration->copy();
    }

    /**
     * Get the ban's description
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Get the ban summary that will appear when a player is denied access to a league server on join
     * @return string The ban summary
     */
    public function getServerMessage()
    {
        return $this->server_message;
    }

    /**
     * Get the time when the ban was last updated
     * @return TimeDate
     */
    public function getUpdated()
    {
        return $this->updated->copy();
    }

    /**
     * Get the ID of the player who was banned
     *
     * @return int The ID of the victim of the ban
     */
    public function getVictimID()
    {
        return $this->player;
    }

    /**
     * Get the player who was banned
     *
     * @return Player The banned player
     */
    public function getVictim()
    {
        return Player::get($this->player);
    }

    /**
     * Get whether or not the player should be penalized on the site.
     *
     * @return bool True if the player should NOT be penalized on the site.
     */
    public function isSoftBan()
    {
        return (bool) $this->is_soft_ban;
    }

    /**
     * Calculate whether a ban has expired or not.
     *
     * @return bool True if the ban's expiration time has already passed
     */
    public function isExpired()
    {
        if ($this->expiration === null) {
            return false;
        }

        return TimeDate::now()->gte($this->expiration);
    }

    /**
     * Check whether the ban will expire automatically
     *
     * @return bool
     */
    public function isPermanent()
    {
        return $this->expiration === null;
    }

    /**
     * Mark the ban as expired
     *
     * @return self
     */
    public function expire()
    {
        $this->setExpiration(TimeDate::now());

        return $this;
    }

    /**
     * Set the expiration date of the ban
     *
     * @param  TimeDate $expiration The expiration
     *
     * @return self
     */
    public function setExpiration($expiration)
    {
        if ($expiration !== null) {
            $expiration = TimeDate::from($expiration);
        }

        return $this->updateProperty($this->expiration, 'expiration', $expiration);
    }

    /**
     * Set the server message of the ban
     *
     * @param  string $message The new server message
     *
     * @return self
     */
    public function setServerMessage($message)
    {
        return $this->updateProperty($this->server_message, 'server_message', $message);
    }

    /**
     * Set the reason of the ban
     * @param  string $reason The new ban reason
     * @return self
     */
    public function setReason($reason)
    {
        return $this->updateProperty($this->reason, 'reason', $reason);
    }

    /**
     * Update the last edit timestamp
     * @return self
     */
    public function updateEditTimestamp()
    {
        return $this->updateProperty($this->updated, 'updated', TimeDate::now());
    }

    /**
     * Set whether the ban's victim is allowed to enter a match server
     *
     * @param  bool $is_soft_ban
     *
     * @return self
     */
    public function setSoftBan($is_soft_ban)
    {
        return $this->updateProperty($this->is_soft_ban, 'is_soft_ban', (bool) $is_soft_ban);
    }

    /**
     * Add a new ban.
     *
     * @param int        $playerID    The ID of the victim of the ban
     * @param int        $authorID    The ID of the player responsible for the ban
     * @param mixed|null $expiration  The expiration of the ban (set to NULL for permanent ban)
     * @param string     $reason      The full reason for the ban (supports markdown)
     * @param string     $serverMsg   A summary of the ban to be displayed on server banlists (max 150 characters)
     * @param string[]   $ipAddresses An array of IPs that have been banned
     * @param bool       $is_soft_ban Whether or not the player is allowed to join match servers
     *
     * @return Ban An object representing the ban that was just entered or false if the ban was not created
     */
    public static function addBan($playerID, $authorID, $expiration, $reason, $serverMsg = '', $ipAddresses = [], $is_soft_ban = false)
    {
        if ($expiration !== null) {
            $expiration = TimeDate::from($expiration)->toMysql();
        }

        $ban = self::create(array(
            'player'         => $playerID,
            'expiration'     => $expiration,
            'server_message' => $serverMsg,
            'reason'         => $reason,
            'is_soft_ban'    => $is_soft_ban,
            'author'         => $authorID,
        ), ['created', 'updated']);

        if (!is_array($ipAddresses)) {
            $ipAddresses = [$ipAddresses];
        }

        foreach ($ipAddresses as $ip) {
            $ban->addIP($ip);
        }

        return $ban;
    }

    /**
     * Get a query builder for news
     *
     * @return QueryBuilder
     */
    public static function getQueryBuilder()
    {
        return new QueryBuilder('Ban', array(
            'columns' => array(
                'player'  => 'player',
                'status'  => 'status',
                'updated' => 'updated'
            ),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Ban against ' . $this->getVictim()->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        parent::delete();
    }

    /**
     * {@inheritdoc}
     */
    public static function getLazyColumns()
    {
        return null;
    }

    /**
     * Get all the bans in the database that aren't disabled or deleted
     *
     * @return Ban[] An array of ban objects
     */
    public static function getBans()
    {
        return self::arrayIdToModel(self::fetchIds('ORDER BY updated DESC'));
    }

    /**
     * Get an active ban for the player
     *
     * @param  int      $playerId The player's ID
     *
     * @return Ban|null null if the player isn't currently banned
     */
    public static function getBan($playerId)
    {
        $bans = self::fetchIdsFrom('player', [$playerId], false, 'AND (expiration IS NULL OR expiration > UTC_TIMESTAMP())');

        if (empty($bans)) {
            return null;
        }

        return self::get($bans[0]);
    }
}
