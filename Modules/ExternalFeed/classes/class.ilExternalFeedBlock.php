<?php

/* Copyright (c) 1998-2021 ILIAS open source, GPLv3, see LICENSE */


/**
 * Custom block for external feeds.
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilExternalFeedBlock extends ilCustomBlock
{

    /**
     * @var Logger
     */
    protected $log;


    protected $feed_url;

    /**
    * Constructor.
    *
    * @param	int	$a_id
    */
    public function __construct($a_id = 0)
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->log = $DIC["ilLog"];
        if ($a_id > 0) {
            $this->setId($a_id);
            $this->read();
        }
    }

    /**
    * Set FeedUrl.
    *
    * @param	string	$a_feed_url	URL of the external news feed.
    */
    public function setFeedUrl($a_feed_url)
    {
        $this->feed_url = $a_feed_url;
    }

    /**
     * Check if feed url is local
     * @param string
     * @return bool
     */
    public function isFeedUrlLocal($url)
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST));
        if (is_int(strpos($url, ".localhost")) || trim($host) == "localhost") {
            return true;
        }
        $ip = gethostbyname($host);

        $res = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        if (in_array($res, [false, ""])) {
            return true;
        }
        return false;
    }

    /**
    * Get FeedUrl.
    *
    * @return	string	URL of the external news feed.
    */
    public function getFeedUrl()
    {
        return $this->feed_url;
    }

    /**
    * Create new item.
    *
    */
    public function create() : void
    {
        $ilDB = $this->db;
        $ilLog = $this->log;
        
        parent::create();
        
        $query = "INSERT INTO il_external_feed_block (" .
            " id" .
            ", feed_url" .
            " ) VALUES (" .
            $ilDB->quote($this->getId(), "integer")
            . "," . $ilDB->quote($this->getFeedUrl(), "text") . ")";
        $ilDB->manipulate($query);
    }

    /**
    * Read item from database.
    *
    */
    public function read() : void
    {
        $ilDB = $this->db;
        
        parent::read();
        
        $query = "SELECT * FROM il_external_feed_block WHERE id = " .
            $ilDB->quote($this->getId(), "integer");
        $set = $ilDB->query($query);
        $rec = $ilDB->fetchAssoc($set);

        $this->setFeedUrl($rec["feed_url"]);
    }

    /**
    * Update item in database.
    *
    */
    public function update() : void
    {
        $ilDB = $this->db;
        
        parent::update();
        
        $query = "UPDATE il_external_feed_block SET " .
            " feed_url = " . $ilDB->quote($this->getFeedUrl(), "text") .
            " WHERE id = " . $ilDB->quote($this->getId(), "integer");
        
        $ilDB->manipulate($query);
    }

    /**
    * Delete item from database.
    *
    */
    public function delete() : void
    {
        $ilDB = $this->db;
        
        parent::delete();
        
        $query = "DELETE FROM il_external_feed_block" .
            " WHERE id = " . $ilDB->quote($this->getId(), "integer");
        
        $ilDB->manipulate($query);
    }
}
